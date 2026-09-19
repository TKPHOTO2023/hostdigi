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

## Route A — upload the theme (10 minutes, no git)

You need **two files**, and they go in **two different places**. This is the step that
most often goes wrong, so the paths are spelled out exactly.

### A1. The theme → `public_html/templates/`

1. cPanel → **File Manager** → open **`public_html/templates`**
   (the folder that already contains `twenty-one`, `six`, `hostie`, `antler`).
2. **Upload** `hostdigi-theme.zip` into that folder.
3. Right-click it → **Extract** → extract into that same folder.
4. You should now have **`public_html/templates/hostdigi`**, sitting beside `twenty-one`.
   Inside it: `theme.yaml`, `homepage.tpl`, `assets/`.
5. Delete the ZIP.

> The ZIP contains exactly one folder — `hostdigi/` — so extracting it in the templates
> folder produces `templates/hostdigi`, never `templates/templates`.

### A2. The hook → `public_html/includes/hooks/`

1. In File Manager, open **`public_html/includes/hooks`**
   (**not** `templates/includes` — a different folder entirely).
2. **Upload** `hostdigi_theme.php` there. No extracting; it's a plain PHP file.
3. You should now have **`public_html/includes/hooks/hostdigi_theme.php`**.

### A3. Check the product group name

Right-click `hostdigi_theme.php` → **Edit**, and confirm this matches your group name
under Configuration → Products/Services → Products/Services:

```php
const HOSTDIGI_PLAN_GROUPS = [
    'Cloud Hosting' => 'Cloud Shared Hosting',
];
```

Save.

### A4. Preview before going live

In your browser:

```
https://hostdigi.co.za/index.php?systpl=hostdigi
```

This shows the new theme **to you only** — real visitors still see the current site.
Click through the cart and client area while it's active.

### A5. Activate

WHMCS admin → **Configuration → System Settings → General → Template → Hostdigi** → Save.

**To undo at any point:** set Template back to your previous theme. No billing data is
touched at any stage.

---

## If the Template dropdown lists odd names

WHMCS treats **every folder inside `templates/`** as a theme. So if a zip is ever
extracted in the wrong place, entries like **Templates**, **Includes** or **MACOSX**
appear in the dropdown — and selecting one renders a blank page, because the folder
isn't a theme.

To clean that up:

1. First, set **Template** back to a real theme (Twenty-One, Hostie, …) so the site works.
2. In `public_html/templates`, delete the stray `templates/`, `includes/` and `__MACOSX/`
   folders — after checking they contain only the misplaced files, nothing of yours.
3. Re-do A1 and A2 above with the correct paths.

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
| Blank page, only "Powered by WHMCompleteSolution" | A non-theme folder is selected as the Template | Admin → Configuration → System Settings → General → Template → pick a real theme |
| Dropdown lists Templates / Includes / MACOSX | A zip was extracted inside `templates/` | See "If the Template dropdown lists odd names" above |
| 500 error after uploading the hook | PHP fatal in the hook file | Delete `includes/hooks/hostdigi_theme.php`; the site recovers immediately. Send me the error from **Utilities → Logs → Activity Log** |

Screenshot whatever you see and send it — that's usually enough for me to pin the cause.
