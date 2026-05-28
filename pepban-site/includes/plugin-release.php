<?php
defined('PEPBAN_VERSION') || die;

/**
 * Single source of truth for plugin release data.
 * To ship a new version:
 *   1. Bump PEPBAN_PLUGIN_VERSION in config.php
 *   2. Add a new entry at the TOP of pepban_plugin_changelog() below
 * Everything else (API endpoint, website changelog page) is auto-generated.
 */

function pepban_plugin_description(): string {
	return '<p><strong>PepBan</strong> connects your WooCommerce store to the centralized PepBan ban database — shared across all member peptide stores.</p>'
		. '<h4>Features</h4><ul>'
		. '<li>Automatically blocks banned customers at checkout by email, phone, IP, or billing address</li>'
		. '<li>One-click reporting from the WooCommerce order screen</li>'
		. '<li>Per-site blacklist and whitelist — block or allow customers on your store only</li>'
		. '<li>Bulk CSV import — add multiple bans to your local blacklist at once</li>'
		. '<li>Global IP and domain blocking — hub-level bans checked at every checkout</li>'
		. '<li>Order admin notice shows how many stores have reported the customer</li>'
		. '<li>Automatic updates delivered directly from pepban.com</li>'
		. '</ul>';
}

function pepban_plugin_installation(): string {
	return '<ol>'
		. '<li>Download the plugin ZIP from your <a href="' . rtrim(SITE_URL, '/') . '/portal">PepBan portal</a>.</li>'
		. '<li>In WordPress go to <strong>Plugins → Add New → Upload Plugin</strong> and upload the ZIP.</li>'
		. '<li>Activate the plugin.</li>'
		. '<li>Go to <strong>PepBan → Settings</strong> and enter your API key.</li>'
		. '</ol>';
}

/**
 * Returns changelog as a structured array — newest first.
 * Each entry: ['version' => '1.x.x', 'date' => 'Month YYYY', 'latest' => true/false, 'items' => [['tag'=>'Fix|New|Improved','text'=>'...'],...]]
 */
