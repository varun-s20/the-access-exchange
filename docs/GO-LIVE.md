# Go-live: temp Hostinger domain → theaccessexchange.com

**Situation.** The finished v2 site runs on
`https://violet-alligator-157811.hostingersite.com/` (Hostinger, hPanel).
`theaccessexchange.com` is registered at Namecheap and serves nothing (parked).
The live site stays on **this same Hostinger install** — nothing is copied
anywhere. This is a domain swap, not a server migration.

Because nothing is live on the real domain, there is **no downtime risk and no
301 redirect work**. The only thing that can go wrong is the database still
carrying the old hostname, which fails silently — links and Elementor images
keep pointing at the temp domain. §6 is the step that prevents that.

Logins used:

| Where       | URL                          | Account            |
| ----------- | ---------------------------- | ------------------ |
| Hostinger   | `https://hpanel.hostinger.com` | your Hostinger login |
| WordPress   | `<site>/wp-admin`            | WP admin user      |
| Namecheap   | `https://ap.www.namecheap.com` | client's Namecheap login |

---

## 0. Order of operations

1. Backup (§1) — before anything.
2. Pre-flight checks (§2) — decides which DNS method §4 uses.
3. Get the Hostinger target (nameservers or IP) (§3).
4. Point DNS at Namecheap (§4).
5. Wait for propagation (§5).
6. Change the website domain in hPanel + SSL (§6).
7. Fix URLs inside WordPress (§7). **This is the step that fails silently.**
8. Post-cutover settings, forms, SEO (§8).
9. Verify (§9).
10. Add the owner as a user (§10).

---

## 1. Backup

Two backups. The hPanel one is fast; the downloaded one is the one that saves
you if the account itself has a problem.

### 1a. Hostinger snapshot

1. `hpanel.hostinger.com` → **Websites** → the site → **Manage**.
2. Left sidebar → **Files → Backups**.
3. **Create new backup** (allowed once per 24h). Wait for it to appear in the
   list with today's date.
4. Same page: **Files backups** → **Prepare to download** → download the zip.
   Then **Database backups** → **Prepare to download** → download the `.sql`.

### 1b. Downloaded copy you control

Belt and braces, takes 3 minutes:

- **Files:** left sidebar → **Files → File Manager** → open `public_html` →
  select all → **Compress** → download the archive.
- **Database:** left sidebar → **Databases → phpMyAdmin** → open the site's
  database → **Export** tab → *Quick* → *SQL* → **Go**.
  (Database name is in `public_html/wp-config.php` if more than one is listed.)

### 1c. Sanity check the backup

Open the zip and confirm `wp-content/` is inside, and that the `.sql` is not
0 KB. Do not skip this — a 0-byte export is the classic silent failure.

No full restore drill needed here: nothing is being moved between servers, so
the install itself is never at risk. The backup exists to undo §7 if the
search-replace goes wrong.

---

## 2. Pre-flight checks

### 2a. Does the domain have email on it? (decides §4)

Namecheap → **Domain List** → `theaccessexchange.com` → **MANAGE** →
**Advanced DNS** tab. Look at **MAIL SETTINGS** and any `MX` records.

| What you see                                                     | Use which DNS method                 |
| ---------------------------------------------------------------- | ------------------------------------ |
| "No Email Service", no MX records                                | **Method A — nameservers** (simpler) |
| Google Workspace / Private Email / Titan / Zoho MX records present | **Method B — A record** (keeps mail)  |

> **Warning.** Switching nameservers (Method A) hands *all* DNS for the domain
> to Hostinger and drops every record Namecheap currently holds, MX included.
> If the client already receives mail at `@theaccessexchange.com`, Method A
> breaks it the moment it propagates. Either use Method B, or recreate the MX
> records inside Hostinger's DNS zone **before** switching.

Screenshot the whole Advanced DNS tab before changing anything either way.

### 2b. Are the WordPress URLs editable?

WP admin → **Settings → General**. If **WordPress Address** and **Site Address**
are greyed out, `wp-config.php` defines `WP_HOME` / `WP_SITEURL` and you will
edit that file in §7 instead. Just note which it is now.

### 2c. Plan allows the domain

hPanel → **Websites**. On a Single-website plan the temp site *is* the one
website — you are renaming it, which is fine. Do not "Add website"; that would
create a second empty install.

---

## 3. Get the Hostinger target

hPanel → **Websites** → site → **Manage** → left sidebar → **Domains** (or
**Dashboard**, right-hand *Website details* panel).

Note down:

- **Nameservers** — normally `ns1.dns-parking.com` and `ns2.dns-parking.com`
  (Hostinger shows the pair for your account; use what it shows).
