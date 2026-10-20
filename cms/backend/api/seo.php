<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * GET  /api/seo.php               -> SEO rows for all managed pages
 * GET  /api/seo.php?page=index    -> one page's SEO row
 * PUT  /api/seo.php?page=index    -> update (admin). Body: any of the fields below.
 */

const SEO_FIELDS = ['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image_url', 'canonical_url'];

function seo_find(string $slug): ?array
{
    $st = db()->prepare('SELECT page_slug, ' . implode(', ', SEO_FIELDS) . ', updated_at FROM pages_seo WHERE page_slug = ?');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

respond(function () {
    switch (request_method()) {
        case 'GET':
            $page = query_param('page');
            if ($page === null || $page === '') {
                $pages = cms_config()['pages'];
                $in = implode(',', array_fill(0, count($pages), '?'));
                $st = db()->prepare('SELECT page_slug, ' . implode(', ', SEO_FIELDS) . ", updated_at FROM pages_seo WHERE page_slug IN ($in) ORDER BY FIELD(page_slug, $in)");
                $st->execute([...$pages, ...$pages]);
                return $st->fetchAll();
            }
            $slug = managed_page($page);
            return seo_find($slug) ?? array_merge(['page_slug' => $slug], array_fill_keys(SEO_FIELDS, ''));

        case 'PUT':
            require_admin();
            $slug = managed_page(query_param('page'));
            $current = seo_find($slug) ?? array_fill_keys(SEO_FIELDS, '');
            $in = array_merge($current, json_body());

            $v = new Validator($in);
            $row = [
                'meta_title'       => $v->string('meta_title', 255),
                'meta_description' => $v->string('meta_description', 500),
                'meta_keywords'    => $v->string('meta_keywords', 500),
                'og_title'         => $v->string('og_title', 255),
                'og_description'   => $v->string('og_description', 500),
                'og_image_url'     => $v->url('og_image_url'),
                'canonical_url'    => $v->url('canonical_url'),
            ];
            $v->check();

            $cols = implode(', ', SEO_FIELDS);
            $marks = implode(', ', array_fill(0, count(SEO_FIELDS), '?'));
            $updates = implode(', ', array_map(fn ($f) => "$f = VALUES($f)", SEO_FIELDS));
            db()->prepare("INSERT INTO pages_seo (page_slug, $cols) VALUES (?, $marks) ON DUPLICATE KEY UPDATE $updates")
                ->execute([$slug, ...array_values($row)]);
            return seo_find($slug);

        default:
            throw new ApiError('Method not allowed', 405);
    }
});
