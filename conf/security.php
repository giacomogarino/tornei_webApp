<?php
/**
 * security.php — Configurazione di sicurezza centralizzata
 * Includere PRIMA di qualsiasi output, come primo file dopo l'avvio della sessione.
 *
 * Risolve:
 *  - Punto 2: disabilita display_errors in produzione
 *  - Punto 7: cookie di sessione HttpOnly + Secure + SameSite
 *  - Punto 8: header HTTP di sicurezza (CSP, X-Frame-Options, ecc.)
 */

// ── Punto 2: errori in produzione ────────────────────────────────────────────
// Gli errori vengono loggati (utile per debug) ma NON mostrati all'utente.
error_reporting(E_ALL);
ini_set('display_errors', 0);       // <-- MAI mostrare errori in produzione
ini_set('log_errors', 1);           // li salva comunque nel log del server
// ini_set('error_log', '/path/to/custom_error.log'); // opzionale: log personalizzato

// ── Punto 7: cookie di sessione sicuri ───────────────────────────────────────
// Va chiamato PRIMA di session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,            // cookie di sessione (sparisce alla chiusura del browser)
        'path'     => '/',
        'domain'   => '',           // lascia vuoto: si applica al dominio corrente
        'secure'   => true,         // cookie inviato SOLO su HTTPS
        'httponly' => true,         // non accessibile da JavaScript (protegge da XSS)
        'samesite' => 'Strict',     // non inviato in richieste cross-site (protegge da CSRF)
    ]);
    session_start();
}

// ── Punto 8: header HTTP di sicurezza ────────────────────────────────────────
// Content-Security-Policy: permette risorse solo dai nostri domini + Google Fonts
header("Content-Security-Policy: default-src 'self'; "
     . "script-src 'self' 'unsafe-inline'; "          // unsafe-inline serve per i <script> inline esistenti
     . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
     . "font-src 'self' https://fonts.gstatic.com; "
     . "img-src 'self' data: https:; "
     . "connect-src 'self'; "
     . "frame-ancestors 'none';");

// Impedisce che il sito sia caricato dentro un <iframe> (clickjacking)
header("X-Frame-Options: DENY");

// Impedisce al browser di "indovinare" il tipo MIME (protegge upload)
header("X-Content-Type-Options: nosniff");

// Forza HTTPS per 1 anno (solo se il sito è sempre su HTTPS)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Non inviare il Referer a siti esterni
header("Referrer-Policy: strict-origin-when-cross-origin");

// Disabilita funzioni browser non necessarie
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// ── Punto 3: protezione CSRF ─────────────────────────────────────────────────

/**
 * Genera (o riutilizza) il token CSRF per la sessione corrente.
 * Chiamare nelle pagine che mostrano form: <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica che il token CSRF inviato dal form sia valido.
 * Chiamare nei file PHP che processano POST.
 * In caso di fallimento fa un redirect o termina l'esecuzione.
 *
 * @param string $redirect  URL a cui redirigere in caso di token non valido (opzionale)
 */
function csrf_verify(string $redirect = ''): void {
    $token_ricevuto = $_POST['csrf_token'] ?? '';
    $token_sessione = $_SESSION['csrf_token'] ?? '';

    if (
        empty($token_ricevuto) ||
        empty($token_sessione) ||
        !hash_equals($token_sessione, $token_ricevuto)
    ) {
        if ($redirect) {
            header("Location: $redirect");
            exit;
        }
        http_response_code(403);
        die("Richiesta non valida (CSRF). Torna indietro e riprova.");
    }
}
