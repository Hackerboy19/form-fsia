# FSIA – Architecture Audit (Phase 1)

Date: 2026-10-15 · Scope: repository `Hackerboy19/form-fsia` + read-only checks of the live site www.fsia.in.
Each statement is marked **[verified]** (seen in code or on the live server) or **[unknown]** (not in the repo, must be confirmed before building on it).

## 1. Hosting and runtime

| Item | Finding |
|---|---|
| Host | Plesk, nginx proxy → Apache, PHP 8.3 **[verified]** |
| PHP mode | mysqli throws exceptions by default (8.1+); any failing query becomes a 500 unless `mysqli_report(MYSQLI_REPORT_OFF)` or try/catch **[verified]** |
| Routing | Root `.htaccess` ≈ 12,940 lines / 11k+ RewriteRules, one rule per profile slug. Parsed on every request; a main cause of slow responses and intermittent 502s **[verified]** |
| Deploys | Manual zip upload through Plesk File Manager. No CI/CD, no staging site **[verified]** |
| Direct server edits | Files are edited by hand on the server (feach-data*, category pages, admin bundle). The repo is **not** the source of truth for the live site **[verified]** |
| Static assets | Served through Apache; nginx static serving not enabled **[verified]** |

## 2. Code that exists in this repository

| Area | Path | Notes |
|---|---|---|
| Public pages | `index.php`, `contact.php`, `our-teams.php`, `socialmediaprofile.php`, `fashion-designer.php`, `makeup-artist.php`, `channel-partner.php`, `bharat-couture-week-fashion-week.php`, `miss-universe-registration.php` | Procedural PHP, inline HTML/CSS, `$connect` (mysqli) from the site `config.php` |
| State registration pages | ~70 `miss-*.php` / `mrs-*.php` | Copy-pasted forms; each has its own OTP + insert code |
| Shared includes | `header1806.php` (+ `footer1806.php`, `config.php`, `scripts.php` … referenced but **not in repo**) | |
| CMS admin (SPA) | `cms/admin` | React 18 + Vite + Tailwind + TipTap. Pages: Dashboard, Homepage, Page editor (About/Contact/…), Team, News, SEO, Site Check, Profile Changes, Server Cleanup |
| CMS API | `cms/backend/api/*.php` | `auth, sections, seo, teams, news, upload, sitecheck, changelog, cleanup` |
| CMS libs | `cms/backend/lib/` | `bootstrap.php` (respond/ApiError/Validator/auth), `site_db.php` (bridge to site DB), `sanitize_html.php`, `users_file.php` |
| CMS DB | `cms/database/schema.sql` + `migrations/` | Tables `pages_seo`, `page_sections` (JSON), `team_members`, `news_coverage` |
| Site integration | `cms/integration/fsia_cms_client.php` | Read-only helper the public pages use to pull CMS content |

## 3. Code that runs live but is NOT in the repo **[unknown]**

- Profile detail pages: `verified_user_details_*.php`, `feach-data-detail-page*.php` (copies received from the user and patched, but the live copy keeps changing).
- Registration endpoints: `registration.php`, `savequickapply.php`, `quickapply`, `registration-success.php`.
- **Payment flow**: `registration.payment_status / trans_id / pdate` columns exist and the Miss Universe page shows a ₹2,999 audition fee, but no gateway code is in the repo. Gateway, callback URL and who marks payments as paid are unknown.
- Old admin panel `/fsiaadmm21/` and the `starindia.fsia.in` subdomain (both write to `registration`).
- `config.php` (DB credentials), `footer1806.php`, `scripts.php`, `state-city.php`, `faq-section.php`.
- The full database schema. Only columns referenced in code are known.

**Without these, Phase 2+ work on profiles, payments and events cannot be designed safely.** See *Missing access* in `security-and-integration-requirements.md`.

## 4. Data model (as far as known)

### Site DB (≈100k profiles)
- `registration`: one row per profile/registration. Columns seen in code: `id, first_name, last_name, email, mobile, state, cityfsia, instagram, facebook, twitter, youtube, regtype, age, dob, qualification, skills, message, image, url, status, setdefault, starindia_action, awardee, nationalwinner, statewinner, citywinner, ptitle, ptitle1, ptitle2, year, date_of_reg, payment_status, trans_id, pdate`.
- `regtype` is the category (6 Miss India, 7 Mrs India, 13 Miss Teen, 30 Miss Universe, …; mapping in `sitecheck.php` `SC_CATEGORIES`). There is no categories table we have seen that is authoritative; `reg_category*` tables exist but their link to `regtype` is unconfirmed.
- Profile content tables: `user_reviews`, `tbl_achievements`, `reg_user_data`, `reg_user_print_news`, `reg_user_blog`, `user_image_caption`, `city`, `more_pages`.
- Form tables: `otp_verifications`, `savemissindia`, `savemrsindia`.
- Added by us (opt-in, installed from admin): `fsia_profile_changes` + 3 triggers, `site_check_baseline`.

### CMS DB
`pages_seo`, `page_sections`, `team_members`, `news_coverage` (see `cms/database/schema.sql`).

## 5. Authentication and permissions

- **CMS admin**: username + bcrypt hash in `cms/backend/admin_users.php`; HMAC-signed 12h session token (`X-Admin-Token`); login throttle 8/15 min per IP. **Every admin has full rights — there are no roles.**
- **Old admin `/fsiaadmm21/`**: separate, unknown auth.
- **Public users / profile owners**: no login. Identity = mobile OTP at registration.
- OTP is sent through a WhatsApp webhook (`auto.clickchat.io/webhook/<id>`), with the webhook id hard-coded in ~70 state pages.

## 6. Existing features relevant to the new spec

| Spec module | What exists today |
|---|---|
| 1 Profile intelligence / filters | Site Check → Profiles: category/year/month/status/search, bio/banner/image/SEO problem filters, CSV export, last-changed (**[verified]**, admin-only) |
| 2 Quality score | None. Site Check problem flags are the raw inputs a score can use |
| 3 Premium plans | None. Flags like `awardee/nationalwinner/statewinner` exist, but no plan/entitlement data |
| 4 Templates & migration | Profiles render through 1–2 PHP templates (`verified_user_details_*`). No template choice per profile |
| 5 Instagram | Only a free-text `instagram` column + links to `instagram.com/fsia_forever`. No API integration |
| 6 Events / promotions | Event content is hard-coded in each page; homepage sections partly CMS-driven |
| 7 Form builder | None. ~75 hard-coded forms with duplicated logic |
| 8 Registrations & payments | Registration insert with `payment_status='pending'`; gateway unknown |
| 9 RBAC & audit | Single admin role; profile change log via DB triggers (opt-in) |
| 10 Analytics & tests | No analytics in CMS; no automated test suite; `npm run build` is the only check |

## 7. Main risks found

1. **Live ≠ repo.** Direct server edits get overwritten by our zips, and our fixes get overwritten by theirs. Must be stopped before larger work.
2. **Duplicated form code** (~75 files): every change to registration has to be made 75 times. A form builder will have to replace these gradually, not all at once.
3. **SQL built by string concatenation** in public pages (escaped, but not parameterised). `registration-success.php?token=md5(id)` is guessable, which matters once payments link to it.
4. **`mysqli_error()` shown to visitors** on insert failure (`miss-universe-registration.php` line ~155): leaks schema details.
5. **.htaccess size**: any new profile URL scheme must not add more rules.
6. **Public leftovers**: logs, zips and `.bak` files reachable over HTTP (reported earlier, still pending).
7. **No staging and no tests**: every change goes straight to production.
