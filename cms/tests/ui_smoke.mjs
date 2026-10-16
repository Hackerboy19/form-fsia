// Opens every admin screen at desktop and phone width and reports page errors
// and sideways overflow. Point it at a running admin (the demo build works):
//   BASE='http://localhost:8779/index.html' PW=$(npm root -g)/playwright node cms/tests/ui_smoke.mjs
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PW || 'playwright');
const BASE = process.env.BASE || 'http://localhost:5173/';
const routes = ['/', '/events', '/seo', '/homepage', '/pages/about', '/pages/our-teams', '/team', '/news', '/profiles',
  '/site-check', '/changes', '/cleanup', '/users', '/activity'];

const browser = await chromium.launch();
let bad = 0;
for (const [w, h] of [[1280, 900], [390, 844]]) {
  const page = await browser.newPage({ viewport: { width: w, height: h } });
  for (const r of routes) {
    const errors = [];
    page.removeAllListeners('pageerror');
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}#${r}`);
    await page.waitForTimeout(1500);
    const width = await page.evaluate(() => document.documentElement.scrollWidth);
    const ok = !errors.length && width <= w;
    if (!ok) bad++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${w}px ${r}${width > w ? ` overflow ${width}px` : ''}${errors.length ? ` errors: ${errors.join('; ')}` : ''}`);
  }
  await page.close();
}
await browser.close();
console.log(`${routes.length * 2} screens, ${bad} failed`);
process.exit(bad ? 1 : 0);
