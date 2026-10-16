# SEO Regression Report (live, read-only)

- First run: 2026-10-15. Re-checked: 2026-10-16.
- Method: `curl` GET only. No URLs, redirects or indexing directives were changed by this work.

## Re-check 2026-10-16

| URL | Status | Title length | Canonical | H1 | Change since 10-15 |
|---|---|---|---|---|---|
| `/` | 200 | 77 | self | **0** | none |
| `/about`, `/about.php` | 200 / 200 | 56 | **missing** | 1 | none (BUG-13 open) |
| `/news-coverage` | 200 | **93** | self | 1 | none |
| `/supriya-jain-mrs-india-2021-id-15273` (in sitemap) | **404** | — | — | — | none (BUG-14 open) |
| `starindia.fsia.in/azra` | 200 | 68 | self (starindia) | **2** | new check: 3 broken image tags (BUG-19) |
| `www…/azra-chennai-title-holder-lawyer-2025` | 200 (10-15); time-out on first try today | 92 | self (www) | 2 | same person as above (BUG-20) |
| `robots.txt` | 200 | — | — | — | **Sitemap now `https://www.fsia.in/sitemap.xml`** (BUG-01 fixed) |
| `sitemap.xml` | 200 | — | — | — | 4,332 URLs; 9 duplicates; still lists the 404 above |

## Findings, ordered by impact

1. **BUG-20 (P2), duplicate profiles across hosts.** The starindia admin creates an "edited" copy of a profile on `starindia.fsia.in/<slug>`. The original stays on `www.fsia.in/<old-slug>`. Both pages are indexable and canonical to themselves.
   - Recommendation: choose the primary URL per person, then point the other at it with a canonical or a 301.
   - This changes indexing, so it needs the owner's approval. Not done.
2. **BUG-19 (P2), broken images on starindia profiles.** Hero image and logo fields hold a list of files instead of one file.
   - Fix it in the starindia admin (Hero / Identity).
   - The CMS lists every affected profile once the new zip is up.
3. **BUG-13 (P2):** `/about` duplicate with no canonical.
4. **BUG-14 (P2):** the sitemap has a 404 URL and 9 duplicates. Regenerate it from published profiles.
5. **BUG-17 (P2):** first-request time-outs.
6. **BUG-15 / 16 (P3):** H1 count and long titles.

## Regression status for the CMS work

- The CMS work adds no public URLs, no indexing directives and no page markup. Every change seen above existed before it.
- The deep-link script only runs inside `starindia.fsia.in/admin` (`noindex, nofollow`).
