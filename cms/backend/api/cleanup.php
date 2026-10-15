<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/site_db.php';

/*
 * Server Cleanup (admin only). Finds files the website does not need and moves
 * the chosen ones to a Trash folder OUTSIDE the website, where they can be put
 * back. Nothing is deleted straight away; Trash can be emptied after 7 days.
 * The database is never changed.
 *
 * GET  /api/cleanup.php?action=scan                 -> groups of unwanted files
 * GET  /api/cleanup.php?action=preview&path=x       -> what the file holds (text, image, zip list)
 * POST /api/cleanup.php?action=trash   {paths:[]}   -> move files to Trash
 * GET  /api/cleanup.php?action=trash_list           -> what is in Trash
 * POST /api/cleanup.php?action=restore {batch, paths?}
 * POST /api/cleanup.php?action=purge   {batch}      -> delete for good (7+ days old)
 */

const CL_KEEP_DAYS = 7;          // Trash can be emptied after this many days
const CL_ORPHAN_MIN_AGE = 30;    // photos younger than this are never called unused
const CL_LARGE_BYTES = 10485760; // 10 MB
const CL_MAX_ITEMS = 3000;       // per group in one answer
const CL_MAX_FILES = 400000;     // stop walking after this many files

/* Folders the scan never enters: the CMS itself, code libraries, system folders. */
const CL_SKIP_DIRS = ['cms', 'cms-admin', 'cms-admin-test', 'vendor', 'node_modules', '.git', '.well-known', 'plesk-stat', 'cgi-bin', 'error_docs'];
/* Files that are never touched, whatever their name looks like. */
const CL_PROTECTED = ['.htaccess', '.user.ini', 'php.ini', 'web.config', 'config.php', 'robots.txt', 'index.php', 'index.html', 'favicon.ico'];

const CL_GROUPS = [
    'backup'  => 'Backup and copy files',
    'archive' => 'Zip files and database dumps',
    'log'     => 'Log files',
    'orphan'  => 'Unused profile photos',
    'large'   => 'Very large files',
];

function cl_trash_root(): string
{
    $dir = (string) (cms_config()['trash_dir'] ?? dirname(site_root()) . '/cms-trash');
    return rtrim($dir, '/');
}

/** Which group a file belongs to by its name, or '' when it is a normal site file. */
function cl_group_by_name(string $rel): string
{
    $name = basename($rel);
    if (in_array(strtolower($name), CL_PROTECTED, true)) return '';
    if (preg_match('/\.(log)$|^error_log$|^php_errors?\.log/i', $name)) return 'log';
    if (preg_match('/\.(zip|tar|tgz|gz|rar|7z|sql|bz2)$/i', $name)) return 'archive';
    if (preg_match('/\.(bak|old|orig|swp|tmp|save|backup)$|\.bak[_.-][\w.-]*$|\.php\.[\w-]+$|\.(html?|js|css)\.(bak|old)[\w-]*$|-broken$|-backup$|~$|^copy of /i', $name)) return 'backup';
    if (preg_match('/^\.htaccess[._-].+/i', $name)) return 'backup';
    return '';
}

/** Path as stored in the database ("missindia_profile/x.jpg", "/x.jpg", "https://fsia.in/x.jpg") -> "missindia_profile/x.jpg". */
function cl_norm(string $p): string
{
    $p = trim($p);
    $p = preg_replace('#^https?://(www\.)?fsia\.in/#i', '', $p);
    return ltrim(rawurldecode($p), '/');
}

