{*
    Hostdigi homepage.

    Overrides templates/twenty-one/homepage.tpl. Everything priced on this page
    comes from WHMCS via includes/hooks/hostdigi_theme.php - there are no
    hard-coded prices here.

    Available from the hook:
      $hdPlanGroups  list of groups, each with label, slug and plans; every
                     plan has name, description, monthly, annualPerMonth,
                     annually, features, orderUrl, featured
      $hdTlds        list of tld, register, transfer, renew
*}
<div class="hd-root" data-theme="dark">

  {* ---------------------------------------------------------------- hero *}
  <section class="hd-hero">
    <div class="hd-hero-bg" aria-hidden="true"></div>
    <div class="hd-wrap hd-hero-grid">
      <div class="hd-hero-copy">
        <span class="hd-badge"><i class="hd-dot"></i> Hosted in South Africa &middot; JHB &amp; CPT</span>
        <h1>Reliable hosting.<br><span class="hd-accent">Seamless domains.</span></h1>
        <p class="hd-lede">{$hdBrand} runs your website on NVMe servers peered locally at NAPAfrica &mdash; so pages load in milliseconds for the people who actually buy from you. Free SSL, free migration, and support that answers in minutes, not days.</p>
        <div class="hd-hero-cta">
          <a class="hd-btn hd-btn-primary" href="#plans">See hosting plans</a>
          <a class="hd-btn hd-btn-ghost" href="{$WEB_ROOT}/contact.php">Talk to a human</a>
        </div>
        <div class="hd-hero-proof">
          <span>99.9% uptime SLA</span>
          <span>Free migration</span>
          <span>30-day money back</span>
        </div>
      </div>

      <div class="hd-console">
        <div class="hd-console-bar"><i></i><i></i><i></i><span class="hd-t">{$hdBrand|lower} &middot; status &middot; za-jhb-01</span></div>
        <div class="hd-console-body">
          <div class="hd-metric-row">
            <div class="hd-metric"><div class="hd-k">Uptime 30d</div><div class="hd-v">99.98<small>%</small></div></div>
            <div class="hd-metric"><div class="hd-k">TTFB Gauteng</div><div class="hd-v">41<small>ms</small></div></div>
            <div class="hd-metric"><div class="hd-k">Sites hosted</div><div class="hd-v">7 400<small>+</small></div></div>
          </div>
          <div class="hd-log">
            <div><b>&check;</b> LiteSpeed cache warmed &middot; 12 ms</div>
            <div><b>&check;</b> Let's Encrypt SSL renewed</div>
            <div><span class="hd-warn">&#9889;</span> Nightly off-site backup complete &middot; 04:02 SAST</div>
            <div><b>&check;</b> DDoS filter active &middot; 0 incidents today</div>
          </div>
        </div>
      </div>
    </div>

    {* ------------------------------------------------------ domain search *}
    <div class="hd-wrap" style="margin-top:38px">
      <div class="hd-dsearch">
        {*
            This posts to WHMCS's own domain checker, so results, availability
            and pricing are all WHMCS's - nothing to sync.

            If your install's search form differs, open
            templates/twenty-one/homepage.tpl, copy its <form> block here, and
            keep the hd-* classes below on the wrapper, input and button.
        *}
        <form class="hd-dsearch-row" method="post" action="{$WEB_ROOT}/domainchecker.php" role="search">
          <input type="hidden" name="token" value="{$token}">
          <input type="hidden" name="direct" value="true">
          <div class="hd-dsearch-field">
            <label class="hd-sr-only" for="hdDomain">Search for a domain name</label>
            <input id="hdDomain" type="text" name="domain" placeholder="yourbusiness.co.za" autocomplete="off" spellcheck="false" required>
          </div>
          <button class="hd-btn hd-btn-primary" type="submit">Search domains</button>
        </form>

        {if $hdTlds}
          <div class="hd-dsearch-tlds">
            {foreach $hdTlds as $tld}
              <span class="hd-tld-chip"><b>{$tld.tld}</b><i>from {$tld.register}/yr</i></span>
            {/foreach}
          </div>
        {/if}
      </div>
    </div>
  </section>

  {* --------------------------------------------------------------- strip *}
  <div class="hd-strip">
    <div class="hd-wrap hd-strip-inner">
      <div class="hd-strip-item"><span><b>99.98%</b>Uptime, last 30 days</span></div>
      <div class="hd-strip-item"><span><b>41 ms</b>Median TTFB from Gauteng</span></div>
      <div class="hd-strip-item"><span><b>&lt; 12 min</b>Median first ticket reply</span></div>
      <div class="hd-strip-item"><span><b>7 400+</b>Websites hosted</span></div>
      <div class="hd-strip-item"><span><b>R0</b>Migration &amp; setup fees</span></div>
    </div>
  </div>

  {* --------------------------------------------------------------- plans *}
  <section class="hd-section" id="plans">
    <div class="hd-wrap">
      <div class="hd-section-head hd-center">
        <span class="hd-eyebrow">Hosting plans</span>
        <h2>Pick the plan, not the puzzle</h2>
        <p class="hd-lede">Every plan includes free SSL, daily backups, cPanel and unlimited email. No setup fees, ever.</p>
      </div>

      {if $hdPlanGroups}
        <div class="hd-plan-toolbar">
          {* One group needs no tab bar - the empty div keeps the toolbar layout. *}
          <div class="hd-tabs" role="tablist" aria-label="Hosting type"{if $hdPlanGroups|count < 2} hidden{/if}>
            {foreach $hdPlanGroups as $group}
              <button class="hd-tab" role="tab" type="button" data-hd-type="{$group.slug}" aria-selected="{if $group@first}true{else}false{/if}">{$group.label|escape}</button>
            {/foreach}
          </div>
          <div class="hd-cycle">
            <span>Monthly</span>
            <button class="hd-switch" type="button" id="hdCycle" role="switch" aria-checked="true" aria-label="Toggle annual billing"></button>
            <span>Annual</span>
          </div>
        </div>

        {foreach $hdPlanGroups as $group}
          <div class="hd-plans" data-hd-panel="{$group.slug}"{if !$group@first} hidden{/if}>
            {foreach $group.plans as $plan}
              <article class="hd-plan{if $plan.featured} hd-featured{/if}">
                {if $plan.featured}<span class="hd-plan-flag">Most popular</span>{/if}
                <div>
                  <h3>{$plan.name}</h3>
                  <p class="hd-blurb">{$plan.description|truncate:90:"...":true|escape}</p>
                </div>

                <div class="hd-price">
                  <span class="hd-amt" data-hd-monthly="{$plan.monthly|escape}" data-hd-annual="{$plan.annualPerMonth|escape}">{if $plan.annualPerMonth}{$plan.annualPerMonth}{else}{$plan.monthly}{/if}</span>
                  <span class="hd-per">/month</span>
                </div>
                <p class="hd-price-note">
                  <span data-hd-note="annual"{if !$plan.annually} hidden{/if}>{$plan.annually} billed yearly</span>
                  <span data-hd-note="monthly" hidden>Billed monthly &middot; cancel any time</span>
                </p>

                {if $plan.features}
                  <ul>
                    {foreach $plan.features as $feature}<li><span>{$feature|escape}</span></li>{/foreach}
                  </ul>
                {/if}

                <a class="hd-btn {if $plan.featured}hd-btn-primary{else}hd-btn-ghost{/if}" href="{$WEB_ROOT}/{$plan.orderUrl}">Choose {$plan.name|escape}</a>
              </article>
            {/foreach}
          </div>
        {/foreach}
      {else}
        {* The hook returned nothing - check HOSTDIGI_PLAN_GROUPS in the hook file. *}
        <p class="hd-lede hd-center">Our plans are being updated. <a href="{$WEB_ROOT}/cart.php">Browse the store</a>.</p>
      {/if}

      <p class="hd-form-note hd-center" style="margin-top:18px">Prices shown in {$activeCurrency->code} &middot; 30-day money-back guarantee.</p>
    </div>
  </section>

  {* ----------------------------------------------------------------- why *}
  <section class="hd-section" style="background:var(--hd-bg-2);border-block:1px solid var(--hd-line)">
    <div class="hd-wrap">
      <div class="hd-section-head">
        <span class="hd-eyebrow">Why {$hdBrand}</span>
        <h2>Built for South African traffic first</h2>
        <p class="hd-lede">Most "cheap" hosting serves your site from Europe or the US. Every extra hop costs you a conversion. We keep the whole stack local.</p>
      </div>
      <div class="hd-grid hd-g-3">
        <article class="hd-card hd-feat"><h3>NVMe + LiteSpeed everywhere</h3><p>Every shared server runs LiteSpeed Enterprise on NVMe drives, with per-account CPU and I/O limits so one busy site can never slow yours down.</p></article>
        <article class="hd-card hd-feat hd-c2"><h3>Peered at NAPAfrica</h3><p>Traffic to Telkom, Vodacom, MTN, Rain and Afrihost subscribers stays inside South Africa &mdash; no trip to Europe and back on every request.</p></article>
        <article class="hd-card hd-feat hd-c3"><h3>Security on by default</h3><p>Free SSL, malware scanning, WAF rules, brute-force protection and nightly off-site backups on every single plan.</p></article>
        <article class="hd-card hd-feat"><h3>Support by engineers</h3><p>Tickets go straight to people who can read a log file. Median first response is under 12 minutes during SA business hours.</p></article>
        <article class="hd-card hd-feat hd-c2"><h3>Free, scheduled migrations</h3><p>We move sites, databases and mailboxes from any host, test on staging, then cut DNS over at a time that suits your traffic.</p></article>
        <article class="hd-card hd-feat hd-c3"><h3>Rand pricing, no renewal shock</h3><p>You renew at the price you signed up at. No dollar-linked billing surprises when the exchange rate moves against you.</p></article>
      </div>
    </div>
  </section>

  {* ------------------------------------------------------- domain pricing *}
  {if $hdTlds}
    <section class="hd-section">
      <div class="hd-wrap">
        <div class="hd-section-head">
          <span class="hd-eyebrow">Domains</span>
          <h2>Domain pricing</h2>
          <p class="hd-lede">Live from our registry pricing, in {$activeCurrency->code}.</p>
        </div>
        <div class="hd-table-wrap">
          <table>
            <thead><tr><th>Extension</th><th>Register</th><th>Transfer</th><th>Renew</th></tr></thead>
            <tbody>
              {foreach $hdTlds as $tld}
                <tr><td class="hd-tld">{$tld.tld}</td><td class="hd-num">{$tld.register}</td><td class="hd-num">{$tld.transfer}</td><td class="hd-num">{$tld.renew}</td></tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </section>
  {/if}

  {* ------------------------------------------------------------ announcements *}
  {if $announcements}
    <section class="hd-section" style="background:var(--hd-bg-2);border-block:1px solid var(--hd-line)">
      <div class="hd-wrap">
        <div class="hd-section-head"><span class="hd-eyebrow">Latest</span><h2>News from {$hdBrand}</h2></div>
        <div class="hd-grid hd-g-3">
          {foreach $announcements as $announcement}
            <article class="hd-card">
              <h3>{$announcement.title}</h3>
              <p class="hd-lede" style="font-size:.9rem">{$announcement.date}</p>
              <a class="hd-btn hd-btn-ghost hd-btn-sm" href="{routePath('announcement-view', $announcement.id, $announcement.urlfriendlytitle)}">Read more</a>
            </article>
          {/foreach}
        </div>
      </div>
    </section>
  {/if}

  {* ------------------------------------------------------------------ cta *}
  <section class="hd-section">
    <div class="hd-wrap">
      <div class="hd-band">
        <span class="hd-eyebrow">Ready when you are</span>
        <h2>Get your site on a host that keeps up</h2>
        <p class="hd-lede" style="text-align:center">Start in under five minutes. Keep your domain, keep your email, keep your weekends.</p>
        <div class="hd-hero-cta" style="justify-content:center">
          <a class="hd-btn hd-btn-primary" href="#plans">Choose a plan</a>
          <a class="hd-btn hd-btn-ghost" href="{$WEB_ROOT}/cart.php?a=add&amp;domain=register">Search a domain</a>
        </div>
      </div>
    </div>
  </section>

</div>
