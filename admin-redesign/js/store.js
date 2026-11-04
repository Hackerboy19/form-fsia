/* Persistence for the preview. Edits live in this browser only (localStorage). */
(function () {
  'use strict';
  var F = window.FSIA;
  var KEY = 'fsia-admin-preview-v1';
  var state = null, listeners = [], cache = null;

  function load() {
    if (state) return state;
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(KEY)); } catch (e) {}
    state = { status: {}, notes: {}, deleted: [], pages: {} };
    if (saved && typeof saved === 'object') {
      ['status', 'notes', 'pages'].forEach(function (k) { if (saved[k] && typeof saved[k] === 'object') state[k] = saved[k]; });
      if (Array.isArray(saved.deleted)) state.deleted = saved.deleted;
    }
    return state;
  }
  function commit() {
    cache = null;
    try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
    listeners.forEach(function (fn) { fn(); });
  }

  F.store = {
    on: function (fn) { listeners.push(fn); return function () { listeners = listeners.filter(function (l) { return l !== fn; }); }; },
    setStatus: function (ids, status) {
      var s = load();
      var prev = {};
      [].concat(ids).forEach(function (id) { prev[id] = (F.reg.get(id) || {}).status; s.status[id] = status; });
      commit();
      return prev;
    },
    restoreStatus: function (prev) {
      var s = load();
      Object.keys(prev).forEach(function (id) { s.status[id] = prev[id]; });
      commit();
    },
    setNotes: function (id, text) { var s = load(); if (text) s.notes[id] = text; else delete s.notes[id]; commit(); },
    remove: function (ids) {
      var s = load();
      [].concat(ids).forEach(function (id) { if (s.deleted.indexOf(id) < 0) s.deleted.push(id); });
      commit();
    },
    restore: function (ids) {
      var s = load(); ids = [].concat(ids);
      s.deleted = s.deleted.filter(function (d) { return ids.indexOf(d) < 0; });
      commit();
    },
    page: function (id) { var s = load(); return Object.assign({}, F.data.pageMeta[id], s.pages[id] || {}); },
    savePage: function (id, data) {
      var s = load();
      s.pages[id] = {};
      F.data.metaKeys.forEach(function (k) { s.pages[id][k] = data[k] == null ? '' : String(data[k]); });
      commit();
    },
    isEdited: function (id) { return !!load().pages[id]; },
    reset: function () { state = { status: {}, notes: {}, deleted: [], pages: {} }; commit(); }
  };

  /* Registrations with the user's local edits applied. */
  F.reg = {
    all: function () {
      if (cache) return cache;
      var s = load();
      cache = F.data.regs.filter(function (g) { return s.deleted.indexOf(g.id) < 0; }).map(function (g) {
        return Object.assign({}, g, { status: s.status[g.id] || g.status, notes: s.notes[g.id] || '' });
      });
      return cache;
    },
    get: function (id) { return F.reg.all().filter(function (g) { return g.id === id; })[0]; }
  };

  /* Preview-only session flag; there is no real authentication here. */
  var memAuth = false;
  F.auth = {
    isIn: function () { try { return sessionStorage.getItem('fsia-in') === '1'; } catch (e) { return memAuth; } },
    login: function () { memAuth = true; try { sessionStorage.setItem('fsia-in', '1'); } catch (e) {} },
    logout: function () { memAuth = false; try { sessionStorage.removeItem('fsia-in'); } catch (e) {} }
  };
  F.prefs = {
    get: function (k, d) { try { return localStorage.getItem('fsia-' + k) || d; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem('fsia-' + k, v); } catch (e) {} }
  };
})();
