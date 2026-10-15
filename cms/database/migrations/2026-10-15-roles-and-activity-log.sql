-- Roles and activity log for the CMS admin (CMS database).
-- You do not need to run this by hand: the API creates both tables itself
-- (cms_audit_log on the first logged change, cms_user_roles when an owner
-- clicks "Turn on roles"). Kept here as the reference definition.
--
-- Rollback: DROP TABLE cms_user_roles;   -- roles off again, everyone full access
--           DROP TABLE cms_audit_log;    -- deletes the activity history

CREATE TABLE IF NOT EXISTS cms_user_roles (
    username    VARCHAR(64) NOT NULL PRIMARY KEY,
    role        VARCHAR(20) NOT NULL,
    updated_by  VARCHAR(64) NOT NULL DEFAULT '',
    updated_at  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created_at  DATETIME NOT NULL,
    username    VARCHAR(64) NOT NULL DEFAULT '',
    role        VARCHAR(20) NOT NULL DEFAULT '',
    ip          VARCHAR(45) NOT NULL DEFAULT '',
    method      VARCHAR(8) NOT NULL,
    endpoint    VARCHAR(64) NOT NULL,
    action      VARCHAR(40) NOT NULL DEFAULT '',
    target      VARCHAR(255) NOT NULL DEFAULT '',
    status      SMALLINT UNSIGNED NOT NULL,
    summary     TEXT NULL,
    user_agent  VARCHAR(255) NOT NULL DEFAULT '',
    KEY idx_created (created_at),
    KEY idx_user (username, created_at),
    KEY idx_endpoint (endpoint, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
