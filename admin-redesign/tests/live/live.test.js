/* End-to-end test of the PHP API against a real MySQL/MariaDB:  node tests/live/live.test.js
   Needs php (with pdo_mysql), a local MySQL/MariaDB the current user can reach as root over the socket, and
   Playwright for the browser part. It builds a throwaway database from fixture.sql, writes a temporary
   api/config.php, runs `php -S`, and removes all of it again at the end. */
'use strict';
const { execFileSync, spawn } = require('child_process');
const fs = require('fs'), path = require('path');
const ROOT = path.join(__dirname, '..', '..');
const CONFIG = path.join(ROOT, 'api', 'config.php');
const DB = 'fsia_admin_test', DBUSER = 'fsia_admin_test', DBPASS = 'test-' + Math.random().toString(36).slice(2);
const PORT = 8000 + Math.floor(Math.random() * 900);
const BASE = 'http://127.0.0.1:' + PORT + '/';
const SETUP_KEY = 'setup-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);

function sql(q, db) { return execFileSync('mysql', ['-uroot', '-N', '-B'].concat(db ? [db] : []), { input: q }).toString().trim(); }
let passed = 0, failed = 0;
async function check(name, fn) { try { await fn(); passed++; console.log('  ✓ ' + name); } catch (e) { failed++; console.log('  ✗ ' + name + '\n    ' + e.message.split('\n')[0]); } }
function ok(v, msg) { if (!v) throw new Error(msg || 'expected true'); }

