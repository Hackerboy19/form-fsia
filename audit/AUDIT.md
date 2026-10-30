# fsia.in — Front-end, Back-end & SEO Audit

_Audit date: 2026-10-30. Scope: this repo (73 registration-form pages) plus the live site at https://www.fsia.in (sitemaps, rendered HTML, headers, real-browser timings)._

Data files in this folder:

| File | What it is |
|---|---|
| `repo-pages-missing-from-sitemap.txt` | 53 live form pages from this repo that no sitemap lists |
| `live-pages-missing-from-sitemap.tsv` | Every internal link found on the homepage/header/footer/forms that is not in any sitemap, with its live status |
| `sitemap-broken-urls.tsv` | Every sitemap URL that does not return 200 (status, redirect target, URL) |
| `sitemap-slow-urls.tsv` | Sitemap URLs with server response (TTFB) above 2 s |

---

## 0. Headline findings (fix these first)

| # | Severity | Finding | Where |
|---|---|---|---|
| 1 | **Critical** | **Every static asset and page is served `Cache-Control: no-store`.** CSS, JS, images and logo are re-downloaded on every page view; nothing is ever cached by browsers or a CDN. | nginx/Plesk config |
| 2 | **Critical** | **`bharat-couture-week-fashion-week.php` declares canonical `register-bharat-couture.php`, which 302-redirects to the homepage.** Google is being told this page is a duplicate of a redirect to `/` → it will drop the page from the index. | `bharat-couture-week-fashion-week.php` |
| 3 | **Critical** | **52 live form pages from this repo (plus 7 other live pages) are missing from every sitemap**: 25 of 31 state Miss pages, 26 of 32 state Mrs pages, and India Fashion Week. | see §3 |
| 4 | **High** | **Tailwind Play CDN (`cdn.tailwindcss.com`) on all 73 pages.** 126 KB (br) render-blocking JS that compiles CSS in the browser on every load; browser console prints "should not be used in production". | every form `<head>` |
| 5 | **High** | **Second public OTP endpoint with no rate limit.** Each state page has an inline `?action=send_otp` / `?action=verify_otp` handler that is reachable on live (`/miss-delhi.php?action=verify_otp…` answers `Content-Type: application/json` with HTTP 500). By the code, `send_otp` sends a WhatsApp message to any number on demand with no throttling — an abuse vector and a cost leak. (Not exercised during the audit, to avoid sending a real message.) The front-end doesn't use it; it calls `send_otpnew.php`. | 66 state pages, lines ~22–63 |
| 6 | **High** | OTP generated with `rand()` (not cryptographically secure), no attempt limit on verify (1,000,000 codes, 10-minute window), TLS verification disabled on the WhatsApp webhook call, and the webhook ID is hard-coded in 67 files. | 68 pages |
| 7 | **High** | **192 of 5,519 sitemap URLs (3.5%) are broken**: 80 → 404, **76 → HTTP 500** (server errors on every retry), 22 → 302 to the homepage (soft 404s), 10 → 302 to another page, 3 on the wrong host, 1 with a literal space. ~177 of them sit in `sitemap-complete.xml`. | `sitemap-broken-urls.tsv` |
| 8 | **High** | Sitemaps are effectively frozen: child sitemaps have **no `<lastmod>`**, the index `lastmod` values are 2023–2024, and **only 3 of 5,519 URLs are 2026 content** while the site is running the 2026 season. | `sitemap.xml` + children |
| 9 | **Medium** | No CSRF tokens on any form (0 of 73). | all forms |
| 10 | **Medium** | 73 files are ~95% identical copies (`miss-delhi.php` vs `miss-goa.php`: 42 of 855 lines differ). Every fix must be made 64–73 times, which is how drift and bugs creep in. | whole repo |

---

## 1. Performance — what is slow, what is fast

### 1.1 Measured (headless Chromium, mobile viewport, no throttling, from a US datacenter)

