# SEO Regression Report (live, read-only)

Date: 2026-10-15.
- Method: `curl` GET with a custom user agent. Status, title, meta description, canonical, robots meta, H1 count and JSON-LD count were read from the HTML.
- Load: about 45 requests in total, spaced out.
- No URLs, redirects or indexing directives were changed.

## Pages checked

| URL | Status | Title length | Description length | Canonical | Robots meta | H1 | JSON-LD |
|---|---|---|---|---|---|---|---|
| `/` | 200 | 77 | 179 | self | index, follow, max-image-preview:large | **0** | 1 |
| `fsia.in/`, `http://www.fsia.in/` | 301 → `https://www.fsia.in/` | — | — | — | — | — | — |
| `/about` | 200 | 56 | 187 | **missing** | — | 1 | 0 |
| `/about.php` | 200 (same page) | 56 | 187 | **missing** | — | 1 | 0 |
| `/contact` | 200 (first try timed out) | 38 | 165 | self | — | 1 | 0 |
| `/our-teams.php` | 200 | 37 | 144 | self | — | 1 | 0 |
| `/quickapply` | 200 | 30 | 125 | self | — | 1 | 1 |
| `/quickapply.php` | 301 → `/quickapply` | — | — | — | — | — | — |
| `/news-coverage` | 200 | **93** | 121 | self | — | 1 | 0 |
| `/special-news-coverage` | 200 | 30 | 175 | self | — | 1 | 0 |
| `/miss-assam.php` (and `?x=1`) | 200 | 64 | 123 | self (query removed) | index, follow | 1 | 4 |
| `/miss-universe-registration.php` | **404** (in the repo, not live) | — | — | — | noindex,follow | — | — |
| `/socialmediaprofile.php` | 301 → `/contact.php` | — | — | — | — | — | — |
| `/miss-india-contestant-2025-nishita-tahilramani-aurangabad` | 200 | 61 | 95 | self | index, follow | **2** | 0 |
| `/azra-chennai-title-holder-lawyer-2025` | 200 | **92** | 118 | self | index, follow | **2** | 1 |
| `/inspirational-dance-performer-kolkata-pritam-dhar` | 200 | **89** | 141 | self | index, follow | **2** | 1 |
| `/this-page-does-not-exist-xyz` | 404 (first try timed out) | — | — | — | noindex,follow | — | — |

## robots.txt and sitemap

| Check | Result |
|---|---|
| `robots.txt` | `Allow: /`, `Disallow: /admin/`, **`Sitemap: https://influencer.fsia.in/sitemap.xml`** |
| `https://www.fsia.in/sitemap.xml` | 200; 4,332 URLs; 164 end in `.php`; **9 duplicates**; all on www.fsia.in |
| `https://influencer.fsia.in/sitemap.xml` | 200; 6 URLs, all on the influencer subdomain |
| 20 random sitemap URLs | 19 return 200, **1 returns 404** (`/supriya-jain-mrs-india-2021-id-15273`). 7 timed out on the first pass but returned 200 on retry. |

## Findings, ordered by impact

1. **BUG-01 (P1):** www's `robots.txt` announces only the influencer sitemap.
   - Fix: replace the `Sitemap:` line with `Sitemap: https://www.fsia.in/sitemap.xml`. Keep the influencer line only if that subdomain has no `robots.txt` of its own.
   - Impact: search engines learn about all 4,332 main-site URLs. Nothing is removed from the index.
2. **BUG-13 (P2):** `/about` and `/about.php` are duplicates with no canonical. Add a canonical to `/about` on `about.php`, and list `/about` in the sitemap.
3. **BUG-14 (P2):** the sitemap includes 404s and duplicates. Regenerate it from approved profiles only.
4. **BUG-17 (P2):** intermittent time-outs hurt crawling. The cause is the long-term `.htaccess` routing (plan Phase 5).
5. **BUG-15 / BUG-16 (P3):**
   - The homepage has no H1.
   - Profile pages have two H1s.
   - Titles over ~65 characters are cut off: the homepage, news-coverage and achiever profiles.

## Regression status for the new CMS work

- The new admin features add no public pages, URLs or indexing directives.
- Nothing here was caused by them.
- After the upload, re-run this check on the same URL list (deployment checklist step 6).
