# Security Audit Report

Scope:
- The CMS API (`cms/backend`), the admin (`cms/admin`), and the public PHP pages in this repo.
- Read-only requests to www.fsia.in.
- No destructive or load testing was done against production.
- No secrets are reproduced in this report.

## Summary

| Area | Result |
|---|---|
| CMS API authentication | Passed tests: signed HMAC sessions, tamper and name-swap refused, sign-in rate limit, sessions end on password change |
| CMS authorization | Passed: server-side check on every endpoint (`rbac_enforce` in `require_admin`); 80-request matrix verified |
| SQL injection (CMS) | Passed: prepared statements everywhere. Injection strings in search return 200 with 0 rows. |
| XSS (CMS-stored HTML) | Passed: allow-list sanitizer removes script, event handlers, `javascript:` and iframes. URL fields accept http(s) and `/` only. |
| File upload | Passed: type taken from the file's bytes; PHP-as-JPEG and SVG refused; re-encoded; random names |
| Path traversal (Server Cleanup) | Passed: 5 attack paths refused; protected files and folders refused |
| Secret exposure | **Fixed:** the Server Cleanup preview showed secrets from config backups (BUG-08). The built admin bundle contains no secrets (scan, test 9). |
| SSRF (Site Check) | Limited to fsia.in pages; **hardened** to http/https. Images inside fsia.in pages are still fetched from wherever they point (status only; low risk). |
| CSRF | **N/A for the CMS API**: authentication is a header token, not a cookie, so a third-party site cannot send it. The public forms have no CSRF tokens (see BUG-02/03). |
| Audit logging | Passed: changes, refusals, sign-ins, failed sign-ins and downloads. Passwords, tokens and OTPs redacted. |
| Public registration pages | **Open P1 issues in the repo code** (below) |

## Open issues by severity

**P1**

1. **BUG-02:** OTP send has no rate limit.
   - Where: the `?action=send_otp` handler in every `miss-*.php` / `mrs-*.php`.
   - Fix: limit per phone (e.g. 3 per 15 min) and per IP (e.g. 10 per hour) in `otp_verifications`, plus a simple challenge.
2. **BUG-03:** OTP verify has no attempt limit.
   - Where: `?action=verify_otp`.
   - Fix: lock the code after 5 wrong tries and require a new one. Use `random_int()` instead of `rand()`.
3. **BUG-04:** the messaging webhook ID is hard-coded in 64 files, with TLS verification off.
   - Fix: move it to config, rotate it with the provider, and set `CURLOPT_SSL_VERIFYPEER => true`.
   - All three fixes belong in one shared include (`otp.php`) that the 64 pages call. They also need the **live copies** of those pages first, because the live files differ from the repo.

**P2**

4. **BUG-05:** a DB error is shown to visitors.
5. **BUG-06:** guessable `md5(id)` success token (suspected; needs the live `registration-success.php`).
6. **BUG-07:** unescaped `REQUEST_URI` in the canonical tag.
7. **RISK-02:** the admin token sits in `sessionStorage`. Add a Content-Security-Policy for `/cms-admin/`.

**Earlier findings still open (owner actions)**

- Public `contact-submissions.log`, zip and `.bak` files are reachable over HTTP. Use Server Cleanup, plus a `FilesMatch` deny rule.
- Rotate the Google client secret that was exposed earlier.

## Personal data

- No personal data is sent to any external AI provider; the quality score uses local rules only.
- Change log and activity log: email, mobile, password, OTP and phone values are not stored.
- The profile CSV export has no contact fields.
- Synthetic data only was used in tests.

## Not tested (BLOCKED)

- The old admin `/fsiaadmm21/`, `starindia.fsia.in` and the live payment flow: code not available.
- The live public forms: would send real messages.
- Penetration testing of production: not authorized.
