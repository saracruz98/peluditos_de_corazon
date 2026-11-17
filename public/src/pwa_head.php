<?php
// public/src/pwa_head.php
// Incluir en todas las páginas DENTRO del <head>
// Uso: require_once __DIR__ . '/pwa_head.php';
?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#198754">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Peluditos">
<meta name="mobile-web-app-capable" content="yes">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  /* ══ Estilos globales PWA ══ */
  body {
    background: #f0f4f1;
    padding-bottom: 80px; /* espacio para bottom-nav mobile */
    padding-top: env(safe-area-inset-top);
    padding-left: env(safe-area-inset-left);
    padding-right: env(safe-area-inset-right);
    font-size: 15px;
  }
  /* Bottom navigation bar (solo mobile) */
  .bottom-nav {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 1050;
    background: #198754;
    display: flex;
    border-top: 2px solid #145c38;
    padding-bottom: env(safe-area-inset-bottom);
    box-shadow: 0 -2px 12px rgba(0,0,0,0.18);
  }
  .bottom-nav a {
    flex: 1; text-align: center; color: #fff; text-decoration: none;
    padding: 10px 2px 7px; font-size: 10px; line-height: 1.3;
    transition: background 0.15s;
  }
  .bottom-nav a .nav-icon { font-size: 24px; display: block; margin-bottom: 2px; }
  .bottom-nav a.active  { background: #0d5c35; }
  .bottom-nav a:active  { background: #0a4228; }
  @media (min-width: 768px) {
    .bottom-nav { display: none !important; }
    body { padding-bottom: 0; }
  }
  /* Cards más cómodas en mobile */
  .card { border-radius: 14px; }
  .card-header { border-radius: 14px 14px 0 0 !important; font-size: 1rem; }
  .form-label { font-weight: 600; margin-bottom: 4px; }
  .form-control, .form-select { border-radius: 10px; font-size: 16px; } /* 16px evita zoom en iOS */
  .btn-lg { border-radius: 10px; font-size: 1rem; }
  .table th, .table td { vertical-align: middle; white-space: nowrap; }
  .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 10px; }
  /* Navbar desktop */
  .navbar-brand { font-size: 1.1rem; }
  /* Splash / header mobile */
  .mobile-header {
    background: linear-gradient(135deg, #198754, #0d5c35);
    color: #fff; text-align: center; padding: 12px;
    font-weight: bold; font-size: 1.1rem;
    letter-spacing: 0.5px;
  }
  /* Banner install prompt */
  #install-banner {
    position: fixed; bottom: 70px; left: 10px; right: 10px; z-index: 2000;
    background: #fff; border: 2px solid #198754; border-radius: 14px;
    padding: 14px 16px; display: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  }
</style>
