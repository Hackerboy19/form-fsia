# FSIA admin: redesign preview

A separate, self-contained front-end clone of the FSIA admin panel (`fsia.in/fsiaadmm21`), built to
try the new design on Vercel **before** anything touches the live PHP panel.

It is plain HTML, CSS and JavaScript. There is no build step, no server and no real data.

## What is in it

| Screen | What you can do |
| --- | --- |
| Dashboard | KPIs (7/30/90 days), registrations-per-day chart, pipeline, top forms, categories, latest registrations |
| Registrations | Search, filter by category / form / state / status / OTP / date, sort, pagination, bulk status change, bulk delete with undo, CSV export, detail panel with WhatsApp / call / email / notes |
| Forms & Pages | Edit the SEO title, description, keywords, share title/description/image and fee box of every form, with live Google, WhatsApp and fee-box previews and "needs attention" flags |
| OTP Log | Verification attempts with result filter and mobile search |
| Settings | Light/dark theme, table density, keyboard shortcuts, reset sample data |
| Everywhere | `Ctrl/⌘ + K` quick search, `/` to focus search, mobile layout, keyboard and screen-reader support |

## Where the sample data comes from

Real, taken from the public forms in this repository:

- the 71 form ids (the `more_pages.page_name` values, for example `100` = `miss-delhi`) and slugs
- the editable page columns: `meta_title`, `descritpion` (sic), `meta_keyword`, `og_title`, `og_description`,
  `og_image`, `app_fee`, `fee_title`, `fee_heading`, `fee_subtitle`, `journey_note`
- the registration fields: `fname`, `mobile`, `email`, `dob`, `age`, `city`, `state`, `landing`

Invented for the preview, so check them against the live panel:

- every person, mobile number, e-mail address and date (generated, `example.com` addresses)
- the **status workflow** (New, Contacted, Shortlisted, Fee paid, Rejected) and **internal notes**: these are
  suggested additions, not features read from the live panel
- the OTP result labels and the dashboard numbers

> The live panel's source is not in this repository, so feature parity has not been checked. Anything the live
> panel does that is not listed above still has to be carried over once its source is available.

Edits (status, notes, deletions, page settings) are kept in the browser's `localStorage` only. **Settings → Reset**
clears them. The sign-in screen accepts any input: nothing is sent or stored.

## Run locally

```bash
cd admin-redesign
python3 -m http.server 4173
```

Open <http://127.0.0.1:4173>.

## Deploy to Vercel

1. In Vercel choose **Add New → Project** and import this GitHub repository.
2. Set **Root Directory** to `admin-redesign`.
3. Set **Framework Preset** to `Other`. Leave the build command and output directory empty.
4. Deploy. Every push to the branch gets its own preview URL.

`vercel.json` adds `noindex` and security headers (CSP, no framing), so the preview is kept out of search engines.

If this repository is deployed to the live server on merge, keep this folder out of that deploy (or keep the
pull request unmerged and use the Vercel branch preview).

## Files

```
index.html          page shell
css/app.css         design tokens, layout, components, dark theme
js/core.js          DOM helper, icons, formatting, toast, confirm dialog, pager
js/data.js          sample data and the real form list
js/store.js         localStorage persistence
js/views/*.js       one file per screen
js/app.js           shell, router, quick search, theme
vercel.json         headers and clean URLs
```

## Moving to the real PHP panel

Each screen reads and writes through `F.reg` and `F.store` (`js/store.js`) and `F.data` (`js/data.js`).
Replacing those with calls to the existing PHP handlers, or porting the markup into the PHP templates, is the
only wiring needed. The column names above are kept as they are in the database to make that mapping direct.
