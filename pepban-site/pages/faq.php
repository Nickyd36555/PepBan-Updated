<?php
$page_title = 'FAQ — PepBan';
require __DIR__ . '/../templates/layout.php';
?>

<section class="pb-section">
  <div class="pb-section-inner">
    <div class="pb-faq-page-header">
      <span class="pb-section-eyebrow">FAQ</span>
      <h1 class="pb-section-title">Frequently Asked Questions</h1>
      <p class="pb-section-sub">Everything you need to know about PepBan and protecting your WooCommerce store.</p>
    </div>

    <div class="pb-faq-list">

      <!-- About PepBan -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          What is PepBan?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan is a centralized banned-customer database built for peptide and supplement stores. Member stores share a single ban list — when one store bans a customer for fraud, chargebacks, or abuse, every other member store is automatically protected from that customer as well.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How does PepBan work?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan connects your WooCommerce store to a shared database via a lightweight plugin. When a customer attempts to check out, the plugin sends their email address to the PepBan API in real-time. If the customer is on the ban list, their order is blocked immediately. If they're not banned, checkout proceeds as normal with zero friction for legitimate customers.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Who is PepBan for?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan is designed specifically for WooCommerce store owners in the peptide and supplement industry. If you run an online store and have dealt with customers who file false chargebacks, commit fraud, or repeatedly abuse return policies, PepBan is built for you.</div>
      </div>

      <!-- Setup & Integration -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How do I get started?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Sign up for an account on this page. You'll get an API key immediately. Then download the PepBan Client plugin from your portal, upload it to your WordPress site, activate it, and enter your API key in the plugin settings. The whole process takes less than five minutes.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          What WooCommerce version is required?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan Client is compatible with WooCommerce 6.0 or later and WordPress 5.8 or later. The vast majority of stores running a reasonably modern WordPress setup will work without any issues. If you're on an older version and unsure about compatibility, reach out and we can help.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Will the plugin slow down my store?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">No. The PepBan API is designed for speed. The lookup request is asynchronous and only fires on the checkout page, not during regular browsing. The API response time is typically under 100ms. Customers will not notice any delay in the checkout process.</div>
      </div>

      <!-- Data & Privacy -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Is my customer data safe?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. PepBan only stores the minimum data needed to identify a banned customer — specifically their email address and the reason for the ban. Your store's full customer records, payment information, and order history never leave your server. All API keys are hashed with bcrypt and are never stored in plaintext.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          What data is sent to PepBan during a checkout check?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">During a checkout check, the plugin sends the customer's email address and your store's API key (for authentication) to the PepBan API. That's it. No names, addresses, order details, payment information, or any other data is transmitted.</div>
      </div>

      <!-- Features -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Can I whitelist a customer at my store?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. The per-site whitelist feature lets you allow a globally banned customer through at your store without removing them from the shared ban list. Other member stores will still block that customer — the whitelist only applies to your store. This is useful if, for example, a customer was banned in error or you have a pre-existing arrangement with them.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How do I report a problem customer?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Once the plugin is installed, a "Report to PepBan" button appears on each order in your WooCommerce admin. Click the button, select the reason (fraud, chargeback, abuse, etc.), and confirm. The customer is immediately added to the shared ban list and all member stores are protected.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Can other stores see the reason I enter when banning a customer?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. The ban reason you enter is stored in the shared PepBan database and is visible to every member store when that customer appears on one of their orders. This is intentional — context helps other stores understand why someone is flagged. Because of this, keep reasons factual and professional (e.g. "Fraudulent chargeback" or "Threatening messages") and avoid including personal opinions, full verbatim quotes, or anything you wouldn't want shared across the network.</div>
      </div>

      <!-- Blocked customers -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          I was blocked at a store — can I dispute this?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. If you believe you were incorrectly flagged, visit our <a href="<?= url('/dispute') ?>">dispute page</a> and submit your case. Include the email address that was blocked and a brief explanation. We review every dispute and will contact you within 3–5 business days. If we determine the ban was an error, your account will be removed from the list and you'll receive a confirmation email.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How long does a dispute take to resolve?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">We aim to review all disputes within 3–5 business days. Once reviewed, you'll receive an email at the address you provided with the outcome. If the dispute is resolved in your favor, the ban is lifted immediately and you'll be able to check out at member stores.</div>
      </div>

      <!-- Pricing & Account -->
      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How much does PepBan cost?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">PepBan is currently in early access. Sign up for an account and get in touch to discuss pricing for your store. We're working with founding members to determine the best pricing structure. Early members receive preferential rates.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          Can I cancel at any time?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">Yes. There are no long-term contracts or cancellation fees. If you decide to stop using PepBan, simply deactivate the plugin on your store and your API key will be revoked. Any bans you submitted to the shared database remain in place for the protection of other member stores.</div>
      </div>

      <div class="pb-faq-item">
        <button class="pb-faq-question" type="button">
          How do I contact PepBan support?
          <svg class="pb-faq-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="pb-faq-answer">You can reach us by emailing support from your registered account email. We aim to respond to all inquiries within one business day. For urgent technical issues, mention "URGENT" in your subject line and we'll prioritize your request.</div>
      </div>

    </div>

    <div style="text-align:center;margin-top:52px">
      <p style="color:var(--text-muted);margin-bottom:20px;font-size:.95rem">Still have questions? We're happy to help.</p>
      <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary pb-btn-lg">Get Started Free</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
