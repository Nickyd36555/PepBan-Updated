<?php
// Derive slug from the URL directly — works whether called from the router variable or the static routes array
$_blog_path = current_path(); // e.g. /blog or /blog/some-slug
$blog_slug  = preg_replace('/[^a-z0-9-]/', '', ltrim(substr($_blog_path, 5), '/'));
$post       = $blog_slug ? pepban_blog_post_by_slug($blog_slug) : null;

if ($blog_slug && !$post) {
	http_response_code(404);
	require __DIR__ . '/404.php';
	return;
}

if ($post) {
	// ── Single post ───────────────────────────────────────────────────────────
	$page_title = $post['title'] . ' — PepBan';
	$page_meta_desc = $post['meta_desc'];
	require __DIR__ . '/../templates/layout.php';
	?>
	<article class="pb-section pb-blog-article">
	  <div class="pb-section-inner" style="max-width:780px">
	    <div class="pb-blog-breadcrumb">
	      <a href="<?= url('/blog') ?>">Blog</a> &rsaquo; <span><?= e($post['title']) ?></span>
	    </div>
	    <h1 class="pb-blog-article-title"><?= e($post['title']) ?></h1>
	    <div class="pb-blog-meta">
	      <span><?= e($post['date']) ?></span>
	      <span class="pb-blog-meta-div">&bull;</span>
	      <span><?= e($post['author']) ?></span>
	    </div>
	    <div class="pb-blog-body">
	      <?= $post['content'] ?>
	    </div>
	    <div class="pb-blog-back">
	      <a href="<?= url('/blog') ?>" class="pb-btn pb-btn-ghost">&larr; Back to Blog</a>
	    </div>
	  </div>
	</article>
	<?php

} else {
	// ── Blog index ────────────────────────────────────────────────────────────
	$page_title = 'Blog — Peptide Store Fraud Protection | PepBan';
	$page_meta_desc = 'Guides and insights for peptide store owners on stopping chargeback fraud, blocking scammers, and protecting WooCommerce stores.';
	require __DIR__ . '/../templates/layout.php';
	$posts = pepban_blog_posts();
	?>
	<section class="pb-section">
	  <div class="pb-section-inner">
	    <div class="pb-feat-header" style="margin-bottom:40px">
	      <h1>Blog</h1>
	      <p>Guides for peptide store owners on fraud prevention, WooCommerce security, and protecting your business.</p>
	    </div>
	    <div class="pb-blog-grid">
	      <?php foreach ($posts as $p): ?>
	      <a href="<?= url('/blog/' . $p['slug']) ?>" class="pb-blog-card">
	        <div class="pb-blog-card-date"><?= e($p['date']) ?></div>
	        <h2 class="pb-blog-card-title"><?= e($p['title']) ?></h2>
	        <p class="pb-blog-card-excerpt"><?= e($p['excerpt']) ?></p>
	        <span class="pb-blog-card-link">Read article &rarr;</span>
	      </a>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php
}

require __DIR__ . '/../templates/layout-end.php';
