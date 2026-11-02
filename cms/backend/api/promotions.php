<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/events.php';

/*
 * Public: the promotions to show on a page of the website.
 * GET  /api/promotions.php?path=/miss-assam.php
 *      -> {"promotion": {...} | null, "promotions": [{...}, ...]}   (best first; they take turns)
 * POST /api/promotions.php?track=view|click|wa&id=5
 *      -> counts one bar view, "Register" click or WhatsApp click (live events only)
 * Read by /cms/promo.js. Only published, live events; never drafts or cancelled ones.
 */
respond(function () {
    header('Access-Control-Allow-Origin: *');
    $method = request_method();
    if ($method === 'POST' && isset($_GET['track'])) {
        header('Cache-Control: no-store');
        $id = (int) ($_GET['id'] ?? 0);
        return ['ok' => $id > 0 && events_track($id, (string) $_GET['track'])];
    }
    if ($method !== 'GET') {
        throw new ApiError('Method not allowed', 405);
    }
    header('Cache-Control: public, max-age=60');
    $list = events_promotions_for((string) (query_param('path') ?? '/'));
    return ['promotion' => $list[0] ?? null, 'promotions' => $list];
});
