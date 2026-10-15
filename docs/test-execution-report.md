# Test Execution Report

Date: 2026-10-15. Environment: cloud container, PHP 8.3.6, MariaDB (local, throwaway databases), Node 22, Chromium (Playwright 1.56).

## Test tools in the project

| Tool | Status |
|---|---|
| `cms/tests/api_test.php` | End-to-end API tests. Plain PHP, no Composer. Each run starts PHP's built-in server on a copy of `cms/backend` and creates, then drops, a throwaway database with synthetic profiles. |
| `cms/tests/ui_smoke.mjs` (new) | Opens every admin screen at 1280 px and 390 px and fails on page errors or sideways overflow. |
| `npx vite build` | The admin build. |
| `php -l` | Syntax check of every PHP file. |
| ESLint, PHPStan, PHPUnit, Vitest | **Not installed in the project.** Not run. |

## Commands and results

| # | Command | Result |
|---|---|---|
| 1 | `git status --short` (before any change) | Clean; HEAD `e5ad059` |
| 2 | `TEST_DB_USER=tester TEST_DB_PASS=*** php cms/tests/api_test.php` (baseline, before the audit's new tests) | **49 checks, 0 failed** |
| 3 | `cd cms/admin && npx vite build` (baseline) | Built OK |
| 4 | `for f in cms/backend/{api,lib}/*.php; do php -l $f; done` | No syntax errors |
| 5 | `api_test.php` with the audit's new tests (filters, matrix, change log, XSS, upload, sessions, cleanup, rate limit), first run | **84 checks, 19 failed.** 18 came from mistakes in the new tests, which were corrected: 14 because the admin's session ended when the test rewrote the users file, 3 because the log checks read only the newest 50 rows, and 1 hand-computed month count was wrong. 1 was a real product defect: BUG-09 (invalid year). |
| 6 | Same, after fixing the tests | 84 checks, 3 failed: BUG-09; **BUG-08** (secrets shown in the preview, now visible because the admin tests ran); and a test-setup mistake (purge on an emptied batch) |
| 7 | Same, after the BUG-09 fix, the first BUG-08 fix attempt and the purge test fix | 85 checks, 1 failed: the BUG-08 fix had a PHP escaping mistake (`"\2"` in a double-quoted string) |
| 8 | Same, after correcting the BUG-08 fix | **85 checks, 0 failed** (exit 0) |
| 9 | `npx vite build` + secret scan of `dist/` (admin token value, `api_key/secret/password = "…"`, `AIza…`, `sk_live_`, `rzp_live_`, `clickchat`) | 0 matches |
| 10 | `ui_smoke.mjs` against the demo build | **First run: 2 failures** (About and Our Team editors overflow to 1,298 px at 390 px, BUG-12). After the fix: **26 screens, 0 failed** |
| 11 | Live read-only checks (`curl` GET) of 19 main and profile URLs, `robots.txt`, `sitemap.xml` and 20 sampled sitemap URLs | See `seo-regression-report.md`. BUG-01, BUG-13 to BUG-17 recorded. |

## What the 85 API checks cover

| Group | Checks |
|---|---|
| Sign-in and sessions | 6 |
| Roles: off, turning on, editor, viewer, owner and token, new users | 23 |
| Full permission matrix (16 actions × 5 identities) | 1 (80 requests) |
| Activity log | 9 |
| Profile quality score | 9 |
| Profile filters | 11 |
| Profile Changes (triggers) | 4 |
| News sanitizing | 2 |
| Image upload | 4 |
| Server Cleanup safety | 11 |
| Logging failure does not break saves | 1 |
| Sign-in rate limit | 1 |

## Not run, with the reason

| Test | Reason |
|---|---|
| Live tests of the new CMS features | Not uploaded to production yet |
| Public registration forms (OTP, insert) on live | Each run sends a real WhatsApp message and writes a real registration. Needs a test number and a staging copy. |
| Payments | No gateway code in the repo; no sandbox keys |
| Instagram | Not implemented; no Meta app |
| Events, form builder, premium, templates, analytics | Not implemented |
| Load / performance tests | Not allowed against production; no staging |
| Homepage editor and Team CRUD round trips | Not automated yet (see checklist A4, A5) |

## How to run

```bash
# API (needs a MariaDB/MySQL user that may CREATE/DROP DATABASE; never production)
TEST_DB_HOST=127.0.0.1 TEST_DB_USER=tester TEST_DB_PASS=... php cms/tests/api_test.php

# Admin screens (serve the demo build or `npm run dev`, then:)
BASE='http://localhost:5173/' PW=$(npm root -g)/playwright node cms/tests/ui_smoke.mjs
```