/** Every image path the database still uses. Empty set = database not readable (then no photo is called unused). */
function cl_used_paths(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $used = [];
    $add = function (?string $list) use (&$used) {
        foreach (explode(',', (string) $list) as $p) {
            $p = cl_norm($p);
            if ($p !== '') $used[$p] = true;
        }
    };
    $cols = array_column(site_db_rows('SHOW COLUMNS FROM registration'), 'Field');
    $want = array_values(array_intersect(['setdefault', 'image', 'portfolio', 'workimage', 'data_value', 'brand_logo',
        'nimage1', 'simage1', 'image1', 'nimage2', 'simage2', 'image2', 'newsimg'], $cols));
    if (!$want) return $cache = [];
    foreach (site_db_rows('SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", $want)) . ' FROM registration') as $r) {
        foreach ($r as $v) $add($v);
    }
    // Other tables that can point at the same folders; skipped when they do not exist.
    foreach (['user_image_caption' => 'image', 'tbl_achievements' => 'achivfile'] as $t => $c) {
        try {
            foreach (site_db_rows("SELECT `$c` FROM `$t`") as $r) $add($r[$c] ?? '');
        } catch (Throwable $e) {
            // table not there on this site
        }
    }
    return $cache = $used;
}

/** Walks the website folder once and sorts the unwanted files into groups. */
function cl_scan(): array
{
    @set_time_limit(300);
    $root = site_root();
    $groups = array_fill_keys(array_keys(CL_GROUPS), ['count' => 0, 'bytes' => 0, 'items' => []]);
    $used = null;
    $usedOk = false;
    $oldEnough = time() - CL_ORPHAN_MIN_AGE * 86400;
    $walked = 0;
    $stopped = false;

    $dirIt = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
    $filter = new RecursiveCallbackFilterIterator($dirIt, function (SplFileInfo $f) use ($root) {
        if ($f->isLink()) return false;
        if ($f->isDir()) {
            $rel = ltrim(substr($f->getPathname(), strlen($root)), '/');
            $top = explode('/', $rel)[0];
            return !in_array($top, CL_SKIP_DIRS, true) && $f->getFilename()[0] !== '.';
        }
        return true;
    });
    foreach (new RecursiveIteratorIterator($filter) as $f) {
        /** @var SplFileInfo $f */
        if (++$walked > CL_MAX_FILES) { $stopped = true; break; }
        if (!$f->isFile()) continue;
        $rel = ltrim(substr($f->getPathname(), strlen($root)), '/');
        $size = (int) $f->getSize();
        $group = cl_group_by_name($rel);

        // Photos in the profile folders that no profile uses any more.
        if ($group === '' && preg_match('#^[\w-]+_profile/[^/]+\.(jpe?g|png|webp|gif|jfif|avif)$#i', $rel) && $f->getMTime() < $oldEnough) {
            if ($used === null) {
                try { $used = cl_used_paths(); } catch (Throwable $e) { $used = []; }
                $usedOk = count($used) > 100; // a near-empty list means the database was not read properly
            }
            if ($usedOk && !isset($used[$rel])) $group = 'orphan';
        }
        if ($group === '' && $size >= CL_LARGE_BYTES && !preg_match('/\.php$/i', $rel)) $group = 'large';
        if ($group === '') continue;

        $g = &$groups[$group];
        $g['count']++;
        $g['bytes'] += $size;
        if (count($g['items']) < CL_MAX_ITEMS) {
            $g['items'][] = ['path' => $rel, 'size' => $size, 'date' => date('Y-m-d', $f->getMTime())];
        }
        unset($g);
    }
    foreach ($groups as &$g) {
        usort($g['items'], fn ($a, $b) => $b['size'] <=> $a['size']);
    }
    unset($g);

    return [
        'groups'   => $groups,
        'labels'   => CL_GROUPS,
        'walked'   => min($walked, CL_MAX_FILES),
        'stopped'  => $stopped,
        'photos_checked' => $usedOk,
        'trash'    => cl_trash_root(),
    ];
}

/** Checks one path from the browser: inside the site, not protected, and still in a cleanup group. */
function cl_check_path(string $rel): array
{
    $root = site_root();
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0")) {
        throw new ApiError("Not allowed: $rel", 422);
    }
    $abs = $root . '/' . $rel;
    $real = realpath($abs);
    if ($real === false || !is_file($real) || is_link($abs) || !str_starts_with($real, $root . '/')) {
        throw new ApiError("File not found: $rel", 422);
    }
    $top = explode('/', $rel)[0];
    if (in_array($top, CL_SKIP_DIRS, true) || in_array(strtolower(basename($rel)), CL_PROTECTED, true)) {
        throw new ApiError("Protected file: $rel", 422);
    }
    $group = cl_group_by_name($rel);
    if ($group === '' && preg_match('#^[\w-]+_profile/[^/]+$#', $rel)) {
        $used = cl_used_paths();
        if (count($used) > 100 && !isset($used[$rel]) && filemtime($real) < time() - CL_ORPHAN_MIN_AGE * 86400) $group = 'orphan';
    }
    if ($group === '' && filesize($real) >= CL_LARGE_BYTES && !preg_match('/\.php$/i', $rel)) $group = 'large';
    if ($group === '') {
        throw new ApiError("This file is in use by the website and cannot be moved: $rel", 422);
    }
    return [$rel, $real];
}

