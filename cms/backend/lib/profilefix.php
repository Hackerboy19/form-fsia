<?php
declare(strict_types=1);

/*
 * Auto fix for profile SEO fields (Site Check -> Profiles).
 *
 * Only the meta title, meta description and meta keywords of profiles on
 * www.fsia.in are filled in (empty) or shortened (too long). Bio, photos,
 * banners and the profile link need real content or a page in the admin, so
 * they are never touched. Profiles edited in the starindia admin keep their
 * data there and are skipped.
 *
 * Every change is saved in fsia_cms_profile_fixes with the old value, so a
 * whole batch can be undone. Undo only puts a value back if nobody changed
 * that field again after the fix.
 */

require_once __DIR__ . '/profile_report.php';
require_once __DIR__ . '/sitefix.php';

const PF_TABLE = 'fsia_cms_profile_fixes';
const PF_FIELDS = ['meta_title', 'meta_desc', 'meta_keyword'];
const PF_TITLE_MAX = 70;     // same limits as the profile check
const PF_DESC_MAX = 170;
const PF_TITLE_AIM = 60;
const PF_DESC_AIM = 158;

function pf_table(): void
{
    site_db_exec('CREATE TABLE IF NOT EXISTS ' . PF_TABLE . ' (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        batch       VARCHAR(40) NOT NULL,
        profile_id  INT NOT NULL,
        field       VARCHAR(40) NOT NULL,
        old_value   MEDIUMTEXT NULL,
        new_value   MEDIUMTEXT NOT NULL,
        by_user     VARCHAR(120) NOT NULL DEFAULT \'\',
        created_at  DATETIME NOT NULL,
        undone_at   DATETIME NULL,
        KEY batch (batch),
        KEY profile_id (profile_id)
    ) DEFAULT CHARSET=utf8mb4');
}

/** "jigyasa kursange" -> "Jigyasa Kursange"; names already in mixed case stay as they are. */
function pf_name(array $r): string
{
    $n = trim(preg_replace('/\s+/u', ' ', trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))));
    if ($n === '') return '';
    $words = explode(' ', $n);
    foreach ($words as &$w) {
        if ($w === mb_strtolower($w) || $w === mb_strtoupper($w)) $w = mb_convert_case(mb_strtolower($w), MB_CASE_TITLE);
    }
    return implode(' ', $words);
}

function pf_plain(string $html): string
{
    $t = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', $t)));
}

/** Fits $text in $max characters without a trailing "." added by sf_shorten for short limits. */
function pf_fit(string $text, int $max): string
{
    return sf_shorten($text, $max);
}

/**
 * What the auto fix would write for one registration row: [field => new value].
 * Empty when there is nothing it can fix.
 */
function pf_suggest(array $r): array
{
    if (($r['starindia_action'] ?? '') === 'edited') return [];
    $name = pf_name($r);
    if ($name === '') return [];
    $cat = sc_category_of((int) ($r['regtype'] ?? 0));
    $catLabel = $cat === 'other' ? '' : SC_CATEGORIES[$cat][0];
    $year = substr((string) ($r['date_of_reg'] ?? ''), 0, 4);
    $year = preg_match('/^20\d\d$/', $year) ? $year : '';
    $catYear = trim("$catLabel $year");

    $out = [];
    $title = trim((string) ($r['meta_title'] ?? ''));
    if (array_key_exists('meta_title', $r)) {
        if ($title === '') {
            $opts = [
                $catYear !== '' ? "$name | $catYear | FSIA" : "$name | Forever Star India Awards",
                $catLabel !== '' ? "$name | $catLabel | FSIA" : "$name | FSIA",
                "$name | FSIA",
            ];
            foreach ($opts as $o) {
                if (mb_strlen($o) <= PF_TITLE_AIM) { $out['meta_title'] = $o; break; }
            }
            $out['meta_title'] ??= pf_fit($name, PF_TITLE_AIM);
        } elseif (mb_strlen($title) > PF_TITLE_MAX) {
            $out['meta_title'] = pf_fit($title, PF_TITLE_AIM);
        }
    }

    $desc = trim((string) ($r['meta_desc'] ?? ''));
    if (array_key_exists('meta_desc', $r)) {
        if ($desc === '') {
            $bio = '';
            foreach (['shortbio', 'fullbio', 'newbio', 'bio', 'comment', 'fullbio_2', 'fullbio_3'] as $k) {
                $t = pf_plain((string) ($r[$k] ?? ''));
                if (mb_strlen($t) >= 60) { $bio = $t; break; }
            }
            if ($bio !== '') {
                $out['meta_desc'] = pf_fit($bio, PF_DESC_AIM);
            } else {
                $what = $catYear !== '' ? "$name, $catYear at Forever Star India Awards (FSIA)." : "$name at Forever Star India Awards (FSIA).";
                $out['meta_desc'] = pf_fit("$what See the profile, photos and journey of $name.", PF_DESC_AIM);
            }
        } elseif (mb_strlen($desc) > PF_DESC_MAX) {
            $out['meta_desc'] = pf_fit($desc, PF_DESC_AIM);
        }
    }

    if (array_key_exists('meta_keyword', $r) && trim((string) ($r['meta_keyword'] ?? '')) === '') {
        $k = [$name];
        if ($catLabel !== '') $k[] = $catLabel;
        if ($catLabel !== '' && $year !== '') $k[] = "$catLabel $year";
        array_push($k, 'FSIA', 'Forever Star India Awards');
        $out['meta_keyword'] = implode(', ', array_unique($k));
    }
    return $out;
}

