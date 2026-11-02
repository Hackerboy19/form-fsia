<?php
declare(strict_types=1);

/*
 * "Edit here" for one profile in Site Check -> Profiles: reads the real values
 * from the registration table and saves the few fields the list checks
 * (biography, photos, banners, SEO). Only columns that already hold the data
 * on the live page are written, so the profile page shows the edit as is.
 * Old values go to fsia_cms_profile_fixes, so a save can be undone like an
 * auto fix.
 */

require_once __DIR__ . '/profilefix.php';
require_once __DIR__ . '/sanitize_html.php';

const PE_BIO_FIELDS = ['fullbio', 'shortbio', 'newbio', 'bio', 'comment'];
const PE_BANNERS = ['desktop' => ['nimage2', 'simage2', 'image2'], 'mobile' => ['nimage1', 'simage1', 'image1']];
const PE_SEO = ['meta_title', 'meta_desc', 'meta_keyword', 'h1'];

function pe_row(int $id): array
{
    $have = array_column(site_db_rows('SHOW COLUMNS FROM registration'), 'Field');
    $want = array_merge(sc_profile_columns()[0], PE_BIO_FIELDS, PE_SEO, ['nimage2', 'simage2', 'image2', 'nimage1', 'simage1', 'image1',
        'dbanner_alt', 'mbanner_alt', 'city', 'state', 'ptitle', 'ptitle1', 'ptitle2', 'reg_sub_cat']);
    $cols = array_values(array_unique(array_intersect($want, $have)));
    $r = site_db_rows('SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", $cols)) . ' FROM registration WHERE id = ?', [$id])[0] ?? null;
    if (!$r) throw new ApiError('Profile not found', 404);
    return $r;
}

/** Web address of an image as the database stores it ("uploads/x.jpg", a banner file name, or a full URL). */
function pe_img_url(string $v, string $base = ''): string
{
    $v = trim($v, " ,");
    if ($v === '') return '';
    if (preg_match('#^https?://#i', $v)) return $v;
    $site = rtrim((string) (cms_config()['site_url'] ?? 'https://www.fsia.in'), '/');
    $rel = ltrim(($base !== '' && !sc_file_ok($v) ? $base . '/' : '') . ltrim($v, '/'), '/');
    return $site . '/' . implode('/', array_map('rawurlencode', explode('/', $rel)));
}

/** Which column a banner is read from: the first one with a value, else the plain one (image2 / image1). */
function pe_banner_field(array $r, string $kind): string
{
    foreach (PE_BANNERS[$kind] as $k) {
        if (array_key_exists($k, $r) && trim((string) $r[$k], " ,") !== '') return $k;
    }
    $last = PE_BANNERS[$kind][2];
    return array_key_exists($last, $r) ? $last : PE_BANNERS[$kind][0];
}

/** Which column holds the biography the page shows: the first filled one, else "comment". */
function pe_bio_field(array $r): string
{
    foreach (PE_BIO_FIELDS as $k) {
        if (array_key_exists($k, $r) && !sc_blank($r[$k])) return $k;
    }
    return array_key_exists('comment', $r) ? 'comment' : (array_values(array_intersect(PE_BIO_FIELDS, array_keys($r)))[0] ?? 'comment');
}

function pe_photos(array $r): array
{
    $list = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($r['image'] ?? ''))), fn ($p) => $p !== '')));
    $main = trim((string) ($r['setdefault'] ?? ''), " ,");
    // The main photo (setdefault) first, as the edit box shows it.
    if ($main !== '') $list = [$main, ...array_values(array_filter($list, fn ($p) => $p !== $main))];
    return array_map(fn ($p) => ['path' => $p, 'url' => pe_img_url($p), 'ok' => sc_file_ok($p), 'main' => $p === $main], $list);
}

/** Everything the edit box shows, read from the database. */
function pe_get(int $id): array
{
    $r = pe_row($id);
    $bioField = pe_bio_field($r);
    $banners = [];
    foreach (['desktop', 'mobile'] as $kind) {
        $f = pe_banner_field($r, $kind);
        $v = trim((string) ($r[$f] ?? ''), " ,");
        $banners[$kind] = ['field' => $f, 'value' => $v, 'url' => pe_img_url($v, 'images'),
            'ok' => $v === '' ? null : (sc_file_ok($v, 'images') || sc_file_ok($v)),
            'alt' => (string) ($r[$kind === 'desktop' ? 'dbanner_alt' : 'mbanner_alt'] ?? '')];
    }
    $seo = [];
    foreach (PE_SEO as $k) {
        if (array_key_exists($k, $r)) $seo[$k] = (string) ($r[$k] ?? '');
    }
    $cat = sc_category_of((int) ($r['regtype'] ?? 0));
    [$city, $state] = pf_place($r);
    return [
        'id'       => (int) $r['id'],
        'name'     => pf_name($r),
        'category' => $cat === 'other' ? 'Other' : SC_CATEGORIES[$cat][0],
        'title'    => pf_label($r),
        'city'     => $city,
        'state'    => $state,
        'joined'   => substr((string) ($r['date_of_reg'] ?? ''), 0, 10),
        'link'     => trim((string) ($r['url'] ?? '')) !== '' ? 'https://www.fsia.in/' . ltrim(trim((string) $r['url']), '/') : '',
        'starindia' => ($r['starindia_action'] ?? '') === 'edited',
        'bio'      => ['field' => $bioField, 'value' => (string) ($r[$bioField] ?? '')],
        'photos'   => pe_photos($r),
        'banners'  => $banners,
        'seo'      => $seo,
        'suggest'  => pf_suggest($r, true),
    ];
}

