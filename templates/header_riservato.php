<?php
require_once __DIR__ . '/../conf/security.php';  // avvia sessione sicura

if(!isset($_SESSION['login'])) {
    header("location: login.php?msg=NecessariaAutentificazione");
    exit;
}

require_once __DIR__ . '/header.php';
?>
