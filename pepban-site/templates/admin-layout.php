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
		<svg width="24" height="24" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="8" fill="#2563eb"/><path d="M18 6L8 10.5V19.5C8 24.747 12.477 29.223 18 30C23.523 29.223 28 24.747 28 19.5V10.5L18 6Z" fill="white" fill-opacity=".2" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 18L16.5 20.5L22 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<span>PepBan</span>
	</a>
	<nav class="pb-sidebar-nav">
		<?php
		$cur_segment = ltrim(substr(current_path(), 6), '/') ?: 'dashboard';
		$nav_items = [
			'dashboard' => 'Dashboard',
			'banned'    => 'Banned Customers',
			'clients'   => 'Clients',
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
