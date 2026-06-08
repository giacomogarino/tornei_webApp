<?php
/**
 * PHP/HELPERS/RATE_LIMIT.PHP — Rate limiting per login e registrazione
 * =====================================================================
 * Posizione: /php/helpers/rate_limit.php
 *
 * Strategia a due livelli:
 *   1. Per IP     — blocca attacchi a dizionario da un singolo IP
 *   2. Per email  — blocca attacchi mirati a un account specifico
 *
 * Finestre temporali e soglie:
 *   - Login:      5 fail in 10 min → backoff 30s | 20 fail in 1h → blocco 15 min
 *   - Register:   10 tentativi in 1h per IP → blocco 30 min
 *
 * NON usa ban permanenti: tutti i blocchi scadono automaticamente.
 * NON rivela all'utente se il blocco è per IP o per email (sicurezza).
 */

/**
 * Recupera l'IP reale del visitatore.
 * Supporta proxy/CDN (Cloudflare, hosting Netsons).
 */
function get_real_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            // X-Forwarded-For può contenere una lista: prendi il primo
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Controlla se l'IP o l'email è bloccato.
 * Registra il tentativo nel DB.
 * Pulisce record vecchi (1% di probabilità per non appesantire ogni richiesta).
 *
 * @param mysqli      $conn      Connessione DB
 * @param string      $endpoint  'login' | 'register' | 'google'
 * @param string|null $email     Email usata nel tentativo (null se non disponibile)
 *
 * @return array{blocked: bool, wait_seconds: int, reason: string}
 */
function rate_limit_check(mysqli $conn, string $endpoint, ?string $email = null): array {
    $ip  = get_real_ip();
    $now = date('Y-m-d H:i:s');

    // ── Pulizia probabilistica (1%) ───────────────────────────────────
    if (random_int(1, 100) === 1) {
        $conn->query("DELETE FROM login_attempt WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }

    // ── Configurazione soglie per endpoint ────────────────────────────
    $cfg = match($endpoint) {
        'login'    => [
            ['window_min' => 10, 'max_fail' => 5,  'block_sec' => 30  * 60], // 5 fail/10min → 30min
            ['window_min' => 60, 'max_fail' => 15, 'block_sec' => 15  * 60], // 15 fail/1h  → 15min
        ],
        'register' => [
            ['window_min' => 60, 'max_fail' => 10, 'block_sec' => 30  * 60], // 10 tentativi/1h → 30min
        ],
        default    => [
            ['window_min' => 60, 'max_fail' => 20, 'block_sec' => 60  * 60],
        ],
    };

    // ── Check per IP ──────────────────────────────────────────────────
    foreach ($cfg as $rule) {
        $windowStart = date('Y-m-d H:i:s', time() - $rule['window_min'] * 60);
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS n, MAX(created_at) AS last_attempt
             FROM login_attempt
             WHERE ip = ? AND endpoint = ? AND esito = 'fail'
               AND created_at >= ?"
        );
        $stmt->bind_param('sss', $ip, $endpoint, $windowStart);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)$row['n'] >= $rule['max_fail']) {
            // Calcola quanto manca allo sblocco
            $lastTs    = strtotime($row['last_attempt']);
            $unblockTs = $lastTs + $rule['block_sec'];
            $waitSec   = max(0, $unblockTs - time());
            return ['blocked' => true, 'wait_seconds' => $waitSec, 'reason' => 'ip'];
        }
    }

    // ── Check per email (solo se fornita) ────────────────────────────
    if ($email !== null && $endpoint === 'login') {
        $windowStart = date('Y-m-d H:i:s', time() - 15 * 60); // 15 minuti
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS n, MAX(created_at) AS last_attempt
             FROM login_attempt
             WHERE email = ? AND endpoint = 'login' AND esito = 'fail'
               AND created_at >= ?"
        );
        $stmt->bind_param('ss', $email, $windowStart);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)$row['n'] >= 8) { // 8 fail sull'email in 15 min
            $waitSec = max(0, strtotime($row['last_attempt']) + 20 * 60 - time());
            return ['blocked' => true, 'wait_seconds' => $waitSec, 'reason' => 'email'];
        }
    }

    return ['blocked' => false, 'wait_seconds' => 0, 'reason' => ''];
}

/**
 * Registra un tentativo (successo o fallimento).
 *
 * @param mysqli      $conn
 * @param string      $endpoint  'login' | 'register'
 * @param string      $esito     'ok' | 'fail'
 * @param string|null $email
 */
function rate_limit_record(mysqli $conn, string $endpoint, string $esito, ?string $email = null): void {
    $ip = get_real_ip();
    $stmt = $conn->prepare(
        'INSERT INTO login_attempt (ip, email, endpoint, esito) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('ssss', $ip, $email, $endpoint, $esito);
    $stmt->execute();
    $stmt->close();
}

/**
 * Formatta i secondi di attesa in testo leggibile.
 */
function format_wait(int $seconds): string {
    if ($seconds < 60)  return $seconds . ' second' . ($seconds === 1 ? 'o' : 'i');
    $min = (int)ceil($seconds / 60);
    return $min . ' minut' . ($min === 1 ? 'o' : 'i');
}
