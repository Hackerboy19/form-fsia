# starindia admin add-on

`admin-enhancements.js` is the file served at `https://starindia.fsia.in/admin/admin-enhancements.js`
(already loaded by `/admin/index.html`). This copy is the live file of 2026-10-16
(`admin-enhancements.live-2026-10-16.js`, kept for rollback) plus one block at the end:

**Open a profile from the FSIA CMS.** A link like

    https://starindia.fsia.in/admin/#fsia-open=107550&q=azra&n=Azra&year=2026&tab=seo

opens Profiles after sign-in, sets the year, searches the slug (then the name), clicks
Edit on the row with `#107550` and opens the section (`identity`, `hero`, `stats`,
`details`, `services`, `achievements`, `gallery`, `articles`, `contact`, `vision`, `seo`).
It only clicks and types in the page; it never saves. A note in the corner says what
it is doing, or why it could not find the profile.

Tested against the real admin build (`/admin/assets/index-B40UsB4G.js`) with a
stand-in API and synthetic profiles: right profile when two match the search, slug
then name fallback, unknown id, signed out then signing in, and no write requests.

Upload: replace `/admin/admin-enhancements.js` on starindia.fsia.in. Rollback: upload
`admin-enhancements.live-2026-10-16.js` under the same name.
