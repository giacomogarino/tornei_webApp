<?php
/**
 * HEADER.PHP — Template header comune
 * =====================================
 * Variabili opzionali da settare PRIMA di includere questo file:
 *   $page_title       — titolo specifico della pagina (stringa)
 *   $page_description — meta description (stringa)
 *   $extra_css        — array di path CSS aggiuntivi
 */

require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();

$current = basename($_SERVER['PHP_SELF']);

function nav_active(string $page, string $current): string {
    return $current === $page
        ? 'm-navbar__link m-navbar__link--active'
        : 'm-navbar__link';
}

// Titolo pagina con fallback
$_page_title = !empty($page_title)
    ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' — Matchora Tornei'
    : 'Matchora Tornei — Organizza e segui i tuoi tornei';

// Meta description con fallback
$_page_desc = !empty($page_description)
    ? htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8')
    : 'Matchora è la piattaforma per organizzare e seguire tornei sportivi amatoriali in modo semplice e professionale.';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_page_title ?></title>
    <meta name="description" content="<?= $_page_desc ?>">
    <meta name="robots" content="index, follow">

    <!-- Favicon & PWA -->
    <link rel="icon" type="image/png" href="/assets/matchora_icon.png">
    <link rel="apple-touch-icon" href="/assets/matchora_icon.png">
    <meta name="theme-color" content="#5b4cdb">
    <link rel="manifest" href="/manifest.json">

    <!-- Open Graph (condivisione social / WhatsApp) -->
    <meta property="og:type"         content="website">
    <meta property="og:site_name"    content="Matchora Tornei">
    <meta property="og:title"        content="<?= $_page_title ?>">
    <meta property="og:description"  content="<?= $_page_desc ?>">
    <meta property="og:image"        content="<?= isset($og_image) ? htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') : 'https://matchoratorneo.netsons.org/assets/matchora_icon.png' ?>">
    <meta property="og:url"          content="https://matchoratorneo.netsons.org<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= $_page_title ?>">
    <meta name="twitter:description" content="<?= $_page_desc ?>">

    <!--
        FONT — serviti dal nostro server (nessuna connessione a Google dal browser).
        Conforme GDPR/ePrivacy: l'IP dell'utente non viene mai inviato a Google.
        Ref: sentenza LG München 2022, provvedimento Garante Privacy IT.
    -->
    <link rel="stylesheet" href="/assets/fonts/load_fonts.php">

    <!-- CSS base -->
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/navbar.css">
    <link rel="stylesheet" href="/css/footer.css">

    <?php if (!empty($extra_css)): foreach ($extra_css as $css_file): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css_file, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; endif; ?>
</head>
<body>

<nav class="m-navbar">
    <div class="m-navbar__inner">
        <a href="/index.php" class="m-navbar__brand">
            <span class="m-navbar__brand-mark"><img src="/assets/matchora_icon.png" alt="Logo Matchora"></span>
            <span class="m-navbar__brand-name">MATCHORA<span class="m-navbar__brand-sub">Tornei</span></span>
        </a>

        <ul class="m-navbar__links" id="navbar">
            <li><a href="/index.php"         class="<?= nav_active('index.php',         $current) ?>">Home</a></li>
            <li><a href="/profilo.php"        class="<?= nav_active('profilo.php',        $current) ?>">Profilo</a></li>
            <li><a href="/tornei_seguiti.php" class="<?= nav_active('tornei_seguiti.php', $current) ?>">Seguiti</a></li>
            <li><a href="/privati.php"        class="<?= nav_active('privati.php',        $current) ?>">Privati</a></li>
            <li><a href="/tornei_creati.php"  class="<?= nav_active('tornei_creati.php',  $current) ?>">Tornei creati</a></li>
            <?php if (($_SESSION['role_utente'] ?? '') === 'admin'): ?>
                <li><a href="/admin/index.php" class="<?= nav_active('index.php', $current) ?> m-navbar__link--admin">🔧 Admin</a></li>
            <?php endif; ?>
        </ul>

        <div class="m-navbar__spacer"></div>

        <div class="m-navbar__actions">
            <?php if (isset($_SESSION['id_utente'])): ?>
                <span class="m-navbar__user">
                    <span class="m-avatar"><?php
                        $nome = $_SESSION['nome_utente'] ?? '';
                        $iniziali = '';
                        foreach (explode(' ', trim($nome)) as $parte) {
                            if ($parte !== '') $iniziali .= strtoupper(substr($parte, 0, 1));
                            if (strlen($iniziali) >= 2) break;
                        }
                        echo htmlspecialchars($iniziali ?: 'U', ENT_QUOTES, 'UTF-8');
                    ?></span>
                    Ciao&nbsp;<b><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></b>
                </span>
                <a href="/logout.php" class="m-btn m-btn--ghost m-btn--sm">Logout</a>
            <?php else: ?>
                <a href="/login.php"    class="m-btn m-btn--secondary m-btn--sm">Login</a>
                <a href="/register.php" class="m-btn m-btn--primary m-btn--sm">Registrati</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="nav-toggle"
                aria-expanded="false" aria-controls="navbar" aria-label="Apri menu">
            <svg class="icon-open"  viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true"><line x1="3" y1="6"  x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <svg class="icon-close" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true"><line x1="5"  y1="5"  x2="19" y2="19"/><line x1="19" y1="5"  x2="5"  y2="19"/></svg>
        </button>
    </div>
</nav>

<script>
(function () {
    var btn    = document.getElementById('nav-toggle');
    var navbar = document.getElementById('navbar');
    if (!btn || !navbar) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = navbar.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
        if (!navbar.contains(e.target) && !btn.contains(e.target)) {
            navbar.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 880) {
            navbar.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}());

// ── PWA: registra Service Worker ──────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .catch(function(err) { console.warn('SW registration failed:', err); });
    });
}
</script>