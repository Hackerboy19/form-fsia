# Feature Audit Checklist

Audit date: 2026-10-15. Scope: repository `Hackerboy19/form-fsia` (branch `claude/gallant-mayer-hgabky`) plus read-only checks of www.fsia.in.

**Where things were tested**
- **Local**: the API runs on PHP 8.3 against a throwaway MariaDB with synthetic profiles (`cms/tests/api_test.php`). The admin UI runs in Chromium against the demo backend (`cms/tests/ui_smoke.mjs`).
- **Live (read-only)**: GET requests to www.fsia.in only. Nothing was written to the production database or files.
- The new CMS files **have not been uploaded to production yet**. Every PASS below means "verified locally". Each one needs a short live re-check after upload (see `deployment-and-rollback-checklist.md`).

**Statuses**
- PASS: verified by an executed test.
- PARTIAL: some of the requirement works.
- FAIL: broken.
- NOT IMPLEMENTED: no code exists.
- BLOCKED: cannot be verified without access or credentials.
- N/A: does not apply.

Code inspection alone never gave a PASS.

## A. Existing CMS features

| # | Feature | Frontend | Backend | DB tables | Permission | Tests (actual result) | Status | Fix / next step |
|---|---|---|---|---|---|---|---|---|
| A1 | Admin sign-in, sessions, sign-in rate limit | `components/Login.jsx`, `lib/auth.jsx` | `api/auth.php`, `lib/bootstrap.php` (`issue_session`, `session_user`, `login_throttle`) | file `admin_users.php` | — | Sign-in ok and wrong password refused. A tampered token and a token carrying another user's name are both refused (401). Changing a password signs old sessions out. The 9th failed try in a row is blocked with 429. | PASS | — |
| A2 | Global SEO manager | `pages/SeoManager.jsx` | `api/seo.php` | `pages_seo` | `content.edit` to save | Save as editor (200); viewer refused (403); public read 200. UI loads at 1280 and 390 px. | PASS | — |
| A3 | Page text editor + live preview (About, Our Team) | `pages/PageEditor.jsx`, `components/LivePreview.jsx` | `api/sections.php` | `page_sections` | `content.edit` | Save as editor ok; viewer refused. UI loads without errors. **Phone layout overflowed (1298 px on a 390 px screen); fixed and re-tested.** | PASS (after fix) | BUG-12 fixed |
| A4 | Homepage editor | `pages/HomepageEditor.jsx` | `api/sections.php` | `page_sections` | `content.edit` | Screen loads at both widths. Saving uses the same endpoint as A3 (tested). Homepage-specific field round-trips are not automated. | PARTIAL | Add a save/reload test for homepage sections |
| A5 | Team directory (multi-category) | `pages/TeamDirectory.jsx` | `api/teams.php` | `team_members` | `content.edit` | Create/delete reach the API for editors (validation answers 422/404); viewer refused. Full create→edit→delete round trip not automated. **The live `teams.php` was hand-edited on the server and is not in the repo.** | PARTIAL | Bring the live `teams.php` into the repo; add a CRUD test; run migration `2026-10-15-team-multi-category.sql` |
| A6 | News manager | `pages/NewsManager.jsx` | `api/news.php`, `lib/sanitize_html.php` | `news_coverage` | `content.edit` | Article created (201). `<script>`, `onerror`, `javascript:` links and `<iframe>` removed. A `javascript:` featured image is refused (422). | PASS | — |
| A7 | Image upload | `components/ImageUpload.jsx` | `api/upload.php` | — (files) | `content.edit` | PHP renamed to `.jpg` refused (415). SVG refused (415). A real PNG is stored under a random 32-hex name (201). Viewer refused (403). | PASS | — |
| A8 | Site Check (live page check, baseline) | `pages/SiteCheck.jsx` | `api/sitecheck.php` (`sc_check`, `sc_fetch_page`, `sc_check_images`) | `site_check_baseline` | view `tools.view`, baseline `tools.manage` | Permissions verified. Fetching is limited to fsia.in, and now to http/https only. The live fetch was exercised earlier in this project but **not re-run in this audit**. | PARTIAL | Re-run against live after upload |
| A9 | Profile search and smart filters | `pages/Profiles.jsx`, `pages/ProfileData.jsx` | `api/sitecheck.php?action=profiles` (`sc_profile_rows`, `sc_profile_report`) | `registration` (read only) | `tools.view` | **Passed:** search by name and by ID; category; year and month (counts match direct SQL); 230 rows over 3 pages with none missing or repeated; "all" vs "any" problem logic; SQL-injection and script text in search return 200 with 0 rows. **Bug found:** an invalid year returned 0 rows; now ignored (BUG-09). **Not implemented:** filters by image count, premium, Instagram, event, saved filters, bulk actions. | PARTIAL | Missing filters depend on modules C1–C4 |
| A10 | Profile quality score | `ProfileData.jsx` (`ScoreCell`, band chips) | `sitecheck.php` (`sc_quality_score`, `SC_SCORE_BANDS`) | none (computed) | `tools.view` | Exact scores 100 / 68 / 20 for sample profiles. Parts add to the total. Same input gives the same score. Changing only the bio changes only the bio part (68→76). Band filter, sort and counts verified. | PARTIAL | Weights are in code, not admin-configurable. Optimization workflow is not implemented (B1). |
| A11 | Profile Changes (change log via DB triggers) | `pages/ProfileChanges.jsx` | `api/changelog.php` | `fsia_profile_changes` + 3 triggers | view `tools.view`, on/off `tools.manage` | Turned on (self-test, then triggers). A profile update is recorded before→after. The mobile change is listed without its value. Turning off leaves 0 triggers. | PASS | Not yet turned on in production |
| A12 | Server Cleanup (Trash, restore, purge, preview) | `pages/ServerCleanup.jsx` | `api/cleanup.php` (`cl_check_path`, `cl_preview`, `cl_redact_secrets`) | — (files) | `cleanup.manage` | `../config.php`, `config.php`, `cms/…`, `/etc/passwd` and `x/../config.php` are all refused (422). Move to Trash → restore works. Purging before 7 days is refused. **Preview showed passwords from old config copies; fixed** (BUG-08). | PASS (after fix) | — |
| A13 | Roles and permissions | `pages/UsersRoles.jsx`, `Layout.jsx` (`visibleNav`) | `lib/rbac.php` (`rbac_enforce`), `api/users.php` | `cms_user_roles` | `users.manage` | 16 actions × 5 identities checked server-side (see `role-permission-matrix.md`). Last owner cannot be demoted. Editor cannot promote itself. A user added later is a viewer. | PASS for the 4 built roles; PARTIAL against the 7 proposed roles | Add Event / Registration / Finance roles with those modules |
| A14 | Activity log | `pages/ActivityLog.jsx` | `lib/rbac.php` (`audit_record`), `api/audit.php` | `cms_audit_log` | `audit.view` | Records changes, refusals, sign-ins and failed sign-ins. Passwords and tokens never stored. Search input is not SQL. A change still saves when the log cannot be written. | PASS | — |

