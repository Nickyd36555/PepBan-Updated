<?php
$page_title = 'Changelog — PepBan';
require __DIR__ . '/../templates/layout.php';
?>

<div class="pb-changelog">
  <div class="pb-changelog-header">
    <h1>Changelog</h1>
    <p>Every update to the PepBan plugin, in one place.</p>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.1.3</h2>
      <span class="pb-cl-latest">Latest</span>
      <span class="pb-cl-date">May 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Local blacklist now supports Email, IP Address, and Billing Address blocking — not just email</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Global IP blocking — IPs banned at the hub level are now also checked at checkout via the API (in addition to the existing site-wide visitor block)</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> Billing address blocking uses partial/substring match — block by city, zip code, or full address</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> All four checkout hooks now check IP and address against the local blacklist</li>
    </ul>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.1.2</h2>
      <span class="pb-cl-date">May 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Per-site customer blacklist — block specific customers on your store only</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> "Report to PepBan" button on blacklist — escalate local bans to the global network in one click</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Per-site domain blacklist — block entire email domains (e.g. block all @tempmail.com orders)</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> FunnelKit and custom checkout builder compatibility via <code>woocommerce_after_checkout_validation</code> hook</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> Universal safety net — all checkout types now covered including block-based and headless</li>
    </ul>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.1.0</h2>
      <span class="pb-cl-date">April 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> WordPress auto-update support — updates delivered directly through the WP plugins dashboard</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Hub URL is now embedded — no configuration needed on install</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-fix">Fix</span> Connection status incorrectly showing error even when API was responding correctly</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> Plugin details popup now shows Description, Installation, and Changelog tabs</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> "Check for Updates" button added to settings page for immediate update checks</li>
    </ul>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.0.9</h2>
      <span class="pb-cl-date">March 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Block-based checkout (WooCommerce Blocks / Gutenberg) support</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Order-level safety net — bans checked at order creation as a final fallback for all checkout types</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> Admin ban notice shown on order edit screen for already-placed orders</li>
    </ul>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.0.5</h2>
      <span class="pb-cl-date">February 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> IP blocking — ban specific IP addresses site-wide from the PepBan hub</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-improvement">Improved</span> API error handling — configurable fail-open / fail-closed on API timeout</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-fix">Fix</span> Checkout process hook priority adjusted to prevent conflicts with other plugins</li>
    </ul>
  </div>

  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v1.0.0</h2>
      <span class="pb-cl-date">January 2025</span>
    </div>
    <ul class="pb-cl-items">
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Initial release</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Real-time checkout blocking via PepBan API</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> One-click report from WooCommerce order screen</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Per-site customer whitelisting</li>
      <li class="pb-cl-item"><span class="pb-cl-tag pb-cl-tag-new">New</span> Settings page with API key and block message configuration</li>
    </ul>
  </div>

</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
