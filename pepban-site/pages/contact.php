<?php
$page_title = 'Contact — PepBan';

$sent    = false;
$errors  = [];

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$name    = trim( $_POST['contact_name']    ?? '' );
	$email   = trim( $_POST['contact_email']   ?? '' );
	$subject = trim( $_POST['contact_subject'] ?? '' );
	$message = trim( $_POST['contact_message'] ?? '' );

	if ( empty( $name ) )    $errors[] = 'Name is required.';
	if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) $errors[] = 'A valid email address is required.';
	if ( empty( $message ) ) $errors[] = 'Message is required.';

	if ( empty( $errors ) ) {
		$subject_line = $subject ? "[PepBan Contact] {$subject}" : "[PepBan Contact] Message from {$name}";
		$body =
			"Name:    {$name}\n" .
			"Email:   {$email}\n" .
			"Subject: {$subject}\n\n" .
			"Message:\n{$message}\n\n" .
			"---\nSent from pepban.com/contact";

		$ok = Mailer::send( ADMIN_EMAIL, $subject_line, $body );
		if ( $ok ) {
			$sent = true;
		} else {
			$errors[] = 'Could not send message. Please email us directly at ' . SUPPORT_EMAIL . '.';
		}
	}
}

require __DIR__ . '/../templates/layout.php';
?>

<div class="pb-contact-wrap">
  <div class="pb-contact-inner">

    <div class="pb-contact-info">
      <h1>Get in Touch</h1>
      <p>Have a question about PepBan, need help with your account, or want to report an issue? Send us a message and we'll get back to you within one business day.</p>

      <div class="pb-contact-detail">
        <span class="pb-contact-label">Email</span>
        <a href="mailto:<?= SUPPORT_EMAIL ?>"><?= SUPPORT_EMAIL ?></a>
      </div>
      <div class="pb-contact-detail">
        <span class="pb-contact-label">Response time</span>
        <span>Within 1 business day</span>
      </div>
      <div class="pb-contact-detail">
        <span class="pb-contact-label">Plugin support</span>
        <span>Include your site URL and plugin version</span>
      </div>
    </div>

    <div class="pb-contact-form-wrap">
      <?php if ( $sent ) : ?>
        <div class="pb-contact-success">
          <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          <div>
            <strong>Message sent!</strong>
            <p>We'll get back to you at <strong><?= htmlspecialchars( $email ) ?></strong> within one business day.</p>
          </div>
        </div>
      <?php else : ?>
        <?php if ( $errors ) : ?>
          <div class="pb-contact-errors">
            <?php foreach ( $errors as $err ) : ?>
              <p><?= htmlspecialchars( $err ) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="pb-contact-form">
          <div class="pb-cf-row pb-cf-row-2">
            <div class="pb-cf-field">
              <label for="contact_name">Your Name</label>
              <input type="text" id="contact_name" name="contact_name" required
                     value="<?= htmlspecialchars( $_POST['contact_name'] ?? '' ) ?>"
                     placeholder="Jane Smith">
            </div>
            <div class="pb-cf-field">
              <label for="contact_email">Email Address</label>
              <input type="email" id="contact_email" name="contact_email" required
                     value="<?= htmlspecialchars( $_POST['contact_email'] ?? '' ) ?>"
                     placeholder="jane@yourstore.com">
            </div>
          </div>
          <div class="pb-cf-field">
            <label for="contact_subject">Subject <span class="pb-cf-optional">(optional)</span></label>
            <input type="text" id="contact_subject" name="contact_subject"
                   value="<?= htmlspecialchars( $_POST['contact_subject'] ?? '' ) ?>"
                   placeholder="e.g. Plugin not blocking at checkout">
          </div>
          <div class="pb-cf-field">
            <label for="contact_message">Message</label>
            <textarea id="contact_message" name="contact_message" rows="6" required
                      placeholder="Describe your question or issue in detail…"><?= htmlspecialchars( $_POST['contact_message'] ?? '' ) ?></textarea>
          </div>
          <button type="submit" class="pb-btn pb-btn-primary pb-btn-lg">Send Message</button>
        </form>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
