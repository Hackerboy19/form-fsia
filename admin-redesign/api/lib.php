<?php
/* Shared helpers for the admin API: database, session, CSRF, permissions, activity log, source tables.
   Rules that matter: existing form tables are only ever read; every write goes to the admin_* or
   registration_work tables; every request is checked against the signed-in member's role and states. */
declare(strict_types=1);

const PERMS = [
    'dashboard.view', 'registrations.view', 'registrations.edit', 'registrations.assign', 'registrations.export',
    'registrations.delete', 'registrations.sensitive', 'payments.send', 'payments.confirm', 'pages.view', 'pages.edit',
    'otp.view', 'team.view', 'team.manage',
];
const ROLES = ['admin', 'manager', 'caller', 'content', 'accounts', 'viewer'];
const DEFAULT_PERMS = [
    'manager'  => ['dashboard.view', 'registrations.view', 'registrations.edit', 'registrations.assign', 'registrations.export',
                   'registrations.delete', 'registrations.sensitive', 'payments.send', 'payments.confirm', 'pages.view', 'pages.edit', 'otp.view', 'team.view'],
    'caller'   => ['dashboard.view', 'registrations.view', 'registrations.edit', 'payments.send', 'otp.view'],
    'content'  => ['dashboard.view', 'pages.view', 'pages.edit'],
    'accounts' => ['dashboard.view', 'registrations.view', 'registrations.export', 'payments.send', 'payments.confirm'],
    'viewer'   => ['dashboard.view', 'registrations.view', 'pages.view', 'otp.view'],
];
const STATUSES = ['new', 'contacted', 'shortlisted', 'paid', 'rejected'];
const PAGE_FIELDS = ['meta_title', 'descritpion', 'meta_keyword', 'og_title', 'og_description', 'og_image',
                     'app_fee', 'fee_title', 'fee_heading', 'fee_subtitle', 'journey_note'];
const UID_SPAN = 10000000; // uid = (source index + 1) * UID_SPAN + row id

function cfg(): array
{
    static $c = null;
    if ($c === null) {
        $f = __DIR__ . '/config.php';
        if (!is_file($f)) fail(500, 'api/config.php is missing. Copy config.sample.php to config.php and fill it in.');
        $c = require $f;
    }
    return $c;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $d = cfg()['db'];
        $pdo = new PDO('mysql:host=' . $d['host'] . ';port=' . ($d['port'] ?? 3306) . ';dbname=' . $d['name'] . ';charset=utf8mb4', $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function fail(int $code, string $message): void { out(['error' => $message], $code); }

/* Table and column names cannot be bound as parameters, so they are checked and quoted here. */
function ident(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) fail(500, 'Bad table or column name in config: ' . $name);
    return '`' . $name . '`';
}

function start_session(): void
{
    session_name('fsia_admin');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
    ]);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function require_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent)) fail(403, 'Your session has expired. Reload the page and try again.');
}
function body(): array
{
    $j = json_decode((string) file_get_contents('php://input'), true);
    return is_array($j) ? $j : [];
}

