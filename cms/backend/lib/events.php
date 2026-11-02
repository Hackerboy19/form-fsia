<?php
declare(strict_types=1);

/*
 * Events and the website-wide promotion built from them.
 *
 * An event is saved as draft, published or cancelled. What visitors see follows
 * from that and the dates (India time):
 *   draft      -> never shown
 *   cancelled  -> never shown
 *   published  -> "scheduled" before promo_start, "live" while promoted,
 *                 "completed" after the event ends (or after promo_end)
 * Only one promotion is shown per page: the live one with the highest priority
 * (then the earliest event), so overlapping events never stack up.
 */

const EVENT_TZ = 'Asia/Kolkata';
const EVENT_STATUSES = ['draft', 'published', 'cancelled'];
/* Guests marked "Show on website bar" (e.g. the celebrity chief guest); at most this many. */
const EVENT_BAR_GUESTS = 2;
const EVENT_TABLE_SQL = "CREATE TABLE IF NOT EXISTS cms_events (
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
    theme          VARCHAR(16) NOT NULL DEFAULT 'gold',
    badge          VARCHAR(40) NOT NULL DEFAULT '',
    whatsapp       VARCHAR(20) NOT NULL DEFAULT '',
    whatsapp_text  VARCHAR(200) NOT NULL DEFAULT '',
    popup          TINYINT(1) NOT NULL DEFAULT 0,
    solo           TINYINT(1) NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    KEY idx_status (status, promo_start, promo_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

/* Columns added after the first release (Nov 2026). Added to an existing table on first use. */
const EVENT_NEW_COLUMNS = [
    'theme'         => "VARCHAR(16) NOT NULL DEFAULT 'gold'",
    'badge'         => "VARCHAR(40) NOT NULL DEFAULT ''",
    'whatsapp'      => "VARCHAR(20) NOT NULL DEFAULT ''",
    'whatsapp_text' => "VARCHAR(200) NOT NULL DEFAULT ''",
    'popup'         => 'TINYINT(1) NOT NULL DEFAULT 0',
    'solo'          => 'TINYINT(1) NOT NULL DEFAULT 0',
];
/* Views and clicks per event per day (India time). */
const EVENT_STATS_SQL = "CREATE TABLE IF NOT EXISTS cms_event_stats (
    event_id  INT UNSIGNED NOT NULL,
    day       DATE NOT NULL,
    views     INT UNSIGNED NOT NULL DEFAULT 0,
    clicks    INT UNSIGNED NOT NULL DEFAULT 0,
    wa_clicks INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (event_id, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
const EVENT_STAT_KINDS = ['view' => 'views', 'click' => 'clicks', 'wa' => 'wa_clicks'];
/* Bar colours. Same keys as THEMES in /cms/promo.js. */
const EVENT_THEMES = ['gold', 'royal', 'rose', 'emerald', 'ivory'];
/* How many live events can take turns on one page. */
const EVENT_ROTATE_MAX = 5;

/* Page groups an event can target or exclude, besides "/path" rules (with * wildcards). */
const EVENT_GROUPS = [
    'all'      => 'Every page',
    'home'     => 'Homepage',
    'pageants' => 'State pageant pages (miss-*.php, mrs-*.php)',
    'profiles' => 'Profile pages',
];
/* Site pages that are not profiles (everything else with one path part is a profile URL). */
const EVENT_MAIN_PATHS = ['/about', '/about.php', '/contact', '/contact.php', '/our-teams.php', '/quickapply', '/news-coverage',
    '/special-news-coverage', '/faq.php', '/fashion-designer.php', '/makeup-artist.php', '/channel-partner.php', '/socialmediaprofile.php'];

function events_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(EVENT_TZ));
}

