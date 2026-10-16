# Feature Audit Checklist

Audit date: 2026-10-16 (second run; supersedes the 2026-10-15 run).
Scope:
- Repository `Hackerboy19/form-fsia`, branch `claude/gallant-mayer-hgabky` (HEAD `2f45910` + this audit).
- Read-only requests to www.fsia.in and starindia.fsia.in.

**Where things were tested**
- **Local:** PHP 8.3 API against a throwaway MariaDB with synthetic profiles (`cms/tests/api_test.php`, 107 checks). A stand-in starindia API serves synthetic profiles.
- **Upload simulation:** the zip extracted over a copy of the old live version (commit 3315ddd), with an existing admin login (`cms/tests/upgrade_check.php`, 29 checks, plus a browser run of every screen).
- **Browser:** every admin screen at 1280 / 390 px (`cms/tests/ui_smoke.mjs`). The starindia deep link ran on a local copy of the real starindia admin build with a stand-in API (`starindia-admin/test-deeplink.mjs`).
- **Live:** GET only. Nothing was written to production.

**What is live right now (checked 2026-10-16)**
- `/cms-admin/` is the single-admin build uploaded earlier (`index-ZG4XlubJ.js`): Profiles & Score v1, Activity Log, Site Check, Profile Changes, Server Cleanup.
- **Not yet uploaded:**
  - The latest zip: Verification filter, starindia-data checks, "Fix in starindia admin" links. `/cms-admin-test/` returns 404.
  - The starindia `admin-enhancements.js` with the deep link (live file has no `fsia-open`).
- **Done live:** robots.txt Sitemap fix.

**Statuses**
- PASS: verified by an executed test.
- PARTIAL: some of the requirement works.
- FAIL: broken.
- NOT IMPLEMENTED: no code.
- BLOCKED: needs access or credentials we don't have.
- N/A: does not apply.

Code reading alone never gave a PASS.

## A. CMS admin (this repository)

