# Handover: moving the site off our hosting onto the client's

The site is live on `theaccessexchange.com`, served from **our** Hostinger plan.
The goal is that the client ends up owning all three layers - domain, hosting,
WordPress - with nothing of ours left in the path.

This is a **real migration**, unlike the domain cutover in `GO-LIVE.md`. Files
and a database move between servers. The domain does not change, which removes
the single worst part of any WordPress migration: no URL rewriting is needed,
because the site answers on the same hostname before and after.

Do it now, before the client publishes anything. The moment they start adding
interviews, every hour between export and cutover is content that has to be
re-entered by hand.

---

## Phase 0 - Does the client have hosting?

Namecheap → log in → left sidebar **Hosting List** (also under **Products**).

| What you find                             | What happens                                        |
| ----------------------------------------- | --------------------------------------------------- |
| A Stellar / Stellar Plus / Business plan  | Go to Phase 1.                                      |
| Nothing                                   | Stop. Current setup stands. Send the message below. |

If they have no hosting, the site stays on our Hostinger until they buy a plan -
it is live and working, so there is no urgency, only cost. Tell them:

> The site is live and running on our hosting. To hand it over properly you'll
> want your own hosting plan so the site sits in your account, not ours. On
> Namecheap, **Stellar Plus** is the right tier - it includes the free migration
> service and has room for the interview library to grow. Once it's purchased we
> move the site across; nothing about the site changes and there's no downtime.

Also tell them about Elementor Pro at the same time - see Phase 3d. It is a
running cost they should hear about once, early, not discover later.

---

## Phase 1 - Prepare the source

On the current Hostinger install:

1. **Freeze it.** No content edits between export and cutover.
2. **Record the environment.** wp-admin → **Tools → Site Health → Info**:
   WordPress version, PHP version, and the full active plugin list with
   versions. The destination has to match or exceed the PHP version.
3. **Note the Elementor Pro version** (Plugins list). Your zip must be that
   version or newer - never older, or the saved page data won't parse.
4. **Fresh backup.** UpdraftPlus → **Backup Now** → all five components →
   download. This is separate from the migration file; it is the rollback.

---

## Phase 2 - Move it

### Option A - Namecheap's free migration service

Worth one request, because if they take it they own the outcome.

Namecheap → **Hosting List** → **Manage** → look for **Migration** / open a
support ticket at `support.namecheap.com` requesting a website transfer.

**Expect friction.** The service is built for cPanel-to-cPanel. Hostinger is not
cPanel, so they will likely ask for a files archive plus a database dump rather
than source-host credentials - which you already have from Phase 1.4. If they
decline, use Option B; do not wait more than a day or two on this.

### Option B - All-in-One WP Migration (the fallback that always works)

**B1. Install WordPress on their hosting.**

cPanel → **Softaculous Apps Installer** (or **WordPress Toolkit**) → WordPress →
Install. Install it on `theaccessexchange.com` even though DNS still points at
Hostinger - cPanel doesn't care that the domain resolves elsewhere yet.

Set an admin username and a strong password. This install gets overwritten by
the import, so nothing here is precious except the login.

**B2. Reach the new site before DNS moves.**

Two ways:

- **cPanel temp URL** - `http://<server>.web-hosting.com/~<cpaneluser>/`.
  Works, but WordPress redirects can fight it.
