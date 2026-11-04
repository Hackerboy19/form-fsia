# FSIA admin: redesign preview

A separate, self-contained front-end clone of the FSIA admin panel (`fsia.in/fsiaadmm21`), built to
try the new design on Vercel **before** anything touches the live PHP panel.

It is plain HTML, CSS and JavaScript. There is no build step, no server and no real data.

## What is in it

| Screen | What you can do |
| --- | --- |
| Dashboard | Registrations, follow-ups, payments waiting, fee collected (7/30/90 days), chart, pipeline, top forms, data check, latest registrations |
| Registrations | Search; quick views (My queue, New today, Needs follow-up, Waiting for payment, Fee paid, Data problems); filters for category, form, state, city, status, payment, OTP, data check, assigned person, age range and applied date, each shown as a removable chip; sort; bulk status, assign, download, delete |
| Profile panel | Every field the form saved, including fields from older form versions; data check with fix hints; offer & payment link (create, copy, send on WhatsApp, mark as paid); assignment; notes |
| Forms & Pages | SEO title, description, keywords, share title/description/image and fee box of all 71 forms, with live previews |
| OTP Log | Verification attempts with result filter |
| Team & Access | Team members with roles and states, editable permission matrix, activity log |
| Settings | Theme, density, shortcuts, reset sample data |
| Everywhere | `Ctrl/⌘ + K` quick search, Help guide written for the signed-in role, phone layout |

## Team roles

| Role | In short |
| --- | --- |
| Super Admin | Everything, including the team and access. Cannot be locked out. |
| Manager | All daily work. Cannot change team access. |
| Registration team | Calls applicants, updates status, sends payment links. Can be limited to some states. |
| Content editor | Form pages only. Does not see applicants. |
| Accounts | Confirms payments, downloads reports. |
| Viewer | Reads everything, changes nothing. |

The matrix in **Team & Access → Roles & access** can change any of this. In the preview the sign-in screen lets you
pick a member to see the panel exactly as they would.

> In the live panel every permission must be checked by the PHP backend on each request. Hiding a button in the
> browser is not security. `docs/multi-user-schema.sql` proposes the tables.

## Profiles from old and new forms

The forms have changed over time, so the database holds rows in different shapes. Found in this repository:

- Every form asks for the **full name**. Most post it as `fname`; Channel Partner and Bharat Couture Week post it as `firstName`. Both are read as the full name.
- `city` on most forms, `cityfsia` on India Fashion Week and Miss Universe
- `dob`, plus `birthday` / `birthmonth` / `birthyear` on the state forms
- Miss World (`miss-world-beauty-pageant.php` → `saveworld.php`, live only, not in this repository) posts `country`,
  `ccode` + `mobile_local` and a combined `mobile`, and `ncategory`. Numbers from other countries keep their country
  code for display, calls and WhatsApp.
- The live forms also post `fsia_source`, which the copies in this repository do not have yet, so the repository is
  behind the live site. It is read as "Found us via".
- Earlier versions of Celebrity PR Agency, Forever Fashion Week and India Fashion Week also asked for
  `education`, `totalmarks`, `instname`, `brandname`, `workexpr`, `address`, `kyc`, `comment`, `password`,
  `instagram`, `message`. Old rows still hold these.

`js/profile.js` reads every shape into one profile and **drops nothing**. Known extra fields get a readable label,
unknown columns are shown with their own name, old passwords are never shown or exported, and KYC files need the
KYC permission. Broken values such as `NULL`, `0000-00-00`, a 9-digit mobile or an age that does not match the date
of birth are flagged with a plain-language fix, never printed as "undefined".

## What is real and what is sample

Real, from this repository: the 71 form ids (`more_pages.page_name`, e.g. `100` = `miss-delhi`), the editable page
columns (`meta_title`, `descritpion` (sic), `meta_keyword`, `og_*`, `app_fee`, `fee_*`, `journey_note`) and the
registration field names above.

Sample: every person, number, date and payment; the team members; the status workflow and notes; the payment link
format. "Create link" makes a sample `pay.example.com` link; the live panel has to use the real payment gateway.

The live panel's source is not in this repository, so **feature parity is not verified**. Track it in
[`FEATURE-CHECKLIST.md`](FEATURE-CHECKLIST.md).

Edits are kept in the browser's `localStorage` only. **Settings → Reset** clears them.

## Tests

```bash
cd admin-redesign
node tests/data.test.js
```

Checks that no field from any form version is lost, that broken values are flagged, and that each role gets the
right access.

```bash
npm i --no-save playwright && npx playwright install chromium
node tests/e2e.js
```

Signs in as each role and runs through every screen: filters, payment link, old-form profile, CSV, team, access
rules, phone layout, and no JavaScript or CSP errors.

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

`vercel.json` adds `noindex` and security headers. `.vercelignore` keeps tests and docs out of the deploy.

If this repository is deployed to the live server on merge, keep this folder out of that deploy (or keep the
pull request unmerged and use the Vercel branch preview).

## Files

```
index.html          page shell
css/app.css         design tokens, layout, components, dark theme
js/core.js          DOM helper, icons, formatting, toast, dialogs, pager
js/profile.js       reads old and new form rows into one profile, data checks
js/data.js          sample data, real form list, roles and permissions
js/store.js         localStorage persistence, team, access, activity log
js/views/*.js       one file per screen
js/app.js           shell, router, access guard, quick search, help
tests/              data and browser tests
docs/               proposed database tables for team access
```

## Moving to the real PHP panel

Each screen reads and writes through `F.reg`, `F.store`, `F.team` and `F.audit` (`js/store.js`). Replace those with
calls to PHP endpoints that check the permission and write the activity log, or port the markup into the PHP
templates. `js/profile.js` has no browser dependencies and can be ported to PHP as is.
