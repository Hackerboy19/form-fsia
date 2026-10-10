<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * GET /api/sections.php?page=index            -> { "<section_key>": {content, image_url, updated_at}, ... }
 * GET /api/sections.php?page=index&key=hero   -> one section
 * PUT /api/sections.php?page=index&key=hero   -> create/replace (admin)
 *     Body: { "content": { ...any JSON object... }, "image_url": "/uploads/cms/..." }
 */

function section_key(?string $key): string
{
    if ($key === null || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $key)) {
        throw new ApiError('?key= must be 1-64 characters: a-z, 0-9, "_" or "-"');
    }
    return $key;
}

function section_row(array $r): array
{
    return [
        'content'    => json_decode($r['content'], true) ?? new stdClass(),
        'image_url'  => $r['image_url'],
        'updated_at' => $r['updated_at'],
    ];
}

function ensure_page_row(string $slug): void
{
    // page_sections has an FK to pages_seo; create an empty SEO row if needed.
    db()->prepare('INSERT IGNORE INTO pages_seo (page_slug) VALUES (?)')->execute([$slug]);
}

respond(function () {
    $slug = managed_page(query_param('page'));

    switch (request_method()) {
        case 'GET':
            $key = query_param('key');
            if ($key !== null && $key !== '') {
                $st = db()->prepare('SELECT content, image_url, updated_at FROM page_sections WHERE page_slug = ? AND section_key = ?');
                $st->execute([$slug, section_key($key)]);
                $r = $st->fetch();
                if (!$r) {
                    throw new ApiError('Section not found', 404);
                }
                return section_row($r);
            }
            $st = db()->prepare('SELECT section_key, content, image_url, updated_at FROM page_sections WHERE page_slug = ? ORDER BY section_key');
            $st->execute([$slug]);
            $out = [];
            foreach ($st->fetchAll() as $r) {
                $out[$r['section_key']] = section_row($r);
            }
            return (object) $out;

        case 'PUT':
            require_admin();
            $key = section_key(query_param('key'));
            $body = json_body();

            $v = new Validator($body);
            $image = $v->url('image_url');
            $content = $body['content'] ?? null;
            if (!is_array($content) || (array_is_list($content) && $content !== [])) {
                $v->addError('content', 'Must be a JSON object');
            }
            $json = json_encode($content ?: new stdClass(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false && strlen($json) > 512 * 1024) {
                $v->addError('content', 'Section content is larger than 512 KB');
            }
            $v->check();

            ensure_page_row($slug);
            db()->prepare(
                'INSERT INTO page_sections (page_slug, section_key, content, image_url) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE content = VALUES(content), image_url = VALUES(image_url)'
            )->execute([$slug, $key, $json, $image]);

            $st = db()->prepare('SELECT content, image_url, updated_at FROM page_sections WHERE page_slug = ? AND section_key = ?');
            $st->execute([$slug, $key]);
            return section_row($st->fetch());

        default:
            throw new ApiError('Method not allowed', 405);
    }
});
