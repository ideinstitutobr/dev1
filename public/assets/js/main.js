// Placeholder main.js – suporte a toggles simples
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-toggle]');
  if (!btn) return;
  const targetId = btn.getAttribute('data-target');
  const el = document.getElementById(targetId);
  if (el) {
    el.classList.toggle('show');
  }
});
