<?php
/*se c'è una sessione ma la sessione non è attiva allora non posso entrare 
lo usiamo quando abbiamo una pagina riservata in cui serve il login*/
session_start(); //apre o crea una nuova sessione

if(!isset($_SESSION['login']))
    header("location: ./index.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progetto scuola</title>
</head>
<body>
    <header>
        <div id="header_top">
            <div>Registro ITIS</div>
        </div>
        <div id="header_php">
            <?php
                echo "<h2>Benvenuto ".$_SESSION['nome_utente']."</h2>";
            ?>
        </div> 
        <div id="header_logout">
            <a href="index.php"><button>LOG OUT</button></a>
        </div>
    </header>