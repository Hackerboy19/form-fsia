<?php
// Login monitor: kaun, kab, kahan se login hua (admin panel, cms-admin, Site Monitor, SSH, FTP)

function sm_client_ip()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    // Plesk mein nginx aage hota hai; agar REMOTE_ADDR server ka hai to asli IP header se lo
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        || $ip === ($_SERVER['SERVER_ADDR'] ?? '')) {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $c = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($c, FILTER_VALIDATE_IP)) return $c;
            }
        }
    }
    return $ip;
}

function sm_login_install()
{
    static $done = false;
    if ($done) return;
    $done = true;
    sm_q("CREATE TABLE IF NOT EXISTS site_monitor_logins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        at DATETIME NOT NULL,
        kind VARCHAR(40) NOT NULL,
        user VARCHAR(100) NOT NULL DEFAULT '',
        ip VARCHAR(64) NOT NULL,
        place VARCHAR(400) NOT NULL DEFAULT '',
        ua VARCHAR(300) NOT NULL DEFAULT '',
        page VARCHAR(300) NOT NULL DEFAULT '',
        token VARCHAR(40) NOT NULL DEFAULT '',
        new_ip TINYINT NOT NULL DEFAULT 0,
        UNIQUE KEY k_dedupe (kind, at, ip), KEY k_at (at), KEY k_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Ek login record karo. Naya IP ho to alert.
function sm_log_login($kind, $ip, $user = '', $ua = '', $page = '', $token = '', $at = null)
{
    sm_login_install();
    $at = $at ?: time();
    $seen = sm_rows("SELECT id FROM site_monitor_logins WHERE ip='" . sm_e($ip) . "' LIMIT 1");
    $place = sm_ip_place($ip);
    sm_q("INSERT IGNORE INTO site_monitor_logins (at, kind, user, ip, place, ua, page, token, new_ip) VALUES ('"
        . date('Y-m-d H:i:s', $at) . "', '" . sm_e($kind) . "', '" . sm_e(mb_substr($user, 0, 100)) . "', '" . sm_e($ip) . "', '"
        . sm_e(mb_substr($place, 0, 400)) . "', '" . sm_e(mb_substr($ua, 0, 300)) . "', '" . sm_e(mb_substr($page, 0, 300)) . "', '"
        . sm_e($token) . "', " . ($seen ? 0 : 1) . ")");
    $id = mysqli_insert_id(sm_db());
    if ($id && !$seen) {
        $msg = "$kind login NAYE IP se: $ip" . ($place ? " · $place" : '') . ($user ? " · user: $user" : '') . ' · ' . sm_fmt_time($at);
        sm_q("INSERT INTO site_monitor_events (run_id, created_at, area, type, severity, target, details) VALUES (0, NOW(), 'logins', 'new_login_ip', 'warning', '"
            . sm_e($ip) . "', '" . sm_e($msg) . "')");
        sm_mail('Naye IP se login: ' . $kind . ' (' . $ip . ')', $msg . "\n\nAgar yeh aap ya aapki team nahi hai, to turant password badlein.\n");
    }
    return $id;
}

function sm_mail($subject, $body)
{
    $cfg = sm_config();
    if (empty($cfg['alert_emails'])) return false;
    $headers = 'From: ' . $cfg['alert_from'] . "\r\nContent-Type: text/plain; charset=UTF-8";
    $ok = false;
    foreach ($cfg['alert_emails'] as $to) $ok = @mail($to, '[FSIA] ' . $subject, $body, $headers) || $ok;
    return $ok;
}

// ---- SSH logins (/var/log/wtmp) ----
function sm_collect_ssh_logins($since)
{
    $f = '/var/log/wtmp';
    if (!@is_readable($f)) return 0;
    $me = function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '') : get_current_user();
    $size = filesize($f);
    $rec = 384;
    $start = max(0, $size - $rec * 20000);
    $start -= $start % $rec;
    $fh = @fopen($f, 'rb');
    if (!$fh) return 0;
    fseek($fh, $start);
    $n = 0;
    while (($b = fread($fh, $rec)) !== false && strlen($b) === $rec) {
        $u = unpack('stype/x2/ipid/a32line/a4id/a32user/a256host/x4/isession/Isec', $b);
        if ($u['type'] !== 7) continue; // USER_PROCESS
        $user = rtrim($u['user'], "\0");
        if ($me !== '' && $user !== $me) continue;
        if ($u['sec'] < $since) continue;
        $host = rtrim($u['host'], "\0");
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : ($host === '' ? '127.0.0.1' : (gethostbyname($host) ?: $host));
        $kind = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ? 'SSH / SFTP' : 'Plesk SSH Terminal';
        if (sm_log_login($kind, $ip, $user, '', rtrim($u['line'], "\0"), '', $u['sec'])) $n++;
    }
    fclose($fh);
    return $n;
}

// ---- FTP (FileZilla etc.) — xferlog se, har IP ka pehla transfer = ek login ----
function sm_collect_ftp_logins($since)
{
    $logs = sm_log_entries($since);
    $n = 0;
    $seen = [];
    foreach ($logs['ftp'] as $e) {
        if ($e['t'] < $since) continue;
        $k = $e['ip'] . '|' . date('YmdH', $e['t']);
        if (isset($seen[$k])) continue;
        $seen[$k] = true;
        if (sm_log_login('FTP (FileZilla etc.)', $e['ip'], $e['user'], '', basename($e['file']), '', $e['t'])) $n++;
    }
    return $n;
}

function sm_collect_logins($run_id)
{
    sm_login_install();
    $last = sm_rows("SELECT MAX(at) t FROM site_monitor_logins WHERE kind IN ('SSH / SFTP','Plesk SSH Terminal','FTP (FileZilla etc.)')");
    $since = $last[0]['t'] ? strtotime($last[0]['t']) - 60 : time() - 7 * 86400;
    $n = 0;
    try { $n += sm_collect_ssh_logins($since); } catch (Throwable $e) {}
    try { $n += sm_collect_ftp_logins($since); } catch (Throwable $e) {}
    return $n;
}

function sm_short_ua($ua)
{
    if ($ua === '') return '—';
    $os = preg_match('/iPhone|iPad/', $ua) ? 'iPhone/iPad' : (preg_match('/Android/', $ua) ? 'Android' : (preg_match('/Mac OS X/', $ua) ? 'Mac' : (preg_match('/Windows/', $ua) ? 'Windows' : (preg_match('/Linux/', $ua) ? 'Linux' : ''))));
    $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/OPR\//', $ua) ? 'Opera' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'Browser'))));
    return trim("$br" . ($os ? " · $os" : ''));
}
