(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;

  F.views.settings = {
    title: 'Settings',
    render: function () {
      function radio(name, current, opts, onPick) {
        return h('div', { class: 'seg', role: 'radiogroup', 'aria-label': name }, opts.map(function (o) {
          return h('button', { type: 'button', role: 'radio', class: 'seg-btn' + (o[0] === current ? ' is-on' : ''), 'aria-checked': o[0] === current ? 'true' : 'false', onclick: function (e) {
            F.$$('.seg-btn', e.currentTarget.parentNode).forEach(function (b) { var on = b === e.currentTarget; b.classList.toggle('is-on', on); b.setAttribute('aria-checked', on); });
            onPick(o[0]);
          } }, o[1]);
        }));
      }
      function row(title, desc, control) { return h('div', { class: 'set-row' }, h('div', null, h('strong', null, title), h('p', { class: 'muted small' }, desc)), h('div', null, control)); }
      var keys = [['Ctrl / ⌘ + K', 'Open quick search'], ['/', 'Focus the search box on a page'], ['← →', 'Previous / next registration in the detail panel'], ['Esc', 'Close panels and dialogs'], ['Ctrl / ⌘ + S', 'Save the page you are editing']];
      return { el: h('div', { class: 'view-settings' },
        h('div', { class: 'page-head' }, h('div', null, h('h1', { class: 'page-title' }, 'Settings'), h('p', { class: 'page-sub' }, 'Make the panel comfortable for you.'))),
        h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, 'Appearance')), h('div', { class: 'card-body' },
          row('Theme', 'Match your device or pick one.', radio('Theme', F.prefs.get('theme', 'system'), [['system', 'System'], ['light', 'Light'], ['dark', 'Dark']], function (v) { F.applyTheme(v); })),
          row('Table density', 'Compact fits more rows on screen.', radio('Density', F.prefs.get('density', 'comfortable'), [['comfortable', 'Comfortable'], ['compact', 'Compact']], function (v) { F.prefs.set('density', v); document.documentElement.setAttribute('data-density', v); })))),
        h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, 'Keyboard shortcuts')), h('div', { class: 'card-body' },
          h('dl', { class: 'keys' }, keys.map(function (k) { return h('div', null, h('dt', null, h('kbd', null, k[0])), h('dd', null, k[1])); })))),
        h('section', { class: 'card' }, h('div', { class: 'card-head' }, h('h2', null, F.live.on ? 'Account' : 'Preview data')), h('div', { class: 'card-body' },
          F.live.on ? null : row('Reset sample data', 'Clears every change made in this preview: statuses, notes, payments, team members, access and the activity log.', h('button', { type: 'button', class: 'btn btn-danger-ghost', onclick: function () {
            F.confirm({ title: 'Reset all sample data?', body: 'Every change you made in this preview will be removed.', confirmText: 'Reset', danger: true }).then(function (ok) { if (ok) { F.store.reset(); F.toast('Sample data reset'); } });
          } }, icon('refresh', 16), 'Reset')),
          row('Sign out', 'Return to the sign-in screen.', h('button', { type: 'button', class: 'btn', onclick: function () { F.signOut(); } }, icon('logout', 16), 'Sign out'))))) };
    }
  };
})();