## B. Requested modules (FSIA Next-Generation spec)

| # | Feature | Evidence | Status | Dependency |
|---|---|---|---|---|
| B1 | Optimization workflow: assignment, notes, approval, before/after, AI drafts | No code, tables or screens. | NOT IMPLEMENTED | Business rules; approval to use any AI provider with personal data |
| B2 | Premium plans and entitlements | No `plans` / `subscriptions` tables, no entitlement checks (repo-wide search). | NOT IMPLEMENTED | Plan names, prices, features (business decision) |
| B3 | Instagram integration (official Meta API) | Only a free-text `instagram` column and static links. No OAuth, token storage or callback. | NOT IMPLEMENTED; live tests BLOCKED | Meta developer app, business verification, App Review |
| B4 | New profile template and migration | Live profile pages (`verified_user_details_*.php`, `feach-data-detail-page*.php`) are **not in the repo**. | NOT IMPLEMENTED; current templates BLOCKED for audit | Copy of live httpdocs |
| B5 | Central events and website-wide promotions | Event content is hard-coded per page; no `events` table or promotion component. | NOT IMPLEMENTED | Event list and placement rules |
| B6 | Dynamic registration form builder | ~75 hard-coded forms; no form definitions or versioning. | NOT IMPLEMENTED | B5 |
| B7 | Existing public registration (OTP + insert) | Repo copies of state pages and `miss-universe-registration.php`. Defects confirmed **in the repo code**: no OTP send limit, unlimited OTP guesses, TLS checks off, hard-coded webhook, DB error shown to visitors. Live forms not exercised (each run sends a real WhatsApp message and writes a real registration). | PARTIAL (defects in code); live functional test BLOCKED | A test phone number and a staging copy |
| B8 | Payments and registration status | Columns `payment_status/trans_id/pdate` exist; gateway code not in the repo. | NOT IMPLEMENTED in repo; live flow BLOCKED | Gateway name, sandbox keys, live code |
| B9 | Analytics | None. | NOT IMPLEMENTED | — |
| B10 | Profile index for fast filtering of 100k profiles | Planned in `implementation-plan.md` (Phase 2 step 3). Filters read `registration` directly, capped at 5,000 rows. | NOT IMPLEMENTED | Schema dump; cron access |

## C. Website (live, read-only)

| # | Check | Result | Status |
|---|---|---|---|
| C1 | HTTPS / www redirects | `fsia.in` and `http://` → 301 to `https://www.fsia.in/` | PASS |
| C2 | `quickapply.php` → `/quickapply` | 301 | PASS |
| C3 | 404 page | Real 404 with `noindex,follow` | PASS |
| C4 | robots.txt sitemap | **Points to `influencer.fsia.in/sitemap.xml` (6 URLs) instead of `www.fsia.in/sitemap.xml` (4,332 URLs)** | FAIL (BUG-01) |
| C5 | Sitemap health | 20-URL sample: 19 return 200 (after one retry for transient failures), 1 returns 404. 9 duplicate entries; 164 `.php` URLs. | PARTIAL (BUG-14) |
| C6 | Canonicals | Present on most pages, but `/about` and `/about.php` both return 200 with no canonical | PARTIAL (BUG-13) |
| C7 | Titles and H1 | Homepage has no H1. Profile pages have 2 H1s. Several titles are over 70 characters. | PARTIAL (BUG-15/16) |
| C8 | Availability | Several requests timed out and then worked on retry. | PARTIAL (BUG-17) |

## Status counts

| Status | Count | Items |
|---|---|---|
| PASS | 11 | A1, A2, A3, A6, A7, A11, A12, A14, C1, C2, C3 |
| PARTIAL | 11 | A4, A5, A8, A9, A10, A13, B7, C5, C6, C7, C8 |
| FAIL | 1 | C4 |
| NOT IMPLEMENTED | 9 | B1, B2, B3, B4, B5, B6, B8, B9, B10 |
| BLOCKED (sub-tests) | 4 | B3 live Instagram, B4 current live templates, B7 live registration, B8 live payment flow |

32 features in total (11 + 11 + 1 + 9). BLOCKED sub-tests belong to features already counted above. A13 is counted as PARTIAL: PASS for the 4 built roles, but short of the 7 requested.
