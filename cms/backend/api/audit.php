<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * GET /api/audit.php?action=list&q=&user=&endpoint=&result=ok|failed|denied&days=&page=
 * Activity log: every change made through the CMS, refused requests, sign-ins
 * and file downloads. Newest first, 50 per page.
 */

const AUDIT_PAGE_SIZE = 50;

respond(function () {
    require_admin();
    if (request_method() !== 'GET' || query_param('action') !== 'list') {
        throw new ApiError('Unknown action', 400);
    }

    $where = [];
    $params = [];
    $q = (string) (query_param('q') ?? '');
    if ($q !== '') {
        $where[] = '(target LIKE ? OR summary LIKE ? OR action LIKE ?)';
        $like = '%' . addcslashes(mb_substr($q, 0, 100), '%_\\') . '%';
        array_push($params, $like, $like, $like);
    }
    if (($u = (string) (query_param('user') ?? '')) !== '') {
        $where[] = 'username = ?';
        $params[] = mb_substr($u, 0, 64);
    }
    if (($ep = (string) (query_param('endpoint') ?? '')) !== '') {
        $where[] = 'endpoint = ?';
        $params[] = mb_substr($ep, 0, 64);
    }
    $result = query_param('result');
    if ($result === 'ok') {
        $where[] = 'status < 400';
    } elseif ($result === 'denied') {
        $where[] = 'status IN (401, 403)';
    } elseif ($result === 'failed') {
        $where[] = 'status >= 400';
    }
    $days = (int) (query_param('days') ?? 0);
    if ($days > 0) {
        $where[] = 'created_at >= ?';
        $params[] = date('Y-m-d H:i:s', time() - min($days, 3650) * 86400);
    }
    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int) (query_param('page') ?? 1));

    try {
        $st = db()->prepare("SELECT COUNT(*) FROM cms_audit_log $sqlWhere");
        $st->execute($params);
        $total = (int) $st->fetchColumn();
        $st = db()->prepare("SELECT id, created_at, username, role, ip, method, endpoint, action, target, status, summary
                             FROM cms_audit_log $sqlWhere ORDER BY id DESC LIMIT " . AUDIT_PAGE_SIZE . ' OFFSET ' . (($page - 1) * AUDIT_PAGE_SIZE));
        $st->execute($params);
        $rows = $st->fetchAll();
        $users = db()->query('SELECT DISTINCT username FROM cms_audit_log WHERE username <> \'\' ORDER BY username')->fetchAll(PDO::FETCH_COLUMN);
        $endpoints = db()->query('SELECT DISTINCT endpoint FROM cms_audit_log ORDER BY endpoint')->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        if (!rbac_missing_table($e)) {
            throw $e;
        }
        // Nothing has been logged yet.
        return ['rows' => [], 'total' => 0, 'page' => 1, 'page_size' => AUDIT_PAGE_SIZE, 'users' => [], 'endpoints' => []];
    }
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['status'] = (int) $r['status'];
    }
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'page_size' => AUDIT_PAGE_SIZE, 'users' => $users, 'endpoints' => $endpoints];
});
