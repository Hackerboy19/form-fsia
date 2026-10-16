<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/site_db.php';
require_once __DIR__ . '/../lib/profile_report.php';
require_once __DIR__ . '/../lib/sanitize_html.php';
require_once __DIR__ . '/../lib/image_store.php';

/*
 * Fix a profile from the Profiles & Score screen.
 *
 * GET  /api/profiles.php?id=N                     -> editable fields, banners, recent edits
 * PUT  /api/profiles.php?id=N   {fields:{k:v}}    -> saves the fields that changed
 * POST /api/profiles.php?id=N&action=banner       -> multipart "image" + "which" (desktop|mobile)
 * POST /api/profiles.php?action=undo {batch}      -> puts the values of one save back
 *
 * Only the columns below can be changed, and only on the profile asked for.
 * Every save keeps the old values in cms_profile_edits (CMS database) so it can
 * be undone; an undo is refused when someone changed the field again since.
 */

/* Column => [label, kind, recommended length]. Same columns the live profile page shows. */
const PE_FIELDS = [
    'meta_title'   => ['SEO title', 'line', 70],
    'meta_desc'    => ['SEO description', 'text', 170],
    'meta_keyword' => ['SEO keywords', 'line', 0],
    'shortbio'     => ['Short bio (tagline under the name)', 'text', 0],
    'fullbio'      => ['Introduction', 'html', 0],
    'fullbio_2'    => ['Success story', 'html', 0],
    'fullbio_3'    => ['Journey', 'html', 0],
];
/* The page uses the first filled column of each list; the editor writes the first one. */
const PE_BANNERS = [
    'desktop' => ['nimage2', 'simage2', 'image2'],
    'mobile'  => ['nimage1', 'simage1', 'image1'],
];
const PE_EDITS_SQL = "CREATE TABLE IF NOT EXISTS cms_profile_edits (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    batch         CHAR(16) NOT NULL,
    profile_id    INT UNSIGNED NOT NULL,
    field         VARCHAR(64) NOT NULL,
    before_value  MEDIUMTEXT NULL,
    after_value   MEDIUMTEXT NULL,
    username      VARCHAR(64) NOT NULL DEFAULT '',
    created_at    DATETIME NOT NULL,
    undone_at     DATETIME NULL,
    KEY idx_profile (profile_id, id),
    KEY idx_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

/** registration columns => max characters (null = long text). */
function pe_columns(): array
{
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        foreach (site_db_rows('SHOW COLUMNS FROM registration') as $c) {
            $max = preg_match('/^(?:var)?char\((\d+)\)/i', (string) $c['Type'], $m) ? (int) $m[1]
                : (preg_match('/^tinytext/i', (string) $c['Type']) ? 255 : (preg_match('/^text/i', (string) $c['Type']) ? 65535 : null));
            $cols[$c['Field']] = $max;
        }
    }
    return $cols;
}

