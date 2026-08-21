# Running the site - owner handoff

Handoff §24 asks for a short tutorial covering nine things. This is that
document, in the order you are most likely to need them.

Everything here is done in **wp-admin**. None of it needs a developer, and none
of it needs the page to be rebuilt. That was the design constraint for the whole
build: if you find yourself needing to edit a page's HTML to do something in this
list, something has gone wrong - tell us rather than working around it.

---

## 1. Publish a new interview

**Interviews → Add Interview.**

| Field                                          | What it does                                                                                           |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------ | ------------------- | ----------------------------------------------------------------------------------------------------- |
| Title                                          | The episode title. Also the page title and the shared link title.                                      |
| Poster / portrait _(Set poster, right column)_ | The image on every card and at the top of the episode page. Landscape, at least 1400px wide.           |
| Guest name · Professional title · Organization | Printed together on the episode page and used across the site.                                         |
| Episode number                                 | Optional. Prints as "Episode 12".                                                                      |
| Duration                                       | `MM:SS`. Optional.                                                                                     |
| **YouTube video ID or URL**                    | Paste the whole link if that is easier - the ID is pulled out of it and saved on its own. If the field comes back empty after saving, there was no video ID in what you pasted. |
| Standfirst                                     | One or two sentences under the headline.                                                               |
| Key takeaways                                  | One per line. Becomes the numbered list on the episode page.                                           |
| Chapters                                       | One per line as `time                                                                                  | label`, e.g. `12:40 | The decision that changed the company`. Each becomes a button that jumps the video to that timestamp. |
| Categories _(right column)_                    | Pick one. See §7 to add your own.                                                                      |

Press **Publish**. That single action does all of this:

- creates the episode page at `theaccessexchange.com/interviews/your-title/`
- adds it to the archive on the Interview Series page
- **removes the "First interview coming soon" block** from both the home page
  and the Interview Series page

You do not edit either page to make that happen. Publishing is the whole action.

> **Nothing loads from YouTube until a visitor presses play.** That is deliberate
>
> - it is most of why the site is fast. Do not replace the embed with a pasted
>   YouTube block; it would undo it.

---

## 2. Change the featured interview on the home page

Open any interview and tick **Slot · Featured interview (Home)**. That interview
becomes the large block on the home page, with its chapters listed beside it.

The other slots work the same way:

| Slot                   | Where it shows                                 |
| ---------------------- | ---------------------------------------------- |
| Cover story            | The large story at the top of Interview Series |
| Left rail              | The two smaller cards beside the cover story   |
| Featured interview     | The full-bleed block on the home page          |
| Show in "Most watched" | The numbered list on Interview Series          |

An interview can hold more than one slot. If two claim the same slot, the one
with the lower **Order** number wins (Order is in the right-hand column, under
Page Attributes).

**You do not have to open interviews to find out which is which.** The
**Interviews** list has an _Appears in_ column showing every slot each one holds,
plus its episode number and guest. Anything in no slot reads "Archive only",
which is not a problem - it is still in the archive and still searchable.

---

## 3. Add a guest profile

There is no separate guest record - a guest lives on their interview, in the
**Guest name**, **Professional title** and **Organization** fields. That is
deliberate: one place to edit, and no way for a guest's title to disagree with
itself across two pages.

If the same person returns for a second interview, fill the fields in again on
the new one. They can differ, and often should - people change jobs.

---

## 4. Add takeaways, clips and related content

**Takeaways → Add Takeaway.** Use these for a written piece, a standout idea, or
a clip pulled out of an interview.

| Field                          | What it does                                                                                                                                                       |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Title, content, featured image | The piece itself.                                                                                                                                                  |
| **Came from which interview**  | Pick the interview it came out of. This is what links it back to the full conversation. Leave it on "Stands on its own" for a piece that is not from an interview. |
| Topics                         | Takeaways · Frameworks · Clips · Written. Drives the filter buttons.                                                                                               |
| Dek                            | One line, shown only on the wide lead tile.                                                                                                                        |
| Byline                         | A person, or a line of context.                                                                                                                                    |
| Onward link                    | Where the piece sends the reader next. Optional.                                                                                                                   |
| Use as lead tile               | Makes it the wide tile at the top. Leave every one unticked and the newest leads.                                                                                  |

Publishing the first takeaway makes the **Takeaways** section appear on the
Interview Series page. It is not there before that, on purpose.

**"Came from which interview" is worth filling in every time.** Setting it adds
a **From this interview** section to that episode's page listing every piece cut
out of the conversation, and puts a **Watch the full interview** button on the
takeaway. Leave it empty and the piece still publishes - it just floats free of
the conversation it came from. The **Takeaways** list has a _From_ column so you
can see at a glance which ones are still unattached.

Use **Order** to rank them; that is the sequence they appear in on the episode
page.

---

## 5. Replace images and video thumbnails

- **An interview's thumbnail** - open the interview, right column, _Poster /
  portrait_ → **Replace**. It updates everywhere that interview appears.
