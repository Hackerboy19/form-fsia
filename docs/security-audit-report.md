# Security Audit Report

Updated 2026-10-16.

Scope:
- CMS API and admin.
- `starindia-admin/admin-enhancements.js`.
- Public PHP pages in the repo.
- Read-only requests to www.fsia.in, starindia.fsia.in and admin.fsia.in.

No destructive or load testing was done. No secrets or personal data are reproduced here.

## Summary

| Area | Result |
|---|---|
| CMS authentication | PASS: signed sessions, tamper and name-swap refused, rate limit, sessions end on password change |
| CMS authorization | PASS: server-side check on every endpoint; 80-request matrix. Single admin by default: roles cannot be switched on from the UI (`roles_enabled` in config.php). |
| SQL injection (CMS) | PASS: prepared statements; injection strings in search return 200 with 0 rows |
| XSS (CMS-stored HTML) | PASS: allow-list sanitizer; URL fields accept http(s) and `/` only |
| Upload | PASS: type from bytes, re-encoded, random names |
| Path traversal | PASS: 5 attack paths refused |
| Secrets | PASS: Server Cleanup preview masks passwords and keys; the admin bundle contains no secrets |
| CMS writes to profiles | **None.** The in-CMS profile editor was removed. |
| CMS reads from the starindia API | GET only (verified by the stand-in API's method log). Cached for 5 minutes in the server temp folder (0700), holding text lengths, hero/logo values and SEO fields only: never phone, email or address (verified). |
| Deep link script | Only clicks and types in the starindia admin page; never saves (0 write requests in tests). Runs only with a valid `#fsia-open=<digits>`; values go into form fields through the DOM, never into HTML. |
| CSRF | N/A for the CMS API (header token, not cookies). Public forms have none (see P1 below). |
| Audit logging | PASS |

## Open issues by severity

**P1**, in the repo copies of the public pages (needs the live copies):
1. **BUG-02:** OTP send has no rate limit.
2. **BUG-03:** OTP verify has no attempt limit.
3. **BUG-04:** hard-coded messaging webhook ID, with TLS verification off.

**P2**
- **BUG-05:** DB error shown to visitors.
- **BUG-06:** guessable success token (suspected).
- **BUG-07:** unescaped canonical.
- **RISK-02:** admin token in `sessionStorage`.
- **RISK-07 (new):** `admin.fsia.in/api/profiles` answers without a login, including unpublished profiles.
  - A 100-profile sample showed that hidden phone and email are **not** returned.
  - Still, drafts should not be public. Recommend: login required, or published-only for anonymous callers.
  - The CMS then needs a read-only key in config.

**Owner actions still open from earlier**
- Remove public logs, zips and `.bak` files.
- Rotate the exposed Google client secret.

## Not tested (BLOCKED)

- The starindia admin's own authentication and roles (its code is not in the repo).
- The old admin `/fsiaadmm21/`.
- Live payment flow.
- Live public forms.
