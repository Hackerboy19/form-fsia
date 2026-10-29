/*
 * FSIA event calendar for registration form pages.
 * Add once to the shared footer, next to promo.js:
 *   <script src="/cms/calendar.js?v=1" defer></script>
 *
 * It reads the homepage's "Event Calendar" from the CMS (Homepage > Event Calendar)
 * and, on pages that have a registration form, shows a month calendar, an agenda
 * list and a day inspector at the top of the page, just above the form. The same CMS tab has the switch
 * "Show on registration form pages" and the option to hide it by itself once every
 * event is over. To place it somewhere else on a page, put
 *   <div id="fsia-event-calendar"></div>
 * where it should go. Text is set as text, never as HTML.
 */
(function () {
  if (window.__fsiaCalendar) return;
  window.__fsiaCalendar = true;

  var script = document.currentScript;
  var API = (script && script.getAttribute('data-api')) || '/cms/backend/api/sections.php?page=index&key=calendar_events';
  var FORM_SEL = 'form#registrationForm, form[data-form-type="register"], form#qform';

  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var TYPES = [
    { value: 'all', label: 'All Dates' },
    { value: 'audition', label: 'Auditions & Screenings', color: '#059669', soft: '#5F8F6B' },
    { value: 'workshop', label: 'Masterclasses & Camps', color: '#0d9488', soft: '#3E8A84' },
    { value: 'pageant', label: 'Pageant Finales', color: '#9F1239', soft: '#9F1239' },
    { value: 'award', label: 'National Awards', color: '#D4AF37', soft: '#A8892C' },
    { value: 'ceremony', label: 'Coronation Galas', color: '#7c2d12', soft: '#7c2d12' },
  ];
  var STATUS = { upcoming: 'Upcoming', open: 'Registrations Open', active: 'Happening Now', closing_soon: 'Closing Soon', concluded: 'Concluded' };

  var CSS = [
    '.fcal,.fcal *{box-sizing:border-box}',
    '.fcal{--g:#B8860B;--gd:#7E591B;--gl:#EADBAC;--ink:#0C1322;--mut:#64748B;--red:#8B2635;background:#FAF9F5;border-top:1px solid #EADBAC;padding:56px 16px;font:400 14px/1.5 "Plus Jakarta Sans",Poppins,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink);text-align:left}',
    '.fcal-w{max-width:1180px;margin:0 auto}',
    '.fcal h2,.fcal h3,.fcal h4,.fcal p,.fcal ul{margin:0;padding:0}',
    '.fcal button{font:inherit;cursor:pointer}',
    '.fcal-head{text-align:center;max-width:760px;margin:0 auto 28px}',
    '.fcal-badge{display:inline-flex;align-items:center;gap:8px;padding:4px 12px;background:#fff;border:1px solid rgba(212,175,55,.5);color:var(--gd);font-size:11px;font-weight:600;letter-spacing:.18em;text-transform:uppercase}',
    '.fcal-badge i{width:6px;height:6px;border-radius:50%;background:var(--g)}',
    '.fcal-head h2{margin-top:14px;font:700 34px/1.15 "Playfair Display",Georgia,serif;color:var(--ink)}',
    '.fcal-head p{margin-top:10px;color:#475569;font-size:15px}',
    '.fcal-card{background:#fff;border:1px solid rgba(234,219,172,.9);border-radius:22px;box-shadow:0 1px 2px rgba(12,19,34,.04)}',
    '.fcal-bar{display:flex;align-items:center;gap:16px;padding:18px 20px;margin-bottom:28px}',
    '.fcal-views{flex:none;display:flex;gap:4px;padding:4px;background:#F4F1EA;border-radius:12px}',
    '.fcal-views button{display:flex;align-items:center;gap:8px;border:0;background:transparent;padding:8px 14px;border-radius:9px;font-size:13px;font-weight:600;color:#44403C;line-height:1.25}',
    '.fcal-views button[aria-pressed="true"]{background:#fff;color:var(--ink);box-shadow:0 1px 3px rgba(0,0,0,.08)}',
    '.fcal-views svg{width:13px;height:13px;color:var(--g);flex:none}',
    '.fcal-chips{flex:1;display:flex;flex-wrap:wrap;gap:8px;min-width:0}',
    '.fcal-chips button{border:1px solid #E7E5E4;background:#FAFAF9;color:#292524;padding:7px 12px;border-radius:8px;font-size:13px;line-height:1.2}',
    '.fcal-chips button:hover{border-color:var(--g)}',
    '.fcal-chips button[aria-pressed="true"]{background:var(--red);border-color:var(--red);color:#fff}',
    '.fcal-search{flex:none;position:relative;width:230px}',
    '.fcal-search svg{position:absolute;left:11px;top:50%;width:14px;height:14px;transform:translateY(-50%);color:#A8A29E}',
    '.fcal-search input{width:100%;height:36px;padding:0 10px 0 32px;border:1px solid #E7E5E4;border-radius:8px;background:#FAFAF9;font:inherit;font-size:13px;color:var(--ink);outline:none}',
    '.fcal-search input:focus{border-color:var(--g);background:#fff}',
    '.fcal-grid{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:28px;align-items:start}',
    '.fcal-cal{padding:26px 24px}',
    '.fcal-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding-bottom:16px;border-bottom:1px solid #F1EDE4}',
    '.fcal-top h3{font:700 26px/1.2 "Playfair Display",Georgia,serif}',
    '.fcal-top p{margin-top:4px;color:var(--mut);font-size:12.5px}',
    '.fcal-nav{flex:none;display:flex;align-items:center;gap:8px}',
    '.fcal-peak{border:1px solid #EADBAC;background:#FFFDF7;color:var(--gd);padding:5px 10px;border-radius:7px;font-size:12.5px}',
    '.fcal-arrow{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1px solid #E7E5E4;background:#fff;border-radius:8px;color:#292524}',
    '.fcal-arrow svg{width:14px;height:14px}',
    '.fcal-wd{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px;margin:20px 0 10px;text-align:center;font:600 12px/1 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.08em;color:#78716C}',
    '.fcal-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px}',
    '.fcal-day{position:relative;min-height:70px;padding:8px;border:1px solid #F0EDE7;border-radius:12px;background:#fff;text-align:left;display:flex;flex-direction:column;justify-content:space-between;gap:4px;color:var(--ink);transition:border-color .15s,opacity .15s}',
    '.fcal-day:hover{border-color:#D6C79A}',
    '.fcal-day b{font:700 14px/1 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}',
    '.fcal-day.is-out{visibility:hidden}',
    '.fcal-day.has{background:#FFFDF5;border-color:#EADBAC}',
    '.fcal-day.is-today b{color:var(--g)}',
    '.fcal-day.is-sel{background:#F7ECEC;border-color:var(--red);box-shadow:0 0 0 1px var(--red) inset}',
    '.fcal-day.is-sel b{color:var(--red)}',
    '.fcal-day.is-hit{box-shadow:0 0 0 2px #D4AF37}',
    '.fcal-day.is-dim{opacity:.35}',
    '.fcal-star{position:absolute;top:6px;right:8px;color:#D4AF37;font-size:12px;line-height:1}',
    '.fcal-tag{display:block;max-width:100%;padding:2px 5px;border-radius:4px;font-size:10.5px;font-weight:600;line-height:1.35;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.fcal-more{font-size:10px;color:var(--mut)}',
    '.fcal-legend{display:flex;flex-wrap:wrap;gap:6px 16px;margin-top:18px;padding-top:14px;border-top:1px solid #F1EDE4;font-size:12px;color:#57534E}',
    '.fcal-legend span{display:inline-flex;align-items:center;gap:6px}',
    '.fcal-legend i{width:9px;height:9px;border-radius:50%}',
    '.fcal-ins{padding:26px 24px}',
    '.fcal-eye{font:600 11.5px/1.2 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.16em;text-transform:uppercase;color:var(--g)}',
    '.fcal-ins>h3{margin-top:6px;font:700 24px/1.2 "Playfair Display",Georgia,serif}',
    '.fcal-cnt{margin-top:4px;font-size:12.5px;color:var(--mut);padding-bottom:16px;border-bottom:1px solid #F1EDE4;margin-bottom:18px}',
    '.fcal-ev{border:1px solid #E7E5E4;border-radius:14px;padding:14px;background:#FCFBF9}',
    '.fcal-ev+.fcal-ev{margin-top:16px}',
    '.fcal-pic{position:relative;aspect-ratio:16/9;border-radius:10px;overflow:hidden;background:#1A2236}',
    '.fcal-pic img{width:100%;height:100%;object-fit:cover;display:block}',
    '.fcal-pic::after{content:"";position:absolute;inset:40% 0 0;background:linear-gradient(transparent,rgba(0,0,0,.75))}',
    '.fcal-pic-cap{position:absolute;left:12px;right:12px;bottom:10px;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:8px;color:#fff;font-size:12.5px;font-weight:600}',
    '.fcal-pic-cap span:first-child{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}',
    '.fcal-st{flex:none;padding:3px 7px;border:1px solid rgba(243,210,122,.5);background:rgba(0,0,0,.45);color:#F3D27A;font:600 10.5px/1.2 ui-monospace,Menlo,Consolas,monospace;letter-spacing:.08em;text-transform:uppercase;border-radius:3px}',
    '.fcal-ev h4{margin-top:14px;font:700 19px/1.3 "Playfair Display",Georgia,serif;color:var(--ink)}',
    '.fcal-meta{margin-top:10px;display:grid;gap:5px;font-size:13px;color:#44403C}',
    '.fcal-meta div{display:flex;gap:8px;align-items:flex-start}',
    '.fcal-meta svg{flex:none;width:14px;height:14px;margin-top:2px;color:var(--g)}',
    '.fcal-desc{margin-top:10px;font-size:13.5px;color:#475569}',
    '.fcal-elig{margin-top:10px;padding:8px 10px;border-left:3px solid #D4AF37;background:#FFFBEB;font-size:12.5px;color:#57534E}',
    '.fcal-hl{margin-top:10px;list-style:none;display:grid;gap:4px;font-size:12.5px;color:#44403C}',
    '.fcal-hl li{position:relative;padding-left:16px}',
    '.fcal-hl li::before{content:"\\2726";position:absolute;left:0;color:#D4AF37;font-size:11px}',
    '.fcal-cta{display:inline-flex;align-items:center;gap:8px;margin-top:14px;padding:10px 18px;border-radius:999px;background:linear-gradient(135deg,#F6DC8C,#D4AF37 55%,#B98F1E);color:#0C1322 !important;font-size:13.5px;font-weight:700;text-decoration:none !important}',
    '.fcal-cta:hover{filter:brightness(1.05)}',
    '.fcal-empty{padding:28px 10px;text-align:center;color:var(--mut);font-size:13.5px}',
    '.fcal-empty button{margin-top:12px;border:1px solid #EADBAC;background:#FFFDF7;color:var(--gd);padding:8px 14px;border-radius:8px;font-size:13px}',
    '.fcal-ag{padding:10px 24px}',
    '.fcal-row{display:flex;gap:18px;align-items:flex-start;padding:18px 0;border-bottom:1px solid #F1EDE4}',
    '.fcal-row:last-child{border-bottom:0}',
    '.fcal-date{flex:none;width:66px;padding:8px 0;border:1px solid #EADBAC;border-radius:12px;background:#FFFDF5;text-align:center}',
    '.fcal-date b{display:block;font:700 24px/1 "Playfair Display",Georgia,serif;color:var(--ink)}',
    '.fcal-date span{display:block;margin-top:4px;font:600 10.5px/1 ui-monospace,Menlo,Consolas,monospace;letter-spacing:.1em;text-transform:uppercase;color:var(--gd)}',
    '.fcal-row-b{flex:1;min-width:0}',
    '.fcal-row-b h4{margin-top:4px;font:700 17px/1.3 "Playfair Display",Georgia,serif}',
    '.fcal-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:700;color:#fff;letter-spacing:.04em}',
    '.fcal-row .fcal-meta{margin-top:6px}',
    '.fcal-row .fcal-cta{margin-top:10px;padding:7px 14px;font-size:12.5px}',
    '.fcal-past{opacity:.55}',
    '@media (max-width:1024px){.fcal-bar{flex-wrap:wrap}.fcal-search{width:100%}.fcal-grid{grid-template-columns:1fr}}',
    '@media (max-width:640px){.fcal{padding:40px 12px}.fcal-head h2{font-size:26px}.fcal-bar{padding:14px;gap:12px}.fcal-views{width:100%}.fcal-views button{flex:1;justify-content:center;padding:8px 6px;font-size:12px}' +
      '.fcal-chips{flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px;-webkit-overflow-scrolling:touch}.fcal-chips button{flex:none}' +
      '.fcal-cal,.fcal-ins{padding:18px 14px}.fcal-top{flex-direction:column}.fcal-top h3{font-size:22px}.fcal-wd,.fcal-days{gap:4px}.fcal-wd{font-size:10px;letter-spacing:0}' +
      '.fcal-day{min-height:48px;padding:5px;border-radius:9px}.fcal-day b{font-size:12px}.fcal-tag{height:6px;padding:0;font-size:0;border-radius:3px}.fcal-more{display:none}.fcal-star{top:3px;right:4px;font-size:10px}' +
      '.fcal-ag{padding:4px 14px}.fcal-row{gap:12px}.fcal-date{width:56px}.fcal-ins>h3{font-size:20px}}',
  ].join('');

  var ICON = {
    cal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    pin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    left: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m15 18-6-6 6-6"/></svg>',
    right: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m9 18 6-6-6-6"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="14" height="14"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
  };

  /* ---------- small helpers ---------- */
  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null && text !== '') e.textContent = String(text);
    return e;
  }
  function icon(name) { var s = el('span'); s.innerHTML = ICON[name]; return s.firstChild; } // fixed SVGs only
  function str(v) { return typeof v === 'string' ? v.trim() : ''; }
  function safeUrl(u) {
    u = str(u);
    return /^(https?:\/\/|\/(?!\/)|tel:|mailto:)/i.test(u) ? u : '';
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
  function parse(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function addDays(s, n) { var d = parse(s); d.setDate(d.getDate() + n); return iso(d.getFullYear(), d.getMonth(), d.getDate()); }
  function todayIST() {
    var d = new Date(Date.now() + (330 + new Date().getTimezoneOffset()) * 60000);
    return iso(d.getFullYear(), d.getMonth(), d.getDate());
  }
  function longDate(s) { var d = parse(s); return DAYS[d.getDay()] + ', ' + MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear(); }
  function typeOf(t) { for (var i = 1; i < TYPES.length; i++) if (TYPES[i].value === t) return TYPES[i]; return { value: t, label: 'Event', color: '#64748B', soft: '#64748B' }; }
  function isDate(s) { return /^\d{4}-\d{2}-\d{2}$/.test(s || ''); }

  /* Dates an event is drawn on: every day of a short event (up to a week), otherwise its first and last day. */
  function eventDays(e) {
    if (!e.end || e.end === e.date) return [e.date];
    var days = [], d = e.date;
    for (var i = 0; i < 7 && d <= e.end; i++) { days.push(d); d = addDays(d, 1); }
    if (d <= e.end) return [e.date, e.end];
    return days;
  }

  function normalize(raw) {
    var out = [];
    (Array.isArray(raw) ? raw : []).forEach(function (r, i) {
      if (!r || !isDate(r.date)) return;
      var e = {
        id: str(r.id) || 'e' + i,
        title: str(r.title) || 'FSIA Event',
        date: r.date,
        end: isDate(r.endDate) && r.endDate > r.date ? r.endDate : '',
        time: str(r.time), type: str(r.type), category: str(r.category), venue: str(r.venue), city: str(r.city),
        description: str(r.description), eligibility: str(r.eligibility),
        highlights: Array.isArray(r.highlights) ? r.highlights.map(str).filter(Boolean) : [],
        status: str(r.status) || 'upcoming',
        ctaText: str(r.ctaText), ctaUrl: safeUrl(r.ctaUrl), image: safeUrl(r.image),
        keywords: Array.isArray(r.keywords) ? r.keywords.map(str).filter(Boolean) : [],
        star: !!r.highlight,
      };
      e.days = eventDays(e);
      e.last = e.end || e.date;
      e.search = [e.title, e.category, e.venue, e.city, e.description, typeOf(e.type).label].concat(e.keywords).join(' ').toLowerCase();
      out.push(e);
    });
    return out.sort(function (a, b) { return a.date < b.date ? -1 : a.date > b.date ? 1 : 0; });
  }

  /* ---------- the widget ---------- */
  function build(host, events, header) {
    var today = todayIST();
    var isOver = function (e) { return e.status === 'concluded' || e.last < today; };
    var upcoming = events.filter(function (e) { return !isOver(e); });

    // Start on today if something is on, else on the next event's first day still to come.
    function firstShownDay(e) { for (var i = 0; i < e.days.length; i++) if (e.days[i] >= today) return e.days[i]; return e.days[e.days.length - 1]; }
    var start = today;
    var onToday = events.some(function (e) { return e.days.indexOf(today) >= 0; });
    if (!onToday && upcoming.length) {
      start = upcoming.map(firstShownDay).sort()[0]; // the nearest event day still to come
    }

    var state = { view: 'month', type: 'all', q: '', sel: start, y: parse(start).getFullYear(), m: parse(start).getMonth() };

    var byDay = {};
    events.forEach(function (e) { e.days.forEach(function (d) { (byDay[d] = byDay[d] || []).push(e); }); });

    function matches(e) {
      if (state.type !== 'all' && e.type !== state.type) return false;
      var q = state.q.trim().toLowerCase();
      if (!q) return true;
      return q.split(/\s+/).every(function (w) { return e.search.indexOf(w) >= 0; });
    }
    function filtering() { return state.type !== 'all' || state.q.trim() !== ''; }

    if (!document.getElementById('fcal-css')) {
      var st = el('style'); st.id = 'fcal-css'; st.textContent = CSS; document.head.appendChild(st);
    }

    var root = el('section', 'fcal');
    root.setAttribute('aria-label', 'FSIA event calendar');
    var wrap = el('div', 'fcal-w');
    root.appendChild(wrap);

    // Heading (CMS calendar header text, when set)
    var head = el('div', 'fcal-head');
    var badge = el('span', 'fcal-badge');
    badge.appendChild(el('i'));
    badge.appendChild(el('span', '', str(header.badge) || 'Official Season Schedule'));
    head.appendChild(badge);
    head.appendChild(el('h2', '', str(header.title) || 'National Pageant & Awards Calendar'));
    if (str(header.subtitle)) head.appendChild(el('p', '', header.subtitle));
    wrap.appendChild(head);

    // Toolbar: view switch, type chips, search
    var bar = el('div', 'fcal-card fcal-bar');
    var views = el('div', 'fcal-views');
    var vMonth = el('button'); vMonth.type = 'button'; vMonth.appendChild(icon('cal')); vMonth.appendChild(el('span', '', 'Month Calendar'));
    var vAgenda = el('button'); vAgenda.type = 'button'; vAgenda.appendChild(icon('clock'));
    var vAgendaTxt = el('span'); vAgenda.appendChild(vAgendaTxt);
    views.appendChild(vMonth); views.appendChild(vAgenda);
    bar.appendChild(views);
    var chips = el('div', 'fcal-chips');
    var chipBtns = TYPES.map(function (t) {
      var b = el('button', '', t.label); b.type = 'button';
      b.addEventListener('click', function () { state.type = t.value; render(); });
      chips.appendChild(b);
      return b;
    });
    bar.appendChild(chips);
    var sWrap = el('label', 'fcal-search');
    sWrap.appendChild(icon('search'));
    var sIn = el('input'); sIn.type = 'search'; sIn.placeholder = 'Search city, award, pageant'; sIn.setAttribute('aria-label', 'Search events');
    sIn.addEventListener('input', function () { state.q = sIn.value; render(); });
    sWrap.appendChild(sIn);
    bar.appendChild(sWrap);
    wrap.appendChild(bar);
    vMonth.addEventListener('click', function () { state.view = 'month'; render(); });
    vAgenda.addEventListener('click', function () { state.view = 'agenda'; render(); });

    var body = el('div');
    wrap.appendChild(body);

    function metaRow(name, text) {
      var d = el('div'); d.appendChild(icon(name)); d.appendChild(el('span', '', text)); return d;
    }
    function meta(e, withDate) {
      var m = el('div', 'fcal-meta');
      if (withDate) {
        var dt = longDate(e.date) + (e.end ? ' – ' + longDate(e.end) : '');
        m.appendChild(metaRow('cal', dt));
      }
      if (e.time) m.appendChild(metaRow('clock', e.time));
      var place = [e.venue, e.city].filter(Boolean).join(', ');
      if (place) m.appendChild(metaRow('pin', place));
      return m;
    }
    function cta(e) {
      if (!e.ctaUrl) return null;
      var a = el('a', 'fcal-cta');
      a.href = e.ctaUrl;
      if (/^https?:/i.test(e.ctaUrl) && e.ctaUrl.indexOf(location.host) < 0) { a.target = '_blank'; a.rel = 'noopener'; }
      a.appendChild(el('span', '', e.ctaText || 'Register Now'));
      a.appendChild(icon('arrow'));
      return a;
    }
    function statusText(e) { return isOver(e) ? 'Concluded' : (STATUS[e.status] || 'Upcoming'); }

    function eventCard(e) {
      var c = el('article', 'fcal-ev');
      if (e.image) {
        var pic = el('div', 'fcal-pic');
        var img = el('img'); img.src = e.image; img.alt = e.title; img.loading = 'lazy';
        img.addEventListener('error', function () { pic.parentNode && pic.parentNode.removeChild(pic); });
        pic.appendChild(img);
        var cap = el('div', 'fcal-pic-cap');
        cap.appendChild(el('span', '', e.category || typeOf(e.type).label));
        cap.appendChild(el('span', 'fcal-st', statusText(e)));
        pic.appendChild(cap);
        c.appendChild(pic);
      } else {
        var p = el('span', 'fcal-pill', typeOf(e.type).label + ' • ' + statusText(e));
        p.style.background = typeOf(e.type).soft;
        c.appendChild(p);
      }
      c.appendChild(el('h4', '', e.title));
      c.appendChild(meta(e, !!e.end));
      if (e.description) c.appendChild(el('p', 'fcal-desc', e.description));
      if (e.eligibility) c.appendChild(el('div', 'fcal-elig', 'Eligibility: ' + e.eligibility));
      if (e.highlights.length) {
        var ul = el('ul', 'fcal-hl');
        e.highlights.slice(0, 6).forEach(function (h) { ul.appendChild(el('li', '', h)); });
        c.appendChild(ul);
      }
      var b = cta(e); if (b) c.appendChild(b);
      return c;
    }

    function jumpTo(day) {
      state.sel = day; state.y = parse(day).getFullYear(); state.m = parse(day).getMonth(); state.view = 'month'; render();
    }
    function nextEventDay(after) {
      var best = '';
      events.forEach(function (e) {
        if (!matches(e)) return;
        e.days.forEach(function (d) { if (d > after && (!best || d < best)) best = d; });
      });
      return best;
    }

    function renderMonth() {
      var grid = el('div', 'fcal-grid');

      // Calendar
      var cal = el('div', 'fcal-card fcal-cal');
      var top = el('div', 'fcal-top');
      var tl = el('div');
      tl.appendChild(el('h3', '', MONTHS[state.m] + ' ' + state.y));
      tl.appendChild(el('p', '', 'Click any highlighted day to inspect scheduled official activities'));
      top.appendChild(tl);
      var nav = el('div', 'fcal-nav');
      var peak = el('button', 'fcal-peak', 'Season Peak'); peak.type = 'button'; peak.title = 'Jump to the next featured event';
      peak.addEventListener('click', function () {
        var pool = upcoming.filter(function (e) { return e.star; });
        if (!pool.length) pool = upcoming.length ? upcoming : events;
        if (pool.length) jumpTo(pool.map(firstShownDay).sort()[0]);
      });
      var prev = el('button', 'fcal-arrow'); prev.type = 'button'; prev.setAttribute('aria-label', 'Previous month'); prev.appendChild(icon('left'));
      var next = el('button', 'fcal-arrow'); next.type = 'button'; next.setAttribute('aria-label', 'Next month'); next.appendChild(icon('right'));
      prev.addEventListener('click', function () { state.m--; if (state.m < 0) { state.m = 11; state.y--; } render(); });
      next.addEventListener('click', function () { state.m++; if (state.m > 11) { state.m = 0; state.y++; } render(); });
      if (events.length) nav.appendChild(peak);
      nav.appendChild(prev); nav.appendChild(next);
      top.appendChild(nav);
      cal.appendChild(top);

      var wd = el('div', 'fcal-wd');
      ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'].forEach(function (d) { wd.appendChild(el('div', '', d)); });
      cal.appendChild(wd);

      var days = el('div', 'fcal-days');
      var first = new Date(state.y, state.m, 1).getDay();
      var count = new Date(state.y, state.m + 1, 0).getDate();
      var cells = Math.ceil((first + count) / 7) * 7;
      for (var i = 0; i < cells; i++) {
        var n = i - first + 1;
        var b = el('button', 'fcal-day'); b.type = 'button';
        if (n < 1 || n > count) { b.className += ' is-out'; b.tabIndex = -1; b.setAttribute('aria-hidden', 'true'); days.appendChild(b); continue; }
        var day = iso(state.y, state.m, n);
        var list = byDay[day] || [];
        b.appendChild(el('b', '', n));
        if (list.length) {
          b.className += ' has';
          var e0 = list[0];
          var tag = el('span', 'fcal-tag', e0.category || e0.title);
          tag.style.background = typeOf(e0.type).soft;
          b.appendChild(tag);
          if (list.length > 1) b.appendChild(el('span', 'fcal-more', '+' + (list.length - 1) + ' more'));
          if (list.some(function (e) { return e.star; })) { var s = el('span', 'fcal-star', '★'); s.setAttribute('aria-hidden', 'true'); b.appendChild(s); }
          if (filtering()) b.className += list.some(matches) ? ' is-hit' : ' is-dim';
        } else if (filtering()) {
          b.className += ' is-dim';
        }
        if (day === today) b.className += ' is-today';
        if (day === state.sel) { b.className += ' is-sel'; b.setAttribute('aria-current', 'date'); }
        b.setAttribute('aria-label', longDate(day) + (list.length ? ', ' + list.length + ' event' + (list.length > 1 ? 's' : '') : ''));
        (function (d) { b.addEventListener('click', function () { state.sel = d; render(); }); })(day);
        days.appendChild(b);
      }
      cal.appendChild(days);

      var lg = el('div', 'fcal-legend');
      TYPES.slice(1).forEach(function (t) {
        var s = el('span'); var dot = el('i'); dot.style.background = t.soft; s.appendChild(dot); s.appendChild(document.createTextNode(t.label)); lg.appendChild(s);
      });
      var st = el('span', '', '★ Featured'); st.style.color = '#7E591B'; lg.appendChild(st);
      cal.appendChild(lg);
      grid.appendChild(cal);

      // Day inspector
      var ins = el('div', 'fcal-card fcal-ins');
      ins.setAttribute('aria-live', 'polite');
      ins.appendChild(el('div', 'fcal-eye', 'Event Day Inspector'));
      ins.appendChild(el('h3', '', longDate(state.sel)));
      var onDay = (byDay[state.sel] || []).filter(matches);
      ins.appendChild(el('p', 'fcal-cnt', onDay.length ? onDay.length + ' official event' + (onDay.length > 1 ? 's' : '') + ' scheduled for this date.' : 'No scheduled official sessions on this selected date.'));
      if (onDay.length) {
        onDay.forEach(function (e) { ins.appendChild(eventCard(e)); });
      } else {
        var empty = el('div', 'fcal-empty');
        empty.appendChild(el('p', '', 'Pick a highlighted day on the calendar to see its event.'));
        var nd = nextEventDay(state.sel);
        if (nd) {
          var nb = el('button', '', 'Next event: ' + longDate(nd)); nb.type = 'button';
          nb.addEventListener('click', function () { jumpTo(nd); });
          empty.appendChild(nb);
        }
        ins.appendChild(empty);
      }
      grid.appendChild(ins);
      return grid;
    }

    function renderAgenda() {
      var box = el('div', 'fcal-card fcal-ag');
      var list = events.filter(matches);
      // Coming events first, finished ones after them.
      list = list.filter(function (e) { return !isOver(e); }).concat(list.filter(isOver));
      if (!list.length) { box.appendChild(el('div', 'fcal-empty', 'No events match this filter.')); return box; }
      list.forEach(function (e) {
        var row = el('div', 'fcal-row' + (isOver(e) ? ' fcal-past' : ''));
        var d = parse(e.date);
        var db = el('div', 'fcal-date');
        db.appendChild(el('b', '', d.getDate()));
        db.appendChild(el('span', '', MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear()));
        row.appendChild(db);
        var rb = el('div', 'fcal-row-b');
        var pill = el('span', 'fcal-pill', typeOf(e.type).label + ' • ' + statusText(e));
        pill.style.background = typeOf(e.type).soft;
        rb.appendChild(pill);
        rb.appendChild(el('h4', '', e.title));
        rb.appendChild(meta(e, !!e.end));
        var b = cta(e); if (b) rb.appendChild(b);
        row.appendChild(rb);
        box.appendChild(row);
      });
      return box;
    }

    function render() {
      vMonth.setAttribute('aria-pressed', state.view === 'month');
      vAgenda.setAttribute('aria-pressed', state.view === 'agenda');
      vAgendaTxt.textContent = 'Agenda Timeline (' + events.filter(matches).length + ')';
      chipBtns.forEach(function (b, i) { b.setAttribute('aria-pressed', TYPES[i].value === state.type); });
      body.textContent = '';
      body.appendChild(state.view === 'month' ? renderMonth() : renderAgenda());
    }

    render();
    host.appendChild(root);
  }

  /* ---------- where and whether to show it ---------- */
  function placeholder() {
    var p = document.getElementById('fsia-event-calendar');
    if (p) return p;
    var path = location.pathname.replace(/\/+$/, '') || '/';
    if (path === '/' || /^\/index\.(php|html)$/i.test(path)) return null; // the homepage has its own calendar
    var form = document.querySelector(FORM_SEL);
    if (!form) return null;
    var anchor = form.closest('section') || form;
    var holder = document.createElement('div');
    holder.id = 'fsia-event-calendar';
    anchor.parentNode.insertBefore(holder, anchor); // top of the page content, just above the form section
    return holder;
  }

  function start() {
    // Find the spot first so pages without a registration form never call the CMS.
    if (!document.getElementById('fsia-event-calendar')) {
      var path = location.pathname.replace(/\/+$/, '') || '/';
      if (path === '/' || /^\/index\.(php|html)$/i.test(path) || !document.querySelector(FORM_SEL)) return;
    }
    fetch(API, { credentials: 'omit' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        var c = data && data.content;
        if (!c || typeof c !== 'object') return;
        var opt = c.formPages || {};
        if (opt.enabled === false) return; // switched off in the CMS
        var events = normalize(c.items);
        if (!events.length) return;
        var today = todayIST();
        var anyLeft = events.some(function (e) { return e.status !== 'concluded' && e.last >= today; });
        if (opt.autoHide !== false && !anyLeft) return; // every event is over
        var host = placeholder();
        if (host) build(host, events, c.header && typeof c.header === 'object' ? c.header : {});
      })
      .catch(function () { /* no calendar is better than a broken one */ });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
