# Bug and Risk Register

Severity:
- **P0**: data loss, unauthorized access, critical failure.
- **P1**: major failure or security exposure.
- **P2**: partial or incorrect behaviour.
- **P3**: minor.

"Confirmed" means reproduced by a test or a live request. "In repo code" means confirmed in this repository's copy. The live server copy may differ, because files there are edited by hand.

No P0 issue was found in the code that was tested.

## Confirmed defects

| ID | Sev | Where | Defect | Evidence | Status |
|---|---|---|---|---|---|
| BUG-01 | P1 | live `robots.txt` | The `Sitemap:` line points to `https://influencer.fsia.in/sitemap.xml` (6 URLs). The main sitemap, `https://www.fsia.in/sitemap.xml` (4,332 URLs), is not announced to search engines. | `curl https://www.fsia.in/robots.txt` | **Open**: one-line fix, needs upload (see deployment checklist) |
| BUG-02 | P1 | state pages `miss-*.php`, `mrs-*.php` (`?action=send_otp`) | No limit on sending OTPs. Anyone can make the site send unlimited WhatsApp messages to any number, which costs money and is abuse. | Code: no counter, captcha or rate limit before the curl call | Open (in repo code; live copy unverified) |
| BUG-03 | P1 | same pages (`?action=verify_otp`) | No limit on wrong OTP guesses. A 6-digit code valid for 10 minutes can be brute-forced. | Code: no attempt counter | Open (in repo code) |
| BUG-04 | P1 | 64 PHP files | The WhatsApp webhook ID is hard-coded in every state page, and TLS verification is turned off for that call (`CURLOPT_SSL_VERIFYPEER => false`). | `grep -l auto.clickchat.io/webhook *.php` | Open: move to config, rotate the ID, turn verification on |
| BUG-05 | P2 | `miss-universe-registration.php` (insert failure) | `mysqli_error()` is shown to visitors, which leaks schema details. | line ~154 | Open (the live URL currently returns 404) |
| BUG-06 | P2 | registration redirect | The success link uses `md5(insert_id)`, which can be guessed. This matters if `registration-success.php` shows personal data or payment state. | Code; `registration-success.php` is not in the repo | Suspected: needs the live file |
| BUG-07 | P2 | state pages | The canonical tag prints `$_SERVER['REQUEST_URI']` without escaping. | Code (live `?x=1` was stripped correctly) | Open (in repo code) |
| BUG-08 | P2 | `cms/backend/api/cleanup.php` `cl_preview` | Server Cleanup preview showed passwords and keys from old config copies (`config.php.bak`, `.env.bak`) in plain text to admins. | Test "preview hides passwords…" failed before the fix | **Fixed**: `cl_redact_secrets()`, 2 regression tests |
| BUG-09 | P3 | `cms/backend/api/sitecheck.php` profiles filter | An invalid `year`/`month` (e.g. `year=abc`) returned 0 profiles instead of being ignored. | Test "unknown filter values are ignored" failed before the fix | **Fixed**, regression test |
| BUG-10 | P3 | `sitecheck.php` `sc_curl` | Page and image checks followed redirects with any protocol curl supports. | Code review | **Hardened**: http/https only |
| BUG-11 | P2 | process | The live server is edited by hand (admin bundle, `teams.php`, category pages, feach-data). The repo is not the source of truth, and an upload can overwrite those edits. | Earlier diff of the live admin bundle | Open: team process |
| BUG-12 | P3 | `cms/admin/src/pages/PageEditor.jsx` | On phones the About / Our Team editors were 1,298 px wide, because the preview frame stretched the grid column. | `ui_smoke.mjs`: overflow at 390 px | **Fixed** (`min-w-0`), smoke test passes |
| BUG-13 | P2 | live `/about` and `/about.php` | Both return 200 with the same content and no canonical tag (duplicate content). The sitemap lists `about.php`. | Live GET | Open: add `<link rel="canonical" href="https://www.fsia.in/about">` |
| BUG-14 | P2 | live `sitemap.xml` | Contains a 404 URL (1 of 20 sampled: `/supriya-jain-mrs-india-2021-id-15273`) and 9 duplicate entries. | Live GET | Open: regenerate from live profiles only |
| BUG-15 | P3 | live homepage | No `<h1>`. | Live GET | Open |
| BUG-16 | P3 | live profile pages, `/news-coverage` | 2 `<h1>` per profile page. Titles of 77–93 characters get cut off in Google. | Live GET | Open |
| BUG-17 | P2 | live server | Some requests time out, then work on retry. The 12,940-line `.htaccess` is a known contributor. | 9 time-outs over ~45 requests, all fine on retry (the test network may contribute) | Open: front-controller routing (plan Phase 5) |
| BUG-18 | P3 | repo vs live | `miss-universe-registration.php` is in the repo but returns 404 live. | Live GET | Open: decide which is correct |

## Risks and recommendations (not defects)

| ID | Sev | Risk | Recommendation |
|---|---|---|---|
| RISK-01 | P2 | Uploading the zip replaces `cms/backend/lib/bootstrap.php`. If it was hand-edited on the server, those edits are lost. Live API messages match the repo version. | Download a backup of that file first (deployment checklist step 1) |
| RISK-02 | P2 | The admin session token is kept in `sessionStorage`. Any script injection in the admin could read it. | Add a Content-Security-Policy for `/cms-admin/` |
| RISK-03 | P3 | Viewers can download the profile CSV. It holds names, links and problems only: no email or mobile. | Fine for now. Add `profiles.export` when contact fields are added. |
| RISK-04 | P2 | Profile filters scan at most 5,000 rows per request. | Profile index (plan Phase 2 step 3) |
| RISK-05 | P2 | No staging site. Every change goes straight to production. | Staging subdomain with a masked DB copy |
| RISK-06 | P1 | Payments: the live gateway code has never been reviewed. | Share the live payment files before any payment work |
| RISK-07 | P3 | Score weights live in code. | Admin-editable weights once the business confirms them |
