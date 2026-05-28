<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? 'Admin') ?> — <?= e(defined('SITE_NAME') ? SITE_NAME : 'PepBan') ?></title>
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="pb-admin-body">

<aside class="pb-sidebar">
	<a href="<?= url('/admin/dashboard') ?>" class="pb-sidebar-brand">
		<img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pb-sidebar-logo">
	</a>
	<nav class="pb-sidebar-nav">
		<?php
		$cur_segment = ltrim(substr(current_path(), 6), '/') ?: 'dashboard';
		$nav_items = [
			'dashboard' => 'Dashboard',
			'banned'    => 'Banned Customers',
			'clients'   => 'Clients',
			'import'    => 'Bulk Import',
			'feedback'  => 'Feedback',
			'audit'     => 'Audit Log',
			'security'  => 'Security',
			'settings'  => 'Settings',
		];
		foreach ($nav_items as $slug => $label):
			$is_active = ($cur_segment === $slug || str_starts_with($cur_segment, $slug . '/'));
		?>
		<a href="<?= url('/admin/' . $slug) ?>" <?= $is_active ? 'class="pb-active"' : '' ?>>
			<?= e($label) ?>
		</a>
		<?php endforeach; ?>
	</nav>
	<div class="pb-sidebar-footer">
		<a href="<?= url('/admin/logout') ?>">Log out</a>
	</div>
</aside>

<div class="pb-main">
	<header class="pb-topbar">
		<h1><?= e($page_title ?? 'Dashboard') ?></h1>
	</header>
	<div class="pb-content">
<?php foreach (get_admin_flashes() as $_af): ?>
<div class="pb-alert pb-alert-<?= e($_af['type']) ?>"><?= e($_af['message']) ?></div>
<?php endforeach; ?>
