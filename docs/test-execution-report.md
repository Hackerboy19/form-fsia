# Test Execution Report

Second run: 2026-10-16. Environment: cloud container, PHP 8.3.6, MariaDB (throwaway databases), Node 22, Chromium (Playwright 1.56).

## Test tools in the project

| Tool | What it does |
|---|---|
| `cms/tests/api_test.php` | End-to-end API tests: copy of `cms/backend` on PHP's built-in server, a throwaway database with synthetic profiles, and a stand-in starindia API. 107 checks. |
| `cms/tests/upgrade_check.php` | Signs in as the site's **existing** admin after the zip is extracted over a copy of the old live version, and uses every feature. 29 checks. |
| `cms/tests/ui_smoke.mjs` | Every admin screen at 1280 and 390 px; fails on page errors or sideways overflow. |
| `starindia-admin/test-deeplink.mjs` | The deep link on a local copy of the real starindia admin build, with a stand-in API. |
| `npx vite build`, `php -l`, `node --check` | Build and syntax |
| ESLint / PHPStan / PHPUnit / Vitest | **Not in the project. Not run.** |

## Commands and results (this run)

| # | Command | Result |
|---|---|---|
| 1 | `git status --short` | Clean; HEAD `2f45910` |
| 2 | `TEST_DB_USER=tester TEST_DB_PASS=*** php cms/tests/api_test.php` | **107 checks, 0 failed** (exit 0) |
| 3 | `cd cms/admin && npx vite build` | Built OK |
| 4 | `php -l` on all `cms/backend/{api,lib}/*.php` | No syntax errors |
| 5 | `node --check` on `starindia-admin/admin-enhancements.js` and `test-deeplink.mjs` | OK |
| 6 | `MATRIX_OUT=… php cms/tests/api_test.php` | Matrix regenerated (`role-permission-matrix.md`) |
| 7 | Live GET checks (deploy state, robots, sitemap, 7 pages, starindia Azra page) | See `seo-regression-report.md` and the checklist, section C |

## Earlier runs today, on the same code (kept for the record)

| What | Result |
|---|---|
| Upload simulation: zip over commit 3315ddd + existing admin | Only the listed backend files and `cms-admin-test/` changed. `config.php`, `admin_users.php`, `.htaccess`, `teams.php` unchanged. `upgrade_check.php` **29/29**. |
| Browser on the simulation, signed in as the existing admin | 12 screens × 2 widths, **0 failures**. UI save kept after reload. Verification filter: Asha = Verified Member, Bina = Pending / Guest. |
| `ui_smoke.mjs` on the demo build | **26/26** |
| `test-deeplink.mjs` on the real starindia admin build | 5/5 cases: right profile among two matches (SEO section); slug → name fallback (Hero section); unknown id message; signed out → sign in → opens; **0 write requests** |
| CMS demo link → starindia admin | Same profile id, SEO section open |

## Failures found during this work, and what happened

| Found by | Problem | Outcome |
|---|---|---|
| Owner, on live `/cms-admin/` | starindia profiles judged from empty www columns (BUG-21) | Fixed: `lib/starindia.php`; 12 checks |
| Browser test | Nested confirm dialog closed the editor (BUG-23) | Fixed in `Modal.jsx` |
| api_test | Unchecked profiles counted as "nothing missing" | Fixed in `sitecheck.php`; regression check |
| api_test | Test mistakes (null vs 0, expected counts) | Tests corrected; no product change |

## Not run, with the reason

| Test | Reason |
|---|---|
| The CMS reading the real `admin.fsia.in` API | Runs on the server after upload. The test environment may not pull real profiles' personal data. |
| Deep link on the real starindia admin | Not uploaded yet; needs a starindia admin login. |
| Public OTP / registration forms | Would send real WhatsApp messages and create real registrations |
| Payments, Instagram, events, forms, premium | Not implemented |
| Load tests | Not allowed on production; no staging |

## How to run

```bash
TEST_DB_HOST=127.0.0.1 TEST_DB_USER=tester TEST_DB_PASS=... php cms/tests/api_test.php
BASE='http://localhost:5173/' PW=$(npm root -g)/playwright node cms/tests/ui_smoke.mjs
php cms/tests/upgrade_check.php          # only on a copy of the site, see its header
BASE=http://localhost:8785/admin/ WAIT=14000 PW=$(npm root -g)/playwright node starindia-admin/test-deeplink.mjs
```
