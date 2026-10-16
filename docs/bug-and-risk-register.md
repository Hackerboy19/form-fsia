# Bug and Risk Register

Updated 2026-10-16.

Severity:
- **P0**: data loss, unauthorized access, critical failure.
- **P1**: major failure or exposure.
- **P2**: partial or incorrect behaviour.
- **P3**: minor.

"Confirmed" means reproduced by a test or a live GET request. "In repo code" means the repository copy; the live copy may differ, because the server is edited by hand.

No P0 issue was found in the code that was tested.

## Confirmed defects

| ID | Sev | Where | Defect | Evidence | Status |
|---|---|---|---|---|---|
| BUG-01 | P1 | live `robots.txt` | Sitemap pointed to the influencer subdomain | live GET | **Fixed live** (now `https://www.fsia.in/sitemap.xml`) |
| BUG-02 | P1 | `miss-*.php` / `mrs-*.php` `?action=send_otp` | No limit on sending OTPs (unlimited WhatsApp messages to any number) | repo code | Open: needs live copies |
| BUG-03 | P1 | same, `?action=verify_otp` | No limit on wrong OTP guesses | repo code | Open |
| BUG-04 | P1 | 64 PHP files | Messaging webhook ID hard-coded; TLS verification off for that call | `grep auto.clickchat.io/webhook` | Open: move to config, rotate, verify TLS |
| BUG-05 | P2 | `miss-universe-registration.php` | `mysqli_error()` shown to visitors | repo code | Open (URL is 404 live) |
| BUG-06 | P2 | registration redirect | `md5(id)` success token is guessable | repo code; target file not in repo | Suspected |
| BUG-07 | P2 | state pages | Canonical prints `REQUEST_URI` unescaped | repo code | Open |
| BUG-08 | P2 | `api/cleanup.php` `cl_preview` | Preview showed passwords from config backups | test | **Fixed** (`cl_redact_secrets`), 2 regression tests; live since the earlier upload |
| BUG-09 | P3 | `sitecheck.php` profiles | Invalid year emptied the list | test | **Fixed**, regression test |
| BUG-10 | P3 | `sitecheck.php` `sc_curl` | Any curl protocol on redirects | review | **Hardened** (http/https only) |
| BUG-11 | P2 | process | Live files edited by hand; repo is not the source of truth | earlier diffs | Open (team process) |
| BUG-12 | P3 | `PageEditor.jsx` | Editors overflowed on phones | `ui_smoke.mjs` | **Fixed** |
| BUG-13 | P2 | live `/about`, `/about.php` | Duplicate pages, no canonical | live GET (re-checked today) | Open |
| BUG-14 | P2 | live `sitemap.xml` | 9 duplicates and at least one 404 URL | live GET (re-checked today) | Open |
| BUG-15 | P3 | live homepage | No H1 | live GET | Open |
| BUG-16 | P3 | live profile pages, news | 2 H1 per profile page; titles 77–93 chars | live GET | Open |
| BUG-17 | P2 | live server | Time-outs on first request, fine on retry | live GET | Open (routing / `.htaccess` size) |
| BUG-18 | P3 | repo vs live | `miss-universe-registration.php` in repo, 404 live | live GET | Open |
| BUG-19 | P2 | starindia data (e.g. #107550 Azra) | Hero image and profile logo fields hold a comma list of 12 files; the page prints that list as one `src`, so 3 images are broken | live GET of `starindia.fsia.in/azra` | Open: fix in the starindia admin (Hero / Identity). The CMS now lists these (pending upload). |
| BUG-20 | P2 | SEO | One person, two indexable profiles: `www…/azra-chennai-title-holder-lawyer-2025` (#86294) and `starindia.fsia.in/azra` (#107550), each canonical to itself | live GET | Open: decide which one is primary, then canonical or 301 the other (needs approval: URL/indexing change) |
| BUG-21 | P2 | live `/cms-admin/` Profiles & Score | starindia-edited profiles were judged from empty www columns ("No meta title", "No banner") instead of their starindia data | reported by owner; reproduced with the stand-in API | **Fixed in the pending zip** (`lib/starindia.php`); 12 regression checks |
| BUG-22 | P2 | CMS profile editor (`api/profiles.php`, removed) | A second profile editor in the CMS could overwrite starindia admin edits | design review | **Resolved by removing** the editor; fixes happen only in the starindia admin |
| BUG-23 | P3 | `Modal.jsx` | Closing a nested dialog closed its parent | browser test | **Fixed** |

## Risks and recommendations

| ID | Sev | Risk | Recommendation |
|---|---|---|---|
| RISK-01 | P2 | The zip replaces `cms/backend/lib/bootstrap.php` | Back it up first (deployment checklist) |
| RISK-02 | P2 | Admin token in `sessionStorage` | Content-Security-Policy for `/cms-admin/` |
| RISK-03 | P3 | Profile CSV export is available to anyone who can view profiles (no contact fields in it) | Add an export permission if contact fields are ever added |
| RISK-04 | P2 | Profile filters scan at most 5,000 rows; starindia data is fetched for at most 400 profiles per load (the rest show "Not checked") | Profile index (plan Phase 2) |
| RISK-05 | P2 | No staging | Staging subdomain with masked data |
| RISK-06 | P1 | Live payment code never reviewed | Share it before any payment work |
| RISK-07 | P2 | `admin.fsia.in/api/profiles` answers without a login, including unpublished profiles. In a 100-profile sample, hidden phone/email were **not** exposed. | Ask the starindia developer to require login, or return only published profiles to anonymous callers. The CMS check uses the same GET; if the API gets a login, give the CMS a read-only key via config. |
| RISK-08 | P3 | The deep link drives the starindia admin through its page (button titles, labels). A redesign of that admin can break it. | It fails safe: a corner note says the profile or section was not found; nothing is saved. Re-run `starindia-admin/test-deeplink.mjs` after starindia admin updates. |
| RISK-09 | P3 | Global SEO, Our Team CTA and News in the CMS do not reach the live pages (A15) | Wire those pages to the CMS (needs the live files) |
