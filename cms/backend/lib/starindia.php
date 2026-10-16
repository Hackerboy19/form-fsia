<?php
declare(strict_types=1);

/*
 * Reads profiles edited in the starindia admin (starindia.fsia.in/admin) from its
 * API, so Profiles & Score checks exactly what that admin shows and what
 * starindia.fsia.in/<slug> prints. GET only; nothing is written.
 *
 * Only the facts the checks need are kept (text lengths, the hero image value,
 * SEO fields, counts) - never contact details. They are cached for a few
 * minutes in the server's temp folder so the list stays quick.
 */

const STARINDIA_TTL = 300;         // seconds a fetched profile is reused
const STARINDIA_MAX_FETCH = 400;   // profiles fetched per request; the rest show "not checked yet"
const STARINDIA_PARALLEL = 8;

function starindia_api(): string
{
    return rtrim((string) (cms_config()['starindia_api'] ?? 'https://admin.fsia.in/api'), '/');
}

function starindia_cache_dir(): string
{
    $dir = sys_get_temp_dir() . '/fsia-starindia';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return $dir;
}

function starindia_plain(mixed $html): string
{
    $t = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $t));
}

/** The facts the checks use, from one API answer. */
function starindia_facts(array $p): array
{
    $d = is_array($p['data'] ?? null) ? $p['data'] : [];
    $seo = is_array($d['seo'] ?? null) ? $d['seo'] : [];
    $texts = [starindia_plain($p['bio'] ?? ''), starindia_plain($d['detailsBody'] ?? '')];
    foreach (['services' => 'desc', 'achievements' => 'description'] as $list => $key) {
        foreach ((array) ($d[$list] ?? []) as $item) {
            if (is_array($item)) $texts[] = starindia_plain($item[$key] ?? '');
        }
    }
    return [
        'slug'        => (string) ($p['slug'] ?? ''),
        'bio_len'     => max(array_map('mb_strlen', $texts)),
        'hero'        => trim((string) ($d['hero']['image'] ?? '')),
        'logo'        => trim((string) ($p['logo'] ?? '')),
        'seo_title'   => trim((string) ($seo['title'] ?? '')),
        'seo_desc'    => trim((string) ($seo['description'] ?? '')),
        'seo_keys'    => trim((string) ($seo['keywords'] ?? '')),
        'seo_h1'      => trim((string) ($seo['h1'] ?? '')),
        'noindex'     => !empty($seo['noindex']),
        'published'   => isset($p['published']) ? (int) $p['published'] === 1 : null,
        'gallery'     => count((array) ($d['gallery'] ?? [])),
        'updated_at'  => (string) ($p['updated_at'] ?? ''),
    ];
}

/**
 * id => ['ok' => true, 'facts' => [...]] or ['ok' => false, 'why' => '...'].
 * Ids beyond STARINDIA_MAX_FETCH that are not cached come back as not checked.
 */
function starindia_profiles(array $ids, bool $fresh = false): array
{
    $out = [];
    $todo = [];
    $dir = starindia_cache_dir();
    foreach (array_unique(array_map('intval', $ids)) as $id) {
        $file = "$dir/$id.json";
        if (!$fresh && is_file($file) && filemtime($file) > time() - STARINDIA_TTL) {
            $c = json_decode((string) file_get_contents($file), true);
            if (is_array($c)) { $out[$id] = $c; continue; }
        }
        $todo[] = $id;
    }
    $later = array_slice($todo, STARINDIA_MAX_FETCH);
    foreach ($later as $id) {
        $out[$id] = ['ok' => false, 'why' => 'Not checked yet: too many starindia profiles at once. Narrow the filters.'];
    }
    foreach (array_chunk(array_slice($todo, 0, STARINDIA_MAX_FETCH), STARINDIA_PARALLEL) as $chunk) {
        $mh = curl_multi_init();
        $hs = [];
        foreach ($chunk as $id) {
            $ch = curl_init(starindia_api() . '/profiles/' . $id);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'FSIA-CMS/1.0 (profile checks)',
            ]);
            curl_multi_add_handle($mh, $ch);
            $hs[$id] = $ch;
        }
        do {
            $st = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $st === CURLM_OK);
        foreach ($hs as $id => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $json = json_decode((string) curl_multi_getcontent($ch), true);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($code === 200 && is_array($json) && (int) ($json['id'] ?? 0) === $id) {
                $r = ['ok' => true, 'facts' => starindia_facts($json)];
                @file_put_contents("$dir/$id.json", json_encode($r), LOCK_EX);
            } else {
                // Not cached: try again on the next load.
                $r = ['ok' => false, 'why' => $code === 404 ? 'Not found in the starindia admin.' : "The starindia admin did not answer (HTTP $code)."];
            }
            $out[$id] = $r;
        }
        curl_multi_close($mh);
    }
    return $out;
}
