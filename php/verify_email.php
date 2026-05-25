<?php
require_once __DIR__ . '/../conf/db_config.php';

$token = trim($_GET['token'] ?? '');

if (empty($token) || strlen($token) !== 64) {
    header('Location: ../register.php?msg=errMsg');
    exit;
}

// Cerca utente con quel token non ancora verificato
$stmt = $conn->prepare(
    'SELECT id, created_at FROM utente WHERE token = ? AND verified = 0 LIMIT 1'
);
$stmt->bind_param('s', $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: ../register.php?msg=errMsg');
    exit;
}

// Controllo scadenza 24 ore
$diff = time() - (new DateTime($row['created_at']))->getTimestamp();
if ($diff > 86400) {
    $del = $conn->prepare('DELETE FROM utente WHERE id = ?');
    $del->bind_param('i', $row['id']);
    $del->execute();
    $del->close();
    $conn->close();
    header('Location: ../register.php?msg=errMsg');
    exit;
}

// Verifica l'account e cancella il token
$upd = $conn->prepare(
    'UPDATE utente SET verified = 1, token = NULL WHERE id = ?'
);
$upd->bind_param('i', $row['id']);
$success = $upd->execute();
$upd->close();
$conn->close();

if ($success) {
    header('Location: ../login.php?msg=registrazioneCompletata');
} else {
    header('Location: ../register.php?msg=errMsg');
}
exit;
