<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/events.php';

/*
 * Public: the promotion to show on a page of the website.
 * GET /api/promotions.php?path=/miss-assam.php  -> {"promotion": {...}} or {"promotion": null}
 * Read by /cms/promo.js. Only published, live events; never drafts or cancelled ones.
 */
respond(function () {
    if (request_method() !== 'GET') {
        throw new ApiError('Method not allowed', 405);
    }
    header('Cache-Control: public, max-age=60');
    header('Access-Control-Allow-Origin: *');
    return ['promotion' => events_promotion_for((string) (query_param('path') ?? '/'))];
});
