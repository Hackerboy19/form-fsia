/* Preview-only sign-in. No credentials are read, sent or stored. */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;

  F.views.login = function (onDone) {
    var pw = h('input', { class: 'input', id: 'lg-pass', type: 'password', value: 'preview', autocomplete: 'off' });
    var toggle = h('button', { type: 'button', class: 'btn-icon field-end', 'aria-label': 'Show password', onclick: function () {
      var show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    } }, icon('eye'));
    var form = h('form', { class: 'login-card', onsubmit: function (e) { e.preventDefault(); F.auth.login(); onDone(); } },
      h('div', { class: 'login-brand' }, h('span', { class: 'brand-mark' }, icon('star', 22)), h('div', null, h('strong', null, 'FSIA Admin'), h('span', null, 'Forever Star India'))),
      h('h1', { class: 'login-title' }, 'Welcome back'),
      h('p', { class: 'login-sub' }, 'Sign in to manage registrations and form pages.'),
      h('div', { class: 'field' }, h('label', { class: 'label', for: 'lg-user' }, 'User name'),
        h('input', { class: 'input', id: 'lg-user', type: 'text', value: 'preview', autocomplete: 'off', autofocus: true })),
      h('div', { class: 'field' }, h('label', { class: 'label', for: 'lg-pass' }, 'Password'), h('div', { class: 'field-wrap' }, pw, toggle)),
      h('button', { class: 'btn btn-primary btn-lg btn-block', type: 'submit' }, 'Sign in'),
      h('p', { class: 'login-note' }, icon('info', 16), h('span', null, 'This is a design preview with sample data. There is no server, so any input signs you in.')));
    return h('div', { class: 'login' }, h('div', { class: 'login-art', 'aria-hidden': 'true' }), form);
  };
})();
