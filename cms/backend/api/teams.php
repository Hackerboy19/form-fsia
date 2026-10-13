<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * GET    /api/teams.php                         -> active members, grouped order
 * GET    /api/teams.php?category=Mentors        -> filter by category
 * GET    /api/teams.php?include_inactive=1      -> also hidden members (admin)
 * GET    /api/teams.php?id=5                    -> one member
 * POST   /api/teams.php                         -> create (admin)
 * PUT    /api/teams.php?id=5                    -> update; send only the fields to change (admin)
 * DELETE /api/teams.php?id=5                    -> delete (admin)
 */

const TEAM_CATEGORIES = ['Leadership', 'Core Team', 'Mentors', 'Anchors', 'Media'];
const SOCIAL_KEYS = ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'website'];

function team_row(array $r): array
{
    $r['id'] = (int) $r['id'];
    $r['display_order'] = (int) $r['display_order'];
    $r['is_active'] = (bool) $r['is_active'];
    $r['social_links'] = $r['social_links'] ? (json_decode($r['social_links'], true) ?: new stdClass()) : new stdClass();
    return $r;
}

function team_find(int $id, bool $includeInactive): ?array
{
    $sql = 'SELECT * FROM team_members WHERE id = ?' . ($includeInactive ? '' : ' AND is_active = 1');
    $st = db()->prepare($sql);
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? team_row($r) : null;
}

/** Columns of team_members (bio/quote/phone exist only after the 2026-10-13 migration). */
function team_columns(): array
{
    static $cols = null;
    if ($cols === null) {
        $cols = array_column(db()->query('SHOW COLUMNS FROM team_members')->fetchAll(), 'Field');
    }
    return $cols;
}

/** Validates a full member record (existing values merged with the request). */
function team_validate(array $in): array
{
    $v = new Validator($in);
    $row = [
        'name'          => $v->string('name', 150, true),
        'designation'   => $v->string('designation', 150),
        'category'      => $v->enum('category', TEAM_CATEGORIES),
        'image_url'     => $v->url('image_url'),
        'display_order' => $v->int('display_order', 0, -100000, 100000),
        'is_active'     => $v->bool('is_active', true) ? 1 : 0,
    ];

    // Profile text: bio (pop-up paragraph), quote (leader's italic line), phone.
    $extra = [
        'bio'   => $v->string('bio', 2000),
        'quote' => $v->string('quote', 255),
        'phone' => preg_replace('/[^\d+]/', '', (string) $v->string('phone', 30)),
    ];
    if ($extra['phone'] !== '' && !preg_match('/^\+?\d{7,15}$/', $extra['phone'])) {
        $v->addError('phone', 'Use a phone number like 9983599666 or +919983599666');
    }
    $have = team_columns();
    foreach ($extra as $col => $val) {
        if (in_array($col, $have, true)) {
            $row[$col] = $val;
        } elseif ($val !== '') {
            $v->addError($col, 'Run the database update (cms/database/migrations/2026-10-13-team-bio-quote-phone.sql) to save this field');
        }
    }

    $links = $in['social_links'] ?? [];
    if ($links instanceof stdClass) {
        $links = (array) $links;
    }
    $clean = [];
    if (!is_array($links)) {
        $v->addError('social_links', 'Must be an object like {"instagram": "https://..."}');
    } else {
        foreach ($links as $k => $url) {
            if (!in_array($k, SOCIAL_KEYS, true)) {
                $v->addError("social_links.$k", 'Unknown network. Allowed: ' . implode(', ', SOCIAL_KEYS));
                continue;
            }
            $url = is_string($url) ? trim($url) : '';
            if ($url === '') {
                continue;
            }
            if (!is_safe_url($url) || str_starts_with($url, '/') || mb_strlen($url) > 512) {
                $v->addError("social_links.$k", 'Must be a full https:// URL');
                continue;
            }
            $clean[$k] = $url;
        }
    }
    $v->check();
    $row['social_links'] = json_encode((object) $clean, JSON_UNESCAPED_SLASHES);
    return $row;
}

respond(function () {
    switch (request_method()) {
        case 'GET':
            $admin = is_admin();
            $includeInactive = $admin && query_param('include_inactive') === '1';

            if (isset($_GET['id'])) {
                return team_find(query_id(), $includeInactive) ?? throw new ApiError('Member not found', 404);
            }

            $where = [];
            $params = [];
            if (!$includeInactive) {
                $where[] = 'is_active = 1';
            }
            $cat = query_param('category');
            if ($cat !== null && $cat !== '') {
                if (!in_array($cat, TEAM_CATEGORIES, true)) {
                    throw new ApiError('Unknown category');
                }
                $where[] = 'category = ?';
                $params[] = $cat;
            }
            $sql = 'SELECT * FROM team_members'
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                . " ORDER BY FIELD(category, 'Leadership','Core Team','Mentors','Anchors','Media'), display_order, name";
            $st = db()->prepare($sql);
            $st->execute($params);
            return array_map('team_row', $st->fetchAll());

        case 'POST':
            require_admin();
            $row = team_validate(json_body());
            $cols = array_keys($row);
            db()->prepare('INSERT INTO team_members (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
                ->execute(array_values($row));
            return created(team_find((int) db()->lastInsertId(), true));

        case 'PUT':
            require_admin();
            $id = query_id();
            $current = team_find($id, true) ?? throw new ApiError('Member not found', 404);
            $row = team_validate(array_merge($current, json_body()));
            $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($row)));
            db()->prepare("UPDATE team_members SET $set WHERE id = ?")->execute([...array_values($row), $id]);
            return team_find($id, true);

        case 'DELETE':
            require_admin();
            $st = db()->prepare('DELETE FROM team_members WHERE id = ?');
            $st->execute([query_id()]);
            if ($st->rowCount() === 0) {
                throw new ApiError('Member not found', 404);
            }
            return ['deleted' => true];

        default:
            throw new ApiError('Method not allowed', 405);
    }
});
