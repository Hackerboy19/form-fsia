(function () {
  'use strict';
  var F = window.FSIA, h = F.h, svg = F.svg, icon = F.icon, DAY = 86400000;
  var range = 30;

  function startOfDay(ts) { var d = new Date(ts); d.setHours(0, 0, 0, 0); return d.getTime(); }
  function pct(cur, prev) {
    if (!prev) return cur ? { text: 'new', dir: 'up' } : { text: '0%', dir: 'flat' };
    var p = Math.round((cur - prev) / prev * 100);
    return { text: (p > 0 ? '+' : '') + p + '%', dir: p > 0 ? 'up' : p < 0 ? 'down' : 'flat' };
  }
  function niceMax(v) {
    if (v <= 4) return 4;
    var mag = Math.pow(10, Math.floor(Math.log(v) / Math.LN10)), n = v / mag;
    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * mag;
  }

  /* KPI numbers count up from zero once, keeping their ₹ / % formatting. */
  function countUp(scope) {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    F.$$('.kpi-value', scope).forEach(function (el) {
      var text = el.textContent, m = text.match(/[\d,]+/);
      if (!m) return;
      var target = parseInt(m[0].replace(/,/g, ''), 10);
      if (!(target > 0)) return;
      var start = performance.now(), dur = 900;
      (function step(t) {
        var k = Math.min(1, (t - start) / dur), e = 1 - Math.pow(1 - k, 3);
        el.textContent = text.replace(m[0], Math.round(target * e).toLocaleString('en-IN'));
        if (k < 1 && document.contains(el)) requestAnimationFrame(step); else el.textContent = text;
      })(start);
    });
  }

  function kpi(label, value, sub, delta, href, ico) {
    var body = [
      h('div', { class: 'kpi-top' }, h('span', { class: 'kpi-label' }, label), h('span', { class: 'kpi-ico' }, icon(ico, 18))),
      h('div', { class: 'kpi-value' }, value),
      h('div', { class: 'kpi-sub' },
        delta ? h('span', { class: 'delta ' + delta.dir }, delta.text) : null, sub)
    ];
    return href ? h('a', { class: 'card kpi kpi-link', href: href }, body) : h('div', { class: 'card kpi' }, body);
  }

  /* SVG area chart sized to its container; redrawn on resize. */
  function drawChart(host, tip, points) {
    host.querySelectorAll('svg').forEach(function (s) { s.remove(); });
    var W = Math.max(280, host.clientWidth), H = 250;
    var m = { l: 34, r: 10, t: 12, b: 26 };
    var iw = W - m.l - m.r, ih = H - m.t - m.b;
    var max = niceMax(Math.max.apply(null, points.map(function (p) { return p.v; })));
    var x = function (i) { return m.l + (points.length === 1 ? iw / 2 : i / (points.length - 1) * iw); };
    var y = function (v) { return m.t + ih - v / max * ih; };
    var g = svg('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Registrations per day, last ' + range + ' days' });
    var gradId = 'grad-' + range;
    g.append(svg('defs', {}, svg('linearGradient', { id: gradId, x1: 0, y1: 0, x2: 0, y2: 1 },
      svg('stop', { offset: '0%', 'class': 'grad-a' }), svg('stop', { offset: '100%', 'class': 'grad-b' }))));
    var t;
    for (t = 0; t <= 4; t++) {
      var val = max / 4 * t;
      g.append(svg('line', { x1: m.l, x2: W - m.r, y1: y(val), y2: y(val), 'class': 'grid' }));
      g.append(svg('text', { x: m.l - 8, y: y(val) + 4, 'class': 'axis', 'text-anchor': 'end' }, String(Math.round(val))));
    }
    var ticks = Math.min(points.length, W < 480 ? 4 : 7);
    for (t = 0; t < ticks; t++) {
      var idx = Math.round(t / (ticks - 1 || 1) * (points.length - 1));
      g.append(svg('text', { x: x(idx), y: H - 6, 'class': 'axis', 'text-anchor': t === 0 ? 'start' : t === ticks - 1 ? 'end' : 'middle' }, F.fmt.short(points[idx].t)));
    }
    var line = points.map(function (p, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(p.v).toFixed(1); }).join(' ');
    g.append(svg('path', { d: line + ' L' + x(points.length - 1) + ' ' + (m.t + ih) + ' L' + x(0) + ' ' + (m.t + ih) + ' Z', fill: 'url(#' + gradId + ')', 'class': 'area' }));
    g.append(svg('path', { d: line, 'class': 'line', pathLength: 1 }));
    var cross = svg('line', { y1: m.t, y2: m.t + ih, 'class': 'cross', visibility: 'hidden' });
    var dot = svg('circle', { r: 4.5, 'class': 'dot', visibility: 'hidden' });
    g.append(cross, dot);
    function show(e) {
      var rect = g.getBoundingClientRect();
      var px = (e.clientX - rect.left) / rect.width * W;
      var i = Math.max(0, Math.min(points.length - 1, Math.round((px - m.l) / iw * (points.length - 1))));
      var p = points[i];
      cross.setAttribute('x1', x(i)); cross.setAttribute('x2', x(i)); cross.setAttribute('visibility', 'visible');
      dot.setAttribute('cx', x(i)); dot.setAttribute('cy', y(p.v)); dot.setAttribute('visibility', 'visible');
      tip.hidden = false;
      tip.textContent = '';
      tip.append(h('strong', null, String(p.v)), ' registration' + (p.v === 1 ? '' : 's'), h('span', null, F.fmt.date(p.t)));
      var left = Math.min(Math.max(x(i) * (rect.width / W) - tip.offsetWidth / 2, 0), rect.width - tip.offsetWidth);
      tip.style.left = left + 'px';
    }
    g.addEventListener('pointermove', show);
    g.addEventListener('pointerleave', function () { tip.hidden = true; cross.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); });
    host.prepend(g);
  }

  function pagesOnly() {
    var CHECK = ['meta_title', 'descritpion', 'meta_keyword', 'og_title', 'og_description', 'og_image', 'app_fee'];
    var weak = F.data.forms.filter(function (f) { var m = F.store.page(f.id); return CHECK.some(function (k) { return !String(m[k] || '').trim(); }); });
    return h('div', null,
      h('div', { class: 'page-head' }, h('div', null, h('h1', { class: 'page-title' }, 'Hello ' + F.auth.user().name.split(' ')[0]), h('p', { class: 'page-sub' }, F.team.role(F.auth.user().role).desc))),
      h('div', { class: 'kpis three' },
        kpi('Form pages', F.fmt.num(F.data.forms.length), 'Miss, Mrs, business and event forms', null, '#/pages', 'file'),
        kpi('Need attention', F.fmt.num(weak.length), 'missing title, description, image or fee', null, '#/pages', 'alert'),
        kpi('Complete', F.fmt.num(F.data.forms.length - weak.length), 'all search and share fields filled', null, '#/pages', 'check')));
  }

  F.views.dashboard = {
    title: 'Dashboard',
    perm: 'dashboard.view',
    render: function () {
      if (!F.can('registrations.view')) return { el: pagesOnly() };
      var root = h('div', { class: 'view-dashboard' });
      var ro = null, offStore = null;

      function build() {
        var all = F.reg.all(), now = Date.now(), today = startOfDay(now);
        var cur = all.filter(function (g) { return g.createdAt >= now - range * DAY; });
        var prev = all.filter(function (g) { return g.createdAt < now - range * DAY && g.createdAt >= now - 2 * range * DAY; });
        var todayN = all.filter(function (g) { return g.createdAt >= today; }).length;
        var verified = cur.filter(function (g) { return g.verified; }).length;
        var newCount = all.filter(function (g) { return g.status === 'new'; }).length;
        var paid = cur.filter(function (g) { return g.status === 'paid'; }).length;
        var waiting = all.filter(function (g) { return g.payment.status === 'sent'; });
        var waitingSum = waiting.reduce(function (a, g) { return a + (g.payment.amount || 0); }, 0);
        var collected = all.filter(function (g) { return g.payment.status === 'paid' && g.payment.paidAt >= now - range * DAY; });
        var collectedSum = collected.reduce(function (a, g) { return a + (g.payment.amount || 0); }, 0);
        var withIssues = all.filter(function (g) { return g.issues.length; });
        var issueCount = {};
        withIssues.forEach(function (g) { g.issues.forEach(function (i) { issueCount[i.code] = (issueCount[i.code] || 0) + 1; }); });
        var issueList = Object.keys(issueCount).sort(function (a, b) { return issueCount[b] - issueCount[a]; });

        // daily series
        var points = [], d;
        for (d = range - 1; d >= 0; d--) points.push({ t: today - d * DAY, v: 0 });
        cur.forEach(function (g) {
          var idx = range - 1 - Math.floor((today - startOfDay(g.createdAt)) / DAY);
          if (points[idx]) points[idx].v++;
        });

        // top forms
        var byForm = {};
        cur.forEach(function (g) { byForm[g.formId] = (byForm[g.formId] || 0) + 1; });
        var top = Object.keys(byForm).map(function (k) { return { id: k, n: byForm[k], title: F.data.formsById[k].title }; })
          .sort(function (a, b) { return b.n - a.n; }).slice(0, 7);
        var topMax = top.length ? top[0].n : 1;


        // pipeline
        var pipeline = F.STATUSES.map(function (s) { return { s: s, n: all.filter(function (x) { return x.status === s.id; }).length }; });
        var pipeMax = Math.max.apply(null, pipeline.map(function (p) { return p.n; })) || 1;

        var recent = all.slice().sort(function (a, b) { return b.createdAt - a.createdAt; }).slice(0, 8);

        var rangeSel = h('div', { class: 'seg', role: 'group', 'aria-label': 'Date range' },
          [7, 30, 90].map(function (n) {
            return h('button', { type: 'button', class: 'seg-btn' + (n === range ? ' is-on' : ''), 'aria-pressed': n === range ? 'true' : 'false', onclick: function () { range = n; build(); } }, 'Last ' + n + ' days');
          }));

        var tip = h('div', { class: 'chart-tip', hidden: true });
        var chartHost = h('div', { class: 'chart' }, tip);

        var greet = (function () { var hr = new Date().getHours(); return hr < 12 ? 'Good morning' : hr < 17 ? 'Good afternoon' : 'Good evening'; })();

        F.fill(root, 
          h('div', { class: 'page-head' },
            h('div', null, h('h1', { class: 'page-title' }, greet + ', ' + F.auth.user().name.split(' ')[0]),
              h('p', { class: 'page-sub' }, F.fmt.num(todayN) + ' new today · ' + (cur.length ? Math.round(verified / cur.length * 100) : 0) + '% verified their number by OTP' + (F.scope() ? ' · showing your states only' : ''))),
            h('div', { class: 'page-actions' }, rangeSel)),
          h('div', { class: 'kpis' },
            kpi('Registrations', F.fmt.num(cur.length), 'vs previous ' + range + ' days', pct(cur.length, prev.length), '#/registrations?from=' + F.fmt.ymd(now - range * DAY), 'users'),
            kpi('Need follow-up', F.fmt.num(newCount), 'status “New”, nobody called yet', null, '#/registrations?status=new', 'alert'),
            kpi('Waiting for payment', F.fmt.num(waiting.length), F.fmt.money(waitingSum) + ' not paid yet', null, '#/registrations?pay=sent', 'clock'),
            kpi('Fee collected', F.fmt.money(collectedSum), F.fmt.num(collected.length) + ' payments in ' + range + ' days', null, '#/registrations?pay=paid', 'card')),
          h('div', { class: 'grid-2' },
            h('section', { class: 'card' },
              h('div', { class: 'card-head' }, h('h2', null, 'Registrations per day'), h('span', { class: 'muted' }, F.fmt.num(cur.length) + ' in total · ' + F.fmt.num(paid) + ' fee paid')),
              h('div', { class: 'card-body' }, chartHost)),
            h('section', { class: 'card' },
              h('div', { class: 'card-head' }, h('h2', null, 'Pipeline'), h('a', { class: 'link', href: '#/registrations' }, 'View all')),
              h('div', { class: 'card-body' },
                h('ul', { class: 'bars' }, pipeline.map(function (p) {
                  return h('li', null, h('a', { class: 'bar-row', href: '#/registrations?status=' + p.s.id },
                    h('span', { class: 'bar-label' }, p.s.label), h('span', { class: 'bar-track' }, h('span', { class: 'bar-fill st-' + p.s.id, style: { width: Math.max(2, p.n / pipeMax * 100) + '%' } })),
                    h('span', { class: 'bar-n' }, F.fmt.num(p.n))));
                })))),
            h('section', { class: 'card' },
              h('div', { class: 'card-head' }, h('h2', null, 'Top forms'), h('span', { class: 'muted' }, 'last ' + range + ' days')),
              h('div', { class: 'card-body' },
                top.length ? h('ul', { class: 'bars' }, top.map(function (t) {
                  return h('li', null, h('a', { class: 'bar-row', href: '#/registrations?form=' + t.id },
                    h('span', { class: 'bar-label' }, t.title), h('span', { class: 'bar-track' }, h('span', { class: 'bar-fill', style: { width: Math.max(3, t.n / topMax * 100) + '%' } })),
                    h('span', { class: 'bar-n' }, F.fmt.num(t.n))));
                })) : h('p', { class: 'muted' }, 'No registrations in this period.'))),
            h('section', { class: 'card' },
              h('div', { class: 'card-head' }, h('h2', null, 'Data check'), withIssues.length ? h('a', { class: 'link', href: '#/registrations?data=issues' }, 'Review all') : null),
              h('div', { class: 'card-body' },
                h('p', { class: 'muted small health-sub' }, withIssues.length ? F.fmt.num(withIssues.length) + ' of ' + F.fmt.num(all.length) + ' profiles have missing or wrong details.' : 'Every profile has complete, readable details.'),
                withIssues.length ? h('ul', { class: 'bars' }, issueList.slice(0, 6).map(function (c) {
                  return h('li', null, h('a', { class: 'bar-row health', href: '#/registrations?data=' + c }, h('span', { class: 'bar-label' }, F.profile.issueLabel(c)), h('span', { class: 'bar-n' }, F.fmt.num(issueCount[c]))));
                })) : h('p', { class: 'ok-text' }, '✓ Nothing to fix')))),
          h('section', { class: 'card' },
            h('div', { class: 'card-head' }, h('h2', null, 'Latest registrations'), h('a', { class: 'link', href: '#/registrations' }, 'See all')),
            h('ul', { class: 'recent' }, recent.map(function (g) {
              return h('li', null, h('a', { class: 'recent-row', href: '#/registrations?open=' + g.id },
                F.avatar(g.name, 38),
                h('span', { class: 'recent-main' }, h('strong', null, g.name), h('span', { class: 'muted' }, g.formTitle + ' · ' + g.city)),
                h('span', { class: 'pill st-' + g.status }, F.STATUSES.filter(function (s) { return s.id === g.status; })[0].label),
                h('time', { class: 'muted recent-time', datetime: new Date(g.createdAt).toISOString() }, F.fmt.ago(g.createdAt))));
            }))));

        var redraw = function () { drawChart(chartHost, tip, points); };
        redraw();
        if (ro) ro.disconnect();
        if (window.ResizeObserver) { var lastW = chartHost.clientWidth; ro = new ResizeObserver(function () { if (chartHost.clientWidth !== lastW) { lastW = chartHost.clientWidth; redraw(); } }); ro.observe(chartHost); }
      }

      build();
      countUp(root);
      offStore = F.store.on(build);
      return { el: root, destroy: function () { if (ro) ro.disconnect(); if (offStore) offStore(); } };
    }
  };
})();
