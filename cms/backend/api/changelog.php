<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/site_db.php';

/*
 * Profile change log (admin only). Database triggers on the `registration` table
 * record every change to a profile, whichever page or tool made it (old admin,
 * the profile owner, starindia, phpMyAdmin): when, which fields, before -> after,
 * and who (from @fsia_actor, which config.php can set; see the snippet in the admin).
 *
 * GET  /api/changelog.php?action=status
 * POST /api/changelog.php?action=install    -> creates the log table + triggers (after a self-test)
 * POST /api/changelog.php?action=uninstall  -> removes the triggers; the log stays
 * GET  /api/changelog.php?action=list&q=&days=&field=&page=
 */

const CL_LOG = 'fsia_profile_changes';
const CL_TRIGGERS = ['fsia_log_profile_update', 'fsia_log_profile_create', 'fsia_log_profile_delete'];
const CL_TEXT_LIMIT = 300;  // characters kept of each before/after value
const CL_PAGE = 50;

/* Columns that change on their own (page views, logins): never logged. */
function chl_ignored(string $col): bool
{
    return in_array(strtolower($col), ['id', 'completion', 'viewpage', 'devicetoken'], true)
        || (bool) preg_match('/(^|_)(otp|token|last_?login|login_?time|views?|view_?count|visits?|hits?)$/i', $col);
}

/* Columns whose values are personal or secret: the log notes the change, not the value. */
function chl_hidden(string $col): bool
{
    return (bool) preg_match('/pass(word)?|^email$|^mobile$|phone|whatsapp|otp/i', $col);
}

function chl_ident(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_$]+$/', $name)) {
        throw new ApiError("Unexpected column name: $name", 500);
    }
    return "`$name`";
}

function chl_columns(string $table): array
{
    return array_column(site_db_rows('SHOW COLUMNS FROM ' . chl_ident($table)), 'Field');
}

/** A value as a JSON string (or the word null), cut short and in utf8mb4 whatever the column's charset. */
function chl_json(string $expr, string $col): string
{
    if (chl_hidden($col)) return "'\"(hidden)\"'";
    return "COALESCE(JSON_QUOTE(CONVERT(LEFT($expr, " . CL_TEXT_LIMIT . ") USING utf8mb4)), 'null')";
}

