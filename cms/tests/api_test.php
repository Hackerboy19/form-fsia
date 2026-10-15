<?php
declare(strict_types=1);

/*
 * End-to-end tests for the CMS API: sign-in, roles and the activity log.
 *
 * Runs a copy of cms/backend on PHP's built-in server against a throwaway
 * database that is created and dropped here. Never point it at production.
 *
 *   TEST_DB_HOST=127.0.0.1 TEST_DB_USER=tester TEST_DB_PASS=... php cms/tests/api_test.php
 *
 * The DB user needs CREATE/DROP DATABASE rights.
 */

if (PHP_SAPI !== 'cli') {
    exit;
}

$host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('TEST_DB_PORT') ?: 3306);
$dbUser = getenv('TEST_DB_USER') ?: 'root';
$dbPass = getenv('TEST_DB_PASS') ?: '';
$dbName = 'fsia_cms_test_' . bin2hex(random_bytes(4));
$backend = dirname(__DIR__) . '/backend';
$tmp = sys_get_temp_dir() . '/fsia-cms-test-' . bin2hex(random_bytes(4));
$token = bin2hex(random_bytes(32));

// --- Set up -------------------------------------------------------------------

$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4");
$pdo->exec("USE `$dbName`");
$pdo->exec((string) file_get_contents(dirname(__DIR__) . '/database/schema.sql'));

mkdir("$tmp/backend", 0700, true);
foreach (['api', 'lib'] as $dir) {
    mkdir("$tmp/backend/$dir");
    foreach (glob("$backend/$dir/*.php") as $f) {
        copy($f, "$tmp/backend/$dir/" . basename($f));
    }
}
copy("$backend/db.php", "$tmp/backend/db.php");
file_put_contents("$tmp/backend/config.php", '<?php return ' . var_export([
    'db' => ['host' => $host, 'port' => $port, 'name' => $dbName, 'user' => $dbUser, 'password' => $dbPass],
    'admin_token' => $token,
    'allowed_origins' => [],
    'pages' => ['index', 'about', 'our-teams'],
    'uploads' => ['dir' => "$tmp/uploads", 'base_url' => '/uploads', 'max_bytes' => 1048576, 'max_pixels' => 2000],
    'debug' => true,
], true) . ';');
$passwords = ['owner1' => 'Owner-pass-1', 'editor1' => 'Editor-pass-1', 'viewer1' => 'Viewer-pass-1'];
function write_users(string $tmp, array $passwords): void
{
    $hashes = array_map(fn ($p) => password_hash($p, PASSWORD_DEFAULT, ['cost' => 4]), $passwords);
    file_put_contents("$tmp/backend/admin_users.php", "<?php exit; ?>\n" . json_encode($hashes));
}
write_users($tmp, $passwords);

$httpPort = 18000 + random_int(0, 999);
$server = proc_open(['php', '-S', "127.0.0.1:$httpPort", '-t', "$tmp/backend"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $httpPort); $i++) {
    usleep(100000);
}

register_shutdown_function(function () use ($server, $pdo, $dbName, $tmp) {
    proc_terminate($server);
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    exec('rm -rf ' . escapeshellarg($tmp));
});

// --- Helpers ------------------------------------------------------------------

function call(string $method, string $path, ?string $auth = null, ?array $body = null): array
{
    global $httpPort;
    $ch = curl_init("http://127.0.0.1:$httpPort/api/$path");
    $headers = ['Accept: application/json'];
    if ($auth !== null) {
        $headers[] = "X-Admin-Token: $auth";
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    return [$status, json_decode($raw, true), $raw];
}

$failures = 0;
$count = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $failures, $count;
    $count++;
    if (!$ok) {
        $failures++;
    }
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . ($ok || $detail === '' ? '' : "  -- $detail") . "\n";
}

function login(string $user, string $pass): string
{
    [$s, $d] = call('POST', 'auth.php', null, ['username' => $user, 'password' => $pass]);
    return $s === 200 ? (string) $d['token'] : '';
}

