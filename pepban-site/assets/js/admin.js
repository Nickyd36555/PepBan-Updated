/* PepBan Admin JS */
(function () {
  'use strict';

  // Auto-dismiss flash messages after 4 seconds
  document.querySelectorAll('.pb-alert').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4000);
  });

  // Copy-to-clipboard for API base URL code block
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.dataset.copy);
      if (!target) return;
      navigator.clipboard.writeText(target.textContent.trim()).then(function () {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = orig; }, 1500);
      });
    });
  });

  // Confirm danger actions that don't already have onsubmit handlers
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
  });
})();