| Page | TTFB | FCP | LCP | CLS | Requests | Transferred |
|---|---|---|---|---|---|---|
| `/` (homepage) | 0.99 s | 2.15 s | **4.09 s (poor)** | 0.071 | 23 | **1.55 MB** |
| `/forever-miss-india-new.php` | 0.96 s | 2.18 s | 2.18 s | 0.004 | 17 | 88 KB |
| `/miss-delhi.php` | 1.07 s | 2.75 s | 2.75 s | 0 | 18 | 83 KB |
| `/mrs-kerala.php` | 0.59 s | 2.02 s | 2.02 s | 0.009 | 18 | 85 KB |
| `/makeup-artist.php` | 0.60 s | 1.71 s | 1.71 s | 0.009 | 18 | 92 KB |
| `/forever-fashion-week.php` | 0.56 s | 1.92 s | 1.92 s | 0.002 | 18 | 87 KB |

All 72 deployed form pages: average TTFB **0.87 s** from the test location (max 1.66 s — `channel-partner.php`), raw HTML **165–193 KB** each (≈37 KB gzipped).

**Important baseline:** a static file (`robots.txt`) has TTFB ≈ 0.77 s from the same location (≈0.5 s of that is the TLS handshake over the distance). PHP pages come in at 0.79–0.98 s, so **server-side processing is only ~20–200 ms. The back-end is fast; the slowness users feel is front-end.** Across all 5,325 working sitemap URLs TTFB was p50 0.84 s / p90 0.88 s / p99 1.06 s — consistent and healthy; only 11 URLs exceeded 1.5 s (`sitemap-slow-urls.tsv`).

On a real Indian 4G phone with CPU throttling, expect these numbers to be **2–3× worse**; the form pages will sit around LCP 4–6 s.

### 1.2 What's fast / fine
- HTTP/2, gzip on HTML, Brotli on CSS/JS — compression is on.
- Layout shift on form pages is very low (CLS ≤ 0.01).
- Form pages are light in bytes (≈85 KB transferred) and DOM size is reasonable (660–1,400 nodes).
- Host/scheme normalisation works: `http://`, `fsia.in`, and trailing `/` all 301 to `https://www.fsia.in/...`. Unknown URLs return a real 404.

### 1.3 What's slow and why (in order of impact)

1. **`Cache-Control: no-store` on everything** (`/assets-new/css/*`, `/cms/*.js`, `/uploads/*.webp`, `logo.gif`). Every repeat visit and every page-to-page click re-fetches all of it.
   _Fix:_ for `/assets-new/`, `/static/`, `/uploads/`, `/cms/*.js` send `Cache-Control: public, max-age=31536000, immutable` (files are already versioned with `?v=` or hashed names); keep `no-store` only on the HTML of pages that truly need it.
2. **Every page starts a PHP session and sends `no-store` on the HTML** (`Set-Cookie: PHPSESSID` for every anonymous visitor). This also makes the pages uncacheable by any CDN. Start the session only when the user begins OTP/submit.
3. **Tailwind Play CDN** — render-blocking 126 KB script, then a runtime CSS compile before first paint. Build Tailwind once (CLI/PostCSS) to a static, purged CSS file (typically 10–25 KB).
4. **Render-blocking chain in `<head>`**: 2 separate Google Fonts stylesheets (Playfair/Poppins + Outfit), 4 site CSS files, Tailwind, jQuery 3.6.0 (full build), all without `defer`/`preconnect`/`preload`. Merge the CSS, add `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>`, `defer` jQuery and the CMS scripts.
5. **`/cms/calendar.js` + `/cms/promo.js` + `sections.php?page=index&key=calendar_events`** — the three slowest requests on every form page (1.0–2.1 s), loaded on registration forms where the calendar/promo isn't the point. Load them after `load` or only where needed.
6. **~95 KB of inline `<style>`/`<script>` per page** (55 KB CSS + 40 KB JS, duplicated in all 73 files) — none of it can be cached. Move to one shared, cacheable file.
7. **Homepage**: 1.55 MB, LCP 4.1 s. 41 `<img>` with **no `width`/`height`** (causes CLS 0.071), 10 without `alt`, partner logos served as JPG with spaces in filenames, several images duplicated in the markup. Convert to WebP/AVIF, add dimensions, `fetchpriority="high"` on the hero, lazy-load the rest (33/41 already are).
8. **A few slow outliers on the server** — only 11 of 5,325 URLs had TTFB > 1.5 s (worst 2.4 s: `miss-uttar-pradesh-2021-first-runner-up-hemlata-kashyap`, `shubhangi-sharma-miss-india-2022-id-26764`, `sitemap.php` 2.0 s). Check these queries; the rest of the back-end is fine.
9. **TLS handshake** takes ~0.5 s from outside India. Enabling TLS 1.3 + session resumption, or putting the site behind a CDN with Indian and global edges (e.g. Cloudflare), cuts this for every visitor.

