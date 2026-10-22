<?php
// "Kahan se badla": server logs dekh kar pata lagata hai ki file kis raste se badli/delete hui
// (FTP upload, website/CMS request, SSH command), kis IP se, aur woh IP kis shehar ka hai.

// Logs ki saari lines ek baar padho (sirf aakhri hissa, tez rehne ke liye)
function sm_log_entries($since)
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $cfg = sm_config();
    $cache = ['web' => [], 'ftp' => []];
    $seen = [];
    foreach ($cfg['log_dirs'] as $dir) {
        if (!@is_dir($dir)) continue;
        foreach (['proxy_access_ssl_log', 'proxy_access_log', 'access_ssl_log', 'access_log'] as $f) {
            $p = realpath($dir . '/' . $f);
            if (!$p || isset($seen[$p])) continue;
            $seen[$p] = true;
            foreach (sm_tail_lines($p, $cfg['log_read_bytes']) as $line) {
                // 1.2.3.4 - - [21/Oct/2026:19:23:11 +0530] "POST /x.php HTTP/1.1" 200 123 "ref" "UA"
                if (!preg_match('#^(\S+) \S+ (\S+) \[([^\]]+)\] "(\S+) (\S+)[^"]*" (\d{3}) \S+(?: "([^"]*)" "([^"]*)")?#', $line, $m)) continue;
                $t = strtotime(preg_replace('#^(\d+)/(\w+)/(\d+):(\d+:\d+:\d+)#', '$1 $2 $3 $4', $m[3]));
                if (preg_match('#([+-]\d{4})$#', $m[3], $om)) $GLOBALS['sm_log_offset'] = $om[1];
                if (!$t || $t < $since) continue;
                $ua = $m[8] ?? '';
                if (strpos($ua, 'FSIA-SiteMonitor') !== false) continue;
                $cache['web'][] = ['t' => $t, 'ip' => $m[1], 'user' => $m[2], 'method' => $m[4], 'url' => $m[5], 'code' => (int)$m[6], 'ua' => $ua];
            }
        }
        foreach (['xferlog_regular', 'xferlog'] as $f) {
            $p = realpath($dir . '/' . $f);
            if (!$p || isset($seen[$p])) continue;
            $seen[$p] = true;
            foreach (sm_tail_lines($p, $cfg['log_read_bytes']) as $line) {
                // Tue Oct 21 19:23:11 2026 0 1.2.3.4 1234 /var/www/.../about.php b _ i r user ftp 0 * c
                if (!preg_match('#^\w{3} (\w{3}\s+\d+ \d+:\d+:\d+ \d{4}) \d+ (\S+) \d+ (.+?) [ab] \S+ ([io]) \S (\S+) #', $line, $m)) continue;
                $t = strtotime($m[1] . (isset($GLOBALS['sm_log_offset']) ? ' ' . $GLOBALS['sm_log_offset'] : '')); // FTP log mein timezone nahi hota, server ka lo
                if (!$t || $t < $since) continue;
                $cache['ftp'][] = ['t' => $t, 'ip' => $m[2], 'file' => $m[3], 'dir' => $m[4], 'user' => $m[5]];
            }
        }
    }
    return $cache;
}

function sm_tail_lines($path, $bytes)
{
    $size = @filesize($path);
    if (!$size) return [];
    $fh = @fopen($path, 'r');
    if (!$fh) return [];
    if ($size > $bytes) {
        fseek($fh, -$bytes, SEEK_END);
        fgets($fh); // aadhi line chhodo
    }
    $data = stream_get_contents($fh);
    fclose($fh);
    return explode("\n", $data);
}

function sm_bash_history()
{
    static $lines = null;
    if ($lines !== null) return $lines;
    $lines = [];
    $cfg = sm_config();
    foreach ($cfg['shell_history'] as $h) {
        if (!@is_readable($h)) continue;
        $t = 0;
        foreach (sm_tail_lines($h, 300000) as $l) {
            if (preg_match('/^#(\d{10})$/', $l, $m)) { $t = (int)$m[1]; continue; } // HISTTIMEFORMAT wali history
            if (trim($l) !== '') $lines[] = ['t' => $t, 'cmd' => $l];
        }
    }
    return $lines;
}

