/* Data checks that run without a browser:  node tests/data.test.js
   1. Every field the forms collect, today or in earlier versions, survives normalisation (nothing is dropped).
   2. Broken values from old rows are flagged, never shown as "undefined" / "null" / "NaN".
   3. Roles and state scopes give each team member exactly the access they should have. */
'use strict';
const assert = require('assert');
const path = require('path');

global.window = global;
const memory = {};
global.localStorage = { getItem: (k) => (k in memory ? memory[k] : null), setItem: (k, v) => { memory[k] = String(v); }, removeItem: (k) => { delete memory[k]; } };
global.sessionStorage = { ...global.localStorage };
['core', 'profile', 'data', 'store'].forEach((f) => require(path.join(__dirname, '..', 'js', f + '.js')));
const F = global.FSIA, P = F.profile;
const NOW = Date.parse('2026-11-04T12:00:00Z');
let passed = 0;
function test(name, fn) { fn(); passed++; console.log('  ✓ ' + name); }

/* Field names read from the forms in this repository (current files and git history). */
const FORM_SHAPES = {
  'Miss state forms': { fname: 'Riya Sharma', mobile: '9876543210', email: 'riya@example.com', dob: '2003-04-05', age: '23', birthday: '5', birthmonth: '4', birthyear: '2003', city: 'Jaipur', state: 'Rajasthan', landing: 'Instagram' },
  'Mrs state forms': { fname: 'Meera Nair', mobile: '9876543211', email: 'meera@example.com', dob: '1990-01-15', age: '36', city: 'Kochi', state: 'Kerala', landing: 'Google', category: 'Classic', reg_type: 'Self' },
  'Channel Partner / Bharat Couture': { firstName: 'Rahul Verma', mobile: '9876543212', email: 'rahul@example.com', dob: '1995-06-01', age: '31', city: 'Pune', state: 'Maharashtra' },
  'Fashion Designer / Makeup Artist': { fname: 'Diya Das', mobile: '9876543213', email: 'diya@example.com', dob: '1999-02-02', age: '27', city: 'Kolkata', state: 'West Bengal', regtype: 'Designer' },
  'India Fashion Week': { fname: 'Sanya Kapoor', mobile: '9876543214', email: 'sanya@example.com', dob: '2001-09-09', age: '25', cityfsia: 'Delhi', state: 'Delhi', regtype: 'Model', category: 'Ramp', fsia_category: 'Fashion', fsia_model_sub: 'Runway', submit_reg: '1' },
  'Miss Universe': { fname: 'Zoya Khan', mobile: '9876543215', email: 'zoya@example.com', dob: '2004-12-12', age: '21', cityfsia: 'Lucknow', state: 'Uttar Pradesh', instagram: '@zoya', message: 'Hi', qualification: 'Graduate', skills: 'Dance', regtype: 'Self' },
  'Celebrity PR (older version)': { fname: 'Kavya Rao', mobile: '9876543216', email: 'kavya@example.com', dob: '1998-03-03', age: '28', city: 'Hyderabad', state: 'Telangana', password: 'secret', code: '123456', education: 'Graduate', totalmarks: '80%', instname: 'City College', brandname: 'Styling', workexpr: '3 years', address: 'Banjara Hills', kyc: ['front.jpg', 'back.jpg'], comment: 'Hello' },
  'A column nobody knew about': { fname: 'Test Person', mobile: '9876543217', email: 't@example.com', dob: '2000-01-01', city: 'Goa', state: 'Goa', new_column_2027: 'kept' }
};
const CORE = Object.keys(P.ALIASES).reduce((m, k) => (P.ALIASES[k].forEach((a) => { m[a] = k; }), m), {});

console.log('Profile fields');
Object.keys(FORM_SHAPES).forEach((shape) => test(shape + ': no field is lost', () => {
  const raw = FORM_SHAPES[shape], p = P.normalize(raw, { now: NOW });
  Object.keys(raw).forEach((k) => {
    if (P.PLUMBING[k]) return;                       // submit button / OTP digits: not profile data
    if (P.FOLDED[k]) { assert.ok(p.dob, shape + ': ' + k + ' should feed the date of birth'); return; }
    if (CORE[k]) { assert.notStrictEqual(p[CORE[k]], '', shape + ': ' + k + ' -> ' + CORE[k] + ' is empty'); return; }
    const e = p.extras.find((x) => x.key === k);
    assert.ok(e, shape + ': field "' + k + '" was dropped');
    assert.strictEqual(e.value, P.clean(raw[k]));
  });
  assert.deepStrictEqual(p.issues.map((i) => i.code), [], shape + ' should have no data problems');
}));

test('old secret and KYC fields are marked so the screen can hide them', () => {
  const p = P.normalize(FORM_SHAPES['Celebrity PR (older version)'], { now: NOW });
  assert.ok(p.extras.find((e) => e.key === 'password').secret);
  assert.ok(p.extras.find((e) => e.key === 'kyc').sensitive);
});

