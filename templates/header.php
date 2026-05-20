<?php
    // per sapere in che pagina si è
    if(session_status() === PHP_SESSION_NONE)
        session_start();
    
    $current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/staging/css/navbar.css">
    <title>Torneo crazy</title>
</head>
<body>
    <header>
        <h1 class="header-title">WebApp Gestione Tornei</h1>
        <h2 class="header-greeting">Ciao 
            <?php if (isset($_SESSION['id_utente'])): ?>
                <?= $_SESSION['nome_utente']; ?>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </h2>

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

        <div id="navbar">
            <nav>
                <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="profilo.php" class="<?= $current === 'profilo.php' ? 'active' : '' ?>">Profilo</a>
                <a href="tornei_seguiti.php" class="<?= $current === 'tornei_seguiti.php' ? 'active' : '' ?>">Seguiti</a>
                <a href="privati.php" class="<?= $current === 'privati.php' ? 'active' : '' ?>">Privati</a>
                <a href="tornei_creati.php" class="<?= $current === 'tornei_creati.php' ? 'active' : '' ?>">Tornei creati</a>

                <?php if (!isset($_SESSION['id_utente'])): ?>
                    <a href="login.php">Login</a>
                <?php else: ?>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <script>
        (function () {
            var btn    = document.getElementById('nav-toggle');
            var navbar = document.getElementById('navbar');

            btn.addEventListener('click', function () {
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
                if (window.innerWidth > 768) {
                    navbar.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>