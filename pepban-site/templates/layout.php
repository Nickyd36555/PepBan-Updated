<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ?? SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/public.css') ?>">
</head>
<body class="pepban-body">

<nav class="pepban-nav">
	<a href="<?= url('/') ?>" class="pepban-nav-brand">
		<svg width="28" height="28" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="10" fill="#2563eb"/><path d="M18 6L8 10.5V19.5C8 24.747 12.477 29.223 18 30C23.523 29.223 28 24.747 28 19.5V10.5L18 6Z" fill="white" fill-opacity=".15" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 18L16.5 20.5L22 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		PepBan
	</a>
	<div class="pepban-nav-links">
		<?php if (Auth::isClient()): ?>
			<a href="<?= url('/portal') ?>" class="pepban-nav-link">My Portal</a>
			<a href="<?= url('/logout') ?>" class="pepban-nav-link">Log out</a>
		<?php else: ?>
			<a href="<?= url('/login') ?>" class="pepban-nav-link">Log in</a>
			<a href="<?= url('/signup') ?>" class="pepban-btn pepban-btn-primary">Sign Up</a>
		<?php endif; ?>
	</div>
</nav>

<main class="pepban-main">
<?php foreach (get_flashes() as $f): ?>
<div class="pepban-alert pepban-alert-<?= e($f['type']) ?> pepban-alert-global">
	<?= e($f['message']) ?>
</div>
<?php endforeach; ?>
