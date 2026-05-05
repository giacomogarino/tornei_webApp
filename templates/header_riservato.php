<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

if(!isset($_SESSION['login']))
    header("location: ./login.php?msg=NecessariaAutentificazione");

$current = basename($_SERVER['PHP_SELF']);
require_once('header.php')
?>