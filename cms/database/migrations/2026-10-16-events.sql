-- Events & Promotions (CMS database).
-- You do not need to run this by hand: api/events.php creates the table on first use.
-- Kept as the reference definition. Rollback: DROP TABLE cms_events;  (deletes all events)

CREATE TABLE IF NOT EXISTS cms_events (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(150) NOT NULL,
    status         VARCHAR(12) NOT NULL DEFAULT 'draft',
    starts_at      DATETIME NOT NULL,
    ends_at        DATETIME NOT NULL,
    venue          VARCHAR(200) NOT NULL DEFAULT '',
    city           VARCHAR(100) NOT NULL DEFAULT '',
    summary        VARCHAR(300) NOT NULL DEFAULT '',
    description    MEDIUMTEXT NULL,
    banner_desktop VARCHAR(512) NOT NULL DEFAULT '',
    banner_mobile  VARCHAR(512) NOT NULL DEFAULT '',
    guests         TEXT NULL,
    cta_label      VARCHAR(40) NOT NULL DEFAULT 'Register now',
    cta_url        VARCHAR(512) NOT NULL DEFAULT '',
    priority       SMALLINT NOT NULL DEFAULT 0,
    promo_start    DATETIME NOT NULL,
    promo_end      DATETIME NOT NULL,
    targets        TEXT NULL,
    excludes       TEXT NULL,
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    KEY idx_status (status, promo_start, promo_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
