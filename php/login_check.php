<?php
/**
 * LOGIN_CHECK.PHP — Verifica credenziali e avvia sessione
 * ========================================================
 * Aggiornato: aggiunge role e bannato alla sessione.
 */

require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();
csrf_verify();

require_once __DIR__ . '/../conf/db_config.php';

$email = trim($_POST['email'] ?? '');
$psw   = $_POST['password'] ?? '';

if (empty($email) || empty($psw)) {
    header('Location: ../login.php?msg=campiVuoti');
    exit;
}

// Seleziona anche role e bannato
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, email, password, verified, created_at, google_id, role, bannato
     FROM utente WHERE email = ? LIMIT 1'
);
if (!$stmt) {
    error_log('Login prepare error: ' . $conn->error);
    header('Location: ../login.php?msg=err');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Account solo Google
if ($row && !empty($row['google_id']) && empty($row['password'])) {
    header('Location: ../login.php?msg=usaGoogle');
    exit;
}

// Utente bannato: rifiuta prima ancora di verificare la password
if ($row && (int)$row['bannato'] === 1) {
    header('Location: ../login.php?msg=accountBannato');
    exit;
}

if ($row && password_verify($psw, $row['password'])) {

    if ((int)$row['verified'] === 0) {
        header('Location: ../login.php?msg=emailNonConfermata');
        exit;
    }

    session_regenerate_id(false);

    $_SESSION['login']             = 'ok';
    $_SESSION['id_utente']         = $row['id'];
    $_SESSION['nome_utente']       = $row['nome'];
    $_SESSION['cognome_utente']    = $row['cognome'];
    $_SESSION['email_utente']      = $row['email'];
    $_SESSION['verified_utente']   = (bool)$row['verified'];
    $_SESSION['created_at_utente'] = $row['created_at'];
    $_SESSION['role_utente']       = $row['role'];   // 'user' o 'admin'

    // Redirect: admin → pannello, utente → home
    if ($row['role'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

header('Location: ../login.php?msg=errLogin');
exit;
