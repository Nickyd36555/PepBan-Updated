<?php
$page_title = 'Changelog — PepBan';
require __DIR__ . '/../templates/layout.php';
$tag_css = ['New' => 'new', 'Fix' => 'fix', 'Improved' => 'improvement'];
?>

<div class="pb-changelog">
  <div class="pb-changelog-header">
    <h1>Changelog</h1>
    <p>Every update to the PepBan plugin, in one place.</p>
  </div>

  <?php foreach (pepban_plugin_changelog() as $release): ?>
  <div class="pb-cl-entry">
    <div class="pb-cl-dot"></div>
    <div class="pb-cl-version">
      <h2>v<?= e($release['version']) ?></h2>
      <?php if (!empty($release['latest'])): ?><span class="pb-cl-latest">Latest</span><?php endif; ?>
      <span class="pb-cl-date"><?= e($release['date']) ?></span>
    </div>
    <ul class="pb-cl-items">
      <?php foreach ($release['items'] as $item): ?>
      <li class="pb-cl-item">
        <span class="pb-cl-tag pb-cl-tag-<?= e($tag_css[$item['tag']] ?? 'new') ?>"><?= e($item['tag']) ?></span>
        <?= $item['text'] ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>

</div>

<?php require __DIR__ . '/../templates/layout-end.php'; ?>
