# Database Change Plan

Rules:
- **Additive only.** New tables or new nullable columns in side tables. No `DROP`, no type changes, no renames on `registration` or any existing site table.
- Every migration is a numbered file in `cms/database/migrations/`, idempotent (`IF NOT EXISTS`), with a written rollback.
- Run on staging first, then production after a full backup (Plesk DB export) taken the same day.
- Large tables (`registration`, ~100k rows): no `ALTER TABLE` on production without approval; use side tables keyed by `registration.id` instead.
- No personal data copied into new tables unless needed; mobile/email stay only in `registration`.

**No database changes were made in Phase 1.**

## Already present (from earlier work)
| Object | Status | Rollback |
|---|---|---|
| `fsia_profile_changes` + triggers `fsia_log_profile_update/create/delete` | Opt-in, installed from admin → Profile Changes | Admin "Uninstall" (drops the triggers, keeps the log) |
| `site_check_baseline` | Created on first baseline save | `DROP TABLE site_check_baseline` |
| `cms_user_roles`, `cms_audit_log` (CMS DB) | Built in Phase 2; created by the API itself (log on first change, roles when an owner turns them on). Reference SQL: `migrations/2026-10-15-roles-and-activity-log.sql` | `DROP TABLE cms_user_roles` = roles off; `DROP TABLE cms_audit_log` = history gone |
| `team_members.category` → `VARCHAR(255)` | Migration `2026-10-15-team-multi-category.sql`, **pending on live** | Not reversible to ENUM if multi-values exist; harmless otherwise |

## Planned, by phase (proposed — not created)

### Phase 2
| Table | Purpose | Key columns |
|---|---|---|
| ~~`cms_users`, `cms_role_permissions`~~ | Replaced by the simpler `cms_user_roles` (built): passwords stay in `admin_users.php`, permissions per role live in code | — |
| `fsia_profile_index` | Derived, rebuildable profile facts | `profile_id PK, regtype, category_key, year, month, has_bio, has_banner, image_ok, seo_issues, quality_score, score_breakdown JSON, last_changed, rebuilt_at` |

### Phase 4
`plans(id, code, name, price_paise, duration_days, active)`, `plan_features(plan_id, feature, value)`, `profile_subscriptions(id, profile_id, plan_id, starts_at, ends_at, status, source, created_by)`.

### Phase 5
`profile_settings(profile_id PK, template, visibility, custom_slug, updated_at)`.

### Phase 6
`events`, `event_categories`, `promotions`, `promotion_placements`, `forms`, `form_fields`, `form_submissions(id, form_id, event_id, data JSON, mobile_verified_at, created_at)`.

### Phase 7
`registrations(id, event_id, submission_id, profile_id NULL, status, amount_paise, currency, success_token CHAR(43) UNIQUE, created_at)`, `payments(id, registration_id, gateway, gateway_order_id UNIQUE, gateway_payment_id, amount_paise, status, raw_webhook JSON, created_at)`, `payment_events` (webhook log, idempotency key UNIQUE).
The existing `registration.payment_status/trans_id/pdate` stay as they are; new payments also write them for the old admin until it is retired.

### Phase 8
`instagram_connections(profile_id, ig_user_id, username, token_encrypted, token_expires_at, scopes, connected_at, revoked_at)` — tokens encrypted with a key from the environment.

### Phase 9
`analytics_daily(date, entity_type, entity_id, metric, value)` — aggregates only, no IPs stored.

## Information needed before any of this
- Full schema dump (`mysqldump --no-data`) of the site DB and the CMS DB, including engine, charset and existing indexes on `registration`.
- MySQL/MariaDB version (triggers and JSON need ≥ 5.7.8 / 10.2.3).
- Whether the CMS and site use the same database and DB user (affects permissions for triggers and new tables).