| # | Feature | Frontend | Backend / data | Permission | Tests run → result | Status |
|---|---|---|---|---|---|---|
| A1 | Sign-in, sessions, rate limit, single admin | `Login.jsx`, `lib/auth.jsx` | `api/auth.php`, `lib/bootstrap.php`, `lib/rbac.php` (`rbac_roles_enabled`) | — | The existing login works after the upload (sim). A tampered token or a token with another user's name is refused. A changed password ends sessions. The 9th failed try → 429. With roles off, one admin has full access and roles cannot be turned on from the UI (409). | PASS |
| A2 | Global SEO | `SeoManager.jsx` | `api/seo.php`, `pages_seo` | content.edit | Save/refuse matrix; save in the sim. **But the 5 SEO rows are not used by the live pages** (titles differ, see A15). | PARTIAL |
| A3 | Page text + live preview (About, Our Team) | `PageEditor.jsx`, `LivePreview.jsx` | `api/sections.php`, `page_sections` | content.edit | Saved through the UI in the sim and kept after reload. Phone layout OK (BUG-12 fixed). **Our Team CTA is not read by the live page** (A15). | PARTIAL |
| A4 | Homepage editor | `HomepageEditor.jsx` | `api/sections.php` | content.edit | Screens load. All 10 saved homepage sections match the live homepage text. Homepage-specific save round trip not automated. | PASS (data) / save via A3 path |
| A5 | Team directory | `TeamDirectory.jsx` | `api/teams.php` (live copy hand-edited, not in zip) | content.edit | Sim: list (27), add, edit, delete OK. Live: 24/24 active names on Our Team page. | PASS (sim) |
| A6 | News | `NewsManager.jsx` | `api/news.php`, `sanitize_html.php` | content.edit | Add/edit/delete; script, event handlers, `javascript:` and iframe removed. **Live: 0 articles in the CMS; the News pages are not CMS-driven** (A15). | PARTIAL |
| A7 | Image upload | `ImageUpload.jsx` | `api/upload.php` | content.edit | PHP-as-JPEG and SVG refused; real image stored under a random name; viewer refused. | PASS |
| A8 | Site Check (live page check, baseline) | `SiteCheck.jsx` | `api/sitecheck.php` | tools.view / tools.manage | Sim: live check of www.fsia.in/quickapply → 200; baseline saved; fetches limited to fsia.in over http(s). | PASS |
| A9 | Profile search and filters | `Profiles.jsx`, `ProfileData.jsx` | `sitecheck.php?action=profiles`, `lib/profile_report.php` | tools.view | **Passed:** name, ID, category, year/month (= SQL counts); 230 rows / 3 pages, none missing or repeated; problem any/all; invalid values ignored; SQL and script text in search are harmless. **Verification:** Verified Member / Pending-Guest totals = database; badge; date. **Missing:** filters by image count, premium, Instagram, event; saved filters; bulk actions. Max 5,000 rows per query. | PARTIAL |
| A10 | Quality score | `ProfileData.jsx` (`ScoreCell`) | `sc_quality_score` | tools.view | Exact scores (100/76/68/63/20/95), parts add up, same input → same score, one field changes one part. starindia profiles use that admin's limits (60/160). An unreadable profile gets no score, is shown as "Not checked" and is left out of the average and of "nothing missing". **Missing:** configurable weights; workflow (B1). | PARTIAL |
| A11 | Issues match the admin that edits the profile | `ProfileData.jsx` | `lib/starindia.php` (GET `admin.fsia.in/api/profiles/<id>`) | tools.view | Stand-in API: SEO from starindia data, not the empty DB columns (the Azra case); hero/logo holding several images reported; GET only; cache holds no contact details; second load uses the cache. **Live API not exercised from the test environment** (personal data; runs on the server after upload). | PASS (local) / live BLOCKED until upload |
| A12 | "Fix in starindia admin" deep link | `ProfileData.jsx` (`starindiaUrl`, `FixLink`, per-issue Fix ↗) | `starindia-admin/admin-enhancements.js` (`fsia-open` block) | starindia admin login | Real starindia admin build + stand-in API: right profile when two match; slug → name fallback; unknown id message; signed out → sign in → opens; CMS demo link → same profile, SEO section; 0 write requests. | PASS (local) / not uploaded |
| A13 | Profile Changes (DB triggers) | `ProfileChanges.jsx` | `api/changelog.php`, `fsia_profile_changes` | tools.view / tools.manage | On (self-test, triggers) → edit recorded before→after, mobile value hidden → off leaves 0 triggers. Not yet turned on in production. | PASS |
| A14 | Server Cleanup | `ServerCleanup.jsx` | `api/cleanup.php` | cleanup.manage | 5 traversal paths refused; protected files refused; Trash → restore; no purge before 7 days; secrets masked in preview (BUG-08). Sim: junk found, config.php/.htaccess never listed. | PASS |
| A15 | CMS content actually reaches the site | — | live pages `our-teams.php`, `index.php`, `about.php`, `news-coverage*` (not in repo) | — | Live comparison: Team, About, Homepage sections match. **Global SEO titles (5/5), Our Team CTA and News are not read by the live pages.** | PARTIAL |
| A16 | Roles and permissions | `UsersRoles.jsx` (hidden while roles off) | `lib/rbac.php`, `api/users.php` | users.manage | 16 actions × 5 identities via real requests (matrix doc); escalation refused; last owner kept. Currently off by choice (single admin). | PASS (4 roles) / PARTIAL vs 7 requested |
| A17 | Activity log | `ActivityLog.jsx` | `rbac.php` (`audit_record`), `api/audit.php` | audit.view | Changes, refusals, sign-ins, failed sign-ins; no passwords/tokens; a save still works when logging fails. Sim: UI save logged under the existing admin. | PASS |

## B. Requested modules (Next-Generation spec)

