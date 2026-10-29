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
// A stand-in for the starindia admin API (synthetic profiles only); it logs every method it receives.
$stubPort = 19000 + random_int(0, 999);
@mkdir("$tmp/stub/profiles", 0700, true);
file_put_contents("$tmp/stub/router.php", '<?php
file_put_contents(__DIR__ . "/methods.log", $_SERVER["REQUEST_METHOD"] . "\n", FILE_APPEND);
header("Content-Type: application/json");
if (preg_match("#^/api/profiles/(\\d+)$#", parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), $m) && is_file(__DIR__ . "/profiles/$m[1].json")) {
    readfile(__DIR__ . "/profiles/$m[1].json");
} else {
    http_response_code(404);
    echo "{\"error\":\"Profile not found\"}";
}');
$stub = proc_open(['php', '-S', "127.0.0.1:$stubPort", "$tmp/stub/router.php"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $stubPipes);

function write_config(bool $roles): void
{
    global $tmp, $host, $port, $dbName, $dbUser, $dbPass, $token, $stubPort;
    file_put_contents("$tmp/backend/config.php", '<?php return ' . var_export([
    'db' => ['host' => $host, 'port' => $port, 'name' => $dbName, 'user' => $dbUser, 'password' => $dbPass],
    'admin_token' => $token,
    'allowed_origins' => [],
    'pages' => ['index', 'about', 'our-teams'],
    'uploads' => ['dir' => "$tmp/uploads", 'base_url' => '/uploads', 'max_bytes' => 1048576, 'max_pixels' => 2000],
    'debug' => true,
    'site_root' => "$tmp/site",
    'starindia_api' => "http://127.0.0.1:$stubPort/api",
    'roles_enabled' => $roles,
], true) . ';');
}
write_config(false);

// A tiny "site": the registration table in the same database, and image files on disk.
$pdo->exec("CREATE TABLE registration (id INT PRIMARY KEY, first_name VARCHAR(100), last_name VARCHAR(100), regtype INT, status INT,
    date_of_reg DATETIME, url VARCHAR(255), setdefault TEXT, image TEXT, nimage1 VARCHAR(255), nimage2 VARCHAR(255),
    fullbio TEXT, meta_title VARCHAR(255), meta_desc TEXT, meta_keyword VARCHAR(255))");
foreach (['missindia_profile/a1.webp', 'missindia_profile/a2.webp', 'missindia_profile/a3.webp', 'missindia_profile/c1.webp', 'missindia_profile/c2.webp', 'images/d.jpg', 'images/m.jpg'] as $f) {
    @mkdir(dirname("$tmp/site/$f"), 0700, true);
    file_put_contents("$tmp/site/$f", 'x');
}
$ins = $pdo->prepare('INSERT INTO registration VALUES (?, ?, ?, 6, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
// A: everything there -> 100
$ins->execute([1, 'Asha', 'Full', '2026-01-05', 'asha-full', 'missindia_profile/a1.webp', 'missindia_profile/a2.webp,missindia_profile/a3.webp', 'm.jpg', 'd.jpg',
    '<p>' . str_repeat('Asha has worked in theatre for years. ', 20) . '</p>', 'Asha Full | Miss India 2026', 'Asha Full is a Miss India 2026 finalist from Pune.', 'asha, miss india']);
// B: one photo that is not on the server, nothing else but the link -> 15 + 5 = 20
$ins->execute([2, 'Bina', 'Bare', '2026-02-05', 'bina-bare', 'missindia_profile/gone.webp', '', '', '', '', '', '', '']);
// C: 2 photos, 200-character bio, desktop banner, long title, no keywords -> 25 + 17 + 10 + 16 = 68
$ins->execute([3, 'Chitra', 'Half', '2026-03-05', 'chitra-half', 'missindia_profile/c1.webp', 'missindia_profile/c2.webp', '', 'd.jpg',
    str_repeat('b', 200), str_repeat('T', 80), 'A short description.', '']);
$passwords = ['owner1' => 'Owner-pass-1', 'admin1' => 'Admin-pass-1', 'editor1' => 'Editor-pass-1', 'viewer1' => 'Viewer-pass-1'];
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

register_shutdown_function(function () use ($server, $stub, $pdo, $dbName, $tmp) {
    proc_terminate($server);
    proc_terminate($stub);
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
write_config(true);
[$s, $d] = call('GET', 'users.php?action=me', $viewer);
check('with roles allowed but not turned on, still everyone owner', $s === 200 && $d['role'] === 'owner' && $d['roles_available'] === true, json_encode($d));
write_config(false);
[$s, $d, $raw] = call('PUT', 'seo.php?page=about', $viewer, $seoBody);
check('any user can still edit content', $s === 200, "got $s $raw");

echo "Single admin (roles switched off in config.php, the default)\n";
[$s, $d] = call('GET', 'users.php?action=me', $editor);
check('me answers, roles not available', $s === 200 && $d['role'] === 'owner' && $d['roles_available'] === false, json_encode($d));
[$s] = call('POST', 'users.php?action=setup', $owner, []);
check('roles cannot be turned on from the admin', $s === 409, "got $s");
[$s] = call('GET', 'users.php?action=list', $owner);
check('Users & Roles answers "switched off"', $s === 409, "got $s");
write_config(true);

echo "Turning roles on\n";
[$s, $d] = call('POST', 'users.php?action=setup', $owner, []);
check('setup makes every user an owner', $s === 200 && count(array_filter($d['users'], fn ($u) => $u['role'] === 'owner')) === count($passwords), json_encode($d));
[$s] = call('POST', 'users.php?action=setup', $owner, []);
check('setup twice is refused', $s === 409, "got $s");
[$s] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'editor1', 'role' => 'editor']);
[$s2] = call('POST', 'users.php?action=set_role', $owner, ['username' => 'viewer1', 'role' => 'viewer']);
call('POST', 'users.php?action=set_role', $owner, ['username' => 'admin1', 'role' => 'admin']);
$admin = login('admin1', $passwords['admin1']);
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

echo "Permission matrix (every role against every protected action)\n";
// [label, method, path, body, roles that must be allowed]. "Allowed" = not refused with 401/403;
// the action itself may still answer 4xx for the empty test input.
$all = ['owner', 'admin', 'editor', 'viewer'];
$matrix = [
    ['Read page text / SEO / team / news (public)', 'GET', 'seo.php?page=about', null, ['owner', 'admin', 'editor', 'viewer', 'none']],
    ['Edit page text', 'PUT', 'sections.php?page=about&key=hero', ['content' => ['title' => 'x'], 'image_url' => ''], ['owner', 'admin', 'editor']],
    ['Edit SEO', 'PUT', 'seo.php?page=about', $seoBody, ['owner', 'admin', 'editor']],
    ['Add team member', 'POST', 'teams.php', [], ['owner', 'admin', 'editor']],
    ['Delete team member', 'DELETE', 'teams.php?id=999999', null, ['owner', 'admin', 'editor']],
    ['Write news', 'POST', 'news.php', [], ['owner', 'admin', 'editor']],
    ['Upload image', 'POST', 'upload.php', [], ['owner', 'admin', 'editor']],
    ['Profiles & Score / Site Check: view', 'GET', 'sitecheck.php?action=profiles', null, ['owner', 'admin', 'editor', 'viewer']],
    ['Events: create / publish / cancel', 'POST', 'events.php', [], ['owner', 'admin', 'editor']],
    ['Events: view list', 'GET', 'events.php', null, ['owner', 'admin', 'editor', 'viewer']],
    ['Site Check: save baseline', 'POST', 'sitecheck.php?action=baseline', ['pages' => []], ['owner', 'admin']],
    ['Profile Changes: view', 'GET', 'changelog.php?action=status', null, ['owner', 'admin', 'editor', 'viewer']],
    ['Profile Changes: turn on/off', 'POST', 'changelog.php?action=uninstall', [], ['owner', 'admin']],
    ['Server Cleanup: scan / preview / download', 'GET', 'cleanup.php?action=trash_list', null, ['owner', 'admin']],
    ['Server Cleanup: move to Trash', 'POST', 'cleanup.php?action=trash', ['paths' => []], ['owner', 'admin']],
    ['Users & Roles: view', 'GET', 'users.php?action=list', null, ['owner']],
    ['Users & Roles: change a role', 'POST', 'users.php?action=set_role', ['username' => 'nobody', 'role' => 'viewer'], ['owner']],
    ['Activity Log: view', 'GET', 'audit.php?action=list', null, ['owner', 'admin']],
];
$tokens = ['owner' => $owner, 'admin' => $admin, 'editor' => $editor, 'viewer' => $viewer, 'none' => ''];
$md = "| Action | Owner | Admin | Editor | Viewer | Not signed in |\n|---|---|---|---|---|---|\n";
$matrixOk = true;
foreach ($matrix as [$label, $method, $path, $body, $allowed]) {
    $cells = [];
    foreach ($tokens as $role => $tok) {
        [$s] = call($method, $path, $tok === '' ? null : $tok, $body);
        $isAllowed = $s !== 401 && $s !== 403;
        $want = in_array($role, $allowed, true);
        if ($isAllowed !== $want) {
            $matrixOk = false;
            echo "    mismatch: $label as $role got $s\n";
        }
        $cells[] = ($isAllowed ? 'Allowed' : 'Refused') . " ($s)";
    }
    $md .= "| $label | " . implode(' | ', $cells) . " |\n";
}
check('every role gets exactly the access it should (server-side)', $matrixOk);
if ($out = getenv('MATRIX_OUT')) {
    file_put_contents($out, $md);
}

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
$admin = login('admin1', $passwords['admin1']);
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
$rows = [];
foreach (['endpoint=seo.php&user=editor1', 'result=denied&user=viewer1', 'endpoint=auth.php', 'endpoint=users.php'] as $f) {
    $rows = array_merge($rows, call('GET', "audit.php?action=list&$f", $editor)[1]['rows'] ?? []);
}
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

echo "Profile quality score\n";
[$s, $d, $raw] = call('GET', 'sitecheck.php?action=profiles&sort=score', $editor);
$byId = [];
foreach ($d['rows'] ?? [] as $r) {
    $byId[$r['id']] = $r;
}
check('profiles load with a score', $s === 200 && count($byId) === 3, "got $s " . substr($raw, 0, 300));
check('a complete profile scores 100 (excellent)', ($byId[1]['score'] ?? null) === 100 && $byId[1]['band'] === 'excellent', json_encode($byId[1]['score_parts'] ?? null));
check('a bare profile scores 20 (poor)', ($byId[2]['score'] ?? null) === 20 && $byId[2]['band'] === 'poor', json_encode($byId[2]['score_parts'] ?? null));
check('a half-done profile scores 68 (good)', ($byId[3]['score'] ?? null) === 68 && $byId[3]['band'] === 'good', json_encode($byId[3]['score_parts'] ?? null));
check('parts add up to the score', array_sum(array_column($byId[3]['score_parts'] ?? [], 'got')) === 68 && array_sum(array_column($byId[3]['score_parts'] ?? [], 'max')) === 100);
check('lowest score first', array_column($d['rows'], 'id') === [2, 3, 1], json_encode(array_column($d['rows'], 'id')));
check('band counts and average', $d['bands'] === ['excellent' => 1, 'good' => 1, 'fair' => 0, 'poor' => 1, 'unchecked' => 0] && $d['avg_score'] === 63, json_encode([$d['bands'], $d['avg_score']]));
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&band=poor,fair', $editor);
check('filter by band', array_column($d['rows'], 'id') === [2], json_encode(array_column($d['rows'], 'id')));
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&band=excellent&problem=bio', $editor);
check('band and problem filters combine', $d['total'] === 0);

echo "Profile filters\n";
// 230 more profiles in another category, spread over years and months.
$ins2 = $pdo->prepare("INSERT INTO registration VALUES (?, ?, 'Test', 7, 1, ?, ?, '', '', '', '', ?, '', '', '')");
for ($i = 0; $i < 230; $i++) {
    $ins2->execute([1000 + $i, "Mrs$i", sprintf('%d-%02d-10', 2024 + ($i % 2), 1 + $i % 12), "mrs-$i", $i % 3 ? 'bio' : '']);
}
$q = fn (string $qs) => call('GET', "sitecheck.php?action=profiles&$qs", $editor)[1];
check('search by name', array_column($q('q=Asha')['rows'], 'id') === [1]);
check('search by ID', in_array(2, array_column($q('q=2')['rows'], 'id'), true));
check('filter by category', $q('category=miss-india')['total'] === 3 && $q('category=mrs-india')['total'] === 230);
$sqlCount = fn (string $where) => (int) $pdo->query("SELECT COUNT(*) FROM registration WHERE regtype = 7 AND $where")->fetchColumn();
check('filter by year and month match the database', $q('category=mrs-india&year=2024')['total'] === $sqlCount('YEAR(date_of_reg) = 2024')
    && $q('category=mrs-india&year=2024&month=3')['total'] === $sqlCount('YEAR(date_of_reg) = 2024 AND MONTH(date_of_reg) = 3'));
$seen = [];
foreach ([1, 2, 3] as $pg) {
    foreach ($q("category=mrs-india&page=$pg")['rows'] as $r) {
        $seen[] = $r['id'];
    }
}
check('pagination: 230 rows over 3 pages, none missing or repeated', count($seen) === 230 && count(array_unique($seen)) === 230, count($seen) . ' / ' . count(array_unique($seen)));
$any = $q('problem=bio,image&match=any&all=1')['rows'];
$both = $q('problem=bio,image&match=all&all=1')['rows'];
check('combined problems: "all" is a subset of "any" and needs both',
    count($both) <= count($any) && !array_filter($both, fn ($r) => !isset($r['problems']['bio'], $r['problems']['image'])) && !array_filter($any, fn ($r) => !isset($r['problems']['bio']) && !isset($r['problems']['image'])));
$u = $q('category=zzz&year=abc&problem=nonsense&band=xyz');
check('unknown filter values are ignored, not errors', ($u['total'] ?? null) === 233, json_encode(array_diff_key($u ?? [], ['rows' => 1, 'paths' => 1])));
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&q=' . urlencode("' OR 1=1 -- "), $editor);
check('search text is not SQL', $s === 200 && $d['total'] === 0, "got $s");
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&q=' . urlencode('<script>alert(1)</script>'), $editor);
check('script in search returns plain JSON', $s === 200 && $d['total'] === 0);
check('score is the same on every run', $q('q=Chitra')['rows'][0]['score_parts'] === $q('q=Chitra')['rows'][0]['score_parts']);
$pdo->exec("UPDATE registration SET fullbio = REPEAT('b', 600) WHERE id = 3");
$after = $q('q=Chitra')['rows'][0];
check('a longer bio changes only the bio part (68 -> 76)', $after['score'] === 76 && array_column($after['score_parts'], 'got', 'key') === ['photos' => 25, 'bio' => 25, 'banner' => 10, 'seo' => 16], json_encode($after['score_parts']));

echo "Verified Member / Pending / Guest\n";
$pdo->exec('ALTER TABLE registration ADD verification INT NULL DEFAULT 1, ADD suspended INT NOT NULL DEFAULT 0, ADD verification_date DATE NULL');
$pdo->exec('UPDATE registration SET verification = 0 WHERE id BETWEEN 1000 AND 1039');
$pdo->exec('UPDATE registration SET verification = NULL WHERE id = 1040');
$pdo->exec("UPDATE registration SET verification_date = '2026-10-15' WHERE id = 1");
$vCount = fn (string $w) => (int) $pdo->query("SELECT COUNT(*) FROM registration WHERE $w")->fetchColumn();
$all = $q('status=all&all=1');
check('verification totals match the database', $all['verification_counts'] === ['verified' => $vCount('verification = 1'), 'pending' => $vCount('verification IS NULL OR verification <> 1')], json_encode($all['verification_counts']));
check('Pending / Guest filter: 41 profiles (40 with 0, 1 empty)', $q('status=all&verification=pending')['total'] === 41);
check('Verified Member filter: all the others', $q('status=all&verification=verified')['total'] === $vCount('verification = 1'));
check('each profile says which group it is in', count(array_filter($all['rows'], fn ($r) => $r['verified'] === false)) === 41);
$asha = array_values(array_filter($all['rows'], fn ($r) => $r['id'] === 1))[0] ?? [];
check('verified date is shown when the database has it', ($asha['verified_on'] ?? '') === '2026-10-15', json_encode($asha['verified_on'] ?? null));
check('verification combines with category', $q('status=all&verification=pending&category=mrs-india')['total'] === 41 && $q('status=all&verification=pending&category=miss-india')['total'] === 0);
check('an unknown verification value is ignored', $q('status=all&verification=bogus')['total'] === 233);

echo "starindia profiles are checked from the starindia admin's data\n";
$pdo->exec("ALTER TABLE registration ADD starindia_action VARCHAR(20) NULL");
$si = function (int $id, string $slug, string $title, string $desc, string $keys, string $h1, string $hero, string $logo, int $bioLen) use ($tmp) {
    file_put_contents("$tmp/stub/profiles/$id.json", json_encode(['id' => $id, 'slug' => $slug, 'name' => "SI $id", 'bio' => '<b>' . str_repeat('x', $bioLen) . '</b>&nbsp;',
        'logo' => $logo, 'published' => 1, 'updated_at' => '2026-10-14T06:49:31.000Z',
        'data' => ['hero' => ['image' => $hero], 'seo' => ['title' => $title, 'description' => $desc, 'keywords' => $keys, 'h1' => $h1, 'noindex' => false],
                   'services' => [], 'achievements' => [], 'gallery' => [], 'detailsBody' => '', 'contact' => ['phone' => '9000000000', 'phonePublic' => false]]]));
};
$si(3001, 'si-a', str_repeat('T', 68), str_repeat('D', 151), 'k1, k2', 'H1 text', 'a.webp,b.webp', 'a.webp,b.webp', 300);
$si(3003, 'si-c', str_repeat('T', 50), str_repeat('D', 140), 'k1', 'H1 text', 'hero.webp', '', 600);
$ins3 = $pdo->prepare("INSERT INTO registration (id, first_name, last_name, regtype, status, date_of_reg, url, setdefault, image, nimage1, nimage2, fullbio, meta_title, meta_desc, meta_keyword, starindia_action)
    VALUES (?, 'SI', ?, 9, 1, '2026-10-14', ?, 'missindia_profile/a1.webp', '', '', '', '', '', '', '', 'edited')");
foreach ([[3001, 'A', 'si-a'], [3002, 'B', 'si-b'], [3003, 'C', 'si-c']] as $r) $ins3->execute($r);
exec('rm -rf ' . escapeshellarg(sys_get_temp_dir() . '/fsia-starindia'));
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&status=all&category=super-women&fresh=1&all=1', $editor);
$by = array_column($d['rows'] ?? [], null, 'id');
$a = $by[3001] ?? [];
check('starindia profile: SEO comes from the starindia admin, not the empty database columns',
    ($a['source'] ?? '') === 'starindia' && in_array('Meta title too long (68/60)', $a['problems']['seo'] ?? [], true) && !preg_grep('/No meta (title|description)/', $a['problems']['seo'] ?? []), json_encode($a['problems'] ?? $d));
check('...hero image holding 2 images is reported, desktop/mobile banners are not',
    ($a['problems']['banner'] ?? []) === ['Hero image field holds 2 images (shows as broken)'], json_encode($a['problems']['banner'] ?? null));
check('...profile logo holding 2 images is reported', in_array('Profile logo field holds 2 images (shows as broken)', $a['problems']['image'] ?? [], true));
check('...score from that data: 25 + 17 + 0 + 21 = 63', ($a['score'] ?? null) === 63 && array_column($a['score_parts'], 'got', 'key') === ['photos' => 25, 'bio' => 17, 'banner' => 0, 'seo' => 21], json_encode($a['score_parts'] ?? null));
$b = $by[3002] ?? [];
check('starindia profile the API does not know: no score, no SEO/bio/hero issues, says why', array_key_exists('score', $b) && $b['score'] === null && ($b['band'] ?? '') === 'unchecked'
    && !isset($b['problems']['seo']) && !isset($b['problems']['bio']) && !isset($b['problems']['banner']) && str_contains((string) ($b['unchecked'] ?? ''), 'Not found'), json_encode($b));
check('a complete starindia profile scores 95 (1 photo)', ($by[3003]['score'] ?? null) === 95 && empty($by[3003]['problems']['seo']) && empty($by[3003]['problems']['banner']), json_encode($by[3003] ?? null));
check('an unchecked profile is not counted as "nothing missing"', $d['counts']['ok'] === count(array_filter($d['rows'], fn ($r) => !$r['problems'] && $r['unchecked'] === null)));
check('unchecked profiles are left out of the average and counted apart', $d['bands']['unchecked'] === 1 && $d['avg_score'] === (int) round((63 + 95) / 2), json_encode([$d['bands'], $d['avg_score']]));
check('the list says how many starindia profiles were checked', ($d['starindia'] ?? null) == ['profiles' => 3, 'checked' => 2, 'admin' => 'https://starindia.fsia.in/admin/']);
$methods = array_unique(array_filter(explode("\n", (string) @file_get_contents("$tmp/stub/methods.log"))));
check('only GET requests are sent to the starindia admin', $methods === ['GET'], json_encode($methods));
$cached = (string) @file_get_contents(sys_get_temp_dir() . '/fsia-starindia/3001.json');
check('the cache keeps no contact details', $cached !== '' && !str_contains($cached, '9000000000') && !str_contains($cached, 'phone'));
@unlink("$tmp/stub/methods.log");
call('GET', 'sitecheck.php?action=profiles&status=all&category=super-women', $editor);
check('a second load uses the cache (only the not-found profile is asked again)', count(array_filter(explode("\n", (string) @file_get_contents("$tmp/stub/methods.log")))) === 1);
$pdo->exec('DELETE FROM registration WHERE id IN (3001, 3002, 3003)');

echo "Events & Promotions\n";
$nowIst = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
$at = fn (string $mod) => $nowIst->modify($mod)->format('Y-m-d\TH:i');
$ev = fn (array $o) => array_merge(['title' => 'Test Pageant 2027', 'status' => 'published', 'starts_at' => $at('+10 days'), 'ends_at' => $at('+12 days'),
    'promo_start' => $at('-1 day'), 'promo_end' => $at('+12 days'), 'venue' => 'Test Hall', 'city' => 'Jaipur', 'cta_url' => '/quickapply',
    'targets' => ['all'], 'excludes' => [], 'priority' => 10], $o);
[$s, $d] = call('POST', 'events.php', $editor, ['title' => '', 'starts_at' => 'x']);
check('validation: title and real dates are required', $s === 422 && isset($d['fields']['title'], $d['fields']['starts_at'], $d['fields']['ends_at']), json_encode($d));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['ends_at' => $at('+5 days')]));
check('validation: an event cannot end before it starts', $s === 422 && isset($d['fields']['ends_at']));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['targets' => []]));
check('validation: publishing needs at least one page', $s === 422 && isset($d['fields']['targets']));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['cta_url' => 'javascript:alert(1)']));
check('validation: registration link must be http(s) or /path', $s === 422 && isset($d['fields']['cta_url']));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['targets' => ['<script>']]));
check('validation: page rules must be a group or a /path', $s === 422 && isset($d['fields']['targets']));
[$s] = call('POST', 'events.php', $viewer, $ev([]));
check('a viewer cannot create events', $s === 403, "got $s");
[$s, $d, $raw] = call('POST', 'events.php', $editor, $ev(['title' => 'All pages event', 'description' => '<p>Hi</p><script>alert(1)</script>', 'excludes' => ['/quickapply', '/news*'],
    'guests' => [['name' => 'Guest One', 'role' => 'Jury', 'photo' => 'https://example.com/g.jpg'], ['name' => '', 'role' => 'skipped'],
        ['name' => 'Star Guest', 'role' => 'Celebrity Chief Guest', 'photo' => '/uploads/cms/star.webp', 'featured' => true]]]));
