/* Team & Access: who is on the team, what each role may do, and what everyone did. */
(function () {
  'use strict';
  var F = window.FSIA, h = F.h, icon = F.icon;
  var tab = 'members';
  var ACTIONS = { status: 'Status changes', assign: 'Assignments', payment: 'Payments', share: 'Links shared', page: 'Page edits', export: 'Downloads', delete: 'Deletions', team: 'Team & access', login: 'Sign-ins' };

  function areaText(u) { return u.states.length ? u.states.join(', ') : 'All states'; }

  function memberDialog(u) {
    var isNew = !u;
    u = u || { name: '', email: '', role: 'caller', states: [], active: true };
    var err = h('p', { class: 'form-err', role: 'alert' });
    var name = h('input', { class: 'input', type: 'text', value: u.name, required: true, autocomplete: 'off' });
    var email = h('input', { class: 'input', type: 'email', value: u.email, required: true, autocomplete: 'off' });
    var username = h('input', { class: 'input', type: 'text', value: u.username || '', autocomplete: 'off', placeholder: 'e.g. neha.singh' });
    var password = h('input', { class: 'input', type: 'password', autocomplete: 'new-password', placeholder: isNew ? 'At least 10 characters' : 'Leave empty to keep the current one' });
    var roleDesc = h('p', { class: 'hint' }, F.team.role(u.role).desc);
    var role = h('select', { class: 'select', value: u.role, onchange: function () { roleDesc.textContent = F.team.role(role.value).desc; } }, F.ROLES.map(function (r) { return h('option', { value: r.id }, r.label); }));
    var some = h('input', { type: 'radio', name: 'area', value: 'some', checked: u.states.length > 0 });
    var allR = h('input', { type: 'radio', name: 'area', value: 'all', checked: !u.states.length });
    var grid = h('div', { class: 'state-grid', hidden: !u.states.length }, F.STATE_NAMES.map(function (s) {
      return h('label', { class: 'check' }, h('input', { type: 'checkbox', value: s, checked: u.states.indexOf(s) >= 0 }), s);
    }));
    [some, allR].forEach(function (r) { r.addEventListener('change', function () { grid.hidden = !some.checked; }); });
    var body = h('div', { class: 'stackf' },
      h('label', { class: 'field' }, h('span', { class: 'label' }, 'Full name'), name),
      h('label', { class: 'field' }, h('span', { class: 'label' }, 'Email'), email),
      F.live.on ? h('div', { class: 'row2' }, h('label', { class: 'field' }, h('span', { class: 'label' }, 'User name'), username), h('label', { class: 'field' }, h('span', { class: 'label' }, isNew ? 'Password' : 'New password'), password)) : null,
      h('label', { class: 'field' }, h('span', { class: 'label' }, 'Role'), role, roleDesc),
      h('fieldset', { class: 'field plain' }, h('legend', { class: 'label' }, 'Which registrations can they work on?'),
        h('label', { class: 'check' }, allR, 'All states'), h('label', { class: 'check' }, some, 'Only some states'), grid),
      err);
    function read() {
      var states = some.checked ? F.$$('input[type=checkbox]:checked', grid).map(function (c) { return c.value; }) : [];
      return { name: name.value.trim(), email: email.value.trim().toLowerCase(), role: role.value, states: states, username: username.value.trim(), password: password.value };
    }
    return F.dialog({ title: isNew ? 'Add a team member' : 'Edit ' + u.name, body: body, wide: true,
      buttons: [['cancel', 'Cancel', ''], ['ok', isNew ? 'Add member' : 'Save', 'btn-primary']],
      validate: function () {
        var v = read();
        if (!v.name) { err.textContent = 'Enter their full name.'; return false; }
        if (!F.profile.validEmail(v.email)) { err.textContent = 'Enter a valid email, like name@example.com.'; return false; }
        if (F.team.users().some(function (x) { return x.email === v.email && x.id !== u.id; })) { err.textContent = 'Someone on the team already uses this email.'; return false; }
        if (F.live.on && v.username && !/^[A-Za-z0-9._@-]{3,60}$/.test(v.username)) { err.textContent = 'The user name can use letters, numbers and . _ - @ only.'; return false; }
        if (F.live.on && (isNew ? v.password.length < 10 : v.password && v.password.length < 10)) { err.textContent = 'Passwords need at least 10 characters.'; return false; }
        if (some.checked && !v.states.length) { err.textContent = 'Pick at least one state, or choose “All states”.'; return false; }
        if (!isNew && u.role === 'admin' && v.role !== 'admin' && F.team.activeAdmins() <= 1) { err.textContent = 'Keep at least one Super Admin.'; return false; }
        return true;
      } }).then(function (v) { return v === 'ok' ? read() : null; });
  }

  F.views.team = {
    title: 'Team & Access',
    perm: 'team.view',
    render: function () {
      var root = h('div', { class: 'view-team' }), body = h('div');
      var manage = F.can('team.manage'), me = F.auth.user();

      function tabs() {
        return h('div', { class: 'tabs', role: 'tablist', 'aria-label': 'Team sections' }, [['members', 'Team members'], ['roles', 'Roles & access'], ['log', 'Activity log']].concat(F.live.on && manage ? [['db', 'Database check']] : []).map(function (t) {
          return h('button', { type: 'button', role: 'tab', class: 'tab' + (tab === t[0] ? ' is-on' : ''), 'aria-selected': tab === t[0] ? 'true' : 'false', onclick: function () { tab = t[0]; render(); } }, t[1]);
        }));
      }

      function members() {
        var users = F.team.users();
        return h('div', null,
          h('div', { class: 'section-head' }, h('p', { class: 'muted' }, users.filter(function (u) { return u.active; }).length + ' active members. Each member signs in with their own account, so the activity log shows who did what.'),
            manage ? h('button', { type: 'button', class: 'btn btn-primary', onclick: function () {
              memberDialog(null).then(function (v) { if (!v) return; v.active = true; F.team.add(v); F.audit.add('team', 'Added ' + v.name + ' as ' + F.team.role(v.role).label); F.toast(v.name + ' added'); render(); });
            } }, icon('userplus', 16), 'Add team member') : null),
          h('div', { class: 'card tbl-card' }, h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive static' },
            h('thead', null, h('tr', null, ['Member', 'Role', 'Works on', 'Access', ''].map(function (t) { return h('th', null, t); }))),
            h('tbody', null, users.map(function (u) {
              var self = me && me.id === u.id, lastAdmin = u.role === 'admin' && u.active && F.team.activeAdmins() <= 1;
              return h('tr', null,
                h('td', { 'data-label': 'Member', class: 'cell-main' }, h('div', { class: 'who' }, F.avatar(u.name, 34), h('div', null, h('strong', null, u.name + (self ? ' (you)' : '')), h('div', { class: 'muted small' }, u.email)))),
                h('td', { 'data-label': 'Role' }, h('strong', null, F.team.role(u.role).label), h('div', { class: 'muted small role-desc' }, F.team.role(u.role).desc)),
                h('td', { 'data-label': 'Works on' }, areaText(u)),
                h('td', { 'data-label': 'Access' }, manage && !self && !lastAdmin ? h('label', { class: 'switch' }, h('input', { type: 'checkbox', checked: u.active, onchange: function (e) {
                  F.team.update(u.id, { active: e.target.checked }); F.audit.add('team', (e.target.checked ? 'Turned on access for ' : 'Turned off access for ') + u.name); render();
                } }), h('span', null, u.active ? 'On' : 'Off')) : h('span', { class: 'badge ' + (u.active ? 'ok' : 'plain') }, u.active ? 'On' : 'Off')),
                h('td', { class: 'col-act' }, manage ? h('div', { class: 'row-actions' },
                  h('button', { type: 'button', class: 'btn-icon sm', 'aria-label': 'Edit ' + u.name, title: 'Edit', onclick: function () {
                    memberDialog(u).then(function (v) {
                      if (!v) return;
                      var roleChanged = v.role !== u.role;
                      F.team.update(u.id, v); F.audit.add('team', roleChanged ? 'Changed the role of ' + v.name + ' to ' + F.team.role(v.role).label : 'Edited ' + v.name); F.toast('Saved'); render();
                    });
                  } }, icon('edit', 15)),
                  !self && !lastAdmin ? h('button', { type: 'button', class: 'btn-icon sm', 'aria-label': 'Remove ' + u.name, title: 'Remove', onclick: function () {
                    F.confirm({ title: 'Remove ' + u.name + '?', body: 'They will no longer be able to sign in. Their past activity stays in the log.', confirmText: 'Remove', danger: true }).then(function (ok) {
                      if (ok) { F.team.remove(u.id); F.audit.add('team', 'Removed ' + u.name + ' from the team'); render(); }
                    });
                  } }, icon('trash', 15)) : null) : null));
            }))))));
      }

      function roles() {
        var groups = [];
        F.PERMS.forEach(function (p) { if (groups.indexOf(p.group) < 0) groups.push(p.group); });
        return h('div', null,
          h('div', { class: 'section-head' }, h('p', { class: 'muted' }, manage ? 'Tick what each role may do. Changes apply at once to everyone with that role. Super Admin always has everything.' : 'What each role may do. Only a Super Admin can change this.'),
            manage ? h('button', { type: 'button', class: 'btn btn-sm', onclick: function () {
              F.confirm({ title: 'Go back to the default access?', body: 'Every role gets its original permissions back.', confirmText: 'Reset' }).then(function (ok) { if (ok) { F.team.resetPerms(); F.audit.add('team', 'Reset all roles to the default access'); render(); } });
            } }, icon('refresh', 15), 'Reset to default') : null),
          h('div', { class: 'card tbl-card' }, h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl matrix' },
            h('thead', null, h('tr', null, h('th', null, 'What they can do'), F.ROLES.map(function (r) { return h('th', { title: r.desc }, r.label); }))),
            h('tbody', null, groups.map(function (gName) {
              return [h('tr', { class: 'mx-group' }, h('td', { colspan: F.ROLES.length + 1 }, gName))].concat(F.PERMS.filter(function (p) { return p.group === gName; }).map(function (p) {
                return h('tr', null, h('td', null, p.label), F.ROLES.map(function (r) {
                  var on = F.team.perms(r.id).indexOf(p.id) >= 0;
                  if (r.locked) return h('td', { class: 'mx-cell' }, h('span', { class: 'ok-text small mx-always', 'aria-label': r.label + ': ' + p.label + ', always allowed' }, '✓ Always'));
                  return h('td', { class: 'mx-cell' }, h('input', { type: 'checkbox', checked: on, disabled: !manage || r.locked, 'aria-label': r.label + ': ' + p.label, onchange: function (e) {
                    F.team.setPerm(r.id, p.id, e.target.checked);
                    F.audit.add('team', (e.target.checked ? 'Allowed ' : 'Stopped ') + r.label + ' to: ' + p.label.toLowerCase());
                  } }));
                }));
              }));
            }))))),
          h('div', { class: 'role-cards' }, F.ROLES.map(function (r) {
            return h('div', { class: 'card role-card' }, h('strong', null, r.label), h('p', { class: 'muted small' }, r.desc), h('span', { class: 'tab-n' }, F.team.users().filter(function (u) { return u.role === r.id; }).length + ' members'));
          })));
      }

      var logF = { who: '', action: '' };
      function log() {
        var list = F.audit.all().filter(function (a) { return (!logF.who || a.userId === logF.who) && (!logF.action || a.action === logF.action); });
        var tbody = h('tbody', null, list.slice(0, 200).map(function (a) {
          var u = a.userId && F.team.user(a.userId);
          return h('tr', null,
            h('td', { 'data-label': 'When' }, h('span', { title: F.fmt.dateTime(a.at) }, F.fmt.ago(a.at))),
            h('td', { 'data-label': 'Who', class: 'cell-main' }, h('div', { class: 'who' }, F.avatar(u ? u.name : 'Removed member', 28), u ? u.name : 'Removed member')),
            h('td', { 'data-label': 'What happened' }, a.text),
            h('td', { 'data-label': 'Type' }, h('span', { class: 'badge plain' }, ACTIONS[a.action] || a.action)));
        }));
        return h('div', null,
          h('div', { class: 'filters open log-filters' },
            h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'Person'), h('select', { class: 'select', value: logF.who, onchange: function (e) { logF.who = e.target.value; F.fill(body, log()); } },
              [h('option', { value: '' }, 'Everyone')].concat(F.team.users().map(function (u) { return h('option', { value: u.id }, u.name); })))),
            h('label', { class: 'fsel' }, h('span', { class: 'fsel-label' }, 'Type'), h('select', { class: 'select', value: logF.action, onchange: function (e) { logF.action = e.target.value; F.fill(body, log()); } },
              [h('option', { value: '' }, 'Everything')].concat(Object.keys(ACTIONS).map(function (k) { return h('option', { value: k }, ACTIONS[k]); })))),
            h('span', { class: 'muted small' }, list.length + ' entries')),
          h('div', { class: 'card tbl-card' }, h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive static' },
            h('thead', null, h('tr', null, ['When', 'Who', 'What happened', 'Type'].map(function (t) { return h('th', null, t); }))),
            list.length ? tbody : h('tbody', null, h('tr', { class: 'empty-row' }, h('td', { colspan: 4 }, h('div', { class: 'empty' }, h('strong', null, 'Nothing here yet')))))))));
      }

      /* Live only: which tables the API reads, and what it found in them. */
      function dbCheck() {
        var box = h('div', null, h('p', { class: 'muted' }, 'Checking the database…'));
        F.live.call('db.check').then(function (d) {
          F.fill(box,
            h('p', { class: 'muted section-head' }, 'These are the tables the panel reads registrations from (set in api/config.php). Every column is read, so new form fields show up on their own.'),
            h('div', { class: 'card tbl-card' }, h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl responsive static' },
              h('thead', null, h('tr', null, ['Table', 'Found', 'Rows', 'Id / date column', 'Columns'].map(function (t) { return h('th', null, t); }))),
              h('tbody', null, d.sources.map(function (s) {
                return h('tr', null, h('td', { 'data-label': 'Table' }, h('strong', null, s.table)),
                  h('td', { 'data-label': 'Found' }, h('span', { class: 'badge ' + (s.exists ? 'ok' : 'bad') }, s.exists ? 'Yes' : 'Not in database')),
                  h('td', { 'data-label': 'Rows' }, F.fmt.num(s.rows)),
                  h('td', { 'data-label': 'Id / date column' }, (s.idColumn || 'none found') + ' / ' + (s.dateColumn || 'none found'), s.hasState ? null : h('div', { class: 'small warn-text' }, 'No state column: members limited to states will not see these rows')),
                  h('td', { 'data-label': 'Columns', class: 'small' }, s.columns.join(', ')));
              }))))),
            d.otherTables.length ? h('p', { class: 'hint section-head' }, 'Other tables in the database, not read by the panel: ' + d.otherTables.join(', ') + '. If one of them holds form registrations, add it to sources in api/config.php.') : null);
        }, function (e) { F.fill(box, h('p', { class: 'form-err' }, e.message)); });
        return box;
      }

      function render() {
        F.fill(root,
          h('div', { class: 'page-head' }, h('div', null, h('h1', { class: 'page-title' }, 'Team & Access'), h('p', { class: 'page-sub' }, 'Give each person their own sign-in and only the tools they need.'))),
          tabs(), body);
        F.fill(body, tab === 'members' ? members() : tab === 'roles' ? roles() : tab === 'db' ? dbCheck() : log());
      }
      render();
      var off = F.store.on(function () { if (tab !== 'db') render(); });
      return { el: root, destroy: off };
    }
  };
})();
