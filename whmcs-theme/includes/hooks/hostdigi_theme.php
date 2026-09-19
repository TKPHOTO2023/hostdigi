<?php
/**
 * Hostdigi child theme - data and asset hooks.
 *
 * Drop this file in /includes/hooks/. It does three things:
 *
 *   1. Loads the theme's CSS/JS on every page, without touching header.tpl
 *      or footer.tpl (so Twenty-One updates never conflict with us).
 *   2. Feeds the custom homepage live product pricing from WHMCS itself,
 *      via the local API - no hard-coded prices anywhere in the template.
 *   3. Feeds the homepage live TLD pricing the same way.
 *
 * Everything is read through localAPI(), which runs in-process as an admin.
 * That means no API identifier/secret, no IP allowlisting and no external
 * HTTP calls are involved.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Admin username used for local API calls.
 *
 * Leave as null to use the one configured at
 * Configuration > System Settings > General > Admin API Username.
 * Otherwise set it to an admin account with API access, e.g. 'apiuser'.
 */
const HOSTDIGI_API_ADMIN = null;

/**
 * Which WHMCS product groups appear on the homepage, and what each tab is
 * called.
 *
 *     'Tab label' => 'WHMCS product group name'   (or its numeric id)
 *
 * The group name is exactly as it appears under
 * Configuration > Products/Services > Products/Services - matching is
 * case-insensitive, so 'cloud shared hosting' works too. A numeric id is
 * still accepted if you prefer to pin it.
 *
 * Tabs appear in the order listed here. One entry = no tab bar, just the
 * plans.
 */
const HOSTDIGI_PLAN_GROUPS = [
    'Cloud Hosting' => 'Cloud Shared Hosting',
    // 'WordPress'  => 'WordPress Hosting',
    // 'Reseller'   => 'Reseller Hosting',
];

/**
 * The ONE product to flag "Most popular". Matched case-insensitively against
 * the product name. Set to '' to flag nothing.
 *
 * Only the first match is flagged - two "most popular" badges defeat the
 * purpose.
 */
const HOSTDIGI_FEATURED_PRODUCT = 'Business Cloud';

/**
 * Short brand name for headings and body copy. WHMCS's own company name is
 * often the legal entity ("Hostdigi Pty Ltd"), which reads badly mid-sentence.
 * Leave '' to fall back to the WHMCS company name.
 */
const HOSTDIGI_BRAND = 'Hostdigi';

/** Sort plans cheapest-first rather than by WHMCS's product order. */
const HOSTDIGI_SORT_BY_PRICE = true;

/** TLDs to feature on the homepage, in display order. */
const HOSTDIGI_FEATURED_TLDS = ['.co.za', '.africa', '.com', '.net', '.org', '.io'];

/** How long (seconds) to cache API reads. Pricing rarely changes; 0 disables. */
const HOSTDIGI_CACHE_TTL = 900;

/* ------------------------------------------------------------------ assets */

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    $root = rtrim($vars['WEB_ROOT'] ?? '', '/');
    $v = '1.0.0'; // bump to bust browser caches after a CSS change
    return <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{$root}/templates/hostdigi/assets/css/hostdigi.css?v={$v}">
<link rel="stylesheet" href="{$root}/templates/hostdigi/assets/css/hostdigi-whmcs.css?v={$v}">
HTML;
});

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $root = rtrim($vars['WEB_ROOT'] ?? '', '/');
    $v = '1.0.0';
    return '<script src="' . $root . '/templates/hostdigi/assets/js/hostdigi.js?v=' . $v . '" defer></script>';
});

/* -------------------------------------------------------------- local API */

/**
 * Run a local API call and cache the result briefly.
 *
 * @return array Decoded API response, or [] when the call failed.
 */