$allId = $d['id'] ?? 0;
check('a valid event is saved and is live now', $s === 201 && ($d['phase'] ?? '') === 'live' && count($d['guests']) === 2 && !str_contains((string) $d['description'], 'script'), "got $s $raw");
$promo = fn (string $path) => call('GET', 'promotions.php?path=' . urlencode($path))[1]['promotion'] ?? null;
check('public: shows on a targeted page, without signing in', ($promo('/miss-assam.php')['id'] ?? 0) === $allId);
check('public: not on an excluded page', $promo('/quickapply') === null && $promo('/news-coverage') === null && $promo('/news-coverage/x') === null);
$pub = $promo('/') ?? [];
check('public: answer holds only display fields (no page rules, description or status)', $pub !== [] && !array_intersect(array_keys($pub), ['targets', 'excludes', 'description', 'status', 'priority']), json_encode(array_keys($pub)));
check('public: only the guest marked "Show on website bar" (name, role, photo)', ($pub['guests'] ?? null) === [['name' => 'Star Guest', 'role' => 'Celebrity Chief Guest', 'photo' => '/uploads/cms/star.webp']], json_encode($pub['guests'] ?? null));
$star = fn (string $n) => ['name' => $n, 'role' => 'Chief Guest', 'photo' => '', 'featured' => true];
[$s, $d] = call('POST', 'events.php', $editor, $ev(['guests' => [$star('A'), $star('B'), $star('C')]]));
check('validation: at most 2 guests on the website bar', $s === 422 && isset($d['fields']['guests']), json_encode($d));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['guests' => [array_merge($star('A'), ['photo' => 'javascript:alert(1)'])]]));
check('validation: guest photo must be a link or /path', $s === 422 && isset($d['fields']['guests.0']), json_encode($d));
[$s, $d] = call('POST', 'events.php', $editor, $ev(['title' => 'Homepage only, higher priority', 'targets' => ['home'], 'priority' => 50]));
$homeId = $d['id'] ?? 0;
check('overlap: the higher priority event wins on its page, only one banner', ($promo('/')['id'] ?? 0) === $homeId && ($promo('/about')['id'] ?? 0) === $allId);
[$s, $d] = call('POST', 'events.php', $editor, $ev(['title' => 'Later promo', 'targets' => ['pageants'], 'priority' => 90, 'promo_start' => $at('+3 days')]));
check('scheduled: not shown before its promotion starts', ($d['phase'] ?? '') === 'scheduled' && ($promo('/miss-assam.php')['id'] ?? 0) === $allId);
[$s, $d] = call('POST', 'events.php', $editor, $ev(['title' => 'Finished', 'targets' => ['pageants'], 'priority' => 95, 'starts_at' => $at('-5 days'), 'ends_at' => $at('-2 days'), 'promo_start' => $at('-9 days'), 'promo_end' => $at('-2 days')]));
check('completed: hidden after the event ended', ($d['phase'] ?? '') === 'completed' && ($promo('/mrs-kerala.php')['id'] ?? 0) === $allId);
[$s, $d] = call('POST', 'events.php', $editor, $ev(['title' => 'Draft one', 'status' => 'draft', 'targets' => ['profiles'], 'priority' => 99]));
$draftId = $d['id'] ?? 0;
check('draft: never shown', ($d['phase'] ?? '') === 'draft' && ($promo('/some-profile-slug')['id'] ?? 0) === $allId);
[$s, $d] = call('GET', "events.php?id=$homeId", $editor);
$upd = array_merge($d, ['status' => 'cancelled', 'starts_at' => substr($d['starts_at'], 0, 16), 'ends_at' => substr($d['ends_at'], 0, 16), 'promo_start' => substr($d['promo_start'], 0, 16), 'promo_end' => substr($d['promo_end'], 0, 16)]);
[$s, $d] = call('PUT', "events.php?id=$homeId", $editor, $upd);
check('cancelled: taken off the homepage at once', $s === 200 && $d['phase'] === 'cancelled' && ($promo('/')['id'] ?? 0) === $allId, "got $s");
check('rule: profile pages = one-part paths that are not main pages', ($promo('/azra')['id'] ?? 0) === $allId);
[$s, $d] = call('GET', 'events.php?action=match&path=' . urlencode('/Miss-Assam.php?x=1'), $viewer);
check('admin "which promotion shows here" tool (path normalised)', $s === 200 && $d['path'] === '/miss-assam.php' && ($d['promotion']['id'] ?? 0) === $allId);
[$s, $d] = call('POST', "events.php?action=duplicate&id=$allId", $editor, []);
check('duplicate makes a draft copy', $s === 201 && $d['status'] === 'draft' && str_starts_with($d['title'], 'Copy of') && $d['targets'] === ['all']);
[$s] = call('DELETE', "events.php?id=$draftId", $viewer);
check('a viewer cannot delete events', $s === 403, "got $s");
[$s] = call('DELETE', "events.php?id=$draftId", $editor);
check('delete works', $s === 200 && call('GET', "events.php?id=$draftId", $editor)[0] === 404);
[$s] = call('POST', 'promotions.php', $editor, []);
check('public endpoint is read-only', $s === 405, "got $s");
[$s, $d] = call('GET', 'audit.php?action=list&endpoint=events.php', $owner);
check('event changes are in the activity log', ($d['total'] ?? 0) >= 6, json_encode($d['total'] ?? null));

