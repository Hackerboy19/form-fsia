# FSIA headless CMS

Content, images and SEO for five pages of fsia.in: `index.php`, `about.php`,
`our-teams.php`, `news-coverage.php` and `special-news-coverage.php`.

```
cms/
├── database/schema.sql          tables + seed (5 pages, current 27-person team)
├── backend/                     PHP 8.1+ JSON API (PDO, prepared statements)
│   ├── config.sample.php        copy to config.php (git-ignored)
│   ├── db.php                   PDO connection
│   ├── lib/bootstrap.php        CORS, auth, JSON + validation helpers
│   ├── lib/sanitize_html.php    allow-list sanitizer for article HTML
│   └── api/
│       ├── auth.php             token check for the admin sign-in
│       ├── seo.php              GET / PUT page SEO
│       ├── sections.php         GET / PUT page JSON sections
│       ├── teams.php            CRUD team members
│       ├── news.php             CRUD news articles
│       └── upload.php           image upload -> URL
├── admin/                       React 18 + Vite + Tailwind admin panel
│   └── src/
│       ├── lib/                 api client, auth, page/section definitions, toasts
│       ├── components/          Layout (sidebar), Field, ImageUpload, Modal, RichTextEditor, Login
│       └── pages/               Dashboard, SeoManager, HomepageEditor, PageEditor, TeamDirectory, NewsManager
├── integration/
│   ├── fsia_cms_client.php      fetch + cache helper for the public pages
│   └── index.example.php        how index.php reads SEO + sections
└── uploads-htaccess/.htaccess   copy into the upload folder
```

## 1. Database

```sql
CREATE DATABASE fsia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;  -- or use your existing DB
CREATE USER 'fsia_cms'@'localhost' IDENTIFIED BY '<strong password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON fsia.* TO 'fsia_cms'@'localhost';
```

```bash
mysql -u root -p fsia < cms/database/schema.sql
```

The CMS user only gets data permissions, so even a compromised API cannot drop
or alter tables. The script is safe to run twice.

## 2. Backend

1. Upload `cms/backend/` to the server, e.g. `public_html/cms/backend/`.
2. `cp config.sample.php config.php` and fill in the database login, then set
   `admin_token` to a long random value:
   `php -r 'echo bin2hex(random_bytes(32)), "\n";'`
3. Create the upload folder from `uploads.dir` (default `public_html/uploads/cms`),
   make it writable by PHP, and copy `uploads-htaccess/.htaccess` into it.
4. Check: `https://www.fsia.in/cms/backend/api/seo.php?page=index` returns JSON,
   and `https://www.fsia.in/cms/backend/config.php` returns 403.

On **nginx** (fsia.in runs nginx) the `.htaccess` files are ignored. Add the
equivalent rules to the site config:

```nginx
location ^~ /cms/backend/ {
    location ~ ^/cms/backend/api/[a-z]+\.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass php; }   # use your existing fastcgi_pass
    return 403;                               # config.php, db.php, lib/ ...
}
location ^~ /uploads/cms/ {
    location ~ \.php$ { return 403; }         # never execute anything uploaded
}
```

### API reference

Reads are public (the website needs them). Every write needs
`Authorization: Bearer <admin_token>` (or `X-Admin-Token: <token>`).
Hosts that block PUT/DELETE: send POST with `X-HTTP-Method-Override: PUT|DELETE`.

| Endpoint | Methods |
|---|---|
| `seo.php` | `GET` (all) · `GET ?page=` · `PUT ?page=` |
| `sections.php` | `GET ?page=` · `GET ?page=&key=` · `PUT ?page=&key=` body `{content:{…}, image_url}` |
| `teams.php` | `GET` (`?category=`, admin: `?include_inactive=1`) · `GET ?id=` · `POST` · `PUT ?id=` (partial) · `DELETE ?id=` |
| `news.php` | `GET ?type=&q=&page=&limit=` · `GET ?slug=` / `?id=` · `POST` · `PUT ?id=` (partial) · `DELETE ?id=` |
| `upload.php` | `POST` multipart field `image` → `{url, width, height}` |

Errors are JSON: `{"error": "...", "fields": {"name": "Required"}}` with 400/401/404/405/413/415/422.

