/*
 * FSIA website-wide event promotion.
 * Add once to the shared footer (e.g. footer1806.php), before </body>:
 *   <script src="/cms/promo.js" defer></script>
 * It asks the CMS which promotion belongs on this page (Events & Promotions in the
 * CMS admin decides: pages, dates, priority) and shows it at the bottom of the
 * screen: a full-width bar on computers, a floating card on phones. A visitor can
 * close it; it stays closed for that event until the event is changed.
 * Text is set as text, never as HTML.
 *
 * The CMS admin runs this same file for its previews (window.__fsiaPromoPreview
 * set first): it then only defines window.FSIAPromo.build and fetches nothing.
 */
(function () {
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var IST = 330 * 60000;
  var DAY = 86400000;

  var CSS = [
    '.fsia-promo,.fsia-promo *{box-sizing:border-box;margin:0;padding:0;border:0;line-height:1.3;letter-spacing:normal;text-transform:none;text-align:left;font-style:normal}',
    '.fsia-promo{position:fixed;left:0;right:0;bottom:0;z-index:2147483000;color:#F6F2E8;font:500 14px/1.3 "Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;' +
      'background:linear-gradient(100deg,#0A0F1C 0%,#111A2E 55%,#1C1A12 100%);border-top:1px solid rgba(212,175,55,.55);box-shadow:0 -12px 32px rgba(0,0,0,.28);' +
      'animation:fsia-promo-in .5s cubic-bezier(.2,.8,.2,1) both}',
    '.fsia-promo::before{content:"";position:absolute;left:0;right:0;top:-1px;height:2px;background:linear-gradient(90deg,transparent,#F3D27A 20%,#D4AF37 50%,#F3D27A 80%,transparent)}',
    '.fsia-promo-in{display:flex;align-items:center;gap:18px;max-width:1240px;margin:0 auto;padding:12px 60px 12px 20px}',
    '.fsia-promo-pic{flex:none;width:136px;height:68px;border-radius:10px;object-fit:cover;border:1px solid rgba(212,175,55,.5);background:#1A2236}',
    '.fsia-promo-txt{flex:1;min-width:0}',
    '.fsia-promo-eye{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#E2C46B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-eye span{text-transform:uppercase;letter-spacing:inherit}',
    '.fsia-promo-dot{flex:none;width:7px;height:7px;border-radius:50%;background:#34D399;box-shadow:0 0 0 0 rgba(52,211,153,.6);animation:fsia-promo-pulse 1.8s infinite}',
    '.fsia-promo-title{margin-top:3px;font:700 19px/1.2 Cinzel,"Playfair Display",Georgia,serif;color:#FFFFFF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-meta{display:flex;align-items:center;gap:14px;margin-top:5px;font-size:13px;color:#C9CFDD;white-space:nowrap;overflow:hidden}',
    '.fsia-promo-meta span{display:inline-flex;align-items:center;gap:5px;min-width:0;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-meta svg{flex:none;width:14px;height:14px;color:#D4AF37}',
    '.fsia-promo-sum{color:#9AA3B5;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-guests{flex:none;display:flex;align-items:center;gap:16px;padding-left:18px;border-left:1px solid rgba(255,255,255,.12)}',
    '.fsia-promo-g{display:flex;align-items:center;gap:10px;max-width:230px;min-width:0}',
    '.fsia-promo-face{flex:none;width:50px;height:50px;border-radius:50%;object-fit:cover;background:#1A2236;box-shadow:0 0 0 2px #0A0F1C,0 0 0 4px #D4AF37}',
    '.fsia-promo-face-x{display:flex;align-items:center;justify-content:center;color:#D4AF37;font-size:20px}',
    '.fsia-promo-role{font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#E2C46B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-name{margin-top:2px;font-size:15px;font-weight:700;color:#FFFFFF;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fsia-promo-cta{flex:none;display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:800;color:#0C1322 !important;text-decoration:none !important;white-space:nowrap;' +
      'background:linear-gradient(135deg,#F6DC8C 0%,#D4AF37 55%,#B98F1E 100%);box-shadow:0 6px 18px rgba(212,175,55,.35);transition:transform .15s,box-shadow .15s}',
    '.fsia-promo-cta:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(212,175,55,.45)}',
    '.fsia-promo-cta svg{width:16px;height:16px}',
    '.fsia-promo-x{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.08);color:#F6F2E8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}',
    '.fsia-promo-x:hover{background:rgba(255,255,255,.18)}',
    '.fsia-promo-x svg{width:14px;height:14px}',
    /* Phones: a floating card */
    '.fsia-promo.fsia-m{left:10px;right:10px;bottom:10px;border:1px solid rgba(212,175,55,.45);border-radius:18px;box-shadow:0 14px 40px rgba(0,0,0,.4)}',
    '.fsia-promo.fsia-m::before{left:24px;right:24px}',
    '.fsia-m .fsia-promo-in{display:block;padding:12px}',
    '.fsia-m .fsia-promo-top{display:flex;align-items:center;gap:12px;padding-right:30px}',
    '.fsia-m .fsia-promo-pic{width:62px;height:62px;border-radius:12px}',
    '.fsia-m .fsia-promo-eye{font-size:10px;letter-spacing:.12em}',
    '.fsia-m .fsia-promo-title{font-size:16px;white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}',
    '.fsia-m .fsia-promo-meta{margin-top:4px;font-size:12px;gap:10px}',
    '.fsia-m .fsia-promo-bot{display:flex;align-items:center;gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.1)}',
    '.fsia-m .fsia-promo-guests{flex:1;min-width:0;padding:0;border:0;gap:10px}',
    '.fsia-m .fsia-promo-g{max-width:none}',
    '.fsia-m .fsia-promo-face{width:34px;height:34px;box-shadow:0 0 0 2px #0A0F1C,0 0 0 3px #D4AF37}',
    '.fsia-m .fsia-promo-role{font-size:9.5px}',
    '.fsia-m .fsia-promo-name{font-size:13px}',
    '.fsia-m .fsia-promo-cta{padding:11px 18px;font-size:13px}',
    '.fsia-m .fsia-promo-cta.fsia-wide{flex:1;justify-content:center}',
    '.fsia-m .fsia-promo-x{top:10px;right:10px;transform:none;width:28px;height:28px}',
    /* Admin previews: in the flow, no animation */
    '.fsia-promo.fsia-pv{position:relative;z-index:auto;animation:none}',
    '.fsia-promo.fsia-pv.fsia-m{left:auto;right:auto;bottom:auto;margin:0 10px 10px}',
    '@keyframes fsia-promo-in{from{transform:translateY(110%);opacity:0}to{transform:none;opacity:1}}',
    '@keyframes fsia-promo-pulse{70%{box-shadow:0 0 0 7px rgba(52,211,153,0)}100%{box-shadow:0 0 0 0 rgba(52,211,153,0)}}',
    '@media (max-width:1180px){.fsia-promo:not(.fsia-pv) .fsia-promo-sum{display:none}}',
    '.fsia-promo.fsia-narrow .fsia-promo-sum{display:none}',
    '@media (prefers-reduced-motion:reduce){.fsia-promo,.fsia-promo-dot{animation:none}}',
  ].join('\n');

  var ICON = {
    date: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    place: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
  };

  function day(s) { var d = String(s || '').slice(0, 10).split('-'); return d.length === 3 ? (+d[2]) + ' ' + MONTHS[+d[1] - 1] + ' ' + d[0] : ''; }
  // "26 Oct 2026", "26–28 Oct 2026", "30 Oct – 2 Nov 2026"
  function when(p) {
    var a = day(p.starts_at), b = day(p.ends_at);
    if (!b || a === b) return a;
    var x = a.split(' '), y = b.split(' ');
    if (x[2] === y[2] && x[1] === y[1]) return x[0] + '\u2013' + b;
    if (x[2] === y[2]) return x[0] + ' ' + x[1] + ' \u2013 ' + b;
    return a + ' \u2013 ' + b;
  }
  /** "2027-01-08 18:00:00" (India time) -> ms since epoch */
  function ist(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(String(s || ''));
    return m ? Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) - IST : null;
  }
  /** Line above the title: "Happening now", "Upcoming event · In 12 days", ... */
  function status(p, now) {
    var st = ist(p.starts_at), en = ist(p.ends_at);
    if (st === null) return { text: 'Upcoming event' };
    if (now >= st && (en === null || now <= en)) return { text: 'Happening now', live: true };
    var days = Math.floor((st + IST) / DAY) - Math.floor((now + IST) / DAY);
    var soon = days <= 0 ? 'Today' : days === 1 ? 'Tomorrow' : days <= 60 ? 'In ' + days + ' days' : '';
    return { text: soon ? 'Upcoming event \u00b7 ' + soon : 'Upcoming event' };
  }

  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; }
  function icon(name) { var s = el('span'); s.innerHTML = ICON[name]; return s.firstChild; }
  function addCss(doc) {
    if (doc.getElementById('fsia-promo-css')) return;
    var st = doc.createElement('style');
    st.id = 'fsia-promo-css';
    st.textContent = CSS;
    (doc.head || doc.documentElement).appendChild(st);
  }

  /**
   * The promotion element. opts: mobile (phone layout), preview (in the page flow,
   * close does nothing), narrow (no summary; previews under 1180px wide), asset (turns stored image paths into URLs), now (ms), onClose.
   */
  function build(p, opts) {
    opts = opts || {};
    var asset = opts.asset || function (u) { return u; };
    var mobile = !!opts.mobile;
    addCss(document);

    var bar = el('aside', 'fsia-promo' + (mobile ? ' fsia-m' : '') + (opts.preview ? ' fsia-pv' : '') + (opts.narrow ? ' fsia-narrow' : ''));
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Event: ' + (p.title || ''));
    var inner = el('div', 'fsia-promo-in');
    bar.appendChild(inner);

    var guests = (p.guests || []).filter(function (g) { return g && g.name; });
    var banner = mobile ? (p.banner_mobile || p.banner_desktop) : (p.banner_desktop || p.banner_mobile);
    var picSrc = banner || (mobile && guests[0] && guests[0].photo) || '';

    // Text block
    var txt = el('div', 'fsia-promo-txt');
    var s = status(p, opts.now || Date.now());
    var eye = el('div', 'fsia-promo-eye');
    if (s.live) eye.appendChild(el('span', 'fsia-promo-dot'));
    eye.appendChild(el('span', '', s.text));
    txt.appendChild(eye);
    txt.appendChild(el('div', 'fsia-promo-title', p.title || 'Event title'));
    var meta = el('div', 'fsia-promo-meta');
    var date = when(p);
    var place = mobile ? (p.city || p.venue) : [p.venue, p.city].filter(Boolean).join(', ');
    if (date) { var d = el('span'); d.appendChild(icon('date')); d.appendChild(el('span', '', date)); meta.appendChild(d); }
    if (place) { var pl = el('span'); pl.appendChild(icon('place')); pl.appendChild(el('span', '', place)); meta.appendChild(pl); }
    if (p.summary && !mobile) meta.appendChild(el('span', 'fsia-promo-sum', p.summary));
    if (meta.firstChild) txt.appendChild(meta);

    function pic(src, cls) {
      var img = el('img', cls);
      img.alt = '';
      img.loading = 'lazy';
      img.src = asset(src);
      img.onerror = function () { img.remove(); };
      return img;
    }

    // Guests (celebrity chief guest)
    var gbox = null;
    var shown = mobile ? guests.slice(0, 1) : guests;
    if (shown.length) {
      gbox = el('div', 'fsia-promo-guests');
      shown.forEach(function (g) {
        var row = el('div', 'fsia-promo-g');
        if (g.photo) {
          var face = pic(g.photo, 'fsia-promo-face');
          face.onerror = function () { var x = el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605'); face.replaceWith(x); };
          row.appendChild(face);
        } else {
          row.appendChild(el('div', 'fsia-promo-face fsia-promo-face-x', '\u2605'));
        }
        var who = el('div', '');
        who.style.minWidth = '0';
        if (g.role) who.appendChild(el('div', 'fsia-promo-role', g.role));
        who.appendChild(el('div', 'fsia-promo-name', g.name));
        row.appendChild(who);
        gbox.appendChild(row);
      });
    }

    var cta = null;
    if (p.cta_url) {
      cta = el('a', 'fsia-promo-cta' + (mobile && !gbox ? ' fsia-wide' : ''));
      cta.href = p.cta_url;
      cta.appendChild(el('span', '', p.cta_label || 'Register now'));
      cta.appendChild(icon('arrow'));
      if (opts.preview) cta.addEventListener('click', function (e) { e.preventDefault(); });
    }

    if (mobile) {
      var top = el('div', 'fsia-promo-top');
      if (picSrc) top.appendChild(pic(picSrc, 'fsia-promo-pic'));
      top.appendChild(txt);
      inner.appendChild(top);
      if (gbox || cta) {
        var bot = el('div', 'fsia-promo-bot');
        if (gbox) bot.appendChild(gbox);
        if (cta) bot.appendChild(cta);
        inner.appendChild(bot);
      }
    } else {
      if (banner) inner.appendChild(pic(banner, 'fsia-promo-pic'));
      inner.appendChild(txt);
      if (gbox) inner.appendChild(gbox);
      if (cta) inner.appendChild(cta);
    }

    var close = el('button', 'fsia-promo-x');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close');
    close.appendChild(icon('close'));
    close.onclick = function () { if (opts.onClose) opts.onClose(bar); };
    bar.appendChild(close);
    return bar;
  }

  window.FSIAPromo = { build: build };
  if (window.__fsiaPromoPreview || window.__fsiaPromo) return;
  window.__fsiaPromo = true;

  // --- On the website --------------------------------------------------------
  var script = document.currentScript;
  var api = (script && script.getAttribute('data-api')) || '/cms/backend/api/promotions.php';
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  function show(p) {
    var key = 'fsia-promo-closed-' + p.id;
    if (store(key) === String(p.version)) return;
    var mq = window.matchMedia ? window.matchMedia('(max-width: 640px)') : null;
    var pad = document.body.style.paddingBottom;
    var bar = null;
    function place() {
      var next = build(p, {
        mobile: !!(mq && mq.matches),
        onClose: function (b) { store(key, String(p.version)); b.remove(); bar = null; document.body.style.paddingBottom = pad; },
      });
      next.id = 'fsia-promo';
      if (bar) { next.style.animation = 'none'; bar.replaceWith(next); } else document.body.appendChild(next);
      bar = next;
      // Keep the page's own footer reachable above the bar / card.
      document.body.style.paddingBottom = (bar.offsetHeight + (mq && mq.matches ? 20 : 0)) + 'px';
    }
    place();
    if (mq) {
      var redo = function () { if (bar) place(); };
      if (mq.addEventListener) mq.addEventListener('change', redo); else if (mq.addListener) mq.addListener(redo);
    }
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
