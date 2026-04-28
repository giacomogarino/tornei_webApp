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
    <link rel="stylesheet" href="../css/navbar.css">
    <title>Torneo crazy</title>
</head>
<body>
    <header>
        <h1>WebApp Gestione Tornei</h1>
        <h2>Ciao 
            <?php if (isset($_SESSION['id_utente'])): ?>
                <?= $_SESSION['nome_utente']; ?>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </h2>
        <div id="navbar">
            <nav>
                <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="profilo.php" class="<?= $current === 'profilo.php' ? 'active' : '' ?>">Profilo</a>
                <a href="seguiti.php" class="<?= $current === 'seguiti.php' ? 'active' : '' ?>">Seguiti</a>
                <a href="privati.php" class="<?= $current === 'privati.php' ? 'active' : '' ?>">Privati</a>

                <?php if (!isset($_SESSION['id_utente'])): ?>
                    <a href="login.php">Login</a>
                <?php else: ?>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>