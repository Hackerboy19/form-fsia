/* Core helpers: DOM builder, icons, formatting, toast, dialogs, pager. */
(function () {
  'use strict';
  var F = (window.FSIA = window.FSIA || {});
  F.views = {};

  /* ---------- DOM builder ---------- */
  function add(el, kids) {
    kids.forEach(function (k) {
      if (k == null || k === false) return;
      if (Array.isArray(k)) return add(el, k);
      el.append(k.nodeType ? k : document.createTextNode(String(k)));
    });
  }
  function h(tag, attrs) {
    var el = document.createElement(tag);
    var after = null;
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        var v = attrs[k];
        if (v == null || v === false) return;
        if (k === 'class') el.className = v;
        else if (k === 'text') el.textContent = v;
        else if (k === 'value') after = v;
        else if (k === 'style' && typeof v === 'object') {
          Object.keys(v).forEach(function (p) {
            if (p.indexOf('--') === 0) el.style.setProperty(p, v[p]); else el.style[p] = v[p];
          });
        } else if (k.slice(0, 2) === 'on' && typeof v === 'function') el.addEventListener(k.slice(2), v);
        else if (v === true) el.setAttribute(k, '');
        else el.setAttribute(k, v);
      });
    }
    add(el, Array.prototype.slice.call(arguments, 2));
    if (after !== null) el.value = after;
    return el;
  }
  function svg(tag, attrs) {
    var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
    Object.keys(attrs || {}).forEach(function (k) { el.setAttribute(k, attrs[k]); });
    add(el, Array.prototype.slice.call(arguments, 2));
    return el;
  }
  F.h = h; F.svg = svg;
  /* Replace an element's children. Unlike Element.replaceChildren it also accepts arrays and nulls. */
  F.fill = function (el) { el.replaceChildren(); add(el, Array.prototype.slice.call(arguments, 1)); return el; };
  F.$ = function (s, r) { return (r || document).querySelector(s); };
  F.$$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- Icons (24px stroke set) ---------- */
  var ICONS = {
    dashboard: '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    sliders: '<path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    left: '<path d="m15 18-6-6 6-6"/>',
    right: '<path d="m9 18 6-6-6-6"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
    moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
    trash: '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    chat: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    link: '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    eye: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
    undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/><path d="M12 9v4M12 17h.01"/>',
    trend: '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    refresh: '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M8 16H3v5"/>',
    copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
    star: '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    card: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    userplus: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
    help: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/>',
    edit: '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    team: '<path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/>'
  };
  F.icon = function (name, size) {
    var s = size || 18;
    var span = document.createElement('span');
    span.className = 'ico';
    span.setAttribute('aria-hidden', 'true');
    span.innerHTML = '<svg viewBox="0 0 24 24" width="' + s + '" height="' + s + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + (ICONS[name] || '') + '</svg>';
    return span;
  };

  /* ---------- Formatting ---------- */
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  function p2(n) { return n < 10 ? '0' + n : '' + n; }
  F.fmt = {
    date: function (ts) { if (!ts) return 'Date not saved'; var d = new Date(ts); return d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear(); },
    short: function (ts) { var d = new Date(ts); return d.getDate() + ' ' + MONTHS[d.getMonth()]; },
    time: function (ts) {
      var d = new Date(ts), hr = d.getHours(), ap = hr >= 12 ? 'PM' : 'AM';
      hr = hr % 12 || 12;
      return hr + ':' + p2(d.getMinutes()) + ' ' + ap;
    },
    dateTime: function (ts) { return ts ? F.fmt.date(ts) + ', ' + F.fmt.time(ts) : 'Date not saved'; },
    ago: function (ts) {
      if (!ts) return 'Date not saved';
      var s = (Date.now() - ts) / 1000;
      if (s < 60) return 'just now';
      if (s < 3600) return Math.floor(s / 60) + ' min ago';
      if (s < 86400) return Math.floor(s / 3600) + ' hr ago';
      if (s < 86400 * 30) { var d = Math.floor(s / 86400); return d + (d === 1 ? ' day ago' : ' days ago'); }
      return F.fmt.date(ts);
    },
    num: function (n) { return Number(n).toLocaleString('en-IN'); },
    ymd: function (ts) { var d = new Date(ts); return d.getFullYear() + '-' + p2(d.getMonth() + 1) + '-' + p2(d.getDate()); },
    phone: function (m) { return /^\d{10}$/.test(m || '') ? '+91 ' + m.slice(0, 5) + ' ' + m.slice(5) : (m || 'Not provided'); },
    money: function (n) { return '₹ ' + Number(n || 0).toLocaleString('en-IN'); },
    /* never print undefined / null / NaN */
    val: function (v) { return v == null || v === '' || (typeof v === 'number' && isNaN(v)) ? 'Not provided' : String(v); }
  };
  /* Phone helpers for a record with { cc, mobile }; cc defaults to India. */
  F.phone = {
    cc: function (g) { return (g && g.cc) || '91'; },
    valid: function (g) { return F.profile ? F.profile.validMobile(F.phone.cc(g), g.mobile || '') : /^\d{10}$/.test(g.mobile || ''); },
    show: function (g) {
      var m = (g && g.mobile) || '', cc = F.phone.cc(g);
      if (!m) return 'Not provided';
      return '+' + cc + ' ' + (cc === '91' && m.length === 10 ? m.slice(0, 5) + ' ' + m.slice(5) : m);
    },
    full: function (g) { return g.mobile ? '+' + F.phone.cc(g) + g.mobile : ''; },
    wa: function (g) { return 'https://wa.me/' + F.phone.cc(g) + g.mobile; },
    tel: function (g) { return 'tel:+' + F.phone.cc(g) + g.mobile; }
  };
  F.debounce = function (fn, ms) {
    var t;
    return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
  };
  F.csv = {
    cell: function (v) {
      var s = v == null ? '' : String(v);
      if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; // neutralise spreadsheet formulas
      return /[",\n\r]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    },
    download: function (name, rows) {
      var text = rows.map(function (r) { return r.map(F.csv.cell).join(','); }).join('\r\n');
      var blob = new Blob(['﻿' + text], { type: 'text/csv;charset=utf-8' });
      var url = URL.createObjectURL(blob);
      var a = h('a', { href: url, download: name });
      document.body.append(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
    }
  };

  /* ---------- Avatar ---------- */
  F.avatar = function (name, size) {
    var hash = 0, i;
    for (i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) % 360;
    var parts = name.trim().split(/\s+/);
    var ini = (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
    return h('span', { class: 'avatar', 'aria-hidden': 'true', style: { '--h': hash, '--s': (size || 36) + 'px' } }, ini);
  };

  /* ---------- Toast ---------- */
  F.toast = function (msg, opts) {
    opts = opts || {};
    var host = document.getElementById('toasts');
    if (!host) {
      host = h('div', { id: 'toasts', class: 'toasts', role: 'status', 'aria-live': 'polite' });
      document.body.append(host);
    }
    var t = h('div', { class: 'toast' + (opts.type ? ' ' + opts.type : '') }, h('span', { text: msg }));
    function close() { if (t.parentNode) t.remove(); }
    if (opts.action) {
      t.append(h('button', { type: 'button', class: 'toast-action', onclick: function () { opts.action.run(); close(); } }, opts.action.label));
    }
    host.append(t);
    setTimeout(close, opts.action ? 7000 : 3500);
  };

  /* ---------- Confirm dialog ---------- */
  F.confirm = function (o) {
    return new Promise(function (resolve) {
      var cancel = h('button', { value: 'cancel', class: 'btn', type: 'submit', autofocus: o.danger ? true : null }, 'Cancel');
      var ok = h('button', { value: 'ok', class: 'btn ' + (o.danger ? 'btn-danger' : 'btn-primary'), type: 'submit', autofocus: o.danger ? null : true }, o.confirmText || 'Confirm');
      var dlg = h('dialog', { class: 'modal', 'aria-labelledby': 'confirm-title' },
        h('form', { method: 'dialog' },
          h('h2', { id: 'confirm-title', class: 'modal-title' }, o.title),
          h('p', { class: 'modal-body' }, o.body),
          h('div', { class: 'modal-actions' }, cancel, ok)));
      dlg.addEventListener('close', function () { var v = dlg.returnValue === 'ok'; dlg.remove(); resolve(v); });
      document.body.append(dlg);
      dlg.showModal();
    });
  };

  /* ---------- Dialog with custom content; resolves with the button value ---------- */
  F.dialog = function (o) {
    return new Promise(function (resolve) {
      var dlg = h('dialog', { class: 'modal' + (o.wide ? ' wide' : ''), 'aria-labelledby': 'dlg-title' },
        h('form', { method: 'dialog', novalidate: true },
          h('h2', { id: 'dlg-title', class: 'modal-title' }, o.title),
          h('div', { class: 'modal-body' }, o.body),
          h('div', { class: 'modal-actions' }, (o.buttons || [['ok', 'OK', 'btn-primary']]).map(function (b) {
            return h('button', { value: b[0], class: 'btn ' + (b[2] || ''), type: 'submit' }, b[1]);
          }))));
      var form = dlg.querySelector('form');
      form.addEventListener('submit', function (e) {
        var v = e.submitter ? e.submitter.value : 'ok';
        if (v !== 'cancel' && o.validate && !o.validate()) { e.preventDefault(); return; }
      });
      dlg.addEventListener('close', function () { var v = dlg.returnValue || 'cancel'; dlg.remove(); resolve(v); });
      document.body.append(dlg);
      dlg.showModal();
    });
  };

  /* ---------- Pager ---------- */
  F.pager = function (o) {
    var pages = Math.max(1, Math.ceil(o.total / o.size));
    var cur = Math.min(o.page, pages);
    var nums = [], i;
    for (i = 1; i <= pages; i++) {
      if (i === 1 || i === pages || Math.abs(i - cur) <= 1) nums.push(i);
      else if (nums[nums.length - 1] !== '…') nums.push('…');
    }
    function btn(label, page, disabled, current, aria) {
      return h('button', { type: 'button', class: 'pg' + (current ? ' is-current' : ''), disabled: disabled, 'aria-label': aria, 'aria-current': current ? 'page' : null, onclick: function () { o.onPage(page); } }, label);
    }
    var from = o.total ? (cur - 1) * o.size + 1 : 0;
    var to = Math.min(o.total, cur * o.size);
    return h('div', { class: 'tbl-foot' },
      h('div', { class: 'tbl-count' }, 'Showing ', h('strong', null, F.fmt.num(from) + '–' + F.fmt.num(to)), ' of ', h('strong', null, F.fmt.num(o.total))),
      h('div', { class: 'pager', role: 'navigation', 'aria-label': 'Pagination' },
        btn(F.icon('left', 16), cur - 1, cur <= 1, false, 'Previous page'),
        nums.map(function (n) { return n === '…' ? h('span', { class: 'pg-gap', 'aria-hidden': 'true' }, '…') : btn(String(n), n, false, n === cur, 'Page ' + n); }),
        btn(F.icon('right', 16), cur + 1, cur >= pages, false, 'Next page')),
      h('label', { class: 'rows-sel' }, 'Rows',
        h('select', { class: 'select sm', 'aria-label': 'Rows per page', onchange: function (e) { o.onSize(+e.target.value); }, value: String(o.size) },
          [10, 25, 50, 100].map(function (n) { return h('option', { value: String(n) }, String(n)); }))));
  };

  /* ---------- Safe HTML for previews (journey note may contain markup) ---------- */
  F.sanitize = function (html) {
    var ok = { P: 1, BR: 1, B: 1, STRONG: 1, I: 1, EM: 1, UL: 1, OL: 1, LI: 1, A: 1, SPAN: 1 };
    var doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
    function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (c) {
        if (c.nodeType === 3) return;
        if (c.nodeType !== 1 || !ok[c.tagName]) { c.replaceWith(document.createTextNode(c.textContent || '')); return; }
        Array.prototype.slice.call(c.attributes).forEach(function (a) {
          var keep = c.tagName === 'A' && a.name === 'href' && /^https?:\/\//i.test(a.value);
          if (!keep) c.removeAttribute(a.name);
        });
        if (c.tagName === 'A') { c.setAttribute('target', '_blank'); c.setAttribute('rel', 'noopener noreferrer'); }
        walk(c);
      });
    }
    var root = doc.body.firstChild;
    walk(root);
    return root;
  };
})();
