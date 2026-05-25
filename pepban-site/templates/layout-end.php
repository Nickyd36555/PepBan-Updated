</main>

<footer class="pb-footer">
  <div class="pb-footer-inner">
    <p>&copy; <?= date('Y') ?> PepBan. All rights reserved.</p>
    <div class="pb-footer-links">
      <a href="<?= url('/faq') ?>">FAQ</a>
      <a href="<?= url('/login') ?>">Login</a>
      <a href="<?= url('/signup') ?>">Sign Up</a>
    </div>
  </div>
</footer>

<script>
document.querySelectorAll('.pb-faq-question').forEach(function(btn) {
  btn.addEventListener('click', function() {
    btn.closest('.pb-faq-item').classList.toggle('open');
  });
});
</script>

</body>
</html>