// IP ka shehar/desh (ip-api.com, free). DB mein cache hota hai.
function sm_ip_place($ip)
{
    static $mem = [];
    static $lookups = 0;
    if (isset($mem[$ip])) return $mem[$ip];
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return $mem[$ip] = '';
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $mem[$ip] = 'server / local';
    $r = sm_rows("SELECT place FROM site_monitor_ips WHERE ip='" . sm_e($ip) . "'");
    if ($r) return $mem[$ip] = $r[0]['place'];
    $cfg = sm_config();
    if (!$cfg['ip_lookup'] || $lookups >= 30) return $mem[$ip] = '';
    $lookups++;
    $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,regionName,city,district,zip,lat,lon,isp,mobile,proxy,hosting');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3]);
    $j = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    $place = '';
    if (is_array($j) && ($j['status'] ?? '') === 'success') {
        // Area, shehar, state + pincode, desh · company (mobile/broadband) · map
        $area = implode(', ', array_filter([$j['district'] ?? '', $j['city'] ?? '',
            trim(($j['regionName'] ?? '') . ' ' . ($j['zip'] ?? '')), $j['country'] ?? '']));
        $tags = [];
        if (!empty($j['isp'])) $tags[] = $j['isp'] . (!empty($j['mobile']) ? ' (mobile data)' : ' (broadband/wifi)');
        if (!empty($j['proxy'])) $tags[] = 'VPN/proxy - asli jagah chhupi ho sakti hai';
        if (!empty($j['hosting'])) $tags[] = 'server/hosting IP (koi bot ya script)';
        $place = $area . ($tags ? ' · ' . implode(' · ', $tags) : '');
        if (isset($j['lat'], $j['lon'])) $place .= ' · map (lagbhag): https://maps.google.com/?q=' . $j['lat'] . ',' . $j['lon'];
    }
    sm_q("INSERT IGNORE INTO site_monitor_ips (ip, place, looked_up) VALUES ('" . sm_e($ip) . "', '" . sm_e($place) . "', NOW())");
    return $mem[$ip] = $place;
}

function sm_who($ip)
{
    $p = sm_ip_place($ip);
    return $ip . ($p ? " · $p" : '');
}

