/* Browser checks for every screen and role:  node tests/e2e.js
   Needs Playwright (npm i -D playwright && npx playwright install chromium).
   Serves the folder with the same headers as vercel.json, so a CSP problem fails the run too. */
'use strict';
const http = require('http'), fs = require('fs'), path = require('path');
const { chromium } = require('playwright');
const ROOT = path.join(__dirname, '..');
const HEADERS = JSON.parse(fs.readFileSync(path.join(ROOT, 'vercel.json'), 'utf8')).headers[0].headers;
const TYPES = { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json' };

const server = http.createServer((req, res) => {
  const p = path.join(ROOT, decodeURIComponent(req.url.split('?')[0]) === '/' ? 'index.html' : decodeURIComponent(req.url.split('?')[0]));
  if (!p.startsWith(ROOT) || !fs.existsSync(p) || fs.statSync(p).isDirectory()) { res.writeHead(404); return res.end(); }
  HEADERS.forEach((h) => res.setHeader(h.key, h.value));
  res.writeHead(200, { 'Content-Type': TYPES[path.extname(p)] || 'application/octet-stream' });
  fs.createReadStream(p).pipe(res);
});

let passed = 0, failed = 0;
async function check(name, fn) {
  try { await fn(); passed++; console.log('  ✓ ' + name); } catch (e) { failed++; console.log('  ✗ ' + name + '\n    ' + e.message.split('\n')[0]); }
}
function ok(v, msg) { if (!v) throw new Error(msg || 'expected true'); }

(async () => {
  await new Promise((r) => server.listen(0, '127.0.0.1', r));
  const base = 'http://127.0.0.1:' + server.address().port + '/';
  const browser = await chromium.launch();
  const errors = [];

  async function session(userName, viewport) {
    const ctx = await browser.newContext({ viewport: viewport || { width: 1366, height: 900 }, acceptDownloads: true });
    const page = await ctx.newPage();
    page.on('console', (m) => { if (m.type() === 'error') errors.push(userName + ': ' + m.text()); });
    page.on('pageerror', (e) => errors.push(userName + ': ' + e.message));
    await page.goto(base);
    await page.click('.who-opt:has-text("' + userName + '")');
    await page.click('button[type=submit]');
    await page.waitForSelector('.app');
    return { ctx, page };
  }
  async function go(page, hash, sel) { await page.evaluate((h) => { location.hash = h; }, hash); await page.waitForSelector(sel); await page.waitForTimeout(150); }
  async function noBrokenText(page, where) {
    const bad = await page.evaluate(() => {
      const t = document.body.innerText;
      const m = t.match(/\b(undefined|NaN|null)\b|\[object /);
      return m ? t.slice(Math.max(0, m.index - 40), m.index + 40) : '';
    });
    ok(!bad, where + ' shows broken text: …' + bad + '…');
  }

  console.log('Super Admin');
  {
    const { ctx, page } = await session('Ritu Sharma');
    await check('every screen opens with no broken text', async () => {
      for (const [hash, sel] of [['#/dashboard', '.kpis'], ['#/registrations', '.tbl tbody tr'], ['#/pages/100', '.ed-form'], ['#/otp', '.tbl tbody tr'], ['#/team', '.tbl'], ['#/settings', '.set-row']]) {
        await go(page, hash, sel); await noBrokenText(page, hash);
      }
    });
    await check('quick view “Data problems” lists only rows with problems', async () => {
      await go(page, '#/registrations', '.tbl tbody tr');
      await page.click('.qviews .chip:has-text("Data problems")'); await page.waitForTimeout(200);
      const n = await page.$$eval('.tbl tbody tr', (r) => r.length), dots = await page.$$eval('.tbl tbody .issue-dot', (r) => r.length);
      ok(n > 0 && n === dots, n + ' rows but ' + dots + ' warning signs');
      ok(await page.$('.fchip:has-text("Has a data problem")'), 'filter chip shown');
    });
    await check('removing a filter chip removes that filter', async () => {
      await page.click('.fchip:has-text("Has a data problem")'); await page.waitForTimeout(200);
      ok(!(await page.$('.fchip')), 'no chips left');
      ok(!(await page.evaluate(() => location.hash)).includes('data='), 'hash cleared');
    });
    await check('age range and city filters', async () => {
      await page.click('.more-btn'); await page.fill('[aria-label="Age from"]', '30'); await page.press('[aria-label="Age from"]', 'Tab');
      await page.fill('[aria-label="Age to"]', '35'); await page.press('[aria-label="Age to"]', 'Tab'); await page.waitForTimeout(250);
      const ages = await page.$$eval('.tbl tbody .who .small', (e) => e.map((x) => +(x.textContent.match(/Age (\d+)/) || [])[1]));
      ok(ages.length && ages.every((a) => a >= 30 && a <= 35), 'ages ' + ages.join(','));
      await page.click('.link-btn:has-text("Clear all")'); await page.waitForTimeout(200);
    });
    await check('payment: create link, save, WhatsApp message, mark as paid', async () => {
      await go(page, '#/registrations?pay=none&status=contacted', '.tbl tbody tr');
      await page.click('.tbl tbody tr:first-child .who-name'); await page.waitForSelector('dialog.drawer[open]');
      const name = await page.textContent('#dr-title');
      await page.fill('[aria-label="Amount in rupees"]', 'abc').catch(() => {});
      await page.click('dialog.drawer button:has-text("Create link")');
      await page.click('dialog.drawer .pay-card button:has-text("Save")'); await page.waitForTimeout(250);
      ok((await page.textContent('dialog.drawer .pay-card')).includes('Link sent'), 'status should be Link sent');
      const wa = await page.getAttribute('dialog.drawer a:has-text("Send on WhatsApp")', 'href');
      ok(wa && decodeURIComponent(wa).includes('https://pay.example.com/pay/FSIA-'), 'WhatsApp text carries the link');
      await page.click('dialog.drawer button:has-text("Mark as paid")'); await page.fill('[aria-label="Payment reference"]', 'UPI123');
      await page.click('dialog.modal button:has-text("Mark as paid")'); await page.waitForTimeout(300);
      ok((await page.textContent('dialog.drawer .pay-card')).includes('Ref UPI123'), 'reference shown');
      ok((await page.textContent('dialog.drawer .seg-btn.is-on')).includes('Fee paid'), 'status became Fee paid');
      await page.keyboard.press('Escape');
      await go(page, '#/team', '.tabs'); await page.click('.tab:has-text("Activity log")');
      ok((await page.textContent('.view-team')).includes('Marked the payment of ' + name), 'activity log has the payment');
    });
    await check('invalid payment link is refused', async () => {
      await go(page, '#/registrations?pay=none', '.tbl tbody tr');
      await page.click('.tbl tbody tr:first-child .who-name'); await page.waitForSelector('dialog.drawer[open]');
      await page.fill('[aria-label="Payment link"]', 'http://not-secure.example.com');
      await page.click('dialog.drawer .pay-card button:has-text("Save")');
      ok((await page.textContent('dialog.drawer .form-err')).includes('https://'), 'error message shown');
      await page.keyboard.press('Escape');
    });
    await check('old-form profile shows every saved field, password stays hidden', async () => {
      const id = await page.evaluate(() => FSIA.reg.all().find((g) => g.extras.some((e) => e.key === 'kyc')).id);
      await go(page, '#/registrations?open=' + id, 'dialog.drawer[open]');
      const t = await page.textContent('dialog.drawer');
      ['Highest qualification', 'Total marks', 'Institute', 'Work experience', 'Address', 'KYC documents', 'Password'].forEach((l) => ok(t.includes(l), 'missing ' + l));
      ok(!t.includes('stored-by-old-form'), 'password value must never be shown');
      ok(t.includes('_front.jpg'), 'Super Admin sees KYC file names');
      await page.keyboard.press('Escape');
    });
    await check('Miss World applicant from abroad: country code kept in number and WhatsApp link', async () => {
      const id = await page.evaluate(() => (FSIA.reg.all().find((g) => g.cc && g.cc !== '91') || {}).id);
      ok(id, 'sample has a foreign applicant');
      await go(page, '#/registrations?open=' + id, 'dialog.drawer[open]');
      const t = await page.textContent('dialog.drawer'), wa = await page.getAttribute('dialog.drawer a:has-text("WhatsApp")', 'href');
      ok(/\+(977|971|44) /.test(t) && t.includes('Country'), 'shows +country code and country');
      ok(/wa\.me\/(977|971|44)\d+$/.test(wa), 'WhatsApp link uses the country code: ' + wa);
      await page.keyboard.press('Escape');
    });
    await check('CSV download includes payment and old-form columns', async () => {
      await go(page, '#/registrations', '.tbl tbody tr');
      const [dl] = await Promise.all([page.waitForEvent('download'), page.click('.page-actions button:has-text("Download CSV")')]);
      const head = fs.readFileSync(await dl.path(), 'utf8').split('\r\n')[0];
      ['Payment link', 'Payment status', 'Assigned to', 'Data check', 'KYC documents', 'Highest qualification'].forEach((c) => ok(head.includes(c), 'missing column ' + c));
      ok(!head.includes('Password'), 'password column must not be exported');
    });
    await check('add a team member, with validation', async () => {
      await go(page, '#/team', '.tbl');
      await page.click('.tab:has-text("Team members")');
      await page.click('button:has-text("Add team member")');
      await page.click('dialog.modal button:has-text("Add member")');
      ok((await page.textContent('dialog.modal .form-err')).includes('full name'), 'asks for a name');
      await page.fill('dialog.modal input[type=text]', 'Test Caller'); await page.fill('dialog.modal input[type=email]', 'test@example.com');
      await page.check('dialog.modal input[value=some]'); await page.check('dialog.modal input[value="Bihar"]');
      await page.click('dialog.modal button:has-text("Add member")'); await page.waitForTimeout(200);
      ok((await page.textContent('.view-team')).includes('Test Caller'), 'member listed');
    });
    await check('roles matrix: Super Admin column is locked', async () => {
      await page.click('.tab:has-text("Roles & access")');
      ok(!(await page.$('.matrix tbody input[aria-label^="Super Admin"]')), 'no editable Super Admin boxes');
      const always = await page.$$eval('.matrix .mx-always', (e) => e.length);
      ok(always === await page.evaluate(() => FSIA.PERMS.length), 'every permission shows “Always” for Super Admin');
    });
    await ctx.close();
  }

  console.log('Registration team (scoped to 4 states)');
  {
    const { ctx, page } = await session('Neha Singh');
    await check('sees only Registrations-related menus', async () => {
      const nav = await page.$$eval('.sb-nav li:not([hidden]) .sb-link span:not(.ico):not(.sb-badge)', (e) => e.map((x) => x.textContent));
      ok(!nav.includes('Team & Access') && !nav.includes('Forms & Pages'), nav.join(', '));
    });
    await check('only her states, no delete, no download', async () => {
      await go(page, '#/registrations', '.tbl tbody tr');
      const states = await page.evaluate(() => [...new Set(FSIA.reg.all().map((g) => g.state))]);
      ok(states.every((s) => ['Rajasthan', 'Delhi', 'Haryana', 'Punjab'].includes(s)), states.join(','));
      ok(!(await page.$('.page-actions button:has-text("Download CSV")')), 'no download');
      await page.click('.tbl tbody tr:first-child .who-name'); await page.waitForSelector('dialog.drawer[open]');
      ok(!(await page.$('dialog.drawer .dr-foot button:has-text("Delete")')), 'no delete');
      ok(await page.$('dialog.drawer button:has-text("Create link")'), 'can create payment link');
      ok(!(await page.$('dialog.drawer button:has-text("Mark as paid")')), 'cannot confirm payment');
      await page.keyboard.press('Escape');
    });
    await check('opening Team & Access by URL shows “no access”', async () => {
      await go(page, '#/team', '.empty.big'); ok((await page.textContent('#view')).includes('You do not have access'));
    });
    await ctx.close();
  }

  console.log('Content editor');
  {
    const { ctx, page } = await session('Pooja Nair');
    await check('starts on a pages-only dashboard and cannot open registrations', async () => {
      await page.waitForSelector('.kpis'); ok(!(await page.textContent('#view')).includes('Waiting for payment'));
      await go(page, '#/registrations', '.empty.big');
    });
    await check('can edit and save a page', async () => {
      await go(page, '#/pages/100', '.ed-form'); await page.fill('#f-meta_title', 'Miss Delhi 2026 | Forever Star India');
      await page.click('.ed-actions .btn-primary'); await page.waitForTimeout(200);
      ok(await page.$eval('.ed-actions .btn-primary', (b) => b.disabled), 'saved');
    });
    await ctx.close();
  }

  console.log('Accounts');
  {
    const { ctx, page } = await session('Sandeep Rao');
    await check('can see the waiting-for-payment list and confirm a payment', async () => {
      await go(page, '#/registrations?pay=sent', '.tbl tbody tr');
      await page.click('.tbl tbody tr:first-child .who-name'); await page.waitForSelector('dialog.drawer[open]');
      ok(await page.$('dialog.drawer button:has-text("Mark as paid")'));
      ok(await page.$eval('dialog.drawer .seg-btn:not(.is-on)', (b) => b.disabled), 'status is read-only for Accounts');
      await page.keyboard.press('Escape');
    });
    await ctx.close();
  }

  console.log('Phone screen');
  {
    const { ctx, page } = await session('Ritu Sharma', { width: 390, height: 844 });
    await check('no sideways scrolling on any screen', async () => {
      for (const [hash, sel] of [['#/dashboard', '.kpis'], ['#/registrations', '.tbl tbody tr'], ['#/pages/100', '.ed-form'], ['#/team', '.tbl']]) {
        await go(page, hash, sel);
        const over = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        ok(over <= 0, hash + ' overflows by ' + over + 'px');
      }
    });
    await ctx.close();
  }

  await check('no JavaScript or CSP errors in any session', async () => { ok(!errors.length, errors.join(' | ')); });
  await browser.close(); server.close();
  console.log('\n' + passed + ' passed, ' + failed + ' failed');
  process.exit(failed ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
