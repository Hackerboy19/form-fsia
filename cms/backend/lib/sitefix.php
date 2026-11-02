<?php
declare(strict_types=1);

/*
 * Auto fixes for Site Check. Each fix edits one file of the website:
 *   redirect    -> adds "RedirectMatch 301 ^/about/?$ /about.php" to .htaccess (page 404s, /about.php exists)
 *   canonical   -> adds <link rel="canonical"> after the page's <title>
 *   title       -> replaces the text inside the page's <title>
 *   description -> replaces the content of <meta name="description">
 * Only plain text in the file is changed: a title or description written by PHP
 * (<?php ... ?>) is left alone and the fix is not offered. Every changed file is
 * copied first to a backups folder outside the website, and Undo puts it back.
 */

const SF_BLOCK_BEGIN = '# BEGIN FSIA CMS redirects (Site Check)';
const SF_BLOCK_END = '# END FSIA CMS redirects';

function sf_backup_root(): string
{
    return rtrim((string) (cms_config()['fix_backup_dir'] ?? dirname(site_root()) . '/cms-fix-backups'), '/');
}

/** URL path of a page on the site ("/about.php"), or '' for other hosts. */
function sf_path(string $url): string
{
    $p = (string) parse_url($url, PHP_URL_PATH);
    return $p === '' ? '/' : rawurldecode($p);
}

/** The PHP file that serves this page, relative to the site root, or null. */
function sf_page_file(string $url): ?string
{
    $path = sf_path($url);
    if ($path === '/' || $path === '/index.php') {
        $rel = 'index.php';
    } elseif (preg_match('#^/([A-Za-z0-9_\-]+(?:/[A-Za-z0-9_\-]+)*\.php)$#', $path, $m)) {
        $rel = $m[1];
    } else {
        return null;
    }
    $file = site_root() . '/' . $rel;
    return is_file($file) && is_writable($file) ? $rel : null;
}

/** For a 404 like /about: the existing /about.php it should go to, or null. */
function sf_redirect_target(string $url): ?string
{
    $path = rtrim(sf_path($url), '/');
    if (!preg_match('#^/[A-Za-z0-9_\-]+(?:/[A-Za-z0-9_\-]+)*$#', $path)) {
        return null;
    }
    foreach (["$path.php", strtolower($path) . '.php'] as $cand) {
        if (is_file(site_root() . $cand)) {
            return $cand;
        }
    }
    return null;
}

/** [start, length, inner text] of the first <title>...</title>, if it is plain text. */
function sf_find_title(string $src): ?array
{
    if (!preg_match('#<title\b[^>]*>(.*?)</title>#is', $src, $m, PREG_OFFSET_CAPTURE)) return null;
    if (str_contains($m[1][0], '<?')) return null;
    return [$m[1][1], strlen($m[1][0]), $m[1][0]];
}

/** [start, length, value] of the content="" of the first <meta name="description">, if plain text. */
function sf_find_description(string $src): ?array
{
    if (!preg_match('#<meta\b[^>]*\bname\s*=\s*["\']description["\'][^>]*>#i', $src, $tag, PREG_OFFSET_CAPTURE)) return null;
    if (str_contains($tag[0][0], '<?')) return null;
    if (!preg_match('#\bcontent\s*=\s*(["\'])(.*?)\1#is', $tag[0][0], $c, PREG_OFFSET_CAPTURE)) return null;
    return [$tag[0][1] + $c[2][1], strlen($c[2][0]), $c[2][0]];
}

function sf_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
}

/** Title / description cut to Google's length at a word or " | " boundary. */
function sf_shorten(string $text, int $max): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if (mb_strlen($text) <= $max) return $text;
    foreach ([' | ', '. ', ', ', ' — ', ' - '] as $sep) {
        $parts = explode($sep, $text);
        $out = '';
        foreach ($parts as $p) {
            $next = $out === '' ? $p : $out . $sep . $p;
            if (mb_strlen($next) > $max) break;
            $out = $next;
        }
        if ($out !== '' && mb_strlen($out) >= $max * 0.6) return rtrim($out, ' .,|-—') . ($max > 100 ? '.' : '');
    }
    $cut = mb_substr($text, 0, $max);
    $sp = mb_strrpos($cut, ' ');
    $cut = $sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut;
    // Don't end on "&", "and", "of"...
    $cut = preg_replace('/(\s+(&|and|or|of|the|for|to|in|at|with|a|an))+$/iu', '', rtrim($cut, ' ,;:-|—&'));
    return rtrim($cut, ' ,;:-|—&') . ($max > 100 ? '.' : '');
}