function pe_row(int $id): array
{
    $cols = array_keys(pe_columns());
    $want = array_values(array_intersect(array_merge(
        ['id', 'first_name', 'last_name', 'regtype', 'status', 'date_of_reg', 'url', 'starindia_action', 'setdefault', 'image',
         'shortbio', 'newbio', 'bio', 'comment', 'meta_title', 'meta_desc', 'meta_keyword', 'h1', 'fullbio', 'fullbio_2', 'fullbio_3'],
        ...array_values(PE_BANNERS)
    ), $cols));
    $rows = site_db_rows('SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", $want)) . ' FROM registration WHERE id = ? LIMIT 1', [$id]);
    if (!$rows) {
        throw new ApiError('Profile not found', 404);
    }
    return $rows[0];
}

function pe_banner_url(string $v): string
{
    $v = trim($v, " ,\t\n\r");
    if ($v === '') return '';
    if (preg_match('#^https?://#i', $v)) return $v;
    return 'https://www.fsia.in/images/' . implode('/', array_map('rawurlencode', explode('/', ltrim($v, '/'))));
}

function pe_view(array $row): array
{
    $cols = pe_columns();
    $fields = [];
    foreach (PE_FIELDS as $k => [$label, $kind, $soft]) {
        if (!array_key_exists($k, $cols)) continue;
        $fields[] = ['key' => $k, 'label' => $label, 'kind' => $kind, 'soft' => $soft, 'max' => $cols[$k], 'value' => (string) ($row[$k] ?? '')];
    }
    $banners = [];
    foreach (PE_BANNERS as $which => $keys) {
        $keys = array_values(array_intersect($keys, array_keys($cols)));
        if (!$keys) continue;
        $val = '';
        foreach ($keys as $k) {
            if (trim((string) ($row[$k] ?? '')) !== '') { $val = trim((string) $row[$k], " ,"); break; }
        }
        $banners[$which] = ['column' => $keys[0], 'value' => $val, 'url' => pe_banner_url($val),
            'on_server' => $val !== '' && (sc_file_ok($val, 'images') || sc_file_ok($val))];
    }
    return ['id' => (int) $row['id'], 'fields' => $fields, 'banners' => $banners, 'report' => sc_profile_report($row), 'history' => pe_history((int) $row['id'])];
}

function pe_history(int $id): array
{
    try {
        $st = db()->prepare('SELECT batch, field, before_value, after_value, username, created_at, undone_at FROM cms_profile_edits WHERE profile_id = ? ORDER BY id DESC LIMIT 60');
        $st->execute([$id]);
    } catch (PDOException $e) {
        if (rbac_missing_table($e)) return [];
        throw $e;
    }
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $b = $r['batch'];
        $out[$b] ??= ['batch' => $b, 'at' => $r['created_at'], 'by' => $r['username'], 'undone' => $r['undone_at'] !== null, 'fields' => []];
        $out[$b]['fields'][] = ['field' => $r['field'], 'before' => mb_substr((string) $r['before_value'], 0, 300), 'after' => mb_substr((string) $r['after_value'], 0, 300)];
    }
    return array_slice(array_values($out), 0, 10);
}

/** Cleans a submitted value for its field. */
function pe_clean(string $key, string $v): string
{
    $kind = PE_FIELDS[$key][1];
    if ($kind === 'html') {
        return sanitize_article_html($v);
    }
    $v = strip_tags($v);
    return $kind === 'line' ? trim(preg_replace('/\s+/u', ' ', $v)) : trim(str_replace("\r\n", "\n", $v));
}

/** Writes values to one profile and keeps the old ones; returns the batch id. */
function pe_write(int $id, array $changes, array $before): string
{
    $user = rbac_identity()['user'] ?? '';
    $link = site_db_link();
    // Lets the Profile Changes log (if it is on) name the CMS as the source of the change.
    $actor = ($_SERVER['HTTP_HOST'] ?? 'www.fsia.in') . '/cms/backend/api/profiles.php · admin=' . $user;
    if ($link instanceof PDO) {
        $link->prepare('SET @fsia_actor = ?')->execute([$actor]);
    } else {
        $st = $link->prepare('SET @fsia_actor = ?');
        $st->bind_param('s', $actor);
        $st->execute();
    }
    // The undo record is written first: a profile is never changed without one.
    $batch = bin2hex(random_bytes(8));
    db()->exec(PE_EDITS_SQL);
    $ins = db()->prepare('INSERT INTO cms_profile_edits (batch, profile_id, field, before_value, after_value, username, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    foreach ($changes as $col => $val) {
        $ins->execute([$batch, $id, $col, $before[$col] ?? null, $val, $user]);
    }
    $sets = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($changes)));
    try {
        site_db_rows("UPDATE registration SET $sets WHERE id = ? LIMIT 1", [...array_values($changes), $id]);
    } catch (Throwable $e) {
        db()->prepare('DELETE FROM cms_profile_edits WHERE batch = ?')->execute([$batch]);
        throw $e;
    }
    return $batch;
}

