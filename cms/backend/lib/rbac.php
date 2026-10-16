<?php
declare(strict_types=1);

/*
 * Roles and the activity log for the CMS admin.
 *
 * Roles are switched off unless config.php has 'roles_enabled' => true. While
 * off, every signed-in admin has full access (the single-admin setup), the
 * Users & Roles screen is hidden and roles cannot be turned on from the admin.
 *
 * Roles stay off until an owner turns them on (Users & Roles screen, which
 * creates cms_user_roles and makes every current user an owner). Until then
 * every signed-in user keeps full access, exactly as before. Once on, a user
 * who is not in the table (e.g. added later with set-password.php) is a viewer
 * until an owner gives them a role.
 *
 * Permissions are checked here, inside require_admin(), from the endpoint,
 * method and ?action= of the request, so every endpoint is covered without
 * changing it. Writes to an endpoint not listed below need "other.write".
 *
 * Every change (POST/PUT/DELETE), every refused request and every file
 * download is written to cms_audit_log. Logging never breaks a request.
 */

const RBAC_PERMISSIONS = [
    'content.edit'   => 'Edit pages, SEO, team and news; upload images',
    'tools.view'     => 'Open Site Check and Profile Changes',
    'tools.manage'   => 'Save the Site Check baseline; turn the change log on or off',
    'cleanup.manage' => 'Server Cleanup: look at, download, move, restore and delete files',
    'audit.view'     => 'See the activity log',
    'users.manage'   => 'Give people roles',
    'other.write'    => 'Any other change',
];

const RBAC_ROLES = [
    'owner'  => ['label' => 'Owner',  'about' => 'Everything, including roles',        'perms' => ['*']],
    'admin'  => ['label' => 'Admin',  'about' => 'Everything except giving roles',     'perms' => ['content.edit', 'tools.view', 'tools.manage', 'cleanup.manage', 'audit.view', 'other.write']],
    'editor' => ['label' => 'Editor', 'about' => 'Edit content; look at the tools',    'perms' => ['content.edit', 'tools.view']],
    'viewer' => ['label' => 'Viewer', 'about' => 'Look only; cannot change anything', 'perms' => ['tools.view']],
];

/** Name shown for whoever signs in with the admin token from config.php. */
const RBAC_TOKEN_USER = '(admin token)';

/** Permission a request needs, or null when being signed in is enough. */
function rbac_required_permission(string $script, string $method, string $action): ?string
{
    $write = !in_array($method, ['GET', 'HEAD'], true);
    switch ($script) {
        case 'auth.php':
            return null;
        case 'users.php':
            return $action === 'me' ? null : 'users.manage';
        case 'audit.php':
            return 'audit.view';
        case 'sections.php':
        case 'seo.php':
        case 'teams.php':
        case 'news.php':
        case 'upload.php':
            return $write ? 'content.edit' : null;
        case 'sitecheck.php':
        case 'changelog.php':
            return $write ? 'tools.manage' : 'tools.view';
        case 'cleanup.php':
            return 'cleanup.manage';
        default:
            return $write ? 'other.write' : null;
    }
}

/** Who is calling: the session user, RBAC_TOKEN_USER, or null. */
function rbac_user(): ?string
{
    $given = provided_token();
    $secret = session_secret();
    if ($given === '' || $secret === '') {
        return null;
    }
    return hash_equals($secret, $given) ? RBAC_TOKEN_USER : session_user($given);
}

/**
 * Role of a user. 'owner' while roles are off (no cms_user_roles table, or an
 * empty one); 'viewer' for users missing from the table once roles are on.
 */
function rbac_roles_enabled(): bool
{
    return (cms_config()['roles_enabled'] ?? false) === true;
}

function rbac_role_of(string $user): string
{
    if ($user === RBAC_TOKEN_USER || !rbac_roles_enabled()) {
        return 'owner';
    }
    try {
        $st = db()->prepare('SELECT role FROM cms_user_roles WHERE username = ?');
        $st->execute([$user]);
        $role = $st->fetchColumn();
        if (is_string($role)) {
            return isset(RBAC_ROLES[$role]) ? $role : 'viewer';
        }
        return rbac_roles_on() ? 'viewer' : 'owner';
    } catch (PDOException $e) {
        if (rbac_missing_table($e)) {
            return 'owner';
        }
        error_log('[fsia-cms] role lookup: ' . $e->getMessage());
        // Never fall back to more access than the user may have.
        throw new ApiError('Could not check your permissions. Please try again.', 503);
    }
}

function rbac_roles_on(): bool
{
    if (!rbac_roles_enabled()) {
        return false;
    }
    try {
        return (int) db()->query('SELECT COUNT(*) FROM cms_user_roles')->fetchColumn() > 0;
    } catch (PDOException $e) {
        if (rbac_missing_table($e)) {
            return false;
        }
        throw $e;
    }
}

function rbac_missing_table(PDOException $e): bool
{
    return $e->getCode() === '42S02';
}

function rbac_perms(string $role): array
{
    $perms = RBAC_ROLES[$role]['perms'] ?? [];
    return $perms === ['*'] ? array_keys(RBAC_PERMISSIONS) : $perms;
}

