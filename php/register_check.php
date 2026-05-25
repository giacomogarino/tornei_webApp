<?php
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();
csrf_verify();

require_once __DIR__ . '/../conf/db_config.php';
require_once __DIR__ . '/../conf/app_config.php';

$nome    = trim($_POST['nome']     ?? '');
$cognome = trim($_POST['cognome']  ?? '');
$email   = trim($_POST['email']    ?? '');
$psw     = $_POST['password']      ?? '';
$psw2    = $_POST['password2']     ?? '';
$privacy = $_POST['privacy_ok']    ?? '';

// Validazioni
if (empty($nome) || empty($cognome) || empty($email) || empty($psw)) {
    header('Location: ../register.php?msg=campiVuoti');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?msg=emailNonValida');
    exit;
}
if (strlen($psw) < 8) {
    header('Location: ../register.php?msg=passwordDebole');
    exit;
}
if ($psw !== $psw2) {
    header('Location: ../register.php?msg=passwordDiverse');
    exit;
}
if (empty($privacy)) {
    header('Location: ../register.php?msg=privacyNonAccettata');
    exit;
}

$password_hash = password_hash($psw, PASSWORD_BCRYPT);

// Controlla se l'email esiste già
$check = $conn->prepare('SELECT id, verified FROM utente WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$result = $check->get_result();
$existing = $result->fetch_assoc();
$check->close();

if ($existing) {
    if ((int)$existing['verified'] === 1) {
        header('Location: ../register.php?msg=emailEsistente');
        exit;
    }
    // Account non verificato: elimina e ricrea
    $del = $conn->prepare('DELETE FROM utente WHERE id = ?');
    $del->bind_param('i', $existing['id']);
    $del->execute();
    $del->close();
}

// Genera token di verifica
$token = bin2hex(random_bytes(32));

// Inserisce l'utente (non ancora verificato)
$stmt = $conn->prepare(
    'INSERT INTO utente (nome, cognome, password, email, token, verified)
     VALUES (?, ?, ?, ?, ?, 0)'
);
$stmt->bind_param('sssss', $nome, $cognome, $password_hash, $email, $token);

if (!$stmt->execute()) {
    error_log('Register insert error: ' . $conn->error);
    header('Location: ../register.php?msg=errMsg');
    exit;
}
$stmt->close();

// Costruisce il link di verifica
$link = BASE_URL . '/php/verify_email.php?token=' . urlencode($token);

$subject = 'Conferma la tua registrazione — Matchora';
$body    = "Ciao $nome,\n\n"
         . "Grazie per esserti registrato su Matchora Tornei!\n\n"
         . "Conferma il tuo account cliccando sul link seguente:\n\n"
         . "$link\n\n"
         . "Il link è valido per 24 ore.\n\n"
         . "Se non hai richiesto questa registrazione, ignora questa email.\n\n"
         . "— Il team di Matchora\n"
         . BASE_URL;
$headers = "From: " . MAIL_FROM . "\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "X-Mailer: Matchora/1.0\r\n";

if (mail($email, $subject, $body, $headers)) {
    $conn->close();
    header('Location: ../register.php?msg=confermaInviata');
} else {
    // Rollback: elimina utente se l'email non è stata inviata
    $del = $conn->prepare('DELETE FROM utente WHERE token = ?');
    $del->bind_param('s', $token);
    $del->execute();
    $del->close();
    $conn->close();
    header('Location: ../register.php?msg=errMsg');
}
exit;
