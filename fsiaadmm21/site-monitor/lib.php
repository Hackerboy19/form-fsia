<?php
// Site Monitor: har ghante files, profile URLs aur pages check karta hai.
// cron.php isko chalata hai; index.php (admin page) iska data dikhata hai.

function sm_config()
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
        date_default_timezone_set($cfg['timezone']);
    }
    return $cfg;
}

function sm_db()
{
    static $db = null;
    if ($db !== null) return $db;
    $cfg = sm_config();
    mysqli_report(MYSQLI_REPORT_OFF);
    $connect = null;
    // Site ka apna config.php: isme $connect banta hai
    include $cfg['db_config'];
    if (!($connect instanceof mysqli)) {
        throw new RuntimeException('DB connection nahi mila (config.php mein $connect nahi bana).');
    }
    mysqli_set_charset($connect, 'utf8mb4');
    mysqli_query($connect, "SET time_zone = '" . date('P') . "'");
    $db = $connect;
    return $db;
}

function sm_q($sql)
{
    $db = sm_db();
    $r = mysqli_query($db, $sql);
    if ($r === false) {
        throw new RuntimeException('DB error: ' . mysqli_error($db) . ' | ' . substr($sql, 0, 200));
    }
    return $r;
}

function sm_e($s)
{
    return mysqli_real_escape_string(sm_db(), (string)$s);
}

function sm_rows($sql)
{
    $r = sm_q($sql);
    $out = [];
    while ($row = mysqli_fetch_assoc($r)) $out[] = $row;
    return $out;
}

