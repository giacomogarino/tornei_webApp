<?php
/**
 * PHP/LOGIN_CHECK.PHP — Verifica credenziali + rate limiting
 * ===========================================================
 * Posizione: /php/login_check.php
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
require_once __DIR__ . '/../php/helpers/rate_limit.php';

session_secure_start();
csrf_verify();

require_once __DIR__ . '/../conf/db_config.php';

$email = trim($_POST['email'] ?? '');
$psw   = $_POST['password']  ?? '';

if (empty($email) || empty($psw)) {
    header('Location: ../login.php?msg=campiVuoti'); exit;
}

// ── 1. Check rate limit per IP (prima ancora di toccare il DB utenti) ─
$rl = rate_limit_check($conn, 'login', $email);
if ($rl['blocked']) {
    $wait = format_wait($rl['wait_seconds']);
    header('Location: ../login.php?msg=troppiTentativi&wait=' . urlencode($wait));
    exit;
}

// ── 2. Cerca l'utente ────────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, email, password, verified, created_at, google_id, role, bannato
     FROM utente WHERE email = ? LIMIT 1'
);
if (!$stmt) {
    error_log('Login prepare error: ' . $conn->error);
    header('Location: ../login.php?msg=err'); exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Account solo Google
if ($row && !empty($row['google_id']) && empty($row['password'])) {
    // Non registriamo questo come "fail" — non è un tentativo malevolo
    header('Location: ../login.php?msg=usaGoogle'); exit;
}

// Utente bannato
if ($row && (int)$row['bannato'] === 1) {
    rate_limit_record($conn, 'login', 'fail', $email);
    header('Location: ../login.php?msg=accountBannato'); exit;
}

// ── 3. Verifica password ─────────────────────────────────────────────
if ($row && password_verify($psw, $row['password'])) {

    if ((int)$row['verified'] === 0) {
        header('Location: ../login.php?msg=emailNonConfermata'); exit;
    }

    // ✅ Login riuscito: registra OK e azzera il conto dei fail
    rate_limit_record($conn, 'login', 'ok', $email);

    session_regenerate_id(false);
    $_SESSION['login']             = 'ok';
    $_SESSION['id_utente']         = $row['id'];
    $_SESSION['nome_utente']       = $row['nome'];
    $_SESSION['cognome_utente']    = $row['cognome'];
    $_SESSION['email_utente']      = $row['email'];
    $_SESSION['verified_utente']   = (bool)$row['verified'];
    $_SESSION['created_at_utente'] = $row['created_at'];
    $_SESSION['role_utente']       = $row['role'];

    $conn->close();

    if ($row['role'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

// ❌ Password errata: registra fail
rate_limit_record($conn, 'login', 'fail', $email);

// Calcola quanti tentativi rimangono prima del blocco (per UX)
$rimanenti = null;
$windowStart = date('Y-m-d H:i:s', time() - 10 * 60);
$cntStmt = $conn->prepare(
    "SELECT COUNT(*) AS n FROM login_attempt
     WHERE ip = ? AND endpoint = 'login' AND esito = 'fail' AND created_at >= ?"
);
$ip = get_real_ip();
$cntStmt->bind_param('ss', $ip, $windowStart);
$cntStmt->execute();
$failCount = (int)$cntStmt->get_result()->fetch_assoc()['n'];
$cntStmt->close();
$conn->close();

// Avvisa solo quando si avvicina al blocco (ultimi 2 tentativi)
if ($failCount >= 3) {
    $rimanenti = max(0, 5 - $failCount);
    header('Location: ../login.php?msg=errLogin&rimanenti=' . $rimanenti);
} else {
    header('Location: ../login.php?msg=errLogin');
}
exit;