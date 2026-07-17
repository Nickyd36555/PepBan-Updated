<?php
$page_title = 'PepBan — Shared Fraud Protection for the Peptide Industry';
require __DIR__ . '/../templates/layout.php';
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="pb-hero pb-hero-v2">
  <?php
  $logo_file = null;
  foreach (['logo.png','logo.jpg','logo.webp','logo.svg'] as $_lf) {
    if (file_exists(__DIR__ . '/../assets/images/' . $_lf)) { $logo_file = $_lf; break; }
  }
  ?>
  <?php if ($logo_file): ?>
  <div class="pb-hero-logo-wrap">
    <img src="<?= url('assets/images/' . $logo_file) ?>" alt="PepBan" class="pb-hero-logo-img">
  </div>
  <?php endif; ?>
  <div class="pb-hero-split">

    <div class="pb-hero-content">
      <div class="pb-hero-badge">Network Live</div>
      <h1 class="pb-hero-title">Shared Fraud Protection<br><span>for the Peptide Industry</span></h1>
      <p class="pb-hero-sub">One Report. Every Store Protected.</p>
      <p class="pb-hero-sub" style="margin-top:8px;font-size:.95em;opacity:.8">PepBan is the centralized ban list shared across peptide stores. One report protects every member store automatically.</p>
      <div class="pb-hero-actions">
        <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Get Protected Now</a>
        <a href="<?= url('/#how-it-works') ?>" class="pb-btn pb-btn-ghost pb-btn-lg">See How It Works</a>
      </div>
      <div class="pb-hero-metrics">
        <div class="pb-metric">
          <span class="pb-metric-num">500+</span>
          <span class="pb-metric-label">Banned Customers</span>
        </div>
        <div class="pb-metric-div"></div>
        <div class="pb-metric">
          <span class="pb-metric-num">50+</span>
          <span class="pb-metric-label">Member Stores</span>
        </div>
        <div class="pb-metric-div"></div>
        <div class="pb-metric">
          <span class="pb-metric-num">Real-Time</span>
          <span class="pb-metric-label">API Protection</span>
        </div>
      </div>
    </div>

    <div class="pb-hero-visual">
      <div class="pb-block-card">
        <div class="pb-block-header">
          <span class="pb-block-dot"></span>
          Checkout Blocked
        </div>
        <div class="pb-block-email">j***hn.doe91@gmail.com</div>
        <div class="pb-block-meta">
          <span class="pb-block-badge">BANNED</span>
          <span>Reported by 4 member stores</span>
        </div>
        <div class="pb-block-reason">Fraudulent chargeback history</div>
        <div class="pb-block-footer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Order cancelled automatically
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ── Features ──────────────────────────────────────────────────────────────── -->
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
        <div class="pb-feature-icon">&#128222;</div>
        <h3>Phone Number Blocking</h3>
        <p>Block customers by phone number on your local blacklist — checked at checkout alongside email, IP, and billing address so they can't slip through with a new account.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128260;</div>
        <h3>Auto-Updates via WordPress</h3>
        <p>New versions land in your WordPress dashboard automatically. No manual downloads, no version lag, no maintenance headaches.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128276;</div>
        <h3>Checkout Attempt Alerts</h3>
        <p>Get an instant email the moment a flagged customer tries to check out on your store — with their details and reason on file, so you're never caught off guard.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128681;</div>
        <h3>One-Click Order Reporting</h3>
        <p>Spot a scammer after the fact? Report them to the entire PepBan network directly from the WooCommerce order screen — no extra steps, no separate dashboard.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128231;</div>
        <h3>Customer Ban Notification</h3>
        <p>Optionally notify the banned customer by email when they're added to the network, with a link to submit a dispute — reducing chargebacks and support requests.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128172;</div>
        <h3>Ban Dispute Link</h3>
        <p>Add a dispute URL to the checkout block message so customers know exactly where to appeal — keeps your inbox clear and gives legitimate cases a fair path forward.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128257;</div>
        <h3>Auto-Report on Block</h3>
        <p>When a banned customer is stopped at checkout, the plugin automatically re-reports them to the network — no manual step needed, keeping the shared database accurate across every store.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128680;</div>
        <h3>Order Ban Warning</h3>
        <p>A red alert banner on the WooCommerce order edit screen flags orders placed by customers in the ban database — showing total reports and how many stores have flagged them.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128064;</div>
        <h3>Customer Watch Notes</h3>
        <p>Flag customers without banning them. Add internal notes by email — shown as an orange warning on every order screen — so staff can track patterns like repeat non-delivery claims before escalating to a ban.</p>
      </div>
      <div class="pb-feature-card">
        <div class="pb-feature-icon">&#128269;</div>
        <h3>Customer Lookup</h3>
        <p>Search any email address from a single screen and instantly see their network ban status, watch notes, and local blacklist status — then add or update a watch note or blacklist entry without leaving the page.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── How it works ───────────────────────────────────────────────────────────── -->
<section class="pb-section pb-section-alt" id="how-it-works">
  <div class="pb-section-inner">
    <div class="pb-steps-heading">
      <h2>Up and running <em>in minutes</em></h2>
      <p>Three steps to join the network and start blocking banned customers automatically.</p>
    </div>
    <div class="pb-steps-v2">
      <div class="pb-steps-connector"></div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">1</div>
        <h3>Sign Up</h3>
        <p>Create your account and get your API key instantly. No waiting, no manual approval.</p>
      </div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">2</div>
        <h3>Install Plugin</h3>
        <p>Download and install the PepBan Client plugin on your WooCommerce store. Paste your API key and save.</p>
      </div>
      <div class="pb-step-v2">
        <div class="pb-step-v2-num">3</div>
        <h3>Stay Protected</h3>
        <p>Your store automatically checks and blocks banned customers at checkout. Report new ones in one click.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── FAQ preview ────────────────────────────────────────────────────────────── -->
<section class="pb-section" id="faq-preview">
  <div class="pb-section-inner">
    <div class="pb-feat-header">
      <h2>Common <em>questions</em></h2>
      <p>Quick answers to what store owners ask most.</p>
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
        <div class="pb-faq-answer">Yes. Only the email address needed for identification is stored. All API keys are hashed and never stored in plaintext. Your store's customer data stays on your server — only ban lookups are sent to PepBan.</div>
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
        <div class="pb-faq-answer">PepBan Client works with WooCommerce 6.0+ and WordPress 5.8+. Most stores running a moderately recent setup are fully compatible.</div>
      </div>
    </div>
    <div style="text-align:center;margin-top:32px">
      <a href="<?= url('/faq') ?>" class="pb-btn pb-btn-ghost">View All FAQs &rarr;</a>
    </div>
  </div>
</section>

<!-- ── CTA ────────────────────────────────────────────────────────────────────── -->
<section class="pb-cta pb-cta-v2">
  <div class="pb-cta-inner pb-cta-box">
    <h2 class="pb-cta-title">Ready to Protect Your Store?</h2>
    <p class="pb-cta-sub">Join the network today and stop scammers before they cost you money.</p>
    <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Create Free Account</a>
  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