### 1.4 Back-end speed summary

| Area | Verdict |
|---|---|
| PHP page generation | ✅ Fast (~20–200 ms) |
| CMS/profile pages (5,325 URLs) | ✅ Consistent, p99 ≈ TTFB of a static file + 0.3 s |
| Slow outliers | ⚠️ 11 URLs > 1.5 s (`sitemap-slow-urls.tsv`) |
| Errors | ❌ 76 sitemap URLs return HTTP 500 consistently — full list in §3.2.1 |
| Caching headers | ❌ `no-store` on everything |
| Sessions | ❌ every anonymous page view starts a PHP session |

---

## 2. Back-end (code in this repo)

### 2.1 Security

| Issue | Detail | Fix |
|---|---|---|
| Inline OTP handler is a live, unthrottled endpoint | `if (isset($_GET['action']))` block at the top of 66 state pages. `?action=send_otp&phone=…` sends a WhatsApp message; no rate limit, no captcha, no session binding. Front-end uses `send_otpnew.php` instead, so this block is dead weight. | Delete the block from all pages; keep a single OTP endpoint. |
| Weak OTP | `rand(100000, 999999)` | `random_int(100000, 999999)` |
| OTP brute force | `verify_otp` has no attempt counter; 10-minute expiry | Lock after 5 failed attempts per phone/IP; rate-limit `send_otp` per phone and IP (e.g. 3 / 10 min). |
| TLS verification off | `CURLOPT_SSL_VERIFYPEER => false`, `CURLOPT_SSL_VERIFYHOST => false` on the ClickChat call (67 files) | Remove both lines. |
| Hard-coded secret | ClickChat webhook ID in 67 files committed to git | Move to `config.php`/env and rotate the webhook ID (it is in git history). |
| No CSRF protection | 0 of 73 forms carry a token | Add a per-session token in the shared form helper; check it in each `save*.php`. |
| Server-side OTP enforcement unverified | The front-end gates submit with a JS flag (`isPhoneVerified`). Whether `savemissindia-new.php` / `savemrsindia-new.php` re-check `$_SESSION['otp_verified']` can't be confirmed from this repo. | Confirm every `save*.php` rejects submissions without `$_SESSION['otp_verified']` for the same `rmob`. |
| Client-controlled `category` / `landing` | Hidden inputs (`category` on 34 pages, `landing` on 64) decide where a registration is filed. | Derive them server-side from the page, or whitelist them. |
| Raw SQL with string escaping | OTP queries use `mysqli_real_escape_string` + string concatenation. Works, but fragile. | Prepared statements. |

### 2.2 Correctness / maintainability

