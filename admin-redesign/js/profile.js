/* Turns a raw database row (from an old or a new version of a form) into one predictable profile.
   Nothing is dropped: every raw key becomes a core field, an "extra" (shown under older-form details)
   or one of the documented ignores below. Broken values never reach the screen as "undefined" or "null". */
(function (root) {
  'use strict';
  var F = (root.FSIA = root.FSIA || {});

  /* Field names collected by the forms in this repository, current and earlier versions. */
  var ALIASES = {
    name: ['fname', 'firstName', 'first_name', 'fullname', 'name'],
    mobile: ['mobile', 'phone', 'mob', 'contact'],
    email: ['email', 'emailid', 'email_id'],
    dob: ['dob'],
    age: ['age'],
    city: ['city', 'cityfsia'],
    state: ['state'],
    country: ['country'],
    source: ['landing', 'fsia_source']
  };
  var PHONE_PARTS = ['ccode', 'country_code', 'mobile_local']; // Miss World: country code + local number
  var FOLDED = { birthday: 1, birthmonth: 1, birthyear: 1 }; // merged into dob when dob itself is empty
  var PLUMBING = { submit_reg: 1, code: 1 };                  // the submit button and the one-time OTP digits
  var EXTRA = {                                                // [label, sensitive, secret]
    education: ['Highest qualification'], totalmarks: ['Total marks'], instname: ['Institute'], brandname: ['Skill'],
    workexpr: ['Work experience'], address: ['Address'], comment: ['About the applicant'], instagram: ['Instagram'],
    message: ['Message'], qualification: ['Qualification'], skills: ['Skills'], regtype: ['Registration type'],
    reg_type: ['Registration type'], category: ['Category'], fsia_category: ['FSIA category'], fsia_model_sub: ['Model sub-category'],
    ncategory: ['Applied for'], kyc: ['KYC documents', true], password: ['Password', true, true]
  };
  var MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

  /* [what is wrong, how to fix it], in plain words for the team */
  var ISSUES = {
    missing_name: ['Name is missing', 'Open the original form entry and add the name.'],
    missing_mobile: ['Mobile number is missing', 'Ask the applicant for a number. Without it you cannot call or use WhatsApp.'],
    bad_mobile: ['Mobile number looks wrong', 'An Indian number has 10 digits and starts with 6, 7, 8 or 9. A number from another country needs its country code. Check with the applicant.'],
    missing_email: ['Email is missing', 'Ask the applicant for an email address.'],
    bad_email: ['Email looks wrong', 'It should look like name@example.com. Check for typos.'],
    missing_dob: ['Date of birth is missing', 'Ask the applicant for their date of birth.'],
    bad_dob: ['Date of birth cannot be read', 'The saved date is not a real date (for example 0000-00-00). Ask the applicant.'],
    age_mismatch: ['Age does not match the date of birth', 'One of the two is wrong. Check the date of birth with the applicant.'],
    missing_city: ['City is missing', 'Ask the applicant which city they live in.'],
    missing_state: ['State is missing', 'Ask the applicant which state they live in.'],
    bad_payment_url: ['Payment link is missing or not valid', 'A payment link must start with https://. Create the link again.'],
    paid_no_record: ['Marked as fee paid, but no payment is recorded', 'Open Offer & payment and record the payment, or change the status.']
  };

  function clean(v) {
    if (v == null) return '';
    if (Array.isArray(v)) v = v.join(', ');
    v = String(v).replace(/\s+/g, ' ').trim();
    return /^(null|undefined|nan|none|n\/a|0000-00-00( 00:00:00)?)$/i.test(v) ? '' : v;
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function titleize(k) { return String(k).replace(/[_-]+/g, ' ').replace(/([a-z])([A-Z])/g, '$1 $2').replace(/^./, function (c) { return c.toUpperCase(); }); }
  function mobileDigits(v) {
    var d = clean(v).replace(/\D/g, '');
    if (d.length === 12 && d.indexOf('91') === 0) d = d.slice(2);
    if (d.length === 11 && d.charAt(0) === '0') d = d.slice(1);
    return d;
  }
  /* -> { cc: '91', mobile: local digits }. Handles '+91 98765 43210', '09876543210', '+977' + '9812345678'. */
  function splitMobile(full, ccRaw, local) {
    var cc = clean(ccRaw).replace(/\D/g, '');
    var d = clean(full).replace(/\D/g, '');
    if (!d && local) d = clean(local).replace(/\D/g, '');
    else if (cc && cc !== '91' && d.indexOf(cc) === 0 && d.length > cc.length + 5) d = d.slice(cc.length);
    if (!cc || cc === '91') return { cc: '91', mobile: mobileDigits(d) };
    return { cc: cc, mobile: d.replace(/^0+/, '') };
  }
  function validMobile(cc, m) { return cc === '91' ? /^[6-9]\d{9}$/.test(m) : /^\d{6,14}$/.test(m); }
  function validEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function validUrl(v) {
    try { var u = new URL(v); return u.protocol === 'https:' && !!u.hostname; } catch (e) { return false; }
  }
  /* -> { iso: 'YYYY-MM-DD' | '', bad: true when something was saved but is not a real date } */
  function parseDob(v, now) {
    v = clean(v);
    if (!v) return { iso: '', bad: false };
    var y, mo, d, m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (m) { y = +m[1]; mo = +m[2]; d = +m[3]; }
    else if ((m = v.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/))) { d = +m[1]; mo = +m[2]; y = +m[3]; }
    else return { iso: '', bad: true };
    var dt = new Date(Date.UTC(y, mo - 1, d));
    var real = dt.getUTCFullYear() === y && dt.getUTCMonth() === mo - 1 && dt.getUTCDate() === d;
    if (!real || y < 1900 || dt.getTime() > (now || Date.now())) return { iso: '', bad: true };
    return { iso: y + '-' + pad(mo) + '-' + pad(d), bad: false };
  }
  function ageFrom(iso, now) {
    var p = iso.split('-'), t = new Date(now || Date.now());
    var age = t.getUTCFullYear() - +p[0];
    if (t.getUTCMonth() + 1 < +p[1] || (t.getUTCMonth() + 1 === +p[1] && t.getUTCDate() < +p[2])) age--;
    return age;
  }
  function issue(code, field) { return { code: code, field: field, text: ISSUES[code][0], fix: ISSUES[code][1] }; }

  function normalize(raw, opts) {
    raw = raw || {};
    var now = (opts && opts.now) || Date.now(), used = {};
    function take(keys) {
      var found = '';
      keys.forEach(function (k) {
        if (Object.prototype.hasOwnProperty.call(raw, k)) { used[k] = 1; if (!found) found = clean(raw[k]); }
      });
      return found;
    }
    var p = { name: take(ALIASES.name), email: take(ALIASES.email), city: take(ALIASES.city), state: take(ALIASES.state), country: take(ALIASES.country), source: take(ALIASES.source) };
    var mobileRaw = take(ALIASES.mobile), dobRaw = take(ALIASES.dob), ageRaw = take(ALIASES.age);
    var dobZero = /^0000-00-00/.test(String(raw.dob == null ? '' : raw.dob).trim()); // MySQL's "zero date" means a broken save, not a blank one
    var bd = take(['birthday']), bm = take(['birthmonth']), by = take(['birthyear']);
    if (!dobRaw && bd && bm && by) {
      var mi = parseInt(bm, 10) || (MONTHS.indexOf(bm.slice(0, 3).toLowerCase()) + 1);
      if (mi) dobRaw = by + '-' + pad(mi) + '-' + pad(parseInt(bd, 10) || 0);
    }

    var ccRaw = take(['ccode', 'country_code']), local = take(['mobile_local']);
    if (!mobileRaw) mobileRaw = local;
    var ph = splitMobile(mobileRaw, ccRaw, local);
    p.cc = ph.cc; p.mobile = ph.mobile;
    var dob = parseDob(dobRaw, now);
    if (!dobRaw && dobZero) dob.bad = true;
    p.dob = dob.iso;
    var age = parseInt(ageRaw, 10);
    p.age = age > 0 && age < 120 ? age : null;
    p.ageDerived = false;
    if (p.age == null && p.dob) { p.age = ageFrom(p.dob, now); p.ageDerived = true; }

    var extras = [];
    Object.keys(raw).forEach(function (k) {
      if (used[k] || FOLDED[k] || PLUMBING[k]) return;
      var v = clean(raw[k]);
      if (v === '') return;
      var meta = EXTRA[k];
      extras.push({ key: k, label: meta ? meta[0] : titleize(k), value: v, sensitive: !!(meta && meta[1]), secret: !!(meta && meta[2]) });
    });
    p.extras = extras;

    var issues = [];
    if (!p.name) issues.push(issue('missing_name', 'name'));
    if (!mobileRaw) issues.push(issue('missing_mobile', 'mobile'));
    else if (!validMobile(p.cc, p.mobile)) issues.push(issue('bad_mobile', 'mobile'));
    if (!p.email) issues.push(issue('missing_email', 'email'));
    else if (!validEmail(p.email)) issues.push(issue('bad_email', 'email'));
    if (dob.bad) issues.push(issue('bad_dob', 'dob'));
    else if (!p.dob) issues.push(issue('missing_dob', 'dob'));
    else if (age > 0 && Math.abs(ageFrom(p.dob, now) - age) > 1) issues.push(issue('age_mismatch', 'age'));
    if (!p.city) issues.push(issue('missing_city', 'city'));
    if (!p.state) issues.push(issue('missing_state', 'state'));
    p.issues = issues;
    return p;
  }

  /* Payment problems depend on editable data, so they are checked separately. */
  function checkPayment(payment, status) {
    var out = [];
    if (payment && payment.status === 'sent' && !validUrl(payment.url || '')) out.push(issue('bad_payment_url', 'payment'));
    else if (payment && payment.url && !validUrl(payment.url)) out.push(issue('bad_payment_url', 'payment'));
    if (status === 'paid' && !(payment && payment.status === 'paid')) out.push(issue('paid_no_record', 'payment'));
    return out;
  }

  F.profile = {
    normalize: normalize, checkPayment: checkPayment, validUrl: validUrl, validEmail: validEmail,
    parseDob: parseDob, ageFrom: ageFrom, mobileDigits: mobileDigits, splitMobile: splitMobile, validMobile: validMobile, clean: clean, PHONE_PARTS: PHONE_PARTS,
    ISSUES: ISSUES, ALIASES: ALIASES, EXTRA: EXTRA, FOLDED: FOLDED, PLUMBING: PLUMBING,
    issueLabel: function (code) { return ISSUES[code] ? ISSUES[code][0] : code; },
    FIELDS: [['name', 'Full name'], ['mobile', 'Mobile number'], ['email', 'Email'], ['dob', 'Date of birth'], ['age', 'Age'], ['city', 'City'], ['state', 'State'], ['country', 'Country'], ['source', 'Found us via']]
  };
})(typeof window !== 'undefined' ? window : globalThis);
