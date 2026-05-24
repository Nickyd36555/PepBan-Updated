<?php defined( 'ABSPATH' ) || exit; ?>
<div class="pepban-signup-wrap">
	<h2>Sign Up for PepBan</h2>
	<p class="pepban-signup-intro">Create your account to get an API key and protect your peptide store from banned customers.</p>

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
			<label for="pepban-name">Your Name <span class="pepban-req">*</span></label>
			<input type="text" name="name" id="pepban-name"
				value="<?php echo esc_attr( $old_values['name'] ?? '' ); ?>"
				placeholder="Jane Smith" autocomplete="name" required>
		</div>

		<div class="pepban-field">
			<label for="pepban-email">Email Address <span class="pepban-req">*</span></label>
			<input type="email" name="email" id="pepban-email"
				value="<?php echo esc_attr( $old_values['email'] ?? '' ); ?>"
				placeholder="jane@yoursite.com" autocomplete="email" required>
		</div>

		<div class="pepban-field">
			<label for="pepban-site-url">Your Peptide Store URL <span class="pepban-req">*</span></label>
			<input type="url" name="site_url" id="pepban-site-url"
				value="<?php echo esc_attr( $old_values['site_url'] ?? '' ); ?>"
				placeholder="https://your-peptide-store.com" autocomplete="url" required>
			<small>The WooCommerce site where you'll install the PepBan Client plugin.</small>
		</div>

		<div class="pepban-field">
			<label for="pepban-pass">Password <span class="pepban-req">*</span></label>
			<input type="password" name="password" id="pepban-pass"
				minlength="8" autocomplete="new-password" required>
			<small>Minimum 8 characters.</small>
		</div>

		<div class="pepban-field">
			<label for="pepban-pass2">Confirm Password <span class="pepban-req">*</span></label>
			<input type="password" name="password_confirm" id="pepban-pass2"
				minlength="8" autocomplete="new-password" required>
		</div>

		<button type="submit" class="pepban-btn pepban-btn-primary pepban-btn-full">
			Create Account &amp; Get API Key
		</button>
	</form>

	<p class="pepban-alt-link">Already have an account?
		<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in here</a>
	</p>
</div>
