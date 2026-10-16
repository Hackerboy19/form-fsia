<?php
/*
 * Upload check: every CMS feature, signed in as the site's EXISTING admin, after
 * the zip was extracted over a copy of the old site. It writes test content
 * (a team member, an article, a profile edit), so run it ONLY on a copy:
 *
 *   1. git archive 3315ddd cms/backend cms/database | tar -x -C copy/   (old live version)
 *      + config.php, admin_users.php (set-password.php), a registration table
 *   2. unzip -o fsia-admin-tools-upload.zip into copy/
 *   3. php -S 127.0.0.1:8090 -t copy/  and  php cms/tests/upgrade_check.php
 *
 * SIM_USER / SIM_PASS / SIM_DB_* override the defaults used below.
 */
$base = getenv('SIM_BASE') ?: 'http://127.0.0.1:8090/cms/backend/api/';
$simUser = getenv('SIM_USER') ?: 'fsiaadmin';
$simPass = getenv('SIM_PASS') ?: 'Live-Admin-Pass-7';
$fails = 0; $n = 0;
function call($m, $p, $tok = null, $body = null) {
    global $base;
    $ch = curl_init($base . $p);
    $h = ['Accept: application/json'];
    if ($tok) $h[] = "X-Admin-Token: $tok";
    if ($body !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90]);
    $raw = (string) curl_exec($ch);
    return [(int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), json_decode($raw, true), $raw];
}
function ok($name, $cond, $detail = '') { global $fails, $n; $n++; if (!$cond) $fails++; echo ($cond ? '  ok   ' : '  FAIL ') . $name . ($cond ? '' : "  -- $detail") . "\n"; }

[$s, $d] = call('POST', 'auth.php', null, ['username' => $simUser, 'password' => $simPass]);
ok('existing admin signs in with the same username and password', $s === 200 && !empty($d['token']), "got $s");
$t = $d['token'];
[$s] = call('POST', 'auth.php', null, ['username' => $simUser, 'password' => 'wrong']);
ok('wrong password still refused', $s === 401);
[$s, $d] = call('GET', 'users.php?action=me', $t);
ok('single admin = full access, roles not available', $s === 200 && $d['role'] === 'owner' && $d['roles_available'] === false && count($d['permissions']) === 7, json_encode($d));
[$s] = call('POST', 'users.php?action=setup', $t, []);
ok('roles cannot be switched on from the admin (no extra users)', $s === 409, "got $s");