function chl_create_log_table(): void
{
    site_db_exec('CREATE TABLE IF NOT EXISTS ' . CL_LOG . ' (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        action VARCHAR(10) NOT NULL,
        fields TEXT NULL,
        changes MEDIUMTEXT NULL,
        actor VARCHAR(255) NULL,
        db_user VARCHAR(255) NULL,
        changed_at DATETIME NOT NULL,
        KEY profile_time (profile_id, changed_at),
        KEY changed_at (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/** The three CREATE TRIGGER statements for a table with these columns. */
function chl_trigger_sql(string $table, array $cols, string $suffix = ''): array
{
    $t = chl_ident($table);
    $tracked = array_values(array_filter($cols, fn ($c) => !chl_ignored($c)));
    if (!$tracked) throw new ApiError('No columns to track', 500);

    $names = [];
    $pairs = [];
    foreach ($tracked as $c) {
        $q = chl_ident($c);
        $cond = "NOT (OLD.$q <=> NEW.$q)";
        $names[] = "IF($cond, '$c', NULL)";
        $pairs[] = "IF($cond, CONCAT('\"$c\":[', " . chl_json("OLD.$q", $c) . ", ',', " . chl_json("NEW.$q", $c) . ", ']'), NULL)";
    }
    $who = 'LEFT(@fsia_actor, 255), LEFT(USER(), 255), NOW()';
    $nameCols = array_values(array_intersect(['first_name', 'last_name', 'url', 'regtype', 'status'], $cols));
    $snap = function (string $row) use ($nameCols) {
        if (!$nameCols) return "'{}'";
        $parts = array_map(fn ($c) => "CONCAT('\"$c\":', " . chl_json("$row." . chl_ident($c), $c) . ')', $nameCols);
        return "CONCAT('{', CONCAT_WS(',', " . implode(', ', $parts) . "), '}')";
    };

    return [
        'CREATE TRIGGER ' . CL_TRIGGERS[0] . "$suffix AFTER UPDATE ON $t FOR EACH ROW
BEGIN
  DECLARE f TEXT;
  SET f = CONCAT_WS(',', " . implode(', ', $names) . ");
  IF f <> '' THEN
    INSERT INTO " . CL_LOG . " (profile_id, action, fields, changes, actor, db_user, changed_at)
    VALUES (NEW.`id`, 'update', f, CONCAT('{', CONCAT_WS(',', " . implode(', ', $pairs) . "), '}'), $who);
  END IF;
END",
        'CREATE TRIGGER ' . CL_TRIGGERS[1] . "$suffix AFTER INSERT ON $t FOR EACH ROW
  INSERT INTO " . CL_LOG . " (profile_id, action, fields, changes, actor, db_user, changed_at)
  VALUES (NEW.`id`, 'create', NULL, " . $snap('NEW') . ", $who)",
        'CREATE TRIGGER ' . CL_TRIGGERS[2] . "$suffix AFTER DELETE ON $t FOR EACH ROW
  INSERT INTO " . CL_LOG . " (profile_id, action, fields, changes, actor, db_user, changed_at)
  VALUES (OLD.`id`, 'delete', NULL, " . $snap('OLD') . ", $who)",
    ];
}

function chl_installed(): array
{
    $rows = site_db_rows('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = ?', ['registration']);
    return array_values(array_intersect(CL_TRIGGERS, array_column($rows, 'TRIGGER_NAME')));
}

function chl_drop_triggers(string $suffix = ''): void
{
    foreach (CL_TRIGGERS as $name) site_db_exec("DROP TRIGGER IF EXISTS $name$suffix");
}

/**
 * Tries the triggers on a throw-away copy of the registration table (same columns and
 * charsets, one copied row) before they go on the real one, so a database that cannot
 * run them never ends up with triggers that would make profile saves fail.
 */
function chl_self_test(array $cols): void
{
    $test = 'fsia_cms_trigger_test';
    $mark = 'cms-self-test';
    site_db_exec("DROP TABLE IF EXISTS $test");
    site_db_exec("CREATE TABLE $test LIKE registration");
    site_db_exec("SET @fsia_actor = '$mark'");
    try {
        foreach (chl_trigger_sql($test, $cols, '_test') as $sql) site_db_exec($sql);
        site_db_exec("INSERT INTO $test SELECT * FROM registration ORDER BY id DESC LIMIT 1");
        $row = site_db_rows("SELECT `id` FROM $test LIMIT 1")[0] ?? null;
        if ($row) {
            // Flip every tracked text column between 'x' and 'y' (fits any length); the copy is thrown away.
            // JSON columns (MariaDB: longtext + CHECK json_valid) only accept valid JSON, so they flip between '"x"' and '"y"'.
            $create = (string) (array_values(site_db_rows("SHOW CREATE TABLE $test")[0] ?? [])[1] ?? '');
            preg_match_all('/json_valid\(`([^`]+)`\)/i', $create, $m);
            $jsonCols = array_map('strtolower', $m[1]);
            $sets = [];
            foreach (site_db_rows("SHOW COLUMNS FROM $test") as $t) {
                $isJson = stripos($t['Type'], 'json') === 0 || in_array(strtolower($t['Field']), $jsonCols, true);
                if (chl_ignored($t['Field']) || (!$isJson && !preg_match('/char|text/i', $t['Type']))) continue;
                $c = chl_ident($t['Field']);
                $sets[] = $isJson ? "$c = IF($c = '\"x\"', '\"y\"', '\"x\"')" : "$c = IF($c = 'x', 'y', 'x')";
            }
            if ($sets) site_db_exec("UPDATE $test SET " . implode(', ', $sets) . ' WHERE `id` = ' . (int) $row['id']);
            site_db_exec("DELETE FROM $test WHERE `id` = " . (int) $row['id']);
            $n = (int) (site_db_rows('SELECT COUNT(*) AS n FROM ' . CL_LOG . ' WHERE actor = ?', [$mark])[0]['n'] ?? 0);
            if ($n < 2) throw new ApiError('Self-test failed: the test changes were not recorded.', 500);
        }
    } finally {
        site_db_exec("DROP TABLE IF EXISTS $test");
        site_db_exec('DELETE FROM ' . CL_LOG . " WHERE actor = '$mark'");
        site_db_exec('SET @fsia_actor = NULL');
    }
}

respond(function () {
    require_admin();
    $action = query_param('action') ?? '';
    $method = request_method();

    if ($method === 'GET' && $action === 'status') {
        $has = site_db_has_table(CL_LOG);
        $count = $has ? (int) site_db_rows('SELECT COUNT(*) AS n FROM ' . CL_LOG)[0]['n'] : 0;
        $last = $has ? (site_db_rows('SELECT MAX(changed_at) AS t FROM ' . CL_LOG)[0]['t'] ?? null) : null;
        $withActor = $has ? (int) site_db_rows('SELECT COUNT(*) AS n FROM ' . CL_LOG . " WHERE actor IS NOT NULL AND actor <> ''")[0]['n'] : 0;
        return [
            'on'          => count(chl_installed()) === count(CL_TRIGGERS),
            'triggers'    => chl_installed(),
            'log_table'   => $has,
            'count'       => $count,
            'last'        => $last,
            'with_actor'  => $withActor,
            'version'     => site_db_rows('SELECT VERSION() AS v')[0]['v'] ?? '',
        ];
    }

    if ($method === 'POST' && $action === 'install') {
        $v = (string) (site_db_rows('SELECT VERSION() AS v')[0]['v'] ?? '');
        $maria = stripos($v, 'mariadb') !== false;
        $num = preg_replace('/[^0-9.].*$/', '', $v);
        if (($maria && version_compare($num, '10.2.3', '<')) || (!$maria && version_compare($num, '5.7.8', '<'))) {
            throw new ApiError("The database ($v) is too old for the change log (needs MySQL 5.7.8+ or MariaDB 10.2.3+).", 422);
        }
        $cols = chl_columns('registration');
        if (!in_array('id', $cols, true)) throw new ApiError('The registration table has no id column.', 422);
        try {
            chl_create_log_table();
            chl_self_test($cols);
            chl_drop_triggers();
            foreach (chl_trigger_sql('registration', $cols) as $sql) site_db_exec($sql);
        } catch (ApiError $e) {
            chl_drop_triggers();
            throw $e;
        } catch (Throwable $e) {
            chl_drop_triggers();
            error_log('[fsia-cms] change log install: ' . $e->getMessage());
            $msg = $e->getMessage();
            if (stripos($msg, 'SUPER') !== false || stripos($msg, 'TRIGGER command denied') !== false) {
                throw new ApiError('The database user may not create triggers. In Plesk, give the database user full rights, or ask the host to allow triggers.', 422);
            }
            throw new ApiError('Could not turn on the change log: ' . $msg, 500);
        }
        $tracked = count(array_filter($cols, fn ($c) => !chl_ignored($c)));
        return ['on' => true, 'tracked_columns' => $tracked];
    }

    if ($method === 'POST' && $action === 'uninstall') {
        chl_drop_triggers();
        return ['on' => false];
    }

    if ($method === 'GET' && $action === 'list') {
        if (!site_db_has_table(CL_LOG)) return ['rows' => [], 'total' => 0, 'page' => 1, 'page_size' => CL_PAGE];
        $where = ['1=1'];
        $params = [];
        $q = trim((string) query_param('q'));
        if ($q !== '') {
            if (ctype_digit($q)) { $where[] = 'c.profile_id = ?'; $params[] = (int) $q; }
            else {
                $where[] = "(CONCAT_WS(' ', r.first_name, r.last_name) LIKE ? OR r.url LIKE ? OR c.changes LIKE ?)";
                array_push($params, "%$q%", "%$q%", "%$q%");
            }
        }
        $days = (int) query_param('days');
        if ($days > 0) { $where[] = 'c.changed_at >= NOW() - INTERVAL ? DAY'; $params[] = $days; }
        $field = (string) query_param('field');
        if ($field !== '' && preg_match('/^\w+$/', $field)) { $where[] = 'FIND_IN_SET(?, c.fields)'; $params[] = $field; }
        $act = (string) query_param('type');
        if (in_array($act, ['update', 'create', 'delete'], true)) { $where[] = 'c.action = ?'; $params[] = $act; }

        $from = ' FROM ' . CL_LOG . ' c LEFT JOIN registration r ON r.id = c.profile_id WHERE ' . implode(' AND ', $where);
        $total = (int) (site_db_rows('SELECT COUNT(*) AS n' . $from, $params)[0]['n'] ?? 0);
        $page = max(1, (int) query_param('page'));
        $rows = site_db_rows('SELECT c.id, c.profile_id, c.action, c.fields, c.changes, c.actor, c.db_user, c.changed_at,
                r.first_name, r.last_name, r.url' . $from . ' ORDER BY c.changed_at DESC, c.id DESC LIMIT ' . CL_PAGE . ' OFFSET ' . (($page - 1) * CL_PAGE), $params);
        $out = array_map(function ($r) {
            $changes = json_decode((string) $r['changes'], true) ?: [];
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($name === '' && isset($changes['first_name'])) $name = trim((string) $changes['first_name'] . ' ' . (string) ($changes['last_name'] ?? ''));
            $url = $r['url'] ?? ($changes['url'] ?? '');
            return [
                'id' => (int) $r['id'], 'profile_id' => (int) $r['profile_id'], 'action' => $r['action'],
                'name' => $name ?: '(profile #' . $r['profile_id'] . ')',
                'link' => $url ? 'https://www.fsia.in/' . ltrim((string) $url, '/') : '',
                'fields' => $r['fields'] ? explode(',', $r['fields']) : [],
                'changes' => $changes,
                'who' => site_change_who($r['actor'], $r['db_user']),
                'at' => $r['changed_at'],
            ];
        }, $rows);
        return ['rows' => $out, 'total' => $total, 'page' => $page, 'page_size' => CL_PAGE];
    }

    throw new ApiError('Unknown action', 400);
});
