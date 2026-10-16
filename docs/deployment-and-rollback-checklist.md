# Deployment and Rollback Checklist

The deployment is the CMS admin tools zip: Profiles & Score, Activity Log, Site Check, Profile Changes and Server Cleanup, plus the audit fixes.

**Login:** the upload does not touch `config.php` or `admin_users.php`, so the current admin username and password keep working. There is one admin with full access. Roles stay switched off: the Users & Roles screen is hidden and cannot be turned on from the admin. To use them later, add `'roles_enabled' => true` to `cms/backend/config.php`.

**Simulated upload (2026-10-16):**
- Method: the zip was extracted over a copy of the old live version (commit 3315ddd), with an existing admin login.
- Only the 8 listed backend files and `cms-admin-test/` changed. `config.php`, `admin_users.php`, `.htaccess`, `auth.php`, `teams.php`, `sections.php`, `seo.php`, `news.php` and `upload.php` stayed byte-for-byte the same.
- `cms/tests/upgrade_check.php`: 29/29 passed with the existing login.
- All 12 admin screens worked at 1280 and 390 px, with no errors and no sign-outs.
- A save through the UI persisted after a reload.

**Database:** the upload makes no changes to existing tables.
- `cms_audit_log` is created on the first logged change.
- `cms_user_roles` is created only when an owner turns roles on.
- Profile Changes triggers are installed only when someone clicks "Turn on".

## Before upload

1. In Plesk File Manager, download these to your computer. Don't leave `.bak` copies on the server.
   - `httpdocs/cms/backend/lib/bootstrap.php` (the zip replaces it)
   - `httpdocs/cms/backend/api/cleanup.php`, `sitecheck.php`, `changelog.php`, if they exist
2. Plesk → Databases → Export dump of the CMS database (a backup, same day).
3. Make sure nobody is editing files on the server during the upload.

## Upload

4. Upload `fsia-admin-tools-upload.zip` to `httpdocs` → Extract → "Replace existing files". It contains:
   - `cms/backend/api/`: `audit.php`, `changelog.php`, `cleanup.php`, `sitecheck.php`, `users.php`
   - `cms/backend/lib/`: `bootstrap.php`, `rbac.php`, `site_db.php`
   - `cms-admin-test/` (the new admin; the current `/cms-admin/` is not touched)
   - It does **not** contain `teams.php`, `config.php` or any public page.

## Verify on live (report results back)

5. Open `https://www.fsia.in/cms/backend/api/users.php?action=me` without signing in. Expect a JSON 401, not a 404 or 500.
6. Sign in at `/cms-admin-test/` and check:
   - Dashboard, Global SEO, About, Our Team, Team Directory, News open and show the real data.
   - Save one harmless SEO field and change it back.
   - Profiles & Score loads for one category, with scores.
   - Activity Log shows that save.
   - The old `/cms-admin/` still works.
7. Re-run the SEO URL list from `seo-regression-report.md`; nothing should change.
8. When all is fine, copy `cms-admin-test/` over `cms-admin/`.

## Separate quick wins (owner approval)

- **robots.txt** (BUG-01): change the `Sitemap:` line to `Sitemap: https://www.fsia.in/sitemap.xml`.
- `about.php`: add `<link rel="canonical" href="https://www.fsia.in/about">` (BUG-13).
- Run `cms/database/migrations/2026-10-15-team-multi-category.sql` if the Team page needs several categories per person.

## Rollback

| Problem | Undo |
|---|---|
| Anything in the CMS API breaks | Upload the `bootstrap.php` (and other API files) downloaded in step 1. Delete `cms/backend/lib/rbac.php`, `cms/backend/api/users.php`, `cms/backend/api/audit.php`. |
| New admin screens misbehave | Keep using `/cms-admin/`; delete `/cms-admin-test/` |
| Roles locked someone out (only possible after `roles_enabled` was set) | Remove `'roles_enabled' => true` from config.php: everyone is back to full access |
| Change log triggers cause trouble | Profile Changes → Turn off (drops the triggers), or `DROP TRIGGER fsia_log_profile_update; DROP TRIGGER fsia_log_profile_create; DROP TRIGGER fsia_log_profile_delete;` |
| Activity log grows too big | `DELETE FROM cms_audit_log WHERE created_at < NOW() - INTERVAL 180 DAY;` |

No step deletes or changes profile data.