// Old endpoints (unchanged files) with the new bootstrap
[$s, $d] = call('GET', 'seo.php', $t);
ok('SEO list', $s === 200 && count($d) >= 1, "got $s");
[$s, $d] = call('GET', 'seo.php?page=about', $t);
$seo = $d;
[$s, $d, $raw] = call('PUT', 'seo.php?page=about', $t, ['meta_title' => 'About Us | Forever Star India (sim)', 'meta_description' => $seo['meta_description'] ?? 'x', 'meta_keywords' => $seo['meta_keywords'] ?? '', 'canonical_url' => $seo['canonical_url'] ?? '', 'og_image' => $seo['og_image'] ?? '']);
ok('SEO save', $s === 200 && $d['meta_title'] === 'About Us | Forever Star India (sim)', "got $s $raw");
[$s, $d] = call('GET', 'sections.php?page=about', $t);
ok('page sections read', $s === 200, "got $s");
[$s, $d, $raw] = call('PUT', 'sections.php?page=about&key=hero', $t, ['content' => ['eyebrow' => 'About', 'title' => 'Forever Star India', 'subtitle' => 'sim'], 'image_url' => '']);
ok('page section save', $s === 200, "got $s $raw");
[$s, $d] = call('GET', 'teams.php?include_inactive=1', $t);
ok('team list (' . (is_array($d) ? count($d) : 0) . ' members)', $s === 200 && count($d) > 20, "got $s");
[$s, $d, $raw] = call('POST', 'teams.php', $t, ['name' => 'Sim Member', 'designation' => 'Tester', 'category' => 'Mentors', 'is_active' => true]);
ok('team add', $s === 201, "got $s $raw");
$tid = $d['id'] ?? 0;
[$s, $d, $raw] = call('PUT', "teams.php?id=$tid", $t, ['name' => 'Sim Member 2', 'designation' => 'Tester', 'category' => 'Mentors', 'is_active' => false]);
ok('team edit', $s === 200, "got $s $raw");
[$s] = call('DELETE', "teams.php?id=$tid", $t);
ok('team delete', $s === 200 || $s === 204, "got $s");
[$s, $d, $raw] = call('POST', 'news.php', $t, ['title' => 'Sim article', 'content_html' => '<p>Hello</p><script>x</script>']);
ok('news add (script removed)', $s === 201 && !str_contains($d['content_html'], 'script'), "got $s $raw");
$nid = $d['id'] ?? 0;
[$s] = call('PUT', "news.php?id=$nid", $t, ['title' => 'Sim article 2', 'content_html' => '<p>Hi</p>']);
ok('news edit', $s === 200, "got $s");
[$s] = call('DELETE', "news.php?id=$nid", $t);
ok('news delete', $s === 200 || $s === 204, "got $s");
$im = imagecreatetruecolor(30, 20); $png = sys_get_temp_dir() . '/upgrade-check.png';
imagepng($im, $png);
$ch = curl_init($base . 'upload.php');
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ["X-Admin-Token: $t"], CURLOPT_POSTFIELDS => ['image' => new CURLFile($png, 'image/png', 'ok.png')]]);
$up = json_decode((string) curl_exec($ch), true);
ok('image upload', (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 201 && !empty($up['url']), json_encode($up));

// New tools
[$s, $d] = call('GET', 'sitecheck.php?action=urls', $t);
ok('Site Check: page list from .htaccess', $s === 200 && in_array('/asha-full', $d['profiles'] ?? [], true), "got $s");
[$s, $d] = call('GET', 'sitecheck.php?action=check&url=/quickapply', $t);
ok('Site Check: live check of www.fsia.in/quickapply', $s === 200 && ($d['status'] ?? 0) === 200, "got $s status " . ($d['status'] ?? '?'));
[$s, $d] = call('GET', 'sitecheck.php?action=profiles&sort=score', $t);
ok('Profiles & Score: 2 profiles, scores 100 and 20', $s === 200 && array_column($d['rows'] ?? [], 'score', 'id') == [2 => 20, 1 => 100], json_encode(array_column($d['rows'] ?? [], 'score', 'id')));
[$s, $d] = call('POST', 'sitecheck.php?action=baseline', $t, ['pages' => [['url' => 'https://www.fsia.in/quickapply', 'title' => 'Apply Now', 'description' => '', 'keywords' => '', 'canonical' => '', 'h1' => []]]]);
ok('Site Check: save baseline', $s === 200, "got $s");
[$s, $d, $raw] = call('POST', 'changelog.php?action=install', $t, []);
ok('Profile Changes: turn on', $s === 200 && $d['on'] === true, "got $s $raw");
$pdo = new PDO('mysql:host=127.0.0.1;dbname=' . (getenv('SIM_DB_NAME') ?: 'fsia_sim'), getenv('SIM_DB_USER') ?: 'tester', getenv('SIM_DB_PASS') ?: 'tester-pass');
$pdo->exec("UPDATE registration SET meta_title = 'Bina Bare | Mrs India' WHERE id = 2");
[$s, $d] = call('GET', 'changelog.php?action=list', $t);
ok('Profile Changes: edit recorded', $s === 200 && ($d['rows'][0]['profile_id'] ?? 0) === 2, json_encode($d['rows'][0] ?? null));
[$s] = call('POST', 'changelog.php?action=uninstall', $t, []);
ok('Profile Changes: turn off', $s === 200);
[$s, $d] = call('GET', 'cleanup.php?action=scan', $t);
$found = array_merge(...array_map(fn ($g) => array_column($g['items'], 'path'), array_values($d['groups'] ?? [])));
ok('Server Cleanup: scan finds the junk files, not config.php/.htaccess', $s === 200 && in_array('contact.php.old', $found, true) && in_array('contact-submissions.log', $found, true) && !in_array('config.php', $found, true) && !in_array('.htaccess', $found, true), json_encode($found));
[$s, $d] = call('GET', 'cleanup.php?action=preview&path=config.php.bak', $t);
ok('Server Cleanup: preview hides the password', $s === 200 && !str_contains(json_encode($d), 'SimSecret-1'), json_encode($d['text'] ?? $d));
[$s, $d] = call('POST', 'cleanup.php?action=trash', $t, ['paths' => ['contact.php.old']]);
ok('Server Cleanup: move to Trash', $s === 200 && $d['moved'] === 1);
[$s, $d] = call('GET', 'cleanup.php?action=trash_list', $t);
[$s] = call('POST', 'cleanup.php?action=restore', $t, ['batch' => $d['batches'][0]['batch'] ?? '']);
ok('Server Cleanup: restore', $s === 200 && $s === 200);
[$s, $d] = call('GET', 'audit.php?action=list&days=1', $t);
ok('Activity Log: changes recorded under fsiaadmin', $s === 200 && $d['total'] >= 15 && in_array($simUser, $d['users'], true), "got $s total " . ($d['total'] ?? '?'));
ok('Activity Log: password never stored', !str_contains(json_encode($d), $simPass));
echo "\n$n checks, $fails failed\n";
exit($fails ? 1 : 0);
