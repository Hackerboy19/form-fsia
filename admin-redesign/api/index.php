<?php
/* Admin API. Call as api/index.php?r=<action>. GET reads, POST writes (JSON body + X-CSRF-Token header). */
declare(strict_types=1);
require __DIR__ . '/lib.php';

start_session();

$ROUTES = [
    'me' => ['GET', 'r_me'], 'login' => ['POST', 'r_login'], 'logout' => ['POST', 'r_logout'],
    'registrations' => ['GET', 'r_registrations'],
    'reg.status' => ['POST', 'r_reg_status'], 'reg.notes' => ['POST', 'r_reg_notes'], 'reg.assign' => ['POST', 'r_reg_assign'],
    'reg.payment' => ['POST', 'r_reg_payment'], 'reg.delete' => ['POST', 'r_reg_delete'],
    'pages' => ['GET', 'r_pages'], 'pages.save' => ['POST', 'r_pages_save'],
    'otp' => ['GET', 'r_otp'],
    'team.names' => ['GET', 'r_team_names'], 'team.list' => ['GET', 'r_team_list'], 'team.save' => ['POST', 'r_team_save'], 'team.remove' => ['POST', 'r_team_remove'],
    'perms' => ['GET', 'r_perms'], 'perms.set' => ['POST', 'r_perms_set'], 'perms.reset' => ['POST', 'r_perms_reset'],
    'audit' => ['GET', 'r_audit'], 'audit.add' => ['POST', 'r_audit_add'],
    'db.check' => ['GET', 'r_db_check'],
];

$r = (string) ($_GET['r'] ?? '');
if (!isset($ROUTES[$r])) fail(404, 'Unknown action.');
[$method, $fn] = $ROUTES[$r];
if ($_SERVER['REQUEST_METHOD'] !== $method) fail(405, 'Use ' . $method . ' for this action.');
if ($method === 'POST') require_csrf();
try {
    $fn(body());
} catch (PDOException $e) {
    error_log('[fsia admin api] ' . $r . ': ' . $e->getMessage());
    fail(500, 'Database error. The details are in the server error log.');
}

