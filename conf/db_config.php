<?php
/**
 * DB_CONFIG.PHP — Connessione database e utility
 * ================================================
 * Legge le credenziali da secrets.php collocato FUORI dalla webroot.
 *
 * Struttura directory consigliata su Netsons:
 *
 *   /home/itpbrgro/                      ← directory home account
 *   ├── secrets/
 *   │   └── matchora_secrets.php         ← credenziali (non accessibile via HTTP)
 *   └── public_html/                     ← webroot
 *       ├── conf/
 *       │   ├── db_config.php            ← questo file
 *       │   └── app_config.php
 *       └── ...
 *
 * Se il tuo hosting non permette di scrivere fuori dalla webroot,
 * usa il fallback con .htaccess (vedi sotto) come seconda opzione.
 */

// ── Carica le credenziali da fuori webroot ────────────────────────────
// Opzione A (consigliata): file fuori dalla public_html
$secrets_path = dirname($_SERVER['DOCUMENT_ROOT']) . '/secrets/matchora_secrets.php';

// Opzione B (fallback se A non funziona): file nella webroot
// protetto da .htaccess con "Require all denied"
$secrets_fallback = __DIR__ . '/secrets.php';

if (file_exists($secrets_path)) {
    require_once $secrets_path;
} elseif (file_exists($secrets_fallback)) {
    require_once $secrets_fallback;
} else {
    error_log('CRITICO: secrets.php non trovato in nessun percorso!');
    http_response_code(503);
    die('Servizio temporaneamente non disponibile.');
}

require_once __DIR__ . '/app_config.php';

// ── Connessione ───────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    error_log('DB connection error: ' . $conn->connect_error);
    http_response_code(503);
    die('Servizio temporaneamente non disponibile. Riprova tra qualche minuto.');
}

// ── Aggiornamento automatico stato tornei ─────────────────────────────
// Eseguito al massimo una volta al minuto (lock file)
function aggiorna_tornei_scaduti(mysqli $conn): void {
    $lock_file = sys_get_temp_dir() . '/torneo_cron.lock';
    if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 60)
        return;
    touch($lock_file);
    $conn->query("
        UPDATE torneo
        SET stato = 'in_corso'
        WHERE stato = 'aperto'
          AND data_chiusura_iscrizioni <= NOW()
    ");
}

aggiorna_tornei_scaduti($conn);
