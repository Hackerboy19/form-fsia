<?php
// Admin pages khulne par login record karta hai (time, IP, browser). GPS nahi.
// Admin page mein yeh ek line jodni hai:
//   <script src="/fsiaadmm21/site-monitor/track.php?js=1&kind=Admin+panel" defer></script>
require __DIR__ . '/lib.php';
header('Cache-Control: no-store');

if (isset($_GET['js'])) {
    header('Content-Type: application/javascript; charset=utf-8');
    $kind = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)($_GET['kind'] ?? 'Admin'));
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    ?>
(function () {
  var KEY = 'fsia_sm_trk';
  try { var last = +localStorage.getItem(KEY) || 0; if (Date.now() - last < 6 * 3600 * 1000) return; localStorage.setItem(KEY, Date.now()); } catch (e) {}
  var url = <?= json_encode($url) ?>, tok = Math.random().toString(36).slice(2) + Date.now().toString(36);
  function send(d) {
    d.t = tok; d.kind = <?= json_encode($kind) ?>; d.page = location.pathname;
    try { fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d), keepalive: true }); } catch (e) {}
  }
  send({});
})();
<?php
    exit;
}

// Beacon
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{}'); }
$host = parse_url($_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
$me = preg_replace(['/^www\./', '/:\d+$/'], '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
if ($host && preg_replace('/^www\./', '', strtolower($host)) !== $me) { http_response_code(403); exit('{}'); }
$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in) || empty($in['t']) || !preg_match('/^[a-z0-9]{6,40}$/', $in['t'])) { http_response_code(400); exit('{}'); }
try {
    sm_config();
    sm_login_install();
    $tok = $in['t'];
    if (!sm_rows("SELECT id FROM site_monitor_logins WHERE token='" . sm_e($tok) . "' LIMIT 1")) {
        $kind = mb_substr(preg_replace('/[^A-Za-z0-9 _-]/', '', (string)($in['kind'] ?? 'Admin')), 0, 40);
        sm_log_login($kind, sm_client_ip(), '', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''), (string)($in['page'] ?? ''), $tok);
    }
} catch (Throwable $e) {
    http_response_code(500);
}
echo '{}';
