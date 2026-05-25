<?php
/**
 * DB_CONFIG.PHP — Connessione database e utility
 * ================================================
 * Le credenziali sono in secrets.php (non committare su git).
 */

require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/app_config.php';

// Connessione
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    // In produzione: non esporre dettagli dell'errore all'utente
    error_log('DB connection error: ' . $conn->connect_error);
    http_response_code(503);
    die('Servizio temporaneamente non disponibile. Riprova tra qualche minuto.');
}

// ── Utility legacy (mantenuta per compatibilità) ──────────────────────
function cryptpsw(string $psw): string {
    // ⚠️  Deprecata — usare password_hash()/password_verify() invece
    $salt = 'chiave_per_cifratura';
    return crypt($psw, $salt);
}

// ── Aggiornamento automatico stato tornei ────────────────────────────
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
