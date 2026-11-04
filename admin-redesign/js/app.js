/* App shell: sidebar, top bar, hash router, quick search, theme. */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;
  var NAV = [
    ['dashboard', 'Dashboard', 'dashboard'],
    ['registrations', 'Registrations', 'users'],
    ['pages', 'Forms & Pages', 'file'],
    ['otp', 'OTP Log', 'shield'],
    ['team', 'Team & Access', 'team'],
    ['settings', 'Settings', 'sliders']
  ];
  function allowed(name) { var v = F.views[name]; return !!v && (!v.perm || F.can(v.perm)); }
  function home() { var n = NAV.filter(function (x) { return allowed(x[0]); })[0]; return '#/' + (n ? n[0] : 'settings'); }
  var root = document.getElementById('root');
  var shell = null, current = null, firstRoute = true;
  var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  /* ---------- theme ---------- */
  function isDark() { return document.documentElement.getAttribute('data-theme') === 'dark'; }
  F.applyTheme = function (pref) {
    F.prefs.set('theme', pref);
    var dark = pref === 'dark' || (pref === 'system' && mq && mq.matches);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    if (shell) shell.syncTheme();
  };
  if (mq && mq.addEventListener) mq.addEventListener('change', function () { if (F.prefs.get('theme', 'system') === 'system') F.applyTheme('system'); });

  F.signOut = function () { F.auth.logout(); shell = null; if (current && current.destroy) current.destroy(); current = null; go('#/login'); };
  function go(hash) { if (location.hash === hash) route(); else location.hash = hash; }

  /* ---------- shell ---------- */
  function buildShell() {
    var app = h('div', { class: 'app' });
    var badge = h('span', { class: 'sb-badge' });
    var links = {};
    function updateBadge() {
      var n = F.reg.all().filter(function (g) { return g.status === 'new'; }).length;
      badge.textContent = F.fmt.num(n); badge.hidden = !n; badge.setAttribute('aria-label', n + ' new registrations');
    }
    var userCard = h('div', { class: 'sb-user' });
    function updateUser() {
      var u = F.auth.user();
      if (!u) return;
      F.fill(userCard, F.avatar(u.name, 34), h('div', null, h('strong', null, u.name), h('span', null, F.team.role(u.role).label + (u.states.length ? ' · ' + u.states.length + ' states' : ''))));
      NAV.forEach(function (n) { if (links[n[0]]) links[n[0]].parentNode.hidden = !allowed(n[0]); });
    }
    F.store.on(function () { updateBadge(); updateUser(); }); updateBadge();

    var navList = h('ul', null, NAV.map(function (n) {
      var a = h('a', { class: 'sb-link', href: '#/' + n[0], onclick: function () { app.classList.remove('nav-open'); } }, icon(n[2], 19), h('span', null, n[1]), n[0] === 'registrations' ? badge : null);
      links[n[0]] = a;
      return h('li', null, a);
    }));
    var sidebar = h('aside', { class: 'sidebar', id: 'sidebar', 'aria-label': 'Main' },
      h('div', { class: 'sb-brand' }, h('span', { class: 'brand-mark' }, icon('star', 18)), h('div', null, h('strong', null, 'FSIA Admin'), h('span', null, 'Forever Star India'))),
      h('nav', { class: 'sb-nav', 'aria-label': 'Sections' }, navList),
      h('div', { class: 'sb-foot' },
        h('a', { class: 'sb-link', href: 'https://www.fsia.in/', target: '_blank', rel: 'noopener noreferrer' }, icon('link', 19), h('span', null, 'Open live site')),
        h('button', { type: 'button', class: 'sb-link', onclick: F.signOut }, icon('logout', 19), h('span', null, 'Sign out / switch user')),
        userCard));
    updateUser();

    var titleEl = h('div', { class: 'tb-title' });
    var themeBtn = h('button', { type: 'button', class: 'btn-icon', onclick: function () { F.applyTheme(isDark() ? 'light' : 'dark'); } });
    function syncTheme() { F.fill(themeBtn, icon(isDark() ? 'sun' : 'moon')); themeBtn.setAttribute('aria-label', isDark() ? 'Switch to light theme' : 'Switch to dark theme'); }
    syncTheme();
    var view = h('main', { id: 'view', class: 'view', tabindex: '-1' });
    var bannerGone = false;
    try { bannerGone = sessionStorage.getItem('fsia-banner') === '1'; } catch (e) {}
    var banner = bannerGone || F.live.on ? null : h('div', { class: 'banner', role: 'note' }, icon('info', 16),
      h('span', null, h('strong', null, 'Preview with sample data.'), ' Nothing here touches fsia.in. Edits are saved only in this browser.'),
      h('button', { type: 'button', class: 'btn-icon sm', 'aria-label': 'Dismiss', onclick: function () { banner.remove(); try { sessionStorage.setItem('fsia-banner', '1'); } catch (e) {} } }, icon('x', 15)));

    app.append(sidebar, h('div', { class: 'scrim', onclick: function () { app.classList.remove('nav-open'); } }),
      h('div', { class: 'main' },
        h('header', { class: 'topbar' },
          h('button', { type: 'button', class: 'btn-icon menu-btn', 'aria-label': 'Open menu', 'aria-controls': 'sidebar', onclick: function () { app.classList.add('nav-open'); } }, icon('menu')),
          titleEl,
          h('button', { type: 'button', class: 'qs-btn', onclick: openPalette }, icon('search', 16), h('span', null, 'Quick search'), h('kbd', null, 'Ctrl K')),
          h('button', { type: 'button', class: 'btn-icon', 'aria-label': 'How to use this panel', title: 'Help', onclick: showHelp }, icon('help')),
          themeBtn),
        banner, view));

    return {
      el: app, syncTheme: syncTheme,
      mount: function (el, title, name) {
        F.fill(view, el);
        titleEl.textContent = title;
        Object.keys(links).forEach(function (k) { links[k].classList.toggle('active', k === name); if (k === name) links[k].setAttribute('aria-current', 'page'); else links[k].removeAttribute('aria-current'); });
      },
      focusView: function () { view.focus({ preventScroll: true }); }
    };
  }

  /* ---------- router ---------- */
  function route() {
    var raw = location.hash.replace(/^#\/?/, ''), qi = raw.indexOf('?');
    var path = qi < 0 ? raw : raw.slice(0, qi);
    var query = new URLSearchParams(qi < 0 ? '' : raw.slice(qi + 1));
    var parts = path.split('/').filter(Boolean), name = parts[0] || 'dashboard';
    if (current && current.destroy) { current.destroy(); current = null; }
    if (!F.auth.isIn()) {
      shell = null;
      document.title = 'Sign in · FSIA Admin Preview';
      F.fill(root, F.views.login(function () { var back = location.hash, name = back.replace(/^#\/?/, '').split(/[/?]/)[0]; go(!back || name === 'login' || !allowed(name) ? home() : back); }));
      return;
    }
    var v = F.views[name];
    if (!v || !v.render) { go(home()); return; }
    if (!shell) { shell = buildShell(); F.fill(root, shell.el); }
    var out = allowed(name) ? v.render({ parts: parts, query: query }) : { el: h('div', { class: 'empty big' }, icon('lock', 34), h('strong', null, 'You do not have access to ' + v.title),
      h('span', { class: 'muted' }, 'Your role is ' + F.team.role(F.auth.user().role).label + '. Ask a Super Admin if you need this.'), h('a', { class: 'btn btn-sm', href: home() }, 'Go to my start page')) };
    shell.mount(out.el, v.title, name);
    if (out.afterMount) out.afterMount();
    current = out;
    document.title = v.title + ' · FSIA Admin Preview';
    if (!firstRoute) { window.scrollTo(0, 0); shell.focusView(); }
    firstRoute = false;
  }
  window.addEventListener('hashchange', route);

  /* ---------- quick search (Ctrl/Cmd + K) ---------- */
  function openPalette() {
    if (!F.auth.isIn() || document.querySelector('dialog.palette')) return;
    var input = h('input', { class: 'pal-input', type: 'text', placeholder: 'Search pages, forms, people or mobile…', 'aria-label': 'Quick search', autocomplete: 'off', role: 'combobox', 'aria-expanded': 'true', 'aria-controls': 'pal-list' });
    var list = h('ul', { class: 'pal-list', id: 'pal-list', role: 'listbox' });
    var dlg = h('dialog', { class: 'palette', 'aria-label': 'Quick search' }, h('div', { class: 'pal-head' }, icon('search', 18), input, h('kbd', null, 'Esc')), list);
    var items = [], active = 0;

    function compute(q) {
      var s = q.trim().toLowerCase(), out = [];
      var digits = s.replace(/\D/g, ''), num = /^[\d\s+()-]+$/.test(s) && digits.length >= 3;
      NAV.forEach(function (n) { if (allowed(n[0]) && (!s || n[1].toLowerCase().indexOf(s) >= 0)) out.push({ group: 'Go to', label: n[1], sub: '', hash: '#/' + n[0], ico: n[2] }); });
      if (!s && allowed('registrations')) out.push({ group: 'Shortcuts', label: 'Registrations that need follow-up', sub: 'Status: New', hash: '#/registrations?status=new', ico: 'alert' });
      if (s && allowed('pages')) {
        F.data.forms.filter(function (x) { return (x.title + ' ' + x.slug + ' ' + x.id).toLowerCase().indexOf(s) >= 0; }).slice(0, 5)
          .forEach(function (x) { out.push({ group: 'Edit page', label: x.title, sub: '/' + x.slug, hash: '#/pages/' + x.id, ico: 'file' }); });
      }
      if (s && allowed('registrations')) {
        if (s.length >= 2) F.reg.all().filter(function (g) { return num ? g.mobile.indexOf(digits) >= 0 : g._hay.indexOf(s) >= 0; }).slice(0, 6)
          .forEach(function (g) { out.push({ group: 'Registrations', label: g.name, sub: g.formTitle + ' · ' + F.phone.show(g), hash: '#/registrations?open=' + g.id, ico: 'users' }); });
      }
      return out;
    }
    function draw() {
      F.fill(list);
      if (!items.length) { list.append(h('li', { class: 'pal-empty' }, 'Nothing found. Try a name, a mobile number or a form title.')); return; }
      var last = '';
      items.forEach(function (it, i) {
        if (it.group !== last) { list.append(h('li', { class: 'pal-group', role: 'presentation' }, it.group)); last = it.group; }
        list.append(h('li', { id: 'pal-' + i, role: 'option', class: 'pal-item' + (i === active ? ' is-on' : ''), 'aria-selected': i === active ? 'true' : 'false',
          onmousemove: function () { if (active !== i) { active = i; draw(); } }, onclick: function () { pick(i); } },
          icon(it.ico, 17), h('span', { class: 'pal-label' }, it.label), it.sub ? h('span', { class: 'muted small' }, it.sub) : null));
      });
      input.setAttribute('aria-activedescendant', 'pal-' + active);
      var el = list.querySelector('.is-on'); if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
    }
    function pick(i) { var it = items[i]; if (!it) return; dlg.close(); go(it.hash); }
    input.addEventListener('input', function () { items = compute(input.value); active = 0; draw(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(items.length - 1, active + 1); draw(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); draw(); }
      else if (e.key === 'Enter') { e.preventDefault(); pick(active); }
    });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('close', function () { dlg.remove(); });
    items = compute(''); draw();
    document.body.append(dlg); dlg.showModal(); input.focus();
  }

  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openPalette(); return; }
    if (e.key === '/' && !e.metaKey && !e.ctrlKey && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && !document.querySelector('dialog[open]')) {
      var s = document.querySelector('[data-search]'); if (s) { e.preventDefault(); s.focus(); }
    }
    if (e.key === 'Escape') { var a = document.querySelector('.app.nav-open'); if (a) a.classList.remove('nav-open'); }
  });

  /* ---------- plain-language guide, written for the signed-in role ---------- */
  function showHelp() {
    var steps = [];
    if (F.can('registrations.view')) steps.push(['Find someone', 'Open Registrations and type a name, mobile number or city. Use “Quick views” for common lists such as “Needs follow-up”.']);
    if (F.can('registrations.view')) steps.push(['Narrow the list', 'Pick a form, state, status or payment state. “More filters” has city, age, OTP, data check and who is handling it. Every filter shows as a chip you can remove.']);
    if (F.can('registrations.edit')) steps.push(['Update an applicant', 'Click a name to open their full profile. Change the status, write a note, call or WhatsApp them.']);
    if (F.can('payments.send')) steps.push(['Send a payment link', 'In the profile, under “Offer & payment”, set the amount, create or paste the link, press Save and then “Send on WhatsApp”.']);
    if (F.can('payments.confirm')) steps.push(['Confirm a payment', 'After the money arrives, press “Mark as paid” and add the bank or UPI reference.']);
    if (F.can('registrations.view')) steps.push(['Fix data problems', 'A small warning sign next to a name means something is missing or wrong. The profile lists each problem and how to fix it.']);
    if (F.can('pages.edit')) steps.push(['Edit a form page', 'Forms & Pages lets you change the Google title, description, share image and fee box. The preview on the right shows the result.']);
    if (F.can('team.manage')) steps.push(['Manage the team', 'Team & Access: add people, choose their role and states, and see the activity log.']);
    var u = F.auth.user();
    F.dialog({ title: 'How to use this panel', wide: true, body: h('div', { class: 'stackf' },
      h('p', { class: 'muted' }, 'You are signed in as ' + u.name + ' (' + F.team.role(u.role).label + '). ' + F.team.role(u.role).desc),
      h('ol', { class: 'help-steps' }, steps.map(function (st) { return h('li', null, h('strong', null, st[0]), h('span', null, st[1])); })),
      h('p', { class: 'muted small' }, 'Shortcuts: Ctrl + K to search anything, / to jump to the search box, Esc to close.')) });
  }

  if (!location.hash) { history.replaceState(null, '', '#/dashboard'); }
  F.fill(root, h('div', { class: 'boot', role: 'status' }, h('span', { class: 'brand-mark' }, icon('star', 24)), h('span', { class: 'boot-bar', 'aria-hidden': 'true' }), 'Loading the admin panel…'));
  F.boot(root).then(route, function () {});
})();
