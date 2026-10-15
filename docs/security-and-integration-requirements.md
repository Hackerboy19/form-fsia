# Security and Integration Requirements

## 1. Secrets and configuration
- All credentials live outside the web root or in Plesk environment variables, never in the repo: DB, CMS admin token, Meta app secret, payment keys, WhatsApp/OTP webhook, encryption key.
- `cms/backend/config.php` and `admin_users.php` are already git-ignored; keep it that way.
- **Found in code:** the WhatsApp OTP webhook id (`auto.clickchat.io/webhook/…`) is hard-coded in ~70 state pages. Anyone with it can send messages on FSIA's account. Move it into config and rotate it once the pages use the config value.
- Rotate the Google client secret that was exposed earlier (still pending).

## 2. Open security issues in the current site
| Issue | Where | Fix (planned phase) |
|---|---|---|
| Public log/zip/.bak files reachable over HTTP | httpdocs root | FilesMatch deny block + delete (Phase 0) |
| `mysqli_error()` shown to visitors | `miss-universe-registration.php` and similar | Log server-side, generic message (Phase 2) |
| Success link uses `md5(id)` (guessable) | registration redirect | Random 256-bit token (Phase 7, before payments) |
| String-built SQL in public pages | ~75 form pages | Prepared statements via form builder (Phase 6) |
| Single admin role, no audit | CMS | **Built** (roles + activity log); live after upload and once an owner turns roles on |
| No CSRF on public forms; OTP endpoint can be spammed | state pages | CSRF token + rate limit per mobile/IP (Phase 6) |

## 3. Personal data
- `registration` holds name, mobile, email, DOB, age for ~100k people.
- New features read personal fields only server-side. API responses to the admin mask mobile/email unless the role has `profiles.view_contact`.
- Exports containing contact data require the `profiles.export` permission and are logged in the audit log.
- **No personal data is sent to any external AI provider** unless the owner approves a specific provider and purpose in writing. Quality scoring in Phase 3 uses local rules only.
- Change log already hides password/email/mobile/phone/whatsapp/otp values.

## 4. Instagram (official Meta API only)
- Use Instagram API with Instagram Login (or Facebook Login for Business). OAuth only; **never ask for or store Instagram passwords**.
- Needs: Meta developer account, an app, business verification, privacy-policy and data-deletion URLs on fsia.in, App Review for the needed permissions.
- Tokens are stored encrypted, refreshed before expiry, and revocable by the profile owner.
- Until approved, only the existing `instagram` handle is validated and linked.

## 5. Payments
- **No gateway is added and nobody is charged until the owner approves:** the gateway (e.g. Razorpay/PayU/Cashfree), the merchant account, fees per event, GST invoicing, and the refund policy.
- Required design: amounts decided server-side from the `events` table; order created server-side; signature verified on callback **and** webhook; idempotent webhook handling; reconciliation report for finance.
- The current payment flow (columns `payment_status`, `trans_id`, `pdate`) is not in the repo. Its code must be reviewed before anything replaces or touches it.

## 6. Missing access and credentials (blocking items)

| Needed | For | Status |
|---|---|---|
| Full `httpdocs` code (without uploads) | Know the live registration, payment, profile and old-admin code | **Missing** |
| DB schema dump (no data) of site DB + CMS DB | DB change plan, indexes | **Missing** |
| Staging subdomain + masked DB copy | Safe testing | **Missing** |
| MySQL/MariaDB version | Triggers / JSON support | Unknown |
| Current payment gateway name + who manages it | Phase 7 | **Missing** |
| Meta developer app (app id/secret), business verification | Phase 8 | **Missing** |
| `starindia.fsia.in` and `/fsiaadmm21/` code | They write to `registration`; RBAC/audit must cover them | **Missing** |
| Business decisions: premium plan list/prices, event list/fees, roles per staff member | Phases 2, 4, 6, 7 | **Missing** |
| Cron access in Plesk | Profile index rebuild, token refresh | Unknown |

## 7. Approval gates
Approval from the owner is needed before: any production migration on an existing table, enabling the change-log triggers on production (already opt-in), adding a paid service (gateway, Meta, AI, SMS), charging users, or deleting server files (Server Cleanup already moves to Trash only).