function events_ensure_table(): void
{
    static $done = false;
    if (!$done) {
        db()->exec(EVENT_TABLE_SQL);
        $have = array_map('strtolower', db()->query('SHOW COLUMNS FROM cms_events')->fetchAll(PDO::FETCH_COLUMN));
        foreach (EVENT_NEW_COLUMNS as $col => $def) {
            if (!in_array($col, $have, true)) db()->exec("ALTER TABLE cms_events ADD COLUMN $col $def");
        }
        db()->exec(EVENT_STATS_SQL);
        $done = true;
    }
}

/** "/Miss-Assam.php?x=1" -> "/miss-assam.php"; "", "/index.php" -> "/" */
function events_norm_path(string $p): string
{
    $p = strtolower((string) parse_url($p, PHP_URL_PATH));
    $p = '/' . trim($p, '/');
    return in_array($p, ['/', '/index.php', '/index.html'], true) ? '/' : $p;
}

/** Does one rule ("all", "home", "pageants", "profiles", "/path", "/news*") match a normalized path? */
function events_rule_matches(string $rule, string $path): bool
{
    switch ($rule) {
        case 'all':
            return true;
        case 'home':
            return $path === '/';
        case 'pageants':
            return (bool) preg_match('#^/(miss|mrs)-[a-z0-9-]+\.php$#', $path);
        case 'profiles':
            return $path !== '/' && !str_contains(substr($path, 1), '/') && !str_ends_with($path, '.php')
                && !in_array($path, EVENT_MAIN_PATHS, true);
    }
    if (!str_starts_with($rule, '/')) {
        return false;
    }
    $re = '#^' . str_replace('\*', '.*', preg_quote(events_norm_path(rtrim($rule, '*')) . (str_ends_with($rule, '*') ? '*' : ''), '#')) . '$#';
    return (bool) preg_match($re, $path);
}

function events_row(array $r): array
{
    foreach (['id', 'priority'] as $k) {
        $r[$k] = (int) $r[$k];
    }
    $r['theme'] = in_array($r['theme'] ?? '', EVENT_THEMES, true) ? $r['theme'] : 'gold';
    foreach (['badge', 'whatsapp', 'whatsapp_text'] as $k) $r[$k] = (string) ($r[$k] ?? '');
    foreach (['popup', 'solo'] as $k) $r[$k] = !empty($r[$k]);
    foreach (['guests', 'targets', 'excludes'] as $k) {
        $r[$k] = json_decode((string) ($r[$k] ?? ''), true) ?: [];
    }
    $r['phase'] = events_phase($r);
    return $r;
}

/** draft | cancelled | scheduled | live | completed */
function events_phase(array $e, ?DateTimeImmutable $now = null): string
{
    if ($e['status'] !== 'published') {
        return $e['status'];
    }
    $now ??= events_now();
    $tz = new DateTimeZone(EVENT_TZ);
    $end = min(new DateTimeImmutable($e['ends_at'], $tz), new DateTimeImmutable($e['promo_end'], $tz));
    if ($now > $end) return 'completed';
    if ($now < new DateTimeImmutable($e['promo_start'], $tz)) return 'scheduled';
    return 'live';
}

/** The promotion for a page (the first one), or null. */
function events_promotion_for(string $path, ?DateTimeImmutable $now = null): ?array
{
    return events_promotions_for($path, $now)[0] ?? null;
}

/**
 * The promotions for a page, best first: the highest priority (then the earliest event).
 * They take turns on the bar, unless the first one is marked "show alone".
 */
function events_promotions_for(string $path, ?DateTimeImmutable $now = null): array
{
    $path = events_norm_path($path);
    try {
        events_ensure_table();
        $rows = db()->query("SELECT * FROM cms_events WHERE status = 'published'")->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') return [];   // no events yet
        throw $e;
    }
    $live = [];
    foreach ($rows as $r) {
        $e = events_row($r);
        if (events_phase($e, $now) !== 'live') continue;
        $in = false;
        foreach ($e['targets'] as $rule) if (events_rule_matches((string) $rule, $path)) { $in = true; break; }
        foreach ($e['excludes'] as $rule) if (events_rule_matches((string) $rule, $path)) { $in = false; break; }
        if ($in) $live[] = $e;
    }
    if (!$live) return [];
    usort($live, fn ($a, $b) => $b['priority'] <=> $a['priority'] ?: strcmp($a['starts_at'], $b['starts_at']) ?: $a['id'] <=> $b['id']);
    if ($live[0]['solo']) $live = [$live[0]];
    else $live = array_values(array_filter($live, fn ($e) => !$e['solo']));
    return array_map('events_public', array_slice($live, 0, EVENT_ROTATE_MAX));
}

