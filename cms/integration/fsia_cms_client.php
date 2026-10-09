<?php
/**
 * FSIA CMS client for the public PHP pages (index.php, about.php ...).
 *
 * Fetches SEO + sections from the CMS API with:
 *  - a short timeout (the page never hangs on the API),
 *  - a file cache (one API call per page per CACHE_TTL, not per visitor),
 *  - stale-on-error: if the API is down, the last good copy is used, and if
 *    there is none the caller's fallback values are used.
 *
 * Put this file next to index.php and set FSIA_CMS_API below.
 */

if (!defined('FSIA_CMS_API')) {
    define('FSIA_CMS_API', 'https://www.fsia.in/cms/backend/api');
}
if (!defined('FSIA_CMS_CACHE_DIR')) {
    define('FSIA_CMS_CACHE_DIR', sys_get_temp_dir() . '/fsia-cms-cache');
}
if (!defined('FSIA_CMS_CACHE_TTL')) {
    define('FSIA_CMS_CACHE_TTL', 300); // seconds; edits in the admin show up within 5 minutes
}

/** GET an API endpoint and return the decoded JSON, or null. */
function fsia_cms_get(string $endpoint, array $query = []): ?array
{
    $url = rtrim(FSIA_CMS_API, '/') . '/' . $endpoint . ($query ? '?' . http_build_query($query) : '');
    $cacheFile = FSIA_CMS_CACHE_DIR . '/' . sha1($url) . '.json';

    if (is_file($cacheFile) && filemtime($cacheFile) > time() - FSIA_CMS_CACHE_TTL) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $body = null;
    $status = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        // Fallback when the cURL extension is missing.
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"]]);
        $body = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
    }

    $data = ($status === 200 && is_string($body)) ? json_decode($body, true) : null;
    if (is_array($data)) {
        if (!is_dir(FSIA_CMS_CACHE_DIR)) {
            @mkdir(FSIA_CMS_CACHE_DIR, 0775, true);
        }
        @file_put_contents($cacheFile, json_encode($data), LOCK_EX);
        return $data;
    }

    // API unreachable or erroring: serve the last good copy, however old.
    if (is_file($cacheFile)) {
        $stale = json_decode((string) file_get_contents($cacheFile), true);
        return is_array($stale) ? $stale : null;
    }
    return null;
}

/** SEO row for a page, with each empty field filled from $fallback. */
function fsia_cms_seo(string $page, array $fallback = []): array
{
    $row = fsia_cms_get('seo.php', ['page' => $page]) ?? [];
    $out = [];
    foreach (['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image_url', 'canonical_url'] as $k) {
        $v = isset($row[$k]) ? trim((string) $row[$k]) : '';
        $out[$k] = $v !== '' ? $v : (string) ($fallback[$k] ?? '');
    }
    // Sharing tags fall back to the search tags.
    $out['og_title'] = $out['og_title'] !== '' ? $out['og_title'] : $out['meta_title'];
    $out['og_description'] = $out['og_description'] !== '' ? $out['og_description'] : $out['meta_description'];
    return $out;
}

/** All sections of a page: ['hero' => ['content' => [...], 'image_url' => '...'], ...]. */
function fsia_cms_sections(string $page): array
{
    return fsia_cms_get('sections.php', ['page' => $page]) ?? [];
}

/** One text field of a section, or $default. Use with fsia_e() when printing. */
function fsia_cms_text(array $sections, string $section, string $field, string $default = ''): string
{
    $v = $sections[$section]['content'][$field] ?? '';
    return is_string($v) && trim($v) !== '' ? $v : $default;
}

/** A section's image URL, or $default. */
function fsia_cms_image(array $sections, string $section, string $default = ''): string
{
    $v = $sections[$section]['image_url'] ?? '';
    return is_string($v) && $v !== '' ? $v : $default;
}

/** Escape for HTML text and attributes. */
if (!function_exists('fsia_e')) {
    function fsia_e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Absolute URL for og:image etc. (social sites ignore relative URLs). */
function fsia_cms_abs(string $url, string $site = 'https://www.fsia.in'): string
{
    return ($url !== '' && $url[0] === '/' && !str_starts_with($url, '//')) ? rtrim($site, '/') . $url : $url;
}
