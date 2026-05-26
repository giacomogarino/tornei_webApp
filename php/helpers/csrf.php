<?php
/**
 * CSRF.PHP — Protezione Cross-Site Request Forgery
 * =================================================
 * Uso:
 *   1. Nei form:    echo csrf_field();
 *   2. Negli handler POST:  csrf_verify();
 */

/**
 * Restituisce il token CSRF della sessione corrente (lo crea se non esiste).
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        trigger_error('csrf_token() chiamato senza sessione attiva', E_USER_WARNING);
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Restituisce l'<input> hidden con il token CSRF da inserire nei form.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Verifica il token CSRF inviato via POST.
 * Termina l'esecuzione con HTTP 403 se il token non è valido.
 * Rigenera il token dopo la verifica (one-time use).
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = csrf_token();

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        // Log del tentativo (senza esporre dettagli all'utente)
        error_log('CSRF mismatch da IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'N/D'));
        die('Richiesta non valida. Torna indietro e riprova.');
    }

    // Rigenera il token dopo ogni uso
    unset($_SESSION['csrf_token']);
}
