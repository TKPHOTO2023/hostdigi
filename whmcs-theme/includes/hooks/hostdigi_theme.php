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
 * Product group IDs to show on the homepage, in the order of the tabs.
 * Find these under Configuration > Products/Services > Products/Services -
 * the gid is in the URL when you edit a group.
 */
const HOSTDIGI_PLAN_GROUPS = [
    'shared'    => 1,   // <- set to your Web Hosting group id
    'wordpress' => 2,   // <- set to your WordPress group id
    'reseller'  => 3,   // <- set to your Reseller group id
];

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

/** Format an amount using WHMCS's own currency formatting. */
function hostdigi_price($amount)
{
    $formatted = formatCurrency((float) $amount);
    // formatCurrency() returns e.g. "R129.00" - drop trailing .00 for display.
    return preg_replace('/([.,])00$/', '', $formatted);
}

/* ----------------------------------------------------------- homepage data */

add_hook('ClientAreaPageHome', 1, function ($vars) {
    $groups = [];

    foreach (HOSTDIGI_PLAN_GROUPS as $slug => $gid) {
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
                'monthly'      => $monthly >= 0 ? hostdigi_price($monthly) : null,
                'annually'     => $annually >= 0 ? hostdigi_price($annually) : null,
                'annualPerMonth' => $annually >= 0 ? hostdigi_price($annually / 12) : null,
                'features'     => hostdigi_features($product['description']),
                'orderUrl'     => 'cart.php?a=add&pid=' . (int) $product['pid'],
                'featured'     => stripos($product['name'], 'business') !== false
                                  || stripos($product['name'], 'grow') !== false,
            ];
        }

        if ($plans) {
            $groups[$slug] = $plans;
        }
    }

    return [
        'hdPlanGroups' => $groups,
        'hdTlds'       => hostdigi_tld_pricing($vars),
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
    $lines = preg_split('/\r\n|\r|\n/', (string) $description);
    $lines = array_values(array_filter(array_map('trim', $lines), 'strlen'));
    return array_slice($lines, 0, 6);
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

        $out[] = [
            'tld'      => '.' . $key,
            'register' => hostdigi_price($reg),
            'transfer' => ($t = $first($transfer)) !== null ? hostdigi_price($t) : '-',
            'renew'    => ($r = $first($renew)) !== null ? hostdigi_price($r) : '-',
        ];
    }

    return $out;
}
