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

(function() {
  var burger = document.getElementById('pb-nav-burger');
  var links  = document.getElementById('pb-nav-links');
  if (!burger) return;
  burger.addEventListener('click', function() {
    burger.classList.toggle('open');
    links.classList.toggle('open');
  });
  document.addEventListener('click', function(e) {
    if (!burger.contains(e.target) && !links.contains(e.target)) {
      burger.classList.remove('open');
      links.classList.remove('open');
    }
  });
})();
</script>

</body>
</html>
