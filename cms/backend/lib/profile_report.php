<?php
declare(strict_types=1);

/*
 * Profile checks shared by Site Check / Profiles & Score (sitecheck.php) and the
 * profile editor (profiles.php): what is missing, the quality score, categories.
 */

require_once __DIR__ . '/site_db.php';

const SC_CATEGORIES = [
    'miss-india'     => ['Miss India', [6]],
    'mrs-india'      => ['Mrs India', [7]],
    'miss-teen'      => ['Miss Teen India', [13]],
    'super-women'    => ['Super Women', [9, 2, 32, 40]],
    'super-heroes'   => ['Super Heroes', [8, 1]],
    'fashion'        => ['Fashion Designer', [10]],
    'makeup'         => ['Makeup Artist', [11]],
    'trainer'        => ['Pageant Trainer', [12]],
    'business'       => ['Business Award', [26]],
    'international'  => ['International Award', [14]],
    'miss-universe'  => ['Miss Universe', [30]],
    'mrs-universe'   => ['Mrs Universe', [31]],
];
const SC_PROFILE_LIMIT = 5000;
/* Columns some setups use for "last edited"; the first one present is shown as Last changed. */
const SC_UPDATED_COLUMNS = ['updated_at', 'updated_on', 'modified_at', 'modified_on', 'last_updated', 'last_update', 'update_date', 'edit_date', 'modify_date'];
const SC_PAGE_SIZE = 100;

function sc_category_of(int $regtype): string
{
    foreach (SC_CATEGORIES as $key => [, $types]) {
        if (in_array($regtype, $types, true)) return $key;
    }
    return 'other';
}