respond(function () {
    require_admin();
    $method = request_method();
    $action = query_param('action') ?? '';

    if ($method === 'POST' && $action === 'undo') {
        $batch = (string) (json_body()['batch'] ?? '');
        if (!preg_match('/^[0-9a-f]{16}$/', $batch)) throw new ApiError('Unknown save', 422);
        try {
            $st = db()->prepare('SELECT profile_id, field, before_value, after_value, undone_at FROM cms_profile_edits WHERE batch = ?');
            $st->execute([$batch]);
            $rows = $st->fetchAll();
        } catch (PDOException $e) {
            if (rbac_missing_table($e)) throw new ApiError('Unknown save', 404);
            throw $e;
        }
        if (!$rows) throw new ApiError('Unknown save', 404);
        if ($rows[0]['undone_at'] !== null) throw new ApiError('This save was already undone', 409);
        $id = (int) $rows[0]['profile_id'];
        $now = pe_row($id);
        $changed = array_filter($rows, fn ($r) => (string) ($now[$r['field']] ?? '') !== (string) $r['after_value']);
        if ($changed) {
            throw new ApiError('Not undone: ' . implode(', ', array_column($changed, 'field')) . ' changed again after this save.', 409);
        }
        $back = array_column($rows, 'before_value', 'field');
        $batchNew = pe_write($id, array_map(fn ($v) => (string) $v, $back), array_column($rows, 'after_value', 'field'));
        db()->prepare('UPDATE cms_profile_edits SET undone_at = NOW() WHERE batch = ?')->execute([$batch]);
        return ['undone' => count($rows), 'batch' => $batchNew] + pe_view(pe_row($id));
    }

    $id = query_id();
    if ($method === 'GET') {
        return pe_view(pe_row($id));
    }

    if ($method === 'PUT') {
        $in = json_body()['fields'] ?? null;
        if (!is_array($in) || !$in) throw new ApiError('Nothing to save', 422);
        $cols = pe_columns();
        $row = pe_row($id);
        $changes = [];
        $errors = [];
        foreach ($in as $k => $v) {
            if (!isset(PE_FIELDS[$k]) || !array_key_exists($k, $cols)) { $errors[$k] = 'This field cannot be changed here'; continue; }
            if (!is_string($v)) { $errors[$k] = 'Must be text'; continue; }
            if ($v === (string) ($row[$k] ?? '')) continue; // untouched: never re-cleaned or rewritten
            $clean = pe_clean($k, $v);
            if ($cols[$k] !== null && ($cols[$k] === 65535 ? strlen($clean) : mb_strlen($clean)) > $cols[$k]) {
                $errors[$k] = "Too long: the database keeps at most {$cols[$k]} characters"; continue;
            }
            if ($clean !== (string) ($row[$k] ?? '')) $changes[$k] = $clean;
        }
        if ($errors) throw new ApiError('Some fields could not be saved', 422, $errors);
        if (!$changes) return ['saved' => [], 'batch' => null] + pe_view($row);
        $batch = pe_write($id, $changes, $row);
        return ['saved' => array_keys($changes), 'batch' => $batch] + pe_view(pe_row($id));
    }

    if ($method === 'POST' && $action === 'banner') {
        $which = (string) ($_POST['which'] ?? '');
        $cols = pe_columns();
        $keys = array_values(array_intersect(PE_BANNERS[$which] ?? [], array_keys($cols)));
        if (!$keys) throw new ApiError('Choose desktop or mobile', 422);
        $row = pe_row($id);
        $dir = site_root() . '/images';
        [$name] = image_store($_FILES['image'] ?? null, $dir, "fsia-banner-$id-$which-", 8 * 1024 * 1024, 6000);
        if ($cols[$keys[0]] !== null && strlen($name) > $cols[$keys[0]]) {
            @unlink("$dir/$name");
            throw new ApiError('The banner column is too short for the file name', 500);
        }
        $batch = pe_write($id, [$keys[0] => $name], $row);
        return ['saved' => [$keys[0]], 'batch' => $batch] + pe_view(pe_row($id));
    }

    throw new ApiError('Method not allowed', 405);
});
