<?php
$page_title = 'PepBan — Stop Peptide Scammers';
require __DIR__ . '/../templates/layout.php';
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="pb-hero">
  <div class="pb-hero-inner">
    <div class="pb-hero-badge">Trusted by Peptide Stores Nationwide</div>
    <h1 class="pb-hero-title">Stop Peptide Scammers<br><span>Dead In Their Tracks</span></h1>
    <p class="pb-hero-sub">PepBan is the centralized ban list shared across peptide stores. One report protects every member store automatically.</p>
    <div class="pb-hero-actions">
      <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Get Protected Now</a>
      <a href="<?= url('/#how-it-works') ?>" class="pb-btn pb-btn-ghost pb-btn-lg">See How It Works</a>
    </div>
  </div>
</section>

<!-- ── Stats bar ─────────────────────────────────────────────────────────────── -->
<div class="pb-stats">
  <div class="pb-stats-inner">
    <div class="pb-stat-item">
      <span class="pb-stat-num">500+</span>
      <span class="pb-stat-label">Banned Customers</span>
    </div>
    <div class="pb-stat-item">
      <span class="pb-stat-num">50+</span>
      <span class="pb-stat-label">Protected Stores</span>
    </div>
    <div class="pb-stat-item">
      <span class="pb-stat-num">Real-Time</span>
      <span class="pb-stat-label">API Protection</span>
    </div>
  </div>
</div>

<!-- ── Features ──────────────────────────────────────────────────────────────── -->
<section class="pb-section" id="features">
  <div class="pb-section-inner">
    <div class="pb-section-header">
      <span class="pb-section-eyebrow">Features</span>
      <h2 class="pb-section-title">Everything You Need to Protect Your Store</h2>
      <p class="pb-section-sub">Built specifically for WooCommerce peptide stores. Install the plugin and you're protected in minutes.</p>
    </div>
    <div class="pb-features-grid">
      <div class="pb-feature-card">
        <span class="pb-feature-icon">🛡️</span>
        <h3 class="pb-feature-title">Centralized Ban List</h3>
        <p class="pb-feature-body">One shared database across all member stores. A ban from any store protects every other store instantly.</p>
      </div>
      <div class="pb-feature-card">
        <span class="pb-feature-icon">⚡</span>
        <h3 class="pb-feature-title">Real-Time Checkout Blocking</h3>
        <p class="pb-feature-body">Customers are checked at checkout automatically. Banned customers are blocked before they can complete a purchase.</p>
      </div>
      <div class="pb-feature-card">
        <span class="pb-feature-icon">📋</span>
        <h3 class="pb-feature-title">One-Click Reporting</h3>
        <p class="pb-feature-body">Report a problem customer directly from your WooCommerce order screen. No forms to fill, no emails to send.</p>
      </div>
      <div class="pb-feature-card">
        <span class="pb-feature-icon">✅</span>
        <h3 class="pb-feature-title">Per-Site Whitelisting</h3>
        <p class="pb-feature-body">Need to allow a banned customer at your store? Whitelist them locally without affecting other stores.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── How it works ───────────────────────────────────────────────────────────── -->
<section class="pb-section pb-section-alt" id="how-it-works">
  <div class="pb-section-inner">
    <div class="pb-section-header">
      <span class="pb-section-eyebrow">Setup</span>
      <h2 class="pb-section-title">Up and Running in Minutes</h2>
      <p class="pb-section-sub">Three simple steps to join the network and start blocking banned customers automatically.</p>
    </div>
    <div class="pb-steps-row">
      <div class="pb-step-card">
        <div class="pb-step-num">1</div>
        <h3 class="pb-step-title">Sign Up</h3>
        <p class="pb-step-body">Create your account and get your API key instantly. No waiting, no manual approval required.</p>
      </div>
      <div class="pb-step-card">
        <div class="pb-step-num">2</div>
        <h3 class="pb-step-title">Install Plugin</h3>
        <p class="pb-step-body">Download and install the PepBan Client plugin on your WooCommerce store in minutes. Paste your API key and save.</p>
      </div>
      <div class="pb-step-card">
        <div class="pb-step-num">3</div>
        <h3 class="pb-step-title">Stay Protected</h3>
        <p class="pb-step-body">Your store automatically checks and blocks banned customers at checkout. Report new ones in one click from the order screen.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── FAQ preview ────────────────────────────────────────────────────────────── -->
<section class="pb-section" id="faq-preview">
  <div class="pb-section-inner">
    <div class="pb-section-header">
      <span class="pb-section-eyebrow">FAQ</span>
      <h2 class="pb-section-title">Common Questions</h2>
      <p class="pb-section-sub">Quick answers to the questions store owners ask most.</p>
    </div>
    <div class="pb-faq-list">
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How does PepBan work?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan connects your WooCommerce store to a shared database of banned customers. When someone tries to checkout, their email is checked against the database in real-time. If they're banned, their order is blocked automatically — no manual work required.</div>
      </div>
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Is my customer data safe?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. Only minimal data needed for identification (email address) is stored. All API keys are hashed and never stored in plaintext. Your store's customer data stays on your server — only ban lookups are sent to PepBan.</div>
      </div>
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Can I whitelist a customer?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. You can whitelist any banned customer at your store without removing them from the global ban list. Other member stores will still block them — only your store will allow them through.</div>
      </div>
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          What WooCommerce version is required?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan Client works with WooCommerce 6.0+ and WordPress 5.8+. Most stores running a moderately recent setup are fully compatible. Contact us if you're unsure about your version.</div>
      </div>
    </div>
    <div style="text-align:center;margin-top:32px">
      <a href="<?= url('/faq') ?>" class="pb-btn pb-btn-ghost">View All FAQs &rarr;</a>
    </div>
  </div>
</section>

<!-- ── CTA ────────────────────────────────────────────────────────────────────── -->
<section class="pb-cta">
  <div class="pb-cta-inner">
    <h2 class="pb-cta-title">Ready to Protect Your Store?</h2>
    <p class="pb-cta-sub">Join the network today and stop scammers before they cost you money.</p>
    <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Create Free Account</a>
  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
