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
    <h2 class="pb-section-title">Everything the plugin does for you</h2>
    <div class="pb-features-grid">
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128683;</div>
        <h3>Real-Time Checkout Blocking</h3>
        <p>Every order is checked against the shared ban database the moment a customer hits checkout — before any money changes hands.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#127758;</div>
        <h3>Global Ban Database</h3>
        <p>Tap into a growing network of peptide stores. A ban reported by one member protects every other store automatically.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128196;</div>
        <h3>Local Blacklist &amp; Whitelist</h3>
        <p>Ban customers at your store only, or whitelist a globally-banned customer you trust — full local control alongside network rules.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128229;</div>
        <h3>Bulk CSV Import</h3>
        <p>Already have a list of bad actors? Import them in one shot via CSV and they're blocked immediately across every store.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128274;</div>
        <h3>IP &amp; Domain Blocking</h3>
        <p>Block by IP address or email domain, not just individual addresses — stop repeat offenders who create new accounts.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128260;</div>
        <h3>Auto-Updates via WordPress</h3>
        <p>New versions land in your WordPress dashboard automatically. No manual downloads, no version lag, no maintenance headaches.</p>
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