echo "Profile Changes (change log)\n";
$pdo->exec('ALTER TABLE registration ADD mobile VARCHAR(20) NULL');
[$s, $d, $raw] = call('POST', 'changelog.php?action=install', $admin, []);
check('turns on (self-test, then triggers)', $s === 200 && $d['on'] === true, "got $s " . substr($raw, 0, 200));
$pdo->exec("UPDATE registration SET meta_title = 'New title', mobile = '9000000001' WHERE id = 1");
[$s, $d] = call('GET', 'changelog.php?action=list&q=1', $viewer);
$c = $d['rows'][0] ?? [];
check('records what changed, before and after', ($c['profile_id'] ?? 0) === 1 && in_array('meta_title', $c['fields'] ?? [], true) && ($c['changes']['meta_title'][1] ?? '') === 'New title', json_encode($c));
check('mobile change is noted without its value', in_array('mobile', $c['fields'] ?? [], true) && !str_contains(json_encode($c), '9000000001'));
[$s, $d] = call('POST', 'changelog.php?action=uninstall', $admin, []);
$left = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '$dbName'")->fetchColumn();
check('turns off and leaves no triggers', $s === 200 && $left === 0, "triggers left: $left");

echo "News: script cannot be stored\n";
[$s, $d, $raw] = call('POST', 'news.php', $editor, ['title' => 'XSS test', 'content_html' => '<p>Hi</p><script>alert(1)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">x</a><iframe src="//evil"></iframe>']);
$html = (string) ($d['content_html'] ?? '');
check('script, event handlers, javascript: links and iframes are removed', $s === 201 && str_contains($html, '<p>Hi</p>') && !preg_match('/script|onerror|javascript:|iframe/i', $html), "got $s $html");
[$s] = call('POST', 'news.php', $editor, ['title' => 'Bad image', 'featured_image_url' => 'javascript:alert(1)']);
check('javascript: image address is refused', $s === 422, "got $s");

