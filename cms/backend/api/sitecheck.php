<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/site_db.php';
require_once __DIR__ . '/../lib/profile_report.php';
require_once __DIR__ . '/../lib/starindia.php';

/*
 * Site Check (admin only): opens pages of the live site the way a visitor does
 * and reports what is broken (status, images) and the SEO tags, compared with a
 * saved "baseline" so changed titles/descriptions show up.
 *
 * GET  /api/sitecheck.php?action=urls[&q=text]  -> main pages + profile URLs from the root .htaccess
 * GET  /api/sitecheck.php?action=check&url=/x   -> check one page
 * GET  /api/sitecheck.php?action=profiles&category=&year=&month=&status=&problem=bio,banner,image,seo&page=
 *                                               -> profiles from the database with missing bio/banner/photos/SEO
 * POST /api/sitecheck.php?action=baseline       -> body {pages:[{url,title,description,keywords,canonical,h1}]}
 */

const SC_MAIN_PAGES = [
    '/', '/about', '/about.php', '/contact', '/contact.php', '/our-teams.php', '/quickapply',
    '/news-coverage', '/special-news-coverage', '/FAQ.php',
];
const SC_MAX_IMAGES = 60;

function sc_site(): string
{
    return rtrim((string) (cms_config()['site_url'] ?? 'https://www.fsia.in'), '/');
}

/** Accepts "/path" or a full fsia.in URL; returns an absolute URL on the site, or throws. */
function sc_url(?string $in): string
{
    $in = trim((string) $in);
    if ($in === '' || strlen($in) > 600) {
        throw new ApiError('Give a page address, like /about or https://www.fsia.in/about', 422);
    }
    if ($in[0] === '/' && !str_starts_with($in, '//')) {
        return sc_site() . $in;
    }
    $host = strtolower((string) parse_url($in, PHP_URL_HOST));
    $scheme = strtolower((string) parse_url($in, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true) || !in_array($host, ['fsia.in', 'www.fsia.in'], true)) {
        throw new ApiError('Only pages of fsia.in can be checked', 422);
    }
    return $in;
}

/** Spaces and other unsafe characters in old image names ("12712Shradha IMG.jpg") made safe for curl. */
function sc_encode(string $url): string
{
    return preg_replace_callback('/[^A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]/u', fn ($m) => rawurlencode($m[0]), $url);
}

function sc_curl(string $url, bool $range)
{
    $ch = curl_init(sc_encode($url));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $range,
        CURLOPT_MAXREDIRS      => 4,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => $range ? 20 : 40,
        CURLOPT_USERAGENT      => 'FSIA-SiteCheck/1.0 (+admin)',
        CURLOPT_ENCODING       => '',
        CURLOPT_HTTPHEADER     => $range ? ['Range: bytes=0-2047'] : ['Accept: text/html'],
    ]);
    return $ch;
}

/** Fetches a page once, and once more if the server answered 5xx or not at all. */
function sc_fetch_page(string $url): array
{
    $tries = [];
    for ($i = 0; $i < 2; $i++) {
        $ch = sc_curl($url, false);
        $body = curl_exec($ch);
        $r = [
            'status'   => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'time'     => round((float) curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2),
            'redirect' => (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL),
            'error'    => curl_error($ch),
            'body'     => is_string($body) ? $body : '',
        ];
        curl_close($ch);
        $tries[] = $r['status'];
        if ($r['status'] > 0 && $r['status'] < 500) {
            break;
        }
        usleep(800000);
    }
    $r['tries'] = $tries;
    return $r;
}