/**
 * Which fixes are possible for a checked page: list of
 * {type, label, issue (text it fixes), value (suggested text), editable}.
 */
function sf_fixes_for(array $res): array
{
    $out = [];
    $url = $res['url'];
    $issues = array_column($res['issues'] ?? [], 'text');
    $has = fn (string $start) => (bool) array_filter($issues, fn ($t) => str_starts_with($t, $start));

    if ($res['status'] === 404 && ($to = sf_redirect_target($url))) {
        if (is_writable(site_root() . '/.htaccess') || (!is_file(site_root() . '/.htaccess') && is_writable(site_root()))) {
            $out[] = ['type' => 'redirect', 'label' => "Send visitors to $to (301 redirect)", 'issue' => 'Page not found', 'value' => $to, 'editable' => false, 'file' => '.htaccess'];
        }
    }
    if ($res['status'] !== 200 || !($rel = sf_page_file($url))) {
        return $out;
    }
    $src = (string) @file_get_contents(site_root() . '/' . $rel);
    $seo = $res['seo'] ?? [];
    if ($has('No canonical link') && sf_find_title($src)) {
        $out[] = ['type' => 'canonical', 'label' => 'Add canonical link', 'issue' => 'No canonical link', 'value' => sf_canonical_url($url), 'editable' => false];
    }
    if ($has('Title is long') && sf_find_title($src)) {
        $out[] = ['type' => 'title', 'label' => 'Shorten the title', 'issue' => 'Title is long', 'value' => sf_shorten((string) ($seo['title'] ?? ''), 60), 'editable' => true, 'max' => 70];
    }
    if (($has('Meta description is long') || $has('Meta description is short')) && sf_find_description($src)) {
        $d = (string) ($seo['description'] ?? '');
        $out[] = ['type' => 'description', 'label' => $has('Meta description is long') ? 'Shorten the description' : 'Write a longer description',
            'issue' => 'Meta description', 'value' => $has('Meta description is long') ? sf_shorten($d, 160) : $d, 'editable' => true, 'max' => 170];
    }
    foreach ($out as &$f) {
        $f['file'] = $f['type'] === 'redirect' ? '.htaccess' : $rel;
    }
    return $out;
}

function sf_canonical_url(string $url): string
{
    $path = sf_path($url);
    return 'https://www.fsia.in' . ($path === '/index.php' ? '/' : $path);
}

/** Copies a site file to the backups folder; returns the backup id. */
function sf_backup(string $rel): string
{
    $root = sf_backup_root();
    if (!is_dir($root) && !@mkdir($root, 0750, true)) {
        throw new ApiError("Cannot create the backups folder ($root). Nothing was changed.", 500);
    }
    $id = date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '-' . str_replace('/', '__', $rel === '.htaccess' ? 'htaccess' : $rel);
    $file = site_root() . '/' . $rel;
    $copy = is_file($file) ? @copy($file, "$root/$id") : @file_put_contents("$root/$id", '') !== false;
    if (!$copy) {
        throw new ApiError('Could not make a backup copy. Nothing was changed.', 500);
    }
    @file_put_contents("$root/$id.json", json_encode(['file' => $rel, 'existed' => is_file($file), 'at' => date('c')]));
    return $id;
}

function sf_write(string $rel, string $content): void
{
    $file = site_root() . '/' . $rel;
    $tmp = $file . '.cms-tmp';
    if (@file_put_contents($tmp, $content, LOCK_EX) === false || !@rename($tmp, $file)) {
        @unlink($tmp);
        throw new ApiError("Could not write $rel. Check the file permissions.", 500);
    }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
}