echo "Image upload\n";
function upload(string $tok, string $file, string $name): array
{
    global $httpPort;
    $ch = curl_init("http://127.0.0.1:$httpPort/api/upload.php");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["X-Admin-Token: $tok"],
        CURLOPT_POSTFIELDS => ['image' => new CURLFile($file, '', $name)]]);
    $raw = (string) curl_exec($ch);
    return [(int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), json_decode($raw, true)];
}
file_put_contents("$tmp/fake.jpg", '<?php echo "hi"; ?>');
file_put_contents("$tmp/x.svg", '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');
$im = imagecreatetruecolor(20, 10);
imagepng($im, "$tmp/ok.png");
[$s] = upload($editor, "$tmp/fake.jpg", 'photo.jpg');
check('PHP renamed to .jpg is refused', $s === 415, "got $s");
[$s] = upload($editor, "$tmp/x.svg", 'x.svg');
check('SVG is refused', $s === 415, "got $s");
[$s, $d] = upload($editor, "$tmp/ok.png", 'ok.png');
check('a real image is stored under a random name', $s === 201 && preg_match('#/[0-9a-f]{32}\.png$#', (string) ($d['url'] ?? '')) && ($d['width'] ?? 0) === 20, "got $s " . json_encode($d));
[$s] = upload($viewer, "$tmp/ok.png", 'ok.png');
check('a viewer cannot upload', $s === 403, "got $s");