/** Registration rows by id, with the columns the report and the fix need. */
function pf_rows(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($i) => $i > 0)));
    if (!$ids) return [];
    return sc_profile_rows_by_ids($ids);
}

function pf_who(): string
{
    $id = function_exists('rbac_identity') ? rbac_identity() : null;
    return (string) ($id['user'] ?? '');
}

/** Fills in / shortens the SEO fields of these profiles. Returns the batch id and what changed. */
function pf_apply(array $ids, string $batch = ''): array
{
    $rows = pf_rows(array_slice($ids, 0, 500));
    pf_table();
    // One "Auto fix SEO" click is sent in parts; they share one batch so one Undo covers it.
    if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}$/', $batch)) $batch = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $who = pf_who();
    $fixed = 0;
    $fields = 0;
    $skipped = 0;
    foreach ($rows as $r) {
        $new = pf_suggest($r);
        if (!$new) { $skipped++; continue; }
        foreach ($new as $field => $value) {
            if (!in_array($field, PF_FIELDS, true)) continue;
            $old = $r[$field] ?? null;
            site_db_rows('INSERT INTO ' . PF_TABLE . ' (batch, profile_id, field, old_value, new_value, by_user, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$batch, (int) $r['id'], $field, $old ?? '', $value, $who]);
            // Only change the field if it still holds what was read a moment ago.
            site_db_rows("UPDATE registration SET `$field` = ? WHERE id = ? AND (`$field` = ? OR `$field` IS NULL)", [$value, (int) $r['id'], (string) ($old ?? '')]);
            $fields++;
        }
        $fixed++;
    }
    return ['batch' => $batch, 'profiles' => $fixed, 'fields' => $fields, 'skipped' => $skipped];
}

/** Puts back the values a batch replaced (only where the field still holds the fix). */
function pf_undo(string $batch): array
{
    if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}$/', $batch)) throw new ApiError('Unknown fix', 404);
    pf_table();
    $rows = site_db_rows('SELECT id, profile_id, field, old_value, new_value FROM ' . PF_TABLE . ' WHERE batch = ? AND undone_at IS NULL', [$batch]);
    if (!$rows) throw new ApiError('This fix was already undone.', 409);
    $back = 0;
    $kept = 0;
    $ids = [];
    foreach ($rows as $x) {
        if (!in_array($x['field'], PF_FIELDS, true)) continue;
        $cur = site_db_rows("SELECT `{$x['field']}` AS v FROM registration WHERE id = ?", [(int) $x['profile_id']])[0]['v'] ?? null;
        if ((string) $cur === (string) $x['new_value']) {
            site_db_rows("UPDATE registration SET `{$x['field']}` = ? WHERE id = ?", [(string) $x['old_value'], (int) $x['profile_id']]);
            $back++;
        } else {
            $kept++; // changed again since the fix: leave the newer text
        }
        $ids[(int) $x['profile_id']] = true;
    }
    site_db_rows('UPDATE ' . PF_TABLE . ' SET undone_at = NOW() WHERE batch = ? AND undone_at IS NULL', [$batch]);
    return ['batch' => $batch, 'restored' => $back, 'kept' => $kept, 'ids' => array_keys($ids)];
}

/** Recent fix batches, newest first. */
function pf_batches(): array
{
    if (!site_db_has_table(PF_TABLE)) return [];
    return array_map(fn ($b) => [
        'batch' => $b['batch'], 'at' => $b['at'], 'by' => $b['by_user'], 'profiles' => (int) $b['profiles'],
        'fields' => (int) $b['fields'], 'undone' => $b['undone'] !== null,
    ], site_db_rows('SELECT batch, MIN(created_at) AS at, MAX(by_user) AS by_user, COUNT(DISTINCT profile_id) AS profiles,
            COUNT(*) AS fields, MAX(undone_at) AS undone
        FROM ' . PF_TABLE . ' GROUP BY batch ORDER BY at DESC, batch DESC LIMIT 20'));
}
