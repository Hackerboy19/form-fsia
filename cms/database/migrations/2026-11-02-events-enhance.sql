-- Events & Promotions, Nov 2026: colour theme, offer badge, WhatsApp button, popup,
-- "show alone" (no rotation), and views / clicks per day.
-- You do not need to run this by hand: api/events.php and api/promotions.php add the
-- columns and the stats table on first use. Kept as the reference definition.
-- Rollback: DROP TABLE cms_event_stats; ALTER TABLE cms_events DROP COLUMN theme, DROP COLUMN badge,
--           DROP COLUMN whatsapp, DROP COLUMN whatsapp_text, DROP COLUMN popup, DROP COLUMN solo;

ALTER TABLE cms_events
    ADD COLUMN theme         VARCHAR(16)  NOT NULL DEFAULT 'gold',
    ADD COLUMN badge         VARCHAR(40)  NOT NULL DEFAULT '',
    ADD COLUMN whatsapp      VARCHAR(20)  NOT NULL DEFAULT '',
    ADD COLUMN whatsapp_text VARCHAR(200) NOT NULL DEFAULT '',
    ADD COLUMN popup         TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN solo          TINYINT(1)   NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS cms_event_stats (
    event_id  INT UNSIGNED NOT NULL,
    day       DATE NOT NULL,
    views     INT UNSIGNED NOT NULL DEFAULT 0,
    clicks    INT UNSIGNED NOT NULL DEFAULT 0,
    wa_clicks INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (event_id, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