/** What a file holds, for the preview in the admin: text, image, zip listing or basic facts. */
function cl_preview(string $rel, string $real): array
{
    $size = (int) filesize($real);
    $out = ['path' => $rel, 'size' => $size, 'date' => date('Y-m-d H:i', (int) filemtime($real)), 'kind' => 'other'];
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));

    if (preg_match('/^(jpe?g|png|webp|gif|jfif|avif|svg|bmp|ico)$/', $ext)) {
        $dim = @getimagesize($real);
        return array_merge($out, ['kind' => 'image', 'url' => rtrim((string) (cms_config()['site_url'] ?? 'https://www.fsia.in'), '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $rel))),
            'width' => $dim[0] ?? null, 'height' => $dim[1] ?? null]);
    }
    if ($ext === 'zip' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($real) === true) {
            $entries = [];
            for ($i = 0; $i < min($zip->numFiles, 500); $i++) {
                $st = $zip->statIndex($i);
                $entries[] = ['name' => $st['name'], 'size' => (int) $st['size']];
            }
            $n = $zip->numFiles;
            $zip->close();
            return array_merge($out, ['kind' => 'zip', 'entries' => $entries, 'total_entries' => $n]);
        }
    }
    if (preg_match('/^(mp4|mov|webm|mkv|avi|mp3|wav|pdf|rar|7z|tar|tgz|bz2)$/', $ext)) {
        return array_merge($out, ['mime' => function_exists('mime_content_type') ? (string) @mime_content_type($real) : '']);
    }

    // Everything else: show it as text when it is text (logs, .bak copies, .sql, .htaccess-broken, .gz of text).
    $limit = 65536;
    if ($ext === 'gz' && function_exists('gzopen')) {
        $h = @gzopen($real, 'rb');
        $raw = $h ? (string) gzread($h, $limit + 1) : '';
        if ($h) gzclose($h);
    } else {
        $h = @fopen($real, 'rb');
        $raw = $h ? (string) fread($h, $limit + 1) : '';
        if ($h) fclose($h);
    }
    if ($raw !== '' && str_contains(substr($raw, 0, 8000), "\0")) {
        return array_merge($out, ['mime' => function_exists('mime_content_type') ? (string) @mime_content_type($real) : '']);
    }
    $truncated = strlen($raw) > $limit || ($ext !== 'gz' && $size > $limit);
    $raw = substr($raw, 0, $limit);
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    }
    return array_merge($out, ['kind' => 'text', 'text' => $raw, 'truncated' => $truncated, 'lines' => substr_count($raw, "\n") + 1]);
}

function cl_move(string $from, string $to): void
{
    $dir = dirname($to);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
        throw new ApiError('Cannot create the Trash folder. Check that the website can write to ' . cl_trash_root(), 500);
    }
    if (!@rename($from, $to)) {
        throw new ApiError('Could not move ' . basename($from), 500);
    }
}

function cl_manifest(string $batch): array
{
    if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{6}$/', $batch)) throw new ApiError('Unknown Trash entry', 404);
    $file = cl_trash_root() . "/$batch/manifest.json";
    if (!is_file($file)) throw new ApiError('Unknown Trash entry', 404);
    return json_decode((string) file_get_contents($file), true) ?: [];
}