- **Not deployed:** `miss-universe-registration.php` returns 404 on live, but shares CMS meta row `page_name='145'` with `miss-universe-beauty-pageant.php` and posts to `action=""` (self) with an include of `process_registration.php` that isn't in the repo. Either deploy it with its own meta row or delete it.
- **Inline OTP handler returns HTTP 500** live (e.g. `?action=verify_otp`), so it's both an attack surface and broken.
- **Duplicated code:** 73 files × ~52 KB. Build one template (`registration-form.php`) driven by a per-page config array (state, title, meta, category, save URL). The recent history (`Match the … design across all 68 registration forms`, `… on 64 state forms`) shows how much time the copies cost.
- **Dependencies missing from this repo** (live only): `config.php`, `header1806.php`, `footer1806.php`, `form_header.php`, `faq-section.php`, `state-city.php`, `process_registration.php`, all 8 `save*.php` handlers, `send_otpnew.php`, `verify_otpnew.php`, `get_city_name.php`, `save_reg_profile.php`, and `/assets-new/`. The repo can't be run, tested or reviewed end-to-end; bring them in (with secrets moved to env).
- **Typo in CMS meta key** `descritpion` (used consistently, so it works — but any new code using `description` will silently get nothing).
- **Accessibility:** `makeup-artist.php` has 8 inputs with no associated `<label>`/`aria-label`; `forever-miss-india-new.php` has 2.

---

## 3. Sitemap ↔ pages reconciliation

**Live sitemap:** `https://www.fsia.in/sitemap.xml` is an index of 25 child sitemaps → **5,631 `<loc>` entries, 5,519 unique URLs**. `robots.txt` points to it correctly.

### 3.1 Pages we have, but no sitemap lists (59 live pages)

**From this repo (52 live + 1 not deployed)** — all return 200 at `.php`, 404 without it:

- **Miss (25 of 31 states):** andhra-pradesh, arunachal-pradesh, assam, bihar, chandigarh, chattisgarh, delhi, goa, haryana, himachal-pradesh, jammu-kashmir, jharkhand, kerala, madhya-pradesh, manipur, meghalaya, mizoram, nagaland, odisha, poducherry, punjab, sikkim, tamil-nadu, tripura, uttarakhand. _(In sitemap: gujarat, karnataka, maharashtra, rajasthan, telangana, uttar-pradesh, west-bengal.)_
- **Mrs (26 of 32 states):** andhra-pradesh, arunachal-pradesh, assam, bihar, chandigarh, chattisgarh, gujarat, haryana, himachal-pradesh, jammu-kashmir, jharkhand, karnataka, madhya-pradesh, maharashtra, manipur, meghalaya, mizoram, nagaland, punjab, sikkim, tamil-nadu, telangana, tripura, uttar-pradesh, uttarakhand, west-bengal. _(In sitemap: delhi, goa, kerala, odisha, poducherry, rajasthan.)_
- **Other:** `india-fashion-week-fashion-show.php`; `miss-universe-registration.php` (in repo, **not deployed — 404**)

The 20 repo pages that _are_ in the sitemap: 18 as `.php`; `bharat-couture-week-fashion-week` and `channel-partner` as clean URLs (both also answer at `.php`).

**Elsewhere on the site (7)** — linked from the header/footer/homepage, return 200, not in any sitemap:
`business-awards.php`, `international-award.php`, `miss-fsia-international-2024.php`, `mrs-fsia-international-2024.php`, `news-coverage` (and also `news-coverage.php` — same page at two URLs), `star-india-kids-contest.php`, `termscondition.php`.

Full list with status codes: `live-pages-missing-from-sitemap.tsv`.

### 3.2 Pages in the sitemap that we don't have (broken sitemap entries)

Crawled every one of the 5,519 sitemap URLs (10 parallel requests, then retried every 500/timeout once). **5,327 return 200; 192 do not.** Full list with status and redirect target: `sitemap-broken-urls.tsv`.