$seoBody = ['meta_title' => 'Test title', 'meta_description' => 'Test description', 'meta_keywords' => '', 'canonical_url' => '', 'og_image' => ''];

// --- Tests --------------------------------------------------------------------

echo "Sign-in\n";
$owner = login('owner1', $passwords['owner1']);
$editor = login('editor1', $passwords['editor1']);
$viewer = login('viewer1', $passwords['viewer1']);
check('users can sign in', $owner !== '' && $editor !== '' && $viewer !== '');
[$s] = call('POST', 'auth.php', null, ['username' => 'owner1', 'password' => 'wrong']);
check('wrong password is refused', $s === 401, "got $s");
[$s] = call('GET', 'auth.php', 'not-a-token');
check('bad token is refused', $s === 401, "got $s");

echo "Before roles are turned on (nothing changes for anyone)\n";
[$s, $d] = call('GET', 'users.php?action=me', $viewer);
check('every user is an owner', $s === 200 && $d['role'] === 'owner' && $d['roles_on'] === false, json_encode($d));
[$s, $d, $raw] = call('PUT', 'seo.php?page=about', $viewer, $seoBody);
check('any user can still edit content', $s === 200, "got $s $raw");
[$s] = call('GET', 'users.php?action=list', $editor);
check('any user can open Users & Roles', $s === 200, "got $s");

echo "Turning roles on\n";
[$s, $d] = call('POST', 'users.php?action=setup', $owner, []);
check('setup makes every user an owner', $s === 200 && count(array_filter($d['users'], fn ($u) => $u['role'] === 'owner')) === 3, json_encode($d));
[$s] = call('POST', 'users.php?action=setup', $owner, []);
check('setup twice is refused', $s === 409, "got $s");
[$s] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'editor1', 'role' => 'editor']);
[$s2] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'viewer1', 'role' => 'viewer']);
check('owner can give roles', $s === 200 && $s2 === 200, "got $s / $s2");
[$s] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'viewer1', 'role' => 'superuser']);
check('unknown role is refused', $s === 422 || $s === 400, "got $s");
[$s] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'nobody', 'role' => 'viewer']);
check('unknown user is refused', $s === 404, "got $s");
[$s, $d] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'owner1', 'role' => 'admin']);
check('the last owner cannot be demoted', $s === 409, "got $s");

echo "Editor\n";
[$s, $d] = call('GET', 'users.php?action=me', $editor);
check('sees own role', $d['role'] === 'editor' && in_array('content.edit', $d['permissions'], true) && !in_array('cleanup.manage', $d['permissions'], true));
[$s] = call('PUT', 'seo.php?page=about', $editor, $seoBody);
check('can edit content', $s === 200, "got $s");
[$s] = call('POST', 'sitecheck.php?action=baseline', $editor, ['pages' => []]);
check('cannot save the Site Check baseline', $s === 403, "got $s");
[$s] = call('GET', 'cleanup.php?action=scan', $editor);
check('cannot open Server Cleanup', $s === 403, "got $s");
[$s] = call('GET', 'cleanup.php?action=download&path=x.log', $editor);
check('cannot download server files', $s === 403, "got $s");
[$s] = call('GET', 'users.php?action=list', $editor);
check('cannot open Users & Roles', $s === 403, "got $s");
[$s] = call('POST', 'users.php?action=set_role', $editor, ['username' => 'editor1', 'role' => 'owner']);
check('cannot make itself owner', $s === 403, "got $s");
[$s] = call('GET', 'audit.php?action=list', $editor);
check('cannot read the activity log', $s === 403, "got $s");

