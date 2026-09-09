# Forms - Contact Form 7 + SMTP

Six forms, one plugin, one mail transport. Nothing on the site posts anywhere
until this is done - the shortcodes in the page files are placeholders.

| #   | Form                       | Page                                 | Placeholder to replace     |
| --- | -------------------------- | ------------------------------------ | -------------------------- | --- |
| 4.1 | TAE - Contact · Guest      | `Guest enquiry - [your-name]`        | RETIRED - replaced by 4.13 | -   |
| 4.2 | TAE - Contact · University | `University enquiry - [institution]` | RETIRED - replaced by 4.13 | -   |
| 4.3 | TAE - Contact · Press      | `Press - [outlet] - [your-name]`     | RETIRED - replaced by 4.13 | -   |
| 4.4 | TAE - Contact · Other      | `Website message - [your-name]`      | RETIRED - replaced by 4.13 | -   |
| 4.5 | TAE - Partnership enquiry  | `/university-partnerships/`          | line 326                   |
| 4.6 | TAE - Be a guest           | `/interview-series/`                 | line 382                   |

Order of work: **§1 SMTP → §2 plugins → §3 three snippets → §4 the six forms,
one at a time → §6 test.** Do SMTP first, so the first form you test already
proves the whole path.

Each form in §4 is one complete unit - form fields, mail settings, branded HTML
email, thank-you copy and the exact line its shortcode replaces. Finish one
before starting the next; §5 is only a look-up table for when you lose your place.

---

## 1. SMTP

WordPress' built-in `wp_mail()` hands the message to the host's PHP `mail()`.
That mail is unsigned, comes from a shared server IP, and is routinely dropped
by Gmail and Outlook without a bounce. Every form on the site goes through
`wp_mail()`, so fixing it once fixes all six.

Plugin: **FluentSMTP**, free. Every provider is included - there is no paid
tier gating the mailer you need.

### 1.1 Mailer

The mailbox is **Microsoft 365**, so mail leaves through the mailbox itself over
OAuth (Microsoft Graph). Sent mail lands in that mailbox's Sent folder and
inherits Microsoft's own SPF and DKIM, so **no DNS work is required** and the
domain's existing Namecheap mail forwarding is untouched.

Two dead ends worth naming, so nobody rediscovers them:

| Route                                          | Status                                                                                                        |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Basic-auth SMTP to `smtp.office365.com`        | **Dead.** Microsoft permanently disabled Basic Auth for SMTP AUTH client submission in September 2025.        |
| WP Mail SMTP's own Outlook mailer              | **Pro only.** FluentSMTP does the same OAuth for free, which is why it is the plugin here.                     |
| A relay (Brevo, Postmark, SendGrid)            | Works, but cannot sign `@…onmicrosoft.com` - Microsoft owns that domain. Only an option once §1.6 is done.     |

### 1.2 Install and configure

Deactivate WP Mail SMTP first if it is present. **Never leave both active** -
two plugins filtering PHPMailer fight over the config and you get silent
failures that look like DNS problems.

**Plugins → Add New → "FluentSMTP" → Install → Activate**, then
**Settings → FluentSMTP → Add Connection**:

- **From Email** - `connect@theaccessexchange.onmicrosoft.com`
- **Force From Email** - ON
- **From Name** - `The Access Exchange`, Force From Name ON
- **Connection Provider** - Outlook / Office 365

