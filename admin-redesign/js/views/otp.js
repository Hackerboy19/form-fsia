(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;
  var LABEL = { verified: 'Verified', expired: 'Expired', failed: 'Too many tries', sent: 'Code sent' };
  var CLS = { verified: 'ok', expired: 'warn', failed: 'bad', sent: 'info' };

  F.views.otp = {
    title: 'OTP Log',
    perm: 'otp.view',
    render: function () {
      var f = { q: '', status: '', page: 1, size: 25 };
      var root = h('div'), tbody = h('tbody'), foot = h('div'), kpis = h('div', { class: 'kpis three' });
      var all = F.data.otps, rows = [];

      function compute() {
        var q = f.q.replace(/\D/g, '');
        rows = all.filter(function (o) { return (!f.status || o.status === f.status) && (!q || o.mobile.indexOf(q) >= 0); });
        var pages = Math.max(1, Math.ceil(rows.length / f.size)); if (f.page > pages) f.page = pages;
      }
      function render() {
        compute();
        var day = Date.now() - 86400000, last = all.filter(function (o) { return o.at >= day; });
        var ver = last.filter(function (o) { return o.status === 'verified'; }).length;
        F.fill(kpis, 
          h('div', { class: 'card kpi' }, h('div', { class: 'kpi-top' }, h('span', { class: 'kpi-label' }, 'Codes sent (24h)'), h('span', { class: 'kpi-ico' }, icon('chat', 18))), h('div', { class: 'kpi-value' }, F.fmt.num(last.length))),
          h('div', { class: 'card kpi' }, h('div', { class: 'kpi-top' }, h('span', { class: 'kpi-label' }, 'Verified (24h)'), h('span', { class: 'kpi-ico' }, icon('shield', 18))), h('div', { class: 'kpi-value' }, last.length ? Math.round(ver / last.length * 100) + '%' : '–'), h('div', { class: 'kpi-sub' }, F.fmt.num(ver) + ' of ' + F.fmt.num(last.length))),
          h('div', { class: 'card kpi' }, h('div', { class: 'kpi-top' }, h('span', { class: 'kpi-label' }, 'Failed or expired (24h)'), h('span', { class: 'kpi-ico' }, icon('alert', 18))), h('div', { class: 'kpi-value' }, F.fmt.num(last.filter(function (o) { return o.status === 'failed' || o.status === 'expired'; }).length)), h('div', { class: 'kpi-sub' }, 'Candidates may need a manual call')));
        var page = rows.slice((f.page - 1) * f.size, f.page * f.size);
        F.fill(tbody, page.length ? page.map(function (o) {
          return h('tr', null,
            h('td', { 'data-label': 'Mobile' }, h('span', { class: 'mono' }, F.phone.show(o))),
            h('td', { 'data-label': 'Form' }, o.formTitle),
            h('td', { 'data-label': 'Requested' }, h('span', { title: F.fmt.dateTime(o.at) }, F.fmt.ago(o.at))),
            h('td', { 'data-label': 'Result' }, h('span', { class: 'badge ' + CLS[o.status] }, LABEL[o.status])),
            h('td', { 'data-label': 'Attempts' }, String(o.attempts)),
            h('td', { class: 'col-act' }, h('a', { class: 'btn-icon sm', href: F.phone.wa(o), target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'WhatsApp ' + F.phone.show(o) }, icon('chat', 15))));
        }) : h('tr', { class: 'empty-row' }, h('td', { colspan: 6 }, h('div', { class: 'empty' }, icon('search', 28), h('strong', null, 'No OTP records found')))));
        F.fill(foot, F.pager({ page: f.page, size: f.size, total: rows.length, onPage: function (p) { f.page = p; render(); }, onSize: function (s) { f.size = s; f.page = 1; render(); } }));
      }

      root.append(
        h('div', { class: 'page-head' }, h('div', null, h('h1', { class: 'page-title' }, 'OTP Log'), h('p', { class: 'page-sub' }, 'WhatsApp verification codes sent from the registration forms.'))),
        kpis,
        h('div', { class: 'card tbl-card' },
          h('div', { class: 'toolbar' },
            h('div', { class: 'search' }, icon('search', 16), h('input', { class: 'input has-ico', type: 'search', placeholder: 'Search by mobile number…', 'aria-label': 'Search by mobile number', 'data-search': '', oninput: F.debounce(function (e) { f.q = e.target.value; f.page = 1; render(); }, 150) })),
            h('div', { class: 'filters' }, h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'Result'),
              h('select', { class: 'select', onchange: function (e) { f.status = e.target.value; f.page = 1; render(); } }, [h('option', { value: '' }, 'All results')].concat(Object.keys(LABEL).map(function (k) { return h('option', { value: k }, LABEL[k]); })))))),
          h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive' }, h('thead', null, h('tr', null, ['Mobile', 'Form', 'Requested', 'Result', 'Attempts'].map(function (t) { return h('th', null, t); }), h('th', { class: 'col-act' }, h('span', { class: 'sr-only' }, 'Actions')))), tbody)),
          foot));
      render();
      return { el: root };
    }
  };
})();
