# FSIA Next-Generation Platform – Implementation Plan

Principle: build **beside** the existing site, never rewrite it in one go. Every phase ships behind an admin switch, is reversible, and is verified on a staging copy before production.
A feature counts as done only when backend, DB behaviour, UI, permissions and tests are all implemented and verified.

## Phase 0 – Preconditions (blocking, mostly owner actions)

| # | Item | Owner |
|---|---|---|
| 0.1 | Stop direct server edits; all changes go through the repo | Owner/team |
| 0.2 | Download full `httpdocs` (code only, no uploads) + DB **schema-only** dump (`mysqldump --no-data`) and share | Owner |
| 0.3 | Create a staging subdomain (e.g. `staging.fsia.in`) with a copy of the DB that has personal data masked | Owner (Plesk) |
| 0.4 | Finish pending security clean-up (public logs/zips/.bak, rotate Google secret) | Owner |
| 0.5 | Upload the pending admin-tools zip and run the team multi-category migration | Owner |

## Phase 1 – Audit (this delivery)
The four docs in `docs/`. No code or DB changes.

## Phase 2 – Foundations (no visible change for visitors)

**Status (2026-10-15):** step 1 (roles + activity log) and the API test harness are built and tested locally; waiting for upload. Step 3 (profile index) is next.

What was built for step 1, and how it keeps today's admin working:
- Roles: Owner, Admin, Editor, Viewer (`cms/backend/lib/rbac.php`). `events_manager` and `finance` are added when those modules exist, so no role has permissions for screens that do not exist yet.
- **Roles are off until an owner turns them on** (Settings → Users & Roles). Until then everyone keeps full access, the same as today. Turning them on makes every current user an Owner first; the last Owner can never be demoted.
- Permissions are checked centrally in `require_admin()` from endpoint + method + action, so every existing endpoint (including the hand-patched live `teams.php`) is covered without editing it. Unknown future endpoints need `other.write` for changes.
- Activity log (`cms_audit_log`): every change, sign-in, failed sign-in, refused request and server-file download, with user, role, IP and a redacted copy of what was sent (passwords/tokens/OTP replaced by `***`). If the log cannot be written, the change still saves.
- Admin: Users & Roles and Activity Log screens; the menu hides what a role cannot open; the server refuses it anyway.
- Passwords are still managed on the server with `set-password.php` (no password handling over the web).
- Tests: `php cms/tests/api_test.php` (40 checks, needs a throwaway MariaDB user; see the file header).

1. **RBAC + audit log** (Module 9) first, because every later module needs permissions.
   - Roles: `owner`, `admin`, `editor`, `events_manager`, `finance`, `viewer`. Move `admin_users.php` users into a `cms_users` table with a role, keeping the current login working.
   - `require_permission('profiles.edit')` next to `require_admin()`.
   - `cms_audit_log` written for every CMS write.
2. **Test harness**: PHPUnit for API endpoints against a disposable MariaDB; Vitest for admin UI logic; one smoke script that checks key public URLs return 200 (reuse Site Check).
3. **Read-only profile index** (`fsia_profile_index`): one row per profile with derived fields (category, year, has_bio, has_banner, image_ok, seo issues, last_changed). Rebuilt by a cron job, so filters stop scanning `registration` live.

## Phase 3 – Profile intelligence and quality score (Modules 1, 2)
- Extend Site Check filters onto the index; saved filters; bulk export.
- Quality score 0–100 from **real, explainable checks only** (bio length, image present and loading, banner, SEO title/description, social links valid, achievements count). Weights stored in config; the score breakdown is always shown. No AI scoring unless approved (see security doc).
- Admin "fix list": lowest-scoring profiles first.

## Phase 4 – Premium plans and entitlements (Module 3)
- Tables: `plans`, `plan_features`, `profile_subscriptions` (start/end, source, status).
- Entitlements checked server-side (`profile_has('custom_template')`).
- **No charging** in this phase: plans are assigned manually by admin until the payment flow (Phase 7) is approved.
- **Business decisions needed:** plan names, prices, features, duration, refunds.

## Phase 5 – Templates and migration (Module 4)
- New profile template rendered by one PHP front controller: `/{slug}` → lookup in `registration.url`. This replaces thousands of `.htaccess` rules with one rule (big performance win). It needs careful rollout: old rules stay as fallback until every slug is verified with Site Check.
- Template choice per profile (`profile_template` column in a side table, not in `registration`).
- Migration runs per category in batches, with a before/after Site Check comparison (the baseline feature already exists).

## Phase 6 – Events, promotions and form builder (Modules 6, 7)
- `events` (name, category, dates, venue, status, fees), `promotions` (banner, target pages, schedule) managed in CMS; public pages read them via `fsia_cms_client.php`.
- Form builder: JSON form definitions (`forms`, `form_fields`), one renderer, one submit endpoint with the existing OTP check. Pilot on **one** state page, then migrate others one by one. Old pages stay until their replacement is verified.

## Phase 7 – Registrations and payments (Module 8)
- `registrations` linked to `events` and `form_submissions`; status machine `draft → otp_verified → payment_pending → paid → confirmed / refunded / cancelled`.
- Payment gateway **only after approval** of gateway, account and flow. Server-side signature verification, webhook idempotency, no amounts from the browser, opaque random success tokens (not `md5(id)`).
- Reconcile with the existing `payment_status/trans_id/pdate` columns; never rewrite historic rows.

## Phase 8 – Instagram (Module 5)
- Official Meta Instagram API (Instagram Login / Graph API) with OAuth. Profile owners connect their own account; **never ask for passwords**.
- Needs a Meta developer app, business verification and App Review (weeks). Until then: validate and normalise the existing `instagram` handle only.

## Phase 9 – Analytics (Module 10)
- First-party counters (page views per profile/event, form starts/completions, payments) stored in aggregate tables. Dashboards show only measured data; empty states say "no data yet", never sample numbers.

## Order and dependencies

```
0 → 2 (RBAC, tests, index) → 3 → 4 → 5
                     ↘ 6 → 7 (needs payment approval)
                     ↘ 8 (needs Meta app)      9 runs alongside 3–7
```

## Rough effort (engineering days, excluding approvals)
Phase 2: 6–8 · Phase 3: 5–7 · Phase 4: 4–5 · Phase 5: 8–12 · Phase 6: 10–14 · Phase 7: 8–10 · Phase 8: 5–7 (+ Meta review) · Phase 9: 4–6.
These are estimates and depend on what the unseen live code (Phase 0.2) contains.