function sm_install()
{
    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        started_at DATETIME NOT NULL,
        finished_at DATETIME NULL,
        trigger_by VARCHAR(20) NOT NULL DEFAULT 'cron',
        files_total INT DEFAULT 0, files_added INT DEFAULT 0, files_changed INT DEFAULT 0,
        files_deleted INT DEFAULT 0, files_restored INT DEFAULT 0,
        profiles_total INT DEFAULT 0, profile_changes INT DEFAULT 0,
        links_total INT DEFAULT 0, links_checked INT DEFAULT 0, links_broken INT DEFAULT 0,
        pages_down INT DEFAULT 0, alert_sent TINYINT DEFAULT 0,
        is_baseline TINYINT DEFAULT 0, error TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        run_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        area VARCHAR(20) NOT NULL,
        type VARCHAR(40) NOT NULL,
        severity VARCHAR(10) NOT NULL,
        target VARCHAR(700) NOT NULL,
        details TEXT NULL,
        KEY k_time (created_at), KEY k_area (area), KEY k_run (run_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_files (
        path_hash CHAR(32) PRIMARY KEY,
        path VARCHAR(700) NOT NULL,
        size BIGINT NOT NULL,
        mtime INT NOT NULL,
        hash CHAR(32) NOT NULL,
        seen_run INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_profiles (
        id INT PRIMARY KEY,
        url VARCHAR(700) NOT NULL,
        status INT NOT NULL,
        suspended INT NOT NULL,
        name VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_links (
        url_hash CHAR(32) PRIMARY KEY,
        url VARCHAR(1000) NOT NULL,
        in_sitemap TINYINT NOT NULL DEFAULT 1,
        first_seen DATETIME NOT NULL,
        last_checked DATETIME NULL,
        last_code INT NULL,
        last_ok DATETIME NULL,
        broken_since DATETIME NULL,
        KEY k_check (in_sitemap, last_checked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_pages (
        path VARCHAR(255) PRIMARY KEY,
        last_checked DATETIME NULL,
        last_code INT NULL,
        last_ms INT NULL,
        down_since DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ---------- data folder (backups, lock) ----------

function sm_data_dir()
{
    static $dir = null;
    if ($dir !== null) return $dir;
    $cfg = sm_config();
    $want = $cfg['data_dir'];
    if ((is_dir($want) || @mkdir($want, 0750, true)) && is_writable($want)) {
        $dir = $want;
    } else {
        $dir = __DIR__ . '/data';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($dir . '/index.html', '');
    }
    return $dir;
}

function sm_backup_path($rel)
{
    return sm_data_dir() . '/backup/' . $rel;
}

function sm_save_backup($rel, $abs)
{
    $cfg = sm_config();
    if (@filesize($abs) > $cfg['backup_max_bytes']) return false;
    $dst = sm_backup_path($rel);
    $d = dirname($dst);
    if (!is_dir($d)) @mkdir($d, 0750, true);
    if (is_file($dst)) @copy($dst, $dst . '.prev'); // ek purana version bhi rakho
    return @copy($abs, $dst);
}

// ---------- events ----------

function sm_event($run_id, $area, $type, $severity, $target, $details = '')
{
    // ek run mein profiles/links ki 400 se zyada entries nahi (bulk update par list na bhare)
    static $count = [];
    $k = $run_id . $area;
    $count[$k] = ($count[$k] ?? 0) + 1;
    if (in_array($area, ['profiles', 'links'], true) && $count[$k] > 400) {
        if ($count[$k] === 401) {
            $type = 'too_many';
            $severity = 'info';
            $target = '(aur bhi badlaav)';
            $details = 'Is check mein bahut saare badlaav the, baaki ki list nahi dikhayi gayi.';
        } else {
            return;
        }
    }
    sm_q("INSERT INTO site_monitor_events (run_id, created_at, area, type, severity, target, details) VALUES ("
        . (int)$run_id . ", NOW(), '" . sm_e($area) . "', '" . sm_e($type) . "', '" . sm_e($severity) . "', '"
        . sm_e(mb_substr($target, 0, 700)) . "', '" . sm_e($details) . "')");
    $GLOBALS['sm_new_events'][] = compact('area', 'type', 'severity', 'target', 'details');
}

function sm_fmt_time($ts)
{
    return $ts ? date('d M Y, h:i A', is_numeric($ts) ? $ts : strtotime($ts)) : '-';
}

// ---------- 1. files ----------

function sm_scan_files()
{
    $cfg = sm_config();
    $root = rtrim($cfg['site_root'], '/');
    $ext = array_flip(array_map('strtolower', $cfg['watch_ext']));
    $skip = array_flip(array_map('strtolower', $cfg['skip_dirs']));
    $data = realpath(sm_data_dir());
    $files = [];
    $stack = [''];
    while ($stack && count($files) < $cfg['max_files']) {
        $rel = array_pop($stack);
        $abs = $root . ($rel === '' ? '' : '/' . $rel);
        $h = @opendir($abs);
        if (!$h) continue;
        while (($name = readdir($h)) !== false) {
            if ($name === '.' || $name === '..') continue;
            $r = $rel === '' ? $name : $rel . '/' . $name;
            $a = $abs . '/' . $name;
            if (is_link($a)) continue;
            if (is_dir($a)) {
                if (isset($skip[strtolower($name)])) continue;
                if ($data && realpath($a) === $data) continue;
                $stack[] = $r;
                continue;
            }
            $e = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!isset($ext[$e])) continue;
            $st = @stat($a);
            if (!$st) continue;
            $files[$r] = ['size' => $st['size'], 'mtime' => $st['mtime']];
        }
        closedir($h);
    }
    return $files;
}

function sm_check_files($run_id, $baseline, &$stats)
{
    $cfg = sm_config();
    $root = rtrim($cfg['site_root'], '/');
    $now = sm_scan_files();

    $old = [];
    foreach (sm_rows("SELECT path, size, mtime, hash FROM site_monitor_files") as $row) {
        $old[$row['path']] = $row;
    }
    $last = sm_rows("SELECT MAX(finished_at) t FROM site_monitor_runs WHERE finished_at IS NOT NULL AND trigger_by <> 'admin' AND id <> " . (int)$run_id);
    $since = $last[0]['t'] ? 'Pichhla check: ' . sm_fmt_time($last[0]['t']) : '';

    // Key files: agar abhi gayab hain aur backup hai to wapas lagao
    foreach ($cfg['key_files'] as $kf) {
        $abs = $root . '/' . $kf;
        if (isset($now[$kf]) || !isset($old[$kf])) continue;
        $bk = sm_backup_path($kf);
        if ($cfg['auto_restore'] && is_file($bk) && @copy($bk, $abs)) {
            @touch($abs, (int)$old[$kf]['mtime']);
            $st = stat($abs);
            $now[$kf] = ['size' => $st['size'], 'mtime' => $st['mtime']];
            $stats['files_restored']++;
            sm_event($run_id, 'files', 'file_deleted', 'critical', $kf,
                "Key file DELETE ho gayi thi. $since. Backup se apne aap wapas laga di gayi (" . sm_fmt_time(time()) . ").");
            sm_event($run_id, 'files', 'file_restored', 'info', $kf, 'Backup se wapas lagayi gayi. Size: ' . number_format($st['size']) . ' bytes.');
            $old[$kf]['restored'] = true;
        }
    }

    $added = $changed = $deleted = 0;
    $limit = 300; // ek run mein ek type ki zyada se zyada entries
    $vals = [];
    foreach ($now as $path => $f) {
        $abs = $root . '/' . $path;
        $o = $old[$path] ?? null;
        if ($o && (int)$o['size'] === (int)$f['size'] && (int)$o['mtime'] === (int)$f['mtime']) {
            $hash = $o['hash'];
        } else {
            $hash = @md5_file($abs) ?: '';
        }
        if (!$o) {
            $added++;
            if (!$baseline) {
                if ($added <= $limit) {
                    sm_event($run_id, 'files', 'file_added', 'info', $path,
                        'Nayi file. Size: ' . number_format($f['size']) . ' bytes. Time: ' . sm_fmt_time($f['mtime']));
                }
            }
            sm_save_backup($path, $abs);
        } elseif ($o['hash'] !== $hash && empty($o['restored'])) {
            $changed++;
            $sev = in_array($path, $cfg['key_files'], true) ? 'warning' : 'info';
            if ($changed <= $limit) {
                $diff = (int)$f['size'] - (int)$o['size'];
                sm_event($run_id, 'files', 'file_changed', $sev, $path,
                    'File badli gayi: ' . sm_fmt_time($f['mtime']) . '. Size ' . number_format($o['size']) . ' → '
                    . number_format($f['size']) . ' bytes (' . ($diff >= 0 ? '+' : '') . number_format($diff) . ').');
            }
            sm_save_backup($path, $abs);
        } elseif (!is_file(sm_backup_path($path))) {
            sm_save_backup($path, $abs);
        }
        $vals[] = "('" . md5($path) . "', '" . sm_e($path) . "', " . (int)$f['size'] . ', ' . (int)$f['mtime'] . ", '" . $hash . "', " . (int)$run_id . ')';
        if (count($vals) >= 500) {
            sm_upsert_files($vals);
            $vals = [];
        }
    }
    if ($vals) sm_upsert_files($vals);

    foreach ($old as $path => $o) {
        if (isset($now[$path])) continue;
        $deleted++;
        if ($deleted > $limit) continue;
        $isKey = in_array($path, $cfg['key_files'], true);
        $bk = is_file(sm_backup_path($path)) ? ' Backup hai: admin page se "Wapas lagao" daba sakte hain.' : ' Backup nahi mila.';
        sm_event($run_id, 'files', 'file_deleted', $isKey ? 'critical' : 'warning', $path,
            'File DELETE ho gayi. Aakhri baar dekhi: ' . sm_fmt_time($o['mtime']) . ' (last modified). ' . $since . '.' . $bk);
    }
    sm_q("DELETE FROM site_monitor_files WHERE seen_run <> " . (int)$run_id);

    if ($added > $limit && !$baseline) sm_event($run_id, 'files', 'file_added', 'info', '(aur ' . ($added - $limit) . ' files)', 'Bahut saari nayi files, list chhoti ki gayi.');
    if ($changed > $limit) sm_event($run_id, 'files', 'file_changed', 'info', '(aur ' . ($changed - $limit) . ' files)', 'Bahut saari files badli, list chhoti ki gayi.');
    if ($deleted > $limit) sm_event($run_id, 'files', 'file_deleted', 'critical', '(aur ' . ($deleted - $limit) . ' files)', 'Bahut saari files delete hui!');

    $stats['files_total'] = count($now);
    $stats['files_added'] = $baseline ? 0 : $added;
    $stats['files_changed'] = $changed;
    $stats['files_deleted'] = $deleted + $stats['files_restored'];
}

function sm_upsert_files($vals)
{
    sm_q("INSERT INTO site_monitor_files (path_hash, path, size, mtime, hash, seen_run) VALUES " . implode(',', $vals)
        . " ON DUPLICATE KEY UPDATE path=VALUES(path), size=VALUES(size), mtime=VALUES(mtime), hash=VALUES(hash), seen_run=VALUES(seen_run)");
}

// ---------- 2. profiles (registration.url) ----------

function sm_check_profiles($run_id, $baseline, &$stats)
{
    $cfg = sm_config();
    $base = rtrim($cfg['base_url'], '/');
    $cur = [];
    $r = sm_q("SELECT id, url, status, suspended, CONCAT(first_name, ' ', last_name) name FROM registration WHERE url <> ''");
    while ($row = mysqli_fetch_assoc($r)) $cur[(int)$row['id']] = $row;

    $old = [];
    $r = sm_q("SELECT id, url, status, suspended, name FROM site_monitor_profiles");
    while ($row = mysqli_fetch_assoc($r)) $old[(int)$row['id']] = $row;

    $n = 0;
    if (!$baseline) {
        foreach ($cur as $id => $p) {
            $o = $old[$id] ?? null;
            $who = trim($p['name']) . " (ID $id)";
            if (!$o) {
                $n++;
                sm_event($run_id, 'profiles', 'profile_url_added', 'info', $base . '/' . $p['url'], "Naya profile URL: $who");
                continue;
            }
            if ($o['url'] !== $p['url']) {
                $n++;
                sm_event($run_id, 'profiles', 'profile_url_changed', 'warning', $base . '/' . $p['url'],
                    "$who ka URL badla. Purana: $base/{$o['url']} → Naya: $base/{$p['url']}. Purana link ab toot sakta hai (redirect lagayein).");
            }
            if ((int)$o['status'] !== (int)$p['status']) {
                $n++;
                sm_event($run_id, 'profiles', 'profile_status_changed', 'info', $base . '/' . $p['url'],
                    "$who ka status {$o['status']} → {$p['status']} hua.");
            }
            if ((int)$o['suspended'] !== (int)$p['suspended']) {
                $n++;
                sm_event($run_id, 'profiles', 'profile_suspended', (int)$p['suspended'] ? 'warning' : 'info', $base . '/' . $p['url'],
                    "$who " . ((int)$p['suspended'] ? 'SUSPEND kiya gaya.' : 'ab suspend nahi hai.'));
            }
        }
        $gone = array_diff_key($old, $cur);
        if ($gone) {
            $ids = implode(',', array_map('intval', array_keys($gone)));
            $still = [];
            foreach (sm_rows("SELECT id FROM registration WHERE id IN ($ids)") as $row) $still[(int)$row['id']] = true;
            foreach ($gone as $id => $o) {
                $n++;
                $who = trim($o['name']) . " (ID $id)";
                if (isset($still[$id])) {
                    sm_event($run_id, 'profiles', 'profile_url_removed', 'warning', $base . '/' . $o['url'],
                        "$who ka URL khali kar diya gaya. Yeh link ab kaam nahi karega.");
                } else {
                    sm_event($run_id, 'profiles', 'profile_deleted', 'warning', $base . '/' . $o['url'],
                        "$who ka profile DB se DELETE ho gaya.");
                }
            }
        }
    }

    // snapshot update
    sm_q("DELETE FROM site_monitor_profiles");
    $vals = [];
    foreach ($cur as $id => $p) {
        $vals[] = '(' . $id . ", '" . sm_e($p['url']) . "', " . (int)$p['status'] . ', ' . (int)$p['suspended'] . ", '" . sm_e(mb_substr(trim($p['name']), 0, 255)) . "')";
        if (count($vals) >= 500) {
            sm_q("INSERT INTO site_monitor_profiles (id, url, status, suspended, name) VALUES " . implode(',', $vals));
            $vals = [];
        }
    }
    if ($vals) sm_q("INSERT INTO site_monitor_profiles (id, url, status, suspended, name) VALUES " . implode(',', $vals));

    $stats['profiles_total'] = count($cur);
    $stats['profile_changes'] = $n;
}

// ---------- 3. HTTP checks ----------

function sm_http_many($urls)
{
    $cfg = sm_config();
    $out = [];
    $mh = curl_multi_init();
    $queue = $urls;
    $active = [];
    $add = function () use (&$queue, &$active, $mh, $cfg) {
        while ($queue && count($active) < $cfg['parallel_requests']) {
            $u = array_shift($queue);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $u,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $cfg['request_timeout'],
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'FSIA-SiteMonitor/1.0',
                CURLOPT_HTTPHEADER => ['Cache-Control: no-cache'],
                CURLOPT_ENCODING => '',
            ]);
            curl_multi_add_handle($mh, $ch);
            $active[(int)$ch] = [$u, $ch, microtime(true)];
        }
    };
    $add();
    do {
        curl_multi_exec($mh, $running);
        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            [$u, , $t0] = $active[(int)$ch];
            $body = curl_multi_getcontent($ch);
            $out[$u] = [
                'code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'ms' => (int)((microtime(true) - $t0) * 1000),
                'body' => $body,
                'location' => (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL),
                'error' => curl_error($ch),
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($active[(int)$ch]);
            $add();
        }
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running || $active);
    curl_multi_close($mh);
    return $out;
}

function sm_page_problem($res)
{
    if ($res['code'] === 0) return 'Site se jawab nahi aaya (' . ($res['error'] ?: 'timeout') . ')';
    if ($res['code'] >= 400) return 'HTTP ' . $res['code'];
    if ($res['code'] === 200) {
        if (trim($res['body']) === '') return 'Page khali aa raha hai';
        if (preg_match('/(Fatal error|Parse error)<\/b>:|Fatal error: /i', $res['body'])) return 'Page par PHP Fatal error';
    }
    return '';
}

function sm_check_pages($run_id, &$stats)
{
    $cfg = sm_config();
    $base = rtrim($cfg['base_url'], '/');
    $urls = [];
    foreach ($cfg['key_pages'] as $p) $urls[$base . $p . (strpos($p, '?') === false ? '?' : '&') . 'smv=' . time()] = $p;
    $res = sm_http_many(array_keys($urls));
    $prev = [];
    foreach (sm_rows("SELECT * FROM site_monitor_pages") as $row) $prev[$row['path']] = $row;
    $down = 0;
    foreach ($urls as $u => $p) {
        $r = $res[$u];
        $prob = sm_page_problem($r);
        $was = $prev[$p] ?? null;
        $wasDown = $was && $was['down_since'];
        if ($prob) {
            $down++;
            if (!$wasDown) {
                sm_event($run_id, 'pages', 'page_down', 'critical', $base . $p, "Page nahi khul raha: $prob.");
            }
            $ds = $wasDown ? "'" . sm_e($was['down_since']) . "'" : 'NOW()';
        } else {
            if ($wasDown) {
                sm_event($run_id, 'pages', 'page_up', 'info', $base . $p, 'Page phir se theek khul raha hai (down tha: ' . sm_fmt_time($was['down_since']) . ' se).');
            }
            $ds = 'NULL';
        }
        sm_q("INSERT INTO site_monitor_pages (path, last_checked, last_code, last_ms, down_since) VALUES ('" . sm_e($p) . "', NOW(), "
            . (int)$r['code'] . ', ' . (int)$r['ms'] . ", $ds) ON DUPLICATE KEY UPDATE last_checked=VALUES(last_checked), last_code=VALUES(last_code), last_ms=VALUES(last_ms), down_since=$ds");
    }
    sm_q("DELETE FROM site_monitor_pages WHERE path NOT IN ('" . implode("','", array_map('sm_e', $cfg['key_pages'])) . "')");
    $stats['pages_down'] = $down;
}

function sm_extract_locs($xml)
{
    preg_match_all('#<loc>\s*(.*?)\s*</loc>#is', $xml, $m);
    return array_map(function ($u) { return html_entity_decode($u, ENT_QUOTES | ENT_XML1, 'UTF-8'); }, $m[1]);
}

function sm_check_links($run_id, $baseline, &$stats)
{
    $cfg = sm_config();
    $base = rtrim($cfg['base_url'], '/');

    // sitemap se saare links lo (index ho to andar ke sitemaps bhi)
    $queue = [];
    foreach ($cfg['sitemap_sources'] as $s) $queue[] = preg_match('#^https?://#', $s) ? $s : $base . $s;
    $seenMaps = [];
    $links = [];
    $fetchOk = false;
    while ($queue && count($seenMaps) < 60) {
        $batch = array_splice($queue, 0, 8);
        foreach (sm_http_many($batch) as $u => $r) {
            $seenMaps[$u] = true;
            if ($r['code'] !== 200) {
                sm_event($run_id, 'links', 'sitemap_error', 'warning', $u, 'Sitemap nahi khula: HTTP ' . $r['code']);
                continue;
            }
            $fetchOk = true;
            $locs = sm_extract_locs($r['body']);
            if (stripos($r['body'], '<sitemapindex') !== false) {
                foreach ($locs as $l) if (!isset($seenMaps[$l])) $queue[] = $l;
            } else {
                foreach ($locs as $l) $links[$l] = true;
            }
        }
    }
    if (!$fetchOk) return; // sitemap hi nahi mila, purani list rehne do

    $existing = [];
    foreach (sm_rows("SELECT url_hash, in_sitemap FROM site_monitor_links") as $row) $existing[$row['url_hash']] = (int)$row['in_sitemap'];
    $vals = [];
    $newCount = 0;
    $hashes = [];
    foreach (array_keys($links) as $l) {
        $h = md5($l);
        $hashes[$h] = true;
        if (!isset($existing[$h])) $newCount++;
        $vals[] = "('$h', '" . sm_e(mb_substr($l, 0, 1000)) . "', 1, NOW())";
        if (count($vals) >= 500) {
            sm_q("INSERT INTO site_monitor_links (url_hash, url, in_sitemap, first_seen) VALUES " . implode(',', $vals) . " ON DUPLICATE KEY UPDATE in_sitemap=1");
            $vals = [];
        }
    }
    if ($vals) sm_q("INSERT INTO site_monitor_links (url_hash, url, in_sitemap, first_seen) VALUES " . implode(',', $vals) . " ON DUPLICATE KEY UPDATE in_sitemap=1");
    $removed = 0;
    foreach ($existing as $h => $in) {
        if ($in && !isset($hashes[$h])) $removed++;
    }
    if ($removed) {
        // jo links ab sitemap mein nahi hain
        $keep = array_keys($hashes);
        sm_q("UPDATE site_monitor_links SET in_sitemap=0 WHERE in_sitemap=1 AND url_hash NOT IN ('" . implode("','", $keep) . "')");
    }
    if (!$baseline && ($newCount || $removed)) {
        sm_event($run_id, 'links', 'sitemap_changed', 'info', 'sitemap',
            "Sitemap mein $newCount naye links aaye, $removed links hat gaye. Kul links: " . count($links) . '.');
    }

    // Har run mein kuch links check karo (jo sabse pehle check hue the / kabhi nahi hue)
    $n = (int)$cfg['links_per_run'];
    $todo = sm_rows("SELECT url_hash, url, last_code, broken_since FROM site_monitor_links WHERE in_sitemap=1 ORDER BY (broken_since IS NULL), last_checked IS NOT NULL, last_checked LIMIT $n");
    $map = [];
    foreach ($todo as $t) $map[$t['url']] = $t;
    $res = sm_http_many(array_keys($map));
    $broken = 0;
    foreach ($map as $u => $t) {
        $r = $res[$u];
        $bad = $r['code'] === 0 || $r['code'] >= 400 || ($r['code'] === 200 && sm_page_problem($r) !== '');
        $wasBroken = (bool)$t['broken_since'];
        if ($bad) {
            $broken++;
            if (!$wasBroken) {
                $why = $r['code'] ? sm_page_problem($r) : 'Jawab nahi aaya';
                $pid = '';
                $slug = ltrim(parse_url($u, PHP_URL_PATH), '/');
                $p = sm_rows("SELECT id, name FROM site_monitor_profiles WHERE url='" . sm_e($slug) . "' LIMIT 1");
                if ($p) $pid = ' Profile: ' . $p[0]['name'] . ' (ID ' . $p[0]['id'] . ').';
                sm_event($run_id, 'links', 'link_broken', 'warning', $u, "Link toot gaya: $why.$pid");
            }
            $bs = $wasBroken ? "broken_since" : 'NOW()';
            sm_q("UPDATE site_monitor_links SET last_checked=NOW(), last_code=" . (int)$r['code'] . ", broken_since=$bs WHERE url_hash='" . $t['url_hash'] . "'");
        } else {
            if ($wasBroken) {
                sm_event($run_id, 'links', 'link_fixed', 'info', $u, 'Link ab theek khul raha hai (HTTP ' . $r['code'] . ').');
            }
            sm_q("UPDATE site_monitor_links SET last_checked=NOW(), last_code=" . (int)$r['code'] . ", last_ok=NOW(), broken_since=NULL WHERE url_hash='" . $t['url_hash'] . "'");
        }
    }
    $stats['links_total'] = count($links);
    $stats['links_checked'] = count($map);
    $tot = sm_rows("SELECT COUNT(*) c FROM site_monitor_links WHERE in_sitemap=1 AND broken_since IS NOT NULL");
    $stats['links_broken'] = (int)$tot[0]['c'];
}

// ---------- alert ----------

function sm_send_alert($run_id)
{
    $cfg = sm_config();
    $ev = $GLOBALS['sm_new_events'] ?? [];
    $crit = array_filter($ev, function ($e) { return $e['severity'] === 'critical'; });
    if (!$crit || !$cfg['alert_emails']) return false;
    $lines = [];
    foreach ($ev as $e) {
        if ($e['severity'] === 'info') continue;
        $lines[] = strtoupper($e['severity']) . ' | ' . $e['target'] . "\n    " . $e['details'];
        if (count($lines) >= 60) break;
    }
    $host = parse_url($cfg['base_url'], PHP_URL_HOST);
    $subject = 'ALERT ' . $host . ': ' . count($crit) . ' zaroori problem (' . date('d M h:i A') . ')';
    $body = "Site Monitor ne yeh problems pakdi:\n\n" . implode("\n\n", $lines)
        . "\n\nPoori list: " . rtrim($cfg['base_url'], '/') . '/fsiaadmm21/site-monitor/' . "\n";
    $headers = 'From: ' . $cfg['alert_from'] . "\r\nContent-Type: text/plain; charset=UTF-8";
    $ok = false;
    foreach ($cfg['alert_emails'] as $to) {
        $ok = @mail($to, $subject, $body, $headers) || $ok;
    }
    return $ok;
}

// ---------- main ----------

function sm_run($trigger = 'cron')
{
    @set_time_limit(0);
    @ignore_user_abort(true);
    sm_config();
    sm_install();
    $GLOBALS['sm_new_events'] = [];

    $lock = fopen(sm_data_dir() . '/run.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return ['ok' => false, 'msg' => 'Pichhla check abhi chal raha hai.'];
    }

    $baseline = !sm_rows("SELECT id FROM site_monitor_runs WHERE finished_at IS NOT NULL AND trigger_by <> 'admin' LIMIT 1");
    sm_q("INSERT INTO site_monitor_runs (started_at, trigger_by, is_baseline) VALUES (NOW(), '" . sm_e($trigger) . "', " . ($baseline ? 1 : 0) . ')');
    $run_id = mysqli_insert_id(sm_db());
    $stats = array_fill_keys(['files_total', 'files_added', 'files_changed', 'files_deleted', 'files_restored', 'profiles_total',
        'profile_changes', 'links_total', 'links_checked', 'links_broken', 'pages_down'], 0);
    $errors = [];
    foreach (['sm_check_files', 'sm_check_profiles', 'sm_check_pages', 'sm_check_links'] as $fn) {
        try {
            if ($fn === 'sm_check_pages') $fn($run_id, $stats);
            else $fn($run_id, $baseline, $stats);
        } catch (Throwable $e) {
            $errors[] = $fn . ': ' . $e->getMessage();
        }
    }
    if ($baseline) {
        sm_event($run_id, 'system', 'baseline', 'info', 'Pehla check',
            "Shuruaati list bana li: {$stats['files_total']} files, {$stats['profiles_total']} profile URLs, {$stats['links_total']} sitemap links. Ab se har badlaav yahan dikhega.");
    }
    foreach ($errors as $er) sm_event($run_id, 'system', 'monitor_error', 'warning', 'Monitor', $er);

    $alert = 0;
    try { $alert = sm_send_alert($run_id) ? 1 : 0; } catch (Throwable $e) {}

    $set = [];
    foreach ($stats as $k => $v) $set[] = "$k=" . (int)$v;
    sm_q("UPDATE site_monitor_runs SET finished_at=NOW(), alert_sent=$alert, " . implode(',', $set)
        . ", error=" . ($errors ? "'" . sm_e(implode("\n", $errors)) . "'" : 'NULL') . " WHERE id=$run_id");

    $keep = (int)sm_config()['keep_days'];
    sm_q("DELETE FROM site_monitor_events WHERE created_at < NOW() - INTERVAL $keep DAY");
    sm_q("DELETE FROM site_monitor_runs WHERE started_at < NOW() - INTERVAL $keep DAY");
    sm_q("DELETE FROM site_monitor_links WHERE in_sitemap=0 AND broken_since IS NULL AND first_seen < NOW() - INTERVAL $keep DAY");

    flock($lock, LOCK_UN);
    fclose($lock);
    return ['ok' => true, 'run_id' => $run_id, 'stats' => $stats, 'errors' => $errors, 'events' => count($GLOBALS['sm_new_events'])];
}

// Admin page se: delete hui file backup se wapas lagao
function sm_restore_file($rel, $usePrev = false)
{
    $cfg = sm_config();
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || strpos($rel, '..') !== false) return 'Galat path';
    $src = sm_backup_path($rel) . ($usePrev ? '.prev' : '');
    if (!is_file($src)) return 'Backup nahi mila';
    $dst = rtrim($cfg['site_root'], '/') . '/' . $rel;
    if (!is_dir(dirname($dst))) @mkdir(dirname($dst), 0755, true);
    if (!@copy($src, $dst)) return 'File likh nahi paye (permission?)';
    sm_install();
    sm_q("INSERT INTO site_monitor_runs (started_at, finished_at, trigger_by) VALUES (NOW(), NOW(), 'admin')");
    $rid = mysqli_insert_id(sm_db());
    sm_event($rid, 'files', 'file_restored', 'info', $rel, $usePrev ? 'Admin ne purana version wapas lagaya.' : 'Admin ne backup se file wapas lagayi.');
    return '';
}
