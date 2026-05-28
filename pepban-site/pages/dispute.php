<?php
$page_title = 'Dispute a Ban — ' . SITE_NAME;

$sent   = false;
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf();

	if (!check_rate_limit('dispute_' . ($_SERVER['REMOTE_ADDR'] ?? ''))) {
		$errors[] = 'Too many requests. Please wait a minute and try again.';
	} else {
		$email      = strtolower(trim(post('email')));
		$name       = post('name');
		$store_hint = post('store_hint');
		$reason     = post('reason');
		$old        = compact('email', 'name', 'store_hint', 'reason');

		if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
		if (!$name)                                                  $errors[] = 'Your name is required.';
		if (strlen($reason) < 20)                                    $errors[] = 'Please provide more detail (at least 20 characters).';

		if (empty($errors)) {
			Database::get()->insert('pepban_disputes', [
				'email'      => $email,
				'name'       => $name,
				'store_hint' => $store_hint,
				'reason'     => $reason,
				'status'     => 'open',
				'date_added' => date('Y-m-d H:i:s'),
			]);
			Mailer::adminNewDispute($email, $name, $reason);
			$sent = true;
		}
	}
}

require __DIR__ . '/../templates/layout.php';
?>

<div class="pb-contact-wrap">
  <div class="pb-contact-inner">

    <div class="pb-contact-info">
      <h1>Dispute a Ban</h1>
      <p>If you were blocked at checkout on a store that uses PepBan and believe it was a mistake, submit your dispute below. We review every submission and will contact you at your email address.</p>

      <div class="pb-contact-detail">
        <span class="pb-contact-label">Review time</span>
        <span>3–5 business days</span>
      </div>
      <div class="pb-contact-detail">
        <span class="pb-contact-label">What to include</span>
        <span>Your email, which store blocked you, and why you believe it's incorrect</span>
      </div>
      <div class="pb-contact-detail">
        <span class="pb-contact-label">Email</span>
        <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>
      </div>
    </div>

    <div class="pb-contact-form-wrap">
      <?php if ($sent): ?>
        <div class="pb-contact-success">
          <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          <div>
            <strong>Dispute submitted!</strong>
            <p>We'll review your case and get back to you within 3–5 business days.</p>
          </div>
        </div>
      <?php else: ?>
        <?php if ($errors): ?>
          <div class="pb-contact-errors">
            <?php foreach ($errors as $err): ?>
              <p><?= e($err) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="pb-contact-form">
          <?= csrf_field() ?>
          <div class="pb-cf-row pb-cf-row-2">
            <div class="pb-cf-field">
              <label for="name">Your Name</label>
              <input type="text" id="name" name="name" required
                     value="<?= e($old['name'] ?? '') ?>" placeholder="Jane Smith">
            </div>
            <div class="pb-cf-field">
              <label for="email">Email Address <small>(the one being blocked)</small></label>
              <input type="email" id="email" name="email" required
                     value="<?= e($old['email'] ?? '') ?>" placeholder="jane@example.com">
            </div>
          </div>
          <div class="pb-cf-field">
            <label for="store_hint">Which store blocked you? <span class="pb-cf-optional">(optional)</span></label>
            <input type="text" id="store_hint" name="store_hint"
                   value="<?= e($old['store_hint'] ?? '') ?>" placeholder="e.g. example-store.com">
          </div>
          <div class="pb-cf-field">
            <label for="reason">Why do you believe this is a mistake?</label>
            <textarea id="reason" name="reason" rows="6" required
                      placeholder="Please describe the situation in as much detail as possible…"><?= e($old['reason'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="pb-btn pb-btn-primary pb-btn-lg">Submit Dispute</button>
        </form>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
