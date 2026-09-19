# Installing the Hostdigi theme on cPanel

Two ways. **Route A is the simplest** and needs no git. Route B is worth setting up once
if you expect the theme to keep changing.

Neither route requires giving anyone your cPanel password.

---

## Before you start: find your WHMCS folder

1. cPanel → **File Manager**.
2. Find the folder containing **`configuration.php`** and a **`templates`** folder.
   With WHMCS at the root of hostdigi.co.za this is almost always `public_html`.

That folder is referred to below as **the WHMCS root**. If `configuration.php` isn't
there, you're in the wrong folder — don't upload anything yet.

---

## Route A — upload the ZIP (10 minutes, no git)

1. Download `hostdigi-whmcs-theme.zip`.
2. cPanel → **File Manager** → open the WHMCS root (see above).
3. Click **Upload**, choose the ZIP, wait for 100%, then go back to the folder.
4. Right-click the ZIP → **Extract** → extract into the WHMCS root.

   It contains `templates/hostdigi/` and `includes/hooks/hostdigi_theme.php`, so it
   merges into the folders already there. It cannot overwrite Twenty-One or any other
   theme — every file it writes is inside a `hostdigi` folder, except the one hook file,
   which is new.
5. Delete the ZIP once extracted.
6. Open `includes/hooks/hostdigi_theme.php` (right-click → **Edit**) and check this line
   matches your product group name exactly:

   ```php
   const HOSTDIGI_PLAN_GROUPS = [
       'Cloud Hosting' => 'Cloud Shared Hosting',
   ];
   ```

   The name on the right must match **Configuration → Products/Services →
   Products/Services**. Save.
7. **Preview it** — in your browser, go to:

   ```
   https://hostdigi.co.za/index.php?systpl=hostdigi
   ```

   This shows the new theme **to you only**. Real visitors still see the current site.
   Click around the cart and client area while it's active.
8. Happy? WHMCS admin → **Configuration → System Settings → General → Template →
   Hostdigi** → Save.

**To undo at any point:** switch the Template setting back to Twenty-One. Nothing in your
billing data is touched at any stage.

---

## Route B — cPanel Git Version Control (set up once, one-click updates after)

1. cPanel → **Git™ Version Control** → **Create**.
2. Tick **Clone a Repository**.
3. **Clone URL:** `https://github.com/TKPHOTO2023/hostdigi.git`
   **Repository Path:** something *outside* public_html, e.g. `/home/<user>/repos/hostdigi`
   **Repository Name:** `hostdigi`
4. If the repo is private, cPanel shows an SSH key — copy it, then add it in GitHub under
   the repo's **Settings → Deploy keys → Add deploy key** (read access is enough), and
   use the SSH clone URL `git@github.com:TKPHOTO2023/hostdigi.git` instead.
5. Open `.cpanel.yml` in the cloned repo and set the first line to your real path:

   ```yaml
   - export WHMCSROOT=/home/YOUR-CPANEL-USER/public_html
   ```

   It refuses to deploy if it can't find `configuration.php` there, so a wrong path fails
   safely instead of scattering files.
6. Back in Git Version Control → **Manage** → **Deploy HEAD Commit**.
7. Preview and activate exactly as in Route A steps 7–8.

**From then on**, whenever I push a change: **Manage → Update from Remote → Deploy HEAD
Commit**. Two clicks, no file copying.

---

## If something looks wrong

| Symptom | Cause | Fix |
|---|---|---|
| Site looks unchanged after activating | Compiled template cache | **Utilities → System → System Cleanup**, or delete everything inside `templates_c/` |
| Plans area shows "browse the store" | Group name doesn't match | Check the name in the hook against Configuration → Products/Services; the mismatch is logged in **Utilities → Logs → Activity Log** |
| Plan bullets are one long line | Product description is a paragraph | Put each feature on its own line in the product description, or tell me and I'll parse commas too |
| Preview URL shows the old theme | Theme dependencies failed validation | Confirm `templates/hostdigi/theme.yaml` uploaded, and that your WHMCS is 8.x with Twenty-One present |
| 500 error after uploading the hook | PHP fatal in the hook file | Delete `includes/hooks/hostdigi_theme.php`; the site recovers immediately. Send me the error from **Utilities → Logs → Activity Log** |

Screenshot whatever you see and send it — that's usually enough for me to pin the cause.