echo "Viewer\n";
[$s] = call('PUT', 'seo.php?page=about', $viewer, $seoBody);
check('cannot edit content', $s === 403, "got $s");
[$s] = call('DELETE', 'teams.php?id=1', $viewer);
check('cannot delete team members', $s === 403, "got $s");
[$s] = call('POST', 'changelog.php?action=install', $viewer, []);
check('cannot turn the change log on', $s === 403, "got $s");
[$s] = call('GET', 'seo.php?page=about', $viewer);
check('public reads still work', $s === 200, "got $s");

echo "Owner, admin token and new users\n";
[$s] = call('GET', 'cleanup.php?action=nothing', $owner);
check('owner passes the permission check', $s !== 403 && $s !== 401, "got $s");
[$s, $d] = call('GET', 'users.php?action=me', $token);
check('the config.php admin token is an owner', $s === 200 && $d['role'] === 'owner');
write_users($tmp, $passwords + ['newbie' => 'Newbie-pass-1']);
// New password hashes sign everyone out (by design), so sign in again.
[$s] = call('GET', 'auth.php', $owner);
check('rewriting the users file signs old sessions out', $s === 401, "got $s");
$owner = login('owner1', $passwords['owner1']);
$editor = login('editor1', $passwords['editor1']);
$viewer = login('viewer1', $passwords['viewer1']);
$newbie = login('newbie', 'Newbie-pass-1');
[$s, $d] = call('GET', 'users.php?action=me', $newbie);
check('a user added later is a viewer until given a role', $d['role'] === 'viewer', json_encode($d));
[$s] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'editor1', 'role' => 'owner']);
[$s2] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'owner1', 'role' => 'admin']);
check('an owner can step down once another owner exists', $s === 200 && $s2 === 200, "got $s / $s2");
[$s] = call('GET', 'users.php?action=list', $owner);
check('...and then loses Users & Roles', $s === 403, "got $s");

echo "Activity log\n";
[$s, $d] = call('GET', 'audit.php?action=list', $editor);
check('owner (editor1 now) can read it', $s === 200 && $d['total'] > 0, "got $s");
$rows = $d['rows'] ?? [];
$has = fn (callable $f) => (bool) array_filter($rows, $f);
check('records content changes with user and role', $has(fn ($r) => $r['endpoint'] === 'seo.php' && $r['username'] === 'editor1' && $r['role'] === 'editor' && $r['status'] === 200));
check('records refused requests', $has(fn ($r) => $r['username'] === 'viewer1' && $r['status'] === 403));
check('records sign-ins and failed sign-ins', $has(fn ($r) => $r['action'] === 'sign-in') && $has(fn ($r) => $r['action'] === 'sign-in failed'));
check('records role changes', $has(fn ($r) => $r['endpoint'] === 'users.php' && $r['action'] === 'set_role' && str_contains((string) $r['summary'], 'viewer1')));
check('never stores passwords', !str_contains(json_encode($rows), 'pass-1') && !str_contains(json_encode($rows), 'wrong'));
check('never stores tokens', !str_contains(json_encode($rows), $token));
[$s, $d] = call('GET', 'audit.php?action=list&result=denied&user=viewer1', $editor);
check('filters by result and user', $s === 200 && $d['total'] > 0 && !array_filter($d['rows'], fn ($r) => $r['status'] !== 403 || $r['username'] !== 'viewer1'));
[$s, $d] = call('GET', 'audit.php?action=list&q=' . urlencode("x' OR 1=1 -- %_"), $editor);
check('search input is not SQL', $s === 200 && $d['total'] === 0, "got $s");

echo "Logging failure never breaks a request\n";
$pdo->exec("RENAME TABLE `$dbName`.cms_audit_log TO `$dbName`.cms_audit_log_x");
$pdo->exec("CREATE VIEW `$dbName`.cms_audit_log AS SELECT 1 AS id");
[$s] = call('PUT', 'seo.php?page=about', $editor, $seoBody);
check('a change still saves when the log cannot be written', $s === 200, "got $s");

echo "\n$count checks, $failures failed\n";
exit($failures ? 1 : 0);
