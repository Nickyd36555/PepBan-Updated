<?php
$page_title = 'PepBan Plugin — Block Scammers at Checkout';
require __DIR__ . '/../templates/layout.php';
?>

<section class="pb-hero pb-hero-v2">
  <div class="pb-hero-split">
    <div class="pb-hero-content">
      <div class="pb-hero-badge">WooCommerce Plugin</div>
      <h1 class="pb-hero-title">Stop Fraudulent Orders<br><span>Before They Happen</span></h1>
      <p class="pb-hero-sub pb-hero-subtitle">The PepBan plugin connects your WooCommerce store to a shared ban database built by and for peptide stores. One report from any store protects every member automatically — in real time.</p>
      <div class="pb-hero-actions">
        <?php if (Auth::isClient()): ?>
          <a href="<?= url('/download/client') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Download Plugin</a>
        <?php else: ?>
          <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Sign Up Free</a>
          <a href="<?= url('/login') ?>" class="pb-btn pb-btn-ghost pb-btn-lg">Log In</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="pb-section" id="features">
  <div class="pb-section-inner">
    <div class="pb-feat-header">
      <h2>Everything you need to <em>protect your store</em></h2>
      <p>Built specifically for WooCommerce peptide stores. Install the plugin and you're protected in minutes.</p>
    </div>
    <div class="pb-feat-grid">
      <div class="pb-feat-item">
        <span class="pb-feat-label">01 — Network</span>
        <h3>Centralized Ban List</h3>
        <p>One shared database across all member stores. A ban from any store protects every other store instantly.</p>
      </div>
      <div class="pb-feat-item">
        <span class="pb-feat-label">02 — Blocking</span>
        <h3>Real-Time Checkout Protection</h3>
        <p>Customers are checked at checkout before the order is placed. Banned customers are blocked before they cost you a cent.</p>
      </div>
      <div class="pb-feat-item">
        <span class="pb-feat-label">03 — Reporting</span>
        <h3>One-Click Reporting</h3>
        <p>Report a problem customer directly from your WooCommerce order screen. No forms to fill, no emails to send.</p>
      </div>
      <div class="pb-feat-item">
        <span class="pb-feat-label">04 — Control</span>
        <h3>Per-Site Whitelisting</h3>
        <p>Need to allow a banned customer at your store? Whitelist them locally without affecting any other member stores.</p>
      </div>
    </div>
  </div>
</section>

<section class="pb-section pb-section-alt" id="how-it-works">
  <div class="pb-section-inner">
    <h2 class="pb-section-title">How it works</h2>
    <div class="pb-steps-v2">
      <div class="pb-steps-connector"></div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">1</div>
        <h3>A store reports a scammer</h3>
        <p>Any member store submits a ban report directly from their WooCommerce order screen — no extra forms needed.</p>
      </div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">2</div>
        <h3>Added to shared database</h3>
        <p>The customer's details are added to the PepBan network database, instantly visible to every connected store.</p>
      </div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">3</div>
        <h3>All stores block them at checkout</h3>
        <p>Every member store's plugin checks against the database in real time. The banned customer is stopped before they complete another order — anywhere on the network.</p>
      </div>
    </div>
  </div>
</section>

<section class="pb-cta pb-cta-v2">
  <div class="pb-cta-inner pb-cta-box">
    <h2 class="pb-cta-title">Ready to protect your store?</h2>
    <p class="pb-cta-sub">Join the network and start blocking fraudulent orders today.</p>
    <?php if (Auth::isClient()): ?>
      <a href="<?= url('/download/client') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Download Plugin</a>
    <?php else: ?>
      <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Sign Up Free</a>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
