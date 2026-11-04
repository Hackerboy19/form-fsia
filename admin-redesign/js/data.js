/* Demo data. Form ids are the real `more_pages` row ids found in the site's PHP forms;
   everything else (people, dates, statuses) is generated and fake. */
(function () {
  'use strict';
  var F = window.FSIA;
  var DAY = 86400000;

  F.GROUPS = [
    { id: 'miss', label: 'Miss India', short: 'Miss' },
    { id: 'mrs', label: 'Mrs India', short: 'Mrs' },
    { id: 'business', label: 'Business & Services', short: 'Business' },
    { id: 'events', label: 'Fashion Events', short: 'Events' }
  ];
  F.STATUSES = [
    { id: 'new', label: 'New' },
    { id: 'contacted', label: 'Contacted' },
    { id: 'shortlisted', label: 'Shortlisted' },
    { id: 'paid', label: 'Fee paid' },
    { id: 'rejected', label: 'Rejected' }
  ];

  // [slug, missId, mrsId, state name, cities]
  var STATES = [
    ['andhra-pradesh', 58, 59, 'Andhra Pradesh', ['Visakhapatnam', 'Vijayawada']],
    ['arunachal-pradesh', 88, 89, 'Arunachal Pradesh', ['Itanagar', 'Naharlagun']],
    ['assam', 90, 91, 'Assam', ['Guwahati', 'Dibrugarh']],
    ['bihar', 92, 93, 'Bihar', ['Patna', 'Gaya']],
    ['chandigarh', 94, 95, 'Chandigarh', ['Chandigarh', 'Manimajra']],
    ['chattisgarh', 96, 97, 'Chhattisgarh', ['Raipur', 'Bhilai']],
    ['delhi', 100, 101, 'Delhi', ['New Delhi', 'Dwarka']],
    ['goa', 102, 103, 'Goa', ['Panaji', 'Margao']],
    ['gujarat', 104, 105, 'Gujarat', ['Ahmedabad', 'Surat']],
    ['haryana', 106, 107, 'Haryana', ['Gurugram', 'Faridabad']],
    ['himachal-pradesh', 108, 109, 'Himachal Pradesh', ['Shimla', 'Manali']],
    ['jammu-kashmir', 48, 49, 'Jammu & Kashmir', ['Srinagar', 'Jammu']],
    ['jharkhand', 50, 51, 'Jharkhand', ['Ranchi', 'Jamshedpur']],
    ['karnataka', 52, 53, 'Karnataka', ['Bengaluru', 'Mysuru']],
    ['kerala', 54, 55, 'Kerala', ['Kochi', 'Thiruvananthapuram']],
    ['madhya-pradesh', 56, 57, 'Madhya Pradesh', ['Bhopal', 'Indore']],
    ['maharashtra', 60, 61, 'Maharashtra', ['Mumbai', 'Pune']],
    ['manipur', 62, 63, 'Manipur', ['Imphal', 'Thoubal']],
    ['meghalaya', 64, 65, 'Meghalaya', ['Shillong', 'Tura']],
    ['mizoram', 66, 67, 'Mizoram', ['Aizawl', 'Lunglei']],
    ['nagaland', 68, 69, 'Nagaland', ['Kohima', 'Dimapur']],
    ['odisha', 70, 71, 'Odisha', ['Bhubaneswar', 'Cuttack']],
    ['poducherry', 72, 73, 'Puducherry', ['Puducherry', 'Karaikal']],
    ['punjab', 74, 75, 'Punjab', ['Ludhiana', 'Amritsar']],
    ['rajasthan', 46, 47, 'Rajasthan', ['Jaipur', 'Jodhpur']],
    ['sikkim', 76, 77, 'Sikkim', ['Gangtok', 'Namchi']],
    ['tamil-nadu', 78, 79, 'Tamil Nadu', ['Chennai', 'Coimbatore']],
    ['telangana', 80, 81, 'Telangana', ['Hyderabad', 'Warangal']],
    ['tripura', 82, 83, 'Tripura', ['Agartala', 'Udaipur']],
    ['uttar-pradesh', 86, 87, 'Uttar Pradesh', ['Lucknow', 'Noida']],
    ['uttarakhand', 84, 85, 'Uttarakhand', ['Dehradun', 'Haridwar']],
    ['west-bengal', 98, 99, 'West Bengal', ['Kolkata', 'Siliguri']]
  ];
  var OTHERS = [
    [24, 'celebrity-pr-agency', 'Celebrity PR Agency', 'business'],
    [23, 'fashion-designer', 'Fashion Designer', 'business'],
    [26, 'makeup-artist', 'Makeup Artist', 'business'],
    [205, 'channel-partner', 'Channel Partner', 'business'],
    [146, 'forever-fashion-week', 'Forever Fashion Week', 'events'],
    [29, 'india-fashion-week-fashion-show', 'India Fashion Week', 'events'],
    [162, 'bharat-couture-week-fashion-week', 'Bharat Couture Week', 'events'],
    [145, 'miss-universe-registration', 'Miss Universe', 'events'],
    // Live form posting to saveworld.php; its more_pages id is not known yet, 9001 is a placeholder.
    [9001, 'miss-world-beauty-pageant', 'Miss World', 'events']
  ];
  var ID_UNKNOWN = { 9001: 1 };

  F.STATE_NAMES = STATES.map(function (s) { return s[3]; }).sort();

  function rng(seed) {
    return function () {
      seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
      var t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  var r = rng(20260);
  function pick(a) { return a[Math.floor(r() * a.length)]; }
  function int(a, b) { return a + Math.floor(r() * (b - a + 1)); }

  /* ---------- forms ---------- */
  var forms = [];
  STATES.forEach(function (s) {
    forms.push({ id: s[1], slug: 'miss-' + s[0], title: 'Miss ' + s[3], group: 'miss', state: s[3], cities: s[4] });
    forms.push({ id: s[2], slug: 'mrs-' + s[0], title: 'Mrs ' + s[3], group: 'mrs', state: s[3], cities: s[4] });
  });
  OTHERS.forEach(function (o) {
    forms.push({ id: o[0], slug: o[1], title: o[2], group: o[3], state: null, cities: null });
  });
  var order = { miss: 0, mrs: 1, business: 2, events: 3 };
  forms.sort(function (a, b) { return order[a.group] - order[b.group] || a.title.localeCompare(b.title); });
  var formsById = {};
  forms.forEach(function (f) { f.live = 'https://www.fsia.in/' + f.slug + (f.id === 9001 ? '.php' : ''); f.idKnown = !ID_UNKNOWN[f.id]; formsById[f.id] = f; });

  /* ---------- team, roles, permissions ---------- */
  F.ROLES = [
    { id: 'admin', label: 'Super Admin', desc: 'Full control, including the team and who can do what.', locked: true },
    { id: 'manager', label: 'Manager', desc: 'Runs the daily work. Cannot change team access.' },
    { id: 'caller', label: 'Registration team', desc: 'Calls applicants, updates status and sends payment links.' },
    { id: 'content', label: 'Content editor', desc: 'Edits form pages, search text and the fee box only.' },
    { id: 'accounts', label: 'Accounts', desc: 'Confirms payments and downloads reports.' },
    { id: 'viewer', label: 'Viewer', desc: 'Can look at everything but cannot change anything.' }
  ];
  F.PERMS = [
    { id: 'dashboard.view', group: 'Overview', label: 'See the dashboard' },
    { id: 'registrations.view', group: 'Registrations', label: 'See registrations and full profiles' },
    { id: 'registrations.edit', group: 'Registrations', label: 'Change status and write notes' },
    { id: 'registrations.assign', group: 'Registrations', label: 'Give registrations to team members' },
    { id: 'registrations.export', group: 'Registrations', label: 'Download CSV files' },
    { id: 'registrations.delete', group: 'Registrations', label: 'Delete registrations' },
    { id: 'registrations.sensitive', group: 'Registrations', label: 'See private documents (KYC)' },
    { id: 'payments.send', group: 'Payments', label: 'Set the offer and send payment links' },
    { id: 'payments.confirm', group: 'Payments', label: 'Mark a payment as received' },
    { id: 'pages.view', group: 'Form pages', label: 'See form pages' },
    { id: 'pages.edit', group: 'Form pages', label: 'Edit form pages (title, fee box, share image)' },
    { id: 'otp.view', group: 'Other', label: 'See the OTP log' },
    { id: 'team.view', group: 'Team', label: 'See team members and the activity log' },
    { id: 'team.manage', group: 'Team', label: 'Add, change and remove team members and access' }
  ];
  var ALL = F.PERMS.map(function (x) { return x.id; });
  F.DEFAULT_PERMS = {
    admin: ALL.slice(),
    manager: ALL.filter(function (x) { return x !== 'team.manage'; }),
    caller: ['dashboard.view', 'registrations.view', 'registrations.edit', 'payments.send', 'otp.view'],
    content: ['dashboard.view', 'pages.view', 'pages.edit'],
    accounts: ['dashboard.view', 'registrations.view', 'registrations.export', 'payments.send', 'payments.confirm'],
    viewer: ['dashboard.view', 'registrations.view', 'pages.view', 'otp.view']
  };
  var users = [
    { id: 'u1', name: 'Ritu Sharma', email: 'ritu@example.com', role: 'admin', states: [], active: true },
    { id: 'u2', name: 'Aman Verma', email: 'aman@example.com', role: 'manager', states: [], active: true },
    { id: 'u3', name: 'Neha Singh', email: 'neha@example.com', role: 'caller', states: ['Rajasthan', 'Delhi', 'Haryana', 'Punjab'], active: true },
    { id: 'u4', name: 'Karan Mehta', email: 'karan@example.com', role: 'caller', states: ['Maharashtra', 'Gujarat', 'Goa', 'Uttar Pradesh'], active: true },
    { id: 'u5', name: 'Pooja Nair', email: 'pooja@example.com', role: 'content', states: [], active: true },
    { id: 'u6', name: 'Sandeep Rao', email: 'sandeep@example.com', role: 'accounts', states: [], active: true },
    { id: 'u7', name: 'Isha Gupta', email: 'isha@example.com', role: 'viewer', states: [], active: false }
  ];

  /* ---------- registrations, stored the way each form version saves them ---------- */
  var FIRST = ['Aarohi', 'Ananya', 'Diya', 'Ishita', 'Kavya', 'Meera', 'Naina', 'Priya', 'Riya', 'Sanya', 'Tanvi', 'Zoya', 'Anjali', 'Pooja', 'Neha', 'Simran', 'Harleen', 'Mansi', 'Shruti', 'Divya', 'Kritika', 'Palak', 'Radhika', 'Sneha', 'Tanya', 'Vaishnavi', 'Aditi', 'Bhavna', 'Charu', 'Deepika', 'Esha', 'Garima', 'Isha', 'Jhanvi', 'Komal', 'Lavanya', 'Mahima', 'Nidhi', 'Ojasvi', 'Payal', 'Rashmi', 'Sakshi', 'Trisha', 'Urvashi', 'Vandana', 'Yamini'];
  var FIRST_M = ['Rahul', 'Amit', 'Karan', 'Vikram', 'Rohan', 'Arjun', 'Nikhil', 'Sahil'];
  var LAST = ['Sharma', 'Verma', 'Singh', 'Kaur', 'Gupta', 'Mehta', 'Nair', 'Reddy', 'Iyer', 'Das', 'Bora', 'Khan', 'Joshi', 'Patel', 'Rao', 'Chauhan', 'Yadav', 'Bhat', 'Menon', 'Thakur', 'Malhotra', 'Kapoor', 'Saini', 'Pandey', 'Mishra', 'Chopra', 'Bansal', 'Sethi'];
  var SOURCES = ['Instagram', 'Google', 'WhatsApp', 'Direct', 'Facebook', 'YouTube'];
  var boost = { delhi: 3, maharashtra: 2.5, rajasthan: 2.5, punjab: 2, 'uttar-pradesh': 2.5, gujarat: 2, haryana: 2, karnataka: 1.5, 'west-bengal': 1.5 };
  // Forms whose earlier version asked for more fields (see git history of these files): old rows still hold them.
  var LEGACY_FORMS = { 24: 1, 146: 1, 29: 1 };
  var LEGACY_DAYS = 25;
  var CAMEL_FORMS = { 162: 1, 205: 1 };       // these forms post `firstName` instead of `fname`
  var CITYFSIA_FORMS = { 29: 1, 145: 1 };     // these forms post `cityfsia` instead of `city`

  var weights = forms.map(function (f) {
    var w = 0.4 + r() * 1.6;
    if (f.group === 'miss' || f.group === 'mrs') {
      var slug = f.slug.replace(/^(miss|mrs)-/, '');
      w *= boost[slug] || 1;
      if (f.group === 'mrs') w *= 0.7;
    } else w *= f.group === 'business' ? 1.4 : 1.1;
    return w;
  });
  var totalW = weights.reduce(function (a, b) { return a + b; }, 0);
  function pickForm() {
    var x = r() * totalW, i;
    for (i = 0; i < forms.length; i++) { x -= weights[i]; if (x <= 0) return forms[i]; }
    return forms[0];
  }
  function feeOf(id) { var n = parseInt(String((pageMeta[id] || {}).app_fee || '').replace(/[^\d]/g, ''), 10); return n || 1999; }

  var now = Date.now();
  var pageMeta = {};
  var raws = [], n = 460, i;
  for (i = 0; i < n; i++) {
    var f = pickForm();
    var isMrs = f.group === 'mrs';
    var firstName = f.group === 'business' && r() < 0.35 ? pick(FIRST_M) : pick(FIRST);
    var name = firstName + ' ' + pick(LAST);
    var state = f.state || pick(STATES)[3];
    var stObj = STATES.filter(function (s) { return s[3] === state; })[0];
    var city = f.cities ? pick(f.cities) : pick(stObj[4]);
    var age = isMrs ? int(24, 46) : f.group === 'miss' ? int(18, 28) : int(19, 44);
    var daysAgo = Math.floor(Math.pow(r(), 1.7) * 90);
    var createdAt = now - daysAgo * DAY - Math.floor(r() * DAY);
    var born = new Date(now);
    born.setFullYear(born.getFullYear() - age); born.setMonth(int(0, 11)); born.setDate(int(1, 28));
    if (born.getTime() > now - age * 365.25 * DAY) born.setFullYear(born.getFullYear() - 1);
    var mobile = '9000' + String(int(0, 999999)).padStart(6, '0');
    var email = (firstName + '.' + name.split(' ')[1] + int(1, 99) + '@example.com').toLowerCase();
    var dob = F.fmt.ymd(born.getTime());

    // the row as the save script stores it
    var raw = {};
    raw[CAMEL_FORMS[f.id] ? 'firstName' : 'fname'] = name;
    raw.mobile = mobile; raw.email = email; raw.dob = dob; raw.age = String(age);
    raw[CITYFSIA_FORMS[f.id] ? 'cityfsia' : 'city'] = city;
    raw.state = state;
    if (f.group === 'miss' || f.group === 'mrs') raw.landing = pick(SOURCES);
    if (f.id === 9001) {
      // saveworld.php: country code + local number, a hidden combined mobile, country and the category
      var abroad = r() < 0.25 ? pick([['Nepal', '977', 'Bagmati', 'Kathmandu'], ['United Arab Emirates', '971', 'Dubai', 'Dubai'], ['United Kingdom', '44', 'England', 'London']]) : null;
      delete raw.mobile;
      raw.regtype = 'Miss World'; raw.ncategory = 'Miss World 2026';
      raw.country = abroad ? abroad[0] : 'India';
      raw.ccode = '+' + (abroad ? abroad[1] : '91');
      raw.mobile_local = abroad ? String(int(5000000000, 5999999999)) : mobile;
      raw.mobile = raw.ccode + raw.mobile_local;
      if (abroad) { raw.state = abroad[2]; raw.city = abroad[3]; }
    }
    if (f.id === 145) { raw.instagram = '@' + firstName.toLowerCase() + int(10, 99); raw.qualification = pick(['Graduate', '12th pass', 'Post graduate']); raw.skills = pick(['Dance', 'Public speaking', 'Singing', 'Acting']); }
    if (LEGACY_FORMS[f.id] && daysAgo > LEGACY_DAYS) {
      raw.education = pick(['Graduate', '12th pass', 'Post graduate', 'Diploma']); raw.totalmarks = int(55, 95) + '%';
      raw.instname = pick(['City College', 'State University', 'Open University']); raw.brandname = pick(['Styling', 'Makeup', 'Modelling', 'Design']);
      raw.workexpr = pick(['Fresher', '1 year', '3 years', '5+ years']); raw.address = city + ', ' + state;
      raw.kyc = 'kyc_' + (1001 + i) + '_front.jpg, kyc_' + (1001 + i) + '_back.jpg'; raw.comment = pick(['Interested in the grand finale.', 'Has done two shows before.', 'Wants to know the dates.']);
      raw.password = 'stored-by-old-form';
    }
    // data problems real tables collect over the years
    var x = r();
    if (x < 0.025) raw.email = '';
    else if (x < 0.045) raw.mobile = mobile.slice(0, 9);
    else if (x < 0.065) raw.dob = '0000-00-00';
    else if (x < 0.08) raw.age = String(age + 6);
    else if (x < 0.11) raw.mobile = '+91 ' + mobile.slice(0, 5) + ' ' + mobile.slice(5);
    else if (x < 0.125) raw[CITYFSIA_FORMS[f.id] ? 'cityfsia' : 'city'] = 'NULL';
    else if (x < 0.135) raw.email = email.replace('@', '');
    else if (x < 0.14) { raw.dob = ''; raw.age = ''; }
    else if (x < 0.143) raw[CAMEL_FORMS[f.id] ? 'firstName' : 'fname'] = null;

    var young = daysAgo < 3;
    var y = r(), status;
    if (young) status = y < 0.72 ? 'new' : y < 0.9 ? 'contacted' : 'shortlisted';
    else status = y < 0.22 ? 'new' : y < 0.5 ? 'contacted' : y < 0.72 ? 'shortlisted' : y < 0.9 ? 'paid' : 'rejected';
    raws.push({ formId: f.id, createdAt: createdAt, verified: r() < 0.86, status: status, raw: raw, _r: [r(), r(), r(), r()] });
  }
  /* ---------- page meta (columns of the `more_pages` table) ---------- */
  var META_KEYS = ['meta_title', 'descritpion', 'meta_keyword', 'og_title', 'og_description', 'og_image', 'app_fee', 'fee_title', 'fee_heading', 'fee_subtitle', 'journey_note'];
  forms.forEach(function (f) {
    var place = f.state || 'India';
    var m = {
      meta_title: f.title + ' 2026 Registration | Forever Star India',
      descritpion: 'Register for ' + f.title + ' 2026 with Forever Star India (FSIA). Apply online — auditions & nominations open from every city of ' + place + '.',
      meta_keyword: f.title.toLowerCase() + ' 2026, ' + f.title.toLowerCase() + ' registration, forever star india, fsia',
      og_title: f.title + ' 2026',
      og_description: 'Apply now for ' + f.title + ' 2026. Free registration, auditions open.',
      og_image: f.slug + '-banner.jpg',
      app_fee: f.group === 'business' ? '₹ 2,500' : '₹ 1,999',
      fee_title: 'Audition fee',
      fee_heading: 'Important',
      fee_subtitle: 'Filling this form is free. The audition fee is payable after your registration is received.',
      journey_note: '<p>Our team will contact you on WhatsApp within <b>24–48 working hours</b>.</p>'
    };
    if (r() < 0.12) m.meta_keyword = '';
    if (r() < 0.09) m.og_image = '';
    if (r() < 0.06) m.descritpion = '';
    if (r() < 0.05) m.app_fee = '';
    pageMeta[f.id] = m;
  });

  /* ---------- normalise rows, add payment and assignment ---------- */
  raws.sort(function (a, b) { return a.createdAt - b.createdAt; });
  var callers = users.filter(function (u) { return u.role === 'caller'; });
  var regs = raws.map(function (row, idx) {
    var f = formsById[row.formId], id = 1001 + idx, q = row._r;
    var p = F.profile.normalize(row.raw, { now: now });
    var amount = feeOf(f.id), early = q[0] < 0.25;
    var pay = { offer: early ? 'Early-bird offer' : 'Audition fee', amount: early ? Math.round(amount * 0.75 / 10) * 10 : amount, url: '', status: 'none', sentAt: null, paidAt: null, ref: '' };
    var link = 'https://pay.example.com/pay/FSIA-' + id;
    if (row.status === 'paid') {
      pay.url = link; pay.status = 'paid'; pay.sentAt = row.createdAt + 2 * DAY; pay.paidAt = Math.min(now - 3600000, row.createdAt + 3 * DAY); pay.ref = 'PAY' + (700000 + id);
    } else if ((row.status === 'shortlisted' && q[1] < 0.65) || (row.status === 'contacted' && q[1] < 0.3)) {
      pay.url = q[2] < 0.04 ? 'pay.example.com/FSIA-' + id : link; pay.status = 'sent'; pay.sentAt = Math.min(now - 600000, row.createdAt + DAY);
    }
    var owner = callers.filter(function (u) { return u.states.indexOf(p.state) >= 0; })[0];
    return {
      id: id, formId: f.id, formTitle: f.title, group: f.group, createdAt: row.createdAt, verified: row.verified, status: row.status,
      name: p.name || 'Unnamed applicant', cc: p.cc, mobile: p.mobile, email: p.email, country: p.country, dob: p.dob, age: p.age, ageDerived: p.ageDerived,
      city: p.city, state: p.state, source: p.source, extras: p.extras, profileIssues: p.issues, raw: row.raw,
      assignedTo: owner && q[3] < 0.75 ? owner.id : null, payment: pay
    };
  });
  regs.forEach(function (g) { g._hay = [g.name, g.email, g.city, g.state, g.formTitle, g.mobile].join(' ').toLowerCase(); });

  /* ---------- OTP log ---------- */
  var otps = regs.map(function (g) {
    var st = g.verified ? 'verified' : (r() < 0.6 ? 'expired' : 'failed');
    return { id: g.id, cc: g.cc, mobile: g.mobile, formId: g.formId, formTitle: g.formTitle, at: g.createdAt - int(60, 400) * 1000, status: st, attempts: st === 'verified' ? int(1, 2) : st === 'failed' ? 5 : int(0, 2) };
  });
  for (i = 0; i < 90; i++) {
    var ff = pickForm();
    var st2 = r() < 0.06 ? 'sent' : r() < 0.7 ? 'expired' : 'failed';
    var at = st2 === 'sent' ? now - int(20, 400) * 1000 : now - Math.floor(Math.pow(r(), 1.5) * 60 * DAY);
    otps.push({ id: 5000 + i, mobile: '9000' + String(int(0, 999999)).padStart(6, '0'), formId: ff.id, formTitle: ff.title, at: at, status: st2, attempts: st2 === 'failed' ? 5 : int(0, 2) });
  }
  otps.sort(function (a, b) { return b.at - a.at; });

  /* ---------- a few weeks of team activity ---------- */
  var audit = [];
  var actions = [
    ['status', function (g) { return 'Changed ' + g.name + ' to ' + F.STATUSES.filter(function (s) { return s.id === g.status; })[0].label; }, ['u2', 'u3', 'u4']],
    ['payment', function (g) { return 'Sent a payment link to ' + g.name + ' (' + g.formTitle + ')'; }, ['u3', 'u4']],
    ['payment', function (g) { return 'Marked the payment of ' + g.name + ' as received'; }, ['u6']],
    ['export', function () { return 'Downloaded a CSV of registrations'; }, ['u2', 'u6']],
    ['page', function (g) { return 'Edited the ' + g.formTitle + ' page'; }, ['u5']],
    ['login', function () { return 'Signed in'; }, ['u1', 'u2', 'u3', 'u4', 'u5', 'u6']]
  ];
  for (i = 0; i < 36; i++) {
    var a = pick(actions), g = pick(regs);
    audit.push({ at: now - Math.floor(Math.pow(r(), 1.3) * 14 * DAY) - 3600000, userId: pick(a[2]), action: a[0], text: a[1](g) });
  }
  audit.sort(function (a1, b1) { return b1.at - a1.at; });

  F.data = { forms: forms, formsById: formsById, regs: regs, otps: otps, pageMeta: pageMeta, metaKeys: META_KEYS, users: users, audit: audit, feeOf: feeOf, now: now };
})();
