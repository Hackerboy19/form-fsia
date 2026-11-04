(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon, DAY = 86400000;

  var DEFAULTS = { q: '', group: '', form: '', state: '', status: '', otp: '', from: '', to: '', sort: 'createdAt', dir: 'desc', page: 1, size: 25 };
  var STATUS_ORDER = { new: 0, contacted: 1, shortlisted: 2, paid: 3, rejected: 4 };

  function statusLabel(id) { return F.STATUSES.filter(function (s) { return s.id === id; })[0].label; }
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
  function activeCount(f) {
    return ['q', 'group', 'form', 'state', 'status', 'otp', 'from', 'to'].filter(function (k) { return f[k] !== ''; }).length;
  }
  function filterRows(all, f) {
    var q = f.q.trim().toLowerCase();
    var numeric = /^[\d\s+()-]+$/.test(q) && q.replace(/\D/g, '').length >= 3;
    var digits = q.replace(/\D/g, '');
    if (digits.length > 10 && digits.indexOf('91') === 0) digits = digits.slice(2);
    var from = f.from ? new Date(f.from + 'T00:00:00').getTime() : 0;
    var to = f.to ? new Date(f.to + 'T23:59:59').getTime() : Infinity;
    return all.filter(function (g) {
      if (f.group && g.group !== f.group) return false;
      if (f.form && String(g.formId) !== String(f.form)) return false;
      if (f.state && g.state !== f.state) return false;
      if (f.status && g.status !== f.status) return false;
      if (f.otp === 'yes' && !g.verified) return false;
      if (f.otp === 'no' && g.verified) return false;
      if (g.createdAt < from || g.createdAt > to) return false;
      if (q) { if (numeric ? g.mobile.indexOf(digits) < 0 : g._hay.indexOf(q) < 0) return false; }
      return true;
    });
  }
  function sortRows(rows, f) {
    var k = f.sort, sign = f.dir === 'asc' ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var r;
      if (k === 'name' || k === 'formTitle' || k === 'city') r = a[k].localeCompare(b[k]);
      else if (k === 'status') r = STATUS_ORDER[a.status] - STATUS_ORDER[b.status];
      else r = a[k] - b[k];
      return (r || b.createdAt - a.createdAt) * sign;
    });
  }
  function exportRows(rows, name) {
    var head = ['ID', 'Name', 'Mobile', 'Email', 'Date of birth', 'Age', 'City', 'State', 'Form', 'Source', 'OTP verified', 'Status', 'Applied at', 'Notes'];
    var body = rows.map(function (g) {
      return [g.id, g.name, '+91' + g.mobile, g.email, g.dob, g.age, g.city, g.state, g.formTitle, g.source, g.verified ? 'Yes' : 'No', statusLabel(g.status), new Date(g.createdAt).toISOString(), g.notes];
    });
    F.csv.download(name, [head].concat(body));
    F.toast('Exported ' + F.fmt.num(rows.length) + ' registration' + (rows.length === 1 ? '' : 's') + ' to CSV');
  }
  function today() { return F.fmt.ymd(Date.now()); }
  function presetOf(f) {
    if (!f.from && !f.to) return 'any';
    if (!f.to) {
      if (f.from === today()) return 'today';
      var map = { 7: 'd7', 30: 'd30', 90: 'd90' };
      for (var n in map) if (f.from === F.fmt.ymd(Date.now() - n * DAY)) return map[n];
    }
    return 'custom';
  }

  F.views.registrations = {
    title: 'Registrations',
    render: function (ctx) {
      var f = parse(ctx.query);
      var selected = new Set();
      var openId = ctx.query.get('open') ? +ctx.query.get('open') : null;
      var rows = [], pageRows = [];
      var root = h('div', { class: 'view-regs' });
      var tbody = h('tbody'), foot = h('div'), summary = h('span', { class: 'page-sub' }), tabsHost = h('div');
      var bulk = h('div', { class: 'bulkbar', hidden: true, role: 'region', 'aria-label': 'Bulk actions' });
      var headRow = h('tr');
      var chk = h('input', { type: 'checkbox', 'aria-label': 'Select all rows on this page', onchange: function () {
        pageRows.forEach(function (g) { chk.checked ? selected.add(g.id) : selected.delete(g.id); });
        renderBody(); renderBulk();
      } });
      var dlg = null, drawerReturn = null, navIds = null, filtersOpen = false;

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

      /* ---------- toolbar ---------- */
      var search = h('input', { class: 'input has-ico', type: 'search', placeholder: 'Search name, mobile, email, city…', 'aria-label': 'Search registrations', 'data-search': '', value: f.q,
        oninput: F.debounce(function (e) { set({ q: e.target.value }); }, 180) });
      function sel(label, key, options, extra) {
        return h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, label),
          h('select', { class: 'select', value: String(f[key]), onchange: function (e) { var p = {}; p[key] = e.target.value; if (extra) extra(p, e.target.value); set(p); } }, options));
      }
      function formOptions() {
        var opts = [h('option', { value: '' }, 'All forms')];
        F.GROUPS.forEach(function (gr) {
          if (f.group && f.group !== gr.id) return;
          opts.push(h('optgroup', { label: gr.label }, F.data.forms.filter(function (x) { return x.group === gr.id; }).map(function (x) { return h('option', { value: String(x.id) }, x.title); })));
        });
        return opts;
      }
      var customDates = h('div', { class: 'date-pair' });
      function renderFilters() {
        var preset = presetOf(f);
        var presetSel = h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'Applied'),
          h('select', { class: 'select', value: preset, onchange: function (e) {
            var v = e.target.value, now = Date.now();
            if (v === 'any') set({ from: '', to: '' });
            else if (v === 'today') set({ from: F.fmt.ymd(now), to: '' });
            else if (v === 'custom') { f.from = f.from || F.fmt.ymd(now - 7 * DAY); set({ to: f.to }); }
            else set({ from: F.fmt.ymd(now - { d7: 7, d30: 30, d90: 90 }[v] * DAY), to: '' });
          } }, [['any', 'Any time'], ['today', 'Today'], ['d7', 'Last 7 days'], ['d30', 'Last 30 days'], ['d90', 'Last 90 days'], ['custom', 'Custom range…']].map(function (o) { return h('option', { value: o[0] }, o[1]); })));
        customDates = h('div', { class: 'date-pair', hidden: preset !== 'custom' },
          h('input', { class: 'input', type: 'date', value: f.from, 'aria-label': 'From date', onchange: function (e) { set({ from: e.target.value }); } }),
          h('span', { 'aria-hidden': 'true' }, '–'),
          h('input', { class: 'input', type: 'date', value: f.to, 'aria-label': 'To date', onchange: function (e) { set({ to: e.target.value }); } }));
        var n = activeCount(f);
        return h('div', { class: 'filters' + (filtersOpen ? ' open' : '') },
          sel('Form', 'form', formOptions()),
          sel('State', 'state', [h('option', { value: '' }, 'All states')].concat(F.STATE_NAMES.map(function (s) { return h('option', { value: s }, s); }))),
          sel('Status', 'status', [h('option', { value: '' }, 'Any status')].concat(F.STATUSES.map(function (s) { return h('option', { value: s.id }, s.label); }))),
          sel('OTP', 'otp', [['', 'Any'], ['yes', 'Verified'], ['no', 'Not verified']].map(function (o) { return h('option', { value: o[0] }, o[1]); })),
          presetSel, customDates,
          n ? h('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: function () { search.value = ''; set(Object.assign({}, DEFAULTS, { sort: f.sort, dir: f.dir, size: f.size })); } }, icon('x', 14), 'Clear filters (' + n + ')') : null);
      }
      var filtersHost = h('div');
      var filterToggle = h('button', { type: 'button', class: 'btn filters-toggle', 'aria-expanded': 'false', onclick: function () {
        filtersOpen = !filtersOpen; filtersHost.firstChild.classList.toggle('open', filtersOpen); filterToggle.setAttribute('aria-expanded', filtersOpen);
      } });

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
      var COLS = [['name', 'Applicant'], [null, 'Contact'], ['city', 'City'], ['createdAt', 'Applied'], ['status', 'Status'], [null, 'OTP']];
      function renderHead() {
        F.fill(headRow, h('th', { class: 'col-chk' }, chk),
          COLS.map(function (c) {
            if (!c[0]) return h('th', null, c[1]);
            var on = f.sort === c[0];
            return h('th', { 'aria-sort': on ? (f.dir === 'asc' ? 'ascending' : 'descending') : 'none' },
              h('button', { type: 'button', class: 'th-sort' + (on ? ' is-on' : ''), onclick: function () {
                set({ sort: c[0], dir: on && f.dir === 'desc' ? 'asc' : (on ? 'desc' : (c[0] === 'name' || c[0] === 'formTitle' || c[0] === 'city' ? 'asc' : 'desc')) }, true);
              } }, c[1], h('span', { class: 'sort-arrow', 'aria-hidden': 'true' }, on ? (f.dir === 'asc' ? '↑' : '↓') : '↕')));
          }));
      }
      function statusSelect(g) {
        return h('select', { class: 'pill-select st-' + g.status, 'aria-label': 'Status for ' + g.name, value: g.status, onchange: function (e) {
          var prev = F.store.setStatus(g.id, e.target.value);
          F.toast(g.name + ' → ' + statusLabel(e.target.value), { action: { label: 'Undo', run: function () { F.store.restoreStatus(prev); compute(); renderAll(); } } });
          compute(); renderAll();
        } }, F.STATUSES.map(function (s) { return h('option', { value: s.id }, s.label); }));
      }
      function row(g) {
        var wa = 'https://wa.me/91' + g.mobile;
        return h('tr', { class: selected.has(g.id) ? 'is-selected' : '', 'data-id': g.id, onclick: function (e) {
          if (e.target.closest('input,select,a,button,label')) return;
          openDrawer(g.id);
        } },
          h('td', { class: 'col-chk' }, h('input', { type: 'checkbox', checked: selected.has(g.id), 'aria-label': 'Select ' + g.name, onchange: function (e) {
            e.target.checked ? selected.add(g.id) : selected.delete(g.id);
            e.target.closest('tr').classList.toggle('is-selected', e.target.checked); renderBulk(); syncChk();
          } })),
          h('td', { 'data-label': 'Applicant', class: 'cell-main' }, h('div', { class: 'who' }, F.avatar(g.name, 36), h('div', null, h('button', { type: 'button', class: 'who-name', onclick: function () { openDrawer(g.id); } }, g.name), h('div', { class: 'muted small' }, g.formTitle + ' · Age ' + g.age)))),
          h('td', { 'data-label': 'Contact' }, h('div', { class: 'contact' }, h('span', { class: 'mono' }, F.fmt.phone(g.mobile)),
            h('a', { class: 'btn-icon sm', href: wa, target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'WhatsApp ' + g.name, title: 'WhatsApp' }, icon('chat', 15)),
            h('a', { class: 'btn-icon sm', href: 'tel:+91' + g.mobile, 'aria-label': 'Call ' + g.name, title: 'Call' }, icon('phone', 15)))),
          h('td', { 'data-label': 'City' }, g.city, h('div', { class: 'muted small' }, g.state)),
          h('td', { 'data-label': 'Applied' }, h('span', { title: F.fmt.dateTime(g.createdAt) }, F.fmt.ago(g.createdAt))),
          h('td', { 'data-label': 'Status' }, statusSelect(g)),
          h('td', { 'data-label': 'OTP' }, h('span', { class: 'badge ' + (g.verified ? 'ok' : 'warn') }, g.verified ? icon('check', 13) : null, g.verified ? 'Verified' : 'Not verified')));
      }
      function syncChk() {
        var n = pageRows.filter(function (g) { return selected.has(g.id); }).length;
        chk.checked = n > 0 && n === pageRows.length; chk.indeterminate = n > 0 && n < pageRows.length;
      }
      function renderBody() {
        if (!pageRows.length) {
          var n = activeCount(f);
          F.fill(tbody, h('tr', { class: 'empty-row' }, h('td', { colspan: 7 }, h('div', { class: 'empty' }, icon('search', 28),
            h('strong', null, n ? 'No registrations match these filters' : 'No registrations yet'),
            h('span', { class: 'muted' }, n ? 'Try removing a filter or searching for something else.' : 'New applications from your forms will show up here.'),
            n ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { search.value = ''; set(Object.assign({}, DEFAULTS)); } }, 'Clear filters') : null))));
        } else F.fill(tbody, pageRows.map(row));
        syncChk();
      }
      function renderFoot() {
        F.fill(foot, F.pager({ page: f.page, size: f.size, total: rows.length, onPage: function (p) { f.page = p; syncUrl(); compute(); renderBody(); renderFoot(); window.scrollTo({ top: 0 }); }, onSize: function (s) { set({ size: s }); } }));
      }
      function renderBulk() {
        var ids = Array.from(selected);
        bulk.hidden = !ids.length;
        if (!ids.length) return;
        var picked = rows.filter(function (g) { return selected.has(g.id); });
        F.fill(bulk, 
          h('strong', null, ids.length + ' selected'),
          h('label', { class: 'fsel inline' }, h('span', { class: 'sr-only' }, 'Change status of selected'),
            h('select', { class: 'select sm', value: '', onchange: function (e) {
              if (!e.target.value) return;
              var prev = F.store.setStatus(ids, e.target.value);
              F.toast(ids.length + ' marked ' + statusLabel(e.target.value), { action: { label: 'Undo', run: function () { F.store.restoreStatus(prev); compute(); renderAll(); } } });
              selected.clear(); compute(); renderAll();
            } }, [h('option', { value: '' }, 'Set status…')].concat(F.STATUSES.map(function (s) { return h('option', { value: s.id }, s.label); })))),
          h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { exportRows(picked, 'fsia-selected-' + today() + '.csv'); } }, icon('download', 15), 'Export'),
          h('button', { type: 'button', class: 'btn btn-sm btn-danger-ghost', onclick: function () { removeMany(ids); } }, icon('trash', 15), 'Delete'),
          h('button', { type: 'button', class: 'btn btn-sm btn-ghost', onclick: function () { selected.clear(); renderBody(); renderBulk(); } }, 'Clear'));
      }
      function removeMany(ids) {
        F.confirm({ title: 'Delete ' + ids.length + ' registration' + (ids.length === 1 ? '' : 's') + '?', body: 'They will disappear from this list. In the live panel this is permanent, so double-check before confirming.', confirmText: 'Delete', danger: true }).then(function (ok) {
          if (!ok) return;
          F.store.remove(ids); selected.clear();
          F.toast(ids.length + ' deleted', { action: { label: 'Undo', run: function () { F.store.restore(ids); compute(); renderAll(); } } });
          if (dlg && ids.indexOf(openId) >= 0) closeDrawer();
          compute(); renderAll();
        });
      }

      function renderAll() {
        renderTabs(); F.fill(filtersHost, renderFilters());
        var fn = activeCount(f) - (f.q ? 1 : 0);
        F.fill(filterToggle, icon('sliders', 16), 'Filters', fn ? h('span', { class: 'tab-n' }, String(fn)) : null); renderHead(); renderBody(); renderFoot(); renderBulk();
        summary.textContent = F.fmt.num(rows.length) + ' of ' + F.fmt.num(F.reg.all().length) + ' registrations' + (activeCount(f) ? ' match your filters' : '');
      }

      /* ---------- detail drawer ---------- */
      /* keepNav: moving inside the drawer keeps the list order it was opened with, so a status change
         that drops the record out of the current filter does not break previous/next. */
      function openDrawer(id, keepNav) {
        openId = id; syncUrl();
        var g = F.reg.get(id);
        if (!g) { openId = null; syncUrl(); return; }
        if (!keepNav || !navIds) navIds = rows.map(function (x) { return x.id; });
        var live = navIds.filter(function (x) { return x === id || F.reg.get(x); });
        var idx = live.indexOf(id);
        var prevId = idx > 0 ? live[idx - 1] : null, nextId = idx >= 0 && idx < live.length - 1 ? live[idx + 1] : null;
        var returnFocus = dlg ? drawerReturn : document.activeElement;
        drawerReturn = returnFocus;
        if (dlg) dlg.remove();
        var notes = h('textarea', { class: 'textarea', rows: 4, placeholder: 'Add a private note, e.g. “Called, will confirm tomorrow”', 'aria-label': 'Internal notes', value: g.notes });
        var saved = h('span', { class: 'muted small', 'aria-live': 'polite' });
        var saveNotes = function () { if (notes.value !== (F.reg.get(id) || {}).notes) { F.store.setNotes(id, notes.value.trim()); saved.textContent = 'Saved'; } };
        notes.addEventListener('input', F.debounce(saveNotes, 500));
        notes.addEventListener('blur', saveNotes);
        function kv(label, val) { return h('div', { class: 'kv' }, h('dt', null, label), h('dd', null, val)); }
        var stSeg = h('div', { class: 'seg wrap', role: 'radiogroup', 'aria-label': 'Status' }, F.STATUSES.map(function (s) {
          return h('button', { type: 'button', role: 'radio', 'aria-checked': s.id === g.status ? 'true' : 'false', class: 'seg-btn st-' + s.id + (s.id === g.status ? ' is-on' : ''), onclick: function () {
            var prev = F.store.setStatus(id, s.id);
            F.toast(g.name + ' → ' + s.label, { action: { label: 'Undo', run: function () { F.store.restoreStatus(prev); compute(); renderAll(); if (dlg) openDrawer(id, true); } } });
            compute(); renderAll(); openDrawer(id, true);
          } }, s.label);
        }));
        dlg = h('dialog', { class: 'drawer', 'aria-labelledby': 'dr-title' },
          h('div', { class: 'dr-head' },
            h('div', { class: 'dr-who' }, F.avatar(g.name, 48), h('div', null, h('h2', { id: 'dr-title' }, g.name), h('p', { class: 'muted' }, g.formTitle + ' · #' + g.id))),
            h('div', { class: 'dr-nav' },
              h('button', { type: 'button', class: 'btn-icon', disabled: prevId == null, 'aria-label': 'Previous registration', title: 'Previous (←)', onclick: function () { openDrawer(prevId, true); } }, icon('left')),
              h('button', { type: 'button', class: 'btn-icon', disabled: nextId == null, 'aria-label': 'Next registration', title: 'Next (→)', onclick: function () { openDrawer(nextId, true); } }, icon('right')),
              h('button', { type: 'button', class: 'btn-icon', 'aria-label': 'Close', onclick: closeDrawer }, icon('x')))),
          h('div', { class: 'dr-body' },
            h('section', null, h('h3', { class: 'dr-h' }, 'Status'), stSeg),
            h('section', null, h('h3', { class: 'dr-h' }, 'Contact'),
              h('div', { class: 'contact-card' },
                h('div', null, h('div', { class: 'mono big' }, F.fmt.phone(g.mobile)), h('div', { class: 'muted small' }, g.email)),
                h('div', { class: 'contact-actions' },
                  h('a', { class: 'btn btn-sm', href: 'https://wa.me/91' + g.mobile, target: '_blank', rel: 'noopener noreferrer' }, icon('chat', 15), 'WhatsApp'),
                  h('a', { class: 'btn btn-sm', href: 'tel:+91' + g.mobile }, icon('phone', 15), 'Call'),
                  h('a', { class: 'btn btn-sm', href: 'mailto:' + g.email }, icon('mail', 15), 'Email'),
                  h('button', { type: 'button', class: 'btn btn-sm', onclick: function () { if (navigator.clipboard) navigator.clipboard.writeText(g.mobile).then(function () { F.toast('Mobile number copied'); }, function () {}); } }, icon('copy', 15), 'Copy')))),
            h('section', null, h('h3', { class: 'dr-h' }, 'Application'),
              h('dl', { class: 'kvs' }, kv('Date of birth', F.fmt.date(new Date(g.dob).getTime()) + ' (age ' + g.age + ')'), kv('City', g.city + ', ' + g.state), kv('Applied', F.fmt.dateTime(g.createdAt)), kv('Found us via', g.source),
                kv('OTP', h('span', { class: 'badge ' + (g.verified ? 'ok' : 'warn') }, g.verified ? 'Verified' : 'Not verified')), kv('Photo', 'Uploaded with the form (shown here in the live panel)'))),
            h('section', null, h('h3', { class: 'dr-h' }, 'Internal notes'), notes, saved)),
          h('div', { class: 'dr-foot' },
            h('button', { type: 'button', class: 'btn btn-danger-ghost', onclick: function () { removeMany([id]); } }, icon('trash', 16), 'Delete'),
            h('span', { class: 'muted small' }, idx >= 0 ? (idx + 1) + ' of ' + F.fmt.num(live.length) + ' in this list' : ''),
            h('button', { type: 'button', class: 'btn', onclick: closeDrawer }, 'Close')));
        dlg.addEventListener('close', function () { openId = null; syncUrl(); if (dlg) { dlg.remove(); dlg = null; } if (returnFocus && returnFocus.focus && document.contains(returnFocus)) returnFocus.focus(); });
        dlg.addEventListener('click', function (e) { if (e.target === dlg) closeDrawer(); });
        dlg.addEventListener('keydown', function (e) {
          if (/TEXTAREA|INPUT|SELECT/.test(e.target.tagName)) return;
          if (e.key === 'ArrowRight' && nextId != null) openDrawer(nextId, true);
          if (e.key === 'ArrowLeft' && prevId != null) openDrawer(prevId, true);
        });
        document.body.append(dlg); dlg.showModal();
      }
      function closeDrawer() { if (dlg) dlg.close(); }

      /* ---------- mount ---------- */
      compute();
      root.append(
        h('div', { class: 'page-head' },
          h('div', null, h('h1', { class: 'page-title' }, 'Registrations'), h('p', { class: 'page-sub' }, summary)),
          h('div', { class: 'page-actions' },
            h('button', { type: 'button', class: 'btn', onclick: function () { exportRows(rows, 'fsia-registrations-' + today() + '.csv'); } }, icon('download', 16), 'Export CSV'))),
        tabsHost,
        h('div', { class: 'card tbl-card' },
          h('div', { class: 'toolbar' }, h('div', { class: 'search-row' }, h('div', { class: 'search' }, icon('search', 16), search), filterToggle), filtersHost),
          h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive' }, h('thead', null, headRow), tbody)),
          foot),
        bulk);
      renderAll();
      if (openId) openDrawer(openId);
      var off = F.store.on(function () { /* edits made elsewhere (e.g. sidebar badge) */ });

      return { el: root, destroy: function () { off(); if (dlg) { dlg.remove(); dlg = null; } } };
    }
  };
})();