| Problem | Count | Examples | Fix |
|---|---|---|---|
| **404 Not Found** | 80 | profile/winner pages that were deleted or renamed | Remove from sitemap; 301 to the replacement where one exists |
| **500 Server Error** (every retry) | 76 | 42 are `…-miss-india-2021` / `…-mrs-india-2021` slugs without an `-id-` (e.g. `/aashi-chariya-miss-india-2021`); 15 are `…-is-coming-for-the-grand-finale`; others like `/miss-india-2021-pratima-sarkar`, `/talent-video-of-miss-india-participant-drishti-kohli` | These are code crashes in the profile/video router, not missing pages — check the PHP error log for these slugs. Fix or 410 them. Google treats repeated 5xx as a site-health signal. |
| **302 → homepage** (soft 404) | 22 | `/amerika-dewangan-mrs-india-2022-id-27776`, `/miss-india-contestant-2025-riddhi-jamnagar`; several redirect to `https://fsia.in/` (non-www → second hop) | Return a real 404/410, or 301 to the right profile; drop from sitemap |
| **302 → another page** | 10 | `/forever-star-kids-contest.php` → `/star-india-kids-contest`; `/fsia-awards.php` → `/international-award.php`; `/media-coverage.php` → `/news-coverage.php`; `/mrs-fsia-rising-star-2022-india-neha-khare` → same slug with **uppercase** `Neha-Khare` | List the target URL in the sitemap; make these redirects **301** (permanent), not 302 |
| Wrong host/scheme | 3 | `https://fsia.in/…`, `http://www.fsia.in/…` | Emit `https://www.fsia.in` only |
| Literal space in URL | 1 | `/miss-andhra-pradesh-2022-divya krishna` | Invalid XML-sitemap URL; slugify |

#### 3.2.1 All 76 URLs returning HTTP 500

Each one returned 500 on the first crawl and again on a retry. Also in `sitemap-broken-urls.tsv`.

**A. `…-miss-india-2021` / `…-mrs-india-2021` slugs with no `-id-` (42)**

1. https://www.fsia.in/aashi-chariya-miss-india-2021
2. https://www.fsia.in/ankana-pal-miss-india-2021
3. https://www.fsia.in/ankita-shetty-mrs-india-2021
4. https://www.fsia.in/ankita-toshniwal-miss-india-2021
5. https://www.fsia.in/bhavya-gaur-miss-india-2021
6. https://www.fsia.in/dipti-ketan-loat-mrs-india-2021
7. https://www.fsia.in/dr-ampritpal-kaur-mrs-india-2021
8. https://www.fsia.in/dr-priyanka-suthariya-mrs-india-2021
9. https://www.fsia.in/dr-sonali-soner-miss-india-2021
10. https://www.fsia.in/dr.-somyashree-patnaik-miss-india-2021
11. https://www.fsia.in/er-meena-mandal-mrs-india-2021
12. https://www.fsia.in/gopika-sajikumar-mrs-india-2021
13. https://www.fsia.in/gundeep-kaur-miss-india-2021
14. https://www.fsia.in/guneshwari-ghatge-miss-india-2021
15. https://www.fsia.in/hinal-shah-jain-mrs-india-2021
16. https://www.fsia.in/jenny-koshy-miss-india-2021
17. https://www.fsia.in/karishma-kaur-miss-india-2021
18. https://www.fsia.in/manasmita-palo-mrs-india-2021
19. https://www.fsia.in/manikanta-jain-mrs-india-2021
20. https://www.fsia.in/manya-vats-miss-india-2021
21. https://www.fsia.in/meenakshi-sharma-mrs-india-2021
22. https://www.fsia.in/naomi-ribeiro-miss-india-2021
23. https://www.fsia.in/neena-jose-miss-india-2021
24. https://www.fsia.in/neha-barwase-miss-india-2021
25. https://www.fsia.in/neha-yadav-mrs-india-2021
26. https://www.fsia.in/pardeep-kaur-mrs-india-2021
27. https://www.fsia.in/pooja-mishra-miss-india-2021
28. https://www.fsia.in/priti-pankaj-khandelwal-mrs-india-2021
29. https://www.fsia.in/priyanka-bharat-shah-mrs-india-2021
30. https://www.fsia.in/rajni-yadav-miss-india-2021
31. https://www.fsia.in/sara-rayees-miss-india-2021
32. https://www.fsia.in/sarita-jadhav-mrs-india-2021
33. https://www.fsia.in/sasmita-tripathy-mrs-india-2021
34. https://www.fsia.in/satabdi-roy-debnath-mrs-india-2021
35. https://www.fsia.in/sheron-kalsi-miss-india-2021
36. https://www.fsia.in/shobitha-mallepula-miss-india-2021
37. https://www.fsia.in/sonal-wadhwa-arora-mrs-india-2021
38. https://www.fsia.in/suman-mishra-miss-india-2021
39. https://www.fsia.in/supriya-nayak-miss-india-2021
40. https://www.fsia.in/surarchi-kumar-miss-india-2021
41. https://www.fsia.in/tabassum-shaikh-miss-india-2021
42. https://www.fsia.in/teaser-launch-grand-finale-miss-india-and-mrs-india-2021