function cl_save_manifest(string $batch, array $m): void
{
    file_put_contents(cl_trash_root() . "/$batch/manifest.json", json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function cl_rmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() && !$f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

respond(function () {
    require_admin();
    $action = query_param('action') ?? '';
    $method = request_method();

    if ($method === 'GET' && $action === 'scan') {
        return cl_scan();
    }

    if ($method === 'GET' && $action === 'preview') {
        [$rel, $real] = cl_check_path((string) query_param('path'));
        return cl_preview($rel, $real);
    }

    if ($method === 'GET' && $action === 'download') {
        // A copy of a file before it goes to Trash (e.g. a log someone still needs).
        [$rel, $real] = cl_check_path((string) query_param('path'));
        header_remove('Content-Type');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($rel)) . '"');
        header('Content-Length: ' . filesize($real));
        audit_record(200);
        readfile($real);
        exit;
    }

    if ($method === 'POST' && $action === 'trash') {
        $paths = json_body()['paths'] ?? null;
        if (!is_array($paths) || !$paths) throw new ApiError('Choose at least one file', 422);
        if (count($paths) > 5000) throw new ApiError('At most 5000 files at a time', 422);
        $checked = array_map(fn ($p) => cl_check_path((string) $p), array_values(array_unique($paths)));

        $batch = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        $base = cl_trash_root() . "/$batch";
        if (!is_dir($base) && !@mkdir($base . '/files', 0700, true)) {
            throw new ApiError('Cannot create the Trash folder outside the website (' . cl_trash_root() . '). Nothing was moved.', 500);
        }
        $m = ['batch' => $batch, 'at' => date('c'), 'items' => []];
        foreach ($checked as [$rel, $real]) {
            $size = (int) filesize($real);
            cl_move($real, "$base/files/$rel");
            $m['items'][] = ['path' => $rel, 'size' => $size, 'restored' => false];
            cl_save_manifest($batch, $m); // written after every file, so a stop half-way is still recoverable
        }
        return ['batch' => $batch, 'moved' => count($m['items']), 'bytes' => array_sum(array_column($m['items'], 'size'))];
    }

    if ($method === 'GET' && $action === 'trash_list') {
        $out = [];
        foreach (glob(cl_trash_root() . '/*/manifest.json') ?: [] as $file) {
            $m = json_decode((string) file_get_contents($file), true);
            if (!$m) continue;
            $left = array_values(array_filter($m['items'], fn ($i) => !$i['restored']));
            if (!$left) continue;
            $age = (int) floor((time() - strtotime($m['at'])) / 86400);
            $out[] = [
                'batch' => $m['batch'], 'at' => $m['at'], 'age_days' => $age,
                'can_purge' => $age >= CL_KEEP_DAYS, 'purge_in' => max(0, CL_KEEP_DAYS - $age),
                'count' => count($left), 'bytes' => array_sum(array_column($left, 'size')),
                'items' => array_slice($left, 0, 2000),
            ];
        }
        usort($out, fn ($a, $b) => strcmp($b['at'], $a['at']));
        return ['batches' => $out, 'keep_days' => CL_KEEP_DAYS, 'trash' => cl_trash_root()];
    }

    if ($method === 'POST' && $action === 'restore') {
        $body = json_body();
        $batch = (string) ($body['batch'] ?? '');
        $m = cl_manifest($batch);
        $only = isset($body['paths']) && is_array($body['paths']) ? array_flip(array_map('strval', $body['paths'])) : null;
        $root = site_root();
        $done = 0;
        $skipped = [];
        foreach ($m['items'] as &$i) {
            if ($i['restored'] || ($only !== null && !isset($only[$i['path']]))) continue;
            $from = cl_trash_root() . "/$batch/files/" . $i['path'];
            $to = $root . '/' . $i['path'];
            if (file_exists($to)) { $skipped[] = $i['path'] . ' (a file with this name is already on the website)'; continue; }
            if (!is_file($from)) { $skipped[] = $i['path'] . ' (missing from Trash)'; continue; }
            cl_move($from, $to);
            $i['restored'] = true;
            $done++;
        }
        unset($i);
        cl_save_manifest($batch, $m);
        if (!array_filter($m['items'], fn ($i) => !$i['restored'])) cl_rmdir(cl_trash_root() . "/$batch");
        return ['restored' => $done, 'skipped' => $skipped];
    }

    if ($method === 'POST' && $action === 'purge') {
        $batch = (string) (json_body()['batch'] ?? '');
        $m = cl_manifest($batch);
        $age = (time() - strtotime($m['at'])) / 86400;
        if ($age < CL_KEEP_DAYS) {
            throw new ApiError('Files stay in Trash for ' . CL_KEEP_DAYS . ' days before they can be deleted for good.', 422);
        }
        cl_rmdir(cl_trash_root() . "/$batch");
        return ['deleted' => count(array_filter($m['items'], fn ($i) => !$i['restored']))];
    }

    throw new ApiError('Unknown action', 400);
});