- **IP address** of the site — a v4 address like `123.45.67.89`. Also visible
  under **Advanced → DNS Zone Editor**.

---

## 4. Point the domain at Hostinger (Namecheap)

Log in: `https://ap.www.namecheap.com` → **Domain List** →
`theaccessexchange.com` → **MANAGE**.

### Method A — nameservers (no email on the domain)

1. **Domain** tab → **NAMESERVERS** section → dropdown → **Custom DNS**.
2. Row 1: `ns1.dns-parking.com`  Row 2: `ns2.dns-parking.com`
3. Click the green ✔ to save.

DNS for the domain is now Hostinger's. Records get managed in
hPanel → **Domains → DNS Zone Editor** from here on, not Namecheap.

### Method B — A record (domain has mail on it)

Leave nameservers on **Namecheap BasicDNS**. Then **Advanced DNS** tab:

1. Delete the parking entries: the `URL Redirect Record` on host `@` and the
   `CNAME Record` `www → parkingpage.namecheap.com`.
2. **ADD NEW RECORD**:

   | Type       | Host | Value                     | TTL       |
   | ---------- | ---- | ------------------------- | --------- |
   | A Record   | `@`  | *Hostinger IP from §3*    | Automatic |
   | A Record   | `www`| *same Hostinger IP*       | Automatic |

3. Save each row with the green ✔. Leave every MX record untouched.

---

## 5. Wait for propagation

Check at `https://dnschecker.org` — enter `theaccessexchange.com`, type `A`.
Wait until most locations return the Hostinger IP. Usually 15–60 minutes,
officially up to 48h. Do not start §6 until it resolves.

Command-line check: `nslookup theaccessexchange.com 8.8.8.8`

---

## 6. Change the website domain in hPanel

hPanel → **Websites** → the site → **Manage** → left sidebar → **Domains** →
**Change website domain** (Hostinger sometimes labels it *Change domain*).

1. Enter `theaccessexchange.com`.
2. Confirm. Hostinger updates the virtual host, moves the site to the new
   domain, and runs its own URL update pass on the WordPress database.

> **This releases the temp URL.** `violet-alligator-157811.hostingersite.com`
> stops serving the site after this. Rolling back means changing the domain
> back here and re-running §7 in reverse — possible, but it is the one step
> with a cost. Do not run it before §5 resolves.

### SSL

Left sidebar → **Security → SSL**. Hostinger normally issues a free Let's
Encrypt certificate automatically within minutes of the domain resolving. If it
has not: **Install SSL** → wait → refresh. Once the certificate is *Active*,
turn on **Force HTTPS**.

Confirm `https://theaccessexchange.com` loads with a padlock before continuing.

---

## 7. Fix URLs inside WordPress — the silent-failure step

Hostinger's domain change handles the two obvious URLs. It does **not**
reliably rewrite URLs stored inside serialized data, which is where Elementor
keeps every image, background and link on this build. Do all three of these.

### 7a. Settings

Log in at `https://theaccessexchange.com/wp-admin`.

**Settings → General** — both **WordPress Address (URL)** and
**Site Address (URL)** must read `https://theaccessexchange.com`
(no `www`, no trailing slash). Save.

If the fields are greyed out (see §2b): hPanel → **Files → File Manager** →
`public_html/wp-config.php` → edit the two lines:

```php
define( 'WP_HOME',    'https://theaccessexchange.com' );
define( 'WP_SITEURL', 'https://theaccessexchange.com' );
```

### 7b. Database search-replace

Plugins → Add New → **Better Search Replace** → install → activate.

**Tools → Better Search Replace:**

| Field       | Value                                         |
| ----------- | --------------------------------------------- |
| Search for  | `violet-alligator-157811.hostingersite.com`   |
| Replace with| `theaccessexchange.com`                       |
| Tables      | select **all** tables                         |
| Dry run     | **ticked** for the first pass                 |

Run it. Read the count of rows it *would* change. Then untick **Run as dry
run** and run it again for real.

Second pass, same settings, for any leftover scheme mismatch:

| Search for  | `http://theaccessexchange.com`  |
| Replace with| `https://theaccessexchange.com` |

Deactivate and delete Better Search Replace when done — it has no business
staying on a live site.

### 7c. Elementor

**Elementor → Tools → Replace URL** tab:

- Old URL: `https://violet-alligator-157811.hostingersite.com`
- New URL: `https://theaccessexchange.com`
- **Replace URL**

Then **Elementor → Tools → General** tab → **Regenerate Files & Data**.

### 7d. Flush rewrite rules

**Settings → Permalinks** → **Save Changes** (no edits needed). This rebuilds
the rewrite rules for the plugin's `/interviews/` and `/insights/` post types.
Skipping it gives 404s on every episode page.

