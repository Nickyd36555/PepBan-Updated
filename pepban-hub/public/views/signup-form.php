<?php defined( 'ABSPATH' ) || exit; ?>
<div class="pepban-public">
<div class="pepban-signup-layout">

	<!-- Left: Brand panel -->
	<div class="pepban-signup-brand">
		<div class="pepban-brand-logo">
			<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="36" height="36" rx="10" fill="#2563eb"/>
				<path d="M18 6L8 10.5V19.5C8 24.747 12.477 29.223 18 30C23.523 29.223 28 24.747 28 19.5V10.5L18 6Z" fill="white" fill-opacity=".15" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
				<path d="M14 18L16.5 20.5L22 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			<span>PepBan</span>
		</div>

		<p class="pepban-brand-tagline">Protect your store from repeat offenders</p>
		<p class="pepban-brand-sub">Join the network of peptide stores sharing a centralized ban list. One sign-up, instant protection.</p>

		<ul class="pepban-benefit-list">
			<li>Shared database across all member stores</li>
			<li>Automatic checkout blocking</li>
			<li>Report bad customers in one click</li>
			<li>Per-site whitelist control</li>
			<li>Real-time API — zero slowdown</li>
		</ul>
	</div>

	<!-- Right: Form panel -->
	<div class="pepban-signup-form-panel">
		<h2>Create your account</h2>
		<p class="pepban-panel-sub">Get your API key instantly — no approval wait.</p>

		<?php if ( ! empty( $errors ) ) : ?>
		<div class="pepban-alert pepban-alert-error">
			<?php foreach ( $errors as $err ) : ?>
				<p><?php echo wp_kses( $err, array( 'a' => array( 'href' => array() ) ) ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pepban-form" novalidate>
			<?php wp_nonce_field( 'pepban_register' ); ?>
			<input type="hidden" name="action" value="pepban_register">

			<div class="pepban-field">
				<label for="pepban-name">Full Name <span class="pepban-req">*</span></label>
				<input type="text" name="name" id="pepban-name"
					value="<?php echo esc_attr( $old_values['name'] ?? '' ); ?>"
					placeholder="Jane Smith" autocomplete="name" required>
			</div>

			<div class="pepban-field">
				<label for="pepban-email">Email Address <span class="pepban-req">*</span></label>
				<input type="email" name="email" id="pepban-email"
					value="<?php echo esc_attr( $old_values['email'] ?? '' ); ?>"
					placeholder="jane@yourstore.com" autocomplete="email" required>
			</div>

			<div class="pepban-field">
				<label for="pepban-site-url">Your Store URL <span class="pepban-req">*</span></label>
				<input type="url" name="site_url" id="pepban-site-url"
					value="<?php echo esc_attr( $old_values['site_url'] ?? '' ); ?>"
					placeholder="https://your-peptide-store.com" autocomplete="url" required>
				<small>The WooCommerce site you'll install the PepBan Client plugin on.</small>
			</div>

			<div class="pepban-field">
				<label for="pepban-pass">Password <span class="pepban-req">*</span></label>
				<input type="password" name="password" id="pepban-pass"
					minlength="8" autocomplete="new-password" required>
				<small>Minimum 8 characters</small>
			</div>

			<div class="pepban-field">
				<label for="pepban-pass2">Confirm Password <span class="pepban-req">*</span></label>
				<input type="password" name="password_confirm" id="pepban-pass2"
					minlength="8" autocomplete="new-password" required>
			</div>

			<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full" style="margin-top:6px">
				Create Account &amp; Get API Key
			</button>
		</form>

		<p class="pepban-alt-link">Already have an account? <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Sign in</a></p>
	</div>

</div>
</div>