**B. `…-is-coming-for-the-grand-finale` (15)**

1. https://www.fsia.in/amrita-shrivastava-is-coming-for-the-grand-finale
2. https://www.fsia.in/dimpy-jain-is-coming-for-the-grand-finale
3. https://www.fsia.in/dr.-rakhi-is-coming-for-the-grand-finale
4. https://www.fsia.in/glory-kalai-is-coming-for-the-grand-finale
5. https://www.fsia.in/jasmine-rayat-is-coming-for-the-grand-finale
6. https://www.fsia.in/lipi-malakar-is-coming-for-the-grand-finale
7. https://www.fsia.in/madhuri-danane-is-coming-for-the-grand-finale
8. https://www.fsia.in/nayana-basarani-is-coming-for-the-grand-finale
9. https://www.fsia.in/prashasti-is-coming-for-the-grand-finale
10. https://www.fsia.in/prerna-nayyar-is-coming-for-the-grand-finale
11. https://www.fsia.in/rajlaxmi-chavan-is-coming-for-the-grand-finale
12. https://www.fsia.in/sadhna-singh-is-coming-for-the-grand-finale
13. https://www.fsia.in/samidha-is-coming-for-the-grand-finale
14. https://www.fsia.in/smitha-xavier-is-coming-for-the-grand-finale
15. https://www.fsia.in/sonu-khoshya-is-coming-for-the-grand-finale

**C. Other profile, video and article pages (19)**

1. https://www.fsia.in/forever-miss-india-2021-building-smile,-confidence,-style,-and-personality
2. https://www.fsia.in/how-forever-star-india-transforms-you-and-taking-out-your-inner-beauty
3. https://www.fsia.in/jenny-khoshy-miss-india
4. https://www.fsia.in/miss-india-2021-bhavika-kiran-tilekar
5. https://www.fsia.in/miss-india-2021-pratima-sarkar
6. https://www.fsia.in/miss-india-amrita-srivastava
7. https://www.fsia.in/miss-india-contestant-samprikta-mandal
8. https://www.fsia.in/miss-india-purva-parate-makeup-and-grooming
9. https://www.fsia.in/miss-tamil-nadu-2022-nishoja-d-1st-runner-up
10. https://www.fsia.in/miss-tamil-nadu-2022-rakshya
11. https://www.fsia.in/multi-talent-candidate-performance-by-mrs-india-participant-piyali-de
12. https://www.fsia.in/performance-of-miss-india-2021-shubhi-bhadauriya
13. https://www.fsia.in/performance-of-miss-india-participant-namya-shrivastava
14. https://www.fsia.in/pratima-sarkar-miss-india-2021-contestant
15. https://www.fsia.in/preparing-for-the-ramp-walk-miss-india-contestant-ria-paul
16. https://www.fsia.in/super-model-performance-by-miss-india-participant-purva-parate
17. https://www.fsia.in/sure-movie-performance-by-miss-india-participant-radhika-rane
18. https://www.fsia.in/talent-video-of-miss-india-participant-drishti-kohli
19. https://www.fsia.in/transformation-makes-you-perfect-sara-rayees-miss-india-2021-participant

Also in the sitemap and worth removing/rewriting: **123 query-string URLs** (`verified_user_details_women.php?id=…&view=1&name=…` ×74, `verified_user_details.php?id=…` ×49). They are duplicates of the clean profile slugs and some redirect targets above use the same `?id=…&view=1` pattern — give them clean slugs or a canonical and drop them from the sitemap.

