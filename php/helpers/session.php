<?php
/**
 * SESSION.PHP — Avvio sicuro della sessione
 * ==========================================
 * Imposta tutti i flag di sicurezza sui cookie di sessione
 * prima di chiamare session_start().
 *
 * Da includere UNA SOLA VOLTA, prima di qualsiasi output.
 */

function session_secure_start(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return; // già avviata
    }

    $is_https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_set_cookie_params([
        'lifetime' => 0,          // session cookie (eliminato alla chiusura browser)
        'path'     => '/',
        'domain'   => '',          // dominio corrente
        'secure'   => $is_https,  // solo HTTPS
        'httponly' => true,        // non accessibile da JavaScript
        'samesite' => 'Lax',      // protezione CSRF aggiuntiva
    ]);

    session_start();
}