/** The caller (cached per request): ['user' => ..., 'role' => ...] or null. */
function rbac_identity(): ?array
{
    static $id = false;
    if ($id === false) {
        $user = rbac_user();
        $id = $user === null ? null : ['user' => $user, 'role' => rbac_role_of($user)];
    }
    return $id;
}

function rbac_can(string $perm): bool
{
    $id = rbac_identity();
    return $id !== null && in_array($perm, rbac_perms($id['role']), true);
}

/** Called by require_admin() once the caller is known to be signed in. */
function rbac_enforce(): void
{
    $perm = rbac_required_permission(audit_endpoint(), request_method(), (string) (query_param('action') ?? ''));
    if ($perm !== null && !rbac_can($perm)) {
        $label = RBAC_ROLES[rbac_identity()['role'] ?? '']['label'] ?? 'Your role';
        throw new ApiError("Your role ({$label}) cannot do this. Ask an owner for access.", 403);
    }
}

// --- Activity log ------------------------------------------------------------

const AUDIT_TABLE_SQL = "CREATE TABLE IF NOT EXISTS cms_audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created_at  DATETIME NOT NULL,
    username    VARCHAR(64) NOT NULL DEFAULT '',
    role        VARCHAR(20) NOT NULL DEFAULT '',
    ip          VARCHAR(45) NOT NULL DEFAULT '',
    method      VARCHAR(8) NOT NULL,
    endpoint    VARCHAR(64) NOT NULL,
    action      VARCHAR(40) NOT NULL DEFAULT '',
    target      VARCHAR(255) NOT NULL DEFAULT '',
    status      SMALLINT UNSIGNED NOT NULL,
    summary     TEXT NULL,
    user_agent  VARCHAR(255) NOT NULL DEFAULT '',
    KEY idx_created (created_at),
    KEY idx_user (username, created_at),
    KEY idx_endpoint (endpoint, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

function audit_endpoint(): string
{
    return basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? ''));
}

/** Which requests are logged: changes, refusals, and file downloads. */
function audit_wanted(string $method, int $status): bool
{
    return !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)
        || $status === 403
        || (audit_endpoint() === 'cleanup.php' && query_param('action') === 'download');
}

/** Replaces passwords, tokens and OTPs with *** at any depth. */
function audit_redact(mixed $v, string $key = ''): mixed
{
    if ($key !== '' && preg_match('/pass|token|secret|otp|authorization/i', $key)) {
        return '***';
    }
    if (is_array($v)) {
        $out = [];
        foreach ($v as $k => $item) {
            $out[$k] = audit_redact($item, (string) $k);
        }
        return $out;
    }
    return $v;
}

function audit_summary(): string
{
    if (!empty($_FILES)) {
        $files = [];
        foreach ($_FILES as $field => $f) {
            $files[$field] = ['name' => is_string($f['name'] ?? null) ? $f['name'] : '', 'size' => is_int($f['size'] ?? null) ? $f['size'] : 0];
        }
        $text = json_encode(['files' => $files], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $raw = (string) file_get_contents('php://input', false, null, 0, 262144);
        $data = json_decode($raw, true);
        $text = is_array($data)
            ? json_encode(audit_redact($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)
            : ($raw === '' ? '' : '(non-JSON body, ' . strlen($raw) . ' bytes)');
    }
    return mb_strcut((string) $text, 0, 4000, 'UTF-8');
}

/** Writes one row for this request (at most once). Never throws. */
function audit_record(int $status): void
{
    static $done = false;
    $method = request_method();
    if ($done || !audit_wanted($method, $status)) {
        return;
    }
    $done = true;
    try {
        $endpoint = audit_endpoint();
        $action = (string) (query_param('action') ?? '');
        try {
            $id = rbac_identity();
        } catch (Throwable) {
            $id = null;
        }
        $user = $id['user'] ?? '';
        if ($endpoint === 'auth.php' && $method === 'POST') {
            // Sign-in: record who tried, whether or not it worked.
            $body = json_decode((string) file_get_contents('php://input'), true);
            $user = is_array($body) && is_string($body['username'] ?? null) ? trim($body['username']) : '';
            $action = $status === 200 ? 'sign-in' : 'sign-in failed';
        }
        $query = $_GET;
        unset($query['action']);
        $row = [
            date('Y-m-d H:i:s'),
            mb_substr($user, 0, 64),
            $id['role'] ?? '',
            mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            $method,
            mb_substr($endpoint, 0, 64),
            mb_substr($action, 0, 40),
            mb_substr(http_build_query(audit_redact($query)), 0, 255),
            $status,
            ($endpoint === 'auth.php') ? '' : audit_summary(),
            mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ];
        $sql = 'INSERT INTO cms_audit_log (created_at, username, role, ip, method, endpoint, action, target, status, summary, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        try {
            db()->prepare($sql)->execute($row);
        } catch (PDOException $e) {
            if (!rbac_missing_table($e)) {
                throw $e;
            }
            db()->exec(AUDIT_TABLE_SQL);
            db()->prepare($sql)->execute($row);
        }
    } catch (Throwable $e) {
        error_log('[fsia-cms] activity log: ' . $e->getMessage());
    }
}