/* ---------- session ---------- */
function r_me(array $b): void
{
    $u = me();
    out(['user' => $u, 'perms' => $u ? perms_for($u['role']) : [], 'csrf' => $_SESSION['csrf'],
         'paymentTemplate' => (string) (cfg()['payment_link_template'] ?? '')]);
}
function r_login(array $b): void
{
    $username = trim((string) ($b['username'] ?? ''));
    $password = (string) ($b['password'] ?? '');
    $st = db()->prepare('SELECT * FROM admin_users WHERE username = ? OR email = ?');
    $st->execute([$username, $username]);
    $u = $st->fetch();
    if ($u && $u['locked_until'] && strtotime($u['locked_until']) > time()) fail(429, 'Too many wrong passwords. Try again in 15 minutes.');
    if (!$u || !password_verify($password, $u['password_hash']) || (int) $u['active'] !== 1) {
        if ($u) db()->prepare('UPDATE admin_users SET failed_logins = failed_logins + 1, locked_until = IF(failed_logins >= 4, NOW() + INTERVAL 15 MINUTE, NULL) WHERE id = ?')->execute([$u['id']]);
        usleep(400000);
        fail(401, $u && (int) $u['active'] !== 1 ? 'Your access is turned off. Ask a Super Admin.' : 'Wrong user name or password.');
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE admin_users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
    audit('login', 'Signed in');
    r_me([]);
}
function r_logout(array $b): void
{
    $_SESSION = [];
    session_destroy();
    out(['ok' => true]);
}

/* ---------- registrations ---------- */
function r_registrations(array $b): void
{
    need('registrations.view');
    $scope = me()['states'];
    $secret = array_map('strtolower', cfg()['secret_columns'] ?? []);
    $sensitive = can('registrations.sensitive') ? [] : array_map('strtolower', cfg()['sensitive_columns'] ?? []);
    $rows = [];
    $skipped = [];
    $verified = [];
    $otpCols = columns(cfg()['otp_table']);
    if (in_array('mobile', $otpCols, true) && in_array('is_verified', $otpCols, true)) {
        foreach (db()->query('SELECT DISTINCT `mobile` FROM ' . ident(cfg()['otp_table']) . ' WHERE `is_verified` = 1')->fetchAll(PDO::FETCH_COLUMN) as $m) {
            $verified[substr(preg_replace('/\D/', '', (string) $m), -10)] = true;
        }
    }
    foreach (sources() as $src) {
        if (!$src['exists'] || !$src['id_col']) { $skipped[] = $src['table']; continue; }
        $t = ident($src['table']);
        $hasState = in_array('state', $src['columns'], true);
        if ($scope && !$hasState) continue; // a member limited to states never sees rows without a state
        $sql = 'SELECT * FROM ' . $t;
        $params = [];
        if ($scope) { $sql .= ' WHERE TRIM(`state`) IN (' . implode(',', array_fill(0, count($scope), '?')) . ')'; $params = $scope; }
        $sql .= ' ORDER BY ' . ident($src['created_col'] ?? $src['id_col']) . ' DESC LIMIT ' . (int) ($src['max_rows'] ?? 5000);
        $st = db()->prepare($sql);
        $st->execute($params);
        $list = $st->fetchAll();

        $work = [];
        $w = db()->prepare('SELECT * FROM registration_work WHERE source_table = ?');
        $w->execute([$src['table']]);
        foreach ($w->fetchAll() as $x) $work[(int) $x['source_id']] = $x;

        foreach ($list as $row) {
            $sid = (int) $row[$src['id_col']];
            $wk = $work[$sid] ?? null;
            if ($wk && $wk['deleted_at']) continue;
            $raw = [];
            $hidden = [];
            $secretKeys = [];
            foreach ($row as $k => $v) {
                if ($k === $src['id_col'] || $k === $src['created_col']) continue;
                $lk = strtolower($k);
                if (in_array($lk, $secret, true)) { if ($v !== null && $v !== '') $secretKeys[] = $k; continue; }
                if (in_array($lk, $sensitive, true)) { if ($v !== null && $v !== '') $hidden[] = $k; continue; }
                $raw[$k] = $v;
            }
            $form = $src['form'] ?? null;
            if (!$form && !empty($src['form_column'])) $form = ($src['form_map'] ?? [])[(string) ($row[$src['form_column']] ?? '')] ?? null;
            $rows[] = [
                'uid' => ($src['index'] + 1) * UID_SPAN + $sid,
                'table' => $src['table'], 'group' => $src['group'], 'formId' => $form,
                'createdAt' => $src['created_col'] ? ms((string) $row[$src['created_col']]) : null,
                'raw' => $raw, 'hidden' => $hidden, 'secret' => $secretKeys,
                'verified' => isset($verified[substr(preg_replace('/\D/', '', (string) ($row['mobile'] ?? $row['mobile_local'] ?? '')), -10)]),
                'work' => $wk ? [
                    'status' => $wk['status'], 'notes' => (string) $wk['notes'], 'assignedTo' => $wk['assigned_to'] ? (int) $wk['assigned_to'] : null,
                    'offer' => $wk['offer_name'], 'amount' => $wk['amount'] !== null ? (int) $wk['amount'] : null, 'url' => (string) $wk['payment_url'],
                    'payStatus' => $wk['payment_status'], 'ref' => (string) $wk['payment_ref'], 'sentAt' => ms($wk['sent_at']), 'paidAt' => ms($wk['paid_at']),
                ] : null,
            ];
        }
    }
    out(['rows' => $rows, 'skipped' => $skipped]);
}
function ids_from(array $b): array
{
    $ids = array_values(array_filter(array_map('intval', (array) ($b['ids'] ?? [$b['id'] ?? 0]))));
    if (!$ids || count($ids) > 1000) fail(400, 'Choose between 1 and 1000 registrations.');
    return $ids;
}
function r_reg_status(array $b): void
{
    need('registrations.edit');
    $status = (string) ($b['status'] ?? '');
    if (!in_array($status, STATUSES, true)) fail(400, 'Unknown status.');
    $ids = ids_from($b);
    db()->beginTransaction();
    foreach ($ids as $uid) { [$src, $sid] = locate($uid); save_work($src, $sid, ['status' => $status]); }
    audit('status', 'Changed ' . count($ids) . ' registration' . (count($ids) > 1 ? 's' : '') . ' to ' . $status . ' (' . implode(', ', $ids) . ')');
    db()->commit();
    out(['ok' => true]);
}
function r_reg_notes(array $b): void
{
    need('registrations.edit');
    [$src, $sid] = locate((int) ($b['id'] ?? 0));
    save_work($src, $sid, ['notes' => mb_substr(trim((string) ($b['notes'] ?? '')), 0, 5000)]);
    out(['ok' => true]);
}
function r_reg_assign(array $b): void
{
    need('registrations.assign');
    $to = $b['user'] ?? null;
    $to = $to === null || $to === '' ? null : (int) $to;
    if ($to !== null) {
        $st = db()->prepare('SELECT name FROM admin_users WHERE id = ? AND active = 1');
        $st->execute([$to]);
        if (!$st->fetch()) fail(400, 'That team member does not exist or has no access.');
    }
    $ids = ids_from($b);
    db()->beginTransaction();
    foreach ($ids as $uid) { [$src, $sid] = locate($uid); save_work($src, $sid, ['assigned_to' => $to]); }
    audit('assign', 'Gave ' . count($ids) . ' registration' . (count($ids) > 1 ? 's' : '') . ' to member #' . ($to ?? 'nobody'));
    db()->commit();
    out(['ok' => true]);
}
function r_reg_payment(array $b): void
{
    if (!me()) fail(401, 'Please sign in.');
    [$src, $sid] = locate((int) ($b['id'] ?? 0));
    $f = [];
    if (array_key_exists('offer', $b) || array_key_exists('amount', $b) || array_key_exists('url', $b)) {
        need('payments.send');
        $amount = (int) ($b['amount'] ?? 0);
        $url = trim((string) ($b['url'] ?? ''));
        if ($amount <= 0 || $amount > 10000000) fail(400, 'Enter the amount in rupees.');
        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https')) fail(400, 'The payment link must start with https://');
        $f += ['offer_name' => mb_substr(trim((string) ($b['offer'] ?? '')), 0, 80) ?: 'Audition fee', 'amount' => $amount, 'payment_url' => $url];
    }
    if (isset($b['status'])) {
        $s = (string) $b['status'];
        if (!in_array($s, ['none', 'sent', 'paid'], true)) fail(400, 'Unknown payment status.');
        if ($s === 'paid' || ($b['undoPaid'] ?? false)) need('payments.confirm'); else need('payments.send');
        $f['payment_status'] = $s;
        if ($s === 'sent') $f['sent_at'] = date('Y-m-d H:i:s');
        if ($s === 'paid') { $f['paid_at'] = date('Y-m-d H:i:s'); $f['payment_ref'] = mb_substr(trim((string) ($b['ref'] ?? '')), 0, 80); $f['status'] = 'paid'; }
        if ($s !== 'paid') { $f['paid_at'] = null; $f['payment_ref'] = ''; }
    }
    if (!$f) fail(400, 'Nothing to save.');
    save_work($src, $sid, $f);
    audit('payment', isset($f['payment_status']) && $f['payment_status'] === 'paid'
        ? 'Marked the payment of registration ' . $b['id'] . ' as received' . ($f['payment_ref'] ? ' (ref ' . $f['payment_ref'] . ')' : '')
        : 'Updated the offer / payment link of registration ' . $b['id']);
    out(['ok' => true]);
}
/* Hides the registration in the panel. The row in the form's own table is kept. */
function r_reg_delete(array $b): void
{
    need('registrations.delete');
    $ids = ids_from($b);
    $undo = !empty($b['undo']);
    db()->beginTransaction();
    foreach ($ids as $uid) { [$src, $sid] = locate($uid); save_work($src, $sid, ['deleted_at' => $undo ? null : date('Y-m-d H:i:s')]); }
    audit('delete', ($undo ? 'Brought back ' : 'Deleted ') . count($ids) . ' registration' . (count($ids) > 1 ? 's' : '') . ' (' . implode(', ', $ids) . ')');
    db()->commit();
    out(['ok' => true]);
}

/* ---------- pages (more_pages) ---------- */
function page_columns(): array { return array_values(array_intersect(PAGE_FIELDS, columns(cfg()['pages_table']))); }
function r_pages(array $b): void
{
    need('pages.view');
    $cols = page_columns();
    $rows = db()->query('SELECT `page_name`' . ($cols ? ', ' . implode(', ', array_map('ident', $cols)) : '') . ' FROM ' . ident(cfg()['pages_table']))->fetchAll();
    out(['pages' => $rows, 'columns' => $cols]);
}
function r_pages_save(array $b): void
{
    need('pages.edit');
    $page = (string) ($b['page'] ?? '');
    $data = (array) ($b['fields'] ?? []);
    $cols = array_values(array_intersect(page_columns(), array_keys($data)));
    if (!$cols) fail(400, 'Nothing to save.');
    $t = ident(cfg()['pages_table']);
    $exists = db()->prepare('SELECT COUNT(*) FROM ' . $t . ' WHERE `page_name` = ?');
    $exists->execute([$page]);
    if (!(int) $exists->fetchColumn()) fail(404, 'There is no row for page ' . $page . ' in ' . cfg()['pages_table'] . '.');
    $sql = 'UPDATE ' . $t . ' SET ' . implode(', ', array_map(fn($c) => ident($c) . ' = ?', $cols)) . ' WHERE `page_name` = ?';
    db()->prepare($sql)->execute(array_merge(array_map(fn($c) => mb_substr((string) $data[$c], 0, 5000), $cols), [$page]));
    audit('page', 'Edited page ' . $page . ' (' . implode(', ', $cols) . ')');
    out(['ok' => true]);
}

/* ---------- OTP log (otp_code is never sent) ---------- */
function r_otp(array $b): void
{
    need('otp.view');
    $t = cfg()['otp_table'];
    $cols = columns($t);
    if (!$cols) out(['rows' => [], 'missing' => true]);
    $keep = array_values(array_intersect(['id', 'mobile', 'is_verified', 'expires_at', 'created_at', 'created'], $cols));
    $rows = db()->query('SELECT ' . implode(', ', array_map('ident', $keep)) . ' FROM ' . ident($t) . (in_array('id', $cols, true) ? ' ORDER BY `id` DESC' : '') . ' LIMIT 2000')->fetchAll();
    out(['rows' => array_map(fn($r) => [
        'id' => (int) ($r['id'] ?? 0), 'mobile' => (string) ($r['mobile'] ?? ''), 'verified' => !empty($r['is_verified']),
        'expiresAt' => ms($r['expires_at'] ?? null), 'createdAt' => ms($r['created_at'] ?? $r['created'] ?? null),
    ], $rows)]);
}

/* ---------- team ---------- */
function team_rows(bool $full): array
{
    $rows = db()->query('SELECT id, name, email, username, role, active, last_login_at FROM admin_users ORDER BY name')->fetchAll();
    return array_map(function ($u) use ($full) {
        $x = ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role'], 'active' => (int) $u['active'] === 1, 'states' => user_states((int) $u['id'])];
        if ($full) $x += ['email' => $u['email'], 'username' => $u['username'], 'lastLoginAt' => ms($u['last_login_at'])];
        return $x;
    }, $rows);
}
function r_team_names(array $b): void { if (!me()) fail(401, 'Please sign in.'); out(['users' => team_rows(false)]); }
function r_team_list(array $b): void { need('team.view'); out(['users' => team_rows(true)]); }
function active_admins(?int $except = null): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND active = 1 AND id <> ?");
    $st->execute([$except ?? 0]);
    return (int) $st->fetchColumn();
}
function r_team_save(array $b): void
{
    need('team.manage');
    $id = isset($b['id']) && $b['id'] !== '' ? (int) $b['id'] : null;
    $name = trim((string) ($b['name'] ?? ''));
    $email = strtolower(trim((string) ($b['email'] ?? '')));
    $username = trim((string) ($b['username'] ?? '')) ?: explode('@', $email)[0];
    $role = (string) ($b['role'] ?? '');
    $active = array_key_exists('active', $b) ? (bool) $b['active'] : true;
    $states = array_values(array_unique(array_filter(array_map(fn($s) => mb_substr(trim((string) $s), 0, 60), (array) ($b['states'] ?? [])))));
    $password = (string) ($b['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail(400, 'Enter a name and a valid email.');
    if (!in_array($role, ROLES, true)) fail(400, 'Unknown role.');
    if (!preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $username)) fail(400, 'The user name can use letters, numbers and . _ - @ only.');
    if ($id === null && strlen($password) < 10) fail(400, 'A new member needs a password of at least 10 characters.');
    if ($password !== '' && strlen($password) < 10) fail(400, 'Passwords need at least 10 characters.');
    if ($id !== null) {
        if ($id === me()['id'] && (!$active || $role !== 'admin' && me()['role'] === 'admin' && active_admins($id) === 0)) fail(400, 'You cannot lock yourself out.');
        $cur = db()->prepare('SELECT role, active FROM admin_users WHERE id = ?');
        $cur->execute([$id]);
        $c = $cur->fetch();
        if (!$c) fail(404, 'That member no longer exists.');
        if ($c['role'] === 'admin' && (int) $c['active'] === 1 && ($role !== 'admin' || !$active) && active_admins($id) === 0) fail(400, 'Keep at least one Super Admin.');
    }
    db()->beginTransaction();
    try {
        if ($id === null) {
            db()->prepare('INSERT INTO admin_users (name, email, username, password_hash, role, active) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT), $role, $active ? 1 : 0]);
            $id = (int) db()->lastInsertId();
            audit('team', 'Added ' . $name . ' as ' . $role);
        } else {
            db()->prepare('UPDATE admin_users SET name = ?, email = ?, username = ?, role = ?, active = ? WHERE id = ?')->execute([$name, $email, $username, $role, $active ? 1 : 0, $id]);
            if ($password !== '') db()->prepare('UPDATE admin_users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            audit('team', 'Updated ' . $name . ' (' . $role . ($active ? '' : ', access off') . ($password !== '' ? ', new password' : '') . ')');
        }
        db()->prepare('DELETE FROM admin_user_states WHERE user_id = ?')->execute([$id]);
        $ins = db()->prepare('INSERT INTO admin_user_states (user_id, state) VALUES (?, ?)');
        foreach ($states as $s) $ins->execute([$id, $s]);
        db()->commit();
    } catch (PDOException $e) {
        db()->rollBack();
        if ($e->getCode() === '23000') fail(400, 'Someone on the team already uses this email or user name.');
        throw $e;
    }
    out(['ok' => true, 'id' => $id]);
}
function r_team_remove(array $b): void
{
    need('team.manage');
    $id = (int) ($b['id'] ?? 0);
    if ($id === me()['id']) fail(400, 'You cannot remove yourself.');
    $st = db()->prepare('SELECT name, role, active FROM admin_users WHERE id = ?');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) fail(404, 'That member no longer exists.');
    if ($u['role'] === 'admin' && (int) $u['active'] === 1 && active_admins($id) === 0) fail(400, 'Keep at least one Super Admin.');
    db()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
    audit('team', 'Removed ' . $u['name'] . ' from the team');
    out(['ok' => true]);
}
function r_perms(array $b): void
{
    if (!me()) fail(401, 'Please sign in.');
    $all = [];
    foreach (ROLES as $role) $all[$role] = perms_for($role);
    out(['perms' => $all]);
}
function r_perms_set(array $b): void
{
    need('team.manage');
    $role = (string) ($b['role'] ?? '');
    $perm = (string) ($b['perm'] ?? '');
    if ($role === 'admin' || !in_array($role, ROLES, true) || !in_array($perm, PERMS, true)) fail(400, 'This permission cannot be changed.');
    if (!empty($b['on'])) db()->prepare('INSERT IGNORE INTO admin_role_permissions (role, permission) VALUES (?, ?)')->execute([$role, $perm]);
    else db()->prepare('DELETE FROM admin_role_permissions WHERE role = ? AND permission = ?')->execute([$role, $perm]);
    audit('team', (!empty($b['on']) ? 'Allowed ' : 'Stopped ') . $role . ': ' . $perm);
    out(['ok' => true]);
}
function seed_perms(): void
{
    db()->exec('DELETE FROM admin_role_permissions');
    $ins = db()->prepare('INSERT INTO admin_role_permissions (role, permission) VALUES (?, ?)');
    foreach (DEFAULT_PERMS as $role => $list) foreach ($list as $p) $ins->execute([$role, $p]);
}
function r_perms_reset(array $b): void { need('team.manage'); seed_perms(); audit('team', 'Reset all roles to the default access'); out(['ok' => true]); }

/* ---------- activity log ---------- */
function r_audit(array $b): void
{
    need('team.view');
    $rows = db()->query('SELECT user_id, action, summary, created_at FROM admin_audit_log ORDER BY id DESC LIMIT 400')->fetchAll();
    out(['rows' => array_map(fn($r) => ['userId' => $r['user_id'] ? (int) $r['user_id'] : null, 'action' => $r['action'], 'text' => $r['summary'], 'at' => ms($r['created_at'])], $rows)]);
}
/* Only events that happen in the browser (downloads, WhatsApp shares) are logged this way. */
function r_audit_add(array $b): void
{
    if (!me()) fail(401, 'Please sign in.');
    $action = (string) ($b['action'] ?? '');
    if (!in_array($action, ['export', 'share'], true)) fail(400, 'This event is logged by the server.');
    audit($action, (string) ($b['text'] ?? ''));
    out(['ok' => true]);
}

/* ---------- database check: what the API can see ---------- */
function r_db_check(array $b): void
{
    need('team.manage');
    $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $src = array_map(function ($s) {
        $n = $s['exists'] ? (int) db()->query('SELECT COUNT(*) FROM ' . ident($s['table']))->fetchColumn() : 0;
        return ['table' => $s['table'], 'exists' => $s['exists'], 'rows' => $n, 'idColumn' => $s['id_col'], 'dateColumn' => $s['created_col'],
                'hasState' => in_array('state', $s['columns'], true), 'columns' => $s['columns']];
    }, sources());
    $configured = array_column($src, 'table');
    $others = array_values(array_filter($tables, fn($t) => !in_array($t, $configured, true) && !preg_match('/^(admin_|registration_work$)/', $t)));
    out(['sources' => $src, 'otherTables' => $others, 'pagesColumns' => columns(cfg()['pages_table']), 'otpColumns' => columns(cfg()['otp_table'])]);
}