| # | Feature | Evidence | Status | Needs |
|---|---|---|---|---|
| B1 | Optimization workflow: assignment, notes, approval, before/after, AI drafts | None in the CMS. The starindia admin has "Save as draft" for profile edits (seen in its UI, code not in repo). | NOT IMPLEMENTED (CMS) | Business rules; approval for any AI provider |
| B2 | Premium plans and entitlements | No plans/subscriptions/entitlements anywhere in the repo. The starindia admin has a "Featured" toggle only. | NOT IMPLEMENTED | Plan names, prices, features |
| B3 | Instagram (official Meta API) | Only text fields (`instagram` column; starindia `contact.instagram`). No OAuth, tokens or callback. | NOT IMPLEMENTED; live tests BLOCKED | Meta app, business verification, App Review |
| B4 | Profile templates and migration | www templates (`verified_user_details_*`) and starindia's React profile pages exist **outside the repo**. Starindia pages render from that admin's JSON; www pages render from `registration`. | BLOCKED (code not in repo) | Live code / starindia source |
| B5 | Central events + website-wide promotions | Event content hard-coded per page; no events table or promotion component. | NOT IMPLEMENTED | Event list and placement rules |
| B6 | Dynamic form builder | ~75 hard-coded forms; no definitions or versions. | NOT IMPLEMENTED | B5 |
| B7 | Existing public registration (OTP + insert) | Repo copies of `miss-*/mrs-*` and `miss-universe-registration.php`. No OTP send limit, unlimited OTP guesses, TLS checks off, hard-coded webhook ID, DB error shown to visitors (all confirmed in repo code). Live forms not exercised (each run sends a real WhatsApp and writes a real registration). | PARTIAL; live BLOCKED | Live copies + a test number |
| B8 | Payments | `payment_status/trans_id/pdate` columns exist; no gateway code in the repo. | NOT IMPLEMENTED (repo); live flow BLOCKED | Gateway, sandbox keys, live code |
| B9 | Analytics | None in the CMS. The starindia admin dashboard shows inquiry counts (`admin-enhancements.js`), but there is no traffic or conversion analytics. | NOT IMPLEMENTED | — |
| B10 | Profile index for 100k profiles | Planned (implementation plan Phase 2). | NOT IMPLEMENTED | Schema dump, cron |

## C. Website (live, read-only, 2026-10-16)

| # | Check | Result | Status |
|---|---|---|---|
| C1 | https / www redirects | 301 to `https://www.fsia.in/` | PASS |
| C2 | `quickapply.php` → `/quickapply` | 301 | PASS |
| C3 | 404 page | real 404, `noindex,follow` | PASS |
| C4 | robots.txt sitemap | **Now `https://www.fsia.in/sitemap.xml`** (was influencer subdomain) | PASS (fixed live) |
| C5 | Sitemap health | 4,332 URLs, 9 duplicates, contains a 404 profile URL | PARTIAL (BUG-14) |
| C6 | Canonicals | `/about` and `/about.php` both 200, no canonical | PARTIAL (BUG-13) |
| C7 | Titles / H1 | Homepage 0 H1; profile pages 2 H1; titles 77–93 chars | PARTIAL (BUG-15/16) |
| C8 | Availability | time-outs on first try, fine on retry | PARTIAL (BUG-17) |
| C9 | starindia profile pages | `starindia.fsia.in/azra`: 3 image tags whose src is a comma list of 12 files (broken). The same person also has `www…/azra-chennai-title-holder-lawyer-2025`; both self-canonical and indexable. | FAIL (BUG-19, BUG-20) |

## Status counts (36 features)

| Status | Count | Items |
|---|---|---|
| PASS | 15 | A1, A4, A5, A7, A8, A11, A12, A13, A14, A17, C1, C2, C3, C4, A16 (built roles) |
| PARTIAL | 11 | A2, A3, A6, A9, A10, A15, B7, C5, C6, C7, C8 |
| FAIL | 1 | C9 |
| NOT IMPLEMENTED | 8 | B1, B2, B3, B5, B6, B8, B9, B10 |
| BLOCKED | 1 | B4 (plus BLOCKED sub-tests: A11 live API, B3 live, B7 live, B8 live) |

A11 and A12 are PASS on the local and simulated runs, but are not live until the two files are uploaded.
