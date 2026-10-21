<?php
// Har ghante chalane wali file.
// Plesk Scheduled Task: "Run a PHP script" -> yeh file
//   ya "Fetch a URL" -> https://www.fsia.in/fsiaadmm21/site-monitor/cron.php?key=CRON_KEY
require __DIR__ . '/lib.php';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    $cfg = sm_config();
    $key = (string)($_GET['key'] ?? '');
    if ($cfg['cron_key'] === '' || strpos($cfg['cron_key'], 'CHANGE') === 0 || !hash_equals($cfg['cron_key'], $key)) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $res = sm_run($cli ? 'cron' : 'url');
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
if (!$res['ok']) {
    echo $res['msg'] . "\n";
    exit(0);
}
$s = $res['stats'];
echo date('Y-m-d H:i:s') . " OK run #{$res['run_id']}\n";
echo "Files: {$s['files_total']} (naye {$s['files_added']}, badle {$s['files_changed']}, delete {$s['files_deleted']}, wapas lagaye {$s['files_restored']})\n";
echo "Profiles: {$s['profiles_total']} (badlaav {$s['profile_changes']})\n";
echo "Pages down: {$s['pages_down']}\n";
echo "Links: {$s['links_total']} (abhi check {$s['links_checked']}, toote {$s['links_broken']})\n";
foreach ($res['errors'] as $e) echo "ERROR: $e\n";