### 3.3 Sitemap hygiene problems

| Problem | Count | Fix |
|---|---|---|
| URLs listed more than once (within a file or across files) | 110 extra entries; e.g. all 46 `state-winners-2023` and 42 `city-winners-2023` URLs are also in `sitemap-complete.xml` | De-duplicate at generation time |
| Non-canonical host/scheme in `<loc>` | 3 (`https://fsia.in/…`, `http://www.fsia.in/…`, `http://fsia.in/…`) | Always emit `https://www.fsia.in` |
| Child sitemaps with no `<lastmod>` | 25 / 25 (index lastmods are hand-set 2023–2024) | Emit real `lastmod` from the DB `updated_at` |
| URL style mixed: `.php` vs extensionless | 163 `.php`, rest extensionless; 70 of 72 form pages **404 without `.php`** | Pick one. Either add an nginx rule so `/miss-delhi` serves `miss-delhi.php` and 301 `.php` → clean, or keep `.php` everywhere — but the sitemap, canonical, and internal links must all agree |
| Uppercase / encoded-space URLs | 136 with uppercase, 65 with `%20`/spaces (e.g. `/Miss-West-Bengal-2022-2nd-Runner-Up-Priyanka-Pal`, `/FAQ.php`) | Lower-case, hyphenated slugs; 301 the old ones |
| Same page live at two URLs | `bharat-couture-week-fashion-week`, `bharat-national-awards`, `channel-partner`, `news-coverage` answer at both `/x` and `/x.php` (200 both); nav links `.php`, sitemap lists clean | 301 one form to the other |
| Hub pages | ✅ Checked 31 nav/hub pages (about, contact, forever-miss/mrs/teen-india-new, super-woman/hero, galleries, nominate-yourself, quickapply, …) — all present | — |
| No 2026 sitemaps | Season-2026 contestants/winners/profiles have no sitemap; only 3 URLs contain "2026" | Generate `*-2026-*.xml` children (or better: generate all children dynamically from the DB) |
| One child is 635 KB / 4,332 URLs (`sitemap-complete.xml`) | Within limits (50,000 / 50 MB) but a catch-all | Split by type so Search Console shows per-section indexing |

**Recommendation:** replace the hand-maintained XML files with a single `sitemap.php` generator (DB-driven + a static list of form/hub pages), cached for an hour. That fixes missing pages, dupes, `lastmod`, host and year drift in one place.

---

## 4. On-page SEO (live rendered HTML of the 72 deployed form pages)

