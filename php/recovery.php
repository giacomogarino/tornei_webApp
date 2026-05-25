<?php
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();
csrf_verify();

require_once __DIR__ . '/../conf/db_config.php';
require_once __DIR__ . '/../conf/app_config.php';

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    header('Location: ../recupera_password.php?msg=emptyEmail');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Risposta sempre uguale per evitare user enumeration
    header('Location: ../login.php?msg=ok');
    exit;
}

$check = $conn->prepare('SELECT id, nome FROM utente WHERE email = ? AND verified = 1 LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if ($row) {
    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expiry     = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $conn->prepare(
        'UPDATE utente SET token = ?, token_expiry = ? WHERE id = ?'
    );
    $stmt->bind_param('ssi', $token_hash, $expiry, $row['id']);
    $stmt->execute();
    $stmt->close();

    $link = BASE_URL . '/php/change_password.php?token=' . urlencode($token);

    $subject = 'Recupera la tua password — Matchora';
    $body    = "Ciao {$row['nome']},\n\n"
             . "Hai richiesto di reimpostare la password del tuo account Matchora.\n\n"
             . "Clicca il link seguente per impostare una nuova password:\n\n"
             . "$link\n\n"
             . "Il link è valido per 24 ore. Se non hai richiesto questo recupero, ignora questa email.\n\n"
             . "— Il team di Matchora\n"
             . BASE_URL;
    $headers = "From: " . MAIL_FROM . "\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "X-Mailer: Matchora/1.0\r\n";

    // Invia al destinatario corretto (l'utente, non l'admin!)
    if (!mail($email, $subject, $body, $headers)) {
        // Rollback token se la mail non parte
        $del = $conn->prepare(
            'UPDATE utente SET token = NULL, token_expiry = NULL WHERE id = ?'
        );
        $del->bind_param('i', $row['id']);
        $del->execute();
        $del->close();
    }
}

$conn->close();

// Risposta sempre uguale indipendentemente dall'esistenza dell'email
// (evita user enumeration — art. 5 §1 GDPR: minimizzazione dati)
header('Location: ../login.php?msg=ok');
exit;
