// Browser test of Events & Promotions: admin creates and publishes an event, the site page shows the bar.
// Needs a copy of the CMS with /cms/promo.js and test pages at /miss-assam.php, /quickapply and / (see test-execution-report.md).
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PW);
const B = 'http://127.0.0.1:8091';
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
const errs = []; p.on('pageerror', (e) => errs.push(e.message)); p.on('dialog', (d) => d.accept());
await p.goto(B + '/cms-admin-test/'); await p.fill('#username', 'admin'); await p.fill('#password', 'Ev-Admin-Pass-1'); await p.click('button[type=submit]'); await p.waitForSelector('nav');
await p.click('nav >> text=Events & Promotions'); await p.waitForSelector('text=No events yet');
await p.click('text=+ New event'); await p.waitForSelector('text=Preview: what visitors see');
const fmt = (d) => new Date(d.getTime() + 5.5 * 3600e3).toISOString().slice(0, 16);   // India time for the inputs
const now = Date.now();
await p.locator('label:has-text("Event title") + * , label:has-text("Event title")').first();
const field = (label) => p.getByLabel(label, { exact: true });
await field('Event title *').fill('Miss India 2027 Grand Finale');
await field('Short line (optional)').fill('Auditions open in 28 states');
await field('Starts *').fill(fmt(new Date(now + 10 * 864e5)));
await field('Ends *').fill(fmt(new Date(now + 12 * 864e5)));
await field('Venue').fill('The Fern');
await field('City').fill('Jaipur');
await field('Button link (registration form) *').fill('/quickapply');
await p.locator('input[type=file]').first().setInputFiles('/tmp/claude-0/evsim/banner.png'); await p.waitForTimeout(1500);
// Never on /quickapply (the registration page itself)
await p.locator('div:has(> p.label:text-is("But never on")) textarea').fill('/quickapply');
await p.screenshot({ path: 'ev-form.png' });
await p.click('dialog footer >> text=Publish'); await p.waitForTimeout(1500);
const row = await p.locator('main li').first().innerText();
console.log('list row:', row.replace(/\n+/g, ' | '));
await p.fill('input[aria-label="Page path"]', '/miss-assam.php'); await p.locator('main button', { hasText: /^Check$/ }).click(); await p.waitForTimeout(600);
console.log('check tool:', await p.locator('main aside p.mt-2.text-sm').innerText().catch(() => 'n/a'));
await p.fill('input[aria-label="Page path"]', '/quickapply'); await p.locator('main button', { hasText: /^Check$/ }).click(); await p.waitForTimeout(600);
console.log('check tool:', await p.locator('main aside p.mt-2.text-sm').innerText().catch(() => 'n/a'));
await p.screenshot({ path: 'ev-list.png' });
// Website pages
const site = await b.newPage({ viewport: { width: 1280, height: 800 } });
await site.goto(B + '/miss-assam.php'); await site.waitForTimeout(1500);
console.log('miss-assam bar:', await site.locator('#fsia-promo').innerText().then((t) => t.replace(/\n+/g, ' | ')).catch(() => 'NONE'));
console.log('footer still reachable (body padding):', await site.evaluate(() => document.body.style.paddingBottom));
await site.screenshot({ path: 'ev-site.png' });
await site.click('#fsia-promo button[aria-label=Close]'); await site.reload(); await site.waitForTimeout(1500);
console.log('after close + reload:', await site.locator('#fsia-promo').count() ? 'shown again (BAD)' : 'stays closed');
const ex = await b.newPage(); await ex.goto(B + '/quickapply'); await ex.waitForTimeout(1500);
console.log('excluded /quickapply:', await ex.locator('#fsia-promo').count() ? 'SHOWN (BAD)' : 'not shown');
const m = await b.newPage({ viewport: { width: 390, height: 780 } }); await m.goto(B + '/'); await m.waitForTimeout(1500);
console.log('mobile home:', await m.locator('#fsia-promo').count() ? 'shown' : 'none', '| overflow:', await m.evaluate(() => document.documentElement.scrollWidth > 390));
await m.screenshot({ path: 'ev-mobile.png' });
// Cancel from the admin -> gone from the site
await p.locator('main li button', { hasText: /^Cancel$/ }).click(); await p.waitForTimeout(1200);
const s2 = await b.newPage(); await s2.goto(B + '/'); await s2.waitForTimeout(1500);
console.log('after Cancel, homepage bar:', await s2.locator('#fsia-promo').count() ? 'SHOWN (BAD)' : 'gone', '| list:', (await p.locator('main li').first().innerText()).match(/Cancelled|Live on the website/)?.[0]);
console.log('admin errors:', errs);
await b.close();
