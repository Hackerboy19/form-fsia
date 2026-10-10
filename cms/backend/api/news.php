<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/sanitize_html.php';

/*
 * GET    /api/news.php?type=Special&page=1&limit=12  -> list (no content_html), newest first
 * GET    /api/news.php?slug=my-article                -> one article with content_html
 * GET    /api/news.php?id=7                           -> one article with content_html
 * POST   /api/news.php                                -> create (admin)
 * PUT    /api/news.php?id=7                           -> update; send only the fields to change (admin)
 * DELETE /api/news.php?id=7                           -> delete (admin)
 *
 * Without the admin token, articles dated in the future are hidden.
 */

const NEWS_TYPES = ['Standard', 'Special', 'Top10'];

function news_row(array $r): array
{
    $r['id'] = (int) $r['id'];
    return $r;
}

function news_find(string $col, int|string $value, bool $admin): ?array
{
    $sql = "SELECT * FROM news_coverage WHERE $col = ?" . ($admin ? '' : ' AND published_date <= CURDATE()');
    $st = db()->prepare($sql);
    $st->execute([$value]);
    $r = $st->fetch();
    return $r ? news_row($r) : null;
}

function slugify(string $text): string
{
    $s = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text), '-'));
    return substr($s !== '' ? $s : 'article', 0, 180);
}

/** Appends -2, -3 ... until the slug is free (ignoring the article being edited). */
function unique_slug(string $base, ?int $ignoreId): string
{
    $slug = $base;
    for ($n = 2; ; $n++) {
        $st = db()->prepare('SELECT id FROM news_coverage WHERE slug = ?' . ($ignoreId ? ' AND id <> ?' : ''));
        $st->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = substr($base, 0, 180) . '-' . $n;
    }
}

function news_validate(array $in, ?int $id): array
{
    $v = new Validator($in);
    $title = $v->string('title', 255, true);
    $slugIn = $v->string('slug', 191);
    if ($slugIn !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugIn)) {
        $v->addError('slug', 'Lowercase letters, numbers and single hyphens only');
    }
    $row = [
        'title'              => $title,
        'news_type'          => $v->enum('news_type', NEWS_TYPES, 'Standard'),
        'featured_image_url' => $v->url('featured_image_url'),
        'content_html'       => sanitize_article_html(is_string($in['content_html'] ?? null) ? $in['content_html'] : ''),
        'published_date'     => $v->date('published_date') ?? date('Y-m-d'),
    ];
    if (strlen($row['content_html']) > 1_000_000) {
        $v->addError('content_html', 'Article is larger than 1 MB');
    }
    $v->check();
    $row['slug'] = unique_slug($slugIn !== '' ? $slugIn : slugify($title), $id);
    return $row;
}

respond(function () {
    $admin = is_admin();

    switch (request_method()) {
        case 'GET':
            if (isset($_GET['id'])) {
                return news_find('id', query_id(), $admin) ?? throw new ApiError('Article not found', 404);
            }
            $slug = query_param('slug');
            if ($slug !== null && $slug !== '') {
                return news_find('slug', $slug, $admin) ?? throw new ApiError('Article not found', 404);
            }

            $where = [];
            $params = [];
            if (!$admin) {
                $where[] = 'published_date <= CURDATE()';
            }
            $type = query_param('type');
            if ($type !== null && $type !== '') {
                if (!in_array($type, NEWS_TYPES, true)) {
                    throw new ApiError('Unknown type. Use: ' . implode(', ', NEWS_TYPES));
                }
                $where[] = 'news_type = ?';
                $params[] = $type;
            }
            $q = query_param('q');
            if ($q !== null && $q !== '') {
                $where[] = 'title LIKE ?';
                $params[] = '%' . addcslashes($q, '%_\\') . '%';
            }
            $limit = max(1, min(100, (int) (query_param('limit') ?: 20)));
            $page = max(1, (int) (query_param('page') ?: 1));
            $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $count = db()->prepare("SELECT COUNT(*) FROM news_coverage$whereSql");
            $count->execute($params);

            // LIMIT/OFFSET are bound as integers, never concatenated from input.
            $st = db()->prepare("SELECT id, title, slug, news_type, featured_image_url, published_date, updated_at
                                 FROM news_coverage$whereSql ORDER BY published_date DESC, id DESC LIMIT ? OFFSET ?");
            foreach ($params as $i => $p) {
                $st->bindValue($i + 1, $p);
            }
            $st->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
            $st->bindValue(count($params) + 2, ($page - 1) * $limit, PDO::PARAM_INT);
            $st->execute();

            return [
                'items' => array_map('news_row', $st->fetchAll()),
                'total' => (int) $count->fetchColumn(),
                'page'  => $page,
                'limit' => $limit,
            ];

        case 'POST':
            require_admin();
            $row = news_validate(json_body(), null);
            $cols = array_keys($row);
            db()->prepare('INSERT INTO news_coverage (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
                ->execute(array_values($row));
            return created(news_find('id', (int) db()->lastInsertId(), true));

        case 'PUT':
            require_admin();
            $id = query_id();
            $current = news_find('id', $id, true) ?? throw new ApiError('Article not found', 404);
            $row = news_validate(array_merge($current, json_body()), $id);
            $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($row)));
            db()->prepare("UPDATE news_coverage SET $set WHERE id = ?")->execute([...array_values($row), $id]);
            return news_find('id', $id, true);

        case 'DELETE':
            require_admin();
            $st = db()->prepare('DELETE FROM news_coverage WHERE id = ?');
            $st->execute([query_id()]);
            if ($st->rowCount() === 0) {
                throw new ApiError('Article not found', 404);
            }
            return ['deleted' => true];

        default:
            throw new ApiError('Method not allowed', 405);
    }
});
