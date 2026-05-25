<?php
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();
csrf_verify(); // blocca richieste senza token valido

require_once __DIR__ . '/../conf/db_config.php';

$email = trim($_POST['email'] ?? '');
$psw   = $_POST['password'] ?? '';

if (empty($email) || empty($psw)) {
    header('Location: ../login.php?msg=campiVuoti');
    exit;
}

// Seleziona solo le colonne necessarie (non SELECT *)
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, email, password, verified, created_at, google_id
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

// Account creato via Google: non ha password locale
if ($row && !empty($row['google_id']) && empty($row['password'])) {
    header('Location: ../login.php?msg=usaGoogle');
    exit;
}

if ($row && password_verify($psw, $row['password'])) {

    if ((int)$row['verified'] === 0) {
        header('Location: ../login.php?msg=emailNonConfermata');
        exit;
    }

    // Rigenera l'ID di sessione dopo il login (session fixation protection)
    session_regenerate_id(true);

    $_SESSION['login']             = 'ok';
    $_SESSION['id_utente']         = $row['id'];
    $_SESSION['nome_utente']       = $row['nome'];
    $_SESSION['cognome_utente']    = $row['cognome'];
    $_SESSION['email_utente']      = $row['email'];
    $_SESSION['verified_utente']   = (bool)$row['verified'];
    $_SESSION['created_at_utente'] = $row['created_at'];

    header('Location: ../index.php');
    exit;
}

header('Location: ../login.php?msg=errLogin');
exit;
