<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

if(!isset($_SESSION['login'])) {
    header("location: login.php?msg=NecessariaAutentificazione");
    exit;
}

require_once __DIR__ . '/header.php';
?>
