<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/sanitize_html.php';
require_once __DIR__ . '/../lib/events.php';

/*
 * Events & Promotions (admin).
 * GET    /api/events.php                    -> all events with their phase
 * GET    /api/events.php?id=5               -> one event
 * GET    /api/events.php?action=match&path=/x -> which promotion that page would show now
 * POST   /api/events.php                    -> create (saved as draft unless status given)
 * POST   /api/events.php?action=duplicate&id=5 -> copy as a new draft
 * PUT    /api/events.php?id=5               -> update (send the whole event)
 * DELETE /api/events.php?id=5               -> delete
 */

function event_dt(Validator $v, array $in, string $key, bool $required): ?string
{
    $raw = trim((string) ($in[$key] ?? ''));
    if ($raw === '') {
        if ($required) $v->addError($key, 'Required');
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', substr($raw, 0, 16), new DateTimeZone(EVENT_TZ))
        ?: DateTimeImmutable::createFromFormat('!Y-m-d H:i', substr($raw, 0, 16), new DateTimeZone(EVENT_TZ));
    if (!$d) {
        $v->addError($key, 'Use a date and time like 2027-01-08T18:00');
        return null;
    }
    return $d->format('Y-m-d H:i:s');
}

function event_rules(Validator $v, mixed $raw, string $key): array
{
    if (!is_array($raw)) {
        $v->addError($key, 'Must be a list');
        return [];
    }
    $out = [];
    foreach ($raw as $r) {
        $r = trim((string) $r);
        if ($r === '') continue;
        if (!isset(EVENT_GROUPS[$r]) && !preg_match('#^/[A-Za-z0-9._~*/-]{0,200}$#', $r)) {
            $v->addError($key, "Not a page rule: $r (use a group or a path like /miss-assam.php or /news*)");
            continue;
        }
        $out[] = $r;
    }
    return array_values(array_unique($out));
}

function event_validate(array $in): array
{
    $v = new Validator($in);
    $row = [
        'title'          => $v->string('title', 150, true),
        'status'         => $v->enum('status', EVENT_STATUSES, 'draft'),
        'venue'          => $v->string('venue', 200),
        'city'           => $v->string('city', 100),
        'summary'        => $v->string('summary', 300),
        'description'    => sanitize_article_html(is_string($in['description'] ?? null) ? $in['description'] : ''),
        'banner_desktop' => $v->url('banner_desktop'),
        'banner_mobile'  => $v->url('banner_mobile'),
        'cta_label'      => $v->string('cta_label', 40, false, 'Register now') ?: 'Register now',
        'cta_url'        => $v->url('cta_url'),
        'priority'       => $v->int('priority', 0, 0, 100),
    ];
    $row['starts_at'] = event_dt($v, $in, 'starts_at', true);
    $row['ends_at'] = event_dt($v, $in, 'ends_at', true);
    $row['promo_start'] = event_dt($v, $in, 'promo_start', false) ?? events_now()->format('Y-m-d H:i:s');
    $row['promo_end'] = event_dt($v, $in, 'promo_end', false) ?? $row['ends_at'];
    if ($row['starts_at'] && $row['ends_at'] && $row['ends_at'] < $row['starts_at']) $v->addError('ends_at', 'Ends before it starts');
    if ($row['promo_end'] && $row['promo_start'] > $row['promo_end']) $v->addError('promo_end', 'Promotion ends before it starts');

    $guests = [];
    foreach ((array) ($in['guests'] ?? []) as $i => $g) {
        if (!is_array($g)) continue;
        $gv = new Validator($g);
        $item = ['name' => $gv->string('name', 100), 'role' => $gv->string('role', 120), 'photo' => $gv->url('photo'),
            'featured' => filter_var($g['featured'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        try { $gv->check(); } catch (ApiError $e) { $v->addError("guests.$i", 'Name up to 100, role up to 120 characters, photo must be a link'); continue; }
        if ($item['name'] !== '') $guests[] = $item;
    }
    if (count($guests) > 30) $v->addError('guests', 'At most 30 guests');
    if (count(array_filter($guests, fn ($g) => $g['featured'])) > EVENT_BAR_GUESTS) $v->addError('guests', 'Show at most ' . EVENT_BAR_GUESTS . ' guests on the website bar');
    $row['guests'] = json_encode($guests, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $targets = event_rules($v, $in['targets'] ?? [], 'targets');
    $excludes = event_rules($v, $in['excludes'] ?? [], 'excludes');
    if ($row['status'] === 'published' && !$targets) $v->addError('targets', 'Choose at least one page to promote on before publishing');
    if ($row['status'] === 'published' && $row['cta_url'] === '') $v->addError('cta_url', 'A published event needs a registration / details link');
    $row['targets'] = json_encode($targets, JSON_UNESCAPED_SLASHES);
    $row['excludes'] = json_encode($excludes, JSON_UNESCAPED_SLASHES);
    $v->check();
    return $row;
}

function event_get(int $id): array
{
    $st = db()->prepare('SELECT * FROM cms_events WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) throw new ApiError('Event not found', 404);
    return events_row($r);
}

function event_save(?int $id, array $row): array
{
    $now = events_now()->format('Y-m-d H:i:s');
    $row['updated_at'] = $now;
    $cols = array_keys($row);
    if ($id === null) {
        $row['created_at'] = $now;
        $cols[] = 'created_at';
        db()->prepare('INSERT INTO cms_events (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
            ->execute(array_values($row));
        return event_get((int) db()->lastInsertId());
    }
    db()->prepare('UPDATE cms_events SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?')
        ->execute([...array_values($row), $id]);
    return event_get($id);
}

respond(function () {
    require_admin();
    events_ensure_table();
    $method = request_method();
    $action = query_param('action') ?? '';

    if ($method === 'GET' && $action === 'match') {
        return ['path' => events_norm_path((string) query_param('path')), 'promotion' => events_promotion_for((string) query_param('path'))];
    }
    if ($method === 'GET') {
        if (isset($_GET['id'])) return event_get(query_id());
        $rows = array_map('events_row', db()->query('SELECT * FROM cms_events ORDER BY starts_at DESC, id DESC')->fetchAll());
        return ['events' => $rows, 'groups' => EVENT_GROUPS, 'now' => events_now()->format('Y-m-d H:i:s'), 'timezone' => EVENT_TZ];
    }
    if ($method === 'POST' && $action === 'duplicate') {
        $e = event_get(query_id());
        $copy = array_intersect_key($e, array_flip(['title', 'venue', 'city', 'summary', 'description', 'banner_desktop', 'banner_mobile', 'cta_label', 'cta_url', 'priority', 'starts_at', 'ends_at', 'promo_start', 'promo_end']));
        $copy['title'] = mb_substr('Copy of ' . $e['title'], 0, 150);
        $copy['status'] = 'draft';
        foreach (['guests', 'targets', 'excludes'] as $k) $copy[$k] = json_encode($e[$k], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return created(event_save(null, $copy));
    }
    if ($method === 'POST') {
        return created(event_save(null, event_validate(json_body())));
    }
    if ($method === 'PUT') {
        $id = query_id();
        event_get($id);
        return event_save($id, event_validate(json_body()));
    }
    if ($method === 'DELETE') {
        $id = query_id();
        event_get($id);
        db()->prepare('DELETE FROM cms_events WHERE id = ?')->execute([$id]);
        return ['deleted' => $id];
    }
    throw new ApiError('Method not allowed', 405);
});