/** What the website gets for one event. */
function events_public(array $e): array
{
    return [
        'id' => $e['id'], 'title' => $e['title'], 'summary' => $e['summary'],
        'starts_at' => $e['starts_at'], 'ends_at' => $e['ends_at'], 'venue' => $e['venue'], 'city' => $e['city'],
        'banner_desktop' => $e['banner_desktop'], 'banner_mobile' => $e['banner_mobile'],
        'cta_label' => $e['cta_label'], 'cta_url' => $e['cta_url'], 'version' => $e['updated_at'],
        'guests' => events_bar_guests($e['guests']),
        'theme' => $e['theme'], 'badge' => $e['badge'], 'whatsapp' => $e['whatsapp'], 'whatsapp_text' => $e['whatsapp_text'],
        'popup' => $e['popup'],
    ];
}

/** Adds one view / click / WhatsApp click to today's count. False if the event is not live. */
function events_track(int $id, string $kind): bool
{
    $col = EVENT_STAT_KINDS[$kind] ?? null;
    if (!$col) return false;
    $st = db()->prepare("SELECT * FROM cms_events WHERE id = ? AND status = 'published'");
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r || events_phase(events_row($r)) !== 'live') return false;
    db()->prepare("INSERT INTO cms_event_stats (event_id, day, $col) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE $col = $col + 1")
        ->execute([$id, events_now()->format('Y-m-d')]);
    return true;
}

/** Totals, today and the last 7 days for every event: [id => [...]] */
function events_stats(): array
{
    $today = events_now()->format('Y-m-d');
    $week = events_now()->modify('-6 days')->format('Y-m-d');
    $out = [];
    $rows = db()->query('SELECT event_id, day, views, clicks, wa_clicks FROM cms_event_stats')->fetchAll();
    foreach ($rows as $r) {
        $id = (int) $r['event_id'];
        $out[$id] ??= ['views' => 0, 'clicks' => 0, 'wa_clicks' => 0, 'today' => ['views' => 0, 'clicks' => 0, 'wa_clicks' => 0], 'week' => ['views' => 0, 'clicks' => 0, 'wa_clicks' => 0], 'days' => []];
        foreach (['views', 'clicks', 'wa_clicks'] as $k) {
            $n = (int) $r[$k];
            $out[$id][$k] += $n;
            if ($r['day'] === $today) $out[$id]['today'][$k] += $n;
            if ($r['day'] >= $week) $out[$id]['week'][$k] += $n;
        }
        if ($r['day'] >= events_now()->modify('-29 days')->format('Y-m-d')) {
            $out[$id]['days'][] = ['day' => $r['day'], 'views' => (int) $r['views'], 'clicks' => (int) $r['clicks'], 'wa_clicks' => (int) $r['wa_clicks']];
        }
    }
    foreach ($out as &$o) usort($o['days'], fn ($a, $b) => strcmp($a['day'], $b['day']));
    return $out;
}

/** The guests shown on the bar: name, role and photo only. */
function events_bar_guests(array $guests): array
{
    $out = [];
    foreach ($guests as $g) {
        if (is_array($g) && !empty($g['featured']) && (string) ($g['name'] ?? '') !== '') {
            $out[] = ['name' => (string) $g['name'], 'role' => (string) ($g['role'] ?? ''), 'photo' => (string) ($g['photo'] ?? '')];
        }
    }
    return array_slice($out, 0, EVENT_BAR_GUESTS);
}
