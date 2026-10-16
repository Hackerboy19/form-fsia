# FSIA Feature Audit Report

Second run: 2026-10-16. Branch `claude/gallant-mayer-hgabky`.

Related documents:
- Item-by-item detail: `feature-audit-checklist.md`
- Defects: `bug-and-risk-register.md`
- Commands: `test-execution-report.md`

## Executive summary

**Overall status: Audit Complete** for everything in the repository and on the public sites. Code that lives only on the servers is marked BLOCKED:
- live profile templates
- the starindia admin's source
- the old admin
- payments

**Feature status (36 features):**

| Status | Count |
|---|---|
| PASS | 15 |
| PARTIAL | 11 |
| FAIL | 1 |
| NOT IMPLEMENTED | 8 |
| BLOCKED | 1 (+4 blocked sub-tests) |

**Works, and was verified by tests:**
- The whole CMS admin:
  - sign-in
  - single-admin access
  - content editors
  - uploads
  - Site Check
  - Profile Changes
  - Server Cleanup
  - Activity Log
- Profiles & Score:
  - search and filters
  - Verified Member / Pending / Guest
  - quality score
- Checks for starindia profiles that use the starindia admin's own data.
- "Fix in starindia admin" links that open the profile at the right section.

**Not yet live:** the two newest files (the CMS zip and the starindia `admin-enhancements.js`) are ready and tested, but not uploaded. Live `/cms-admin/` still has the version that judged starindia profiles from the wrong data (BUG-21).

**Not built** (most of the Next-Generation spec): premium plans, Instagram, events and promotions, form builder, payments, optimization workflow, analytics. Each one needs a business decision or credentials first.

**Biggest live problems:**
- Public OTP code (repo copy) has no send or guess limit (P1).
- Broken starindia hero images (BUG-19).
- Duplicate www / starindia profiles for the same person (BUG-20).

## Features implemented and verified (PASS)

- A1 sign-in and single admin
- A4 homepage data
- A5 team
- A7 upload
- A8 Site Check
- A11 starindia-data checks (local)
- A12 deep link (local)
- A13 Profile Changes
- A14 Server Cleanup
- A16 roles (built set)
- A17 Activity Log
- C1–C4 redirects, 404 page and robots

## Partially implemented

| Feature | Missing |
|---|---|
| A2 Global SEO, A3 Our Team CTA, A6 News, A15 | Saved in the CMS but not read by the live pages |
| A9 Filters | Image-count, premium, Instagram and event filters; saved filters; bulk actions; more than 5,000 rows |
| A10 Score | Configurable weights; workflow |
| B7 Public registration | P1 security fixes; live test |
| C5–C8 SEO | Sitemap, about canonical, H1 / titles, time-outs |

## Missing features (NOT IMPLEMENTED)

- B1 optimization workflow
- B2 premium plans
- B3 Instagram
- B5 events and promotions
- B6 form builder
- B8 payments
- B9 analytics
- B10 profile index

## Failed tests

- **C9 (live):** starindia profile images whose `src` is a list of files (BUG-19), and the same person indexed on two hosts (BUG-20).
- **Found and fixed during this work:**
  - BUG-21: wrong data source for starindia profiles
  - BUG-23: nested dialog closed its parent
  - unchecked profiles counted as complete

## Blocked tests and their dependencies

| Blocked | Needs |
|---|---|
| CMS reading the real starindia API | Upload of the zip (runs on the server) |
| Deep link on the real starindia admin | Upload of `admin-enhancements.js`; a starindia admin login |
| Profile templates (B4) | Live www code and starindia source |
| Instagram | Meta app, verification, App Review |
| Public forms live | Test number and staging |
| Payments | Gateway, sandbox keys, live code |

## Critical security issues

- **P1 (repo copies of public pages):** BUG-02, BUG-03, BUG-04 (OTP limits, hard-coded webhook with TLS off).
- **New P2:** RISK-07. The starindia profile API answers without a login, including unpublished profiles. Hidden phone and email are not exposed in the sample.

## Data-integrity concerns

- The live server is edited by hand (BUG-11).
- Duplicate profile records for one person (#86294 and #107550), with both pages indexed (BUG-20).
- The sitemap lists deleted profiles (BUG-14).
- No staging site.
- The CMS no longer writes to profiles at all, so there is no risk of two admins overwriting each other.

## Performance concerns

- First-request time-outs (BUG-17).
- Filters capped at 5,000 rows, and starindia data at 400 profiles per load. That data is cached for 5 minutes.

## SEO regressions

None caused by this work. Open: BUG-13 to BUG-17, BUG-19, BUG-20. BUG-01 is fixed live.

## Files changed since the first audit

| File | Change |
|---|---|
| `cms/backend/lib/rbac.php`, `api/users.php`, `config.sample.php` | Single admin by default (`roles_enabled`) |
| `cms/backend/lib/profile_report.php` (new), `api/sitecheck.php` | Shared profile checks; verification filter and counts; starindia source; "not checked" handling |
| `cms/backend/lib/starindia.php` (new) | Read-only starindia API reader with a privacy-safe cache |
| `cms/admin/src/pages/ProfileData.jsx`, `Profiles.jsx`, `components/Layout.jsx`, `App.jsx`, `lib/auth.jsx`, `components/Modal.jsx` | Profiles screen, Verification filter, Fix links, hidden Users & Roles, nested dialog fix |
| `cms/backend/api/profiles.php`, `lib/image_store.php`, `pages/ProfileFix.jsx` | Added, then **removed** (fixes belong in the starindia admin) |
| `starindia-admin/admin-enhancements.js` (+ live copy, test, README) | Deep link into the starindia admin |
| `cms/tests/api_test.php`, `upgrade_check.php`, `ui_smoke.mjs` | 107 + 29 checks, screen smoke test |

## Database migrations created or modified

None in this audit.
- Tables the CMS creates by itself: `cms_audit_log` on the first logged change; `cms_user_roles` only if roles are enabled and turned on.
- No change to `registration`.

## Test commands and results

| Command | Result |
|---|---|
| `php cms/tests/api_test.php` | 107/107 |
| `php cms/tests/upgrade_check.php` (simulation) | 29/29 |
| `node cms/tests/ui_smoke.mjs` | 26/26 |
| `starindia-admin/test-deeplink.mjs` | 5/5 cases, 0 writes |
| `npx vite build`, `php -l`, `node --check` | OK |

Details: `test-execution-report.md`.

## Remaining work and priorities

1. **Now (owner):**
   - Upload the zip and the starindia `admin-enhancements.js`, then report back for the live check.
   - Fix Azra's hero image and logo in the starindia admin (BUG-19).
2. **Decide (owner):** which URL is primary for duplicated people (BUG-20). Ask the starindia developer to protect the profile API (RISK-07).
3. **Next (needs live files):**
   - Shared, rate-limited OTP include (BUG-02/03/04).
   - Connect Global SEO, Our Team CTA and News to the live pages (A15).
   - Canonical for `/about`.
   - Sitemap regeneration.
4. **Then:** the profile index, and the spec modules in plan order, each after its business decision or credentials.

## Deployment readiness

- **CMS zip + starindia `admin-enhancements.js`: Ready with Conditions.**
  - All tests pass, including the upload simulation with the existing login.
  - Conditions: back up `bootstrap.php` and the current `admin-enhancements.js` first, then run the live checks in the deployment checklist.
- **The Next-Generation platform as specified: Not Ready.**
  - Most modules are not built.
  - P1 OTP issues are open.
  - Payments and Instagram cannot be verified.
