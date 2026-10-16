<?php
declare(strict_types=1);

/** The website's folder (httpdocs). The CMS lives in httpdocs/cms/backend. */
function site_root(): string
{
    return rtrim((string) (cms_config()['site_root'] ?? dirname(__DIR__, 3)), '/');
}

/** Connection to the site's database. The CMS shares it on most setups; otherwise
    the site's own config.php (which defines $connect) is used. PDO or mysqli. */
function site_db_link(): PDO|mysqli
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
    return $link;
}

/** Runs a query on the site's database and returns all rows. */
function site_db_rows(string $sql, array $params = []): array
{
    $link = site_db_link();
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

/** Runs a statement that cannot be prepared (CREATE TRIGGER, DROP TRIGGER, CREATE TABLE). */
function site_db_exec(string $sql): void
{
    $link = site_db_link();
    if ($link instanceof PDO) {
        $link->exec($sql);
    } else {
        $link->query($sql);
    }
}

/** Does a table exist in the site's database? */
function site_db_has_table(string $table): bool
{
    return (bool) site_db_rows('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
}

/** Turns "host/script.php · userid=12" into something a person recognises. */
function site_change_who(?string $actor, ?string $dbUser): string
{
    if ($actor === null || $actor === '') {
        return 'Unknown page' . ($dbUser ? " (database user $dbUser)" : '');
    }
    [$where, $login] = array_pad(explode(' · ', $actor, 2), 2, '');
    $label = $where;
    if (preg_match('#/fsiaadmm21/#i', $where)) $label = 'Old admin panel (' . basename($where) . ')';
    elseif (preg_match('#^starindia\.#i', $where)) $label = 'starindia.fsia.in (' . basename($where) . ')';
    elseif (preg_match('#/cms/#i', $where)) $label = 'CMS admin';
    return $label . ($login !== '' ? " · $login" : '');
}
