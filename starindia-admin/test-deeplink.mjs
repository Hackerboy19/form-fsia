// End-to-end test of the deep link in admin-enhancements.js, on a copy of the real starindia
// admin build, with a stand-in API (synthetic profiles only, nothing real is called):
//   1. copy https://starindia.fsia.in/admin/ (index.html + assets/) into a folder, put the new
//      admin-enhancements.js next to index.html, serve the folder's parent:  python3 -m http.server 8785
//   2. BASE=http://localhost:8785/admin/ WAIT=14000 PW=$(npm root -g)/playwright node test-deeplink.mjs
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PW);
// Synthetic test profiles only.
const mk = (id, slug, name, created, extra = {}) => ({ id, slug, name, role: '', bio: '<p>Test bio</p>', category: 'awardees', categoryLabel: 'Awardees', subCategory: '9', subCategoryLabel: 'Super Women',
  accent: '#e11d48', logo: '', featured: 0, verified: 1, date_of_verification: '2026-10-15', published: 1, created_at: created + 'T10:00:00.000Z', updated_at: created + 'T10:00:00.000Z', field_values: {},
  data: { hero: { greeting: "Hi, I'm", name, accentWord: '', role: '', tagline: '', image: '', ctaLabel: 'Get in Touch', ctaUrl: '' }, stats: [], services: [], achievements: [], gallery: [], articles: [],
    contact: { phone: '', email: '', address: '', website: '', instagram: '', facebook: '', youtube: '', twitter: '', linkedin: '' },
    seo: { title: name + ' | Test', description: 'Test description', keywords: 'test', image: '', canonical: '', noindex: false, h1: name, h2a: '', h2b: '', h3a: '', h3b: '', h3c: '' },
    legacy: {}, detailsBody: '', details: [], detailsHeading: '' }, ...extra });
const P = [mk(9001, 'test-asha', 'Test Asha', '2026-05-01'), mk(9003, 'test-asha-two', 'Test Asha Two', '2026-06-01'), mk(9002, 'test-bina', 'Test Bina', '2024-03-01')];
async function run(hash, loggedIn = true) {
  const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
  if (loggedIn) await ctx.addInitScript(() => localStorage.setItem('fsia_admin_jwt', 'test-token'));
  const writes = [];
  await ctx.route('https://admin.fsia.in/**', (route) => {
    if (!['GET', 'OPTIONS'].includes(route.request().method())) writes.push(route.request().method() + ' ' + route.request().url());
    const u = new URL(route.request().url()); const path = u.pathname;
    const ok = (body) => route.fulfill({ status: 200, contentType: 'application/json', headers: { 'access-control-allow-origin': '*' }, body: JSON.stringify(body) });
    if (route.request().method() === 'OPTIONS') return route.fulfill({ status: 204, headers: { 'access-control-allow-origin': '*', 'access-control-allow-headers': '*', 'access-control-allow-methods': '*' } });
    if (path === '/api/auth/me') return loggedIn ? ok({ user: { id: 1, username: 'tester', name: 'Tester', isSuper: true, permissions: [] } }) : route.fulfill({ status: 401, body: '{}' });
    let m = path.match(/^\/api\/profiles\/(\d+)$/);
    if (m) { const p = P.find((x) => x.id === +m[1]); return p ? ok(p) : route.fulfill({ status: 404, body: '{"error":"Profile not found"}' }); }
    if (path === '/api/profiles') {
      const year = u.searchParams.get('year'), q = (u.searchParams.get('search') || '').toLowerCase();
      const items = P.filter((p) => (!year || p.created_at.startsWith(year)) && (!q || p.name.toLowerCase().includes(q) || p.slug.includes(q)));
      return u.searchParams.has('page') ? ok({ items, total: items.length, totalPages: 1, page: 1 }) : ok(items);
    }
    return ok([]);
  });
  const p = await ctx.newPage(); const errs = []; p.on('pageerror', (e) => errs.push(e.message));
  await p.goto((process.env.BASE || 'http://localhost:8785/admin/') + hash); await p.waitForTimeout(+(process.env.WAIT || 9000));
  const note = await p.locator('#fsia-open-note').innerText().catch(() => '(no note)');
  const body = await p.locator('body').innerText();
  const res = { note, hashLeft: await p.evaluate(() => location.hash), seoTabOpen: /HEADING TAGS|Heading tags|META TITLE|Meta title/i.test(body), heroTabOpen: /HERO IMAGE|Hero image/i.test(body) && /GREETING|Greeting/i.test(body), errors: errs.length, writesToApi: writes.length };
  await p.screenshot({ path: 'dl-' + (hash.match(/fsia-open=(\d+)/) || ['', 'x'])[1] + (loggedIn ? '' : '-out') + '.png' });
  await b.close(); return res;
}
console.log('SEO tab, slug search   :', await run('#fsia-open=9001&q=test-asha&n=Test%20Asha&year=2026&tab=seo'));
console.log('Hero tab, slug wrong   :', await run('#fsia-open=9002&q=wrong-slug&n=Test%20Bina&year=2024&tab=hero'));
console.log('ID that does not exist :', await run('#fsia-open=9999&q=test-asha&year=2026&tab=seo'));
console.log('Signed out             :', await run('#fsia-open=9001&q=test-asha&year=2026&tab=seo', false));
