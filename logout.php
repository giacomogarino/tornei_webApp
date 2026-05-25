<?php
/**
 * LOGOUT.PHP
 * Distrugge la sessione e reindirizza alla home.
 */
require_once __DIR__ . '/php/helpers/session.php';
session_secure_start();

session_unset();
session_destroy();

// Elimina il cookie di sessione dal browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: index.php');
exit;