// Ek file ke liye sabse sambhav source dhoondho. $from..$to = kis samay ke beech badli.
function sm_find_source($path, $type, $from, $to)
{
    $cfg = sm_config();
    $base = basename($path);
    $root = rtrim($cfg['site_root'], '/');
    $logs = sm_log_entries(min($from, time() - 7200) - 600);
    $found = [];

    // 1. FTP / Plesk upload
    foreach ($logs['ftp'] as $e) {
        if ($e['t'] < $from - 300 || $e['t'] > $to + 300) continue;
        if (substr($e['file'], -strlen('/' . $path)) !== '/' . $path && basename($e['file']) !== $base) continue;
        $found[] = ['kind' => 'FTP', 'score' => 150, 't' => $e['t'], 'sure' => true,
            'text' => 'FTP ' . ($e['dir'] === 'i' ? 'upload' : 'download') . ' (user: ' . $e['user'] . ') · ' . sm_who($e['ip']) . ' · ' . date('h:i:s A', $e['t'])];
    }

    // 2. Website / CMS request. Sirf tab dikhao jab request sach mein file se judi lage:
    //    URL mein file ka naam ho, ya admin/CMS/API ki POST/DELETE request ho jo file ka kaam karti ho.
    $name = pathinfo($base, PATHINFO_FILENAME);
    foreach ($logs['web'] as $e) {
        if ($e['t'] < $from - 90 || $e['t'] > $to + 90) continue;
        $url = urldecode($e['url']);
        if (preg_match('#\.(css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|mp4)(\?|$)#i', $url)) continue;
        if ($e['code'] >= 400) continue;
        $write = in_array($e['method'], ['POST', 'PUT', 'DELETE', 'PATCH'], true);
        $named = strlen($name) >= 3 && stripos($url, $name) !== false && $write;
        $adminFileOp = $write && preg_match('#cms-admin|/api/|fsiaadmm21|/admin#i', $url)
            && preg_match('#delete|remove|unlink|cleanup|trash|upload|restore|file|deploy|sync|save|edit#i', $url);
        if (!$named && !$adminFileOp) continue;
        $sure = $named && $adminFileOp;
        $found[] = ['kind' => 'Website/CMS', 'score' => $sure ? 120 : ($named ? 90 : 60), 't' => $e['t'], 'sure' => $sure,
            'text' => 'Website/CMS request: ' . $e['method'] . ' ' . mb_substr($e['url'], 0, 120) . ' · ' . sm_who($e['ip']) . ' · ' . date('h:i:s A', $e['t'])];
    }

    // 3. SSH / Terminal command
    foreach (sm_bash_history() as $h) {
        if (stripos($h['cmd'], $base) === false) continue;
        if (!preg_match('#\b(rm|mv|cp|unlink|sed|nano|vi|vim|echo|cat|tee|zip|unzip|rsync|git|find)\b#', $h['cmd'])) continue;
        if ($h['t'] && ($h['t'] < $from - 300 || $h['t'] > $to + 300)) continue;
        $found[] = ['kind' => 'SSH', 'score' => $h['t'] ? 140 : 35, 't' => $h['t'], 'sure' => (bool)$h['t'],
            'text' => 'SSH Terminal command: ' . mb_substr($h['cmd'], 0, 140) . ($h['t'] ? ' · ' . date('h:i:s A', $h['t']) : ' (samay pata nahi)') . ' · Plesk SSH Terminal server ke andar chalta hai, isliye IP/jagah nahi milti'];
    }

    // File ka owner (PHP se ya FTP/SSH user se likhi gayi)
    $owner = '';
    $abs = $root . '/' . $path;
    if ($type !== 'file_deleted' && @is_file($abs) && function_exists('posix_getpwuid')) {
        $pw = @posix_getpwuid(@fileowner($abs));
        if ($pw) $owner = 'File owner: ' . $pw['name'];
    }

    usort($found, function ($a, $b) { return $b['score'] <=> $a['score']; });
    $uniq = [];
    foreach ($found as $f) {
        $uniq[$f['text']] = $f;
        if (count($uniq) >= 4) break;
    }
    $out = array_values($uniq);
    if (!$out) {
        $msg = 'Plesk File Manager / SSH Terminal / Plesk extract se hua lagta hai. Yeh Plesk panel ke andar hota hai, isliye website logs mein IP ya jagah nahi aati. (Plesk → Tools → Action Log mein user aur samay dikhega.)';
        return ['kind' => 'Plesk se (jagah pata nahi)', 'list' => [$msg], 'owner' => $owner];
    }
    $kind = $out[0]['kind'] . (empty($out[0]['sure']) ? ' (shayad)' : '');
    return ['kind' => $kind, 'list' => array_column($out, 'text'), 'owner' => $owner];
}

// Run ke aakhir mein: is run ki file entries ke liye source bhar do
function sm_fill_sources()
{
    $todo = $GLOBALS['sm_file_events'] ?? [];
    if (!$todo) return;
    $t0 = microtime(true);
    $limit = PHP_SAPI === 'cli' ? 120 : 15;
    foreach ($todo as $i => $ev) {
        if ($i >= 150 || microtime(true) - $t0 > $limit) break;
        try {
            $s = sm_find_source($ev['path'], $ev['type'], $ev['from'], $ev['to']);
        } catch (Throwable $e) {
            continue;
        }
        sm_q("UPDATE site_monitor_events SET source='" . sm_e(json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "' WHERE id=" . (int)$ev['id']);
    }
}