/* ---------- members and permissions ---------- */
function user_states(int $id): array
{
    $st = db()->prepare('SELECT state FROM admin_user_states WHERE user_id = ? ORDER BY state');
    $st->execute([$id]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}
function me(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    $id = $_SESSION['uid'] ?? null;
    if ($id) {
        $st = db()->prepare('SELECT id, name, email, username, role, active FROM admin_users WHERE id = ?');
        $st->execute([(int) $id]);
        $row = $st->fetch();
        if ($row && (int) $row['active'] === 1) {
            $row['id'] = (int) $row['id'];
            $row['active'] = true;
            $row['states'] = user_states($row['id']);
            $u = $row;
        }
    }
    return $u;
}
function perms_for(string $role): array
{
    if ($role === 'admin') return PERMS; // Super Admin can never be locked out
    $st = db()->prepare('SELECT permission FROM admin_role_permissions WHERE role = ?');
    $st->execute([$role]);
    return array_values(array_intersect(PERMS, $st->fetchAll(PDO::FETCH_COLUMN)));
}
function can(string $perm): bool
{
    $u = me();
    return $u !== null && in_array($perm, perms_for($u['role']), true);
}
function need(string $perm): void
{
    if (!me()) fail(401, 'Please sign in.');
    if (!can($perm)) fail(403, 'Your role does not allow this. Ask a Super Admin.');
}
function audit(string $action, string $summary): void
{
    $u = me();
    db()->prepare('INSERT INTO admin_audit_log (user_id, action, summary, ip) VALUES (?, ?, ?, ?)')
        ->execute([$u['id'] ?? null, $action, mb_substr($summary, 0, 500), $_SERVER['REMOTE_ADDR'] ?? null]);
}

/* ---------- existing tables ---------- */
function columns(string $table): array
{
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        try {
            $cache[$table] = array_column(db()->query('SHOW COLUMNS FROM ' . ident($table))->fetchAll(), 'Field');
        } catch (PDOException $e) {
            $cache[$table] = [];
        }
    }
    return $cache[$table];
}
function pick_column(array $cols, ?string $wanted, array $guesses): ?string
{
    if ($wanted !== null && $wanted !== '') return in_array($wanted, $cols, true) ? $wanted : null;
    foreach ($guesses as $g) if (in_array($g, $cols, true)) return $g;
    return null;
}
/* The configured form tables, with the id and date columns found in each. */
function sources(): array
{
    static $list = null;
    if ($list !== null) return $list;
    $list = [];
    foreach (cfg()['sources'] as $i => $s) {
        $cols = columns($s['table']);
        $list[] = $s + [
            'index' => $i,
            'exists' => (bool) $cols,
            'columns' => $cols,
            'id_col' => pick_column($cols, $s['id'] ?? null, ['id', 'sno', 'reg_id', 'regid', 'ID']),
            'created_col' => pick_column($cols, $s['created'] ?? null, ['created_at', 'created', 'reg_date', 'date', 'added_on', 'createdon', 'timestamp', 'datetime', 'date_time']),
        ];
    }
    return $list;
}
function source_by_index(int $i): array
{
    foreach (sources() as $s) if ($s['index'] === $i && $s['exists'] && $s['id_col']) return $s;
    fail(400, 'Unknown registration.');
    return [];
}
/* uid -> [source, row id]; also checks the row exists and is inside the member's states. */
function locate(int $uid): array
{
    $src = source_by_index(intdiv($uid, UID_SPAN) - 1);
    $sid = $uid % UID_SPAN;
    $cols = $src['columns'];
    $sel = in_array('state', $cols, true) ? '`state`' : 'NULL AS state';
    $st = db()->prepare('SELECT ' . $sel . ' FROM ' . ident($src['table']) . ' WHERE ' . ident($src['id_col']) . ' = ?');
    $st->execute([$sid]);
    $row = $st->fetch();
    if (!$row) fail(404, 'This registration no longer exists.');
    $scope = me()['states'];
    if ($scope && !in_array(trim((string) $row['state']), $scope, true)) fail(403, 'This registration is outside your states.');
    return [$src, $sid];
}
function save_work(array $src, int $sid, array $fields): void
{
    $allowed = ['status', 'assigned_to', 'notes', 'offer_name', 'amount', 'payment_url', 'payment_status', 'payment_ref', 'sent_at', 'paid_at', 'deleted_at'];
    $fields = array_intersect_key($fields, array_flip($allowed));
    if (!$fields) return;
    $names = array_keys($fields);
    $sql = 'INSERT INTO registration_work (source_table, source_id, ' . implode(', ', array_map('ident', $names)) . ') VALUES (?, ?'
        . str_repeat(', ?', count($names)) . ') ON DUPLICATE KEY UPDATE '
        . implode(', ', array_map(fn($n) => ident($n) . ' = VALUES(' . ident($n) . ')', $names));
    db()->prepare($sql)->execute(array_merge([$src['table'], $sid], array_values($fields)));
}
function ms(?string $datetime): ?int
{
    if ($datetime === null || $datetime === '' || str_starts_with($datetime, '0000')) return null;
    $t = strtotime($datetime);
    return $t === false ? null : $t * 1000;
}

/* ---------- schema for the new tables (used by install.php) ---------- */
function schema_sql(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
            failed_logins INT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS admin_user_states (
            user_id INT UNSIGNED NOT NULL, state VARCHAR(60) NOT NULL, PRIMARY KEY (user_id, state),
            FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS admin_role_permissions (
            role VARCHAR(30) NOT NULL, permission VARCHAR(60) NOT NULL, PRIMARY KEY (role, permission)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS registration_work (
            source_table VARCHAR(64) NOT NULL, source_id INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'new', assigned_to INT UNSIGNED NULL, notes TEXT NULL,
            offer_name VARCHAR(80) NULL, amount INT UNSIGNED NULL, payment_url VARCHAR(500) NULL,
            payment_status VARCHAR(10) NOT NULL DEFAULT 'none', payment_ref VARCHAR(80) NULL,
            sent_at DATETIME NULL, paid_at DATETIME NULL, deleted_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (source_table, source_id),
            FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS admin_audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(20) NOT NULL,
            summary VARCHAR(500) NOT NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY (user_id), KEY (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
}