function pepban_plugin_changelog(): array {
	return [
		[
			'version' => '1.4.5',
			'date'    => 'May 2025',
			'latest'  => true,
			'items'   => [
				['tag' => 'Fix', 'text' => 'Admin sidebar icon switched to native WordPress shield dashicon'],
			],
		],
		[
			'version' => '1.4.1',
			'date'    => 'May 2025',
			'latest'  => false,
			'items'   => [
				['tag' => 'Fix', 'text' => 'Admin sidebar icon: PB text now correctly centered within the shield'],
			],
		],
		[
			'version' => '1.4.0',
			'date'    => 'May 2025',
			'latest'  => false,
			'items'   => [
				['tag' => 'New',      'text' => 'Ban appeal URL — add a contact/dispute link that is automatically appended to the block message shown to banned customers, reducing chargebacks'],
				['tag' => 'New',      'text' => 'Auto-report on block — optional setting that automatically reports a blocked customer back to the PepBan network, closing the feedback loop across all member stores'],
				['tag' => 'New',      'text' => 'Ban expiry review queue — bans older than 6 months are automatically flagged for review in the hub admin; flagged bans can be dismissed after manual inspection'],
			],
		],
		[
			'version' => '1.3.0',
			'date'    => 'May 2025',
			'latest'  => false,
			'items'   => [
				['tag' => 'New', 'text' => 'HMAC-signed API requests — every request is cryptographically bound to the API key, endpoint, body, and a 5-minute timestamp window; replayed or stolen keys are rejected'],
				['tag' => 'New', 'text' => 'Domain verification — hub logs requests whose origin does not match the registered store URL'],
				['tag' => 'Improved', 'text' => 'Plugin license updated to proprietary'],
			],
		],
		[
			'version' => '1.2.2',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'Fix',      'text' => 'Banned customer orders now marked <em>failed</em> instead of trashed — prevents conflicts with Stripe, PayPal, and other payment gateways'],
				['tag' => 'Fix',      'text' => 'API checked only once per checkout regardless of which hooks fire — eliminates duplicate lookups'],
				['tag' => 'Fix',      'text' => 'Saving settings no longer wipes fields added by future plugin versions'],
				['tag' => 'Fix',      'text' => 'Removed unregistered AJAX handler that could cause a fatal error on some setups'],
			],
		],
		[
			'version' => '1.2.1',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'Fix', 'text' => '"Check by Phone" label corrected in plugin settings'],
			],
		],
		[
			'version' => '1.2.0',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'Bulk CSV import — add multiple banned customers to the hub or local blacklist via CSV upload'],
				['tag' => 'New',      'text' => 'Order admin notice now shows total reports and number of stores that have reported the customer'],
				['tag' => 'Improved', 'text' => 'Check IP Address and Check Billing Address are now separate toggles in plugin settings'],
			],
		],
		[
			'version' => '1.1.4',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'Improved', 'text' => 'Billing address split into Street, City, State, and ZIP fields for precise blocking and whitelisting'],
				['tag' => 'New',      'text' => 'Local whitelist supports Email, IP Address, and Billing Address — whitelisted customers bypass all blocking on your site'],
			],
		],
		[
			'version' => '1.1.3',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'Local blacklist now supports Email, IP Address, and Billing Address blocking — not just email'],
				['tag' => 'New',      'text' => 'Global IP blocking — IPs banned at the hub level are now also checked at checkout via the API'],
				['tag' => 'Improved', 'text' => 'Billing address blocking uses partial/substring match — block by city, zip code, or full address'],
				['tag' => 'Improved', 'text' => 'All four checkout hooks now check IP and address against the local blacklist'],
			],
		],
		[
			'version' => '1.1.2',
			'date'    => 'May 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'Per-site customer blacklist — block specific customers on your store only'],
				['tag' => 'New',      'text' => '"Report to PepBan" button on blacklist — escalate local bans to the global network in one click'],
				['tag' => 'New',      'text' => 'Per-site domain blacklist — block entire email domains (e.g. block all @tempmail.com orders)'],
				['tag' => 'Improved', 'text' => 'FunnelKit and custom checkout builder compatibility via <code>woocommerce_after_checkout_validation</code> hook'],
				['tag' => 'Improved', 'text' => 'Universal safety net — all checkout types now covered including block-based and headless'],
			],
		],
		[
			'version' => '1.1.0',
			'date'    => 'April 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'WordPress auto-update support — updates delivered directly through the WP plugins dashboard'],
				['tag' => 'New',      'text' => 'Hub URL is now embedded — no configuration needed on install'],
				['tag' => 'Fix',      'text' => 'Connection status incorrectly showing error even when API was responding correctly'],
				['tag' => 'Improved', 'text' => 'Plugin details popup now shows Description, Installation, and Changelog tabs'],
				['tag' => 'Improved', 'text' => '"Check for Updates" button added to settings page for immediate update checks'],
			],
		],
		[
			'version' => '1.0.9',
			'date'    => 'March 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'Block-based checkout (WooCommerce Blocks / Gutenberg) support'],
				['tag' => 'New',      'text' => 'Order-level safety net — bans checked at order creation as a final fallback for all checkout types'],
				['tag' => 'Improved', 'text' => 'Admin ban notice shown on order edit screen for already-placed orders'],
			],
		],
		[
			'version' => '1.0.5',
			'date'    => 'February 2025',
			'items'   => [
				['tag' => 'New',      'text' => 'IP blocking — ban specific IP addresses site-wide from the PepBan hub'],
				['tag' => 'Improved', 'text' => 'API error handling — configurable fail-open / fail-closed on API timeout'],
				['tag' => 'Fix',      'text' => 'Checkout process hook priority adjusted to prevent conflicts with other plugins'],
			],
		],
		[
			'version' => '1.0.0',
			'date'    => 'January 2025',
			'items'   => [
				['tag' => 'New', 'text' => 'Initial release'],
				['tag' => 'New', 'text' => 'Real-time checkout blocking via PepBan API'],
				['tag' => 'New', 'text' => 'One-click report from WooCommerce order screen'],
				['tag' => 'New', 'text' => 'Per-site customer whitelisting'],
				['tag' => 'New', 'text' => 'Settings page with API key and block message configuration'],
			],
		],
	];
}

function pepban_plugin_changelog_html(): string {
	$tag_map = ['New' => 'new', 'Fix' => 'fix', 'Improved' => 'improvement'];
	$html = '';
	foreach (pepban_plugin_changelog() as $release) {
		$html .= '<h4>v' . htmlspecialchars($release['version']) . '</h4><ul>';
		foreach ($release['items'] as $item) {
			$tag   = $item['tag'];
			$label = htmlspecialchars($tag) . ': ';
			$html .= '<li>' . $label . $item['text'] . '</li>';
		}
		$html .= '</ul>';
	}
	return $html;
}