/* A tiny client with its own cookie jar and CSRF token, like one browser tab. */
function client() {
  let cookie = '', csrf = '';
  async function req(action, body) {
    const res = await fetch(BASE + 'api/index.php?r=' + action, {
      method: body ? 'POST' : 'GET',
      headers: Object.assign({ Accept: 'application/json', Cookie: cookie }, body ? { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf } : {}),
      body: body ? JSON.stringify(body) : undefined
    });
    const set = res.headers.get('set-cookie');
    if (set) cookie = set.split(';')[0];
    const data = await res.json();
    if (data.csrf) csrf = data.csrf;
    return { status: res.status, data };
  }
  return { req, setCsrf: (v) => { csrf = v; }, get csrf() { return csrf; } };
}
async function install(fields) {
  const res = await fetch(BASE + 'api/install.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(fields) });
  return res.text();
}
const formTables = ['savemissindia', 'savemrsindia', 'savefashiondesigner', 'saveworld', 'otp_verifications'];
function checksums() { return sql('CHECKSUM TABLE ' + formTables.join(', ') + ';', DB); }

(async () => {
  /* ---------- set up ---------- */
  sql('DROP DATABASE IF EXISTS ' + DB + '; CREATE DATABASE ' + DB + ' CHARACTER SET utf8mb4;' +
      "CREATE USER IF NOT EXISTS '" + DBUSER + "'@'localhost' IDENTIFIED BY '" + DBPASS + "'; ALTER USER '" + DBUSER + "'@'localhost' IDENTIFIED BY '" + DBPASS + "';" +
      'GRANT ALL ON ' + DB + ".* TO '" + DBUSER + "'@'localhost'; FLUSH PRIVILEGES;");
  sql(fs.readFileSync(path.join(__dirname, 'fixture.sql'), 'utf8'), DB);
  if (fs.existsSync(CONFIG)) throw new Error('api/config.php already exists; move it away before running this test.');
  fs.writeFileSync(CONFIG, `<?php return ['db' => ['host' => 'localhost', 'port' => 3306, 'name' => '${DB}', 'user' => '${DBUSER}', 'pass' => '${DBPASS}'],
    'setup_key' => '${SETUP_KEY}', 'pages_table' => 'more_pages', 'otp_table' => 'otp_verifications',
    'sources' => [
      ['table' => 'savemissindia', 'group' => 'miss'], ['table' => 'savemrsindia', 'group' => 'mrs'],
      ['table' => 'savefashiondesigner', 'group' => 'business', 'form_column' => 'fsia_source', 'form_map' => ['Fashion Designer' => 23, 'Celebrity PR Agency' => 24]],
      ['table' => 'saveworld', 'group' => 'events', 'form' => 9001], ['table' => 'table_that_does_not_exist', 'group' => 'events'],
    ],
    'secret_columns' => ['password', 'otp_code', 'code'], 'sensitive_columns' => ['kyc'], 'payment_link_template' => 'https://pay.example.com/l/fsia?amount={amount}&ref={ref}'];`);
  const php = spawn('php', ['-S', '127.0.0.1:' + PORT, '-t', ROOT], { stdio: 'ignore' });
  for (let i = 0; i < 50; i++) { try { await fetch(BASE + 'index.html'); break; } catch (e) { await new Promise((r) => setTimeout(r, 100)); } }
  const before = checksums();

  try {
    console.log('Setup');
    await check('install.php refuses a wrong setup key', async () => {
      ok((await install({ setup_key: 'wrong' })).includes('does not match'));
      ok(sql("SHOW TABLES LIKE 'admin_users';", DB) === '', 'no tables created');
    });
    await check('install.php creates the tables and the first Super Admin', async () => {
      const t = await install({ setup_key: SETUP_KEY, name: 'Ritu Sharma', email: 'ritu@example.com', username: 'ritu', password: 'correct-horse-battery' });
      ok(t.includes('Super Admin created'), t.slice(0, 200));
      ok(/^\$2y\$|^\$argon/.test(sql("SELECT password_hash FROM admin_users WHERE username='ritu';", DB)), 'password is hashed');
    });
    await check('install.php will not add a second Super Admin', async () => {
      ok((await install({ setup_key: SETUP_KEY, name: 'X', email: 'x@example.com', username: 'x', password: 'another-password-1' })).includes('already exist'));
      ok(sql('SELECT COUNT(*) FROM admin_users;', DB) === '1');
    });

    console.log('Sign-in and sessions');
    const admin = client();
    await check('not signed in: no user, data refused', async () => {
      ok((await admin.req('me')).data.user === null);
      ok((await admin.req('registrations')).status === 401);
    });
    await check('a POST without the CSRF token is refused', async () => {
      const c = client(); await c.req('me'); c.setCsrf('nope');
      ok((await c.req('login', { username: 'ritu', password: 'correct-horse-battery' })).status === 403);
    });
    await check('wrong password is refused, right one signs in', async () => {
      await admin.req('me');
      ok((await admin.req('login', { username: 'ritu', password: 'wrong' })).status === 401);
      const r = await admin.req('login', { username: 'ritu', password: 'correct-horse-battery' });
      ok(r.status === 200 && r.data.user.role === 'admin', JSON.stringify(r.data));
    });

    console.log('Registrations');
    let rows;
    await check('every row of every configured table arrives, missing tables are reported', async () => {
      const r = await admin.req('registrations');
      rows = r.data.rows;
      ok(rows.length === 7, 'got ' + rows.length + ' rows');
      ok(r.data.skipped.includes('table_that_does_not_exist'));
    });
    await check('old-form fields are all there; passwords and OTP codes never leave the server', async () => {
      const old = rows.find((x) => x.raw.fname === 'Kavya Rao');
      ['education', 'totalmarks', 'instname', 'workexpr', 'address', 'comment', 'kyc'].forEach((k) => ok(old.raw[k], 'missing ' + k));
      ok(!('password' in old.raw) && old.secret.includes('password'), 'password must be listed as hidden, not sent');
      ok(!JSON.stringify(rows).includes('plain-old-password'), 'password value leaked');
      ok(old.formId === 24 && old.createdAt > 0, 'form from fsia_source, date from reg_date');
    });
    await check('Miss World row keeps country code; OTP-verified flag comes from otp_verifications', async () => {
      const w = rows.find((x) => x.raw.fname === 'Asha Thapa');
      ok(w.raw.ccode === '+977' && w.formId === 9001);
      ok(rows.find((x) => x.raw.fname === 'Riya Sharma').verified === true);
      ok(rows.find((x) => x.raw.fname === 'Kavya Joshi').verified === false);
    });
    const riya = () => rows.find((x) => x.raw.fname === 'Riya Sharma').uid;
    const kavya = () => rows.find((x) => x.raw.fname === 'Kavya Joshi').uid;
    await check('status, notes, assignment are saved in registration_work only', async () => {
      ok((await admin.req('reg.status', { ids: [riya()], status: 'contacted' })).status === 200);
      ok((await admin.req('reg.notes', { id: riya(), notes: 'Called, will confirm' })).status === 200);
      ok(sql("SELECT CONCAT(status,'|',notes) FROM registration_work WHERE source_table='savemissindia';", DB) === 'contacted|Called, will confirm');
    });
    await check('payment: http link refused, https saved, mark as paid sets Fee paid', async () => {
      ok((await admin.req('reg.payment', { id: riya(), offer: 'Audition fee', amount: 1999, url: 'http://pay.example.com/x' })).status === 400);
      ok((await admin.req('reg.payment', { id: riya(), offer: 'Audition fee', amount: 1999, url: 'https://pay.example.com/x', status: 'sent' })).status === 200);
      ok((await admin.req('reg.payment', { id: riya(), status: 'paid', ref: 'UPI123' })).status === 200);
      ok(sql("SELECT CONCAT(status,'|',payment_status,'|',payment_ref,'|',amount) FROM registration_work WHERE source_table='savemissindia';", DB) === 'paid|paid|UPI123|1999');
    });
    await check('delete only hides the row in the panel; undo brings it back', async () => {
      ok((await admin.req('reg.delete', { ids: [kavya()] })).status === 200);
      ok(!(await admin.req('registrations')).data.rows.some((x) => x.uid === kavya()));
      ok(sql("SELECT COUNT(*) FROM savemissindia WHERE fname='Kavya Joshi';", DB) === '1', 'row still in the form table');
      await admin.req('reg.delete', { ids: [kavya()], undo: true });
      ok((await admin.req('registrations')).data.rows.some((x) => x.uid === kavya()));
    });

    console.log('Team access (enforced by the server)');
    await check('Super Admin adds a Registration team member limited to Delhi', async () => {
      const r = await admin.req('team.save', { name: 'Neha Singh', email: 'neha@example.com', username: 'neha', role: 'caller', states: ['Delhi'], password: 'neha-password-123' });
      ok(r.status === 200, JSON.stringify(r.data));
    });
    const neha = client();
    await neha.req('me');
    await neha.req('login', { username: 'neha', password: 'neha-password-123' });
    await check('member sees only Delhi rows, without KYC', async () => {
      const r = (await neha.req('registrations')).data.rows;
      ok(r.length && r.every((x) => x.raw.state === 'Delhi'), r.map((x) => x.raw.state).join(','));
      const old = r.find((x) => x.raw.fname === 'Kavya Rao');
      ok(!('kyc' in old.raw) && old.hidden.includes('kyc'), 'KYC hidden for this role');
    });
    await check('member cannot touch a Rajasthan row, delete, confirm payments or manage the team', async () => {
      ok((await neha.req('reg.status', { ids: [kavya()], status: 'contacted' })).status === 403, 'outside her states');
      ok((await neha.req('reg.delete', { ids: [riya()] })).status === 403, 'delete');
      ok((await neha.req('reg.payment', { id: riya(), status: 'paid' })).status === 403, 'confirm payment');
      ok((await neha.req('team.save', { name: 'X', email: 'x@example.com', role: 'admin', password: 'xxxxxxxxxxxx' })).status === 403, 'team');
      ok((await neha.req('perms.set', { role: 'caller', perm: 'registrations.delete', on: true })).status === 403, 'perms');
      ok((await neha.req('reg.status', { ids: [riya()], status: 'shortlisted' })).status === 200, 'but can change status in her states');
    });
    await check('turning a permission off applies on the next request', async () => {
      await admin.req('perms.set', { role: 'caller', perm: 'registrations.edit', on: false });
      ok((await neha.req('reg.status', { ids: [riya()], status: 'contacted' })).status === 403);
      await admin.req('perms.reset', {});
      ok((await neha.req('reg.status', { ids: [riya()], status: 'contacted' })).status === 200);
    });
    await check('access turned off ends the member\'s session', async () => {
      const id = (await admin.req('team.list')).data.users.find((u) => u.username === 'neha').id;
      await admin.req('team.save', { id, name: 'Neha Singh', email: 'neha@example.com', username: 'neha', role: 'caller', states: ['Delhi'], active: false });
      ok((await neha.req('registrations')).status === 401);
    });
    await check('the last Super Admin cannot lock themselves out', async () => {
      const me = (await admin.req('me')).data.user;
      ok((await admin.req('team.save', { id: me.id, name: me.name, email: me.email, username: me.username, role: 'viewer' })).status === 400);
      ok((await admin.req('team.remove', { id: me.id })).status === 400);
    });
    await check('five wrong passwords lock the account for 15 minutes', async () => {
      const c = client(); await c.req('me');
      for (let i = 0; i < 5; i++) await c.req('login', { username: 'ritu', password: 'bad' });
      ok((await c.req('login', { username: 'ritu', password: 'correct-horse-battery' })).status === 429);
      sql("UPDATE admin_users SET failed_logins=0, locked_until=NULL WHERE username='ritu';", DB);
    });

    console.log('Pages, OTP, log');
    await check('page edits write only the known columns of more_pages', async () => {
      ok((await admin.req('pages.save', { page: '46', fields: { meta_title: 'Miss Rajasthan 2026 | FSIA', descritpion: 'New text', page_name: 'hack' } })).status === 200);
      ok(sql("SELECT CONCAT(page_name,'|',meta_title,'|',descritpion) FROM more_pages WHERE id=2;", DB) === '46|Miss Rajasthan 2026 | FSIA|New text');
      ok((await admin.req('pages.save', { page: '999', fields: { meta_title: 'x' } })).status === 404);
    });
    await check('OTP log never includes the code', async () => {
      const r = await admin.req('otp');
      ok(r.data.rows.length === 2 && !JSON.stringify(r.data).includes('123456'));
    });
    await check('every change is in the activity log with who did it', async () => {
      const r = (await admin.req('audit')).data.rows;
      ['login', 'status', 'payment', 'delete', 'team', 'page'].forEach((a) => ok(r.some((x) => x.action === a), 'no ' + a + ' entry'));
      ok(r.every((x) => x.userId), 'every entry has a member');
    });
    await check('database check lists configured and other tables', async () => {
      const d = (await admin.req('db.check')).data;
      ok(d.sources.find((s) => s.table === 'savefashiondesigner').idColumn === 'sno');
      ok(d.sources.find((s) => s.table === 'savefashiondesigner').dateColumn === 'reg_date');
      ok(d.otherTables.includes('city') && !d.otherTables.includes('admin_users'));
    });
    await check('the existing form tables were never changed', async () => { ok(checksums() === before, 'a form table changed'); });

    console.log('Browser, live mode');
    const { chromium } = require('playwright');
    const browser = await chromium.launch();
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error' && !/401|403|404/.test(m.text())) errors.push(m.text()); });
    await check('real sign-in form, then the real rows', async () => {
      await page.goto(BASE + 'index.html');
      await page.fill('#lg-user', 'ritu'); await page.fill('#lg-pass', 'wrong-one'); await page.click('button[type=submit]');
      await page.waitForSelector('.form-err:not(:empty)');
      await page.fill('#lg-pass', 'correct-horse-battery'); await page.click('button[type=submit]');
      await page.waitForSelector('.kpis');
      ok(!(await page.$('.banner')), 'no sample-data banner in live mode');
      await page.evaluate(() => { location.hash = '#/registrations'; }); await page.waitForSelector('.tbl tbody tr');
      ok((await page.$$('.tbl tbody tr')).length === 7, 'all seven rows');
      ok((await page.textContent('.tbl')).includes('Asha Thapa') && (await page.textContent('.tbl')).includes('+977'));
    });
    await check('a status change in the browser is saved in the database', async () => {
      await page.evaluate(() => { location.hash = '#/registrations?q=Meera'; }); await page.waitForTimeout(300);
      await page.selectOption('.tbl tbody tr:first-child .pill-select', 'shortlisted'); await page.waitForTimeout(600);
      ok(sql("SELECT status FROM registration_work WHERE source_table='savemrsindia';", DB) === 'shortlisted');
    });
    await check('old-form profile in the browser: fields shown, password hidden', async () => {
      await page.evaluate(() => { location.hash = '#/registrations?q=Kavya Rao'; }); await page.waitForTimeout(300);
      await page.click('.tbl tbody tr:first-child .who-name'); await page.waitForSelector('dialog.drawer[open]');
      const t = await page.textContent('dialog.drawer');
      ['Highest qualification', 'Institute', 'KYC documents', 'kyc_front.jpg', 'Password'].forEach((x) => ok(t.includes(x), 'missing ' + x));
      ok(!t.includes('plain-old-password'));
      await page.keyboard.press('Escape');
    });
    await check('Team & Access shows the Database check', async () => {
      await page.evaluate(() => { location.hash = '#/team'; }); await page.waitForSelector('.tabs');
      await page.click('.tab:has-text("Database check")'); await page.waitForSelector('text=savefashiondesigner');
      ok((await page.textContent('.view-team')).includes('table_that_does_not_exist'));
    });
    await check('no JavaScript errors', async () => { ok(!errors.length, errors.join(' | ')); });
    await browser.close();
  } finally {
    php.kill();
    fs.unlinkSync(CONFIG);
    sql('DROP DATABASE IF EXISTS ' + DB + "; DROP USER IF EXISTS '" + DBUSER + "'@'localhost';");
  }
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); try { fs.unlinkSync(CONFIG); } catch (x) {} process.exit(1); });
