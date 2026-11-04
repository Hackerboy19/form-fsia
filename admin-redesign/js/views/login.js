/* Preview-only sign-in: pick a team member to see the panel exactly as they would.
   The live panel keeps a username and password per member (checked on the server). */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;

  F.views.login = function (onDone) {
    var users = F.team.users();
    var pick = (users.filter(function (u) { return u.active; })[0] || {}).id;
    var list = h('div', { class: 'who-list', role: 'radiogroup', 'aria-label': 'Sign in as' }, users.map(function (u) {
      var role = F.team.role(u.role);
      return h('label', { class: 'who-opt' + (u.active ? '' : ' is-off') },
        h('input', { type: 'radio', name: 'who', value: u.id, checked: u.id === pick, disabled: !u.active, onchange: function () { pick = u.id; } }),
        F.avatar(u.name, 34),
        h('span', { class: 'who-txt' }, h('strong', null, u.name), h('span', { class: 'muted small' }, role.label + (u.states.length ? ' · ' + u.states.join(', ') : '') + (u.active ? '' : ' · access turned off'))));
    }));
    var form = h('form', { class: 'login-card', onsubmit: function (e) {
      e.preventDefault(); if (!pick) return;
      F.auth.login(pick); F.audit.add('login', 'Signed in'); onDone();
    } },
      h('div', { class: 'login-brand' }, h('span', { class: 'brand-mark' }, icon('star', 22)), h('div', null, h('strong', null, 'FSIA Admin'), h('span', null, 'Forever Star India'))),
      h('h1', { class: 'login-title' }, 'Welcome back'),
      h('p', { class: 'login-sub' }, 'Choose who you are. Each team member sees only what their role allows.'),
      list,
      h('button', { class: 'btn btn-primary btn-lg btn-block', type: 'submit' }, 'Sign in'),
      h('p', { class: 'login-note' }, icon('info', 16), h('span', null, 'Design preview with sample data. In the live panel each member signs in with their own user name and password.')));
    return h('div', { class: 'login' }, h('div', { class: 'login-art', 'aria-hidden': 'true' }), form);
  };
})();
