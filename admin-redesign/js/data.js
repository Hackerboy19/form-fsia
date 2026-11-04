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
    [145, 'miss-universe-registration', 'Miss Universe', 'events']
  ];

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
  forms.forEach(function (f) { f.live = 'https://www.fsia.in/' + f.slug; formsById[f.id] = f; });

  /* ---------- registrations ---------- */
  var FIRST = ['Aarohi', 'Ananya', 'Diya', 'Ishita', 'Kavya', 'Meera', 'Naina', 'Priya', 'Riya', 'Sanya', 'Tanvi', 'Zoya', 'Anjali', 'Pooja', 'Neha', 'Simran', 'Harleen', 'Mansi', 'Shruti', 'Divya', 'Kritika', 'Palak', 'Radhika', 'Sneha', 'Tanya', 'Vaishnavi', 'Aditi', 'Bhavna', 'Charu', 'Deepika', 'Esha', 'Garima', 'Isha', 'Jhanvi', 'Komal', 'Lavanya', 'Mahima', 'Nidhi', 'Ojasvi', 'Payal', 'Rashmi', 'Sakshi', 'Trisha', 'Urvashi', 'Vandana', 'Yamini'];
  var FIRST_M = ['Rahul', 'Amit', 'Karan', 'Vikram', 'Rohan', 'Arjun', 'Nikhil', 'Sahil'];
  var LAST = ['Sharma', 'Verma', 'Singh', 'Kaur', 'Gupta', 'Mehta', 'Nair', 'Reddy', 'Iyer', 'Das', 'Bora', 'Khan', 'Joshi', 'Patel', 'Rao', 'Chauhan', 'Yadav', 'Bhat', 'Menon', 'Thakur', 'Malhotra', 'Kapoor', 'Saini', 'Pandey', 'Mishra', 'Chopra', 'Bansal', 'Sethi'];
  var SOURCES = ['Instagram', 'Google', 'WhatsApp', 'Direct', 'Facebook', 'YouTube'];
  var boost = { delhi: 3, maharashtra: 2.5, rajasthan: 2.5, punjab: 2, 'uttar-pradesh': 2.5, gujarat: 2, haryana: 2, karnataka: 1.5, 'west-bengal': 1.5 };

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

  var now = Date.now();
  var regs = [], n = 460, i;
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
    var mobile = '9000' + String(int(0, 999999)).padStart(6, '0');
    var young = daysAgo < 3;
    var x = r(), status;
    if (young) status = x < 0.72 ? 'new' : x < 0.9 ? 'contacted' : 'shortlisted';
    else status = x < 0.22 ? 'new' : x < 0.5 ? 'contacted' : x < 0.72 ? 'shortlisted' : x < 0.9 ? 'paid' : 'rejected';
    regs.push({
      id: 0, formId: f.id, formTitle: f.title, group: f.group, name: name,
      mobile: mobile, email: (firstName + '.' + name.split(' ')[1] + int(1, 99) + '@example.com').toLowerCase(),
      dob: F.fmt.ymd(born.getTime()), age: age, city: city, state: state,
      source: pick(SOURCES), verified: r() < 0.86, status: status, createdAt: createdAt
    });
  }
  regs.sort(function (a, b) { return a.createdAt - b.createdAt; });
  regs.forEach(function (g, idx) {
    g.id = 1001 + idx;
    g._hay = [g.name, g.email, g.city, g.state, g.formTitle, g.mobile].join(' ').toLowerCase();
  });

  /* ---------- OTP log ---------- */
  var otps = regs.map(function (g) {
    var st = g.verified ? 'verified' : (r() < 0.6 ? 'expired' : 'failed');
    return { id: g.id, mobile: g.mobile, formId: g.formId, formTitle: g.formTitle, at: g.createdAt - int(60, 400) * 1000, status: st, attempts: st === 'verified' ? int(1, 2) : st === 'failed' ? 5 : int(0, 2) };
  });
  for (i = 0; i < 90; i++) {
    var ff = pickForm();
    var st2 = r() < 0.06 ? 'sent' : r() < 0.7 ? 'expired' : 'failed';
    var at = st2 === 'sent' ? now - int(20, 400) * 1000 : now - Math.floor(Math.pow(r(), 1.5) * 60 * DAY);
    otps.push({ id: 5000 + i, mobile: '9000' + String(int(0, 999999)).padStart(6, '0'), formId: ff.id, formTitle: ff.title, at: at, status: st2, attempts: st2 === 'failed' ? 5 : int(0, 2) });
  }
  otps.sort(function (a, b) { return b.at - a.at; });

  /* ---------- page meta (columns of the `more_pages` table) ---------- */
  var META_KEYS = ['meta_title', 'descritpion', 'meta_keyword', 'og_title', 'og_description', 'og_image', 'app_fee', 'fee_title', 'fee_heading', 'fee_subtitle', 'journey_note'];
  var pageMeta = {};
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

  F.data = { forms: forms, formsById: formsById, regs: regs, otps: otps, pageMeta: pageMeta, metaKeys: META_KEYS, now: now };
})();
