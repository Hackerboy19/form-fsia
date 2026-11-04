/* Persistence for the preview. Edits live in this browser only (localStorage).
   In the live panel every rule here (permissions, scope, audit) must be enforced by the PHP backend. */
(function () {
  'use strict';
  var F = window.FSIA;
  var KEY = 'fsia-admin-preview-v2';
  var state = null, listeners = [], cache = null, cacheUid = null;

  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  function fresh() {
    return { status: {}, notes: {}, deleted: [], pages: {}, assign: {}, payments: {}, users: clone(F.data.users), perms: clone(F.DEFAULT_PERMS), audit: clone(F.data.audit) };
  }
  function load() {
    if (state) return state;
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(KEY)); } catch (e) {}
    state = fresh();
    if (saved && typeof saved === 'object') {
      ['status', 'notes', 'pages', 'assign', 'payments', 'perms'].forEach(function (k) { if (saved[k] && typeof saved[k] === 'object' && !Array.isArray(saved[k])) state[k] = saved[k]; });
      ['deleted', 'users', 'audit'].forEach(function (k) { if (Array.isArray(saved[k])) state[k] = saved[k]; });
    }
    state.perms.admin = F.DEFAULT_PERMS.admin.slice(); // Super Admin can never lose access
    return state;
  }
  function commit() {
    cache = null;
    try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
    listeners.forEach(function (fn) { fn(); });
  }
  function me() { return F.auth.user(); }

  F.audit = {
    add: function (action, text) {
      var s = load(), u = me();
      s.audit.unshift({ at: Date.now(), userId: u ? u.id : null, action: action, text: text });
      if (s.audit.length > 400) s.audit.length = 400;
      commit();
    },
    all: function () { return load().audit; }
  };

  F.store = {
    on: function (fn) { listeners.push(fn); return function () { listeners = listeners.filter(function (l) { return l !== fn; }); }; },
    setStatus: function (ids, status) {
      var s = load(), prev = {};
      [].concat(ids).forEach(function (id) { prev[id] = (F.reg.get(id) || {}).status; s.status[id] = status; });
      commit();
      return prev;
    },
    restoreStatus: function (prev) { var s = load(); Object.keys(prev).forEach(function (id) { s.status[id] = prev[id]; }); commit(); },
    setNotes: function (id, text) { var s = load(); if (text) s.notes[id] = text; else delete s.notes[id]; commit(); },
    setAssign: function (ids, userId) {
      var s = load(), prev = {};
      [].concat(ids).forEach(function (id) { prev[id] = (F.reg.get(id) || {}).assignedTo || null; s.assign[id] = userId || null; });
      commit();
      return prev;
    },
    restoreAssign: function (prev) { var s = load(); Object.keys(prev).forEach(function (id) { s.assign[id] = prev[id]; }); commit(); },
    setPayment: function (id, patch) {
      var s = load(), cur = (F.reg.get(id) || {}).payment || {};
      s.payments[id] = Object.assign({}, cur, patch);
      commit();
    },
    remove: function (ids) { var s = load(); [].concat(ids).forEach(function (id) { if (s.deleted.indexOf(id) < 0) s.deleted.push(id); }); commit(); },
    restore: function (ids) { var s = load(); ids = [].concat(ids); s.deleted = s.deleted.filter(function (d) { return ids.indexOf(d) < 0; }); commit(); },
    page: function (id) { var s = load(); return Object.assign({}, F.data.pageMeta[id], s.pages[id] || {}); },
    savePage: function (id, data) {
      var s = load();
      s.pages[id] = {};
      F.data.metaKeys.forEach(function (k) { s.pages[id][k] = data[k] == null ? '' : String(data[k]); });
      commit();
    },
    reset: function () { state = fresh(); commit(); }
  };

  /* ---------- team & access ---------- */
  F.team = {
    users: function () { return load().users; },
    user: function (id) { return load().users.filter(function (u) { return u.id === id; })[0] || null; },
    role: function (id) { return F.ROLES.filter(function (r) { return r.id === id; })[0] || F.ROLES[F.ROLES.length - 1]; },
    perms: function (roleId) { return load().perms[roleId] || []; },
    activeAdmins: function () { return load().users.filter(function (u) { return u.role === 'admin' && u.active; }).length; },
    add: function (u) { var s = load(); u.id = 'u' + Date.now().toString(36); s.users.push(u); commit(); return u; },
    update: function (id, patch) { var u = F.team.user(id); if (u) { Object.assign(u, patch); commit(); } },
    remove: function (id) { var s = load(); s.users = s.users.filter(function (u) { return u.id !== id; }); commit(); },
    setPerm: function (roleId, perm, on) {
      if (roleId === 'admin') return;
      var s = load(), list = s.perms[roleId] || (s.perms[roleId] = []);
      var i = list.indexOf(perm);
      if (on && i < 0) list.push(perm); else if (!on && i >= 0) list.splice(i, 1);
      commit();
    },
    resetPerms: function () { load().perms = clone(F.DEFAULT_PERMS); commit(); }
  };
  F.can = function (perm) { var u = me(); return !!u && F.team.perms(u.role).indexOf(perm) >= 0; };
  F.scope = function () { var u = me(); return u && u.states && u.states.length ? u.states : null; };

  /* Registrations with local edits applied, limited to the signed-in member's states. */
  F.reg = {
    all: function () {
      var u = me(), uid = u ? u.id + ':' + (u.states || []).join(',') : '';
      if (cache && cacheUid === uid) return cache;
      var s = load(), scope = F.scope();
      cache = F.data.regs.filter(function (g) {
        return s.deleted.indexOf(g.id) < 0 && (!scope || scope.indexOf(g.state) >= 0);
      }).map(function (g) {
        var r = Object.assign({}, g, {
          status: s.status[g.id] || g.status,
          notes: s.notes[g.id] || '',
          assignedTo: Object.prototype.hasOwnProperty.call(s.assign, g.id) ? s.assign[g.id] : g.assignedTo,
          payment: Object.assign({}, g.payment, s.payments[g.id] || {})
        });
        r.issues = g.profileIssues.concat(F.profile.checkPayment(r.payment, r.status));
        return r;
      });
      cacheUid = uid;
      return cache;
    },
    get: function (id) { return F.reg.all().filter(function (g) { return g.id === id; })[0]; }
  };

  /* Preview-only session: choosing a team member signs in as them. No password is checked. */
  var memUid = null;
  F.auth = {
    user: function () {
      var id = memUid;
      try { id = sessionStorage.getItem('fsia-uid') || memUid; } catch (e) {}
      var u = id ? F.team.user(id) : null;
      return u && u.active ? u : null;
    },
    isIn: function () { return !!F.auth.user(); },
    login: function (id) { memUid = id; try { sessionStorage.setItem('fsia-uid', id); } catch (e) {} cache = null; },
    logout: function () { memUid = null; try { sessionStorage.removeItem('fsia-uid'); } catch (e) {} cache = null; }
  };
  F.prefs = {
    get: function (k, d) { try { return localStorage.getItem('fsia-' + k) || d; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem('fsia-' + k, v); } catch (e) {} }
  };
})();