/** Applies one fix. Returns {message, backup, file}. */
function sf_apply(string $url, string $type, string $value): array
{
    if ($type === 'redirect') {
        $to = sf_redirect_target($url);
        if (!$to) throw new ApiError('No matching .php page for this address.', 422);
        $from = rtrim(sf_path($url), '/');
        $line = 'RedirectMatch 301 ^' . preg_quote($from, '#') . '/?$ ' . $to;
        $ht = site_root() . '/.htaccess';
        $src = is_file($ht) ? (string) file_get_contents($ht) : '';
        if (str_contains($src, $line)) {
            return ['message' => 'This redirect is already in .htaccess.', 'backup' => null, 'file' => '.htaccess'];
        }
        $backup = sf_backup('.htaccess');
        if (str_contains($src, SF_BLOCK_BEGIN) && str_contains($src, SF_BLOCK_END)) {
            $src = str_replace(SF_BLOCK_END, $line . "\n" . SF_BLOCK_END, $src);
        } else {
            $src = SF_BLOCK_BEGIN . "\n" . $line . "\n" . SF_BLOCK_END . "\n\n" . $src;
        }
        sf_write('.htaccess', $src);
        return ['message' => "Added a redirect: $from → $to", 'backup' => $backup, 'file' => '.htaccess'];
    }

    $rel = sf_page_file($url);
    if (!$rel) throw new ApiError('This page has no file the CMS can change.', 422);
    $src = (string) file_get_contents(site_root() . '/' . $rel);
    $value = trim(preg_replace('/\s+/u', ' ', $value));

    if ($type === 'canonical') {
        if (preg_match('#<link\b[^>]*rel\s*=\s*["\']canonical["\']#i', $src)) {
            return ['message' => 'The page already has a canonical link.', 'backup' => null, 'file' => $rel];
        }
        if (!preg_match('#</title>#i', $src, $m, PREG_OFFSET_CAPTURE)) throw new ApiError('No <title> found in the file.', 422);
        $at = $m[0][1] + strlen($m[0][0]);
        $backup = sf_backup($rel);
        $src = substr($src, 0, $at) . "\n    <link rel=\"canonical\" href=\"" . sf_html(sf_canonical_url($url)) . '">' . substr($src, $at);
        sf_write($rel, $src);
        return ['message' => "Canonical link added to $rel", 'backup' => $backup, 'file' => $rel];
    }

    if ($type === 'title' || $type === 'description') {
        $max = $type === 'title' ? 70 : 170;
        $min = $type === 'title' ? 10 : 50;
        $len = mb_strlen($value);
        if ($len < $min || $len > $max) throw new ApiError(ucfirst($type) . " must be {$min}–{$max} characters (now {$len}).", 422);
        $pos = $type === 'title' ? sf_find_title($src) : sf_find_description($src);
        if (!$pos) throw new ApiError("The $type in $rel is written by PHP, so it cannot be changed here.", 422);
        $backup = sf_backup($rel);
        $src = substr($src, 0, $pos[0]) . sf_html($value) . substr($src, $pos[0] + $pos[1]);
        sf_write($rel, $src);
        return ['message' => ucfirst($type) . " changed in $rel", 'backup' => $backup, 'file' => $rel];
    }

    throw new ApiError('Unknown fix', 422);
}

/** Puts a backed-up file back. */
function sf_undo(string $id): array
{
    if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}-[A-Za-z0-9_.\-]+$/', $id)) throw new ApiError('Unknown backup', 422);
    $root = sf_backup_root();
    $meta = json_decode((string) @file_get_contents("$root/$id.json"), true);
    if (!is_array($meta) || !is_file("$root/$id")) throw new ApiError('Backup not found', 404);
    $rel = (string) $meta['file'];
    if ($rel !== '.htaccess' && !preg_match('#^[A-Za-z0-9_\-]+(?:/[A-Za-z0-9_\-]+)*\.php$#', $rel)) throw new ApiError('Unknown backup', 422);
    sf_write($rel, (string) file_get_contents("$root/$id"));
    return ['message' => "$rel put back as it was", 'file' => $rel];
}
