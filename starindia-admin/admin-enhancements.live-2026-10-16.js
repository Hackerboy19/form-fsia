/**
 * Small additions to the compiled admin panel (/admin/) for the new homepage:
 *   - "Homepage Extras" sidebar item → /admin/home-editor.php
 *   - "Homepage SEO" sidebar item → /admin/home-editor.php#seo
 *   - "Preview homepage" button in the top bar
 *   - a "Preview" button next to each Save button, opening the homepage at the section
 *     being edited (fresh content via ?nocache=1)
 *   - a Dashboard (the admin build leaves it empty): profile / inquiry / review
 *     counts, inquiries per day and by status, latest inquiries, and the state
 *     of each homepage section with Edit + Preview
 *   - Inquiries (the admin's "Registrations" page, renamed: its entries come from the
 *     "Direct Booking & Inquiries" form on profiles): readable messages, a status per inquiry (New / Contacted /
 *     Converted / Closed, saved in the admin settings store as "leads:status"), status
 *     filter, search and CSV download
 * Load it from /admin/index.html:
 *   <script src="/admin/admin-enhancements.js" defer></script>
 * The admin app renders and re-renders on its own, so everything is (re)applied after
 * each render and never duplicated.
 */
(function () {
  var SITE = '/';
  var EDITOR = '/admin/home-editor.php';

  function previewUrl(anchor, path) {
    return (path || SITE) + '?nocache=1' + (anchor ? '#' + anchor : '');
  }

  function text(el) { return (el && el.textContent || '').replace(/\s+/g, ' ').trim(); }
  function pageTitle() { return text(document.querySelector('main header h1')); }
  // The admin's "Registrations" page is shown as "Inquiries" (see relabelInquiries)
  function isInquiriesPage() { return /^(Registrations|Inquiries)$/.test(pageTitle()); }
  function slugify(s) { return String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }

  function makeButton(label, href, extraClass) {
    var a = document.createElement('a');
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener';
    a.textContent = label;
    a.className = extraClass || '';
    a.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:12px;border:1px solid #fecdd3;background:#fff;color:#be123c;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap';
    return a;
  }

  // ---- Sidebar: "Homepage Extras" ----
  function addSidebarItem() {
    if (document.getElementById('fsia-home-extras-link')) return;
    var buttons = Array.prototype.slice.call(document.querySelectorAll('aside nav button, nav button'));
    var anchor = buttons.filter(function (b) { return /Site Settings/i.test(text(b)); })[0]
      || buttons.filter(function (b) { return /Layout Manager/i.test(text(b)); })[0];
    if (!anchor) return;
    var link = document.createElement('a');
    link.id = 'fsia-home-extras-link';
    link.href = EDITOR;
    link.className = anchor.className.replace(/\bbg-rose-50\b|\btext-rose-700\b|\bfont-semibold\b/g, '') + ' text-gray-600 hover:bg-rose-50 hover:text-rose-700';
    link.style.textDecoration = 'none';
    var icon = anchor.querySelector('svg');
    if (icon) link.appendChild(icon.cloneNode(true));
    link.appendChild(document.createTextNode(' Homepage Extras'));
    anchor.parentNode.insertBefore(link, anchor.nextSibling);
    // Homepage SEO lives on the same page (its own card)
    var seo = link.cloneNode(true);
    seo.id = 'fsia-home-seo-link';
    seo.href = EDITOR + '#seo';
    seo.lastChild.nodeValue = ' Homepage SEO';
    link.parentNode.insertBefore(seo, link.nextSibling);
  }

  // ---- "Registrations" → "Inquiries" ----
  // Every entry comes from the "Direct Booking & Inquiries" form on profiles, so the admin's
  // Registrations page is labelled "Inquiries" (sidebar, page heading, intro line).
  function swapText(root, from, to) {
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    for (var n = walker.nextNode(); n; n = walker.nextNode()) {
      if (n.nodeValue.trim() === from) n.nodeValue = n.nodeValue.replace(from, to);
    }
  }
  function relabelInquiries() {
    Array.prototype.slice.call(document.querySelectorAll('aside nav button, nav button')).forEach(function (b) {
      if (text(b) === 'Registrations') swapText(b, 'Registrations', 'Inquiries');
    });
    var h1 = document.querySelector('main header h1');
    if (h1 && text(h1) === 'Registrations') {
      swapText(h1, 'Registrations', 'Inquiries');
      document.title = document.title.replace('Registrations', 'Inquiries');
    }
    Array.prototype.slice.call(document.querySelectorAll('main p')).forEach(function (p) {
      if (p.dataset.fsiaIntro !== '1' && /Registration & nomination submissions/i.test(text(p))) {
        p.dataset.fsiaIntro = '1';
        swapText(p, text(p), 'Inquiries sent from the “Direct Booking & Inquiries” form on profiles.');
      }
    });
  }

  // ---- Layout Manager: section names of the new homepage ----
  var LAYOUT_NAMES = {
    'Trending Articles carousel': 'Latest Articles (article cards)',
    'Category cards': 'Profiles carousel + category buttons',
    'Directory insta-scroll': '“Explore the Directory” cards',
    'Bottom “Apply” CTA': 'Bottom ad banner'
  };
  function relabelLayout() {
    if (pageTitle() !== 'Layout Manager') return;
    var main = document.querySelector('main');
    if (!main) return;
    Object.keys(LAYOUT_NAMES).forEach(function (from) { swapText(main, from, LAYOUT_NAMES[from]); });
    if (document.getElementById('fsia-layout-note')) return;
    var intro = Array.prototype.slice.call(main.querySelectorAll('p')).filter(function (p) { return /Drag sections to reorder/i.test(text(p)); })[0];
    if (!intro) return;
    var note = document.createElement('p');
    note.id = 'fsia-layout-note';
    note.style.cssText = 'margin:6px 0 0;font-size:12px;color:#9f1239';
    note.textContent = 'The header, hero with search and the top ad banner always stay at the top; these sections follow below them. Empty sections (e.g. no Explore the Directory cards) are not shown.';
    var row = intro.parentNode; // intro sits in a row with the Reset / Save buttons
    row.parentNode.insertBefore(note, row.nextSibling);
    note.style.margin = '-6px 0 12px';
  }

  // ---- Top bar: "Preview homepage" ----
  function addTopPreview() {
    if (document.getElementById('fsia-preview-home')) return;
    var logout = Array.prototype.slice.call(document.querySelectorAll('main header button')).filter(function (b) { return text(b) === 'Logout'; })[0];
    if (!logout) return;
    var btn = makeButton('↗ Preview homepage', previewUrl(''));
    btn.id = 'fsia-preview-home';
    logout.parentNode.insertBefore(btn, logout);
  }

  // Which part of the homepage a Save button belongs to
  function targetFor(saveButton) {
    var title = pageTitle();
    var card = saveButton.closest('.rounded-2xl, .rounded-xl, form, section') || saveButton.parentElement;
    var cardText = text(card).slice(0, 200);
    if (/Homepage Sections/i.test(title)) {
      if (/Footer/i.test(cardText)) return previewUrl('footer');
      if (/Explore the Directory/i.test(cardText)) return previewUrl('fsia-directory');
      if (/Nominate/i.test(cardText)) return null; // not part of the homepage
      return previewUrl('');
    }
    if (/Page Content/i.test(title)) {
      var main = text(document.querySelector('main')).slice(0, 400);
      var editing = main.match(/Editing\s*[:\-–]?\s*([A-Za-z &]+)/i);
      var label = editing ? editing[1].trim() : '';
      if (label && !/^home$/i.test(label)) return previewUrl('', '/' + slugify(label));
      return previewUrl('fsia-hero');
    }
    if (/Navigation/i.test(title)) return previewUrl('site-header');
    if (/Layout Manager/i.test(title)) return previewUrl('');
    if (/Profiles|Users|Roles|Registrations|Inquiries|Reviews|Categories/i.test(title)) return null;
    return previewUrl('');
  }

  // ---- "Preview" next to every Save button ----
  function addSavePreviews() {
    Array.prototype.forEach.call(document.querySelectorAll('main button'), function (b) {
      if (text(b) !== 'Save' || b.dataset.fsiaPreview) return;
      var href = targetFor(b);
      b.dataset.fsiaPreview = '1';
      if (!href) return;
      var btn = makeButton('↗ Preview', href, 'fsia-preview-btn');
      btn.title = 'Save first, then preview the live homepage at this section';
      btn.style.marginRight = '6px';
      b.parentNode.insertBefore(btn, b);
    });
  }

  // ---- Dashboard shortcuts ----
  function clickSidebar(label) {
    var names = label === 'Registrations' ? ['Registrations', 'Inquiries'] : [label];
    var b = Array.prototype.slice.call(document.querySelectorAll('aside nav button, nav button')).filter(function (x) { return names.indexOf(text(x)) !== -1; })[0];
    if (b) b.click();
  }
  // ---- Dashboard ----
  // Small DOM helper: el('div', 'css', [children]) — text is always set as text, never HTML
  function el(tag, css, children) {
    var node = document.createElement(tag);
    if (css) node.style.cssText = css;
    (children || []).forEach(function (c) {
      if (c == null) return;
      node.appendChild(typeof c === 'string' || typeof c === 'number' ? document.createTextNode(String(c)) : c);
    });
    return node;
  }
  var CARD = 'background:#fff;border:1px solid #fecdd3;border-radius:16px;padding:16px';
  var MUTED = '#6b7280';
  var INK = '#111827';
  var dashData = { at: 0, data: null, loading: false };

  function loadDashboard(done) {
    if (dashData.data && Date.now() - dashData.at < 60000) return done(dashData.data);
    if (dashData.loading) return;
    dashData.loading = true;
    var pub = function (key) {
      return fetch(API + '/api/settings/' + key).then(function (r) { return r.ok ? r.json() : {}; }).catch(function () { return {}; });
    };
    Promise.all([
      api('/api/leads').catch(function () { return null; }),
      api('/api/settings/' + STATUS_KEY).catch(function () { return {}; }),
      api('/api/profiles?page=1&limit=1').catch(function () { return null; }),
      api('/api/reviews?status=pending').catch(function () { return null; }),
      pub('page:home'), pub('home:layout'), pub('home:directory'), pub('home:extras'),
    ]).then(function (r) {
      dashData.data = {
        leads: Array.isArray(r[0]) ? r[0] : null,
        statuses: r[1] && typeof r[1] === 'object' ? r[1] : {},
        profilesTotal: r[2] && typeof r[2].total === 'number' ? r[2].total : (Array.isArray(r[2]) ? r[2].length : null),
        pendingReviews: Array.isArray(r[3]) ? r[3].length : (r[3] && Array.isArray(r[3].items) ? r[3].items.length : null),
        home: r[4] || {}, layout: r[5] || {}, directory: r[6] || {}, extras: r[7] || {},
      };
      dashData.at = Date.now();
      leadsState.statuses = dashData.data.statuses;
    }).finally(function () {
      dashData.loading = false;
      if (dashData.data) done(dashData.data);
    });
  }

  function statTile(label, value, sub) {
    return el('div', CARD, [
      el('div', 'font-size:12px;color:' + MUTED + ';font-weight:600', [label]),
      el('div', 'font-size:30px;font-weight:800;color:' + INK + ';line-height:1.2;margin-top:4px;font-variant-numeric:tabular-nums', [value == null ? '—' : String(value)]),
      sub ? el('div', 'font-size:12px;color:' + MUTED + ';margin-top:2px', [sub]) : null,
    ]);
  }

  function dayKey(d) { return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate(); }

  // Inquiries per day, last 14 days: one series, thin rounded bars, hover tooltip, table view
  function leadsChart(leads) {
    var days = [];
    var today = new Date(); today.setHours(0, 0, 0, 0);
    for (var i = 13; i >= 0; i--) { var d = new Date(today); d.setDate(today.getDate() - i); days.push({ date: d, key: dayKey(d), count: 0 }); }
    var byKey = {}; days.forEach(function (x) { byKey[x.key] = x; });
    leads.forEach(function (l) { var t = new Date(l.at); if (!isNaN(+t)) { var k = byKey[dayKey(t)]; if (k) k.count++; } });
    var max = Math.max(1, Math.max.apply(null, days.map(function (x) { return x.count; })));
    var total = days.reduce(function (n, x) { return n + x.count; }, 0);
    var W = 560, H = 160, padL = 28, padB = 22, padT = 8, plotW = W - padL - 4, plotH = H - padB - padT;
    var slot = plotW / days.length, barW = Math.max(6, Math.min(22, slot - 6));
    var NS = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
    svg.setAttribute('width', '100%');
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Inquiries per day, last 14 days: ' + total + ' total');
    svg.style.display = 'block';
    function svgEl(name, attrs) { var n = document.createElementNS(NS, name); Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); }); return n; }
    // recessive gridlines + y labels (0, max)
    [0, max].forEach(function (v) {
      var y = padT + plotH - (v / max) * plotH;
      svg.appendChild(svgEl('line', { x1: padL, x2: W - 4, y1: y, y2: y, stroke: '#f1e4e8', 'stroke-width': 1 }));
      var t = svgEl('text', { x: padL - 6, y: y + 4, 'text-anchor': 'end', 'font-size': 10, fill: MUTED }); t.textContent = v; svg.appendChild(t);
    });
    var tip = el('div', 'position:absolute;pointer-events:none;background:#111827;color:#fff;font-size:12px;padding:6px 8px;border-radius:8px;white-space:nowrap;display:none;transform:translate(-50%,-110%)');
    days.forEach(function (x, i) {
      var cx = padL + slot * i + slot / 2;
      var h = (x.count / max) * plotH;
      var label = x.date.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
      if (x.count > 0) {
        // 4px rounded top, square at the baseline
        var y = padT + plotH - h, r = Math.min(4, h, barW / 2), xl = cx - barW / 2;
        svg.appendChild(svgEl('path', { d: 'M' + xl + ',' + (padT + plotH) + 'V' + (y + r) + 'Q' + xl + ',' + y + ' ' + (xl + r) + ',' + y + 'H' + (xl + barW - r) + 'Q' + (xl + barW) + ',' + y + ' ' + (xl + barW) + ',' + (y + r) + 'V' + (padT + plotH) + 'Z', fill: '#2a78d6' }));
      }
      if (i % 2 === 1 || days.length <= 7) {
        var t = svgEl('text', { x: cx, y: H - 6, 'text-anchor': 'middle', 'font-size': 10, fill: MUTED }); t.textContent = label; svg.appendChild(t);
      }
      // full-column hit target, bigger than the bar
      var hit = svgEl('rect', { x: padL + slot * i, y: padT, width: slot, height: plotH, fill: 'transparent' });
      hit.style.cursor = 'default';
      hit.addEventListener('mouseenter', function () {
        tip.textContent = label + ': ' + x.count + ' inquir' + (x.count === 1 ? 'y' : 'ies');
        tip.style.left = (cx / W * 100) + '%';
        tip.style.top = ((padT + plotH - h) / H * 100) + '%';
        tip.style.display = 'block';
      });
      hit.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
      svg.appendChild(hit);
    });
    svg.appendChild(svgEl('line', { x1: padL, x2: W - 4, y1: padT + plotH, y2: padT + plotH, stroke: '#e5d5da', 'stroke-width': 1 }));
    var table = el('table', 'width:100%;font-size:12px;border-collapse:collapse;margin-top:6px', days.slice().reverse().map(function (x) {
      return el('tr', '', [el('td', 'padding:2px 0;color:' + MUTED, [x.date.toLocaleDateString()]), el('td', 'padding:2px 0;text-align:right;color:' + INK, [String(x.count)])]);
    }));
    var details = el('details', 'margin-top:6px', [el('summary', 'font-size:12px;color:' + MUTED + ';cursor:pointer', ['View as table']), table]);
    return el('div', CARD, [
      el('div', 'font-weight:700;color:' + INK, ['Inquiries, last 14 days']),
      el('div', 'font-size:12px;color:' + MUTED + ';margin-bottom:8px', [total + ' in total']),
      el('div', 'position:relative', [svg, tip]),
      details,
    ]);
  }

  function statusBreakdown(leads) {
    var counts = {}; STATUSES.forEach(function (st) { counts[st[0]] = 0; });
    leads.forEach(function (l) { counts[statusOf(l)] = (counts[statusOf(l)] || 0) + 1; });
    var total = leads.length || 1;
    var bar = el('div', 'display:flex;gap:2px;height:12px;border-radius:999px;overflow:hidden;background:#f3f4f6;margin:10px 0 12px');
    STATUSES.forEach(function (st) { if (counts[st[0]]) bar.appendChild(el('div', 'background:' + st[2] + ';width:' + (counts[st[0]] / total * 100) + '%')); });
    var list = el('div', 'display:grid;gap:6px', STATUSES.map(function (st) {
      return el('div', 'display:flex;align-items:center;gap:8px;font-size:13px;color:' + INK, [
        el('span', 'width:10px;height:10px;border-radius:3px;background:' + st[2] + ';flex:none'),
        el('span', 'flex:1', [st[1]]),
        el('strong', 'font-variant-numeric:tabular-nums', [String(counts[st[0]])]),
      ]);
    }));
    return el('div', CARD, [el('div', 'font-weight:700;color:' + INK, ['Inquiries by status']), bar, list]);
  }

  function linkButton(label, onClick, href) {
    var b = el(href ? 'a' : 'button', 'display:inline-flex;align-items:center;padding:5px 10px;border-radius:10px;border:1px solid #fecdd3;background:#fff;color:#be123c;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;font-family:inherit', [label]);
    if (href) { b.href = href; if (/^\/\?/.test(href)) { b.target = '_blank'; b.rel = 'noopener'; } }
    else { b.type = 'button'; b.onclick = onClick; }
    return b;
  }

  function recentLeads(leads) {
    var rows = leads.slice().sort(function (a, b) { return new Date(b.at) - new Date(a.at); }).slice(0, 5);
    var body = rows.length ? rows.map(function (l) {
      var st = STATUSES.filter(function (x) { return x[0] === statusOf(l); })[0];
      return el('div', 'display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;padding:8px 0;border-top:1px solid #fde7ec;font-size:13px', [
        el('strong', 'color:' + INK + ';min-width:120px', [l.name || '—']),
        el('span', 'color:' + MUTED + ';flex:1;min-width:140px', [l.category || '']),
        el('span', 'display:inline-flex;align-items:center;gap:5px;font-size:12px;color:' + INK, [el('span', 'width:8px;height:8px;border-radius:2px;background:' + st[2]), st[1]]),
        el('span', 'color:' + MUTED + ';font-size:12px', [l.at ? new Date(l.at).toLocaleString() : '']),
      ]);
    }) : [el('div', 'font-size:13px;color:' + MUTED + ';padding:8px 0', ['No inquiries yet.'])];
    return el('div', CARD, [el('div', 'display:flex;justify-content:space-between;align-items:center;margin-bottom:4px', [
      el('div', 'font-weight:700;color:' + INK, ['Latest inquiries']),
      linkButton('View all', function () { clickSidebar('Registrations'); }),
    ])].concat(body));
  }

  function homepageHealth(d) {
    var home = d.home || {}, extras = d.extras || {}, dir = d.directory || {}, layout = d.layout || {};
    var ad = function (a) { return a && (a.headline || a.bannerImageUrl) ? (a.enabled === false ? 'Hidden' : 'Custom') : 'Default text'; };
    var items = [
      ['Hero text & banner', home.heroTitle ? '“' + home.heroTitle + (home.heroHighlight ? ' ' + home.heroHighlight : '') + '”' + (home.heroBanner ? ' · banner set' : ' · no banner') : 'Default text', function () { clickSidebar('Page Content'); }, 'fsia-hero'],
      ['Article carousel', (Array.isArray(home.articles) ? home.articles.length : 0) + ' article(s)', function () { clickSidebar('Page Content'); }, 'fsia-articles'],
      ['Explore the Directory', (Array.isArray(dir.items) && dir.items.length ? dir.items.length + ' card(s)' : 'Empty — section hidden'), function () { clickSidebar('Homepage Sections'); }, 'fsia-directory'],
      ['Section layout', Array.isArray(layout.sections) && layout.sections.length ? 'Custom order' : 'Default order', function () { clickSidebar('Layout Manager'); }, ''],
      ['Top ad banner', ad(extras.adTop), EDITOR, 'premium-ad-slot'],
      ['Bottom ad banner', ad(extras.adBottom), EDITOR, 'fsia-bottom-ad'],
      ['Popular searches', Array.isArray(extras.popular) && extras.popular.length ? extras.popular.length + ' button(s)' : 'Default buttons', EDITOR, 'fsia-hero'],
      ['Pinned profiles', extras.pinnedProfiles ? String(extras.pinnedProfiles).split(/[\s,]+/).filter(Boolean).length + ' pinned' : 'None — default order', EDITOR, 'featured-carousel-section'],
    ];
    return el('div', CARD, [el('div', 'font-weight:700;color:' + INK + ';margin-bottom:4px', ['Homepage content'])].concat(items.map(function (it) {
      return el('div', 'display:flex;flex-wrap:wrap;align-items:center;gap:6px 10px;padding:8px 0;border-top:1px solid #fde7ec;font-size:13px', [
        el('span', 'color:' + INK + ';font-weight:600;min-width:150px', [it[0]]),
        el('span', 'color:' + MUTED + ';flex:1;min-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap', [it[1]]),
        typeof it[2] === 'string' ? linkButton('Edit', null, it[2]) : linkButton('Edit', it[2]),
        linkButton('↗ Preview', null, previewUrl(it[3])),
      ]);
    })));
  }

  function renderDashboard(wrap, d) {
    wrap.textContent = '';
    var leads = d.leads || [];
    var weekAgo = Date.now() - 7 * 864e5, twoWeeksAgo = Date.now() - 14 * 864e5;
    var thisWeek = leads.filter(function (l) { return +new Date(l.at) >= weekAgo; }).length;
    var lastWeek = leads.filter(function (l) { var t = +new Date(l.at); return t >= twoWeeksAgo && t < weekAgo; }).length;
    var newCount = leads.filter(function (l) { return statusOf(l) === 'new'; }).length;
    var head = el('div', 'display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px', [
      el('div', '', [el('div', 'font-size:20px;font-weight:800;color:' + INK, ['Welcome back']), el('div', 'font-size:13px;color:' + MUTED, [new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })])]),
      el('div', 'display:flex;gap:8px', [linkButton('↻ Refresh', function () { dashData.at = 0; addDashboard(true); }), linkButton('↗ Preview homepage', null, previewUrl(''))]),
    ]);
    var tiles = el('div', 'display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px', [
      statTile('Profiles', d.profilesTotal),
      statTile('Inquiries', d.leads ? leads.length : null, 'all time'),
      statTile('New — to follow up', d.leads ? newCount : null, 'status “New”'),
      statTile('This week', d.leads ? thisWeek : null, d.leads ? (thisWeek - lastWeek >= 0 ? '+' : '') + (thisWeek - lastWeek) + ' vs previous week' : ''),
      statTile('Pending reviews', d.pendingReviews),
    ]);
    var charts = el('div', 'display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px', d.leads ? [leadsChart(leads), statusBreakdown(leads)] : []);
    var lower = el('div', 'display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px', [d.leads ? recentLeads(leads) : null, homepageHealth(d)]);
    [head, tiles, charts, lower].forEach(function (n) { wrap.appendChild(n); });
  }

  function addDashboard(forceReload) {
    var existing = document.getElementById('fsia-dashboard');
    if (pageTitle() !== 'Dashboard') {
      // React leaves foreign nodes in place when switching sections
      if (existing) existing.remove();
      return;
    }
    var content = document.querySelector('main > div:last-child');
    if (!content || (existing && !forceReload)) return;
    var wrap = existing || el('div', 'display:grid;gap:14px;max-width:1100px');
    wrap.id = 'fsia-dashboard';
    if (!existing) {
      wrap.appendChild(el('div', 'font-size:13px;color:' + MUTED + ';padding:20px', ['Loading dashboard…']));
      content.appendChild(wrap);
    }
    loadDashboard(function (d) { if (document.body.contains(wrap)) renderDashboard(wrap, d); });
  }

  // ---- Inquiries: status, filter, search, CSV ----
  var API = 'https://admin.fsia.in';
  var STATUS_KEY = 'leads:status';
  // Validated categorical palette (dataviz reference slots 1, 2, 3, 7); always shown with a text label
  var STATUSES = [
    ['new', 'New', '#2a78d6', '#eef5fd'],
    ['contacted', 'Contacted', '#eb6834', '#fdf0ea'],
    ['converted', 'Converted', '#1baf7a', '#e9f8f2'],
    ['closed', 'Closed', '#4a3aa7', '#efedf8'],
  ];
  var leadsState = { leads: null, statuses: null, loading: false, filter: 'all', query: '' };

  function token() { try { return localStorage.getItem('fsia_admin_jwt') || ''; } catch (e) { return ''; } }
  function api(path, opts) {
    opts = opts || {};
    opts.headers = Object.assign({ Authorization: 'Bearer ' + token() }, opts.headers || {});
    return fetch(API + path, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (b) {
        if (!r.ok) throw new Error(b.error || ('HTTP ' + r.status));
        return b;
      });
    });
  }
  function leadKey(l) { return [l.at || '', l.email || l.phone || '', l.name || ''].join('|'); }
  function statusOf(l) {
    var s = leadsState.statuses && leadsState.statuses[leadKey(l)];
    return (s && s.status) || 'new';
  }
  function loadLeads(force) {
    if (leadsState.loading || (leadsState.leads && !force)) return;
    leadsState.loading = true;
    Promise.all([api('/api/leads'), api('/api/settings/' + STATUS_KEY).catch(function () { return {}; })])
      .then(function (res) {
        leadsState.leads = Array.isArray(res[0]) ? res[0] : [];
        leadsState.statuses = res[1] && typeof res[1] === 'object' ? res[1] : {};
      })
      .catch(function () { leadsState.leads = leadsState.leads || []; leadsState.statuses = leadsState.statuses || {}; })
      .finally(function () { leadsState.loading = false; decorateLeads(true); });
  }
  function saveStatus(lead, status, select) {
    var key = leadKey(lead);
    // Re-read before writing so two admins don't overwrite each other's changes
    api('/api/settings/' + STATUS_KEY).catch(function () { return {}; }).then(function (latest) {
      var next = Object.assign({}, latest && typeof latest === 'object' ? latest : {});
      next[key] = { status: status, updatedAt: new Date().toISOString() };
      return api('/api/settings/' + STATUS_KEY, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(next),
      }).then(function () { leadsState.statuses = next; });
    }).then(function () {
      paintSelect(select, status);
      applyLeadFilter();
    }).catch(function (e) {
      alert('Could not save status: ' + e.message);
      paintSelect(select, statusOf(lead));
    });
  }
  function paintSelect(select, status) {
    var st = STATUSES.filter(function (x) { return x[0] === status; })[0] || STATUSES[0];
    select.value = st[0];
    select.style.color = '#111827';
    select.style.background = st[3];
    select.style.borderColor = st[2];
  }
  // Message text with clickable links, one line per line (built from text nodes only)
  function renderMessage(p, message) {
    p.textContent = '';
    p.style.whiteSpace = 'pre-line';
    String(message || '').split(/(https?:\/\/[^\s]+)/g).forEach(function (part) {
      if (/^https?:\/\//.test(part)) {
        var a = document.createElement('a');
        a.href = part; a.target = '_blank'; a.rel = 'noopener'; a.textContent = part;
        a.style.color = '#be123c';
        p.appendChild(a);
      } else {
        p.appendChild(document.createTextNode(part));
      }
    });
  }
  function csvCell(v) {
    v = String(v == null ? '' : v);
    if (/^[=+\-@]/.test(v)) v = "'" + v; // keep spreadsheets from running formulas
    return '"' + v.replace(/"/g, '""') + '"';
  }
  function downloadCsv() {
    var rows = visibleLeads();
    var lines = [['Date', 'Name', 'Phone', 'Email', 'City', 'Category', 'Status', 'Message'].map(csvCell).join(',')];
    rows.forEach(function (l) {
      lines.push([l.at ? new Date(l.at).toLocaleString() : '', l.name, l.phone, l.email, l.city, l.category, statusOf(l), l.message].map(csvCell).join(','));
    });
    var blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'inquiries-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  }
  function matches(l) {
    if (leadsState.filter !== 'all' && statusOf(l) !== leadsState.filter) return false;
    var q = leadsState.query.toLowerCase();
    if (!q) return true;
    return [l.name, l.phone, l.email, l.city, l.category, l.message].join(' ').toLowerCase().indexOf(q) !== -1;
  }
  function visibleLeads() { return (leadsState.leads || []).filter(matches); }
  function leadCards() {
    var list = document.querySelector('main .space-y-3');
    return list ? Array.prototype.slice.call(list.children) : [];
  }
  function applyLeadFilter() {
    var cards = leadCards();
    cards.forEach(function (card, i) {
      var lead = leadsState.leads && leadsState.leads[i];
      card.style.display = !lead || matches(lead) ? '' : 'none';
    });
    var count = document.getElementById('fsia-leads-count');
    if (count && leadsState.leads) count.textContent = visibleLeads().length + ' of ' + leadsState.leads.length;
  }
  function addLeadsToolbar() {
    if (document.getElementById('fsia-leads-toolbar')) return;
    var intro = Array.prototype.slice.call(document.querySelectorAll('main p')).filter(function (p) { return p.dataset.fsiaIntro === '1' || /Registration & nomination submissions/i.test(text(p)); })[0];
    if (!intro) return;
    var bar = document.createElement('div');
    bar.id = 'fsia-leads-toolbar';
    bar.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 12px';
    var filter = document.createElement('select');
    filter.style.cssText = 'padding:7px 10px;border:1px solid #fecdd3;border-radius:10px;font-size:12px;background:#fff';
    [['all', 'All statuses']].concat(STATUSES).forEach(function (st) {
      var o = document.createElement('option'); o.value = st[0]; o.textContent = st[1]; filter.appendChild(o);
    });
    filter.value = leadsState.filter;
    filter.onchange = function () { leadsState.filter = filter.value; applyLeadFilter(); };
    var search = document.createElement('input');
    search.type = 'search'; search.placeholder = 'Search name, phone, email, message…'; search.value = leadsState.query;
    search.style.cssText = 'flex:1;min-width:180px;padding:7px 10px;border:1px solid #fecdd3;border-radius:10px;font-size:12px';
    search.oninput = function () { leadsState.query = search.value.trim(); applyLeadFilter(); };
    var dl = document.createElement('button');
    dl.type = 'button'; dl.textContent = '⬇ Download CSV';
    dl.style.cssText = 'padding:7px 12px;border-radius:10px;border:1px solid #be123c;background:#be123c;color:#fff;font-size:12px;font-weight:700;cursor:pointer';
    dl.onclick = downloadCsv;
    var count = document.createElement('span');
    count.id = 'fsia-leads-count';
    count.style.cssText = 'font-size:12px;color:#6b7280';
    bar.append(filter, search, dl, count);
    var header = intro.parentElement;
    header.parentElement.insertBefore(bar, header.nextSibling);
    // The panel's own Refresh button reloads the list; reload our data with it
    var refresh = Array.prototype.slice.call(header.querySelectorAll('button')).filter(function (b) { return /Refresh/.test(text(b)); })[0];
    if (refresh && !refresh.dataset.fsia) {
      refresh.dataset.fsia = '1';
      refresh.addEventListener('click', function () { setTimeout(function () { loadLeads(true); }, 800); });
    }
  }
  function decorateLeads(force) {
    if (!isInquiriesPage()) { leadsState.leads = null; return; }
    addLeadsToolbar();
    if (!leadsState.leads) { loadLeads(false); return; }
    leadCards().forEach(function (card, i) {
      var lead = leadsState.leads[i];
      if (!lead) return;
      var name = card.querySelector('strong');
      if (!name || text(name) !== String(lead.name || '').trim()) return; // list changed; wait for reload
      if (card.dataset.fsiaLead === leadKey(lead) && !force) return;
      card.dataset.fsiaLead = leadKey(lead);
      var msg = card.querySelector('p');
      if (msg && lead.message) renderMessage(msg, lead.message);
      var select = card.querySelector('select.fsia-lead-status');
      if (!select) {
        select = document.createElement('select');
        select.className = 'fsia-lead-status';
        select.style.cssText = 'padding:4px 8px;border:1px solid;border-radius:999px;font-size:11px;font-weight:700;cursor:pointer';
        STATUSES.forEach(function (st) { var o = document.createElement('option'); o.value = st[0]; o.textContent = st[1]; select.appendChild(o); });
        card.appendChild(select);
      }
      select.onchange = function () { saveStatus(lead, select.value, select); };
      paintSelect(select, statusOf(lead));
    });
    applyLeadFilter();
  }

  function apply() {
    try { relabelInquiries(); relabelLayout(); addSidebarItem(); addTopPreview(); addSavePreviews(); addDashboard(false); decorateLeads(false); } catch (e) { /* never break the admin app */ }
  }

  var scheduled = false;
  var observer = new MutationObserver(function () {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(function () { scheduled = false; apply(); });
  });
  function start() {
    apply();
    observer.observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