function hostdigi_api($action, array $params = [])
{
    $key = 'hostdigi_' . strtolower($action) . '_' . md5(serialize($params));

    if (HOSTDIGI_CACHE_TTL > 0) {
        try {
            $row = Capsule::table('tblconfiguration')->where('setting', $key)->first();
            if ($row) {
                $cached = json_decode($row->value, true);
                if (is_array($cached) && ($cached['expires'] ?? 0) > time()) {
                    return $cached['data'];
                }
            }
        } catch (\Exception $e) {
            // Cache read failures are never fatal - fall through to a live call.
        }
    }

    $result = localAPI($action, $params, HOSTDIGI_API_ADMIN);

    if (!is_array($result) || ($result['result'] ?? '') !== 'success') {
        logActivity('Hostdigi theme: ' . $action . ' failed - ' . ($result['message'] ?? 'unknown error'));
        return [];
    }

    if (HOSTDIGI_CACHE_TTL > 0) {
        $payload = json_encode(['expires' => time() + HOSTDIGI_CACHE_TTL, 'data' => $result]);
        try {
            if (Capsule::table('tblconfiguration')->where('setting', $key)->exists()) {
                Capsule::table('tblconfiguration')->where('setting', $key)->update(['value' => $payload]);
            } else {
                Capsule::table('tblconfiguration')->insert(['setting' => $key, 'value' => $payload]);
            }
        } catch (\Exception $e) {
            // A failed cache write must not break the page.
        }
    }

    return $result;
}

/**
 * Format an amount for the pricing cards.
 *
 * formatCurrency() returns the full "R50.00 ZAR" form - prefix, decimals and
 * the currency code. On a pricing card that is noise, so this uses the active
 * currency's prefix only and drops .00 on whole amounts. The currency code is
 * stated once, under the cards.
 */
function hostdigi_price($amount, $currency = null)
{
    $amount = (float) $amount;
    $prefix = $currency && isset($currency->prefix) ? $currency->prefix : 'R';

    $formatted = $amount == (int) $amount
        ? number_format($amount, 0, '.', ' ')
        : number_format($amount, 2, '.', ' ');

    return $prefix . $formatted;
}


/**
 * Resolve a configured product group to its numeric id.
 *
 * Accepts a numeric id unchanged, or looks a group up by name so the config
 * above can read like the WHMCS admin area does.
 *
 * @return int|null The group id, or null when no such group exists.
 */
function hostdigi_group_id($group)
{
    if (is_numeric($group)) {
        return (int) $group;
    }

    try {
        $row = Capsule::table('tblproductgroups')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($group))])
            ->first();
    } catch (\Exception $e) {
        logActivity('Hostdigi theme: could not look up product group "' . $group . '" - ' . $e->getMessage());
        return null;
    }

    if (!$row) {
        logActivity('Hostdigi theme: no product group named "' . $group . '" - check HOSTDIGI_PLAN_GROUPS.');
        return null;
    }

    return (int) $row->id;
}

/* ----------------------------------------------------------- homepage data */

