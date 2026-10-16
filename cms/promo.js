/*
 * FSIA website-wide event promotion.
 * Add once to the shared footer (e.g. footer1806.php), before </body>:
 *   <script src="/cms/promo.js" defer></script>
 * It asks the CMS which promotion belongs on this page (Events & Promotions in the
 * CMS admin decides: pages, dates, priority) and shows it as a slim bar at the
 * bottom of the screen. A visitor can close it; it stays closed for that event
 * until the event is changed. Text is set as text, never as HTML.
 */
(function () {
  if (window.__fsiaPromo) return;
  window.__fsiaPromo = true;
  var script = document.currentScript;
  var api = (script && script.getAttribute('data-api')) || '/cms/backend/api/promotions.php';
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function day(s) { var d = String(s || '').slice(0, 10).split('-'); return d.length === 3 ? (+d[2]) + ' ' + MONTHS[+d[1] - 1] + ' ' + d[0] : ''; }
  // "26 Oct 2026", "26–28 Oct 2026", "30 Oct – 2 Nov 2026"
  function when(p) {
    var a = day(p.starts_at), b = day(p.ends_at);
    if (!b || a === b) return a;
    var x = a.split(' '), y = b.split(' ');
    if (x[2] === y[2] && x[1] === y[1]) return x[0] + '–' + b;
    if (x[2] === y[2]) return x[0] + ' ' + x[1] + ' – ' + b;
    return a + ' – ' + b;
  }
  function el(tag, css, text) { var e = document.createElement(tag); if (css) e.style.cssText = css; if (text) e.textContent = text; return e; }
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  function show(p) {
    var key = 'fsia-promo-closed-' + p.id;
    if (store(key) === String(p.version)) return;
    var mobile = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
    var bar = el('aside', 'position:fixed;left:0;right:0;bottom:0;z-index:2147483000;display:flex;align-items:center;gap:12px;' +
      'padding:10px 44px 10px 12px;background:#0C1322;color:#F6F2E8;box-shadow:0 -6px 24px rgba(0,0,0,.25);' +
      'font:500 14px/1.35 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif');
    bar.id = 'fsia-promo';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Event');
    var img = mobile ? (p.banner_mobile || p.banner_desktop) : (p.banner_desktop || p.banner_mobile);
    if (img) {
      var pic = el('img', 'height:' + (mobile ? 44 : 52) + 'px;width:auto;max-width:' + (mobile ? 70 : 160) + 'px;object-fit:cover;border-radius:8px;flex:none');
      pic.alt = '';
      pic.src = img;
      pic.onerror = function () { pic.remove(); };
      bar.appendChild(pic);
    }
    var info = el('div', 'min-width:0;flex:1');
    info.appendChild(el('div', 'font-weight:700;color:#D4AF37;white-space:nowrap;overflow:hidden;text-overflow:ellipsis', p.title));
    var line = [when(p), [p.venue, p.city].filter(Boolean).join(', ')].filter(Boolean).join(' · ');
    if (p.summary && !mobile) line = line ? line + ' · ' + p.summary : p.summary;
    if (line) info.appendChild(el('div', 'font-size:12px;color:#C9CFDD;white-space:nowrap;overflow:hidden;text-overflow:ellipsis', line));
    bar.appendChild(info);
    if (p.cta_url) {
      var cta = el('a', 'flex:none;padding:9px 14px;border-radius:999px;background:#D4AF37;color:#0C1322;font-weight:700;font-size:13px;text-decoration:none;white-space:nowrap', p.cta_label || 'Register now');
      cta.href = p.cta_url;
      bar.appendChild(cta);
    }
    var close = el('button', 'position:absolute;right:8px;top:50%;transform:translateY(-50%);width:30px;height:30px;border:0;border-radius:50%;' +
      'background:rgba(255,255,255,.08);color:#F6F2E8;font-size:18px;line-height:30px;cursor:pointer', '×');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close');
    close.onclick = function () { store(key, String(p.version)); bar.remove(); document.body.style.paddingBottom = pad; };
    bar.appendChild(close);
    var pad = document.body.style.paddingBottom;
    document.body.appendChild(bar);
    document.body.style.paddingBottom = bar.offsetHeight + 'px';   // keep the page's own footer reachable
  }

  function start() {
    if (document.getElementById('fsia-promo')) return;
    fetch(api + '?path=' + encodeURIComponent(location.pathname), { credentials: 'omit' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { if (d && d.promotion) show(d.promotion); })
      .catch(function () {});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