## 3. Admin panel

```bash
cd cms/admin
cp .env.example .env.local     # set VITE_API_BASE to the backend's api/ URL
npm install
npm run dev                    # http://localhost:5173 (proxies /api to VITE_DEV_API_TARGET)
npm run build                  # static files in dist/
```

Upload `dist/` anywhere, e.g. `public_html/cms-admin/`. It uses hash URLs
(`/cms-admin/#/team`), so no server rewrite rules are needed. If the admin is
on a different origin than the API, add that origin to `allowed_origins`.
Sign in with the `admin_token`. It is kept in this tab's sessionStorage only.

Add or change Home/About fields in `admin/src/lib/pages.js`; no database change is needed.

## 4. Public pages

Copy `integration/fsia_cms_client.php` next to `index.php` and follow
`integration/index.example.php`: two calls at the top of the page, then print
the values with `fsia_e()`. Every value has a fallback, so a page looks the same
until it is edited in the admin, and keeps rendering if the API is down (last
good copy from the cache, otherwise the fallbacks). Changes appear within
`FSIA_CMS_CACHE_TTL` (5 minutes).

The same pattern works for the other pages, for example:

```php
$team = fsia_cms_get('teams.php') ?? [];                          // our-teams.php
$news = fsia_cms_get('news.php', ['type' => 'Special', 'limit' => 12])['items'] ?? [];
$post = fsia_cms_get('news.php', ['slug' => $_GET['slug'] ?? '']); // article page; content_html is already sanitized
```

## 5. Homepage (hero slider, calendar, ads, social, celebrities)

The live homepage is the React app from `fsia-home-clone-` (bundle in
`/fsia-home-assets/`). The admin's **Homepage** screen edits five rows in
`page_sections` for page `index`:

| Tab | section_key | content |
|---|---|---|
| Hero Slider | `hero_slides` | `{items: [{id, image, title, categoryTag, venue, subtitle, badge, alt, ctaText, ctaUrl, fitMode, startDate, endDate, timerDuration, showCountdown}]}` |
| Event Calendar | `calendar_events` | `{items: [{id, title, date, endDate, time, type, status, category, venue, city, description, eligibility, highlights[], keywords[], highlight, ctaText, ctaUrl, image}]}` |
| Advertisement | `advertisement` | `{enabled, label, rotateSeconds, items: [{id, title, image, mobileImage, link, alt, sizeMode, width, height, mobileWidth, mobileHeight, startDate, endDate, active}]}` |
| Social Media | `social_profiles` | `{items: [{id, name, profileName, iconName, url, description, badge, thumbnail}]}` |
| Celebrity Jury & Guests | `celebrities` | `{items: [{id, name, role, description, image, event}]}` |

`index.php` reads these rows straight from the database (no HTTP call) and prints
them as `window.__FSIA_CMS__` before the bundle. A tab that was never saved is
missing there and the homepage shows its built-in content; if the database is
unreachable the page renders with the built-in content too.

Slides and ads outside their start/end dates are hidden automatically.

`admin/src/lib/homeDefaults.json` is the built-in content, extracted from the
homepage source; the editor starts from it until a tab is saved.

## Security summary

- Every query uses prepared statements with native binding (`EMULATE_PREPARES` off);
  identifiers in SQL come from constants, never from input.
- Writes need the admin token (constant-time compare; 300 ms delay on failure);
  a placeholder or short token disables writes entirely.
- Input is validated per field (lengths, enums, dates, `http(s)`/root-relative URLs only).
- Page slugs are limited to the five managed pages.
- Article HTML passes an allow-list sanitizer: no script/style/iframe, no `on*`
  attributes, no `javascript:` URLs; `target=_blank` links get `rel=noopener`.
- Uploads: type detected from the bytes, JPEG/PNG/WebP only, size and pixel limits,
  re-encoded with GD (strips hidden payloads and EXIF), random file names, no execution
  in the upload folder.
- CORS is opt-in per origin; responses send `nosniff` and `no-store`.

Not included yet (worth adding before many people use the admin): per-user
accounts with an audit log, and login rate limiting at the web-server level.