console.log('Broken values');
const BROKEN = [
  [{ fname: 'NULL', mobile: '98765', email: 'no-at-sign.com', dob: '0000-00-00', city: 'undefined', state: '' }, ['missing_name', 'bad_mobile', 'bad_email', 'bad_dob', 'missing_city', 'missing_state']],
  [{ fname: 'A B', mobile: '+91 98765 43210', email: 'a@b.co', dob: '05/04/2003', age: '30', city: 'X', state: 'Y' }, ['age_mismatch']],
  [{ fname: 'A B', mobile: '09876543210', email: 'a@b.co', dob: '2030-01-01', city: 'X', state: 'Y' }, ['bad_dob']],
  [{ fname: 'A B', email: '', city: 'X', state: 'Y' }, ['missing_mobile', 'missing_email', 'missing_dob']],
  [{ fname: 'A B', mobile: '9876543210', email: 'a@b.co', age: 'NaN', birthday: '31', birthmonth: 'Feb', birthyear: '2001', city: 'X', state: 'Y' }, ['bad_dob']]
];
BROKEN.forEach(([raw, codes], i) => test('broken row ' + (i + 1) + ' -> ' + codes.join(', '), () => {
  const p = P.normalize(raw, { now: NOW });
  assert.deepStrictEqual(p.issues.map((x) => x.code).sort(), codes.slice().sort());
  ['name', 'mobile', 'email', 'dob', 'city', 'state', 'source'].forEach((k) => {
    assert.strictEqual(typeof p[k], 'string', k);
    assert.ok(!/undefined|null|nan/i.test(p[k]), k + ' shows "' + p[k] + '"');
  });
  assert.ok(p.age === null || Number.isInteger(p.age), 'age is a whole number or empty');
}));
test('"+91 98765 43210" and "09876543210" both become 9876543210', () => {
  assert.strictEqual(P.mobileDigits('+91 98765 43210'), '9876543210');
  assert.strictEqual(P.mobileDigits('09876543210'), '9876543210');
});
test('payment link must be https', () => {
  assert.ok(P.validUrl('https://pay.example.com/x'));
  assert.ok(!P.validUrl('http://pay.example.com/x'));
  assert.ok(!P.validUrl('pay.example.com/x'));
  assert.deepStrictEqual(P.checkPayment({ status: 'sent', url: '' }, 'contacted').map((x) => x.code), ['bad_payment_url']);
  assert.deepStrictEqual(P.checkPayment({ status: 'none', url: '' }, 'paid').map((x) => x.code), ['paid_no_record']);
});

console.log('Sample data');
test('every sample registration keeps every raw field', () => {
  F.data.regs.forEach((g) => {
    Object.keys(g.raw).forEach((k) => {
      const v = P.clean(g.raw[k]);
      if (!v || P.PLUMBING[k] || P.FOLDED[k] || CORE[k]) return;
      assert.ok(g.extras.find((e) => e.key === k), '#' + g.id + ' lost ' + k);
    });
  });
});
test('no sample profile shows undefined / null / NaN', () => {
  F.data.regs.forEach((g) => {
    ['name', 'mobile', 'email', 'dob', 'city', 'state', 'source', 'formTitle'].forEach((k) => {
      assert.strictEqual(typeof g[k], 'string', '#' + g.id + ' ' + k);
      assert.ok(!/^(undefined|null|NaN)$/i.test(g[k]), '#' + g.id + ' ' + k + ' = ' + g[k]);
    });
    assert.ok(g.age === null || Number.isInteger(g.age), '#' + g.id + ' age');
    assert.strictEqual(F.fmt.val(''), 'Not provided');
  });
});
test('sample data covers old and new form versions and broken rows', () => {
  assert.ok(F.data.regs.some((g) => g.extras.some((e) => e.key === 'kyc')), 'old-form rows present');
  assert.ok(F.data.regs.some((g) => 'firstName' in g.raw), 'firstName rows present');
  assert.ok(F.data.regs.some((g) => 'cityfsia' in g.raw), 'cityfsia rows present');
  assert.ok(F.data.regs.some((g) => g.profileIssues.length), 'broken rows present');
});

console.log('Team access');
function as(id) { F.auth.login(id); }
test('Super Admin can do everything', () => { as('u1'); F.PERMS.forEach((p) => assert.ok(F.can(p.id), p.id)); });
test('Registration team cannot delete, export, confirm payments or manage the team', () => {
  as('u3');
  ['registrations.delete', 'registrations.export', 'payments.confirm', 'team.manage', 'team.view', 'pages.edit'].forEach((p) => assert.ok(!F.can(p), p));
  ['registrations.view', 'registrations.edit', 'payments.send'].forEach((p) => assert.ok(F.can(p), p));
});
test('Registration team only sees registrations from their states', () => {
  as('u3');
  const states = F.team.user('u3').states, mine = F.reg.all();
  assert.ok(mine.length > 0 && mine.every((g) => states.includes(g.state)));
  as('u1');
  assert.ok(F.reg.all().length > mine.length);
});
test('Content editor cannot see registrations', () => { as('u5'); assert.ok(!F.can('registrations.view')); assert.ok(F.can('pages.edit')); });
test('Accounts can confirm payments and download, but not delete', () => { as('u6'); assert.ok(F.can('payments.confirm') && F.can('registrations.export') && !F.can('registrations.delete')); });
test('a member whose access is off cannot sign in', () => { as('u7'); assert.strictEqual(F.auth.user(), null); });
test('Super Admin access cannot be removed', () => { as('u1'); F.team.setPerm('admin', 'team.manage', false); assert.ok(F.can('team.manage')); });
test('turning a permission off applies at once', () => {
  F.team.setPerm('caller', 'payments.send', false); as('u3'); assert.ok(!F.can('payments.send'));
  F.team.resetPerms(); assert.ok(F.can('payments.send'));
});
test('actions are written to the activity log with who did them', () => {
  as('u4'); const before = F.audit.all().length;
  F.audit.add('status', 'test entry');
  assert.strictEqual(F.audit.all().length, before + 1);
  assert.strictEqual(F.audit.all()[0].userId, 'u4');
});

console.log('\n' + passed + ' checks passed');
