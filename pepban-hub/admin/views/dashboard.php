<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap pepban-wrap">
	<h1>&#128737; PepBan Hub</h1>

	<div class="pepban-stats-grid">
		<div class="pepban-stat-card pepban-stat-banned">
			<span class="pepban-stat-number"><?php echo esc_html( $stats['total_banned'] ); ?></span>
			<span class="pepban-stat-label">Active Bans</span>
		</div>
		<div class="pepban-stat-card pepban-stat-clients">
			<span class="pepban-stat-number"><?php echo esc_html( $stats['total_clients'] ); ?></span>
			<span class="pepban-stat-label">Active Clients</span>
		</div>
		<div class="pepban-stat-card pepban-stat-reports">
			<span class="pepban-stat-number"><?php echo esc_html( $stats['total_reports'] ); ?></span>
			<span class="pepban-stat-label">Total Reports</span>
		</div>
		<div class="pepban-stat-card pepban-stat-recent">
			<span class="pepban-stat-number"><?php echo esc_html( $stats['recent_banned'] ); ?></span>
			<span class="pepban-stat-label">New Bans (30d)</span>
		</div>
	</div>

	<div class="pepban-quick-actions">
		<h2>Quick Actions</h2>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned' ) ); ?>" class="button button-primary">View Banned Customers</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-banned&action=add' ) ); ?>" class="button">+ Add Ban Manually</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-clients' ) ); ?>" class="button">Manage Clients</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pepban-hub-settings' ) ); ?>" class="button">Settings</a>
	</div>

	<div class="pepban-api-info">
		<span>REST API Endpoint</span>
		<code><?php echo esc_html( rest_url( 'pepban/v1' ) ); ?></code>
	</div>
</div>