- **Hosts file** (cleaner, and what I'd use). Edit
  `C:\Windows\System32\drivers\etc\hosts` as Administrator, add:

  ```
  <their-cPanel-IP>   theaccessexchange.com
  <their-cPanel-IP>   www.theaccessexchange.com
  ```

  Now *your* machine resolves the domain to the new host while the rest of the
  world still sees Hostinger. Remove these two lines after cutover or you will
  spend an afternoon debugging a site only you can't see.

  The IP is in cPanel → right sidebar **General Information → Shared IP Address**.

**B3. Export from Hostinger.**

Old site → Plugins → Add New → **All-in-One WP Migration** → install → activate.
**All-in-One WP Migration → Export → Export To → File**. Download the `.wpress`.

Site has no uploads library yet, so expect well under 100 MB.

**B4. Import on their hosting.**

New site → install All-in-One WP Migration the same way →
**Import → Import From → File** → pick the `.wpress` → confirm the overwrite
warning.

If the file exceeds the free upload limit, install their free
**File Extension** add-on, or raise `upload_max_filesize` in cPanel →
**MultiPHP INI Editor**.

**B5. After the import.**

The import replaces the database, which means your login is now the **old
site's** login, not the one from B1.

1. Log in with the Hostinger credentials.
2. **Settings → Permalinks → Save Changes.** Twice. This rebuilds `.htaccess`
   and the rewrite rules for `/interviews/` and `/insights/`.
3. **Plugins → Add New → Upload Plugin** → your Elementor Pro zip → Install →
   Activate. (If the import already carried Pro across, confirm the version
   matches Phase 1.3 and skip.)
4. **Elementor → Tools → Regenerate Files & Data.** The stylesheet lives in
   Elementor's Custom CSS, compiled to a cached file that did not survive the
   move.
5. Walk the site on the hosts-file override. All ten pages, an interview page,
   the archive.

---

## Phase 3 - Cut over and hand ownership across

### 3a. Point the domain at their hosting

Namecheap → **Domain List** → `theaccessexchange.com` → **MANAGE** → **Domain**
tab → **NAMESERVERS**.

Currently **Custom DNS** with Hostinger's `dns-parking.com` pair. Change to:

- **Namecheap Web Hosting DNS** if the dropdown offers it (domain and hosting in
  the same account - it wires itself up), or
- **Custom DNS** with `dns1.namecheaphosting.com` / `dns2.namecheaphosting.com`

Green ✔ to save. Verify:

```
nslookup -type=ns theaccessexchange.com 8.8.8.8
```

Then remove the hosts-file lines from B2 and confirm the site still loads - now
you are seeing what everyone else sees.

**Downtime is near zero.** Both servers hold a working copy of the same site on
the same domain, so a visitor hitting the old one mid-propagation gets the site,
not an error. This is why the domain must not change during a host move.

### 3b. SSL on the new host

cPanel → **SSL/TLS Status** → tick the domain → **Run AutoSSL**. It cannot issue
until DNS resolves to that server, so do this after 3a propagates.

Then force HTTPS: cPanel → **Domains** → toggle **Force HTTPS Redirect**.

Load `https://theaccessexchange.com`, confirm the padlock.

### 3c. Transfer WordPress ownership

In wp-admin on the new site:

1. **Users → Add New** - their real email, role **Administrator**, tick *Send the
   new user an email about their account*.
2. **Settings → General → Administration Email Address** → change to their
   address → Save. **WordPress emails a confirmation link to the new address and
   the change does not take effect until it is clicked.** Tell them to click it;
   otherwise site-critical mail keeps coming to you.
3. Have them log in and confirm they can edit.
4. **Only then**, remove or demote your own admin accounts. Last step, after
   everything below verifies - locking yourself out of a half-checked site is a
   bad afternoon.

### 3d. Elementor Pro

Pro is installed from your zip and will run, but it will never update itself.
Tell the client plainly, in writing:

> Elementor Pro is installed and working. It's a paid plugin - to keep receiving
> updates you'll want your own licence (Elementor Pro *Essential*, around $59/yr).
> Until then the site works exactly as it does now, it just won't update. Once
> you have a key it goes in under Elementor → License and nothing else changes.

### 3e. Everything else that is now theirs

- **Domain** - already in their Namecheap account. Nothing to do.
- **Hosting** - their plan, their cPanel login.
- **Passwords** - rotate the Namecheap password (it shipped in the handoff PDF in
  plaintext) and change the WordPress admin password after your accounts are out.
- **Documents** - hand over `wordpress/OWNER-HANDOFF.md` (their day-to-day
  manual) and `wordpress/CF7-SMTP.md` if they ever touch the forms.

---

## Phase 4 - Verify, then decommission

Run the full checklist in `GO-LIVE.md` §9 against the new host. The additions
specific to a host move:

- [ ] Every form submits **and the email arrives** - new server, new sending IP.
      Re-test all of them. If WP Mail SMTP is configured the transport is
      unchanged, but test anyway.
- [ ] `/interviews/<slug>/` and the archive paging both work - proves
      `.htaccess` rebuilt.
- [ ] Images load - proves uploads came across.
- [ ] Elementor edits and saves cleanly on one page.
- [ ] PHP version on the new host is not older than the old one.

**Leave the Hostinger copy running for 30 days.** It costs nothing extra and it
is the only zero-effort rollback if something surfaces in week two. Diarise the
deletion; do not leave a stale duplicate of a client site online forever.

---

## Rollback

Before 3a: nothing has changed for the public. Abandon the new install.

After 3a: put the nameservers back to Hostinger's `dns-parking.com` pair. The old
site is still there, still complete, still on the same domain. Propagation is the
only cost.
