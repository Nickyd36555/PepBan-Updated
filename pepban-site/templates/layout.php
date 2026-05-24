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
		<img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pepban-nav-logo">
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
