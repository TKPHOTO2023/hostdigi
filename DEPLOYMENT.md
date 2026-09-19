# Getting changes onto the server without uploading files

Three options, from least to most automated. All of them mean I push to GitHub and the
change reaches your site — none of them involve sharing credentials with me, and I have
no network route to your server in any case.

---

## Option 1 — cPanel Git Version Control (no secrets anywhere)

cPanel clones this repo itself and deploys on demand. Set up once:

1. cPanel → **Git™ Version Control** → **Create** → tick **Clone a Repository**
2. **Clone URL:** `https://github.com/TKPHOTO2023/hostdigi.git`
   **Repository Path:** `/home/hostdigi/repos/hostdigi` (outside public_html)
3. If the repo is private, cPanel shows an SSH key — add it in GitHub under
   **Settings → Deploy keys** (read access is enough) and clone via the SSH URL.
4. Edit `.cpanel.yml` in the clone and set the first line:
   `- export WHMCSROOT=/home/hostdigi/public_html`
5. **Manage → Deploy HEAD Commit**

**From then on, each change is two clicks:** Manage → *Update from Remote* → *Deploy HEAD
Commit*. `.cpanel.yml` copies the theme and the hook into place and clears the template
cache for you.

It refuses to deploy if `configuration.php` isn't in the configured path, so a wrong path
fails loudly rather than scattering files.

---

## Option 2 — GitHub Actions over FTPS (fully automatic, zero clicks)

`.github/workflows/deploy.yml` deploys on every push to `main`.

1. In cPanel → **FTP Accounts**, create a **dedicated** account with its directory set to
   `/public_html`. Don't reuse your main cPanel login.
2. In GitHub → repo **Settings → Secrets and variables → Actions**, add three secrets:
   `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.
3. That's it. Every push deploys.

**On safety:** GitHub secrets are write-only — once saved, nobody can read them back,
including anyone who can see the workflow file or the repo. You revoke access instantly by
deleting that FTP account in cPanel. Scope it to `public_html` so a mistake can't reach
the rest of the account. Still: the credentials do live in GitHub, which is a real
trade-off against Option 1, where nothing is stored anywhere.

You can also trigger a deploy by hand from the repo's **Actions** tab (`workflow_dispatch`)
without pushing anything.

---

## Option 3 — manual upload

What we've been doing: download the ZIP, upload, extract. Fine occasionally, tedious for
iteration. See [INSTALL-CPANEL.md](INSTALL-CPANEL.md).

---

## After any deploy

WHMCS compiles templates, so changes can look like they haven't landed:

- **Utilities → System → System Cleanup**, or empty `public_html/templates_c/`
- The pricing hook caches WHMCS data for 15 minutes (`HOSTDIGI_CACHE_TTL`)
- Hard-refresh the browser (Ctrl/Cmd + Shift + R) — CSS and JS are versioned by the `?v=`
  query string in the hook, so bump that constant after a big style change

---

## Which to pick

**Option 1** if you'd rather no credentials existed anywhere — two clicks per change.
**Option 2** if you want it to be genuinely hands-off and you're comfortable with a scoped
FTP account in GitHub secrets.
