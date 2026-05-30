<?php
/**
 * HEADER_ADMIN.PHP — Middleware autenticazione + autorizzazione admin
 * ====================================================================
 * Include questo file in OGNI pagina della sezione /admin/.
 * Controlla:
 *   1. Sessione attiva (utente loggato)
 *   2. Ruolo admin nel DB (non solo in sessione, per sicurezza)
 *   3. Utente non bannato
 *
 * Uso:
 *   require_once __DIR__ . '/../templates/header_admin.php';
 */

require_once __DIR__ . '/../php/helpers/session.php';
session_secure_start();

// ── 1. Utente loggato? ───────────────────────────────────────────────
if (!isset($_SESSION['login']) || !isset($_SESSION['id_utente'])) {
    header('Location: /login.php?msg=NecessariaAutentificazione');
    exit;
}

// ── 2. Verifica ruolo admin direttamente nel DB ──────────────────────
require_once __DIR__ . '/../conf/db_config.php';

$stmt = $conn->prepare(
    'SELECT role, bannato FROM utente WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $_SESSION['id_utente']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['role'] !== 'admin' || (int)$row['bannato'] === 1) {
    error_log(sprintf(
        'ADMIN ACCESS DENIED — user_id=%d ip=%s uri=%s',
        $_SESSION['id_utente'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['REQUEST_URI'] ?? ''
    ));
    http_response_code(403);
    require_once __DIR__ . '/header.php';
    echo '<main class="m-page"><div class="m-container" style="padding:4rem 0;text-align:center;">';
    echo '<h1>403 — Accesso negato</h1>';
    echo '<p>Non hai i permessi per accedere a questa pagina.</p>';
    echo '<a href="/index.php" class="m-btn">Torna alla home</a>';
    echo '</div></main>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// ── 3. Aggiorna il ruolo in sessione ────────────────────────────────
$_SESSION['role_utente'] = 'admin';

// ── 4. Helper: registra un'azione nel log admin ──────────────────────
function admin_log(
    mysqli $conn,
    string $azione,
    string $targetTipo = '',
    int    $targetId   = 0,
    array  $dettagli   = []
): void {
    $adminId    = $_SESSION['id_utente'];
    $ip         = $_SERVER['REMOTE_ADDR'] ?? null;
    $dettagliJs = $dettagli ? json_encode($dettagli, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conn->prepare(
        'INSERT INTO admin_log (admin_id, azione, target_tipo, target_id, dettagli, ip)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issiis', $adminId, $azione, $targetTipo, $targetId, $dettagliJs, $ip);
    $stmt->execute();
    $stmt->close();
}

// ── 5. Includi header HTML ───────────────────────────────────────────
require_once __DIR__ . '/header.php';