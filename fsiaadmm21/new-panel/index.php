<?php
/*
 * FSIA New Admin Panel (dashboard) — alag folder, read-only.
 * Purane admin (fsiaadmm21) ki koi file ya table yeh nahi badalta:
 * sirf SELECT queries chalti hain.
 */
declare(strict_types=1);

$cfg = require __DIR__ . '/config.php';

session_name('FSIANEWPANEL');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => dirname($_SERVER['SCRIPT_NAME'] ?? '/fsiaadmm21/new-panel/index.php') . '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Robots-Tag: noindex, nofollow');

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(array $cfg): mysqli
{
    static $db = null;
    if ($db instanceof mysqli) return $db;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connect = null;
    // Site config.php $connect banata hai. Uska output (agar koi ho) chhupa do.
    ob_start();
    require $cfg['db_config'];
    ob_end_clean();
    if (!($connect instanceof mysqli)) {
        http_response_code(500);
        exit('Database config nahi mila: ' . h($cfg['db_config']));
    }
    return $db = $connect;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$self = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?');

// ---------- Logout ----------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $self);
    exit;
}

// ---------- Login ----------
$loginError = '';
if (($_POST['action'] ?? '') === 'login') {
    $user = trim((string) ($_POST['uname'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $loginError = 'Page purana ho gaya, dobara try karein.';
    } elseif ($user === '' || $pass === '') {
        $loginError = 'Username aur password dono bharein.';
    } else {
        $st = db($cfg)->prepare('SELECT id, uname, name, password, atype FROM adminweb WHERE uname = ? LIMIT 1');
        $st->bind_param('s', $user);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $ok = $row && (hash_equals((string) $row['password'], $pass) || hash_equals((string) $row['password'], md5($pass)));
        if ($ok && !in_array((int) $row['atype'], array_map('intval', $cfg['allowed_atypes']), true)) {
            $loginError = 'Yeh panel sirf Super Admin ke liye hai.';
        } elseif ($ok) {
            session_regenerate_id(true);
            $_SESSION['np_user'] = ['id' => (int) $row['id'], 'uname' => $row['uname'], 'name' => $row['name'] ?: $row['uname']];
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            header('Location: ' . $self);
            exit;
        } else {
            usleep(800000);
            $loginError = 'Username ya password galat hai.';
        }
    }
}

$me = $_SESSION['np_user'] ?? null;

// ---------- Data ----------
$events = $cfg['events'];
$season = preg_match('/^\d{4}-\d{2}-\d{2}$/', $cfg['season_start']) ? $cfg['season_start'] : date('Y') . '-01-01';
$types  = implode(',', array_map(fn($e) => (int) $e['regtype'], $events));
$byType = [];
foreach ($events as $e) $byType[(int) $e['regtype']] = $e;

$stats = [];
$trend = [];
$recentRegs = [];
$recentPays = [];
$today = date('Y-m-d');

if ($me) {
    $db = db($cfg);
    $s  = $db->real_escape_string($season);

    $q = $db->query("
        SELECT regtype,
          SUM(date_of_reg = CURDATE())                       AS reg_today,
          SUM(status = 0 AND date_of_reg >= '$s')            AS unpaid_total,
          SUM(status = 0 AND date_of_reg = CURDATE())        AS unpaid_today,
          SUM(status = 1 AND pdate >= '$s')                  AS s1_total,
          SUM(status = 1 AND DATE(pdate) = CURDATE())        AS s1_today,
          SUM(status = 2 AND pdate >= '$s')                  AS s2_total,
          SUM(status = 2 AND DATE(pdate) = CURDATE())        AS s2_today,
          SUM(status = 3 AND pdate >= '$s')                  AS s3_total,
          SUM(status = 3 AND DATE(pdate) = CURDATE())        AS s3_today
        FROM registration
        WHERE regtype IN ($types)
        GROUP BY regtype");
    while ($r = $q->fetch_assoc()) $stats[(int) $r['regtype']] = array_map('intval', $r);

    // Pichhle 14 din: naye registration aur payments
    for ($i = 13; $i >= 0; $i--) $trend[date('Y-m-d', strtotime("-$i day"))] = ['reg' => 0, 'pay' => 0];
    $from = array_key_first($trend);
    $q = $db->query("SELECT date_of_reg d, COUNT(*) c FROM registration
                     WHERE regtype IN ($types) AND date_of_reg >= '$from' GROUP BY date_of_reg");
    while ($r = $q->fetch_assoc()) if (isset($trend[$r['d']])) $trend[$r['d']]['reg'] = (int) $r['c'];
    $q = $db->query("SELECT DATE(pdate) d, COUNT(*) c FROM registration
                     WHERE regtype IN ($types) AND status IN (1,2,3) AND pdate >= '$from' GROUP BY DATE(pdate)");
    while ($r = $q->fetch_assoc()) if (isset($trend[$r['d']])) $trend[$r['d']]['pay'] = (int) $r['c'];

    $q = $db->query("SELECT id, first_name, last_name, regtype, status, date_of_reg
                     FROM registration WHERE regtype IN ($types)
                     ORDER BY id DESC LIMIT 8");
    $recentRegs = $q->fetch_all(MYSQLI_ASSOC);
    $q = $db->query("SELECT id, first_name, last_name, regtype, status, pdate
                     FROM registration WHERE regtype IN ($types) AND status IN (1,2,3) AND pdate IS NOT NULL
                     ORDER BY pdate DESC, id DESC LIMIT 8");
    $recentPays = $q->fetch_all(MYSQLI_ASSOC);
}

function statusLabel(int $status, string $kind): array
{
    if ($status === 0) return ['Unpaid', 'st-unpaid'];
    if ($kind === 'award') {
        if ($status === 1) return ['Paid', 'st-paid'];
        if ($status === 3) return ['Title', 'st-title'];
    } else {
        if ($status === 2) return ['Semi Paid', 'st-semi'];
        if ($status === 1) return ['Full Paid', 'st-paid'];
    }
    return ['Status ' . $status, 'st-other'];
}

// Har event ke 3 khane: award = Unpaid/Paid/Title, pageant = Unpaid/Semi/Full
function eventMetrics(array $e, array $st): array
{
    $g = fn($k) => $st[$k] ?? 0;
    $m = [['Unpaid', $g('unpaid_total'), $g('unpaid_today'), 'unpaid']];
    if ($e['kind'] === 'award') {
        $m[] = ['Paid',  $g('s1_total'), $g('s1_today'), 'paid'];
        $m[] = ['Title', $g('s3_total'), $g('s3_today'), 'title'];
    } else {
        $m[] = ['Semi Paid', $g('s2_total'), $g('s2_today'), 'semi'];
        $m[] = ['Full Paid', $g('s1_total'), $g('s1_today'), 'paid'];
    }
    return $m;
}

$tot = ['reg_today' => 0, 'pay_today' => 0, 'unpaid' => 0, 'paid' => 0];
foreach ($events as $e) {
    $st = $stats[(int) $e['regtype']] ?? [];
    $tot['reg_today'] += $st['reg_today'] ?? 0;
    $tot['pay_today'] += ($st['s1_today'] ?? 0) + ($st['s2_today'] ?? 0) + ($st['s3_today'] ?? 0);
    $tot['unpaid']    += $st['unpaid_total'] ?? 0;
    $tot['paid']      += ($st['s1_total'] ?? 0) + ($st['s2_total'] ?? 0) + ($st['s3_total'] ?? 0);
}
$fmt = fn($n) => number_format((int) $n, 0, '.', ',');
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>FSIA Admin · Dashboard</title>
<link rel="icon" href="/favicon-32x32.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f5f3f7; --panel:#ffffff; --ink:#1d1726; --muted:#6b6478; --line:#e9e5ef;
  --side:#1a1124; --side-ink:#cfc6dc; --side-active:#2b1d3b; --gold:#c9a24a; --gold-2:#e6c877;
  --unpaid:#64748b; --semi:#d97706; --paid:#16a34a; --title:#7c3aed; --shadow:0 1px 2px rgba(20,10,30,.05),0 6px 20px rgba(20,10,30,.06);
  color-scheme:light;
}
:root[data-theme="dark"]{
  --bg:#0f0b14; --panel:#191320; --ink:#f1ecf7; --muted:#a59cb3; --line:#2a2233;
  --side:#0b0710; --side-ink:#bdb3cc; --side-active:#21182b; --shadow:0 1px 2px rgba(0,0,0,.4);
  --unpaid:#94a3b8; --semi:#f59e0b; --paid:#22c55e; --title:#a78bfa; color-scheme:dark;
}
@media (prefers-color-scheme:dark){
  :root:not([data-theme="light"]){
    --bg:#0f0b14; --panel:#191320; --ink:#f1ecf7; --muted:#a59cb3; --line:#2a2233;
    --side:#0b0710; --side-ink:#bdb3cc; --side-active:#21182b; --shadow:0 1px 2px rgba(0,0,0,.4);
    --unpaid:#94a3b8; --semi:#f59e0b; --paid:#22c55e; --title:#a78bfa; color-scheme:dark;
  }
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.5 Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit}
.num{font-variant-numeric:tabular-nums}

/* ---------- Login ---------- */
.login-wrap{min-height:100vh;display:grid;place-items:center;padding:24px 16px;
  background:radial-gradient(1200px 600px at 10% -10%,#3a1f52 0,transparent 60%),radial-gradient(900px 500px at 110% 110%,#4a3410 0,transparent 55%),#140c1c}
.login{width:100%;max-width:380px;background:#1d1427;border:1px solid #33264a;border-radius:18px;padding:32px 28px;color:#f3eef9;box-shadow:0 30px 60px rgba(0,0,0,.4)}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}
.logo{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font:700 16px Playfair Display,serif;color:#1a1124;background:linear-gradient(135deg,var(--gold-2),var(--gold))}
.brand b{display:block;font:700 18px Playfair Display,serif;letter-spacing:.2px}
.brand span{font-size:12px;color:#a99cbb}
.login label{display:block;font-size:12px;color:#b9aecb;margin:14px 0 6px}
.login input{width:100%;padding:11px 12px;border-radius:10px;border:1px solid #3a2d50;background:#140d1c;color:#fff;font:inherit;outline:none}
.login input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,162,74,.2)}
.btn-gold{margin-top:20px;width:100%;padding:12px;border:0;border-radius:10px;font:600 14px Inter,sans-serif;color:#1a1124;background:linear-gradient(135deg,var(--gold-2),var(--gold));cursor:pointer}
.err{margin-top:14px;padding:10px 12px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#fca5a5;font-size:13px}
.hint{margin-top:16px;font-size:12px;color:#8f83a3;text-align:center}

/* ---------- Layout ---------- */
.app{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
.side{background:var(--side);color:var(--side-ink);padding:22px 14px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:6px}
.side .brand{padding:0 8px;margin-bottom:18px}
.side .brand b{color:#fff}
.nav-h{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#7d708f;padding:14px 10px 6px}
.nav a{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;text-decoration:none;color:var(--side-ink);font-weight:500}
.nav a:hover{background:var(--side-active);color:#fff}
.nav a.active{background:var(--side-active);color:#fff;box-shadow:inset 3px 0 0 var(--gold)}
.nav svg{width:18px;height:18px;flex:none;opacity:.85}
.side .foot{margin-top:auto;padding:12px 10px;border-top:1px solid #2a1f37;font-size:12px}
.side .foot b{display:block;color:#fff;font-weight:600}
.side .foot a{color:var(--gold-2);text-decoration:none}
.main{padding:24px 28px 40px;min-width:0}
.top{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px}
.top h1{margin:0;font:700 26px Playfair Display,serif;letter-spacing:.2px}
.top p{margin:4px 0 0;color:var(--muted)}
.tools{display:flex;gap:8px}
.chip-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font:500 13px Inter,sans-serif;cursor:pointer;text-decoration:none}
.chip-btn:hover{border-color:var(--gold)}
.chip-btn svg{width:16px;height:16px}

/* ---------- KPI ---------- */
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}
.kpi{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 18px;box-shadow:var(--shadow)}
.kpi .l{font-size:12px;color:var(--muted);font-weight:500}
.kpi .v{font:700 28px Inter,sans-serif;margin-top:6px;letter-spacing:-.5px}
.kpi .s{font-size:12px;color:var(--muted);margin-top:2px}
.kpi.gold{background:linear-gradient(135deg,#2a1838,#1a1124);color:#fff;border-color:#3a2750}
.kpi.gold .l,.kpi.gold .s{color:#cdbfe0}
.kpi.gold .v{color:var(--gold-2)}

/* ---------- Event cards ---------- */
.sec-h{display:flex;align-items:baseline;justify-content:space-between;margin:6px 0 12px}
.sec-h h2{margin:0;font-size:15px;font-weight:600}
.sec-h span{font-size:12px;color:var(--muted)}
.events{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:14px;margin-bottom:24px}
.ev{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 18px 14px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.ev::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--c)}
.ev-h{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
.ev-h b{font-size:15px;font-weight:600}
.tag{font-size:11px;font-weight:600;padding:3px 8px;border-radius:999px;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.mets{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.met{padding:10px 10px 8px;border-radius:10px;background:color-mix(in srgb,var(--k) 7%,var(--panel));border:1px solid color-mix(in srgb,var(--k) 18%,transparent)}
.met .l{font-size:11.5px;color:var(--muted);display:flex;align-items:center;gap:6px}
.met .l i{width:7px;height:7px;border-radius:50%;background:var(--k)}
.met .v{font:700 22px Inter,sans-serif;margin-top:2px;letter-spacing:-.3px}
.met .t{font-size:11.5px;color:var(--muted)}
.met .t.up{color:var(--paid);font-weight:600}
.k-unpaid{--k:var(--unpaid)} .k-semi{--k:var(--semi)} .k-paid{--k:var(--paid)} .k-title{--k:var(--title)}
.bar{display:flex;height:6px;border-radius:99px;overflow:hidden;background:var(--line);margin-top:12px}
.bar span{display:block;height:100%}
.bar-l{display:flex;justify-content:space-between;font-size:11.5px;color:var(--muted);margin-top:6px}

/* ---------- Trend + tables ---------- */
.grid2{display:grid;align-items:start;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:14px;margin-bottom:14px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 18px;box-shadow:var(--shadow);min-width:0}
.card h3{margin:0 0 2px;font-size:15px;font-weight:600}
.card .sub{font-size:12px;color:var(--muted);margin-bottom:12px}
.legend{display:flex;gap:14px;font-size:12px;color:var(--muted);margin-bottom:8px}
.legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.chart{width:100%;height:auto;display:block}
.chart text{fill:var(--muted);font:10px Inter,sans-serif}
.tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.tbl td.r{width:96px}
.tbl td{padding:9px 4px;border-top:1px solid var(--line);vertical-align:middle}
.tbl tr:first-child td{border-top:0}
.who b{display:block;font-weight:600;font-size:13.5px}
.who span{font-size:12px;color:var(--muted)}
.ava{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-weight:600;font-size:12px;color:#fff;background:var(--c);flex:none}
.row{display:flex;align-items:center;gap:10px;min-width:0}
.row .who{min-width:0}
.row .who b,.row .who span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pill{font-size:11px;font-weight:600;padding:3px 8px;border-radius:999px;white-space:nowrap}
.st-unpaid{background:color-mix(in srgb,var(--unpaid) 15%,transparent);color:var(--unpaid)}
.st-semi{background:color-mix(in srgb,var(--semi) 15%,transparent);color:var(--semi)}
.st-paid{background:color-mix(in srgb,var(--paid) 15%,transparent);color:var(--paid)}
.st-title{background:color-mix(in srgb,var(--title) 15%,transparent);color:var(--title)}
.st-other{background:var(--line);color:var(--muted)}
.r{text-align:right}
.when{font-size:12px;color:var(--muted);white-space:nowrap}
.foot-note{font-size:12px;color:var(--muted);margin-top:18px}

/* ---------- Mobile ---------- */
.mtop{display:none}
@media (max-width:1100px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.grid2{grid-template-columns:1fr}}
@media (max-width:860px){
  .app{grid-template-columns:1fr}
  .side{position:fixed;inset:0 auto 0 0;width:260px;z-index:20;transform:translateX(-100%);transition:transform .2s}
  body.nav-open .side{transform:none}
  body.nav-open::after{content:"";position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10}
  .mtop{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--side);color:#fff;position:sticky;top:0;z-index:5}
  .mtop .brand{margin:0}
  .mtop .brand b{color:#fff;font-size:16px}
  .mtop button{background:none;border:0;color:#fff;padding:6px;cursor:pointer}
  .main{padding:18px 16px 32px}
  .top h1{font-size:22px}
  .events{grid-template-columns:1fr}
}
@media (max-width:420px){.kpi .v{font-size:24px}.met .v{font-size:19px}.mets{gap:6px}.met{padding:8px 7px}}
</style>
<script>
try{var t=localStorage.getItem('np-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}
</script>
</head>
<?php if (!$me): ?>
<body>
<div class="login-wrap">
  <form class="login" method="post" autocomplete="on">
    <div class="brand"><div class="logo">FS</div><div><b>Forever Star India</b><span>New Admin Panel</span></div></div>
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <label for="u">Username</label>
    <input id="u" name="uname" value="<?= h($_POST['uname'] ?? '') ?>" autofocus required>
    <label for="p">Password</label>
    <input id="p" name="password" type="password" required>
    <button class="btn-gold" type="submit">Sign in</button>
    <?php if ($loginError): ?><div class="err"><?= h($loginError) ?></div><?php endif; ?>
    <div class="hint">Purane admin panel wala hi login chalega</div>
  </form>
</div>
</body>
</html>
<?php exit; endif; ?>
<body>
<?php
$icon = [
  'dash'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
  'link'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>',
  'out'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>',
  'sun'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  'ref'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>',
  'menu'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
];
?>
<div class="mtop">
  <div class="brand"><div class="logo" style="width:32px;height:32px;font-size:13px;border-radius:9px">FS</div><b>FSIA Admin</b></div>
  <button type="button" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')"><?= $icon['menu'] ?></button>
</div>
<div class="app">
  <aside class="side">
    <div class="brand"><div class="logo">FS</div><div><b>FSIA Admin</b><span>Forever Star India</span></div></div>
    <nav class="nav">
      <a class="active" href="<?= h($self) ?>"><?= $icon['dash'] ?>Dashboard</a>
      <div class="nav-h">Shortcuts</div>
      <?php foreach ($cfg['links'] as $l): ?>
        <a href="<?= h($l['url']) ?>"><?= $icon['link'] ?><?= h($l['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="foot">
      Signed in as <b><?= h($me['name']) ?></b>
      <a href="?logout=1">Logout</a>
    </div>
  </aside>

  <main class="main">
    <div class="top">
      <div>
        <h1><?= h($greet) ?>, <?= h($me['name']) ?></h1>
        <p><?= h(date('l, j F Y')) ?> · Season <?= h(date('Y', strtotime($season))) ?> overview</p>
      </div>
      <div class="tools">
        <a class="chip-btn" href="<?= h($self) ?>"><?= $icon['ref'] ?>Refresh</a>
        <button class="chip-btn" type="button" id="theme"><?= $icon['sun'] ?>Theme</button>
        <a class="chip-btn" href="?logout=1"><?= $icon['out'] ?>Logout</a>
      </div>
    </div>

    <section class="kpis">
      <div class="kpi gold"><div class="l">Aaj ke naye registration</div><div class="v num"><?= $fmt($tot['reg_today']) ?></div><div class="s">sab events milake</div></div>
      <div class="kpi"><div class="l">Aaj ke payments</div><div class="v num"><?= $fmt($tot['pay_today']) ?></div><div class="s">Paid + Semi + Full + Title</div></div>
      <div class="kpi"><div class="l">Total unpaid</div><div class="v num"><?= $fmt($tot['unpaid']) ?></div><div class="s">is season</div></div>
      <div class="kpi"><div class="l">Total paid profiles</div><div class="v num"><?= $fmt($tot['paid']) ?></div><div class="s">is season (sab paid status)</div></div>
    </section>

    <div class="sec-h"><h2>Events</h2><span>Bada number = total · neeche = aaj</span></div>
    <section class="events">
      <?php foreach ($events as $e):
        $st = $stats[(int) $e['regtype']] ?? [];
        $mets = eventMetrics($e, $st);
        $all = array_sum(array_column($mets, 1));
        $paidAll = $all - $mets[0][1];
        $pct = $all ? round($paidAll * 100 / $all, 1) : 0;
      ?>
      <article class="ev" style="--c:<?= h($e['color']) ?>">
        <div class="ev-h"><b><?= h($e['name']) ?></b><span class="tag"><?= $e['kind'] === 'award' ? 'Award' : 'Pageant' ?></span></div>
        <div class="mets">
          <?php foreach ($mets as [$label, $total, $todayN, $k]): ?>
          <div class="met k-<?= $k ?>">
            <div class="l"><i></i><?= h($label) ?></div>
            <div class="v num"><?= $fmt($total) ?></div>
            <div class="t num<?= $todayN > 0 ? ' up' : '' ?>"><?= $todayN > 0 ? '+' . $fmt($todayN) . ' aaj' : '0 aaj' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="bar" title="Paid share">
          <?php foreach ($mets as [$label, $total, , $k]): if (!$all || !$total) continue; ?>
            <span style="width:<?= round($total * 100 / $all, 2) ?>%;background:var(--<?= $k ?>)"></span>
          <?php endforeach; ?>
        </div>
        <div class="bar-l"><span><?= $fmt($all) ?> profiles</span><span><?= $pct ?>% paid</span></div>
      </article>
      <?php endforeach; ?>
    </section>

    <section class="grid2">
      <div class="card">
        <h3>Pichhle 14 din</h3>
        <div class="sub">Roz ke naye registration aur payments (sab events)</div>
        <div class="legend"><span><i style="background:var(--gold)"></i>Registrations</span><span><i style="background:var(--paid)"></i>Payments</span></div>
        <?php
          $W = 640; $H = 200; $pad = 26; $n = count($trend);
          $max = max(1, ...array_values(array_map(fn($d) => max($d["reg"], $d["pay"]), $trend)));
          $step = ($W - $pad) / $n; $bw = max(4, $step * 0.32);
          $gridVals = [0, (int) ceil($max / 2), $max];
        ?>
        <svg class="chart" viewBox="0 0 <?= $W ?> <?= $H + 22 ?>" role="img" aria-label="14 din ka chart">
          <?php foreach ($gridVals as $gv): $y = $H - ($gv / $max) * ($H - 10); ?>
            <line x1="<?= $pad ?>" x2="<?= $W ?>" y1="<?= $y ?>" y2="<?= $y ?>" stroke="var(--line)"/>
            <text x="<?= $pad - 6 ?>" y="<?= $y + 3 ?>" text-anchor="end"><?= $gv ?></text>
          <?php endforeach; ?>
          <?php $i = 0; foreach ($trend as $d => $v):
            $x = $pad + $i * $step + ($step - 2 * $bw - 2) / 2;
            $hr = ($v['reg'] / $max) * ($H - 10); $hp = ($v['pay'] / $max) * ($H - 10); ?>
            <rect x="<?= round($x, 1) ?>" y="<?= round($H - $hr, 1) ?>" width="<?= round($bw, 1) ?>" height="<?= round($hr, 1) ?>" rx="2" fill="var(--gold)"><title><?= h(date('j M', strtotime($d))) ?>: <?= $v['reg'] ?> registrations</title></rect>
            <rect x="<?= round($x + $bw + 2, 1) ?>" y="<?= round($H - $hp, 1) ?>" width="<?= round($bw, 1) ?>" height="<?= round($hp, 1) ?>" rx="2" fill="var(--paid)"><title><?= h(date('j M', strtotime($d))) ?>: <?= $v['pay'] ?> payments</title></rect>
            <?php if ($i % 2 === 0 || $i === $n - 1): ?>
            <text x="<?= round($x + $bw, 1) ?>" y="<?= $H + 16 ?>" text-anchor="middle"><?= h(date('j M', strtotime($d))) ?></text>
            <?php endif; ?>
          <?php $i++; endforeach; ?>
        </svg>
      </div>

      <div class="card">
        <h3>Latest payments</h3>
        <div class="sub">Sabse naye paid / semi / title</div>
        <table class="tbl">
          <?php foreach ($recentPays as $r): $e = $byType[(int) $r['regtype']]; [$sl, $sc] = statusLabel((int) $r['status'], $e['kind']); ?>
          <tr>
            <td><div class="row"><div class="ava" style="--c:<?= h($e['color']) ?>"><?= h(strtoupper(substr(trim($r['first_name']) ?: '?', 0, 1))) ?></div>
              <div class="who"><b><?= h(trim($r['first_name'] . ' ' . $r['last_name'])) ?></b><span><?= h($e['name']) ?></span></div></div></td>
            <td class="r"><span class="pill <?= $sc ?>"><?= h($sl) ?></span><div class="when"><?= h(date('j M', strtotime((string) $r['pdate']))) ?></div></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recentPays): ?><tr><td class="when">Abhi koi payment nahi</td></tr><?php endif; ?>
        </table>
      </div>
    </section>

    <section class="card">
      <h3>Latest registrations</h3>
      <div class="sub">Sabse naye 8 form</div>
      <table class="tbl">
        <?php foreach ($recentRegs as $r): $e = $byType[(int) $r['regtype']]; [$sl, $sc] = statusLabel((int) $r['status'], $e['kind']); ?>
        <tr>
          <td><div class="row"><div class="ava" style="--c:<?= h($e['color']) ?>"><?= h(strtoupper(substr(trim($r['first_name']) ?: '?', 0, 1))) ?></div>
            <div class="who"><b><?= h(trim($r['first_name'] . ' ' . $r['last_name'])) ?></b><span><?= h($e['name']) ?> · #<?= (int) $r['id'] ?></span></div></div></td>
          <td class="r"><span class="pill <?= $sc ?>"><?= h($sl) ?></span><div class="when"><?= h(date('j M Y', strtotime((string) $r['date_of_reg']))) ?></div></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </section>

    <p class="foot-note">Yeh naya panel sirf dekhne ke liye hai, isse koi data nahi badalta. Profile edit/approve ke liye purana admin panel use karein.</p>
  </main>
</div>
<script>
document.getElementById('theme').addEventListener('click',function(){
  var r=document.documentElement,cur=r.getAttribute('data-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');
  var next=cur==='dark'?'light':'dark';r.setAttribute('data-theme',next);try{localStorage.setItem('np-theme',next);}catch(e){}
});
document.querySelectorAll('.side a').forEach(function(a){a.addEventListener('click',function(){document.body.classList.remove('nav-open')})});
</script>
</body>
</html>
