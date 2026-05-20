<?php
    // per sapere in che pagina si  e per la sessione
    if(session_status() === PHP_SESSION_NONE)
        session_start();

    $current = basename($_SERVER['PHP_SELF']);

    function nav_active($page, $current) {
        return $current === $page ? 'm-navbar__link m-navbar__link--active' : 'm-navbar__link';
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchora  Tornei</title>
    <link rel="icon" type="image/png" href="assets/matchora_icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>

<nav class="m-navbar">
    <div class="m-navbar__inner">
        <a href="index.php" class="m-navbar__brand">
            <span class="m-navbar__brand-mark"><img src="assets/matchora_icon.png" alt=""></span>
            <span class="m-navbar__brand-name">MATCHORA<span class="m-navbar__brand-sub">Tornei</span></span>
        </a>

        <ul class="m-navbar__links" id="navbar">
            <li><a href="index.php" class="<?= nav_active('index.php', $current) ?>">Home</a></li>
            <li><a href="profilo.php" class="<?= nav_active('profilo.php', $current) ?>">Profilo</a></li>
            <li><a href="tornei_seguiti.php" class="<?= nav_active('tornei_seguiti.php', $current) ?>">Seguiti</a></li>
            <li><a href="privati.php" class="<?= nav_active('privati.php', $current) ?>">Privati</a></li>
            <li><a href="tornei_creati.php" class="<?= nav_active('tornei_creati.php', $current) ?>">Tornei creati</a></li>
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
                        echo htmlspecialchars($iniziali ?: 'U');
                    ?></span>
                    Ciao, <b><?= htmlspecialchars($nome) ?></b>
                </span>
                <a href="logout.php" class="m-btn m-btn--ghost m-btn--sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="m-btn m-btn--secondary m-btn--sm">Login</a>
                <a href="register.php" class="m-btn m-btn--primary m-btn--sm">Registrati</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="navbar" aria-label="Apri menu">
            <svg class="icon-open" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true">
                <line x1="3" y1="6"  x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
            <svg class="icon-close" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" fill="none" aria-hidden="true">
                <line x1="5"  y1="5"  x2="19" y2="19"/>
                <line x1="19" y1="5"  x2="5"  y2="19"/>
            </svg>
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
    })();
</script>
