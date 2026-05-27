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
 * Mostra una pagina di errore 403 stilizzata con il design di Matchora.
 */
function csrf_error_page(): void {
    // Calcola il percorso root del progetto (due livelli su da php/helpers/)
    $root = rtrim(dirname(__DIR__, 2), '/\\');
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    $back_url = $referer ? htmlspecialchars($referer, ENT_QUOTES, 'UTF-8') : 'javascript:history.back()';
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Richiesta non valida — Matchora Tornei</title>
        <link rel="icon" type="image/png" href="<?= $root ?>/assets/matchora_icon.png">
        <link rel="stylesheet" href="<?= $root ?>/assets/fonts/load_fonts.php">
        <link rel="stylesheet" href="<?= $root ?>/css/base.css">
        <link rel="stylesheet" href="<?= $root ?>/css/navbar.css">
        <link rel="stylesheet" href="<?= $root ?>/css/footer.css">
    </head>
    <body>

    <?php
    // Includi navbar e footer nativi se disponibili, altrimenti fallback minimale
    $header_path = $root . '/templates/header.php';
    $footer_path = $root . '/templates/footer.php';

    if (file_exists($header_path)) {
        $page_title       = 'Richiesta non valida';
        $page_description = 'Si è verificato un errore di sicurezza. Torna indietro e riprova.';
        require $header_path;
    }
    ?>

    <main class="m-container" style="min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: var(--m-8) var(--m-4);">
        <div style="max-width: 480px; width: 100%; text-align: center;">

            <!-- Icona errore -->
            <div style="
                width: 80px; height: 80px; border-radius: 50%;
                background: var(--m-danger-50, #fff1f2);
                border: 2px solid var(--m-danger-200, #fecdd3);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto var(--m-5);
            ">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                     stroke="var(--m-danger-500, #ef4444)" stroke-width="1.75"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>

            <!-- Codice errore -->
            <p style="font-size: 13px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--m-danger-500, #ef4444); margin-bottom: var(--m-2);">
                Errore 403
            </p>

            <!-- Titolo -->
            <h1 style="font-size: clamp(1.4rem, 4vw, 1.9rem); font-weight: 700; margin-bottom: var(--m-3); color: var(--m-text);">
                Richiesta non valida
            </h1>

            <!-- Spiegazione -->
            <p style="color: var(--m-muted); line-height: 1.65; margin-bottom: var(--m-6);">
                Il modulo che hai inviato non è più valido, probabilmente perché la pagina è rimasta
                aperta troppo a lungo o è stata ricaricata.<br>
                Torna indietro, ricarica la pagina e riprova.
            </p>

            <!-- Azioni -->
            <div style="display: flex; gap: var(--m-3); justify-content: center; flex-wrap: wrap;">
                <a href="<?= $back_url ?>" class="m-btn m-btn--primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true" style="margin-right: 6px;">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Torna indietro
                </a>
                <a href="index.php" class="m-btn m-btn--ghost">
                    Home
                </a>
            </div>

        </div>
    </main>

    <?php if (file_exists($footer_path)) require $footer_path; ?>

    </body>
    </html>
    <?php
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
        csrf_error_page();
        exit;
    }

    // Rigenera il token dopo ogni uso
    unset($_SESSION['csrf_token']);
}