add_hook('ClientAreaPageHome', 1, function ($vars) {
    $groups = [];
    $currency = $vars['activeCurrency'] ?? null;
    $featuredTaken = false;

    foreach (HOSTDIGI_PLAN_GROUPS as $label => $group) {
        $gid = hostdigi_group_id($group);
        if ($gid === null) {
            continue;
        }

        $response = hostdigi_api('GetProducts', ['gid' => $gid]);
        $products = $response['products']['product'] ?? [];

        $plans = [];
        foreach ($products as $product) {
            $pricing = $product['pricing'][$vars['activeCurrency']->code ?? ''] ?? null;
            if (!$pricing) {
                // Fall back to the first currency WHMCS returned for this product.
                $pricing = is_array($product['pricing'] ?? null) ? reset($product['pricing']) : [];
            }

            $monthly = (float) ($pricing['monthly'] ?? -1);
            $annually = (float) ($pricing['annually'] ?? -1);

            // A cycle priced -1 in WHMCS means "not available" - skip those.
            $plans[] = [
                'pid'          => (int) $product['pid'],
                'name'         => $product['name'],
                'description'  => $product['description'],
                'monthly'      => $monthly >= 0 ? hostdigi_price($monthly, $currency) : null,
                'annually'     => $annually >= 0 ? hostdigi_price($annually, $currency) : null,
                'annualPerMonth' => $annually >= 0 ? hostdigi_price($annually / 12, $currency) : null,
                'sort'         => $monthly >= 0 ? $monthly : ($annually >= 0 ? $annually / 12 : PHP_INT_MAX),
                'features'     => hostdigi_features($product['description']),
                'orderUrl'     => 'cart.php?a=add&pid=' . (int) $product['pid'],
                'featured'     => false,
            ];
        }

        if (HOSTDIGI_SORT_BY_PRICE) {
            usort($plans, function ($a, $b) {
                return $a['sort'] <=> $b['sort'];
            });
        }

        // Flag at most one plan across all groups.
        if (!$featuredTaken && HOSTDIGI_FEATURED_PRODUCT !== '') {
            foreach ($plans as $i => $plan) {
                if (stripos($plan['name'], HOSTDIGI_FEATURED_PRODUCT) !== false) {
                    $plans[$i]['featured'] = true;
                    $featuredTaken = true;
                    break;
                }
            }
        }

        if ($plans) {
            $groups[] = [
                'label' => $label,
                'slug'  => preg_replace('/[^a-z0-9]+/', '-', strtolower($label)),
                'plans' => $plans,
            ];
        }
    }

    return [
        'hdPlanGroups' => $groups,
        'hdTlds'       => hostdigi_tld_pricing($vars),
        'hdBrand'      => HOSTDIGI_BRAND !== '' ? HOSTDIGI_BRAND : ($vars['companyname'] ?? 'Hostdigi'),
    ];
});


/**
 * Turn a product description into feature bullets.
 *
 * WHMCS product descriptions are free text, so we treat each new line as one
 * bullet. Write your product descriptions one feature per line and the
 * homepage cards fill themselves in.
 */
function hostdigi_features($description)
{
    $text = (string) $description;

    // Product descriptions are written by hand and split their lines in
    // whichever way the editor produced: real newlines, <br>, <br/>, <br />,
    // or a mix. Normalise all of them to newlines first.
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = preg_replace('/<\/(p|li|div)>/i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    $lines = preg_split('/\r\n|\r|\n/', $text);
    $lines = array_map(function ($line) {
        return trim($line, " \t\n\r\0\x0B\xC2\xA0-*\u{2022}");
    }, $lines);
    $lines = array_values(array_filter($lines, 'strlen'));

    return array_slice($lines, 0, 7);
}

/** Featured TLD pricing, straight from WHMCS's domain pricing table. */
function hostdigi_tld_pricing($vars)
{
    $currencyId = (int) ($vars['activeCurrency']->id ?? 1);
    $response = hostdigi_api('GetTLDPricing', ['currencyid' => $currencyId]);
    $pricing = $response['pricing'] ?? [];

    $out = [];
    foreach (HOSTDIGI_FEATURED_TLDS as $tld) {
        $key = ltrim($tld, '.');
        if (!isset($pricing[$key])) {
            continue;
        }
        $register = $pricing[$key]['register'] ?? [];
        $transfer = $pricing[$key]['transfer'] ?? [];
        $renew    = $pricing[$key]['renew'] ?? [];

        // Registry pricing is keyed by number of years; show the 1-year price
        // where it exists, otherwise the cheapest term offered.
        $first = function ($prices) {
            if (!is_array($prices) || !$prices) {
                return null;
            }
            return $prices[1] ?? reset($prices);
        };

        $reg = $first($register);
        if ($reg === null) {
            continue;
        }

        $currency = $vars['activeCurrency'] ?? null;

        $out[] = [
            'tld'      => '.' . $key,
            'register' => hostdigi_price($reg, $currency),
            'transfer' => ($t = $first($transfer)) !== null ? hostdigi_price($t, $currency) : '-',
            'renew'    => ($r = $first($renew)) !== null ? hostdigi_price($r, $currency) : '-',
        ];
    }

    return $out;
}
