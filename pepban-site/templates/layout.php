<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/public.css') ?>?v=<?= PEPBAN_VERSION ?>">
</head>
<body>

<nav class="pb-nav">
  <div class="pb-nav-inner">
    <button class="pb-nav-burger" id="pb-nav-burger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
    <a href="<?= url('/') ?>" class="pb-nav-brand">
      <img src="<?= url('assets/images/logo.png') ?>" alt="PepBan" class="pb-nav-logo">
    </a>
    <div class="pb-nav-links" id="pb-nav-links">
      <a href="<?= url('/') ?>" class="pb-nav-link">Home</a>
      <a href="<?= url('/#features') ?>" class="pb-nav-link">Features</a>
      <a href="<?= url('/faq') ?>" class="pb-nav-link">FAQ</a>
      <a href="<?= url('/changelog') ?>" class="pb-nav-link">Changelog</a>
      <a href="<?= url('/signup') ?>" class="pb-nav-link">Pricing</a>
      <?php if (Auth::isClient()): ?>
        <a href="<?= url('/portal') ?>" class="pb-nav-link pb-mobile-only">My Portal</a>
        <a href="<?= url('/logout') ?>" class="pb-nav-link pb-mobile-only">Log out</a>
      <?php else: ?>
        <a href="<?= url('/login') ?>" class="pb-nav-link pb-mobile-only">Log In</a>
        <a href="<?= url('/signup') ?>" class="pb-nav-link pb-mobile-only">Sign Up</a>
      <?php endif; ?>
    </div>
    <div class="pb-nav-actions">
      <?php if (Auth::isClient()): ?>
        <a href="<?= url('/portal') ?>" class="pb-btn pb-btn-ghost">My Portal</a>
        <a href="<?= url('/logout') ?>" class="pb-btn pb-btn-ghost">Log out</a>
      <?php else: ?>
        <a href="<?= url('/login') ?>" class="pb-btn pb-btn-ghost">Log In</a>
        <a href="<?= url('/signup') ?>" class="pb-btn pb-btn-primary">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main>
<?php foreach (get_flashes() as $f): ?>
<div class="pepban-alert pepban-alert-<?= e($f['type']) ?> pepban-alert-global"><?= e($f['message']) ?></div>
<?php endforeach; ?>