- **A page's photographs** - Pages → the page → the HTML block, and swap the
  image URL. If a photograph needs replacing across several pages, replace it in
  the Media Library instead and every page follows.
- **The home page hero** - it is built to become video. Replacing the `<img>`
  with a `<video>` will not shift the layout; the band holds its shape either way.

Compress anything before uploading. A 4MB photograph will not look better than
the same photograph at 250KB, and it will cost you a visitor on a phone.

---

## 6. Review and route form submissions

Every form emails the business inbox. The subject line tells you which one it is
before you open it:

| Prefix             | Form                  |
| ------------------ | --------------------- |
| `GUEST -`          | Guest consideration   |
| `CORPORATE -`      | Corporate partnership |
| `UNIVERSITY -`     | University engagement |
| `COACHING -`       | Professional coaching |
| `COACH TRAINING -` | Coach training        |
| `GENERAL -`        | General inquiry       |

**Set up one inbox filter per prefix.** That is what turns six form types into
six labelled conversations rather than one undifferentiated inbox, and it takes
about five minutes once.

Replying: hit reply. The sender's address is already in the Reply-To, so it goes
to them, not to yourself.

All of them except General also send the sender an immediate confirmation, so
nobody is left wondering whether the form worked.

---

## 7. Rename or add an interview category

**Interviews → Categories.** Add, rename or delete freely - the filter buttons
on the Interview Series page are generated from whatever is there, so they
update themselves. No developer, no code.

The set the site ships with: Leadership · Founders & Builders · Industry & Craft
· Career & Transitions · On Campus.

One caveat worth knowing: if you change a category's **slug** (not its name),
and later deactivate the content plugin, the filter falls back to a hardcoded
list that will not know about it. That only matters if the plugin is ever turned
off, which it should not be.

---

## 8. Edit SEO titles and descriptions

Every page has a **Yoast SEO** panel below the editor.

- **SEO title** - what shows in Google. Aim for under 60 characters.
- **Meta description** - the grey text under it. Aim for 150–155 characters.
- **Slug** - the URL. **Changing it breaks every existing link to that page.**
  If you must, add a redirect from the old one.

`SEO-YOAST.md` has the recommended wording for all ten pages, already written.

Yoast will complain about things that do not matter here - keyphrase density on
a legal page, for instance. `SEO-YOAST.md` §5 lists which warnings to ignore.

---

## 9. Make routine copy changes

**Pages → the page → the HTML block.** Edit the text between the tags and update.

Two rules:

1. **Do not touch anything in square brackets.** `[tae_interviews …]`,
   `[tae_coming_soon …]`, `[contact-form-7 …]` - these are what pull in the live
   content. Deleting one deletes that whole section from the page.
2. **Do not rename a `class="…"`.** The layout is driven by those names.

Anything between `>` and `<` is yours. If you are unsure, change it, look at the
page, and undo it if it is wrong - WordPress keeps revisions of every page,
interview and takeaway. On an interview or takeaway the **Revisions** panel is in
the right-hand column; on a page it is under the editor.

---

## 9b. Change the Subscribe link or the Join button

**Interviews → Links.** Two addresses that appear in more than one place:

| Setting            | What it is                                                    |
| ------------------ | ------------------------------------------------------------- |
| YouTube channel    | The **Subscribe** button under the interview archive.         |
| Join The Access Exchange  | Where every **Join The Access Exchange** button goes.                |

Leave a field empty and it goes back to the shipped default, which is printed
under the box. Change these here rather than by editing a page - the buttons are
generated, so a page edit would not reach them.

---

## 10. Backups, updates and what this site depends on

**Backups.** Confirm your host takes daily backups and that you know how to
restore one. If it does not, install UpdraftPlus and set it to daily, off-site.
Test a restore once - an untested backup is a hope, not a backup.

**Updates.** WordPress core and plugin updates: apply them, but take a backup
first and look at the site afterwards. Set core security updates to automatic.

**What the site depends on** - deactivating any of these breaks something:

| Thing                                    | If it goes away                                    |
| ---------------------------------------- | -------------------------------------------------- |
| **The Access Exchange - Content** plugin | Interviews and takeaways disappear from every page |
| Contact Form 7                           | All seven forms stop rendering                     |
| The SMTP plugin                          | Forms submit but no email arrives - silently       |
| Yoast SEO                                | Titles, descriptions and sitemap go                |
| Elementor                                | Page layouts go                                    |

**Check quarterly:** submit one form and confirm it arrives. Silent email
failure is the most common way a site like this loses business, and nothing on
the page tells you it is happening.

---

## If something looks broken

Open the browser console (F12) and type `TAE`.

- `undefined` - the site's script did not load. Usually a caching or
  optimisation plugin. Clear the cache first.
- `TAE.ready` is `true` and `TAE.errors` is empty - the script is fine and the
  problem is elsewhere.
- The menu will not open - run `TAE.diagnose()` and send us the output.
