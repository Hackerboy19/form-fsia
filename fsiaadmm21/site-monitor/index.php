<?php
// Site Monitor admin page: https://www.fsia.in/fsiaadmm21/site-monitor/
require __DIR__ . '/lib.php';
$cfg = sm_config();

session_name('fsia_site_monitor');
session_start();
if (empty($_SESSION['sm_csrf'])) $_SESSION['sm_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['sm_csrf'];
$self = strtok($_SERVER['REQUEST_URI'], '?');
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

$pwDefault = strpos($cfg['admin_password'], 'CHANGE') === 0;
$loginErr = '';
if (isset($_POST['sm_password'])) {
    if ($pwDefault) {
        $loginErr = 'Pehle site-monitor/config.php mein admin_password badlein.';
    } elseif (hash_equals($cfg['admin_password'], (string)$_POST['sm_password'])) {
        session_regenerate_id(true);
        $_SESSION['sm_ok'] = 1;
        header('Location: ' . $self);
        exit;
    } else {
        sleep(1);
        $loginErr = 'Password galat hai.';
    }
}
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $self);
    exit;
}

if (empty($_SESSION['sm_ok'])) {
    ?><!doctype html><html lang="hi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Site Monitor - Login</title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f4f6;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
form{background:#fff;padding:32px;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08);width:320px}
h1{font-size:20px;margin:0 0 6px}p{color:#6b7280;font-size:14px;margin:0 0 18px}
input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:15px}
button{margin-top:12px;width:100%;padding:11px;border:0;border-radius:8px;background:#7c1d6f;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
.err{color:#b91c1c;font-size:14px;margin-top:10px}</style></head><body>
<form method="post"><h1>Site Monitor</h1><p>FSIA admin: pages, files aur profile URLs ki nigrani</p>
<input type="password" name="sm_password" placeholder="Password" autofocus>
<button>Login</button><?php if ($loginErr) echo '<div class="err">' . $h($loginErr) . '</div>'; ?></form></body></html><?php
    exit;
}

// ---------- actions ----------
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $flash = 'err|Page purana ho gaya, dobara try karein.';
    } elseif (($_POST['action'] ?? '') === 'run') {
        try {
            $r = sm_run('admin-now');
            $flash = $r['ok'] ? 'ok|Check ho gaya. ' . $r['events'] . ' nayi entries.' : 'err|' . $r['msg'];
        } catch (Throwable $e) {
            $flash = 'err|' . $e->getMessage();
        }
    } elseif (($_POST['action'] ?? '') === 'restore') {
        $err = sm_restore_file((string)$_POST['path'], !empty($_POST['prev']));
        $flash = $err ? 'err|' . $err : 'ok|' . $_POST['path'] . ' wapas laga di gayi.';
    }
    $_SESSION['sm_flash'] = $flash;
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
if (!empty($_SESSION['sm_flash'])) {
    $flash = $_SESSION['sm_flash'];
    unset($_SESSION['sm_flash']);
}

sm_install();

// ---------- data ----------
$area = $_GET['area'] ?? 'all';
$days = max(1, min(90, (int)($_GET['days'] ?? 7)));
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 100;
$where = "created_at >= NOW() - INTERVAL $days DAY AND type <> 'baseline'";
if ($area === 'important') $where .= " AND severity IN ('critical','warning')";
elseif (in_array($area, ['files', 'profiles', 'links', 'pages'], true)) $where .= " AND area='" . sm_e($area) . "'";
$total = (int)sm_rows("SELECT COUNT(*) c FROM site_monitor_events WHERE $where")[0]['c'];
$events = sm_rows("SELECT * FROM site_monitor_events WHERE $where ORDER BY id DESC LIMIT " . (($page - 1) * $per) . ", $per");

$lastRun = sm_rows("SELECT * FROM site_monitor_runs WHERE finished_at IS NOT NULL AND trigger_by <> 'admin' ORDER BY id DESC LIMIT 1");
$lastRun = $lastRun[0] ?? null;
$stale = !$lastRun || strtotime($lastRun['finished_at']) < time() - 2 * 3600;
$pages = sm_rows("SELECT * FROM site_monitor_pages ORDER BY path");
$c24 = [];
foreach (sm_rows("SELECT type, COUNT(*) c FROM site_monitor_events WHERE created_at >= NOW() - INTERVAL 1 DAY GROUP BY type") as $r) $c24[$r['type']] = (int)$r['c'];
$crit24 = (int)sm_rows("SELECT COUNT(*) c FROM site_monitor_events WHERE severity='critical' AND created_at >= NOW() - INTERVAL 1 DAY")[0]['c'];
$brokenLinks = sm_rows("SELECT url, last_code, broken_since, last_checked FROM site_monitor_links WHERE in_sitemap=1 AND broken_since IS NOT NULL ORDER BY broken_since DESC LIMIT 200");
$linkStats = sm_rows("SELECT COUNT(*) total, SUM(last_checked IS NOT NULL) checked FROM site_monitor_links WHERE in_sitemap=1")[0];
$badSlugs = sm_rows("SELECT id, url, name FROM site_monitor_profiles WHERE url REGEXP '[() ]|^-|-$|--' ORDER BY id DESC LIMIT 100");
$dupes = sm_rows("SELECT url, GROUP_CONCAT(id ORDER BY id SEPARATOR ', ') ids, COUNT(*) c FROM site_monitor_profiles GROUP BY url HAVING c > 1 ORDER BY c DESC LIMIT 50");
$runs = sm_rows("SELECT * FROM site_monitor_runs WHERE trigger_by <> 'admin' ORDER BY id DESC LIMIT 24");
$root = rtrim($cfg['site_root'], '/');

$typeLabel = [
    'file_deleted' => ['File delete', 'red'], 'file_restored' => ['Wapas lagayi', 'green'], 'file_changed' => ['File badli', 'amber'],
    'file_added' => ['Nayi file', 'blue'], 'page_down' => ['Page down', 'red'], 'page_up' => ['Page theek', 'green'],
    'profile_url_changed' => ['URL badla', 'amber'], 'profile_url_removed' => ['URL khali', 'red'], 'profile_deleted' => ['Profile delete', 'red'],
    'profile_url_added' => ['Naya URL', 'blue'], 'profile_status_changed' => ['Status badla', 'gray'], 'profile_suspended' => ['Suspend', 'amber'],
    'link_broken' => ['Link toota', 'red'], 'link_fixed' => ['Link theek', 'green'], 'sitemap_changed' => ['Sitemap badla', 'gray'],
    'sitemap_error' => ['Sitemap error', 'red'], 'monitor_error' => ['Monitor error', 'red'], 'too_many' => ['Aur bhi', 'gray'],
];
$q = function ($over) { return '?' . http_build_query(array_merge($_GET, $over)); };
$ago = function ($t) {
    $d = time() - strtotime($t);
    if ($d < 90) return 'abhi';
    if ($d < 3600) return round($d / 60) . ' min pehle';
    if ($d < 86400) return round($d / 3600) . ' ghante pehle';
    return round($d / 86400) . ' din pehle';
};
?><!doctype html>
<html lang="hi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Site Monitor - FSIA Admin</title>
<style>
:root{--brand:#7c1d6f;--bg:#f3f4f6;--card:#fff;--line:#e5e7eb;--muted:#6b7280;--text:#111827}
*{box-sizing:border-box}body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--text);font-size:14px}
a{color:var(--brand)}
.top{background:var(--brand);color:#fff;padding:14px 24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.top h1{font-size:18px;margin:0;flex:1}.top a{color:#fde7f7;text-decoration:none;font-size:13px}
.wrap{max-width:1240px;margin:0 auto;padding:20px 16px 60px}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:500}.flash.ok{background:#dcfce7;color:#14532d}.flash.err{background:#fee2e2;color:#7f1d1d}
.alert{background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;border-radius:12px;padding:14px 16px;margin-bottom:16px}
.alert b{display:block;font-size:15px;margin-bottom:2px}
.status{display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 16px;margin-bottom:16px}
.dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px}.dot.g{background:#16a34a}.dot.r{background:#dc2626}
.status .grow{flex:1;min-width:220px}.muted{color:var(--muted)}
.btn{border:0;background:var(--brand);color:#fff;padding:9px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px}
.btn.sm{padding:5px 10px;font-size:12px}.btn.ghost{background:#fff;color:var(--brand);border:1px solid var(--brand)}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 16px}
.card .n{font-size:28px;font-weight:700;line-height:1.1}.card .l{color:var(--muted);font-size:12.5px;margin-top:4px}
.card.red .n{color:#dc2626}.card.amber .n{color:#d97706}.card.green .n{color:#16a34a}
.pages{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.chip{background:var(--card);border:1px solid var(--line);border-radius:999px;padding:6px 12px;font-size:13px;display:flex;align-items:center}
.chip.bad{border-color:#fca5a5;background:#fef2f2;color:#991b1b;font-weight:600}
.panel{background:var(--card);border:1px solid var(--line);border-radius:12px;margin-bottom:16px;overflow:hidden}
.panel h2{font-size:15px;margin:0;padding:14px 16px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.panel h2 .muted{font-weight:400;font-size:13px}
.tabs{display:flex;gap:6px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--line);align-items:center}
.tabs a{padding:6px 12px;border-radius:999px;text-decoration:none;color:var(--text);background:#f3f4f6;font-size:13px}
.tabs a.on{background:var(--brand);color:#fff}.tabs select{margin-left:auto;padding:6px 8px;border-radius:8px;border:1px solid var(--line)}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px 16px;border-bottom:1px solid var(--line);vertical-align:top}
th{font-size:12px;color:var(--muted);font-weight:600;background:#fafafa}
td.t{white-space:nowrap;color:var(--muted);font-size:12.5px}td.tg{word-break:break-all;min-width:230px;font-family:ui-monospace,Menlo,monospace;font-size:12.5px}
.badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}
.b-red{background:#fee2e2;color:#991b1b}.b-amber{background:#fef3c7;color:#92400e}.b-green{background:#dcfce7;color:#166534}.b-blue{background:#dbeafe;color:#1e40af}.b-gray{background:#f3f4f6;color:#374151}
.empty{padding:28px;text-align:center;color:var(--muted)}
.pager{padding:12px 16px;display:flex;gap:8px;align-items:center}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:900px){.grid2{grid-template-columns:1fr}}
.scroll{max-height:380px;overflow:auto}
@media(max-width:640px){th,td{padding:8px 10px}td.t{white-space:normal}}
</style></head><body>
<div class="top"><h1>Site Monitor <span style="font-weight:400;opacity:.8">· pages, files aur profile URLs</span></h1>
<a href="../">← Admin</a><a href="?logout=1">Logout</a></div>
<div class="wrap">

<?php if ($flash): [$ft, $fm] = explode('|', $flash, 2); ?>
<div class="flash <?= $h($ft) ?>"><?= $h($fm) ?></div>
<?php endif; ?>

<?php if ($crit24): ?>
<div class="alert"><b>⚠ Pichhle 24 ghante mein <?= $crit24 ?> zaroori problem</b>Neeche "Zaroori" tab mein dekhein (page down ya key file delete).</div>
<?php endif; ?>

<div class="status">
  <div class="grow">
    <?php if ($stale): ?>
      <span class="dot r"></span><b>Har ghante wala check nahi chal raha</b>
      <div class="muted">Aakhri check: <?= $lastRun ? $h(sm_fmt_time($lastRun['finished_at'])) : 'kabhi nahi' ?>. Plesk ka Scheduled Task dekhein.</div>
    <?php else: ?>
      <span class="dot g"></span><b>Monitor chal raha hai</b>
      <div class="muted">Aakhri check: <?= $h(sm_fmt_time($lastRun['finished_at'])) ?> (<?= $h($ago($lastRun['finished_at'])) ?>) · agla check ~<?= $h(date('h:i A', strtotime($lastRun['started_at']) + 3600)) ?></div>
    <?php endif; ?>
  </div>
  <form method="post"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="run">
  <button class="btn" onclick="this.textContent='Check ho raha hai...';">Abhi check karo</button></form>
</div>

<div class="pages">
<?php foreach ($pages as $p): $bad = (bool)$p['down_since']; ?>
  <span class="chip <?= $bad ? 'bad' : '' ?>"><span class="dot <?= $bad ? 'r' : 'g' ?>"></span><?= $h($p['path']) ?>
  <span class="muted" style="margin-left:6px;<?= $bad ? 'color:#991b1b' : '' ?>"><?= $p['last_code'] ? (int)$p['last_code'] : 'no reply' ?><?= $bad ? '' : ' · ' . round($p['last_ms'] / 1000, 1) . 's' ?></span></span>
<?php endforeach; if (!$pages) echo '<span class="muted">Pages abhi check nahi hue.</span>'; ?>
</div>

<div class="cards">
  <div class="card <?= !empty($c24['file_deleted']) ? 'red' : '' ?>"><div class="n"><?= (int)($c24['file_deleted'] ?? 0) ?></div><div class="l">Files delete (24 ghante)</div></div>
  <div class="card <?= !empty($c24['file_changed']) ? 'amber' : '' ?>"><div class="n"><?= (int)($c24['file_changed'] ?? 0) ?></div><div class="l">Files badli (24 ghante)</div></div>
  <div class="card <?= !empty($c24['file_added']) ? '' : '' ?>"><div class="n"><?= (int)($c24['file_added'] ?? 0) ?></div><div class="l">Nayi files (24 ghante)</div></div>
  <div class="card <?= (($c24['profile_url_changed'] ?? 0) + ($c24['profile_url_removed'] ?? 0) + ($c24['profile_deleted'] ?? 0)) ? 'amber' : '' ?>"><div class="n"><?= (int)(($c24['profile_url_changed'] ?? 0) + ($c24['profile_url_removed'] ?? 0) + ($c24['profile_deleted'] ?? 0)) ?></div><div class="l">Profile URL badle / hate (24 ghante)</div></div>
  <div class="card <?= $brokenLinks ? 'red' : 'green' ?>"><div class="n"><?= count($brokenLinks) ?></div><div class="l">Abhi toote links (<?= (int)$linkStats['checked'] ?>/<?= (int)$linkStats['total'] ?> check hue)</div></div>
</div>

<div class="panel">
  <h2>Kya badla <span class="muted">· <?= number_format($total) ?> entries</span></h2>
  <div class="tabs">
    <?php foreach (['all' => 'Sab', 'important' => 'Zaroori', 'files' => 'Files', 'profiles' => 'Profile URLs', 'links' => 'Toote links', 'pages' => 'Pages'] as $k => $l): ?>
      <a class="<?= $area === $k ? 'on' : '' ?>" href="<?= $h($q(['area' => $k, 'p' => 1])) ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <select onchange="location.href=this.value">
      <?php foreach ([1 => 'Aaj (24 ghante)', 7 => '7 din', 30 => '30 din', 90 => '90 din'] as $d => $l): ?>
        <option value="<?= $h($q(['days' => $d, 'p' => 1])) ?>" <?= $days === $d ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if (!$events): ?>
    <div class="empty">Is samay mein koi badlaav nahi mila. 👍</div>
  <?php else: ?>
  <div style="overflow-x:auto"><table>
    <tr><th style="width:150px">Kab pakda</th><th style="width:120px">Kya hua</th><th>File / URL</th><th>Detail</th><th></th></tr>
    <?php foreach ($events as $e):
        [$lab, $col] = $typeLabel[$e['type']] ?? [$e['type'], 'gray'];
        $act = '';
        if ($e['area'] === 'files' && $e['type'] === 'file_deleted' && $e['target'][0] !== '(' && !is_file($root . '/' . $e['target']) && is_file(sm_backup_path($e['target']))) {
            $act = 'restore';
        } elseif ($e['area'] === 'files' && $e['type'] === 'file_changed' && $e['target'][0] !== '(' && is_file(sm_backup_path($e['target']) . '.prev')) {
            $act = 'prev';
        }
        $isUrl = preg_match('#^https?://#', $e['target']);
    ?>
    <tr>
      <td class="t"><?= $h(sm_fmt_time($e['created_at'])) ?></td>
      <td><span class="badge b-<?= $col ?>"><?= $h($lab) ?></span><?php if ($e['severity'] === 'critical') echo ' <span title="Zaroori">⚠</span>'; ?></td>
      <td class="tg"><?= $isUrl ? '<a href="' . $h($e['target']) . '" target="_blank" rel="noopener">' . $h(preg_replace('#^https?://[^/]+#', '', $e['target'])) . '</a>' : $h($e['target']) ?></td>
      <td><?= $h($e['details']) ?></td>
      <td style="white-space:nowrap"><?php if ($act): ?>
        <form method="post" onsubmit="return confirm('<?= $act === 'prev' ? 'Is file ka pichhla version wapas lagayein?' : 'Yeh file backup se wapas lagayein?' ?>')">
          <input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="path" value="<?= $h($e['target']) ?>">
          <?php if ($act === 'prev'): ?><input type="hidden" name="prev" value="1"><button class="btn sm ghost">Purana version</button>
          <?php else: ?><button class="btn sm">Wapas lagao</button><?php endif; ?>
        </form><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php if ($total > $per): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a href="<?= $h($q(['p' => $page - 1])) ?>">← Pichhle</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> / <?= ceil($total / $per) ?></span>
    <?php if ($page * $per < $total): ?><a href="<?= $h($q(['p' => $page + 1])) ?>">Agle →</a><?php endif; ?>
  </div>
  <?php endif; endif; ?>
</div>

<div class="grid2">
  <div class="panel">
    <h2>Abhi toote hue links <span class="muted">· sitemap se, har ghante <?= (int)$cfg['links_per_run'] ?> check</span></h2>
    <div class="scroll">
    <?php if (!$brokenLinks): ?><div class="empty">Koi toota link nahi mila.</div><?php else: ?>
    <table><tr><th>URL</th><th>Code</th><th>Kab se</th></tr>
    <?php foreach ($brokenLinks as $l): ?>
      <tr><td class="tg"><a href="<?= $h($l['url']) ?>" target="_blank" rel="noopener"><?= $h(preg_replace('#^https?://[^/]+#', '', $l['url'])) ?></a></td>
      <td><span class="badge b-red"><?= $l['last_code'] ? (int)$l['last_code'] : '—' ?></span></td>
      <td class="t"><?= $h(sm_fmt_time($l['broken_since'])) ?></td></tr>
    <?php endforeach; ?></table><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <h2>Shak wale profile URLs <span class="muted">· khali naam, brackets, space, duplicate</span></h2>
    <div class="scroll">
    <?php if (!$badSlugs && !$dupes): ?><div class="empty">Sab URL theek dikh rahe hain.</div><?php else: ?>
    <table><tr><th>URL</th><th>Problem</th></tr>
    <?php foreach ($dupes as $d): ?>
      <tr><td class="tg"><?= $h($d['url']) ?></td><td><span class="badge b-amber">Duplicate</span> <span class="muted">IDs: <?= $h($d['ids']) ?></span></td></tr>
    <?php endforeach; foreach ($badSlugs as $b):
        $why = [];
        if (preg_match('/^-/', $b['url'])) $why[] = 'shuru mein "-" (naam/category khali)';
        if (preg_match('/-$/', $b['url'])) $why[] = 'aakhir mein "-" (id khali)';
        if (preg_match('/[()]/', $b['url'])) $why[] = 'brackets ( )';
        if (strpos($b['url'], ' ') !== false) $why[] = 'space';
        if (strpos($b['url'], '--') !== false) $why[] = 'double "--"';
    ?>
      <tr><td class="tg"><?= $h($b['url']) ?><div class="muted" style="font-family:inherit"><?= $h($b['name']) ?> (ID <?= (int)$b['id'] ?>)</div></td><td><?= $h(implode(', ', $why)) ?></td></tr>
    <?php endforeach; ?></table><?php endif; ?>
    </div>
  </div>
</div>

<div class="panel">
  <h2>Pichhle checks <span class="muted">· aakhri 24</span></h2>
  <div style="overflow-x:auto"><table>
    <tr><th>Kab</th><th>Files</th><th>Profiles</th><th>Links</th><th>Pages down</th><th>Alert</th></tr>
    <?php foreach ($runs as $r): ?>
    <tr>
      <td class="t"><?= $h(sm_fmt_time($r['started_at'])) ?><?= $r['trigger_by'] === 'admin-now' ? ' <span class="badge b-gray">haath se</span>' : '' ?><?= $r['is_baseline'] ? ' <span class="badge b-blue">pehla</span>' : '' ?></td>
      <td><?= number_format($r['files_total']) ?> <span class="muted">(+<?= (int)$r['files_added'] ?> / ~<?= (int)$r['files_changed'] ?> / −<?= (int)$r['files_deleted'] ?>)</span></td>
      <td><?= number_format($r['profiles_total']) ?> <span class="muted">(<?= (int)$r['profile_changes'] ?> badlaav)</span></td>
      <td><?= (int)$r['links_checked'] ?> check <span class="muted">(<?= (int)$r['links_broken'] ?> toote)</span></td>
      <td><?= $r['pages_down'] ? '<span class="badge b-red">' . (int)$r['pages_down'] . '</span>' : '0' ?></td>
      <td><?= $r['finished_at'] ? ($r['alert_sent'] ? '📧' : '—') : '<span class="muted">chal raha</span>' ?><?= $r['error'] ? ' <span class="badge b-red" title="' . $h($r['error']) . '">error</span>' : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>

</div></body></html>