---

## 8. Post-cutover

### 8a. Let search engines in

- **Settings → Reading** → untick **Discourage search engines from indexing
  this site** → Save. (It was on for the temp domain — `SEO-YOAST.md` §303.)
- hPanel → check no **Maintenance mode** / **Coming soon** toggle is left on.

### 8b. Purge every cache layer

1. hPanel → **Performance → Cache Manager** → **Purge all**.
2. Any caching plugin on the site (LiteSpeed / WP Rocket / Autoptimize) →
   purge + clear CSS/JS cache.
3. Hard-refresh your own browser (`Ctrl+Shift+R`).

### 8c. Forms and mail

The from-address must now live on the real domain or Gmail will drop it.

1. **WP Mail SMTP → Settings** → set **From Email** to the real mailbox on
   `@theaccessexchange.com`, **From Name** to the site name.
2. If the mailer is Brevo/Postmark/SendGrid, re-verify the sending domain there
   and add the SPF/DKIM records it gives you — at **Namecheap → Advanced DNS**
   under Method B, or **hPanel → DNS Zone Editor** under Method A.
3. **WP Mail SMTP → Tools → Email Test** → send to a Gmail address. Must land in
   the inbox, not spam.
4. Submit **every** form on the live domain once (`CF7-SMTP.md` §6 lists them)
   and confirm each notification arrives.

### 8d. SEO

- The build's JSON-LD and canonicals already hardcode
  `https://theaccessexchange.com` — they were wrong on the temp domain and are
  correct now. Nothing to change.
- **Yoast SEO → Settings** → confirm the site URL, then load
  `https://theaccessexchange.com/sitemap_index.xml` and check it lists the real
  domain throughout.
- Google Search Console → **Add property** → `https://theaccessexchange.com` →
  verify (DNS TXT record at Namecheap/Hostinger, or via Yoast) → **Submit
  sitemap** `sitemap_index.xml`.
- Google Analytics → update the property's site URL, or create the property now
  if it did not exist.

### 8e. www

The build is canonical on the bare domain. Load `https://www.theaccessexchange.com`
and confirm it redirects to `https://theaccessexchange.com`. If it does not:
hPanel → **Domains → Redirects** → add `www` → non-www, 301.

---

## 9. Verify

Run through this on the live domain, in a private window:

- [ ] `https://theaccessexchange.com` loads, padlock, no mixed-content warning
      (check the browser console — zero errors).
- [ ] All ten pages load: `/`, `/interview-series/`, `/universities/`,
      `/experiences/`, `/coaching/`, `/guests-partners/`, `/get-involved/`,
      `/about/`, `/privacy/`, `/terms/`.
- [ ] Header nav, footer nav, mobile menu all work.
- [ ] Every image renders — a broken image means §7b missed a table.
- [ ] View source, search for `hostingersite` — **zero hits**. This is the real
      test of §7.
- [ ] An interview episode page loads at `/interviews/<slug>/` and the YouTube
      embed plays on click.
- [ ] Search / archive paging works.
- [ ] Every form submits and the email arrives.
- [ ] `https://theaccessexchange.com/sitemap_index.xml` is clean.
- [ ] `https://www.theaccessexchange.com` redirects to the bare domain.
- [ ] Run `python wordpress/tests/check.py` locally — still green.

---

## 10. Give the owner access

### WordPress

WP admin → **Users → Add New**:

- Username: their choice, Email: their real address
- Role: **Administrator**
- Tick **Send the new user an email about their account**

Then log in as them once to confirm, and hand over `wordpress/OWNER-HANDOFF.md`
— it is the tutorial for everything they will actually do day to day.

### Hostinger (only if they need server access)

hPanel → profile menu (top right) → **Account Sharing** → **Add user** → their
email → they accept by email. Do this only if the owner genuinely needs hosting
access; WP admin covers all normal content work.

### Security housekeeping

- Rotate the Namecheap password — the handoff PDF carried it in plaintext
  (`docs/BUILD-PLAN.md` §Security).
- Turn on 2FA on Namecheap and Hostinger.
- Remove any developer admin accounts that are no longer needed, and change the
  shared WP admin password.
- Delete the downloaded backups from any shared folder once the site is stable.

---

## Rollback

If something is badly wrong after §6:

1. hPanel → **Domains → Change website domain** → set it back to the
   `hostingersite.com` temp domain Hostinger assigns.
2. Re-run §7b with search/replace reversed.
3. Or, faster: hPanel → **Files → Backups** → restore the §1a snapshot, then
   re-point the domain.

Namecheap changes reverse on their own terms: put the nameservers back to
**Namecheap BasicDNS**, or re-add the parking records.
