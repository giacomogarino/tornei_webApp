<?php
/**
 * HEADER_RISERVATO.PHP — Header con controllo autenticazione
 * ==========================================================
 * Avvia la sessione sicura e reindirizza al login se l'utente
 * non è autenticato. Poi include il normale header.php.
 */

require_once __DIR__ . '/../php/helpers/session.php';
session_secure_start();

if (!isset($_SESSION['login'])) {
    header('Location: login.php?msg=NecessariaAutentificazione');
    exit;
}

require_once __DIR__ . '/header.php';
