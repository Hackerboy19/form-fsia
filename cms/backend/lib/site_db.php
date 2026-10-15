<?php
declare(strict_types=1);

/** The website's folder (httpdocs). The CMS lives in httpdocs/cms/backend. */
function site_root(): string
{
    return rtrim((string) (cms_config()['site_root'] ?? dirname(__DIR__, 3)), '/');
}

/** Runs a query on the site's database. The CMS shares it on most setups; otherwise
    the site's own config.php (which defines $connect) is used. */
function site_db_rows(string $sql, array $params = []): array
{
    static $link = null;
    if ($link === null) {
        try {
            $has = db()->query("SHOW TABLES LIKE 'registration'")->fetchColumn();
            if ($has) {
                $link = db();
            }
        } catch (Throwable $e) {
            $link = null;
        }
        if ($link === null) {
            $cfg = site_root() . '/config.php';
            if (!is_file($cfg)) {
                throw new ApiError('Cannot reach the profiles database (no config.php in the site folder).', 500);
            }
            $connect = (function (string $cfg) {
                ob_start();
                include $cfg;
                ob_end_clean();
                return $connect ?? null;
            })($cfg);
            if (!$connect instanceof mysqli) {
                throw new ApiError('Cannot reach the profiles database.', 500);
            }
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            mysqli_set_charset($connect, 'utf8mb4');
            $link = $connect;
        }
    }
    if ($link instanceof PDO) {
        $st = $link->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    $st = $link->prepare($sql);
    if ($params) {
        $st->bind_param(str_repeat('s', count($params)), ...array_map('strval', $params));
    }
    $st->execute();
    $res = $st->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
