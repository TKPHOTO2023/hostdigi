# Hostdigi — revamped front-end

The marketing front-end for [hostdigi.co.za](https://hostdigi.co.za/) — a single
self-contained `index.html` (styles, scripts and SVG icons all inline; no build step and no
dependencies beyond a Google Fonts link).

## Deploying

`index.html` sits at the repo root, so any static host serves it as-is:

- **Vercel** — import the repo at [vercel.com/new](https://vercel.com/new); it is detected as
  a static site with no framework, build command or environment variables needed.
- **Cloudflare Pages / Netlify** — same: no build command, output directory `/`.
- **GitHub Pages** — Settings → Pages → deploy from `main` / root.

## Pages

Hash-routed single page app: `#/` (home), `#/hosting`, `#/domains`, `#/wordpress`,
`#/cloud`, `#/pricing`, `#/support`.

## Editing content

Everything — plans, prices, TLD table, features, testimonials, FAQs — lives in the
`DATA` object at the top of the `<script>` block. Nothing else needs touching to
re-price the site.

```js
const DATA = {
  currency: 'R',
  tlds:  [ { tld:'.co.za', reg:99, transfer:99, renew:120, popular:true }, ... ],
  plans: { shared:[...], wordpress:[...], reseller:[...] },
  vps:   [...],
  ...
};
```

- Monthly amounts are **ZAR excluding VAT**.
- Annual pricing is derived as `month * 10` ("2 months free"). Set `annual: <number>`
  on a plan to override that with a real yearly figure.
- Add `featured: true` to a plan to give it the "Most popular" treatment.

> **The prices committed here are placeholders** benchmarked against the SA market.
> Replace them with Hostdigi's real WHMCS product pricing before launch.

## WHMCS child theme

`whmcs-theme/` holds a **Twenty-One child theme** that puts this design inside WHMCS
itself, so products, pricing, cart and client area come from your install with no API
keys and no syncing. See [whmcs-theme/README.md](whmcs-theme/README.md) — that is the
recommended way to run this site.

The standalone `index.html` below remains useful as a landing page or design reference.

## Wiring the standalone version to WHMCS

| Element | Currently | To go live |
|---|---|---|
| Domain search (`searchDomains()`) | Deterministic demo availability | POST to `domainchecker.php`, or the WHMCS API `DomainWhois` action |
| "Choose <plan>" buttons | Link to `#/support` | `cart.php?a=add&pid=<product id>` |
| "Add to cart" on a domain | Link to `#/support` | `cart.php?a=add&domain=register&query=<name>` |
| Client Area | Link to `#/support` | `clientarea.php` |
| Contact form | Validates locally, sends nowhere | WHMCS ticket API (`OpenTicket`) or a form handler |

## Accessibility & performance notes

- Light and dark themes, remembered in `localStorage`, with an explicit `data-theme` attribute.
- Full keyboard support: skip link, visible focus rings, `role="tablist"`/`aria-selected`
  plan tabs, `role="switch"` billing toggle, `aria-expanded` FAQ accordions and mobile menu.
- `prefers-reduced-motion` disables every animation, counter and reveal transition.
- No layout images — all iconography is inline SVG, so there is nothing to lazy-load.
- Verified with headless Chromium: no console errors, zero horizontal overflow at 390px.
