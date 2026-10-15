<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * Roles for CMS admin users. Passwords stay in admin_users.php (set-password.php).
 *
 * GET  /api/users.php?action=me              -> who am I, my role and permissions
 * GET  /api/users.php?action=list            -> users, roles, whether roles are on
 * POST /api/users.php?action=setup           -> turn roles on: every current user becomes an owner
 * POST /api/users.php?action=set_role {username, role}
 */

const ROLES_TABLE_SQL = "CREATE TABLE IF NOT EXISTS cms_user_roles (
    username    VARCHAR(64) NOT NULL PRIMARY KEY,
    role        VARCHAR(20) NOT NULL,
    updated_by  VARCHAR(64) NOT NULL DEFAULT '',
    updated_at  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

function users_list(): array
{
    $roles = [];
    $info = [];
    if (rbac_roles_on()) {
        foreach (db()->query('SELECT username, role, updated_by, updated_at FROM cms_user_roles') as $r) {
            $roles[$r['username']] = $r['role'];
            $info[$r['username']] = ['updated_by' => $r['updated_by'], 'updated_at' => $r['updated_at']];
        }
    }
    $names = array_unique(array_merge(array_keys(admin_users()), array_keys($roles)));
    sort($names, SORT_NATURAL | SORT_FLAG_CASE);
    $users = [];
    foreach ($names as $name) {
        $name = (string) $name;
        $users[] = [
            'username'   => $name,
            'role'       => rbac_role_of($name),
            'can_sign_in' => isset(admin_users()[$name]),
            'updated_by' => $info[$name]['updated_by'] ?? null,
            'updated_at' => $info[$name]['updated_at'] ?? null,
        ];
    }
    return $users;
}

function roles_catalog(): array
{
    $out = [];
    foreach (RBAC_ROLES as $key => $r) {
        $out[] = ['key' => $key, 'label' => $r['label'], 'about' => $r['about'], 'permissions' => rbac_perms($key)];
    }
    return $out;
}

respond(function () {
    require_admin();
    $action = query_param('action') ?? '';
    $method = request_method();
    $me = rbac_identity();

    if ($method === 'GET' && $action === 'me') {
        return [
            'user'        => $me['user'],
            'role'        => $me['role'],
            'role_label'  => RBAC_ROLES[$me['role']]['label'],
            'permissions' => rbac_perms($me['role']),
            'roles_on'    => rbac_roles_on(),
        ];
    }

    if ($method === 'GET' && $action === 'list') {
        $perms = [];
        foreach (RBAC_PERMISSIONS as $k => $label) {
            $perms[] = ['key' => $k, 'label' => $label];
        }
        return ['roles_on' => rbac_roles_on(), 'users' => users_list(), 'roles' => roles_catalog(), 'permissions' => $perms, 'me' => $me['user']];
    }

    if ($method === 'POST' && $action === 'setup') {
        if (rbac_roles_on()) {
            throw new ApiError('Roles are already on', 409);
        }
        if (!admin_users()) {
            throw new ApiError('No users in admin_users.php yet. Create one with set-password.php first.', 409);
        }
        $pdo = db();
        $pdo->exec(ROLES_TABLE_SQL);
        $st = $pdo->prepare('INSERT IGNORE INTO cms_user_roles (username, role, updated_by, updated_at) VALUES (?, ?, ?, NOW())');
        $pdo->beginTransaction();
        foreach (array_keys(admin_users()) as $name) {
            $st->execute([(string) $name, 'owner', $me['user']]);
        }
        $pdo->commit();
        return ['roles_on' => true, 'users' => users_list()];
    }

    if ($method === 'POST' && $action === 'set_role') {
        if (!rbac_roles_on()) {
            throw new ApiError('Turn roles on first', 409);
        }
        $v = new Validator(json_body());
        $user = $v->string('username', 64, true);
        $role = $v->enum('role', array_keys(RBAC_ROLES));
        $v->check();
        if (!isset(admin_users()[$user])) {
            throw new ApiError('No such user', 404);
        }
        $pdo = db();
        $pdo->beginTransaction();
        // Lock the owner rows so two owners cannot demote each other at the same moment.
        $owners = $pdo->query("SELECT username FROM cms_user_roles WHERE role = 'owner' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $signInOwners = array_filter($owners, fn ($o) => isset(admin_users()[$o]));
        if ($role !== 'owner' && $signInOwners === [$user]) {
            $pdo->rollBack();
            throw new ApiError('This is the last owner. Make someone else an owner first.', 409);
        }
        $pdo->prepare('INSERT INTO cms_user_roles (username, role, updated_by, updated_at) VALUES (?, ?, ?, NOW())
                       ON DUPLICATE KEY UPDATE role = VALUES(role), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)')
            ->execute([$user, $role, $me['user']]);
        $pdo->commit();
        return ['users' => users_list()];
    }

    throw new ApiError('Unknown action', 400);
});
