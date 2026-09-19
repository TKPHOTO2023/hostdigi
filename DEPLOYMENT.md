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

1. In cPanel → **FTP Accounts**, create a **dedicated** account with **Directory** set to
   `public_html` (so the full path is `/home/hostdigi/public_html`). Don't reuse your main
   cPanel login. Quota can be Unlimited — the theme is under 300 KB.
2. In GitHub → repo **Settings → Secrets and variables → Actions**, add three secrets:

   | Secret | Value |
   |---|---|
   | `FTP_SERVER` | `ftp.hostdigi.co.za` (or the server hostname cPanel shows under FTP Accounts → Configure FTP Client) |
   | `FTP_USERNAME` | the **full** username, usually `something@hostdigi.co.za` |
   | `FTP_PASSWORD` | the password you set |

3. **Test before trusting it:** repo → **Actions** → *Deploy theme to WHMCS* → **Run
   workflow**, tick **dry run**. It connects and lists what it *would* transfer without
   writing anything. Check the log shows paths like `templates/hostdigi/theme.yaml`, then
   run it again with dry run off.
4. From then on, every push deploys automatically.

### If the deploy fails on paths

cPanel picks an FTP account's login directory itself — it may append the username, or
base the path on a different docroot than you chose. Ours landed on
`/home/hostdigi/hostdigi.co.za/github-user`, nowhere near WHMCS.

So the workflow uses an **absolute** server path by default:
`/home/hostdigi/public_html/`. That sidesteps the login directory entirely, as long as
your server does not jail FTP accounts to their home folder.

If it does jail them, the absolute path fails. Then: make sure the FTP account's
directory really is `public_html`, and set a repository **variable** (not a secret)
`FTP_ROOT` to `./` under Settings → Secrets and variables → Actions → **Variables**.

`FTP_ROOT` is only a path, so a variable rather than a secret — you can see and change it
without touching credentials.

### The old path gotcha

A cPanel FTP account is **jailed to its Directory**, so once logged in, the paths start
*from* `public_html`. That's why the workflow says `server-dir: templates/hostdigi/` and
not `public_html/templates/hostdigi/` — the latter would create
`public_html/public_html/templates/hostdigi`, the same nesting that broke the first manual
install.

If you use your **main cPanel login** instead (which starts at `/home/hostdigi`), prefix
both `server-dir` values with `public_html/`.

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