/** Checks image URLs in parallel (6 at a time). Returns [url => [status, type, ok]]. */
function sc_check_images(array $urls): array
{
    $out = [];
    $pending = $urls;
    for ($round = 0; $round < 2 && $pending; $round++) {
        $mh = curl_multi_init();
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, 6);
        $handles = [];
        foreach ($pending as $u) {
            $handles[$u] = sc_curl($u, true);
            curl_multi_add_handle($mh, $handles[$u]);
        }
        do {
            $st = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $st === CURLM_OK);

        $retry = [];
        foreach ($handles as $u => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $type = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            $ok = ($code === 200 || $code === 206) && (str_starts_with($type, 'image/') || $type === 'application/octet-stream');
            $prev = $out[$u] ?? null;
            $out[$u] = ['status' => $code, 'type' => $type, 'ok' => $ok, 'flaky' => $prev !== null && $ok];
            if (!$ok && ($code === 0 || $code >= 500) && $round === 0) {
                $retry[] = $u;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        $pending = $retry;
    }
    return $out;
}

function sc_abs(string $src, string $pageUrl): string
{
    $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($src === '' || str_starts_with($src, 'data:')) {
        return $src;
    }
    if (str_starts_with($src, '//')) {
        return 'https:' . $src;
    }
    if (preg_match('#^https?://#i', $src)) {
        return $src;
    }
    $p = parse_url($pageUrl);
    $base = $p['scheme'] . '://' . $p['host'];
    if ($src[0] === '/') {
        return $base . $src;
    }
    $dir = preg_replace('#/[^/]*$#', '/', $p['path'] ?? '/');
    return $base . $dir . $src;
}

function sc_check(string $url): array
{
    $page = sc_fetch_page($url);
    $issues = [];
    $add = function (string $level, string $text) use (&$issues) { $issues[] = ['level' => $level, 'text' => $text]; };

    $res = [
        'url' => $url, 'status' => $page['status'], 'time' => $page['time'], 'tries' => $page['tries'],
        'redirect' => $page['redirect'], 'seo' => null, 'images' => ['total' => 0, 'broken' => [], 'checked' => 0],
    ];

    if ($page['status'] === 0) {
        $add('error', 'Page did not open (' . ($page['error'] ?: 'no answer from the server') . ').');
    } elseif ($page['status'] >= 500) {
        $add('error', "Server error {$page['status']}.");
    } elseif ($page['status'] === 404) {
        $add('error', 'Page not found (404).');
    } elseif ($page['status'] >= 300 && $page['status'] < 400) {
        $add('info', "Redirects ({$page['status']}) to {$page['redirect']}");
    } elseif ($page['status'] >= 400) {
        $add('error', "Error {$page['status']}.");
    }
    if (count($page['tries']) > 1 && $page['status'] > 0 && $page['status'] < 500) {
        $add('warn', 'First try failed (' . $page['tries'][0] . '), worked on the second try: the server is overloaded or unstable.');
    }
    if ($page['status'] === 200 && $page['time'] > 5) {
        $add('warn', "Slow: took {$page['time']} s to load.");
    }

    if ($page['status'] !== 200 || $page['body'] === '') {
        $res['issues'] = $issues;
        return $res;
    }

    $html = $page['body'];
    if (preg_match('#<b>(Warning|Notice|Fatal error|Deprecated|Parse error)</b>:\s*([^<]{0,160})#i', $html, $m)
        || preg_match('#\b(Warning|Fatal error|Deprecated|Notice): .{0,40}(Undefined|in /)[^<\n]{0,120}#', $html, $m)) {
        $add('error', 'PHP message visible on the page: ' . trim(strip_tags($m[0])));
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $one = function (string $q) use ($xp): string {
        $n = $xp->query($q);
        return $n && $n->length ? trim(preg_replace('/\s+/u', ' ', $n->item(0)->nodeValue ?? '')) : '';
    };
    $meta = fn (string $name) => $one("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='$name']/@content");
    $prop = fn (string $name) => $one("//meta[@property='$name']/@content");

    $h1 = [];
    foreach ($xp->query('//h1') as $n) {
        $t = trim(preg_replace('/\s+/u', ' ', $n->textContent));
        if ($t !== '') {
            $h1[] = mb_substr($t, 0, 120);
        }
    }
    $seo = [
        'title'       => $one('//title'),
        'description' => $meta('description'),
        'keywords'    => $meta('keywords'),
        'robots'      => $meta('robots'),
        'canonical'   => $one("//link[@rel='canonical']/@href"),
        'og_title'    => $prop('og:title'),
        'og_image'    => $prop('og:image'),
        'h1'          => $h1,
        'words'       => str_word_count(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', ' ', $html))),
    ];
    $res['seo'] = $seo;

    $tl = mb_strlen($seo['title']);
    $dl = mb_strlen($seo['description']);
    if ($tl === 0) $add('error', 'No title.');
    elseif ($tl > 70) $add('warn', "Title is long ($tl characters, Google shows about 60).");
    if ($dl === 0) $add('error', 'No meta description.');
    elseif ($dl < 50) $add('warn', "Meta description is short ($dl characters).");
    elseif ($dl > 170) $add('warn', "Meta description is long ($dl characters, Google shows about 160).");
    if ($seo['keywords'] === '') $add('info', 'No meta keywords.');
    if (stripos($seo['robots'], 'noindex') !== false) $add('error', 'Page is set to noindex: Google will not show it.');
    if ($seo['canonical'] === '') $add('warn', 'No canonical link.');
    elseif (!preg_match('#^https://www\.fsia\.in/#i', $seo['canonical'])) $add('warn', "Canonical points elsewhere: {$seo['canonical']}");
    if (!$h1) $add('warn', 'No H1 heading.');
    if ($seo['og_image'] === '') $add('info', 'No share image (og:image).');
    if ($seo['words'] < 80) $add('warn', "Very little text on the page ({$seo['words']} words): the content may not have loaded.");

    // Images: <img src>, lazy-load attributes, and the share image.
    $srcs = [];
    $emptySrc = 0;
    $noAlt = 0;
    foreach ($xp->query('//img') as $img) {
        $s = $img->getAttribute('src');
        foreach (['data-src', 'data-lazy-src', 'data-original'] as $a) {
            if ($img->getAttribute($a) !== '') {
                $s = $img->getAttribute($a);
            }
        }
        if (trim($s) === '') {
            $emptySrc++;
            continue;
        }
        if (!$img->hasAttribute('alt')) {
            $noAlt++;
        }
        $abs = sc_abs($s, $url);
        if ($abs !== '' && !str_starts_with($abs, 'data:')) {
            $srcs[$abs] = true;
        }
    }
    if ($seo['og_image'] !== '') {
        $srcs[sc_abs($seo['og_image'], $url)] = true;
    }
    $all = array_keys($srcs);
    $res['images']['total'] = count($all);
    $toCheck = array_slice($all, 0, SC_MAX_IMAGES);
    $checked = $toCheck ? sc_check_images($toCheck) : [];
    $res['images']['checked'] = count($checked);
    $flaky = 0;
    foreach ($checked as $u => $c) {
        if (!$c['ok']) {
            $res['images']['broken'][] = ['url' => $u, 'status' => $c['status'], 'type' => $c['type']];
        } elseif ($c['flaky']) {
            $flaky++;
        }
    }
    $nb = count($res['images']['broken']);
    if ($nb) {
        $missing = count(array_filter($res['images']['broken'], fn ($b) => $b['status'] === 404));
        $add('error', "$nb broken image" . ($nb > 1 ? 's' : '') . ($missing ? " ($missing missing from the server, 404)" : '') . '.');
    }
    if ($flaky) $add('warn', "$flaky image" . ($flaky > 1 ? 's' : '') . ' failed first and loaded on retry: the server is overloaded.');
    if ($emptySrc) $add('warn', "$emptySrc image tag" . ($emptySrc > 1 ? 's have' : ' has') . ' no image address.');
    if ($noAlt) $add('info', "$noAlt image" . ($noAlt > 1 ? 's have' : ' has') . ' no alt text.');
    if (count($all) > SC_MAX_IMAGES) $add('info', 'Only the first ' . SC_MAX_IMAGES . ' of ' . count($all) . ' images were checked.');

    // Compare with the saved baseline.
    $base = sc_baseline_get($url);
    $res['baseline'] = $base ? ['saved_at' => $base['saved_at']] : null;
    if ($base) {
        $labels = ['title' => 'Title', 'description' => 'Description', 'keywords' => 'Keywords', 'canonical' => 'Canonical'];
        foreach ($labels as $k => $label) {
            if ((string) $base[$k] !== $seo[$k]) {
                $add('change', "$label changed since {$base['saved_at']}. Before: \"" . mb_substr((string) $base[$k], 0, 160) . '"');
            }
        }
        if ((string) $base['h1'] !== implode(' | ', $h1)) {
            $add('change', "H1 changed since {$base['saved_at']}. Before: \"" . mb_substr((string) $base['h1'], 0, 160) . '"');
        }
    }

    $res['issues'] = $issues;
    return $res;
}

// --- Baseline (saved "correct" SEO per page) ---------------------------------

function sc_baseline_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec('CREATE TABLE IF NOT EXISTS site_check_baseline (
        url VARCHAR(255) NOT NULL PRIMARY KEY,
        title TEXT NOT NULL, description TEXT NOT NULL, keywords TEXT NOT NULL,
        canonical VARCHAR(600) NOT NULL, h1 TEXT NOT NULL,
        saved_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $done = true;
}

function sc_key(string $url): string
{
    return mb_substr(preg_replace('#^https?://(www\.)?fsia\.in#i', '', $url) ?: '/', 0, 255);
}

function sc_baseline_get(string $url): ?array
{
    try {
        sc_baseline_table();
        $st = db()->prepare('SELECT * FROM site_check_baseline WHERE url = ?');
        $st->execute([sc_key($url)]);
        return $st->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('[fsia-cms] sitecheck baseline: ' . $e->getMessage());
        return null;
    }
}

// --- Profile URLs from the root .htaccess -----------------------------------

function sc_profile_urls(): array
{
    $file = site_root() . '/.htaccess';
    if (!is_file($file)) {
        return [];
    }
    $urls = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (!preg_match('/^\s*rewriterule\s+"?\^([^"\s$]+)\$"?\s+"?(verified_user_details[^\s"]*)/i', $line, $m)) {
            continue;
        }
        $slug = str_replace('\\.', '.', $m[1]);
        if (preg_match('/[\\\\()\[\]*+?|{}]/', $slug)) {
            continue; // a pattern, not a plain address
        }
        $urls['/' . $slug] = true;
    }
    return array_keys($urls);
}

// --- Profile data (from the site's registration table) -----------------------

/* Category filter -> regtype values, labels as the site shows them. */
respond(function () {
    require_admin();
    $action = query_param('action') ?? '';

    if (request_method() === 'GET' && $action === 'urls') {
        $profiles = sc_profile_urls();
        $q = mb_strtolower(trim((string) query_param('q')));
        $matches = $q === '' ? [] : array_values(array_filter($profiles, fn ($u) => str_contains(mb_strtolower(rawurldecode($u)), $q)));
        return [
            'main'           => SC_MAIN_PAGES,
            'profiles_total' => count($profiles),
            'profiles'       => $profiles,
            'matches'        => array_slice($matches, 0, 200),
        ];
    }

    if (request_method() === 'GET' && $action === 'profiles') {
        @set_time_limit(120);
        // A year or month that is not a real one is ignored, like an unknown category.
        $year = (int) query_param('year');
        $month = (int) query_param('month');
        $f = [
            'category' => (string) query_param('category'),
            'year'     => $year >= 1990 && $year <= 2100 ? (string) $year : '',
            'month'    => $month >= 1 && $month <= 12 ? (string) $month : '',
            'status'   => query_param('status') === 'all' ? 'all' : 'approved',
            'verification' => in_array(query_param('verification'), ['verified', 'pending'], true) ? query_param('verification') : '',
            'q'        => mb_substr(trim((string) query_param('q')), 0, 80),
        ];
        $want = array_values(array_intersect(explode(',', (string) query_param('problem')), ['bio', 'banner', 'image', 'seo']));
        $match = query_param('match') === 'all' ? 'all' : 'any';
        $bandWant = array_values(array_intersect(explode(',', (string) query_param('band')), [...array_keys(SC_SCORE_BANDS), 'unchecked']));

        $rows = sc_profile_rows($f);
        // Profiles edited in the starindia admin are checked from that admin's data.
        $siIds = array_map(fn ($r) => (int) $r['id'], array_filter($rows, fn ($r) => ($r['starindia_action'] ?? '') === 'edited'));
        $si = $siIds ? starindia_profiles($siIds, query_param('fresh') === '1') : [];
        // Latest change of each profile from the change log (Tools -> Profile Changes), when it is on.
        $latest = [];
        if (site_db_has_table('fsia_profile_changes')) {
            foreach (site_db_rows('SELECT c.profile_id, c.changed_at, c.actor, c.db_user, c.action FROM fsia_profile_changes c
                    JOIN (SELECT profile_id, MAX(id) AS mid FROM fsia_profile_changes GROUP BY profile_id) m ON m.mid = c.id') as $c) {
                $latest[(int) $c['profile_id']] = $c;
            }
        }
        $changedDays = (int) query_param('changed_days');
        $since = $changedDays > 0 ? date('Y-m-d H:i:s', time() - $changedDays * 86400) : null;
        $counts = ['bio' => 0, 'banner' => 0, 'image' => 0, 'seo' => 0, 'ok' => 0];
        $bands = array_fill_keys([...array_keys(SC_SCORE_BANDS), 'unchecked'], 0);
        $scoreSum = 0;
        $scored = 0;
        $out = [];
        foreach ($rows as $r) {
            $rep = sc_profile_report($r, $si[(int) $r['id']] ?? null);
            if (isset($latest[$rep['id']]) && (string) $latest[$rep['id']]['changed_at'] >= (string) $rep['last_changed']) {
                $rep['last_changed'] = (string) $latest[$rep['id']]['changed_at'];
                $rep['changed_by'] = site_change_who($latest[$rep['id']]['actor'], $latest[$rep['id']]['db_user']);
            }
            if ($since !== null && ($rep['last_changed'] === null || $rep['last_changed'] < $since)) continue;
            foreach (array_keys($rep['problems']) as $k) $counts[$k]++;
            if (!$rep['problems'] && $rep['unchecked'] === null) $counts['ok']++;
            $bands[$rep['band']]++;
            if ($rep['score'] !== null) {
                $scoreSum += $rep['score'];
                $scored++;
            }
            if ($bandWant && !in_array($rep['band'], $bandWant, true)) continue;
            if ($want) {
                $hit = array_intersect($want, array_keys($rep['problems']));
                if ($match === 'all' ? count($hit) !== count($want) : !$hit) continue;
            }
            $out[] = $rep;
        }
        $sort = query_param('sort');
        if ($sort === 'changed') {
            usort($out, fn ($a, $b) => strcmp((string) $b['last_changed'], (string) $a['last_changed']));
        } elseif ($sort === 'score') {
            usort($out, fn ($a, $b) => ($a['score'] ?? 101) <=> ($b['score'] ?? 101) ?: $b['id'] <=> $a['id']);
        } elseif ($sort === 'score_desc') {
            usort($out, fn ($a, $b) => ($b['score'] ?? -1) <=> ($a['score'] ?? -1) ?: $b['id'] <=> $a['id']);
        }
        $page = max(1, (int) query_param('page'));
        $years = array_map('intval', array_column(site_db_rows('SELECT DISTINCT YEAR(date_of_reg) AS y FROM registration WHERE date_of_reg IS NOT NULL ORDER BY y DESC'), 'y'));
        return [
            'change_log' => (bool) $latest || site_db_has_table('fsia_profile_changes'),
            'scanned'    => count($rows),
            'capped'     => count($rows) >= SC_PROFILE_LIMIT,
            'counts'     => $counts,
            'bands'      => $bands,
            'verification_counts' => sc_verification_counts($f),
            'starindia'  => ['profiles' => count($siIds), 'checked' => count(array_filter($si, fn ($x) => $x['ok'])), 'admin' => 'https://starindia.fsia.in/admin/'],
            'band_min'   => SC_SCORE_BANDS,
            'avg_score'  => $scored ? (int) round($scoreSum / $scored) : null,
            'total'      => count($out),
            'page'       => $page,
            'page_size'  => SC_PAGE_SIZE,
            'rows'       => query_param('all') === '1' ? $out : array_slice($out, ($page - 1) * SC_PAGE_SIZE, SC_PAGE_SIZE),
            'paths'      => array_values(array_filter(array_column($out, 'path'))),
            'years'      => array_values(array_filter($years, fn ($y) => $y > 2000)),
            'categories' => [...array_map(fn ($k, $c) => ['key' => $k, 'label' => $c[0]], array_keys(SC_CATEGORIES), SC_CATEGORIES), ['key' => 'other', 'label' => 'Other']],
        ];
    }

    if (request_method() === 'GET' && $action === 'check') {
        @set_time_limit(120);
        return sc_check(sc_url(query_param('url')));
    }

    if (request_method() === 'POST' && $action === 'baseline') {
        $pages = json_body()['pages'] ?? null;
        if (!is_array($pages) || !$pages) {
            throw new ApiError('Nothing to save', 422);
        }
        sc_baseline_table();
        $st = db()->prepare('INSERT INTO site_check_baseline (url, title, description, keywords, canonical, h1, saved_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), keywords = VALUES(keywords),
            canonical = VALUES(canonical), h1 = VALUES(h1), saved_at = NOW()');
        $n = 0;
        foreach (array_slice($pages, 0, 2000) as $p) {
            if (!is_array($p) || !isset($p['url'])) continue;
            $url = sc_url((string) $p['url']);
            $txt = fn ($k, $max) => mb_substr(is_string($p[$k] ?? null) ? $p[$k] : '', 0, $max);
            $h1 = is_array($p['h1'] ?? null) ? implode(' | ', array_map('strval', $p['h1'])) : $txt('h1', 2000);
            $st->execute([sc_key($url), $txt('title', 2000), $txt('description', 4000), $txt('keywords', 4000), $txt('canonical', 600), mb_substr($h1, 0, 2000)]);
            $n++;
        }
        return ['saved' => $n];
    }

    throw new ApiError('Unknown action', 400);
});
