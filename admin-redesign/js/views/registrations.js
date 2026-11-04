(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon, DAY = 86400000;

  var DEFAULTS = { q: '', group: '', form: '', state: '', city: '', status: '', pay: '', otp: '', data: '', assignee: '', agemin: '', agemax: '', from: '', to: '', sort: 'createdAt', dir: 'desc', page: 1, size: 25 };
  var FILTER_KEYS = ['q', 'group', 'form', 'state', 'city', 'status', 'pay', 'otp', 'data', 'assignee', 'agemin', 'agemax', 'from', 'to'];
  var STATUS_ORDER = { new: 0, contacted: 1, shortlisted: 2, paid: 3, rejected: 4 };
  var PAY = { none: 'No payment link yet', sent: 'Link sent, waiting', paid: 'Paid' };
  var PAY_SHORT = { none: 'No link', sent: 'Link sent', paid: 'Paid' };

  function statusLabel(id) { return F.STATUSES.filter(function (s) { return s.id === id; })[0].label; }
  function today() { return F.fmt.ymd(Date.now()); }
  function userName(id) { var u = id && F.team.user(id); return u ? u.name : 'Nobody'; }
  function first(name) { return name.split(' ')[0]; }
  function okMobile(g) { return F.phone.valid(g); }
  function parse(query) {
    var f = Object.assign({}, DEFAULTS);
    Object.keys(DEFAULTS).forEach(function (k) { if (query.has(k)) f[k] = query.get(k); });
    f.page = Math.max(1, +f.page || 1); f.size = [10, 25, 50, 100].indexOf(+f.size) >= 0 ? +f.size : 25;
    return f;
  }
  function toQs(f) {
    var p = new URLSearchParams();
    Object.keys(DEFAULTS).forEach(function (k) { if (String(f[k]) !== String(DEFAULTS[k])) p.set(k, f[k]); });
    return p.toString();
  }
  function activeKeys(f) { return FILTER_KEYS.filter(function (k) { return f[k] !== ''; }); }
  function filterRows(all, f) {
    var q = f.q.trim().toLowerCase();
    var numeric = /^[\d\s+()-]+$/.test(q) && q.replace(/\D/g, '').length >= 3;
    var digits = q.replace(/\D/g, '');
    if (digits.length > 10 && digits.indexOf('91') === 0) digits = digits.slice(2);
    var from = f.from ? new Date(f.from + 'T00:00:00').getTime() : 0;
    var to = f.to ? new Date(f.to + 'T23:59:59').getTime() : Infinity;
    var city = f.city.trim().toLowerCase(), me = F.auth.user();
    var amin = f.agemin === '' ? null : +f.agemin, amax = f.agemax === '' ? null : +f.agemax;
    return all.filter(function (g) {
      if (f.group && g.group !== f.group) return false;
      if (f.form && String(g.formId) !== String(f.form)) return false;
      if (f.state && g.state !== f.state) return false;
      if (city && g.city.toLowerCase().indexOf(city) < 0) return false;
      if (f.status && g.status !== f.status) return false;
      if (f.pay && g.payment.status !== f.pay) return false;
      if (f.otp === 'yes' && !g.verified) return false;
      if (f.otp === 'no' && g.verified) return false;
      if (f.data === 'issues' && !g.issues.length) return false;
      if (f.data === 'ok' && g.issues.length) return false;
      if (f.data && f.data !== 'issues' && f.data !== 'ok' && !g.issues.some(function (i) { return i.code === f.data; })) return false;
      if (f.assignee === 'me' && (!me || g.assignedTo !== me.id)) return false;
      if (f.assignee === 'none' && g.assignedTo) return false;
      if (f.assignee && f.assignee !== 'me' && f.assignee !== 'none' && g.assignedTo !== f.assignee) return false;
      if ((amin != null || amax != null) && g.age == null) return false;
      if (amin != null && g.age < amin) return false;
      if (amax != null && g.age > amax) return false;
      if (g.createdAt < from || g.createdAt > to) return false;
      if (q) { if (numeric ? g.mobile.indexOf(digits) < 0 : g._hay.indexOf(q) < 0) return false; }
      return true;
    });
  }
  function sortRows(rows, f) {
    var k = f.sort, sign = f.dir === 'asc' ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var r;
      if (k === 'name' || k === 'city') r = a[k].localeCompare(b[k]);
      else if (k === 'status') r = STATUS_ORDER[a.status] - STATUS_ORDER[b.status];
      else r = (a[k] || 0) - (b[k] || 0);
      return (r || b.createdAt - a.createdAt) * sign;
    });
  }
  function exportRows(rows, name) {
    var sensitive = F.can('registrations.sensitive');
    var extraCols = [];
    rows.forEach(function (g) { g.extras.forEach(function (e) { if (!e.secret && (sensitive || !e.sensitive) && extraCols.indexOf(e.label) < 0) extraCols.push(e.label); }); });
    var head = ['ID', 'Name', 'Mobile', 'Email', 'Date of birth', 'Age', 'City', 'State', 'Country', 'Form', 'Found us via', 'OTP verified', 'Status', 'Assigned to',
      'Offer', 'Amount', 'Payment status', 'Payment link', 'Paid on', 'Payment ref', 'Applied at', 'Data check', 'Notes'].concat(extraCols);
    var body = rows.map(function (g) {
      var p = g.payment;
      var row = [g.id, g.name, F.phone.full(g), g.email, g.dob, g.age == null ? '' : g.age, g.city, g.state, g.country, g.formTitle, g.source, g.verified ? 'Yes' : 'No',
        statusLabel(g.status), g.assignedTo ? userName(g.assignedTo) : '', p.offer, p.amount, PAY[p.status], p.url, p.paidAt ? new Date(p.paidAt).toISOString() : '', p.ref,
        new Date(g.createdAt).toISOString(), g.issues.map(function (i) { return i.text; }).join('; '), g.notes];
      extraCols.forEach(function (label) { var e = g.extras.filter(function (x) { return x.label === label; })[0]; row.push(e ? e.value : ''); });
      return row;
    });
    F.csv.download(name, [head].concat(body));
    F.audit.add('export', 'Downloaded a CSV of ' + rows.length + ' registration' + (rows.length === 1 ? '' : 's'));
    F.toast('Downloaded ' + F.fmt.num(rows.length) + ' registration' + (rows.length === 1 ? '' : 's') + ' as CSV');
  }
  function presetOf(f) {
    if (!f.from && !f.to) return 'any';
    if (!f.to) {
      if (f.from === today()) return 'today';
      var map = { 7: 'd7', 30: 'd30', 90: 'd90' };
      for (var n in map) if (f.from === F.fmt.ymd(Date.now() - n * DAY)) return map[n];
    }
    return 'custom';
  }
  function waText(g) {
    var p = g.payment;
    return 'Hello ' + first(g.name) + ', thank you for registering for ' + g.formTitle + '. Your ' + (p.offer || 'fee').toLowerCase() + ' is ' + F.fmt.money(p.amount) +
      '. Please pay here: ' + p.url + ' — Team Forever Star India';
  }

  F.views.registrations = {
    title: 'Registrations',
    perm: 'registrations.view',
    render: function (ctx) {
      var f = parse(ctx.query);
      var can = { edit: F.can('registrations.edit'), assign: F.can('registrations.assign'), del: F.can('registrations.delete'), exp: F.can('registrations.export'),
        send: F.can('payments.send'), confirm: F.can('payments.confirm'), sensitive: F.can('registrations.sensitive') };
      var bulkAllowed = can.edit || can.assign || can.del || can.exp;
      var me = F.auth.user(), scope = F.scope();
      var selected = new Set();
      var openId = ctx.query.get('open') ? +ctx.query.get('open') : null;
      var rows = [], pageRows = [];
      var root = h('div', { class: 'view-regs' });
      var tbody = h('tbody'), foot = h('div'), summary = h('span'), tabsHost = h('div'), viewsHost = h('div'), chipsHost = h('div');
      var bulk = h('div', { class: 'bulkbar', hidden: true, role: 'region', 'aria-label': 'Bulk actions' });
      var headRow = h('tr');
      var chk = h('input', { type: 'checkbox', 'aria-label': 'Select all rows on this page', onchange: function () {
        pageRows.forEach(function (g) { chk.checked ? selected.add(g.id) : selected.delete(g.id); });
        renderBody(); renderBulk();
      } });
      var dlg = null, drawerReturn = null, navIds = null, filtersOpen = false, moreOpen = false;

      function syncUrl() {
        var qs = toQs(f);
        if (openId) qs += (qs ? '&' : '') + 'open=' + openId;
        history.replaceState(null, '', '#/registrations' + (qs ? '?' + qs : ''));
      }
      function set(patch, keepPage) {
        Object.assign(f, patch);
        if (!keepPage) f.page = 1;
        selected.clear();
        syncUrl(); compute(); renderAll();
      }
      function compute() {
        rows = sortRows(filterRows(F.reg.all(), f), f);
        var pages = Math.max(1, Math.ceil(rows.length / f.size));
        if (f.page > pages) f.page = pages;
        pageRows = rows.slice((f.page - 1) * f.size, f.page * f.size);
      }
      function refresh() { compute(); renderAll(); }

      /* ---------- quick views ---------- */
      var VIEWS = [
        { label: 'Everyone', patch: {} },
        { label: 'New today', patch: { from: today() } },
        { label: 'Needs follow-up', patch: { status: 'new' } },
        { label: 'Waiting for payment', patch: { pay: 'sent' } },
        { label: 'Fee paid', patch: { pay: 'paid' } },
        { label: 'Data problems', patch: { data: 'issues' } }
      ];
      if (me) VIEWS.splice(1, 0, { label: 'My queue', patch: { assignee: 'me' } });
      function renderViews() {
        F.fill(viewsHost, h('div', { class: 'qviews', role: 'group', 'aria-label': 'Quick views' }, h('span', { class: 'qviews-label' }, 'Quick views'),
          VIEWS.map(function (v) {
            var want = Object.assign({}, v.patch), on = FILTER_KEYS.every(function (k) { return String(f[k]) === String(want[k] || ''); });
            return h('button', { type: 'button', class: 'chip' + (on ? ' is-on' : ''), 'aria-pressed': on ? 'true' : 'false', onclick: function () {
              var p = {}; FILTER_KEYS.forEach(function (k) { p[k] = ''; }); search.value = ''; set(Object.assign(p, v.patch));
            } }, v.label);
          })));
      }

      /* ---------- filters ---------- */
      var search = h('input', { class: 'input has-ico', type: 'search', placeholder: 'Search by name, mobile, email or city', 'aria-label': 'Search registrations', 'data-search': '', value: f.q,
        oninput: F.debounce(function (e) { set({ q: e.target.value }); }, 180) });
      function sel(label, key, options, hint) {
        return h('label', { class: 'fsel', title: hint || null }, h('span', { class: 'fsel-label' }, label),
          h('select', { class: 'select', value: String(f[key]), onchange: function (e) { var p = {}; p[key] = e.target.value; set(p); } }, options));
      }
      function opt(v, l) { return h('option', { value: v }, l); }
      function formOptions() {
        var opts = [opt('', 'All forms')];
        F.GROUPS.forEach(function (gr) {
          if (f.group && f.group !== gr.id) return;
          opts.push(h('optgroup', { label: gr.label }, F.data.forms.filter(function (x) { return x.group === gr.id; }).map(function (x) { return opt(String(x.id), x.title); })));
        });
        return opts;
      }
      function renderFilters() {
        var preset = presetOf(f);
        var states = scope || F.STATE_NAMES;
        var issueCodes = Object.keys(F.profile.ISSUES);
        var basic = [
          sel('Form', 'form', formOptions()),
          sel('State', 'state', [opt('', scope ? 'All my states' : 'All states')].concat(states.map(function (s) { return opt(s, s); }))),
          sel('Status', 'status', [opt('', 'Any status')].concat(F.STATUSES.map(function (s) { return opt(s.id, s.label); }))),
          sel('Payment', 'pay', [opt('', 'Any')].concat(Object.keys(PAY).map(function (k) { return opt(k, PAY[k]); }))),
          h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'Applied on'),
            h('select', { class: 'select', value: preset, onchange: function (e) {
              var v = e.target.value, now = Date.now();
              if (v === 'any') set({ from: '', to: '' });
              else if (v === 'today') set({ from: F.fmt.ymd(now), to: '' });
              else if (v === 'custom') { f.from = f.from || F.fmt.ymd(now - 7 * DAY); set({ to: f.to }); }
              else set({ from: F.fmt.ymd(now - { d7: 7, d30: 30, d90: 90 }[v] * DAY), to: '' });
            } }, [['any', 'Any time'], ['today', 'Today'], ['d7', 'Last 7 days'], ['d30', 'Last 30 days'], ['d90', 'Last 90 days'], ['custom', 'Pick dates…']].map(function (o) { return opt(o[0], o[1]); }))),
          h('div', { class: 'date-pair', hidden: preset !== 'custom' },
            h('input', { class: 'input', type: 'date', value: f.from, 'aria-label': 'From date', onchange: function (e) { set({ from: e.target.value }); } }),
            h('span', { 'aria-hidden': 'true' }, 'to'),
            h('input', { class: 'input', type: 'date', value: f.to, 'aria-label': 'To date', onchange: function (e) { set({ to: e.target.value }); } }))
        ];
        var users = F.team.users().filter(function (u) { return u.active; });
        var more = [
          h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'City'),
            h('input', { class: 'input', type: 'text', placeholder: 'Type a city', value: f.city, oninput: F.debounce(function (e) { set({ city: e.target.value }); }, 250) })),
          h('div', { class: 'fsel' }, h('span', { class: 'fsel-label', id: 'age-l' }, 'Age'),
            h('div', { class: 'age-pair', role: 'group', 'aria-labelledby': 'age-l' },
              h('input', { class: 'input', type: 'number', min: 1, max: 99, placeholder: 'From', 'aria-label': 'Age from', value: f.agemin, onchange: function (e) { set({ agemin: e.target.value }); } }),
              h('input', { class: 'input', type: 'number', min: 1, max: 99, placeholder: 'To', 'aria-label': 'Age to', value: f.agemax, onchange: function (e) { set({ agemax: e.target.value }); } }))),
          sel('OTP check', 'otp', [opt('', 'Any'), opt('yes', 'Number verified'), opt('no', 'Not verified')]),
          sel('Data check', 'data', [opt('', 'Any'), opt('issues', 'Has a problem'), opt('ok', 'Complete and correct')].concat(issueCodes.map(function (c) { return opt(c, '· ' + F.profile.issueLabel(c)); })), 'Find profiles with missing or wrong details'),
          sel('Assigned to', 'assignee', [opt('', 'Anyone'), opt('me', 'Me'), opt('none', 'Nobody yet')].concat(users.map(function (u) { return opt(u.id, u.name); })))
        ];
        var nMore = ['city', 'agemin', 'agemax', 'otp', 'data', 'assignee'].filter(function (k) { return f[k] !== ''; }).length;
        return h('div', { class: 'filters' + (filtersOpen ? ' open' : '') },
          h('div', { class: 'filter-row' }, basic,
            h('button', { type: 'button', class: 'btn btn-ghost btn-sm more-btn', 'aria-expanded': moreOpen ? 'true' : 'false', onclick: function (e) {
              moreOpen = !moreOpen; e.currentTarget.setAttribute('aria-expanded', moreOpen); e.currentTarget.closest('.filters').querySelector('.filter-more').hidden = !moreOpen;
              e.currentTarget.firstChild.nextSibling.textContent = moreOpen ? 'Fewer filters' : 'More filters';
            } }, icon('sliders', 15), moreOpen ? 'Fewer filters' : 'More filters', nMore ? h('span', { class: 'tab-n' }, String(nMore)) : null)),
          h('div', { class: 'filter-row filter-more', hidden: !moreOpen }, more));
      }
      var filtersHost = h('div');
      var filterToggle = h('button', { type: 'button', class: 'btn filters-toggle', 'aria-expanded': 'false', onclick: function () {
        filtersOpen = !filtersOpen; filtersHost.firstChild.classList.toggle('open', filtersOpen); filterToggle.setAttribute('aria-expanded', filtersOpen);
      } });

      /* Active filters as chips, each removable on its own. */
      function renderChips() {
        var c = [];
        function chip(text, patch) { c.push(h('button', { type: 'button', class: 'fchip', 'aria-label': 'Remove filter ' + text, onclick: function () { if ('q' in patch) search.value = ''; set(patch); } }, text, icon('x', 13))); }
        if (f.q) chip('Search: “' + f.q + '”', { q: '' });
        if (f.group) chip(F.GROUPS.filter(function (g) { return g.id === f.group; })[0].label, { group: '', form: '' });
        if (f.form) chip('Form: ' + F.data.formsById[f.form].title, { form: '' });
        if (f.state) chip('State: ' + f.state, { state: '' });
        if (f.city) chip('City: ' + f.city, { city: '' });
        if (f.status) chip('Status: ' + statusLabel(f.status), { status: '' });
        if (f.pay) chip('Payment: ' + PAY[f.pay], { pay: '' });
        if (f.otp) chip(f.otp === 'yes' ? 'OTP verified' : 'OTP not verified', { otp: '' });
        if (f.data) chip(f.data === 'issues' ? 'Has a data problem' : f.data === 'ok' ? 'Data complete' : F.profile.issueLabel(f.data), { data: '' });
        if (f.assignee) chip('Assigned to: ' + (f.assignee === 'me' ? 'me' : f.assignee === 'none' ? 'nobody' : userName(f.assignee)), { assignee: '' });
        if (f.agemin || f.agemax) chip('Age ' + (f.agemin || 'any') + '–' + (f.agemax || 'any'), { agemin: '', agemax: '' });
        if (f.from || f.to) chip('Applied ' + (f.from ? F.fmt.date(new Date(f.from + 'T00:00:00').getTime()) : '…') + ' – ' + (f.to ? F.fmt.date(new Date(f.to + 'T00:00:00').getTime()) : 'today'), { from: '', to: '' });
        F.fill(chipsHost, c.length ? h('div', { class: 'fchips' }, h('span', { class: 'muted small' }, 'Showing only:'), c,
          h('button', { type: 'button', class: 'link-btn', onclick: function () { search.value = ''; var p = {}; FILTER_KEYS.forEach(function (k) { p[k] = ''; }); set(p); } }, 'Clear all')) : null);
      }

      function renderTabs() {
        var base = filterRows(F.reg.all(), Object.assign({}, f, { group: '', form: '' }));
        var counts = { '': base.length };
        F.GROUPS.forEach(function (gr) { counts[gr.id] = base.filter(function (g) { return g.group === gr.id; }).length; });
        F.fill(tabsHost, h('div', { class: 'tabs', role: 'tablist', 'aria-label': 'Category' },
          [{ id: '', label: 'All' }].concat(F.GROUPS).map(function (gr) {
            var on = f.group === gr.id;
            return h('button', { type: 'button', role: 'tab', class: 'tab' + (on ? ' is-on' : ''), 'aria-selected': on ? 'true' : 'false', onclick: function () { set({ group: gr.id, form: '' }); } },
              gr.label, h('span', { class: 'tab-n' }, F.fmt.num(counts[gr.id])));
          })));
      }

      /* ---------- table ---------- */
      var COLS = [['name', 'Applicant'], [null, 'Contact'], ['city', 'City'], ['createdAt', 'Applied'], ['status', 'Status'], [null, 'Payment']];
      function renderHead() {
        F.fill(headRow, bulkAllowed ? h('th', { class: 'col-chk' }, chk) : null,
          COLS.map(function (c) {
            if (!c[0]) return h('th', null, c[1]);
            var on = f.sort === c[0];
            return h('th', { 'aria-sort': on ? (f.dir === 'asc' ? 'ascending' : 'descending') : 'none' },
              h('button', { type: 'button', class: 'th-sort' + (on ? ' is-on' : ''), onclick: function () {
                set({ sort: c[0], dir: on && f.dir === 'desc' ? 'asc' : (on ? 'desc' : (c[0] === 'name' || c[0] === 'city' ? 'asc' : 'desc')) }, true);
              } }, c[1], h('span', { class: 'sort-arrow', 'aria-hidden': 'true' }, on ? (f.dir === 'asc' ? '↑' : '↓') : '↕')));
          }));
      }
      function changeStatus(ids, status, label) {
        var prev = F.store.setStatus(ids, status);
        F.audit.add('status', label + ' → ' + statusLabel(status));
        F.toast(label + ' → ' + statusLabel(status), { action: { label: 'Undo', run: function () { F.store.restoreStatus(prev); refresh(); if (dlg && openId) openDrawer(openId, true); } } });
        refresh();
      }
      function statusSelect(g) {
        return h('select', { class: 'pill-select st-' + g.status, 'aria-label': 'Status for ' + g.name, value: g.status, disabled: !can.edit, title: can.edit ? null : 'You can see this but not change it',
          onchange: function (e) { changeStatus(g.id, e.target.value, 'Changed ' + g.name); } },
          F.STATUSES.map(function (s) { return opt(s.id, s.label); }));
      }
      function payPill(g) {
        var p = g.payment;
        return h('span', { class: 'badge ' + (p.status === 'paid' ? 'ok' : p.status === 'sent' ? 'warn' : 'plain') }, p.status === 'paid' ? icon('check', 13) : null,
          p.status === 'paid' ? 'Paid ' + F.fmt.money(p.amount) : PAY_SHORT[p.status]);
      }
      function row(g) {
        var issues = g.issues.length;
        return h('tr', { class: selected.has(g.id) ? 'is-selected' : '', 'data-id': g.id, onclick: function (e) {
          if (e.target.closest('input,select,a,button,label')) return;
          openDrawer(g.id);
        } },
          bulkAllowed ? h('td', { class: 'col-chk' }, h('input', { type: 'checkbox', checked: selected.has(g.id), 'aria-label': 'Select ' + g.name, onchange: function (e) {
            e.target.checked ? selected.add(g.id) : selected.delete(g.id);
            e.target.closest('tr').classList.toggle('is-selected', e.target.checked); renderBulk(); syncChk();
          } })) : null,
          h('td', { 'data-label': 'Applicant', class: 'cell-main' }, h('div', { class: 'who' }, F.avatar(g.name, 36), h('div', null,
            h('button', { type: 'button', class: 'who-name', onclick: function () { openDrawer(g.id); } }, g.name),
            issues ? h('span', { class: 'issue-dot', title: g.issues.map(function (i) { return i.text; }).join('\n') }, icon('alert', 13), h('span', { class: 'sr-only' }, issues + ' data problem' + (issues > 1 ? 's' : ''))) : null,
            h('div', { class: 'muted small' }, g.formTitle + ' · ' + (g.age == null ? 'Age not given' : 'Age ' + g.age))))),
          h('td', { 'data-label': 'Contact' }, h('div', { class: 'contact' }, h('span', { class: 'mono' + (okMobile(g) ? '' : ' bad') }, F.phone.show(g)),
            okMobile(g) ? h('a', { class: 'btn-icon sm', href: F.phone.wa(g), target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'WhatsApp ' + g.name, title: 'WhatsApp' }, icon('chat', 15)) : null,
            okMobile(g) ? h('a', { class: 'btn-icon sm', href: F.phone.tel(g), 'aria-label': 'Call ' + g.name, title: 'Call' }, icon('phone', 15)) : null),
            h('div', { class: 'small ' + (g.verified ? 'ok-text' : 'warn-text') }, g.verified ? '✓ OTP verified' : 'OTP not verified')),
          h('td', { 'data-label': 'City' }, g.city || h('span', { class: 'muted' }, 'Not given'), h('div', { class: 'muted small' }, g.state || 'State not given')),
          h('td', { 'data-label': 'Applied' }, h('span', { title: F.fmt.dateTime(g.createdAt) }, F.fmt.ago(g.createdAt)),
            h('div', { class: 'muted small' }, g.assignedTo ? 'With ' + first(userName(g.assignedTo)) : 'Not assigned')),
          h('td', { 'data-label': 'Status' }, statusSelect(g)),
          h('td', { 'data-label': 'Payment' }, payPill(g)));
      }
      function syncChk() {
        var n = pageRows.filter(function (g) { return selected.has(g.id); }).length;
        chk.checked = n > 0 && n === pageRows.length; chk.indeterminate = n > 0 && n < pageRows.length;
      }
      function renderBody() {
        if (!pageRows.length) {
          var n = activeKeys(f).length;
          F.fill(tbody, h('tr', { class: 'empty-row' }, h('td', { colspan: 7 }, h('div', { class: 'empty' }, icon('search', 28),
            h('strong', null, n ? 'No registrations match these filters' : 'No registrations yet'),
            h('span', { class: 'muted' }, n ? 'Remove a filter above, or search for something else.' : 'New applications from your forms will show up here.'),
            n ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { search.value = ''; var p = {}; FILTER_KEYS.forEach(function (k) { p[k] = ''; }); set(p); } }, 'Clear all filters') : null))));
        } else F.fill(tbody, pageRows.map(row));
        syncChk();
      }
      function renderFoot() {
        F.fill(foot, F.pager({ page: f.page, size: f.size, total: rows.length, onPage: function (p) { f.page = p; syncUrl(); compute(); renderBody(); renderFoot(); window.scrollTo({ top: 0 }); }, onSize: function (s) { set({ size: s }); } }));
      }
      function assignSelect(value, onPick, label) {
        return h('select', { class: 'select sm', 'aria-label': label, value: value, onchange: function (e) { onPick(e.target.value); } },
          [opt('', 'Nobody')].concat(F.team.users().filter(function (u) { return u.active && F.team.perms(u.role).indexOf('registrations.edit') >= 0; }).map(function (u) { return opt(u.id, u.name); })));
      }
      function renderBulk() {
        var ids = Array.from(selected);
        bulk.hidden = !ids.length;
        if (!ids.length) return;
        var picked = rows.filter(function (g) { return selected.has(g.id); });
        F.fill(bulk,
          h('strong', null, ids.length + ' selected'),
          can.edit ? h('label', { class: 'fsel inline' }, h('span', { class: 'sr-only' }, 'Change status of selected'),
            h('select', { class: 'select sm', value: '', onchange: function (e) { if (e.target.value) { changeStatus(ids, e.target.value, ids.length + ' registrations'); selected.clear(); refresh(); } } },
              [opt('', 'Set status…')].concat(F.STATUSES.map(function (s) { return opt(s.id, s.label); })))) : null,
          can.assign ? h('label', { class: 'fsel inline' }, h('span', { class: 'sr-only' }, 'Assign selected to'),
            h('select', { class: 'select sm', value: '-', onchange: function (e) {
              var v = e.target.value; if (v === '-') return;
              var prev = F.store.setAssign(ids, v || null);
              F.audit.add('assign', 'Gave ' + ids.length + ' registrations to ' + userName(v));
              F.toast(ids.length + ' given to ' + userName(v), { action: { label: 'Undo', run: function () { F.store.restoreAssign(prev); refresh(); } } });
              selected.clear(); refresh();
            } }, [opt('-', 'Assign to…'), opt('', 'Nobody')].concat(F.team.users().filter(function (u) { return u.active; }).map(function (u) { return opt(u.id, u.name); })))) : null,
          can.exp ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { exportRows(picked, 'fsia-selected-' + today() + '.csv'); } }, icon('download', 15), 'Download') : null,
          can.del ? h('button', { type: 'button', class: 'btn btn-sm btn-danger-ghost', onclick: function () { removeMany(ids); } }, icon('trash', 15), 'Delete') : null,
          h('button', { type: 'button', class: 'btn btn-sm btn-ghost', onclick: function () { selected.clear(); renderBody(); renderBulk(); } }, 'Clear'));
      }
      function removeMany(ids) {
        F.confirm({ title: 'Delete ' + ids.length + ' registration' + (ids.length === 1 ? '' : 's') + '?', body: 'They will disappear from this list. In the live panel this is permanent, so check before you confirm.', confirmText: 'Delete', danger: true }).then(function (ok) {
          if (!ok) return;
          F.store.remove(ids); selected.clear();
          F.audit.add('delete', 'Deleted ' + ids.length + ' registration' + (ids.length === 1 ? '' : 's') + ' (#' + ids.join(', #') + ')');
          F.toast(ids.length + ' deleted', { action: { label: 'Undo', run: function () { F.store.restore(ids); refresh(); } } });
          if (dlg && ids.indexOf(openId) >= 0) closeDrawer();
          refresh();
        });
      }

      function renderAll() {
        renderViews(); renderTabs(); F.fill(filtersHost, renderFilters()); renderChips(); renderHead(); renderBody(); renderFoot(); renderBulk();
        var fn = activeKeys(f).length - (f.q ? 1 : 0);
        F.fill(filterToggle, icon('sliders', 16), 'Filters', fn ? h('span', { class: 'tab-n' }, String(fn)) : null);
        summary.textContent = F.fmt.num(rows.length) + ' of ' + F.fmt.num(F.reg.all().length) + ' registrations' + (activeKeys(f).length ? ' match your filters' : '') + (scope ? ' in your states' : '');
      }

      /* ---------- detail drawer ---------- */
      function kv(label, val, warn) { return h('div', { class: 'kv' + (warn ? ' has-warn' : '') }, h('dt', null, label), h('dd', null, val, warn ? h('span', { class: 'kv-warn' }, icon('alert', 13), warn) : null)); }
      function paymentSection(g, id) {
        var p = g.payment, err = h('p', { class: 'form-err', role: 'alert' });
        var offer = h('input', { class: 'input', type: 'text', value: p.offer || '', 'aria-label': 'Offer name', disabled: !can.send });
        var amount = h('input', { class: 'input', type: 'number', min: 1, step: 1, value: String(p.amount || ''), 'aria-label': 'Amount in rupees', disabled: !can.send });
        var url = h('input', { class: 'input', type: 'url', value: p.url || '', placeholder: 'https://…', 'aria-label': 'Payment link', disabled: !can.send });
        var badge = h('span', { class: 'badge ' + (p.status === 'paid' ? 'ok' : p.status === 'sent' ? 'warn' : 'plain') }, PAY[p.status]);
        function check() {
          var a = parseInt(amount.value, 10);
          if (!(a > 0)) { err.textContent = 'Enter the amount in rupees, for example 1999.'; return null; }
          if (url.value.trim() && !F.profile.validUrl(url.value.trim())) { err.textContent = 'The payment link must start with https://'; return null; }
          err.textContent = ''; return { offer: offer.value.trim() || 'Audition fee', amount: a, url: url.value.trim() };
        }
        function save(extra, note) {
          var v = check(); if (!v) return false;
          var patch = Object.assign(v, extra || {});
          if (patch.url && p.status === 'none' && !patch.status) { patch.status = 'sent'; patch.sentAt = Date.now(); }
          if (!patch.url && p.status === 'sent') patch.status = 'none';
          F.store.setPayment(id, patch);
          F.audit.add('payment', note || ('Updated the offer and payment link for ' + g.name));
          refresh(); openDrawer(id, true); return true;
        }
        var wa = okMobile(g) && p.url && F.profile.validUrl(p.url) ? F.phone.wa(g) + '?text=' + encodeURIComponent(waText(g)) : null;
        var body = [
          h('div', { class: 'pay-head' }, h('div', null, h('div', { class: 'big' }, F.fmt.money(p.amount)), h('div', { class: 'muted small' }, p.offer || 'Audition fee')), badge),
          p.status === 'paid' ? h('p', { class: 'small ok-text' }, '✓ Received ' + F.fmt.dateTime(p.paidAt) + (p.ref ? ' · Ref ' + p.ref : '')) : null,
          h('div', { class: 'row2' }, h('label', { class: 'field' }, h('span', { class: 'label' }, 'Offer name'), offer), h('label', { class: 'field' }, h('span', { class: 'label' }, 'Amount (₹)'), amount)),
          h('label', { class: 'field' }, h('span', { class: 'label' }, 'Payment link'), h('div', { class: 'file-row' }, url,
            can.send && (!F.live.on || F.live.paymentTemplate) ? h('button', { type: 'button', class: 'btn', onclick: function () {
              var amt = parseInt(amount.value, 10) || p.amount;
              url.value = F.live.on ? F.live.paymentTemplate.replace('{amount}', encodeURIComponent(amt)).replace('{ref}', encodeURIComponent('FSIA-' + id)) : 'https://pay.example.com/pay/FSIA-' + id + '?amount=' + amt;
            } }, 'Create link') : null)),
          h('p', { class: 'hint' }, F.live.on ? (F.live.paymentTemplate ? '“Create link” fills in your payment link pattern. Check it, then press Save.' : 'Create the link in your payment gateway, paste it here and press Save.') : 'Preview: “Create link” makes a sample link. The live panel will create it with your payment gateway.'),
          err,
          h('div', { class: 'contact-actions' },
            can.send ? h('button', { type: 'button', class: 'btn btn-primary btn-sm', onclick: function () { if (save()) F.toast('Payment details saved'); } }, icon('save', 15), 'Save') : null,
            p.url ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { if (navigator.clipboard) navigator.clipboard.writeText(p.url).then(function () { F.toast('Payment link copied'); }, function () {}); } }, icon('copy', 15), 'Copy link') : null,
            wa ? h('a', { class: 'btn btn-sm', href: wa, target: '_blank', rel: 'noopener noreferrer', onclick: function () { F.audit.add('share', 'Sent the payment link to ' + g.name + ' (#' + g.id + ') on WhatsApp'); } }, icon('chat', 15), 'Send on WhatsApp') : null,
            can.confirm && p.status !== 'paid' ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () {
              var ref = h('input', { class: 'input', type: 'text', placeholder: 'Bank or UPI reference (optional)', 'aria-label': 'Payment reference' });
              F.dialog({ title: 'Mark ' + F.fmt.money(parseInt(amount.value, 10) || p.amount) + ' as received?', body: h('div', { class: 'stackf' }, h('p', null, 'Only do this after you see the money in the account. The status will change to “Fee paid”.'), ref),
                buttons: [['cancel', 'Cancel', ''], ['ok', 'Mark as paid', 'btn-primary']] }).then(function (v) {
                if (v !== 'ok') return;
                if (save({ status: 'paid', paidAt: Date.now(), ref: ref.value.trim() }, 'Marked the payment of ' + g.name + ' as received')) {
                  F.store.setStatus(id, 'paid'); refresh(); openDrawer(id, true); F.toast('Payment recorded');
                }
              });
            } }, icon('check', 15), 'Mark as paid') : null,
            can.confirm && p.status === 'paid' ? h('button', { type: 'button', class: 'btn btn-sm btn-ghost', onclick: function () {
              F.confirm({ title: 'Undo this payment?', body: 'Use this only if the payment was recorded by mistake.', confirmText: 'Undo payment', danger: true }).then(function (ok) {
                if (ok && save({ status: p.url ? 'sent' : 'none', paidAt: null, ref: '' }, 'Removed the payment record of ' + g.name)) F.toast('Payment record removed');
              });
            } }, 'Undo payment') : null),
          !can.send && !can.confirm ? h('p', { class: 'hint' }, icon('lock', 13), ' You can see payments but not change them.') : null
        ];
        return h('section', null, h('h3', { class: 'dr-h' }, 'Offer & payment'), h('div', { class: 'pay-card' }, body));
      }

      function openDrawer(id, keepNav) {
        openId = id; syncUrl();
        var g = F.reg.get(id);
        if (!g) { openId = null; syncUrl(); if (dlg) closeDrawer(); return; }
        if (!keepNav || !navIds) navIds = rows.map(function (x) { return x.id; });
        var live = navIds.filter(function (x) { return x === id || F.reg.get(x); });
        var idx = live.indexOf(id);
        var prevId = idx > 0 ? live[idx - 1] : null, nextId = idx >= 0 && idx < live.length - 1 ? live[idx + 1] : null;
        var returnFocus = dlg ? drawerReturn : document.activeElement;
        drawerReturn = returnFocus;
        var scrollTop = dlg ? dlg.querySelector('.dr-body').scrollTop : 0;
        if (dlg) dlg.remove();
        var byField = {};
        g.issues.forEach(function (i) { byField[i.field] = i.text; });
        var notes = h('textarea', { class: 'textarea', rows: 3, placeholder: 'Add a private note, for example “Called, will confirm tomorrow”', 'aria-label': 'Internal notes', value: g.notes, readonly: !can.edit });
        var saved = h('span', { class: 'muted small', 'aria-live': 'polite' });
        var saveNotes = function () { if (can.edit && notes.value.trim() !== (F.reg.get(id) || {}).notes) { F.store.setNotes(id, notes.value.trim()); saved.textContent = 'Saved'; } };
        notes.addEventListener('input', F.debounce(saveNotes, 500));
        notes.addEventListener('blur', saveNotes);
        var stSeg = h('div', { class: 'seg wrap', role: 'radiogroup', 'aria-label': 'Status' }, F.STATUSES.map(function (s) {
          return h('button', { type: 'button', role: 'radio', 'aria-checked': s.id === g.status ? 'true' : 'false', disabled: !can.edit && s.id !== g.status, class: 'seg-btn st-' + s.id + (s.id === g.status ? ' is-on' : ''), onclick: function () {
            if (!can.edit || s.id === g.status) return;
            changeStatus(id, s.id, 'Changed ' + g.name); openDrawer(id, true);
          } }, s.label);
        }));
        var extras = g.extras.filter(function (e) { return !e.secret; });
        var secret = g.extras.filter(function (e) { return e.secret; });
        dlg = h('dialog', { class: 'drawer', 'aria-labelledby': 'dr-title' },
          h('div', { class: 'dr-head' },
            h('div', { class: 'dr-who' }, F.avatar(g.name, 48), h('div', null, h('h2', { id: 'dr-title' }, g.name), h('p', { class: 'muted' }, g.formTitle + ' · #' + g.id))),
            h('div', { class: 'dr-nav' },
              h('button', { type: 'button', class: 'btn-icon', disabled: prevId == null, 'aria-label': 'Previous registration', title: 'Previous (←)', onclick: function () { openDrawer(prevId, true); } }, icon('left')),
              h('button', { type: 'button', class: 'btn-icon', disabled: nextId == null, 'aria-label': 'Next registration', title: 'Next (→)', onclick: function () { openDrawer(nextId, true); } }, icon('right')),
              h('button', { type: 'button', class: 'btn-icon', 'aria-label': 'Close', onclick: closeDrawer }, icon('x')))),
          h('div', { class: 'dr-body' },
            g.issues.length ? h('section', { class: 'dr-issues' }, h('h3', { class: 'dr-h' }, 'Data check · ' + g.issues.length + ' problem' + (g.issues.length > 1 ? 's' : '')),
              h('ul', { class: 'issues' }, g.issues.map(function (i) { return h('li', null, icon('alert', 15), h('div', null, h('strong', null, i.text), h('span', { class: 'muted small' }, i.fix))); }))) : null,
            h('section', null, h('h3', { class: 'dr-h' }, 'Status'), stSeg, can.edit ? null : h('p', { class: 'hint' }, icon('lock', 13), ' You can see the status but not change it.')),
            h('section', null, h('h3', { class: 'dr-h' }, 'Contact'),
              h('div', { class: 'contact-card' },
                h('div', null, h('div', { class: 'mono big' + (okMobile(g) ? '' : ' bad') }, F.phone.show(g)), h('div', { class: 'muted small' }, g.email || 'No email given')),
                h('div', { class: 'contact-actions' },
                  okMobile(g) ? h('a', { class: 'btn btn-sm', href: F.phone.wa(g), target: '_blank', rel: 'noopener noreferrer' }, icon('chat', 15), 'WhatsApp') : null,
                  okMobile(g) ? h('a', { class: 'btn btn-sm', href: F.phone.tel(g) }, icon('phone', 15), 'Call') : null,
                  g.email && F.profile.validEmail(g.email) ? h('a', { class: 'btn btn-sm', href: 'mailto:' + g.email }, icon('mail', 15), 'Email') : null,
                  g.mobile ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { if (navigator.clipboard) navigator.clipboard.writeText(F.phone.full(g)).then(function () { F.toast('Mobile number copied'); }, function () {}); } }, icon('copy', 15), 'Copy number') : null))),
            paymentSection(g, id),
            h('section', null, h('h3', { class: 'dr-h' }, 'Profile'),
              h('dl', { class: 'kvs' },
                kv('Full name', g.name, byField.name),
                kv('Mobile number', F.phone.show(g), byField.mobile),
                kv('Email', F.fmt.val(g.email), byField.email),
                kv('Date of birth', g.dob ? F.fmt.date(new Date(g.dob + 'T00:00:00').getTime()) : 'Not provided', byField.dob),
                kv('Age', g.age == null ? 'Not provided' : g.age + (g.ageDerived ? ' (worked out from date of birth)' : ''), byField.age),
                kv('City', F.fmt.val(g.city), byField.city),
                kv('State', F.fmt.val(g.state), byField.state),
                g.country ? kv('Country', g.country) : null,
                kv('Found us via', F.fmt.val(g.source)),
                kv('OTP check', h('span', { class: 'badge ' + (g.verified ? 'ok' : 'warn') }, g.verified ? 'Number verified' : 'Not verified')),
                kv('Applied', F.fmt.dateTime(g.createdAt)),
                kv('Photo', 'Uploaded with the form (shown here in the live panel)'))),
            extras.length || secret.length ? h('section', null, h('h3', { class: 'dr-h' }, 'More details from the form'),
              h('p', { class: 'hint' }, 'Everything else this form saved, including fields that older versions of the form asked for. Nothing is left out.'),
              h('dl', { class: 'kvs' }, extras.map(function (e) {
                return kv(e.label, e.sensitive && !can.sensitive ? h('span', { class: 'muted' }, icon('lock', 13), ' Only people with KYC access can see this') : e.value);
              }), secret.map(function (e) { return kv(e.label, h('span', { class: 'muted' }, icon('lock', 13), ' Saved by the old form. Hidden for safety')); }))) : null,
            h('section', null, h('h3', { class: 'dr-h' }, 'Who is handling this'),
              can.assign ? assignSelect(g.assignedTo || '', function (v) {
                var prev = F.store.setAssign(id, v || null);
                F.audit.add('assign', 'Gave ' + g.name + ' to ' + userName(v));
                F.toast(g.name + ' given to ' + userName(v), { action: { label: 'Undo', run: function () { F.store.restoreAssign(prev); refresh(); if (dlg) openDrawer(id, true); } } });
                refresh(); openDrawer(id, true);
              }, 'Assigned to') : h('p', null, g.assignedTo ? userName(g.assignedTo) : 'Nobody yet')),
            h('section', null, h('h3', { class: 'dr-h' }, 'Internal notes'), notes, saved)),
          h('div', { class: 'dr-foot' },
            can.del ? h('button', { type: 'button', class: 'btn btn-danger-ghost', onclick: function () { removeMany([id]); } }, icon('trash', 16), 'Delete') : h('span'),
            h('span', { class: 'muted small' }, idx >= 0 ? (idx + 1) + ' of ' + F.fmt.num(live.length) + ' in this list' : ''),
            h('button', { type: 'button', class: 'btn', onclick: closeDrawer }, 'Close')));
        dlg.addEventListener('close', function () { openId = null; if (location.hash.indexOf('#/registrations') === 0) syncUrl(); if (dlg) { dlg.remove(); dlg = null; } if (returnFocus && returnFocus.focus && document.contains(returnFocus)) returnFocus.focus(); });
        dlg.addEventListener('click', function (e) { if (e.target === dlg) closeDrawer(); });
        dlg.addEventListener('keydown', function (e) {
          if (/TEXTAREA|INPUT|SELECT/.test(e.target.tagName)) return;
          if (e.key === 'ArrowRight' && nextId != null) openDrawer(nextId, true);
          if (e.key === 'ArrowLeft' && prevId != null) openDrawer(prevId, true);
        });
        document.body.append(dlg); dlg.showModal();
        if (keepNav && scrollTop) dlg.querySelector('.dr-body').scrollTop = scrollTop;
      }
      function closeDrawer() { if (dlg) dlg.close(); }

      /* ---------- mount ---------- */
      compute();
      root.append(
        h('div', { class: 'page-head' },
          h('div', null, h('h1', { class: 'page-title' }, 'Registrations'), h('p', { class: 'page-sub' }, summary)),
          h('div', { class: 'page-actions' },
            can.exp ? h('button', { type: 'button', class: 'btn', onclick: function () { exportRows(rows, 'fsia-registrations-' + today() + '.csv'); } }, icon('download', 16), 'Download CSV') : null)),
        viewsHost,
        tabsHost,
        h('div', { class: 'card tbl-card' },
          h('div', { class: 'toolbar' }, h('div', { class: 'search-row' }, h('div', { class: 'search' }, icon('search', 16), search), filterToggle), filtersHost, chipsHost),
          h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive' }, h('thead', null, headRow), tbody)),
          foot),
        bulk);
      renderAll();
      if (openId) openDrawer(openId);
      return { el: root, destroy: function () { if (dlg) { dlg.remove(); dlg = null; } } };
    }
  };
})();
