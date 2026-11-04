# Feature parity checklist

Use this list before the new panel replaces the live one at `fsia.in/fsiaadmm21`.
A row is done only when it is ticked in **both** columns:

- **Preview**: the feature exists in this redesign and passes `node tests/data.test.js` and `node tests/e2e.js`.
- **Live verified**: the same feature was checked against the live panel's source and database, with real rows.

The live panel's source is not in this repository yet, so every "Live verified" box is still open.
Anything the live panel does that is missing below must be added as a new row first.

## Registrations and profiles

| Feature | Preview | Live verified |
| --- | :-: | :-: |
| List every registration of every form (64 state forms + 8 others) | ✅ | ☐ |
| Search by name, mobile, email, city | ✅ | ☐ |
| Filter by category, form, state, city, status, payment, OTP, data check, assigned person, age range, applied date | ✅ | ☐ |
| Same filters as the current panel (needs screenshots or source) | ☐ | ☐ |
| Open a full profile with every field the form saved | ✅ | ☐ |
| Fields from **older form versions** (education, totalmarks, instname, brandname, workexpr, address, kyc, comment, instagram, message, qualification, skills, regtype, category, fsia_category, fsia_model_sub, reg_type) still shown | ✅ | ☐ |
| Different column names for the same field (`fname` / `firstName`, `city` / `cityfsia`, `dob` / birthday+birthmonth+birthyear) read correctly | ✅ | ☐ |
| Unknown or new database columns still shown, never dropped | ✅ | ☐ |
| Broken values (`NULL`, `0000-00-00`, short mobile, bad email, age ≠ DOB) flagged with a fix hint, never shown as "undefined" | ✅ | ☐ |
| Old-form passwords never displayed or exported | ✅ | ☐ |
| KYC files visible only to roles with KYC access | ✅ | ☐ |
| Photo shown in the profile | ☐ (sample data has no photos) | ☐ |
| Change status (single and bulk), notes | ✅ | ☐ |
| Delete with confirmation | ✅ | ☐ |
| CSV download with all fields, payment and older-form columns | ✅ | ☐ |

## Offer and payment

| Feature | Preview | Live verified |
| --- | :-: | :-: |
| Offer name and amount per applicant (default from the form's fee box) | ✅ | ☐ |
| Create the payment link (live: with the real payment gateway) | ✅ sample link only | ☐ |
| Paste an existing payment link, https only | ✅ | ☐ |
| Copy link, send on WhatsApp with a ready message | ✅ | ☐ |
| Mark as paid with bank / UPI reference, undo by mistake | ✅ | ☐ |
| Payment status filter: no link / link sent / paid | ✅ | ☐ |
| Gateway callback marks the payment as paid automatically | ☐ | ☐ |
| Any other payment / offer URL function the live panel has | ☐ | ☐ |

## Team and access

| Feature | Preview | Live verified |
| --- | :-: | :-: |
| One sign-in per team member | ✅ (preview: pick a member) | ☐ |
| Roles: Super Admin, Manager, Registration team, Content editor, Accounts, Viewer | ✅ | ☐ |
| Editable permission matrix, Super Admin locked | ✅ | ☐ |
| Limit a member to some states | ✅ | ☐ |
| Assign registrations to a member, "My queue" | ✅ | ☐ |
| Turn access off, remove a member, keep at least one Super Admin | ✅ | ☐ |
| Activity log: who did what and when | ✅ | ☐ |
| Permissions enforced on the **server** for every request | ☐ | ☐ |
| Passwords hashed, sessions regenerated on sign-in, CSRF tokens on every form | ☐ | ☐ |

## Pages, OTP, everything else

| Feature | Preview | Live verified |
| --- | :-: | :-: |
| Edit SEO title, description, keywords, share title/description/image, fee box for every form | ✅ | ☐ |
| Copy the fee box to every page in a group | ✅ | ☐ |
| Upload the share image to `/uploads` | ☐ (preview shows the file only) | ☐ |
| OTP log with result filter | ✅ | ☐ |
| Dashboard: registrations, follow-ups, payments waiting, fee collected, data check | ✅ | ☐ |
| Light / dark theme, phone layout, keyboard shortcuts, help guide | ✅ | ☐ |
