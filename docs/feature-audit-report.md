# FSIA Feature Audit Report

Date: 2026-10-15. Branch `claude/gallant-mayer-hgabky`.

Related documents:
- Item-by-item detail: `feature-audit-checklist.md`
- Defects: `bug-and-risk-register.md`
- Commands and results: `test-execution-report.md`

## Executive summary

- **Overall status: Audit Complete** for everything that exists in the repository and on the public site.
  - Live-only code that is not in the repo is marked BLOCKED: the profile templates, the old admin and the payment flow.
  - Live functional tests that would send real messages or charges are also marked BLOCKED.
- **Feature status (32 features):**
  - PASS 11
  - PARTIAL 11
  - FAIL 1
  - NOT IMPLEMENTED 9
  - BLOCKED sub-tests 4 (belonging to features already counted)
- **What works (verified by tests):**
  - The CMS admin: sign-in, SEO, page text, news, uploads, change log, Server Cleanup, roles and activity log.
  - Profile search/filters and the quality score.
  - 85 automated API checks pass, and all 26 admin screens load without errors on desktop and phone.
- **What does not exist yet:** most of the Next-Generation spec.
  - Premium plans
  - Instagram (Meta API)
  - New profile templates and migration
  - Central events and promotions
  - Form builder
  - Payments
  - Optimization workflow
  - Analytics
  - These are planned in `implementation-plan.md`. Each depends on business decisions or credentials listed there.
- **Most important problems:**
  - Live robots.txt points search engines to the wrong sitemap.
  - The public OTP code (in the repo copy) has no rate limit and no guess limit.
  - A messaging webhook ID is hard-coded in 64 files.

## Implemented and verified (PASS, local test environment)

| Feature | Evidence |
|---|---|
| A1 Sign-in / sessions | `api_test.php` "Sign-in", "Sessions", "Sign-in rate limit" |
| A2 Global SEO | matrix + editor/viewer tests |
| A3 Page text editor + preview | API tests; `ui_smoke.mjs` (after BUG-12 fix) |
| A6 News (XSS-safe) | "News: script cannot be stored" |
| A7 Image upload | "Image upload" (4 checks) |
| A11 Profile Changes | "Profile Changes (change log)" (4 checks) |
| A12 Server Cleanup | "Server Cleanup safety" (11 checks; after BUG-08 fix) |
| A14 Activity Log | "Activity log" (9 checks) |
| C1–C3 Redirects, 404 | live GET |

All CMS items still need the short live check after upload.

## Partially implemented

| Feature | What is missing |
|---|---|
| A4 Homepage editor | Automated save/reload test |
| A5 Team directory | The live `teams.php` is not in the repo; no CRUD test |
| A8 Site Check | Live fetch not re-run in this audit |
| A9 Profile filters | Image-count, premium, Instagram and event filters; saved filters; bulk actions; datasets over 5,000 rows |
| A10 Quality score | Configurable weights; separate content-quality indicator; the workflow around it (B1) |
| A13 Roles | Event, Registration and Finance roles (their modules do not exist) |
| B7 Public registration | Security defects BUG-02/03/04/05; live test blocked |
| C5–C8 SEO | Sitemap 404s and duplicates, duplicate About URL, H1 and title issues, time-outs |

## Missing features (NOT IMPLEMENTED)

- B1 Optimization workflow
- B2 Premium plans
- B3 Instagram
- B4 Templates and migration
- B5 Events and promotions
- B6 Form builder
- B8 Payments (repo)
- B9 Analytics
- B10 Profile index

## Failed tests

**Live:**
- C4 robots.txt sitemap (BUG-01). Open: needs a one-line change on the server.

**During this audit (all now fixed and re-tested):**
- BUG-08: secrets shown in the Server Cleanup preview.
- BUG-09: an invalid year emptied the results.
- BUG-12: phone layout overflow in the page editors.

## Blocked tests and their dependencies

| Blocked | Needs |
|---|---|
| Instagram live tests | Meta developer app, business verification, App Review |
| Current live profile templates | Copy of live `httpdocs` (code only) |
| Live public registration | Test phone number + staging copy |
| Live payment flow | Gateway name, sandbox keys, live payment code |

## Critical security issues

- No P0 issue was found in the code that was tested.
- P1, in the public page code in the repo:
  - BUG-02: OTP send not rate-limited.
  - BUG-03: OTP guesses not limited.
  - BUG-04: hard-coded webhook ID with TLS checks off.
- Details: `security-audit-report.md`.

## Data-integrity concerns

- The live server is edited by hand, so the repo is not the source of truth (BUG-11). Uploads can overwrite those edits.
- The sitemap lists deleted or 404 profiles (BUG-14).
- No staging site exists (RISK-05).
- The change log is opt-in and not yet on in production, so earlier profile edits are not recorded.

## Performance concerns

- The 12,940-line `.htaccess` is parsed on every request, and time-outs were seen (BUG-17).
- Profile filters scan `registration` directly, capped at 5,000 rows (RISK-04).
- The fix for both is the plan's profile index (Phase 2) and front-controller routing (Phase 5).

## SEO regressions

None were caused by the CMS work, which adds no public URLs. Existing issues: BUG-01, BUG-13 to BUG-17 (`seo-regression-report.md`).

## Files changed during bug fixing

| File | Change |
|---|---|
| `cms/backend/api/cleanup.php` | `cl_redact_secrets()`; preview text passes through it (BUG-08) |
| `cms/backend/api/sitecheck.php` | Ignore invalid year/month (BUG-09); curl limited to http/https (BUG-10) |
| `cms/admin/src/pages/PageEditor.jsx` | `min-w-0` on both grid columns (BUG-12) |
| `cms/tests/api_test.php` | +36 checks: filters, matrix, change log, XSS, upload, sessions, cleanup, rate limit, regressions for BUG-08/09 |
| `cms/tests/ui_smoke.mjs` | New: every admin screen at 1280/390 px |

## Database migrations created or modified

None in this audit. The existing migrations are unchanged:
- `2026-10-13-team-bio-quote-phone.sql`
- `2026-10-15-team-multi-category.sql`
- `2026-10-15-roles-and-activity-log.sql` (reference only)

## Test commands and results

- `php cms/tests/api_test.php`: baseline 49/49; final **85/85**.
- `node cms/tests/ui_smoke.mjs`: final **26/26**.
- `npx vite build`: OK.
- `php -l`: OK.
- Bundle secret scan: 0 matches.
- Full log: `test-execution-report.md`.

## Remaining work and priorities

1. **Now (owner, minutes each):**
   - Fix the robots.txt Sitemap line.
   - Upload the zip using the deployment checklist.
   - Remove public logs/zips/.bak files.
   - Rotate the Google secret.
2. **Next (needs the live state pages):** one shared, rate-limited OTP include replacing the code in 64 pages (BUG-02/03/04/05/07).
3. **Then:** the profile index (performance), a canonical for `/about`, and sitemap regeneration.
4. **Spec modules**, in the order of `implementation-plan.md`: premium → templates → events/forms → payments → Instagram. Each needs its business decision or credentials first.

## Deployment readiness

- **CMS admin tools zip: Ready with Conditions.**
  - Tests pass and the zip makes no data changes.
  - Conditions: back up `bootstrap.php` first, run the live checks in the deployment checklist, and keep `/cms-admin/` until `/cms-admin-test/` is confirmed.
- **The Next-Generation platform as specified: Not Ready.**
  - Most modules are not implemented.
  - P1 security issues in the public OTP code are open.
  - Payment and Instagram flows cannot be verified.
