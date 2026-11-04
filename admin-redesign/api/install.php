<?php
/* One-time setup: creates the admin tables and the first Super Admin.
   Needs the setup_key from config.php. Refuses to add a Super Admin once any member exists. */
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$msg = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = (string) ($_POST['setup_key'] ?? '');
    $cfgKey = (string) (cfg()['setup_key'] ?? '');
    if ($cfgKey === '' || str_starts_with($cfgKey, 'CHANGE_ME') || !hash_equals($cfgKey, $key)) {
        usleep(500000);
        $msg = 'The setup key does not match setup_key in api/config.php.';
    } else {
        try {
            foreach (schema_sql() as $sql) db()->exec($sql);
            if (!(int) db()->query('SELECT COUNT(*) FROM admin_role_permissions')->fetchColumn()) {
                $ins = db()->prepare('INSERT INTO admin_role_permissions (role, permission) VALUES (?, ?)');
                foreach (DEFAULT_PERMS as $role => $list) foreach ($list as $p) $ins->execute([$role, $p]);
            }
            if ((int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn()) {
                $ok = true;
                $msg = 'Tables are up to date. Team members already exist, so no Super Admin was added.';
            } else {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $username) || strlen($password) < 12) {
                    $msg = 'Tables created. Now enter a name, a valid email, a user name and a password of at least 12 characters.';
                } else {
                    db()->prepare("INSERT INTO admin_users (name, email, username, password_hash, role) VALUES (?, ?, ?, ?, 'admin')")
                        ->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT)]);
                    $ok = true;
                    $msg = 'Done. Super Admin created. You can sign in to the panel now. Change setup_key in config.php so this page cannot be used again.';
                }
            }
        } catch (PDOException $e) {
            error_log('[fsia admin install] ' . $e->getMessage());
            $msg = 'Database error: check the db settings in api/config.php. Details are in the server error log.';
        }
    }
}
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>FSIA admin setup</title>
<style>body{font:15px/1.5 system-ui,sans-serif;background:#f6f4f1;color:#1c1917;margin:0;padding:24px 16px}main{max-width:460px;margin:0 auto;background:#fff;border:1px solid #e6e1da;border-radius:14px;padding:24px}
label{display:grid;gap:4px;margin:12px 0;font-weight:500}input{font:inherit;padding:9px 11px;border:1px solid #d4cdc3;border-radius:8px}button{font:inherit;font-weight:600;margin-top:8px;padding:10px 16px;border:0;border-radius:8px;background:#be123c;color:#fff;cursor:pointer}
.msg{padding:10px 12px;border-radius:8px;background:<?= $ok ? '#dcfce7' : '#fef3c7' ?>}</style></head>
<body><main>
<h1>Admin setup</h1>
<p>Creates the team tables and the first Super Admin. Existing form tables are not touched.</p>
<?php if ($msg): ?><p class="msg"><?= $e($msg) ?></p><?php endif; ?>
<?php if (!$ok): ?>
<form method="post" autocomplete="off">
  <label>Setup key (from api/config.php)<input type="password" name="setup_key" required></label>
  <label>Your full name<input name="name" value="<?= $e($_POST['name'] ?? '') ?>" required></label>
  <label>Email<input type="email" name="email" value="<?= $e($_POST['email'] ?? '') ?>" required></label>
  <label>User name<input name="username" value="<?= $e($_POST['username'] ?? '') ?>" required></label>
  <label>Password (at least 12 characters)<input type="password" name="password" minlength="12" required></label>
  <button type="submit">Create tables and Super Admin</button>
</form>
<?php endif; ?>
</main></body></html>
