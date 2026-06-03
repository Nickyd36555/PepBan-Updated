<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? SITE_NAME) ?></title>
<link rel="icon" type="image/png" sizes="64x64" href="<?= url('assets/images/favicon.png') ?>">
<link rel="icon" type="image/svg+xml" href="<?= url('assets/images/icon.svg') ?>">
<link rel="shortcut icon" href="<?= url('assets/images/favicon.png') ?>"><?php
// If a PNG logo exists use it as the apple-touch-icon (higher quality on iOS)
foreach (['logo.png','logo.jpg','logo.webp'] as $_fi) {
  if (file_exists(__DIR__ . '/../assets/images/' . $_fi)) {
    echo "\n<link rel=\"apple-touch-icon\" href=\"" . url('assets/images/' . $_fi) . '">';
    break;
  }
} ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap">
<link rel="stylesheet" href="<?= url('assets/css/public.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/public.css') ?>">
</head>
<body>

<nav class="pb-nav">
  <div class="pb-nav-inner">
    <button class="pb-nav-burger" id="pb-nav-burger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
    <a href="<?= url('/') ?>" class="pb-nav-brand">
      <?php
      $nav_logo = null;
      foreach (['logo.png','logo.jpg','logo.webp','logo.svg'] as $_nl) {
        if (file_exists(__DIR__ . '/../assets/images/' . $_nl)) { $nav_logo = $_nl; break; }
      }
      ?>
      <?php if ($nav_logo): ?>
        <img src="<?= url('assets/images/' . $nav_logo) ?>" alt="PepBan" class="pb-nav-logo">
      <?php else: ?>
        <span class="pb-nav-wordmark">Pep<span>Ban</span></span>
      <?php endif; ?>
    </a>
    <div class="pb-nav-links" id="pb-nav-links">
      <a href="<?= url('/') ?>" class="pb-nav-link">Home</a>
      <a href="<?= url('/#features') ?>" class="pb-nav-link">Features</a>
      <a href="<?= url('/faq') ?>" class="pb-nav-link">FAQ</a>
      <a href="<?= url('/contact') ?>" class="pb-nav-link">Contact</a>
      <a href="<?= url('/dispute') ?>" class="pb-nav-link">Dispute a Ban</a>
      <a href="<?= url('/plugin') ?>" class="pb-nav-link">Plugin</a>
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
