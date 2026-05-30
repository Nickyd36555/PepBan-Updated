<?php
$page_title = 'Privacy Policy — PepBan';
require __DIR__ . '/../templates/layout.php';
?>

<section class="pb-section">
  <div class="pb-section-inner" style="max-width:760px">

    <div style="margin-bottom:48px">
      <span class="pb-section-eyebrow">Legal</span>
      <h1 class="pb-section-title" style="text-align:left;margin-bottom:12px">Privacy Policy</h1>
      <p style="color:var(--text-muted);font-size:.9rem">Last updated: <?= date('F j, Y') ?></p>
    </div>

    <div class="pb-prose">

      <h2>Who we are</h2>
      <p>PepBan operates a fraud-prevention network for WooCommerce stores. Our service allows member stores to share information about customers who have committed fraud, filed false chargebacks, or engaged in other abusive behaviour. This policy explains what personal data we collect, how we use it, and your rights regarding that data.</p>
      <p>When we say <strong>"we"</strong>, <strong>"us"</strong>, or <strong>"PepBan"</strong>, we mean the operators of pepban.com. When we say <strong>"member store"</strong>, we mean a business that has signed up to use the PepBan service. When we say <strong>"customer"</strong>, we mean an individual whose information appears in the PepBan database.</p>

      <h2>Data we collect and why</h2>

      <h3>Information about flagged customers</h3>
      <p>When a member store reports a customer to PepBan, we may receive and store:</p>
      <ul>
        <li>Email address</li>
        <li>First and last name</li>
        <li>Phone number</li>
        <li>Billing address</li>
        <li>IP address (as reported by the member store)</li>
        <li>The reason the customer was flagged (e.g. chargeback, fraud)</li>
        <li>The name and URL of the store that reported the customer</li>
      </ul>
      <p>This data is used solely to operate the fraud-prevention network — specifically, to allow member stores to check whether a customer is on the shared ban list before accepting an order.</p>

      <h3>Information about member stores</h3>
      <p>When a business signs up as a member store, we collect:</p>
      <ul>
        <li>Owner name and email address</li>
        <li>Store URL</li>
        <li>A hashed API key (we never store your key in plaintext)</li>
      </ul>
      <p>This is used to authenticate API requests and to contact you about your account.</p>

      <h3>Information from dispute submissions</h3>
      <p>If you submit a ban dispute through our dispute page, we collect the email address, name, and explanation you provide. This is used solely to review and respond to your dispute.</p>

      <h3>Contact form submissions</h3>
      <p>Messages sent through our contact page are forwarded to our support team by email and are not stored in our database.</p>

      <h2>How we use your data</h2>
      <ul>
        <li>To operate the shared ban-list service for member stores</li>
        <li>To send transactional emails (ban notices, dispute outcomes, API key delivery)</li>
        <li>To review and respond to ban disputes</li>
        <li>To contact member stores about their accounts</li>
      </ul>
      <p>We do not sell personal data. We do not use personal data for advertising or profiling. We do not share data with any third party outside the operation of the PepBan network.</p>

      <h2>Who can see your data</h2>
      <p>Member stores that query the PepBan API receive confirmation of whether a given email address is on the ban list, along with limited details (name, reason, report count). They do not receive billing addresses, phone numbers, or IP addresses through the standard API.</p>
      <p>PepBan administrators can access all data stored in the database for the purpose of managing disputes and maintaining the service.</p>

      <h2>Data retention</h2>
      <p>Customer records remain in the database until an admin removes them — either as the result of a successful dispute or a manual deletion. If your dispute is resolved in your favour, your record is removed from the active ban list and member stores will no longer be informed of your status.</p>

      <h2>Your rights</h2>
      <p>Depending on where you live, you may have the right to:</p>
      <ul>
        <li><strong>Access</strong> — request a copy of the data we hold about you</li>
        <li><strong>Correction</strong> — request that inaccurate data be corrected</li>
        <li><strong>Erasure</strong> — request that your data be deleted</li>
        <li><strong>Object</strong> — object to processing based on legitimate interests</li>
      </ul>
      <p>To exercise any of these rights, submit a dispute at <a href="<?= url('/dispute') ?>">pepban.com/dispute</a> or email us directly at <a href="mailto:<?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?>"><?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?></a>. We will respond within 30 days.</p>
      <p>If you believe we are processing your data unlawfully, you have the right to lodge a complaint with your local data protection authority.</p>

      <h2>Cookies and tracking</h2>
      <p>PepBan uses a single session cookie to manage logged-in state for member store portals and admin sessions. We do not use advertising cookies, analytics cookies, or any third-party tracking scripts.</p>

      <h2>Data security</h2>
      <p>API keys are hashed with bcrypt before storage. Admin passwords are hashed with bcrypt. All connections to pepban.com are encrypted via HTTPS. We apply rate limiting to all public-facing forms to prevent abuse.</p>

      <h2>Changes to this policy</h2>
      <p>If we make material changes to this policy, we will update the date at the top of this page. Continued use of the service after a change constitutes acceptance of the updated policy.</p>

      <h2>Contact</h2>
      <p>Questions about this policy or our data practices: <a href="mailto:<?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?>"><?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?></a></p>

    </div>

  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
