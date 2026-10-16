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
    created_at     DATETIME NOT NULL,
    updated_at     DATETIME NOT NULL,
    KEY idx_status (status, promo_start, promo_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

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

/** The one promotion for a page, or null. */
function events_promotion_for(string $path, ?DateTimeImmutable $now = null): ?array
{
    $path = events_norm_path($path);
    try {
        $rows = db()->query("SELECT * FROM cms_events WHERE status = 'published'")->fetchAll();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') return null;   // no events yet
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
    if (!$live) return null;
    usort($live, fn ($a, $b) => $b['priority'] <=> $a['priority'] ?: strcmp($a['starts_at'], $b['starts_at']) ?: $a['id'] <=> $b['id']);
    $e = $live[0];
    return [
        'id' => $e['id'], 'title' => $e['title'], 'summary' => $e['summary'],
        'starts_at' => $e['starts_at'], 'ends_at' => $e['ends_at'], 'venue' => $e['venue'], 'city' => $e['city'],
        'banner_desktop' => $e['banner_desktop'], 'banner_mobile' => $e['banner_mobile'],
        'cta_label' => $e['cta_label'], 'cta_url' => $e['cta_url'], 'version' => $e['updated_at'],
    ];
}
