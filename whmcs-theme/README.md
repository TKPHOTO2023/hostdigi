# Hostdigi WHMCS child theme

A child theme of **Twenty-One** for WHMCS 8.x. WHMCS renders the design itself, so
products, prices, currencies, the cart, the domain checker and the client area all come
from your install — nothing is hard-coded and nothing needs syncing.

> **On licensing:** your WHMCS licence covers the *installation* (its domain and IP).
> Themes are not licensed separately, so this adds no licence cost and needs no licence
> key of its own. If you move WHMCS to a different domain or IP, reissue the licence in
> your WHMCS client area — that is unrelated to this theme.

## What's here

```
templates/hostdigi/theme.yaml            child theme manifest (parent: twenty-one)
templates/hostdigi/homepage.tpl          custom homepage, fed by the hook
templates/hostdigi/assets/css/hostdigi.css   namespaced design system
templates/hostdigi/assets/js/hostdigi.js     tabs, billing toggle, theme switch
includes/hooks/hostdigi_theme.php        loads assets + supplies live WHMCS data
```

Because it is a *child* theme, every page you do not override — cart, checkout, client
area, invoices, tickets — keeps rendering from Twenty-One and keeps working through
WHMCS upgrades.

## Install

1. Upload `templates/hostdigi/` to `/templates/hostdigi/` in your WHMCS root.
2. Upload `includes/hooks/hostdigi_theme.php` to `/includes/hooks/`.
3. Open the hook and set `HOSTDIGI_PLAN_GROUPS` to your product groups, written as
   `'Tab label' => 'WHMCS group name'`. The group name is what appears under
   **Configuration → Products/Services → Products/Services** — matching is
   case-insensitive, and a numeric group id is accepted too if you prefer to pin it.
4. If your install does not have an Admin API Username configured, set
   `HOSTDIGI_API_ADMIN` in the hook to an admin username with API access.
5. Activate: **Configuration → System Settings → General → Template → Hostdigi**.
6. Hard-refresh the homepage. If styling looks unchanged, empty the template cache at
   **Utilities → System → System Cleanup**.

## How the data flows

`includes/hooks/hostdigi_theme.php` calls `localAPI()` — WHMCS's in-process API — on the
`ClientAreaPageHome` hook:

| What | API call | Where it lands |
|---|---|---|
| Plan names, descriptions, monthly/annual pricing | `GetProducts` (per group) | `$hdPlanGroups` in `homepage.tpl` |
| TLD register/transfer/renew pricing | `GetTLDPricing` | `$hdTlds` in `homepage.tpl` |

Because it is the *local* API, there is **no API identifier or secret, no IP allowlist
entry, and no external HTTP request** — the usual pain of WHMCS API integrations does not
apply. Responses are cached for 15 minutes (`HOSTDIGI_CACHE_TTL`).

Prices are rendered through WHMCS's own `formatCurrency()`, so they follow the visitor's
selected currency automatically.

### Feature bullets come from your product descriptions

The plan cards build their bullet list from the product description, **one feature per
line**. Edit a product, put each feature on its own line, and the homepage follows. Up to
six lines are shown.

### Which plan gets the "Most popular" flag

Any product whose name contains one of `HOSTDIGI_FEATURED_KEYWORDS` (by default
`business`, `grow`, `plus`). Set it to `[]` to flag nothing.

### Product groups vs configurable options

`HOSTDIGI_PLAN_GROUPS` means **product groups** — the groupings on
Configuration → Products/Services → Products/Services that hold your plans. It has
nothing to do with Configuration → Products/Services → **Configurable Options**, which
is for per-order add-ons like extra disk or RAM.

## Verify these three things on your install

These depend on your specific setup and I could not test them against a live WHMCS:

1. **The domain search form.** `homepage.tpl` posts to `domainchecker.php` with `token`,
   `direct` and `domain` fields. If your install's form differs, open
   `templates/twenty-one/homepage.tpl`, copy its `<form>` block over the one in
   `homepage.tpl`, and keep the `hd-dsearch-row` / `hd-dsearch-field` / `hd-btn` classes
   on the wrapper, input and button so the styling still applies.