echo "Sessions\n";
[$s] = call('GET', 'auth.php', substr($editor, 0, -1) . (substr($editor, -1) === 'a' ? 'b' : 'a'));
check('a changed token is refused', $s === 401, "got $s");
$parts = explode('.', $editor);
$parts[1] = bin2hex('owner1');
[$s] = call('GET', 'auth.php', implode('.', $parts));
check('another user\'s name in a token is refused', $s === 401, "got $s");

echo "Server Cleanup safety\n";
file_put_contents("$tmp/site/old-page.php.bak", "<?php echo 'old';");
file_put_contents("$tmp/site/config.php", "<?php \$db_pass = 'Real-Secret-1';");
file_put_contents("$tmp/site/config.php.bak", "<?php\n\$host = 'localhost';\n\$password = 'Sup3r-Secret-9';\n\$db_pass=\"Other-Secret-2\";\ndefine('API_KEY', 'Key-Secret-3');\n");
foreach (['../config.php', 'config.php', 'cms/backend/config.php', 'missindia_profile/../config.php', '/etc/passwd'] as $bad) {
    [$s] = call('GET', 'cleanup.php?action=preview&path=' . urlencode($bad), $admin);
    check("refuses $bad", $s === 422, "got $s");
}
[$s, $d] = call('GET', 'cleanup.php?action=preview&path=old-page.php.bak', $admin);
check('previews a real backup file', $s === 200 && ($d['kind'] ?? '') === 'text', "got $s");
[$s, $d] = call('GET', 'cleanup.php?action=preview&path=config.php.bak', $admin);
file_put_contents("$tmp/site/.env.bak", "DB_PASSWORD=Env-Secret-4\nAPP_NAME=fsia\n");
[$s2, $d2] = call('GET', 'cleanup.php?action=preview&path=.env.bak', $admin);
check('...also in .env style files', $s2 === 200 && (!str_contains(json_encode($d2), 'Env-Secret-4') && str_contains((string) $d2['text'], 'APP_NAME=fsia')), "got $s2 " . json_encode($d2['text'] ?? $d2));
check('preview hides passwords and keys in old config copies', $s === 200 && !preg_match('/Sup3r-Secret-9|Other-Secret-2|Key-Secret-3/', json_encode($d)) && str_contains((string) ($d['text'] ?? ''), 'localhost'), json_encode($d['text'] ?? $d));
[$s, $d] = call('POST', 'cleanup.php?action=trash', $admin, ['paths' => ['old-page.php.bak']]);
check('moves a file to Trash', $s === 200 && ($d['moved'] ?? 0) === 1 && !is_file("$tmp/site/old-page.php.bak"), json_encode($d));
[$s, $d] = call('GET', 'cleanup.php?action=trash_list', $admin);
$batch = $d['batches'][0]['batch'] ?? '';
[$s] = call('POST', 'cleanup.php?action=restore', $admin, ['batch' => $batch]);
check('restores it', $s === 200 && is_file("$tmp/site/old-page.php.bak"), "got $s");
call('POST', 'cleanup.php?action=trash', $admin, ['paths' => ['old-page.php.bak']]);
$batch = call('GET', 'cleanup.php?action=trash_list', $admin)[1]['batches'][0]['batch'] ?? '';
[$s, $d] = call('POST', 'cleanup.php?action=purge', $admin, ['batch' => $batch]);
check('cannot delete for good before 7 days', $s === 422, "got $s");

echo "Logging failure never breaks a request\n";
$pdo->exec("RENAME TABLE `$dbName`.cms_audit_log TO `$dbName`.cms_audit_log_x");
$pdo->exec("CREATE VIEW `$dbName`.cms_audit_log AS SELECT 1 AS id");
[$s] = call('PUT', 'seo.php?page=about', $editor, $seoBody);
check('a change still saves when the log cannot be written', $s === 200, "got $s");

echo "Sign-in rate limit\n";
for ($i = 0; $i < 9; $i++) {
    [$s] = call('POST', 'auth.php', null, ['username' => 'owner1', 'password' => 'wrong']);
}
check('after 8 failed sign-ins the next try is blocked', $s === 429, "got $s");
@unlink(sys_get_temp_dir() . '/fsia-cms-login/' . sha1('127.0.0.1') . '.json');

echo "\n$count checks, $failures failed\n";
exit($failures ? 1 : 0);
