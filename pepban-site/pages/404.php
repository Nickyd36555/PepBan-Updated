<?php
$page_title = 'Page Not Found — PepBan';
require __DIR__ . '/../templates/layout.php';
?>

<section class="pb-section" style="min-height:60vh;display:flex;align-items:center">
  <div class="pb-section-inner" style="text-align:center">
    <p style="font-size:5rem;font-weight:800;color:var(--red);letter-spacing:-.04em;line-height:1;margin-bottom:16px">404</p>
    <h1 style="font-size:1.8rem;font-weight:700;color:var(--text);margin-bottom:12px">Page not found</h1>
    <p style="color:var(--text-muted);font-size:1rem;max-width:400px;margin:0 auto 32px">The page you're looking for doesn't exist or has been moved.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="<?= url('/') ?>" class="pb-btn pb-btn-primary">Go home</a>
      <a href="<?= url('/contact') ?>" class="pb-btn pb-btn-ghost">Contact support</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