2. **Group names** in `HOSTDIGI_PLAN_GROUPS` must match your WHMCS group names. A name
   that doesn't match is skipped and logged to **Utilities → Logs → Activity Log**, and
   if nothing matches the template shows a "browse the store" link rather than breaking.
3. **The announcements block** uses `routePath('announcement-view', ...)`. If your install
   renders announcements differently, delete that section — it is self-contained.

## Brand colours

The palette is sampled from `hostdigi logo.png` — violet `#9568f9`, orchid `#b664f5`,
periwinkle `#6c95fb` over brand indigo `#5437cb`. In the theme they are exposed as
`--hd-violet`, `--hd-orchid`, `--hd-periwinkle` and `--hd-brand-indigo`, each with a dark
and a light value. The root [README](../README.md#brand-palette) has the full table and
the measured contrast ratios.

`hostdigi.css` is **generated** from the root `index.html` by
`tools/regen-theme-css.py`. Change colours there and re-run the script rather than
editing the theme CSS by hand, so the standalone site and the WHMCS theme never drift.

Logo assets for the theme live in `templates/hostdigi/assets/img/`.

## Living inside Twenty-One's page shell

`assets/css/hostdigi-whmcs.css` is the only hand-maintained stylesheet (the other is
generated). It exists purely to reconcile the design with the parent theme:

- **Full bleed.** Twenty-One wraps content in a centred max-width container, which left
  the dark sections floating in a white box. `.hd-root` breaks out to viewport width
  without needing to know the container's class name.
- **Page background** follows the theme rather than staying white above and below.
- **Twenty-One's own homepage hero** — the "Secure your domain name" jumbotron with a
  captcha — is rendered from `header.tpl`, not `homepage.tpl`, so overriding the homepage
  does not remove it and you get two domain searches. `hostdigi.js` finds the stray
  domain-checker form that sits outside `.hd-root` and hides its section. It walks up at
  most five levels and never hides an ancestor containing our own content, so an
  unexpected DOM cannot blank the page.

Hiding it in JS is a workaround. The clean fix is overriding `header.tpl` in this child
theme, which needs the parent's version as a starting point — copy
`templates/twenty-one/header.tpl` into the repo and it can be done properly, along with
restyling the navbar to match.

## The skin layer

`assets/css/hostdigi-whmcs.css` restyles WHMCS's own chrome — navbar, dropdowns,
breadcrumb, footer, buttons, links, focus rings — without overriding a single `.tpl`
file. It applies on every page, so the cart, client area and invoices pick up the brand
too, and it cannot break any WHMCS markup because it changes none.

The deliberate split: **chrome goes dark, interior content stays light.** A dark navbar
and footer wrapping the dark homepage reads as one continuous design, while the pages
people pay you on keep legible light surfaces. Taking those dark as well means restyling
every table, modal and alert WHMCS ships — a much larger surface with real readability
risk — and is better done as a full theme, not a skin.

## Styling notes

Every class is prefixed `hd-` and every rule is scoped under `.hd-root`, and the custom
properties are `--hd-*`. This matters: Bootstrap 4 (which Twenty-One loads) defines
`.card`, `.btn` and `.badge`, so an unscoped stylesheet would fight it. Verified in a
headless browser with Bootstrap 4 loaded alongside — computed styles match the standalone
design exactly.

To restyle Twenty-One's own navbar and footer to match, target their classes from
`hostdigi.css`; you do not need to override `header.tpl` or `footer.tpl`, and not
overriding them is what keeps upgrades painless.

## Extending it

Override any other Twenty-One page by copying the file from `templates/twenty-one/` into
`templates/hostdigi/` and editing it — `contact.tpl`, `clientareahome.tpl`, and the cart
templates under `templates/orderforms/` are the usual next candidates.