/** Turns an image the CMS uploaded (/uploads/cms/...) into the path the profile columns use. */
function pe_uploaded_path(string $url): string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $base = (string) parse_url((string) (cms_config()['uploads']['base_url'] ?? '/uploads/cms'), PHP_URL_PATH);
    $base = '/' . trim($base, '/') . '/';
    if (!str_starts_with($path, $base) || str_contains($path, '..')) throw new ApiError('Upload the image here first.', 422);
    $rel = ltrim($path, '/');
    if (!is_file(site_root() . '/' . $rel)) throw new ApiError('The uploaded image was not found on the server.', 422);
    return $rel;
}

/** Banners are stored as a file name inside httpdocs/images: copy the uploaded file there. */
function pe_banner_file(int $id, string $kind, string $url): string
{
    $src = site_root() . '/' . pe_uploaded_path($url);
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    $name = "$id-$kind-banner-" . time() . ".$ext";
    $dir = site_root() . '/images';
    if (!is_dir($dir) || !@copy($src, "$dir/$name")) throw new ApiError('Could not put the banner in the images folder.', 500);
    @chmod("$dir/$name", 0644);
    return $name;
}

/**
 * Saves the changed fields. $in: bio, photos (list of paths in order, first is
 * the main photo), add_photos (uploaded URLs), desktop / mobile (uploaded URL,
 * or "" to keep; desktop_remove / mobile_remove to clear), meta_title, meta_desc, meta_keyword, h1.
 */
function pe_save(int $id, array $in): array
{
    $r = pe_row($id);
    if (($r['starindia_action'] ?? '') === 'edited') throw new ApiError('This profile is edited in the starindia admin. Change it there.', 409);
    $set = [];

    if (array_key_exists('bio', $in) && is_string($in['bio'])) {
        $f = pe_bio_field($r);
        $html = sanitize_article_html($in['bio']);
        if (sc_blank($html)) $html = '';
        if ($html !== (string) ($r[$f] ?? '')) $set[$f] = $html;
    }

    if (isset($in['photos']) && is_array($in['photos'])) {
        $known = array_column(pe_photos($r), 'path');
        $list = array_values(array_filter(array_map('strval', $in['photos']), fn ($p) => in_array($p, $known, true)));
        foreach ((array) ($in['add_photos'] ?? []) as $u) {
            if (is_string($u) && $u !== '') $list[] = pe_uploaded_path($u);
        }
        $list = array_values(array_unique($list));
        $image = $list ? implode(',', $list) : '';
        $main = $list[0] ?? '';
        if (array_key_exists('image', $r) && $image !== trim((string) $r['image'], " ,")) $set['image'] = $image;
        if (array_key_exists('setdefault', $r) && $main !== trim((string) $r['setdefault'], " ,")) $set['setdefault'] = $main;
    }

    foreach (['desktop', 'mobile'] as $kind) {
        if (!empty($in[$kind]) && is_string($in[$kind])) {
            $set[pe_banner_field($r, $kind)] = pe_banner_file($id, $kind, $in[$kind]);
        } elseif (!empty($in[$kind . '_remove'])) {
            $f = pe_banner_field($r, $kind);
            if (trim((string) ($r[$f] ?? '')) !== '') $set[$f] = '';
        }
    }

    foreach (PE_SEO as $k) {
        if (array_key_exists($k, $r) && isset($in[$k]) && is_string($in[$k])) {
            $v = trim(preg_replace('/\s+/u', ' ', strip_tags($in[$k])));
            if ($v !== trim((string) ($r[$k] ?? ''))) $set[$k] = $v;
        }
    }

    if (!$set) return ['batch' => null, 'fields' => 0];
    pf_table();
    $batch = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $who = pf_who();
    foreach ($set as $field => $value) {
        site_db_rows('INSERT INTO ' . PF_TABLE . ' (batch, profile_id, field, old_value, new_value, by_user, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$batch, $id, $field, (string) ($r[$field] ?? ''), $value, $who]);
    }
    site_db_rows('UPDATE registration SET ' . implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($set))) . ' WHERE id = ?',
        [...array_values($set), $id]);
    return ['batch' => $batch, 'fields' => count($set), 'changed' => array_keys($set)];
}
