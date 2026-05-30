<?php
$page_title = 'Terms of Service — PepBan';
require __DIR__ . '/../templates/layout.php';
?>

<section class="pb-section">
  <div class="pb-section-inner" style="max-width:760px">

    <div style="margin-bottom:48px">
      <span class="pb-section-eyebrow">Legal</span>
      <h1 class="pb-section-title" style="text-align:left;margin-bottom:12px">Terms of Service</h1>
      <p style="color:var(--text-muted);font-size:.9rem">Last updated: <?= date('F j, Y') ?></p>
    </div>

    <div class="pb-prose">

      <h2>1. Acceptance of Terms</h2>
      <p>By signing up for, accessing, or using the PepBan service ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, do not use the Service. These Terms apply to all member stores, their authorised representatives, and any visitors to pepban.com.</p>

      <h2>2. Service Description</h2>
      <p>PepBan operates a shared fraud-prevention database for WooCommerce store owners. Member stores may report customers who have committed fraud, filed false chargebacks, or engaged in abusive behaviour. Reported customers are added to a shared ban list that all member stores can query in real time via our API. PepBan also provides a dispute resolution process for customers who believe they were incorrectly flagged.</p>

      <h2>3. Eligibility</h2>
      <p>You must be at least 18 years old and operating a legitimate business to use the Service as a member store. By creating an account, you represent that all registration information you provide is accurate and that you have the authority to bind your business to these Terms.</p>

      <h2>4. Member Store Obligations</h2>
      <p>As a member store, you agree to:</p>
      <ul>
        <li>Only report customers for genuine, documented instances of fraud, chargebacks, or abusive behaviour.</li>
        <li>Provide accurate information when submitting a ban report, including the correct email address, name, and reason.</li>
        <li>Keep your API key confidential. You are responsible for all activity that occurs under your API key.</li>
        <li>Promptly notify PepBan if you believe your API key has been compromised.</li>
        <li>Not use the Service to discriminate against customers on the basis of race, gender, religion, nationality, disability, sexual orientation, age, or any other characteristic protected by applicable law.</li>
        <li>Comply with all applicable laws and regulations in your jurisdiction, including data protection laws, when using the Service.</li>
      </ul>

      <h2>5. Prohibited Uses</h2>
      <p>You may not use the Service to:</p>
      <ul>
        <li>Submit false, misleading, or malicious reports against customers.</li>
        <li>Use reported customer data for any purpose other than fraud prevention at your own store.</li>
        <li>Attempt to reverse-engineer, scrape, or copy the ban list database.</li>
        <li>Share, resell, or sublicense access to the PepBan API to any third party.</li>
        <li>Attempt to circumvent rate limits, authentication, or other security controls.</li>
        <li>Interfere with the operation of the Service or its infrastructure.</li>
      </ul>
      <p>We reserve the right to immediately suspend or terminate accounts found in violation of these prohibitions.</p>

      <h2>6. Customer Data and the Ban List</h2>
      <p>When you submit a ban report, you grant PepBan a non-exclusive licence to store and share that data with other member stores solely for fraud-prevention purposes. You represent that you have a lawful basis for sharing this data under applicable data protection law (e.g. legitimate interest in fraud prevention).</p>
      <p>PepBan does not guarantee the accuracy or completeness of data submitted by member stores. Each member store is solely responsible for the data it submits. PepBan acts as a conduit for this data and is not liable for inaccurate or wrongful reports submitted by member stores.</p>

      <h2>7. Disputes and Appeals</h2>
      <p>Customers who believe they were incorrectly flagged may submit a dispute at pepban.com/dispute. PepBan reviews all disputes and may remove a customer from the ban list if the ban is determined to be in error. Member stores may be contacted during the dispute review process.</p>
      <p>PepBan's decision on disputes is final. We are not liable for any business impact resulting from a customer being banned or a ban being reversed.</p>

      <h2>8. API Usage and Rate Limits</h2>
      <p>API usage is subject to the rate limits set for your account. Exceeding rate limits may result in temporary blocking of your API key. Sustained abuse of rate limits may result in account suspension. PepBan reserves the right to adjust rate limits at any time.</p>

      <h2>9. Service Availability</h2>
      <p>PepBan aims to maintain high availability but does not guarantee uninterrupted access to the Service. We are not liable for any loss resulting from downtime, latency, or service interruptions. You should implement appropriate fallback behaviour in your store if the PepBan API is unreachable (e.g. allow checkout to proceed normally).</p>

      <h2>10. Fees and Billing</h2>
      <p>Pricing is agreed separately with each member store. Failure to pay applicable fees may result in suspension of your account and API key. All fees are non-refundable unless otherwise agreed in writing.</p>

      <h2>11. Intellectual Property</h2>
      <p>The PepBan name, logo, software, and all associated intellectual property are owned by PepBan. Nothing in these Terms grants you any ownership rights in the Service. You may not use our trademarks, logos, or branding without prior written consent.</p>

      <h2>12. Disclaimer of Warranties</h2>
      <p>The Service is provided "as is" and "as available" without warranties of any kind, express or implied. PepBan does not warrant that the Service will be error-free, that the ban list will be accurate, or that use of the Service will prevent all fraud or chargebacks at your store.</p>

      <h2>13. Limitation of Liability</h2>
      <p>To the maximum extent permitted by law, PepBan's total liability to you for any claims arising under or related to these Terms shall not exceed the amount you paid to PepBan in the three months preceding the claim. PepBan is not liable for any indirect, incidental, special, consequential, or punitive damages, including lost profits or loss of business.</p>

      <h2>14. Indemnification</h2>
      <p>You agree to indemnify and hold harmless PepBan and its operators from any claims, damages, or expenses (including legal fees) arising from: (a) your use of the Service; (b) data you submit to PepBan; (c) your violation of these Terms; or (d) your violation of any third-party rights.</p>

      <h2>15. Termination</h2>
      <p>Either party may terminate the Service relationship at any time. You may close your account by contacting us. PepBan may suspend or terminate your account immediately for violation of these Terms or for any reason with reasonable notice. Upon termination, your API key is revoked and you must remove the PepBan plugin from your store. Ban records you submitted to the shared database remain in place to protect other member stores.</p>

      <h2>16. Changes to These Terms</h2>
      <p>We may update these Terms from time to time. Material changes will be communicated via email to your registered address. Continued use of the Service after changes take effect constitutes acceptance of the updated Terms.</p>

      <h2>17. Governing Law</h2>
      <p>These Terms are governed by applicable law. Any disputes arising from these Terms shall be resolved in the courts of the jurisdiction in which PepBan operates. Both parties agree to attempt resolution informally before initiating formal proceedings.</p>

      <h2>18. Contact</h2>
      <p>Questions about these Terms: <a href="mailto:<?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?>"><?= e(defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : ADMIN_EMAIL) ?></a></p>

    </div>

  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
