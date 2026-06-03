<?php
/**
 * PHP/INVIA_SEGNALAZIONE.PHP
 * Riceve il form di segnalazione e inserisce in tabella `segnalazione`.
 * Posizione: /php/invia_segnalazione.php
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
session_secure_start();

// Solo utenti loggati
if (!isset($_SESSION['login']) || !isset($_SESSION['id_utente'])) {
    header('Location: /login.php?msg=NecessariaAutentificazione');
    exit;
}

require_once __DIR__ . '/../conf/db_config.php';
csrf_verify();

$segnalato_da = (int)$_SESSION['id_utente'];
$target_tipo  = $_POST['target_tipo'] ?? '';
$target_id    = (int)($_POST['target_id'] ?? 0);
$motivo       = trim($_POST['motivo'] ?? '');
$redirect     = $_POST['redirect'] ?? '/index.php';

// Validazione
$tipi_validi = ['utente', 'torneo', 'squadra'];
if (!in_array($target_tipo, $tipi_validi, true) || $target_id <= 0 || $motivo === '') {
    header('Location: ' . $redirect . '?msg=errSegnalazione');
    exit;
}
if (mb_strlen($motivo) > 200) {
    $motivo = mb_substr($motivo, 0, 200);
}

// Non puoi segnalare te stesso
if ($target_tipo === 'utente' && $target_id === $segnalato_da) {
    header('Location: ' . $redirect . '?msg=errSegnalazioneSelf');
    exit;
}

// Evita segnalazioni duplicate aperte dallo stesso utente sullo stesso target
$dup = $conn->prepare(
    "SELECT id FROM segnalazione
     WHERE segnalato_da = ? AND target_tipo = ? AND target_id = ?
       AND stato != 'chiusa' LIMIT 1"
);
$dup->bind_param('isi', $segnalato_da, $target_tipo, $target_id);
$dup->execute();
$isDuplicate = $dup->get_result()->num_rows > 0;
$dup->close();

if ($isDuplicate) {
    header('Location: ' . $redirect . '?msg=segnalazioneGiaInviata');
    exit;
}

$stmt = $conn->prepare(
    'INSERT INTO segnalazione (segnalato_da, target_tipo, target_id, motivo)
     VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('isis', $segnalato_da, $target_tipo, $target_id, $motivo);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: ' . $redirect . '?msg=segnalazioneInviata');
exit;