It prints a **Redirect/Callback URL**. Copy it verbatim, then in
[entra.microsoft.com](https://entra.microsoft.com) →
**Applications → App registrations → New registration**:

- Name `TAE Website Mailer`
- Supported account types: **this organizational directory only**
- Redirect URI: platform **Web**, paste the callback URL

Then:

1. Overview → copy **Application (client) ID**
2. **Certificates & secrets → New client secret** → copy the **Value** column
   immediately. Not the Secret ID. It is shown once and never again.
3. **API permissions → Microsoft Graph → Delegated permissions** →
   `Mail.Send`, `offline_access`, `User.Read` → **Grant admin consent**

Paste the ID and secret into FluentSMTP → Save → **Authorize**, signing in as
`connect@theaccessexchange.onmicrosoft.com` itself, **not** your own admin
account. OAuth is delegated: it sends as whoever authorises.

> **The single most common mistake:** setting the From address to the visitor's
> email so replies "just work". Do not. Your server is not authorised to send as
> `someone@gmail.com`, so SPF and DKIM both fail and the mail goes to spam or is
> rejected outright. The From address is always your own domain; the visitor's
> address goes in **Reply-To** (§5). _Force From Email_ being ON is what stops
> any plugin overriding this later.

Keep credentials encrypted at rest. In `wp-config.php`, above the
`/* That's all, stop editing! */` line, and **before** you save the connection:

```php
define( 'FLUENTMAIL_ENCRYPT_SALT', 'a-long-random-string' );
```

Generate the string from any line of
<https://api.wordpress.org/secret-key/1.1/salt/>. Confirm the constant name
against FluentSMTP's current docs before relying on it.

> **The client secret expires - 24 months maximum.** When it lapses every form
> stops delivering silently, with no bounce and no error on screen. Put it in a
> calendar at 22 months. This is the single most likely way this site breaks
> two years from now.

### 1.3 DNS

Nothing to add. Sending as `@theaccessexchange.onmicrosoft.com` through
Microsoft means SPF, DKIM and DMARC are Microsoft's own and already pass -
that is the one real upside of the tenant address.

`theaccessexchange.com`'s MX records point at Namecheap email forwarding
(`eforward1-5.registrar-servers.com`). Whether any alias is actually configured
behind them is **unconfirmed** - `hello@theaccessexchange.com`, used throughout
this repo, was a placeholder we invented, not an address the client gave us.

Confirm with the client which aliases exist before changing MX. If none do, the
MX records can be repointed at Microsoft with nothing to migrate - see §1.6.

Optional, and safe to add now:

| Record                           | Value                                                                        |
| -------------------------------- | ------------------------------------------------------------------------------ |
| **DMARC** (`TXT`, host `_dmarc`) | `v=DMARC1; p=none; rua=mailto:connect@theaccessexchange.onmicrosoft.com`     |

`p=none` only reports, it never blocks. The domain has no DMARC record today.

### 1.6 Later - moving to a real address

Everything above works, with two known ceilings the client should be told about
before launch:

- Microsoft **throttles outbound external mail from `.onmicrosoft.com` tenant
  domains** - roughly 100 external recipients a day before rate limiting. Fine
  for six low-volume forms; not fine if a newsletter ever goes out this way.
- Notifications arrive from `connect@theaccessexchange.onmicrosoft.com`, which
  reads as a spoof to anyone who does not know the tenant, and does not match
  the brand.

The fix, when she is ready: add `theaccessexchange.com` to the M365 tenant
(**Microsoft 365 admin → Settings → Domains → Add domain**), which flips the MX
records to Microsoft. Cost depends entirely on one unanswered question:

- **No Namecheap forwarding aliases in use** - nothing to migrate. Add the
  domain, let Microsoft set MX, done in under an hour.
- **Aliases in use** - each one must be recreated as an M365 mailbox or alias
  *before* the cutover, or that mail starts bouncing the moment MX changes.

Either way, afterwards change From and To here to
`connect@theaccessexchange.com` and re-authorise FluentSMTP. No other part of
the build moves.

> **`hello@theaccessexchange.com` is a placeholder we invented.** It is not an
> address the client supplied and may deliver nowhere. It is still printed as a
> live `mailto:` on `get-involved.html` and published in the Yoast Organization
> schema (`SEO-YOAST.md` §2). Both need a real address before launch.

### 1.4 Prove it

**Settings → FluentSMTP → Email Test** → send an HTML test to a Gmail address
_and_ an Outlook/Hotmail address. Then open the received mail → _Show original_
(Gmail) or _View message source_ (Outlook) and confirm `SPF: PASS`,
`DKIM: PASS`, `DMARC: PASS`.

If any of the three says `fail` or `none`, fix DNS before going further - every
later step assumes mail actually leaves. If the test itself errors, the error
text names the cause (bad credentials, port blocked by host, OAuth not
completed); FluentSMTP prints the raw SMTP response, which is the useful part.

### 1.5 Logging

FluentSMTP logs every `wp_mail()` for free - **Settings → FluentSMTP → Email
Logs**. Each row shows status, provider response and the full body, and failed
sends can be resent from there. No second plugin, no Pro upgrade.

Turn retention down if the log grows: **Settings → Log & Misc**. Keep it on -
having no log makes "I filled the form and heard nothing" unanswerable.

---

## 2. Plugins

**Plugins → Add New → "Contact Form 7" → Install → Activate.**

Spam: the honeypot in §3.3 stops the bulk of it. If you want more, CF7 ships
built-in **reCAPTCHA v3** (Contact → Integration) and **Akismet** support - add
either later; neither is needed to launch.

---

## 3. Three snippets

Put all three in the child theme's `functions.php`, or in a **WPCode / Code
Snippets** snippet if you would rather not touch theme files.

### 3.1 Make shortcodes run inside Elementor HTML widgets - REQUIRED

Elementor's HTML widget prints its content verbatim; it does **not** run
shortcodes. Without this, `/contact/` shows the literal text
`[contact-form-7 id="…"]`.

```php
// Run shortcodes inside Elementor HTML widgets (needed for the CF7 forms).
add_filter( 'elementor/widget/render_content', function ( $content, $widget ) {
    return 'html' === $widget->get_name() ? do_shortcode( $content ) : $content;
}, 10, 2 );
```

### 3.2 Turn off CF7's auto-paragraphs - REQUIRED

CF7 wraps every line of the form template in `<p>` and `<br>`. The forms are CSS
grids, so those paragraphs become stray grid items and the two-column layout
breaks.

```php
add_filter( 'wpcf7_autop_or_not', '__return_false' );
```

### 3.3 Honeypot

Every template keeps the original `hp-field` input - invisible to people,
irresistible to bots. This makes CF7 actually reject it.

```php
// A filled hp-field means a bot. CF7 records it as spam and sends nothing.
add_filter( 'wpcf7_spam', function ( $spam ) {
    return $spam || ! empty( $_POST['hp-field'] );
} );
```

---

## 4. The forms

Each block below is **complete and self-contained**: form name, Form tab, Mail
tab, Messages tab, and the exact line in the exact page file where its shortcode
goes. Finish one form top to bottom, then start the next.

**Contact → Add New** for each. Set the **title exactly** as given - the page
comments reference it.

### 4.0 Rules that apply to all of them

**Form tab**

- Keep every `class="field"` / `field--wide` / `lab` / `form-foot`. Those are
  what `global.css` styles; a CF7 tag with no `.field` wrapper looks wrong.
- Keep `id:` on each tag matching the `<label for="…">` beside it, or the labels
  stop being clickable and screen readers lose the association.
- Keep the `hp-field` input. It pairs with the honeypot filter in §3.3.
- The name field is `your-name`, **not** `name`. `name` is a WordPress reserved
  query variable (it is how WP resolves a post slug), so a form tag called `name`
  can collide with the query and arrive empty or mangled. `your-name` is also
  CF7's own default, so it matches every CF7 doc you will find. Its mail tag is
  therefore `[your-name]`. The other field names - `email`, `role`, `why`,
  `institution`, `explore`, `size`, `when`, `outlet`, `deadline`, `what`,
  `message`, `area` - are not reserved and are used as-is.
- `autocomplete:name` on that tag is unrelated - that is the browser autofill
  token, and it stays `name`.

**Mail tab - two checkboxes, both required**

- ☑ **Use HTML content type** - sits under the message body. Without it the
  branded HTML arrives as raw source code.
- ☑ **Exclude lines with blank mail-tags** - every optional field's `<tr>` below
  is written on **one line** precisely so this option can delete the whole row
  when the field came in empty.

**From address, with the Outlook mailer**

FluentSMTP is authorised against one mailbox, and Microsoft will only send as
that mailbox or one of its proxy addresses. So the From address on every form
must be exactly `connect@theaccessexchange.onmicrosoft.com`. Anything else is
rewritten by Exchange or rejected outright.

The visitor's address never goes in From. It goes in **Reply-To**, which is what
makes hitting Reply in the inbox work.

**Two things that will break the HTML if you change them**

1. **Never leave a blank line inside the message body.** CF7 runs the body
   through `wpautop`, which turns blank lines into `<p>` tags and stray newlines
   into `<br>`. The templates below have none - every `<tr>` is one line.
2. **Every `<td>` carries its own `font-family` and colour.** Outlook's rendering
   engine does not inherit them into table cells. The repetition is deliberate.

Brand values used throughout, straight from `global.css` §01:

| Token       | Hex       | Used for                       |
| ----------- | --------- | ------------------------------ |
| `--paper`   | `#F8F8F3` | card background                |
| `--paper-2` | `#F0F0E8` | message block, footer strip    |
| `--paper-3` | `#E4E4DA` | outer frame, hairline rules    |
| `--ink`     | `#14140F` | header bar, values, button     |
| `--ink-2`   | `#4A4A42` | long-form message text         |
| `--ink-3`   | `#83837A` | small-caps labels, footer meta |

### 4.1 TAE - Contact · Guest

Route: someone offering themselves as an interview guest, from `/contact/#guest`.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="g-name">Name</label>
  [text* your-name id:g-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="g-email">Email</label>
  [email* email id:g-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="g-role">Organisation and role</label>
  [text* role id:g-role placeholder "Where you work, and what you do there"]
</div>
<div class="field field--wide">
  <label class="lab" for="g-why">What would you want to be honest about?</label>
  [textarea* why id:g-why placeholder "The messier the route, the more useful the conversation."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We reply within two working days</p>
  [submit class:btn "Send"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `Guest enquiry - [your-name]`                       |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑                                                  |

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              New guest enquiry
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Name
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Organisation
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [role]
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td
            style="padding:22px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;padding-bottom:9px;"
            >
              What they would be honest about
            </div>
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:16px 18px;font-size:15px;line-height:1.62;color:#4A4A42;"
            >
              [why]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20your%20message%20to%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Reply to [your-name]</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            Contact form · guest route &nbsp;·&nbsp; [_date] at [_time]
            &nbsp;·&nbsp; [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> That has reached us. We read every message ourselves and will come back within two working days.

**D · Shortcode**

`wordpress/contact.html`, **line 77**. Replace:

```html
[contact-form-7 id="REPLACE_ID_CONTACT_GUEST" title="TAE - Contact · Guest"
html_class="form"]
```

with the shortcode CF7 shows at **Contact → Forms** for this form - keeping
`html_class="form"`, which CF7 does not add for you:

```html
[contact-form-7 id="a1b2c3d" title="TAE - Contact · Guest" html_class="form"]
```

---

### 4.2 TAE - Contact · University

Route: a careers service, department or student society, from `/contact/#uni`.
The short version of 4.5 - same audience, fewer questions.

**A · Form tab**

```
<div class="field field--wide">
  <label class="lab" for="u-inst">Institution</label>
  [text* institution id:u-inst placeholder "University or college name"]
</div>
<div class="field">
  <label class="lab" for="u-name">Contact name</label>
  [text* your-name id:u-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="u-email">Email</label>
  [email* email id:u-email autocomplete:email placeholder "you@institution.ac.uk"]
</div>
<div class="field field--wide">
  <label class="lab" for="u-role">Role and department</label>
  [text* role id:u-role placeholder "Careers Consultant, School of Computing"]
</div>
<div class="field field--wide">
  <label class="lab" for="u-explore">What are you hoping to explore?</label>
  [textarea* explore id:u-explore placeholder "The gap you already know your students have is the most useful thing you can tell us."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">Term-time aware · No cost to ask</p>
  [submit class:btn "Send"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `University enquiry - [institution]`                |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑                                                  |

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              New university enquiry
            </div>
            <div style="font-size:13px;color:#83837A;padding-top:6px;">
              [institution]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Institution
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [institution]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Contact
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Role
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [role]
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td
            style="padding:22px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;padding-bottom:9px;"
            >
              What they want to explore
            </div>
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:16px 18px;font-size:15px;line-height:1.62;color:#4A4A42;"
            >
              [explore]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20[institution]%20and%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Book the scoping call</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            Contact form · university route &nbsp;·&nbsp; [_date] at [_time]
            &nbsp;·&nbsp; [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> That has reached us. We read every message ourselves and will come back within two working days.

**D · Shortcode**

`wordpress/contact.html`, **line 84**. Replace:

```html
[contact-form-7 id="REPLACE_ID_CONTACT_UNI" title="TAE - Contact · University"
html_class="form"]
```

with the real id, keeping `html_class="form"`. Leave the `.divert` block that
follows it in the panel - that is the "Read the guide" card, not part of the form.

---

### 4.3 TAE - Contact · Press

Route: journalists, from `/contact/#press`. Deadline is the only field that
changes what you do next, so it gets its own strip at the top of the email
instead of a row in the table.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="p-name">Name</label>
  [text* your-name id:p-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="p-outlet">Publication</label>
  [text* outlet id:p-outlet placeholder "Where it will run"]
</div>
<div class="field">
  <label class="lab" for="p-email">Email</label>
  [email* email id:p-email autocomplete:email placeholder "you@publication.com"]
</div>
<div class="field">
  <label class="lab" for="p-when">Deadline</label>
  [text deadline id:p-when placeholder "For example: Friday"]
</div>
<div class="field field--wide">
  <label class="lab" for="p-what">What do you need?</label>
  [textarea* what id:p-what placeholder "Subject, angle, and what you need from us."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">Deadlines honoured where we can</p>
  [submit class:btn "Send"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `Press - [outlet] - [your-name]`                    |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑                                                  |

The deadline strip is a single `<tr>` on one line, so when the field comes in
empty the "Exclude lines with blank mail-tags" option removes the whole strip
rather than leaving an empty black bar.

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              Press request
            </div>
            <div style="font-size:13px;color:#83837A;padding-top:6px;">
              [outlet]
            </div>
          </td>
        </tr>
        <tr>
          <td
            style="padding:20px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
              style="background:#F0F0E8;border-radius:8px;"
            >
              <tr>
                <td
                  style="padding:14px 18px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;"
                >
                  Deadline<span
                    style="display:block;font-size:19px;letter-spacing:-.01em;text-transform:none;color:#14140F;padding-top:5px;"
                    >[deadline]</span
                  >
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Name
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Publication
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [outlet]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td
            style="padding:22px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;padding-bottom:9px;"
            >
              What they need
            </div>
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:16px 18px;font-size:15px;line-height:1.62;color:#4A4A42;"
            >
              [what]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20your%20press%20request%20-%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Reply to [your-name]</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            Contact form · press route &nbsp;·&nbsp; [_date] at [_time]
            &nbsp;·&nbsp; [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> That has reached us. We read every message ourselves and will come back within two working days.

**D · Shortcode**

`wordpress/contact.html`, **line 98**. Replace:

```html
[contact-form-7 id="REPLACE_ID_CONTACT_PRESS" title="TAE - Contact · Press"
html_class="form"]
```

with the real id, keeping `html_class="form"`.

> Do **not** enable the optional auto-reply (§4.7) on this form. A journalist on
> deadline does not want an autoresponder.

---

### 4.4 TAE - Contact · Other

Route: corrections, recommendations, questions, guest suggestions, from
`/contact/#other`. Three fields, so the email leads with the message itself
rather than a table.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="o-name">Name</label>
  [text* your-name id:o-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="o-email">Email</label>
  [email* email id:o-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="o-msg">Message</label>
  [textarea* message id:o-msg placeholder "Two lines is plenty."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We read every one of these ourselves</p>
  [submit class:btn "Send"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `Website message - [your-name]`                     |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑                                                  |

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              Website message
            </div>
          </td>
        </tr>
        <tr>
          <td
            style="padding:26px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:18px 20px;font-size:16px;line-height:1.62;color:#4A4A42;"
            >
              [message]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-top:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  From
                </td>
                <td
                  style="padding:12px 0;border-top:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-top:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-top:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20your%20message%20to%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Reply to [your-name]</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            Contact form · general route &nbsp;·&nbsp; [_date] at [_time]
            &nbsp;·&nbsp; [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> That has reached us. We read every message ourselves and will come back within two working days.

**D · Shortcode**

`wordpress/contact.html`, **line 105**. Replace:

```html
[contact-form-7 id="REPLACE_ID_CONTACT_OTHER" title="TAE - Contact · Other"
html_class="form"]
```

with the real id, keeping `html_class="form"`.

That is all four Contact panels done. Load `/contact/` and click through the
four segmented-control tabs before moving on - all four should render styled,
and the card should still animate its height between them.

---

### 4.5 TAE - Partnership enquiry

Route: the conversion band at the bottom of `/university-partnerships/`. The
longest form on the site, and the only one with optional fields - **Cohort size**
and **Timing** both default to "Not sure yet", which submits empty. Their rows
are single-line so the blank-tag exclusion deletes them cleanly.

This form sits on the site's one dark section. That changes nothing about the
email; the email is always on cream.

**A · Form tab**

```
<div class="field field--wide">
  <label class="lab" for="u-inst">Institution</label>
  [text* institution id:u-inst placeholder "University or college name"]
</div>
<div class="field">
  <label class="lab" for="u-name">Contact name</label>
  [text* your-name id:u-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="u-email">Email</label>
  [email* email id:u-email autocomplete:email placeholder "you@institution.ac.uk"]
</div>
<div class="field field--wide">
  <label class="lab" for="u-role">Role and department</label>
  [text* role id:u-role placeholder "Careers Consultant, School of Computing"]
</div>
<div class="field">
  <label class="lab" for="u-size">Cohort size</label>
  [select size id:u-size first_as_label "Not sure yet" "Under 30" "30 to 100" "100 to 300" "Over 300"]
</div>
<div class="field">
  <label class="lab" for="u-when">When are you thinking?</label>
  [select when id:u-when first_as_label "Not sure yet" "This term" "Next term" "Next academic year" "Just exploring"]
</div>
<div class="field field--wide">
  <label class="lab" for="u-explore">What are you hoping to explore?</label>
  [textarea* explore id:u-explore placeholder "The gap you already know your students have is the most useful thing you can tell us."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We reply within two working days</p>
  [submit class:btn "Submit inquiry"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `Partnership enquiry - [institution]`               |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑ - **required here**, two fields are optional     |

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              Partnership enquiry
            </div>
            <div style="font-size:13px;color:#83837A;padding-top:6px;">
              [institution]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Institution
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [institution]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Contact
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Role
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [role]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Cohort size
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [size]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Timing
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [when]
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td
            style="padding:22px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;padding-bottom:9px;"
            >
              What they want to explore
            </div>
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:16px 18px;font-size:15px;line-height:1.62;color:#4A4A42;"
            >
              [explore]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20[institution]%20and%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Book the scoping call</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            University Partnerships page &nbsp;·&nbsp; [_date] at [_time]
            &nbsp;·&nbsp; [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> We will come back within two working days to book the scoping call. If your term dates are tight, say so in the reply and we will work backwards from them.

**D · Shortcode**

`wordpress/university-partnerships.html`, **line 326**. Replace:

```html
[contact-form-7 id="REPLACE_ID_PARTNERSHIP" title="TAE - Partnership enquiry"
html_class="form"]
```

with the real id, keeping `html_class="form"`. It stays inside
`<div class="cta-band">`, under the three `.assure` items.

**Verify:** submit once leaving both selects on "Not sure yet". The Cohort size
and Timing rows should be **absent** from the email, not present and empty. If
they are present, the "Exclude lines with blank mail-tags" checkbox is off.

---

### 4.6 TAE - Be a guest

Route: the "Be a guest" band at the bottom of `/interview-series/`. Same intent
as 4.1 but with **Area of expertise** added, which is how these get triaged - so
it goes in the subject line and gets a pill treatment in the email.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="g-name">Name</label>
  [text* your-name id:g-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="g-email">Email</label>
  [email* email id:g-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="g-role">Organisation and role</label>
  [text* role id:g-role placeholder "Where you work, and what you do there"]
</div>
<div class="field field--wide">
  <label class="lab" for="g-area">Area of expertise</label>
  [select* area id:g-area first_as_label "Select an area" "Engineering" "Product" "Data and AI" "Design" "Breaking in and early careers" "Hiring and people" "Something else"]
</div>
<div class="field field--wide">
  <label class="lab" for="g-why">What would you want to be honest about?</label>
  [textarea* why id:g-why placeholder "A few lines is plenty. The messier the route, the more useful the conversation."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We reply within two working days</p>
  [submit class:btn "Submit inquiry"]
</div>
```

**B · Mail tab**

| Field                              | Value                                               |
| ---------------------------------- | --------------------------------------------------- |
| To                                 | `connect@theaccessexchange.onmicrosoft.com`                       |
| From                               | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject                            | `Be a guest - [your-name], [area]`                  |
| Additional headers                 | `Reply-To: [email]`                                 |
| Use HTML content type              | ☑                                                  |
| Exclude lines with blank mail-tags | ☑                                                  |

Message body:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:24px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange · Interview Series
            </div>
            <div
              style="font-size:21px;line-height:1.2;letter-spacing:-.02em;color:#F8F8F3;padding-top:7px;"
            >
              Guest application
            </div>
            <div
              style="display:inline-block;margin-top:12px;padding:6px 13px;border:1px solid #4A4A42;border-radius:999px;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#F8F8F3;"
            >
              [area]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 4px;">
            <table
              role="presentation"
              width="100%"
              cellpadding="0"
              cellspacing="0"
              border="0"
            >
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Name
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [your-name]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Email
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  <a href="mailto:[email]" style="color:#14140F;">[email]</a>
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Organisation
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [role]
                </td>
              </tr>
              <tr>
                <td
                  width="132"
                  style="width:132px;padding:12px 16px 12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;vertical-align:top;"
                >
                  Expertise
                </td>
                <td
                  style="padding:12px 0;border-bottom:1px solid #E4E4DA;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;color:#14140F;vertical-align:top;"
                >
                  [area]
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td
            style="padding:22px 30px 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#83837A;padding-bottom:9px;"
            >
              What they would be honest about
            </div>
            <div
              style="background:#F0F0E8;border-left:2px solid #14140F;border-radius:0 8px 8px 0;padding:16px 18px;font-size:15px;line-height:1.62;color:#4A4A42;"
            >
              [why]
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:24px 30px 28px;">
            <a
              href="mailto:[email]?subject=Re:%20being%20a%20guest%20on%20The%20Access%20Exchange"
              style="display:inline-block;background:#14140F;color:#F8F8F3;text-decoration:none;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;padding:15px 26px;border-radius:8px;"
              >Book the pre-call</a
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            Interview Series page &nbsp;·&nbsp; [_date] at [_time] &nbsp;·&nbsp;
            [_remote_ip]
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

**C · Messages tab**

_Sender's message was sent successfully_ →

> We read every one of these ourselves. If it looks like a fit we will come back within two working days to book the pre-call.

**D · Shortcode**

`wordpress/interview-series.html`, **line 382**. Replace:

```html
[contact-form-7 id="REPLACE_ID_BE_A_GUEST" title="TAE - Be a guest"
html_class="form"]
```

with the real id, keeping `html_class="form"`. It stays inside
`<div class="guest-in">`, under the three `.assure` items.

---

### 4.8 TAE - Guest consideration

Route: the `#guest` panel on `/guests-partners/`. This is the handoff's guest
form (§10) and it **replaces 4.6** - the "Be a guest" band left
`/interview-series/`, which now links here instead of carrying a second copy.

Fields are exactly the ones the brief lists: name, title, organization, email,
LinkedIn, location, areas of expertise, and what perspective they would bring.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="gc-name">Name</label>
  [text* your-name id:gc-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="gc-title">Professional title</label>
  [text* role id:gc-title placeholder "Chief Operating Officer"]
</div>
<div class="field">
  <label class="lab" for="gc-org">Organization</label>
  [text* org id:gc-org autocomplete:organization placeholder "Where you do that job"]
</div>
<div class="field">
  <label class="lab" for="gc-email">Email</label>
  [email* email id:gc-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field">
  <label class="lab" for="gc-li">LinkedIn</label>
  [url linkedin id:gc-li placeholder "linkedin.com/in/..."]
</div>
<div class="field">
  <label class="lab" for="gc-loc">Location</label>
  [text location id:gc-loc placeholder "City, country"]
</div>
<div class="field field--wide">
  <label class="lab" for="gc-area">Areas of expertise</label>
  [text* area id:gc-area placeholder "The two or three things you are genuinely worth asking about"]
</div>
<div class="field field--wide">
  <label class="lab" for="gc-why">What perspective would you bring to The Access Exchange?</label>
  [textarea* why id:gc-why placeholder "A few lines is plenty. What have you learned that most people in your position have not?"]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We read every submission ourselves</p>
  [submit class:btn "Submit for consideration"]
</div>
```

**B · Mail tab**

| Field              | Value                                  |
| ------------------ | -------------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`          |
| Subject            | `GUEST - [your-name], [role] at [org]` |
| Additional headers | `Reply-To: [email]`                    |

The `GUEST -` prefix is what the handoff means by a separate notification
category: one inbox filter on that prefix routes these away from corporate
inquiries. Set the same filter up for `CORPORATE -` in 4.9.

**C · Mail (2) - ON.** The brief asks for a confirmation where appropriate, and
a guest who has just written several paragraphs about themselves is the clearest
case. To `[email]`, subject `Thank you - The Access Exchange`, body confirming
receipt and saying what happens next.

---

### 4.9 TAE - Corporate partnership

Route: the `#corporate` panel on `/guests-partners/`. Separate notification
category from 4.8.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="cp-name">Name</label>
  [text* your-name id:cp-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="cp-title">Title</label>
  [text* role id:cp-title placeholder "Your role"]
</div>
<div class="field">
  <label class="lab" for="cp-org">Organization</label>
  [text* org id:cp-org autocomplete:organization placeholder "Company name"]
</div>
<div class="field">
  <label class="lab" for="cp-email">Work email</label>
  [email* email id:cp-email autocomplete:email placeholder "you@company.com"]
</div>
<div class="field">
  <label class="lab" for="cp-site">Company website</label>
  [url site id:cp-site placeholder "company.com"]
</div>
<div class="field">
  <label class="lab" for="cp-interest">Partnership interest</label>
  [select* interest id:cp-interest first_as_label "Select one" "Sponsor an Interview" "Nominate a Leader" "Build a Partnership" "Not sure yet"]
</div>
<div class="field field--wide">
  <label class="lab" for="cp-leader">Leader being nominated, if applicable</label>
  [text leader id:cp-leader placeholder "Name and role - leave blank if this is not a nomination"]
</div>
<div class="field field--wide">
  <label class="lab" for="cp-explore">What would you like to explore?</label>
  [textarea* explore id:cp-explore placeholder "What you have in mind, and anything that would help us come back usefully."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We reply within two working days</p>
  [submit class:btn "Send inquiry"]
</div>
```

**B · Mail tab**

| Field              | Value                            |
| ------------------ | -------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`    |
| Subject            | `CORPORATE - [org] - [interest]` |
| Additional headers | `Reply-To: [email]`              |

**C · Mail (2) - ON.** Same reasoning as 4.8.

---

### 4.10 TAE - University engagement

Route: the `#enquire` band on `/universities/`. **Replaces 4.5** (TAE -
Partnership enquiry), whose page was rewritten and renamed in Phase 4.

The handoff names six fields for this form specifically (§16): institution,
audience, requested format, preferred timing, expected attendance, and
goals/topics. All six are below, plus contact details. Timing and attendance are
optional - the brief says "if known", and demanding a date from someone still
deciding whether to ask is how an inquiry form loses an inquiry.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="u-name">Name</label>
  [text* your-name id:u-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="u-role">Your role</label>
  [text* role id:u-role placeholder "Careers Manager, Head of Department..."]
</div>
<div class="field">
  <label class="lab" for="u-inst">Institution</label>
  [text* institution id:u-inst autocomplete:organization placeholder "University or organisation"]
</div>
<div class="field">
  <label class="lab" for="u-email">Email</label>
  [email* email id:u-email autocomplete:email placeholder "you@institution.edu"]
</div>
<div class="field">
  <label class="lab" for="u-aud">Audience</label>
  [text* audience id:u-aud placeholder "Who would be in the room - course, year, society"]
</div>
<div class="field">
  <label class="lab" for="u-fmt">Requested format</label>
  [select* format id:u-fmt first_as_label "Select a format" "Leadership Talk + Q&A" "Moderated Leadership Discussion" "Industry Session" "Custom Campus Experience" "Not sure yet"]
</div>
<div class="field">
  <label class="lab" for="u-when">Preferred timing, if known</label>
  [text when id:u-when placeholder "A term, a month, or a date"]
</div>
<div class="field">
  <label class="lab" for="u-size">Expected attendance, if known</label>
  [text size id:u-size placeholder "A rough number is fine"]
</div>
<div class="field field--wide">
  <label class="lab" for="u-goals">Goals and topics</label>
  [textarea* goals id:u-goals placeholder "What you want students to come away with, and any themes you already have in mind."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">No cost to ask. We reply within two working days</p>
  [submit class:btn "Request an engagement"]
</div>
```

**B · Mail tab**

| Field              | Value                                   |
| ------------------ | --------------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`           |
| Subject            | `UNIVERSITY - [institution] - [format]` |
| Additional headers | `Reply-To: [email]`                     |

`UNIVERSITY -` is the notification category, matching the `GUEST -` and
`CORPORATE -` prefixes in 4.8 and 4.9.

**C · Mail (2) - ON.** An institution weighing up whether to ask should get an
immediate confirmation that the ask landed.

---

### 4.11 TAE - Professional coaching

Route: the `#coaching` panel on `/coaching/`. The handoff asks for "concise
qualification fields" (§16) - enough to tell whether this is a fit and to reply
usefully, and no more. A coaching inquiry is a personal thing to send; a long
form loses the people worth talking to.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="pc-name">Name</label>
  [text* your-name id:pc-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="pc-email">Email</label>
  [email* email id:pc-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="pc-role">Where you are professionally</label>
  [text* role id:pc-role placeholder "Your role, and the kind of organisation"]
</div>
<div class="field field--wide">
  <label class="lab" for="pc-focus">What would you want to work on?</label>
  [select* focus id:pc-focus first_as_label "Select a focus" "Career and professional clarity" "Transitions and advancement" "Professional positioning and visibility" "Relationship and network strategy" "Interview and opportunity preparation" "Development planning and accountability" "More than one of these"]
</div>
<div class="field field--wide">
  <label class="lab" for="pc-goal">What would make this worth doing?</label>
  [textarea* goal id:pc-goal placeholder "A few lines. What would have to change for this to have been worth your time?"]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">Replies come from a person, within two working days</p>
  [submit class:btn "Request coaching"]
</div>
```

**B · Mail tab**

| Field              | Value                              |
| ------------------ | ---------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`      |
| Subject            | `COACHING - [your-name] - [focus]` |
| Additional headers | `Reply-To: [email]`                |

**C · Mail (2) - ON.**

---

### 4.12 TAE - Coach training

Route: the `#training` panel on `/coaching/`. A **separate inquiry path** from
4.11, which the handoff asks for by name - these are different people wanting
different things, and merging them would mean triaging them apart by hand.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="ct-name">Name</label>
  [text* your-name id:ct-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="ct-email">Email</label>
  [email* email id:ct-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="ct-stage">Where you are with coaching</label>
  [select* stage id:ct-stage first_as_label "Select one" "Already coaching, seeking certification" "Moving toward coaching" "Building coaching capability inside an organisation" "Exploring the idea"]
</div>
<div class="field field--wide">
  <label class="lab" for="ct-why">What are you hoping to get from the programme?</label>
  [textarea* why id:ct-why placeholder "A few lines about who you want to be able to help, and how."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We reply within two working days</p>
  [submit class:btn "Explore coach training"]
</div>
```

**B · Mail tab**

| Field              | Value                                    |
| ------------------ | ---------------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`            |
| Subject            | `COACH TRAINING - [your-name] - [stage]` |
| Additional headers | `Reply-To: [email]`                      |

**C · Mail (2) - ON.**

---

### 4.13 TAE - General inquiry

Route: the `#general` section on `/get-involved/`. **Replaces 4.1–4.4**, the four
panels of the old contact page. The five specific routes now lead to the form
that owns them, so this one only has to catch what is left.

**A · Form tab**

```
<div class="field">
  <label class="lab" for="gi-name">Name</label>
  [text* your-name id:gi-name autocomplete:name placeholder "Your name"]
</div>
<div class="field">
  <label class="lab" for="gi-email">Email</label>
  [email* email id:gi-email autocomplete:email placeholder "you@example.com"]
</div>
<div class="field field--wide">
  <label class="lab" for="gi-about">What is this about?</label>
  [select* topic id:gi-about first_as_label "Select one" "Press or media" "Speaking or moderating" "Collaboration" "Careers and working with us" "Something else"]
</div>
<div class="field field--wide">
  <label class="lab" for="gi-msg">Your message</label>
  [textarea* message id:gi-msg placeholder "As much or as little as you like."]
</div>
<input class="sr" type="text" name="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="form-foot">
  <p class="lab">We read every message ourselves</p>
  [submit class:btn "Send message"]
</div>
```

**B · Mail tab**

| Field              | Value                             |
| ------------------ | --------------------------------- |
| To                 | `connect@theaccessexchange.onmicrosoft.com`     |
| Subject            | `GENERAL - [topic] - [your-name]` |
| Additional headers | `Reply-To: [email]`               |

**C · Mail (2) - off.** The five specific routes confirm because someone has
just written at length about themselves. A general message does not need one.

---

### 4.7 Auto-reply to the sender - optional, and currently OFF

**Status: not enabled.** Turning it on makes CF7 raise
_"Unsafe email config is used without sufficient protection"_ on the Mail (2)
panel, and that warning is worth respecting rather than dismissing.

**What it means.** Mail (2) sends **To `[your-email]`** - an address typed by
whoever filled the form, not one you control. A bot can therefore submit the
form with a stranger's address and make your server email that stranger. CF7
flags it because at that point the site is a small open relay: the spam is sent
by you, signed with your DKIM key, and the bounces and complaints land on your
domain reputation. The honeypot in §3.3 blocks unsophisticated bots but CF7 does
not count it, because it is not a check CF7 can see.

**To turn it on safely**, add one of these first - either clears the warning:

- **reCAPTCHA v3** - Contact → Integration → reCAPTCHA. Free, and you are
  already in a Google account for the mailer. No visible challenge, just a
  score threshold.
- **Akismet** - install and key it, then add `akismet:author_email` to the email
  tag and `akismet:author` to the name tag, e.g.
  `[email* email akismet:author_email id:g-email …]`. CF7 treats an
  Akismet-tagged field as protected.

**Or leave it off.** Nothing on the site depends on it - every form already
confirms receipt on screen via the Messages tab, and the copy promises a reply
within two working days. Leaving it off is a legitimate final answer, not a
gap; the branded body below is here for when spam protection is in place.

If you do enable it: on 4.1, 4.2, 4.5 and 4.6 only. Skip 4.3 (press, on
deadline) and 4.4 (short messages, often no reply wanted).

| Field                 | Value                                               |
| --------------------- | --------------------------------------------------- |
| To                    | `[email]`                                           |
| From                  | `The Access Exchange <connect@theaccessexchange.onmicrosoft.com>` |
| Subject               | `We have your message`                              |
| Additional headers    | `Reply-To: connect@theaccessexchange.onmicrosoft.com`             |
| Use HTML content type | ☑                                                  |

Message body - same card, two lines of copy, no data echoed back:

```html
<table
  role="presentation"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  border="0"
  style="background:#E4E4DA;padding:30px 12px;"
>
  <tr>
    <td align="center">
      <table
        role="presentation"
        width="600"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="width:600px;max-width:100%;background:#F8F8F3;border-radius:12px;"
      >
        <tr>
          <td
            style="background:#14140F;padding:26px 30px;border-radius:12px 12px 0 0;font-family:Figtree,Helvetica,Arial,sans-serif;"
          >
            <div
              style="font-size:10px;letter-spacing:.19em;text-transform:uppercase;color:#83837A;"
            >
              The Access Exchange
            </div>
            <div
              style="font-size:23px;line-height:1.15;letter-spacing:-.025em;color:#F8F8F3;padding-top:8px;"
            >
              Thank you, [your-name].
            </div>
          </td>
        </tr>
        <tr>
          <td
            style="padding:26px 30px 28px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.62;color:#4A4A42;"
          >
            That has reached us. We read every message ourselves and will come
            back within two working days.<span
              style="display:block;padding-top:14px;"
              >You can just reply to this email if there is anything to
              add.</span
            >
          </td>
        </tr>
        <tr>
          <td
            style="background:#F0F0E8;padding:15px 30px;border-radius:0 0 12px 12px;font-family:Figtree,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;color:#83837A;"
          >
            The Access Exchange &nbsp;·&nbsp; London, recording remotely
            &nbsp;·&nbsp;
            <a href="[_url]" style="color:#83837A;">[_site_url]</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
```

---

## 5. Reference - every form at a glance

Tick-list for when you are halfway through and lose your place. Every one of
these is set inside its own §4.x block.

| §    | Form title                  | Subject                                  | Page file · line                      | Auto-reply |
| ---- | --------------------------- | ---------------------------------------- | ------------------------------------- | ---------- |
| 4.1  | TAE - Contact · Guest       | `Guest enquiry - [your-name]`            | `contact.html` · 77                   | off        |
| 4.2  | TAE - Contact · University  | `University enquiry - [institution]`     | `contact.html` · 84                   | off        |
| 4.3  | TAE - Contact · Press       | `Press - [outlet] - [your-name]`         | `contact.html` · 98                   | off        |
| 4.4  | TAE - Contact · Other       | `Website message - [your-name]`          | `contact.html` · 105                  | off        |
| 4.5  | TAE - Partnership enquiry   | `Partnership enquiry - [institution]`    | RETIRED - replaced by 4.10            | -          |
| 4.6  | TAE - Be a guest            | `Be a guest - [your-name], [area]`       | RETIRED - replaced by 4.8             | -          |
| 4.8  | TAE - Guest consideration   | `GUEST - [your-name], [role] at [org]`   | `guests-partners.html` · `#guest`     | **on**     |
| 4.9  | TAE - Corporate partnership | `CORPORATE - [org] - [interest]`         | `guests-partners.html` · `#corporate` | **on**     |
| 4.10 | TAE - University engagement | `UNIVERSITY - [institution] - [format]`  | `universities.html` · `#enquire`      | **on**     |
| 4.11 | TAE - Professional coaching | `COACHING - [your-name] - [focus]`       | `coaching.html` · `#coaching`         | **on**     |
| 4.12 | TAE - Coach training        | `COACH TRAINING - [your-name] - [stage]` | `coaching.html` · `#training`         | **on**     |
| 4.13 | TAE - General inquiry       | `GENERAL - [topic] - [your-name]`        | `get-involved.html` · `#general`      | off        |

**Seven live forms: 4.8 – 4.13 plus the email opt-in on the home page.** 4.1 – 4.6
are all retired. Auto-reply is ON everywhere except 4.13 - see §4.7.

Identical on all of them: **To** `connect@theaccessexchange.onmicrosoft.com`, **From**
`The Access Exchange <connect@theaccessexchange.onmicrosoft.com>`, **Additional headers**
`Reply-To: [email]`, both Mail-tab checkboxes ticked, and `html_class="form"`
kept on the shortcode (4.8 and 4.9 add a second class after it).

Mail tags used, if you want to restyle any of the bodies:

| Tag                                                                 | Comes from                          |
| ------------------------------------------------------------------- | ----------------------------------- |
| `[your-name]` `[email]` `[role]`                                    | shared across most forms            |
| `[institution]` `[audience]` `[format]` `[when]` `[size]` `[goals]` | university form                     |
| `[outlet]` `[deadline]` `[what]`                                    | press form                          |
| `[why]` `[area]` `[org]` `[linkedin]` `[location]`                  | guest forms                         |
| `[interest]` `[leader]` `[site]`                                    | corporate form                      |
| `[message]`                                                         | contact · other                     |
| `[_date]` `[_time]` `[_url]` `[_site_url]` `[_remote_ip]`           | CF7 built-ins, no form field needed |

---

## 6. Test

1. Submit every one of the six forms with real values.
2. Confirm the mail arrives at `hello@` - **and check the spam folder**; landing
   in spam is a §1.3 DNS problem, not a CF7 problem.
3. Hit **Reply** on one and confirm it addresses the visitor, not `hello@`.
4. Submit with a required field empty → the field-level error text should appear
   under the field, styled, and the page must not reload.
5. On `/contact/`, switch tabs after a successful send - the card should still
   morph its height cleanly.
6. Check whichever log you set up in §1.5 - six sends, all delivered.
7. Send one form's output through [mail-tester.com](https://www.mail-tester.com)
   - put its address in the To field temporarily. Aim for 9/10 or better.

On the emails themselves:

8. **Raw HTML in the inbox** means the _Use HTML content type_ checkbox is off
   on that form.
9. **Stray blank paragraphs or a collapsed layout** means a blank line crept into
   the message body. Every `<tr>` must stay on one line.
10. **An empty "Cohort size" or "Timing" row on 4.5, or an empty deadline strip
    on 4.3**, means _Exclude lines with blank mail-tags_ is off.
11. Open one on a **phone** and once in **Outlook** - those are where table email
    breaks first. The card is 600px and scales down; nothing should overflow.

---

## 7. What changed in this repo

- `contact.html` - four `<form>` blocks → four shortcodes (lines 77, 84, 98, 105).
- `university-partnerships.html` line 326, `interview-series.html` line 382 -
  one shortcode each.
- `assets/global.css` - new **§11b**, normalises the wrapper `<span>`, the
  error tips and the response message that CF7 adds inside the form, and
  restates the submit button's fill on `:hover` / `:focus` / `:active`. That
  last part is not cosmetic: CF7 prints `<input type="submit">` where the
  prototype had `<button>`, and a starter theme reaches an attribute selector
  at a specificity the old markup never exposed. The comment above the rules
  has the full math.
- `assets/global.css` §11 - the Contact segmented control's four buttons got
  the same treatment, for the same reason.
- `assets/global.js` **§07** is now inert. It only binds to
  `form[data-validate]`, and no form carries that attribute any more. Left in
  place so a page can be reverted to the prototype behaviour by re-adding the
  attribute; delete the function if you want the file clean.

The previous prototype markup is in git history if you ever need it back.
