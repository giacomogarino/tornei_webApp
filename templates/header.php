<?php
// Per sapere in che pagina si è
if (session_status() === PHP_SESSION_NONE)
    session_start();

$current = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="it">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet"
          href="/staging/css/navbar.css">

    <title>Matchora Tornei</title>

</head>

<body>

    <header class="main-header">

        <!-- TOP HEADER -->
        <div class="header-top">

            <!-- LOGO / TITLE -->
            <div class="header-brand">

                <h1 class="site-title">
                    MATCHORA TORNEI
                </h1>

            </div>

            <!-- USER -->
            <div class="header-user">

                <h2 class="welcome-text">

                    Ciao

                    <?php if (isset($_SESSION['id_utente'])): ?>

                        <span class="username">
                            <?= htmlspecialchars($_SESSION['nome_utente']) ?>
                        </span>

                    <?php else: ?>

                        <a href="login.php"
                           class="login-link">
                            Login
                        </a>

                    <?php endif; ?>

                </h2>

            </div>

        </div>

        <!-- NAVBAR -->
        <div class="navbar-container">

            <nav class="navbar">

                <a href="index.php"
                   class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
                    Home
                </a>

                <a href="profilo.php"
                   class="nav-link <?= $current === 'profilo.php' ? 'active' : '' ?>">
                    Profilo
                </a>

                <a href="tornei_seguiti.php"
                   class="nav-link <?= $current === 'tornei_seguiti.php' ? 'active' : '' ?>">
                    Seguiti
                </a>

                <a href="privati.php"
                   class="nav-link <?= $current === 'privati.php' ? 'active' : '' ?>">
                    Privati
                </a>

                <a href="tornei_creati.php"
                   class="nav-link <?= $current === 'tornei_creati.php' ? 'active' : '' ?>">
                    Tornei creati
                </a>

                <?php if (!isset($_SESSION['id_utente'])): ?>

                    <a href="login.php"
                       class="nav-link nav-auth">
                        Login
                    </a>

                <?php else: ?>

                    <a href="logout.php"
                       class="nav-link nav-auth logout-link">
                        Logout
                    </a>

                <?php endif; ?>

            </nav>

        </div>

    </header>