### 4.1 Good
- Every page has `<title>`, meta description, canonical, `robots: index, follow`, OG + Twitter tags.
- JSON-LD on every page: `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `Event` (71), `FAQPage` (68). All blocks parse as valid JSON.
- Canonical ignores query strings (`?utm=…` → clean canonical). Good.

### 4.2 Problems

| Issue | Pages | Detail |
|---|---|---|
| Canonical points to a redirect | `bharat-couture-week-fashion-week.php` | → `register-bharat-couture.php` → 302 `/`. Set it to its own URL. A link to `register-bharat-couture.php` also exists in site nav — fix that too. |
| Canonical ≠ sitemap URL | `channel-partner.php` | Canonical is `/channel-partner`; sitemap lists `/channel-partner`; page also live at `.php`. OK, but every other form uses `.php` → pick one convention. |
| Title too long (> 65 chars, truncated in SERP) | 49 of 72 (57 over 60) | Worst: `miss-bihar` 107, `mrs-arunachal-pradesh` 105, `miss-universe-beauty-pageant` 99, `mrs-odisha` 94, `miss-karnataka` 93 |
| Meta description > 160 chars | 49 of 72 | Worst: `mrs-andhra-pradesh` 235, `mrs-kerala` / `mrs-telangana` 229, `miss-sikkim` 227 |
| Multiple `<h1>` | 13 pages | Second H1 "Schedule Online Auditions Now" comes from CMS content (e.g. `miss-odisha`, `miss-punjab`, `miss-tamil-nadu`, `miss-tripura`, `miss-sikkim`…); `miss-universe-beauty-pageant` has 3; `bharat-couture` 2 → change the CMS block to `<h2>` |
| Inconsistent brand in titles | many | "Forever Miss Delhi 2026" vs "Miss Odisha 2026" vs "Mrs Forever Kerala 2026" — standardise one pattern, e.g. `Miss {State} 2026 – Register for Auditions | Forever Star India` (≤ 60 chars) |
| Typo | `miss-delhi.php` | "Auditions Open for **for** Fresh Faces" (title/desc/schema) |
| `Event` schema is not an event | 71 | Every state page claims a 365-day event (`2026-01-01` → `2026-12-31`) located at the Jaipur office — even Miss Delhi / Miss Kerala. `Offer` has no `price`, `priceCurrency`, `availability`, `validFrom`. Google's event guidelines exclude registration windows; risk of a structured-data manual action. Use the real audition/finale dates and venues, or drop `Event`. |
| No `hreflang` | all | Fine if English-only; if Hindi pages exist (some sitemap URLs are Devanagari), add `hreflang`. |
| Unused `meta keywords` | 72 | Ignored by Google; harmless, can be removed. |
| Images w/o dimensions | all | `logo.gif` (11.6 KB animated GIF) on every page with no `width`/`height`; convert to SVG/WebP. |

---

## 5. What's missing (things that should exist and don't)

1. **A working build/deploy story** — no `package.json`, no Tailwind build, no `.htaccess`/nginx rules, no README, no CI. The repo contains only page files.
2. **Shared code in git** — `config.php`, headers/footers, save handlers, OTP endpoints, assets (see §2.2).
3. **A form template** instead of 73 copies.
4. **Rate limiting + captcha** (e.g. Cloudflare Turnstile or hCaptcha) on OTP and form submit.
5. **CSRF tokens** on all forms.
6. **A dynamic sitemap generator** + Search Console sitemap resubmission.
7. **Clean-URL routing** with 301s from `.php` (or the reverse), applied consistently to canonical, sitemap and nav links.
8. **Static asset caching headers** and a CDN (Cloudflare free tier would fix caching, Brotli, HTTP/3 and image resizing in one step).
9. **Monitoring:** uptime + 5xx alerting (the crawl found 500s on live sitemap URLs), and Core Web Vitals field data (GA4 is installed — add the `web-vitals` snippet or use Search Console CWV report).
10. **Error/Thank-you pages audit** — `$_SESSION['form_error']` is the only feedback path; confirm `save*.php` redirects to a `noindex` thank-you page (and fires a GA4 conversion event).
11. **Accessibility pass** — labels on all inputs, focus states, colour contrast on the dark/gold theme.
12. **Privacy/consent** — forms collect minors' data (teen categories) and phone numbers; add an explicit consent checkbox linked to `privacy-policy.php`, and a data-retention policy for `otp_verifications`.

---

## 6. Suggested order of work

| Week | Work | Impact |
|---|---|---|
| 1 | Remove inline OTP handler; `random_int`; enable TLS verify; move & rotate webhook ID; add OTP rate limit/attempt lock | Security, cost |
| 1 | Fix `bharat-couture` canonical; add the 60 missing pages to the sitemap; remove broken/redirecting URLs from the sitemap; resubmit in Search Console | Indexing |
| 1 | `Cache-Control` for static assets; stop sessions on anonymous page views | Speed (repeat views) |
| 2 | Replace Tailwind CDN with a built CSS file; merge CSS; defer JS; preconnect fonts; defer calendar/promo | Speed (first view) |
| 2 | Titles ≤ 60, descriptions ≤ 155, one H1, fix Event schema | SEO/CTR |
| 3 | Single form template + config; bring shared PHP into git; CSRF | Maintainability |
| 3 | DB-driven sitemap generator; clean-URL routing with 301s | SEO long-term |
| 4 | Homepage image work (WebP, dimensions, alt), CDN | LCP |