function sc_blank($v): bool
{
    return trim(strip_tags(html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8')), " \t\n\r\0\x0B\xC2\xA0,") === '';
}

/** Is an image path (as stored in the database) present on the server? */
function sc_file_ok(string $path, string $base = ''): bool
{
    $path = trim($path);
    if ($path === '') return false;
    if (preg_match('#^https?://(www\.)?fsia\.in/(.*)$#i', $path, $m)) {
        $path = rawurldecode($m[2]);
        $base = '';
    } elseif (preg_match('#^https?://#i', $path)) {
        return true; // hosted elsewhere: cannot check from here
    }
    $root = site_root();
    $rel = ltrim(($base !== '' ? $base . '/' : '') . ltrim($path, '/'), '/');
    return is_file($root . '/' . $rel);
}

/*
 * Profile quality score, 0-100, from the same checks as the problem filters.
 * Only facts read from the database and the server's files count; nothing is
 * estimated. Each part says what it got out of what it could get.
 *   Photos 30 · Biography 25 · Banners 20 · SEO 25
 */
const SC_SCORE_BANDS = ['excellent' => 85, 'good' => 65, 'fair' => 40, 'poor' => 0];

function sc_quality_score(array $x): array
{
    $photos = 0;
    if ($x['photos'] > 0) {
        $photos += 15;
        if ($x['missing'] === 0) $photos += 10;
        if ($x['photos'] >= 3 && $x['missing'] === 0) $photos += 5;
    }
    $bio = $x['bio_len'] > 0 ? 10 + ($x['bio_len'] >= 150 ? 7 : 0) + ($x['bio_len'] >= 500 ? 8 : 0) : 0;
    $banners = ($x['desktop_banner'] ? 10 : 0) + ($x['mobile_banner'] ? 10 : 0);
    $seo = ($x['url'] ? 5 : 0)
        + ($x['title_len'] === 0 ? 0 : ($x['title_len'] <= 70 ? 7 : 3))
        + ($x['desc_len'] === 0 ? 0 : ($x['desc_len'] <= 170 ? 8 : 4))
        + ($x['keywords'] ? 5 : 0);
    $parts = [
        ['key' => 'photos', 'label' => 'Photos', 'got' => $photos, 'max' => 30,
         'how' => 'Has a photo 15 · all photos on the server 10 · 3 or more photos 5'],
        ['key' => 'bio', 'label' => 'Biography', 'got' => $bio, 'max' => 25,
         'how' => 'Has a bio 10 · 150+ characters 7 · 500+ characters 8'],
        ['key' => 'banner', 'label' => 'Banners', 'got' => $banners, 'max' => 20,
         'how' => 'Desktop banner on the server 10 · mobile banner on the server 10'],
        ['key' => 'seo', 'label' => 'SEO', 'got' => $seo, 'max' => 25,
         'how' => 'Profile link 5 · meta title up to 70 characters 7 · meta description up to 170 characters 8 · keywords 5'],
    ];
    $score = $photos + $bio + $banners + $seo;
    $band = 'poor';
    foreach (SC_SCORE_BANDS as $name => $min) {
        if ($score >= $min) { $band = $name; break; }
    }
    return ['score' => $score, 'band' => $band, 'parts' => $parts];
}

function sc_profile_rows(array $f): array
{
    $want = ['id', 'first_name', 'last_name', 'regtype', 'status', 'verification', 'suspended', 'date_of_reg', 'url',
        'starindia_action', 'setdefault', 'image', 'nimage1', 'simage1', 'image1', 'nimage2', 'simage2', 'image2',
        'fullbio', 'fullbio_2', 'fullbio_3', 'shortbio', 'newbio', 'bio', 'comment', 'meta_title', 'meta_desc', 'meta_keyword', 'h1',
        ...SC_UPDATED_COLUMNS];
    $have = array_column(site_db_rows('SHOW COLUMNS FROM registration'), 'Field');
    $cols = array_values(array_intersect($want, $have));

    $where = ['1=1'];
    $params = [];
    if (($f['status'] ?? 'approved') === 'approved') {
        $where[] = '(status = 1 OR status = 3)';
        if (in_array('verification', $have, true)) $where[] = 'verification = 1';
        if (in_array('suspended', $have, true)) $where[] = 'suspended = 0';
    }
    if (!empty($f['category'])) {
        if ($f['category'] === 'other') {
            $all = array_merge(...array_map(fn ($c) => $c[1], array_values(SC_CATEGORIES)));
            $where[] = 'regtype NOT IN (' . implode(',', $all) . ')';
        } elseif (isset(SC_CATEGORIES[$f['category']])) {
            $where[] = 'regtype IN (' . implode(',', SC_CATEGORIES[$f['category']][1]) . ')';
        }
    }
    if (!empty($f['year'])) {
        $where[] = 'YEAR(date_of_reg) = ?';
        $params[] = (int) $f['year'];
    }
    if (!empty($f['month'])) {
        $where[] = 'MONTH(date_of_reg) = ?';
        $params[] = (int) $f['month'];
    }
    if (($f['q'] ?? '') !== '') {
        $where[] = "(CONCAT_WS(' ', first_name, last_name) LIKE ? OR url LIKE ? OR id = ?)";
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, (int) $f['q']);
    }
    $sql = 'SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", $cols)) . ' FROM registration WHERE ' . implode(' AND ', $where)
        . ' ORDER BY date_of_reg DESC, id DESC LIMIT ' . SC_PROFILE_LIMIT;
    return site_db_rows($sql, $params);
}

function sc_profile_report(array $r): array
{
    $g = fn ($k) => (string) ($r[$k] ?? '');
    $problems = [];

    // Bio: any of the biography fields the profile pages show.
    $bioFields = ['fullbio', 'fullbio_2', 'fullbio_3', 'shortbio', 'newbio', 'bio', 'comment'];
    $hasBio = false;
    $bioLen = 0;
    foreach ($bioFields as $k) {
        if (!sc_blank($g($k))) {
            $hasBio = true;
            $bioLen = max($bioLen, mb_strlen(trim(strip_tags(html_entity_decode($g($k), ENT_QUOTES | ENT_HTML5, 'UTF-8')))));
        }
    }
    if (!$hasBio) $problems['bio'][] = 'No biography';

    // Banner: desktop (nimage2/simage2/image2) and mobile (nimage1/simage1/image1).
    $bannerOk = [];
    foreach (['desktop' => ['nimage2', 'simage2', 'image2'], 'mobile' => ['nimage1', 'simage1', 'image1']] as $label => $keys) {
        $val = '';
        foreach ($keys as $k) {
            if (trim($g($k)) !== '') { $val = trim($g($k), " ,"); break; }
        }
        $bannerOk[$label] = false;
        if ($val === '') $problems['banner'][] = "No $label banner";
        elseif (!sc_file_ok($val, 'images') && !sc_file_ok($val)) $problems['banner'][] = "$label banner file missing on server";
        else $bannerOk[$label] = true;
    }

    // Photos: setdefault + image list, each checked on disk.
    $photos = array_values(array_unique(array_filter(array_map('trim', array_merge(explode(',', $g('setdefault')), explode(',', $g('image')))), fn ($p) => $p !== '')));
    $missing = array_values(array_filter($photos, fn ($p) => !sc_file_ok($p)));
    if (!$photos) $problems['image'][] = 'No photo';
    elseif ($missing) $problems['image'][] = count($missing) . ' of ' . count($photos) . ' photos missing on server';

    // SEO: empty fields fall back to auto-generated text on the page.
    $seo = [];
    if (trim($g('url')) === '') $seo[] = 'No profile link (url)';
    if (trim($g('meta_title')) === '') $seo[] = 'No meta title (auto)';
    if (trim($g('meta_desc')) === '') $seo[] = 'No meta description (auto)';
    if (trim($g('meta_keyword')) === '') $seo[] = 'No meta keywords (auto)';
    if (mb_strlen($g('meta_title')) > 70) $seo[] = 'Meta title too long (' . mb_strlen($g('meta_title')) . ')';
    if (mb_strlen($g('meta_desc')) > 170) $seo[] = 'Meta description too long (' . mb_strlen($g('meta_desc')) . ')';
    if ($seo) $problems['seo'] = $seo;

    $score = sc_quality_score([
        'photos' => count($photos), 'missing' => count($missing), 'bio_len' => $hasBio ? max($bioLen, 1) : 0,
        'desktop_banner' => $bannerOk['desktop'], 'mobile_banner' => $bannerOk['mobile'],
        'url' => trim($g('url')) !== '', 'title_len' => mb_strlen(trim($g('meta_title'))),
        'desc_len' => mb_strlen(trim($g('meta_desc'))), 'keywords' => trim($g('meta_keyword')) !== '',
    ]);

    $native = '';
    foreach (SC_UPDATED_COLUMNS as $k) {
        if (!empty($r[$k]) && !str_starts_with((string) $r[$k], '0000')) { $native = substr((string) $r[$k], 0, 19); break; }
    }

    $slug = ltrim(trim($g('url')), '/');
    $edited = $g('starindia_action') === 'edited';
    $cat = sc_category_of((int) $g('regtype'));
    return [
        'id'       => (int) $g('id'),
        'name'     => trim($g('first_name') . ' ' . $g('last_name')) ?: '(no name)',
        'category' => $cat === 'other' ? 'Other (' . (int) $g('regtype') . ')' : SC_CATEGORIES[$cat][0],
        'date'     => substr($g('date_of_reg'), 0, 10),
        'status'   => (int) $g('status'),
        'path'     => $slug !== '' && !$edited ? '/' . $slug : '',
        'link'     => $slug !== '' ? ($edited ? 'https://starindia.fsia.in/' : 'https://www.fsia.in/') . $slug : '',
        'photos'   => count($photos),
        'missing_photos' => array_slice($missing, 0, 10),
        'problems' => $problems,
        'score'    => $score['score'],
        'band'     => $score['band'],
        'score_parts' => $score['parts'],
        'last_changed' => $native ?: null,
        'changed_by' => null,
    ];
}
