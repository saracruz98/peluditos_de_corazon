<?php
// public/src/pwa_scripts.php
// Incluir al final de cada página, antes de </body>
// Registra el service worker y muestra el banner de instalación
?>
<script>
// ── Registrar Service Worker ───────────────────────────────────────
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js')
    .then(r => console.log('SW registrado:', r.scope))
    .catch(e => console.warn('SW error:', e));
}

// ── Banner de instalación PWA ──────────────────────────────────────
let deferredPrompt = null;
const banner = document.getElementById('install-banner');
const btnInstall = document.getElementById('btn-install-pwa');
const btnDismiss = document.getElementById('btn-dismiss-pwa');

window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;
  if (banner) banner.style.display = 'flex';
});

if (btnInstall) {
  btnInstall.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    if (banner) banner.style.display = 'none';
  });
}
if (btnDismiss) {
  btnDismiss.addEventListener('click', () => {
    if (banner) banner.style.display = 'none';
    localStorage.setItem('pwa-dismissed', '1');
  });
}

// No mostrar el banner si ya fue descartado
if (localStorage.getItem('pwa-dismissed') === '1' && banner) {
  banner.style.display = 'none';
}

window.addEventListener('appinstalled', () => {
  if (banner) banner.style.display = 'none';
  deferredPrompt = null;
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
