/* Live mode: when the panel is served next to api/ (on Hostinger), it signs in with a real user name and
   password and reads and writes the real database through the PHP API. Anywhere else (Vercel, a laptop)
   api/ is missing and the panel stays in preview mode with sample data. */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;
  var API = 'api/index.php?r=';

  var L = F.live = { on: false, user: null, csrf: '', paymentTemplate: '' };

  function call(action, payload) {
    var post = payload !== undefined;
    return fetch(API + encodeURIComponent(action), {
      method: post ? 'POST' : 'GET',
      credentials: 'same-origin',
      headers: post ? { 'Content-Type': 'application/json', 'X-CSRF-Token': L.csrf, Accept: 'application/json' } : { Accept: 'application/json' },
      body: post ? JSON.stringify(payload) : undefined
    }).then(function (r) {
      var json = (r.headers.get('content-type') || '').indexOf('application/json') >= 0;
      if (!json) throw Object.assign(new Error('The server did not answer.'), { status: r.status, notApi: true });
      return r.json().then(function (d) {
        if (!r.ok) throw Object.assign(new Error(d.error || 'Something went wrong.'), { status: r.status });
        return d;
      });
    });
  }
  L.call = call;

  /* A write the screen has already shown. If the server refuses it, say why and reload the real data. */
  L.send = function (action, payload, reloadAfter) {
    return call(action, payload).then(function (d) {
      if (reloadAfter) return L.reload().then(function () { return d; });
      return d;
    }, function (e) {
      F.toast(e.message + ' Showing the saved data again.', { type: 'error' });
      if (e.status === 401) { location.reload(); return; }
      return L.reload();
    });
  };
  L.sendPayment = function (id, patch, before) {
    var p = { id: id };
    if ('offer' in patch || 'amount' in patch || 'url' in patch) { p.offer = patch.offer; p.amount = patch.amount; p.url = patch.url; }
    if (patch.status && patch.status !== before.status) { p.status = patch.status; if (before.status === 'paid') p.undoPaid = true; }
    if (patch.status === 'paid') p.ref = patch.ref || '';
    return L.send('reg.payment', p);
  };
  L.saveMember = function (u) {
    var body = { id: /^\d+$/.test(String(u.id || '')) ? +u.id : '', name: u.name, email: u.email, username: u.username || '', role: u.role, states: u.states || [], active: u.active !== false };
    if (u.password) body.password = u.password;
    return L.send('team.save', body, true).then(function (d) { if (d && d.ok) F.toast(u.name + ' saved'); });
  };
  L.logout = function () { call('logout', {}).then(function () { location.reload(); }, function () { location.reload(); }); };

  /* ---------- turning API rows into the records the screens use ---------- */
  function formFor(row, p) {
    if (row.formId != null && F.data.formsById[row.formId]) return F.data.formsById[row.formId];
    if (row.group === 'miss' || row.group === 'mrs') {
      var st = (p.state || '').toLowerCase();
      var hit = F.data.forms.filter(function (f) { return f.group === row.group && f.state && f.state.toLowerCase() === st; })[0];
      if (hit) return hit;
    }
    // A table that is not linked to a form yet still shows, under its own name.
    var id = 'table:' + row.table;
    if (!F.data.formsById[id]) {
      var f = { id: id, slug: row.table, title: row.table, group: row.group || 'events', state: null, live: '', idKnown: false, noPage: true };
      F.data.forms.push(f); F.data.formsById[id] = f;
    }
    return F.data.formsById[id];
  }
  function toReg(row) {
    var p = F.profile.normalize(row.raw || {});
    var f = formFor(row, p), w = row.work || {};
    var extras = p.extras.slice();
    (row.hidden || []).forEach(function (k) { extras.push({ key: k, label: (F.profile.EXTRA[k] || [k])[0], value: '', sensitive: true, secret: false }); });
    (row.secret || []).forEach(function (k) { extras.push({ key: k, label: (F.profile.EXTRA[k] || [k])[0], value: '', sensitive: true, secret: true }); });
    var g = {
      id: row.uid, formId: f.id, formTitle: f.title, group: f.group, createdAt: row.createdAt || 0, verified: !!row.verified,
      status: w.status || 'new',
      name: p.name || 'Unnamed applicant', cc: p.cc, mobile: p.mobile, email: p.email, country: p.country, dob: p.dob, age: p.age, ageDerived: p.ageDerived,
      city: p.city, state: p.state, source: p.source, extras: extras, profileIssues: p.issues, raw: row.raw,
      assignedTo: w.assignedTo ? String(w.assignedTo) : null,
      payment: { offer: w.offer || 'Audition fee', amount: w.amount || (typeof f.id === 'number' ? F.data.feeOf(f.id) : 1999), url: w.url || '', status: w.payStatus || 'none', sentAt: w.sentAt || null, paidAt: w.paidAt || null, ref: w.ref || '' },
      notesFromServer: w.notes || ''
    };
    g._hay = [g.name, g.email, g.city, g.state, g.formTitle, g.mobile].join(' ').toLowerCase();
    return g;
  }
  function toUser(u) { return { id: String(u.id), name: u.name, email: u.email || '', username: u.username || '', role: u.role, states: u.states || [], active: !!u.active, lastLoginAt: u.lastLoginAt || null }; }

  L.reload = function () {
    var perms = L.perms;
    function has(x) { return L.user && (L.user.role === 'admin' || perms.indexOf(x) >= 0); }
    return Promise.all([
      call('perms'),
      call(has('team.view') ? 'team.list' : 'team.names'),
      has('registrations.view') ? call('registrations') : Promise.resolve({ rows: [] }),
      has('pages.view') ? call('pages') : Promise.resolve({ pages: [] }),
      has('otp.view') ? call('otp') : Promise.resolve({ rows: [] }),
      has('team.view') ? call('audit') : Promise.resolve({ rows: [] })
    ]).then(function (res) {
      F.DEFAULT_PERMS = res[0].perms;
      F.data.users = res[1].users.map(toUser);
      F.data.forms = F.data.forms.filter(function (f) { return !f.noPage; });
      F.data.formsById = {}; F.data.forms.forEach(function (f) { F.data.formsById[f.id] = f; });
      F.data.regs = res[2].rows.map(toReg);
      var meta = {};
      res[3].pages.forEach(function (row) { meta[row.page_name] = row; });
      F.data.forms.forEach(function (f) { F.data.pageMeta[f.id] = meta[String(f.id)] || {}; });
      var now = Date.now();
      F.data.otps = res[4].rows.map(function (o) {
        var m = F.profile.splitMobile(o.mobile);
        return { id: o.id, cc: m.cc, mobile: m.mobile, formTitle: '', at: o.createdAt || o.expiresAt || 0, attempts: '–',
          status: o.verified ? 'verified' : (o.expiresAt && o.expiresAt < now ? 'expired' : 'sent') };
      });
      F.data.audit = res[5].rows.map(function (a) { return { at: a.at, userId: a.userId ? String(a.userId) : null, action: a.action, text: a.text }; });
      F.data.skippedTables = res[2].skipped || [];
      F.store.rebuild();
      F.data.regs.forEach(function (g) { if (g.notesFromServer) F.store._seedNote(g.id, g.notesFromServer); });
    });
  };

  /* ---------- real sign-in ---------- */
  function loginView(done) {
    var err = h('p', { class: 'form-err', role: 'alert' });
    var user = h('input', { class: 'input', id: 'lg-user', type: 'text', autocomplete: 'username', required: true, autofocus: true });
    var pass = h('input', { class: 'input', id: 'lg-pass', type: 'password', autocomplete: 'current-password', required: true });
    var btn = h('button', { class: 'btn btn-primary btn-lg btn-block', type: 'submit' }, 'Sign in');
    var form = h('form', { class: 'login-card', onsubmit: function (e) {
      e.preventDefault(); err.textContent = ''; btn.disabled = true;
      call('login', { username: user.value.trim(), password: pass.value }).then(function (d) {
        L.user = d.user; L.perms = d.perms; L.csrf = d.csrf; L.paymentTemplate = d.paymentTemplate || '';
        return L.reload();
      }).then(done, function (e2) { err.textContent = e2.message; btn.disabled = false; pass.value = ''; pass.focus(); });
    } },
      h('div', { class: 'login-brand' }, h('span', { class: 'brand-mark' }, icon('star', 22)), h('div', null, h('strong', null, 'FSIA Admin'), h('span', null, 'Forever Star India'))),
      h('h1', { class: 'login-title' }, 'Welcome back'),
      h('p', { class: 'login-sub' }, 'Sign in with your own team account.'),
      h('div', { class: 'field' }, h('label', { class: 'label', for: 'lg-user' }, 'User name or email'), user),
      h('div', { class: 'field' }, h('label', { class: 'label', for: 'lg-pass' }, 'Password'), pass),
      err, btn,
      h('p', { class: 'login-note' }, icon('lock', 16), h('span', null, 'Forgot your password? Ask a Super Admin to set a new one in Team & Access.')));
    return h('div', { class: 'login' }, h('div', { class: 'login-art', 'aria-hidden': 'true' }), form);
  }

  /* Resolves once the app knows which mode it is in and, in live mode, who is signed in. */
  F.boot = function (root, ready) {
    return call('me').then(function (d) {
      L.on = true; L.csrf = d.csrf; L.paymentTemplate = d.paymentTemplate || '';
      document.documentElement.setAttribute('data-mode', 'live');
      if (!d.user) {
        F.views.login = function (done) { return loginView(done); };
        return null;
      }
      L.user = d.user; L.perms = d.perms;
      return L.reload();
    }, function (e) {
      if (e.notApi || e.status === 404 || !e.status) return null; // no API here: preview mode with sample data
      L.on = true;
      document.documentElement.setAttribute('data-mode', 'live');
      F.fill(root, h('div', { class: 'empty big' }, icon('alert', 34), h('strong', null, 'The admin API is not answering'), h('span', { class: 'muted' }, e.message)));
      throw e;
    });
  };
})();
