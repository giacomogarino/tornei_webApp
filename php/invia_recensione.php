<?php
/**
 * PHP/INVIA_RECENSIONE.PHP — Salva una recensione all'organizzatore
 * Posizione: /php/invia_recensione.php
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
session_secure_start();

if (!isset($_SESSION['login'])) {
    header('Location: /login.php?msg=NecessariaAutentificazione'); exit;
}

require_once __DIR__ . '/../conf/db_config.php';
csrf_verify();

$autore_id       = (int)$_SESSION['id_utente'];
$torneo_id       = (int)($_POST['torneo_id']       ?? 0);
$organizzatore_id= (int)($_POST['organizzatore_id'] ?? 0);
$voto            = (int)($_POST['voto']             ?? 0);
$testo           = trim($_POST['testo']             ?? '');
$redirect        = '/dettagli_torneo.php?id=' . $torneo_id;

// ── Validazione base ──────────────────────────────────────────────────
if ($torneo_id <= 0 || $organizzatore_id <= 0 || $voto < 1 || $voto > 5) {
    header('Location: ' . $redirect . '&msg=errRecensione'); exit;
}
if ($autore_id === $organizzatore_id) {
    header('Location: ' . $redirect . '&msg=errRecensioneSelf'); exit;
}
if (mb_strlen($testo) > 500) $testo = mb_substr($testo, 0, 500);

// ── Il torneo deve essere completato e l'autore deve avervi partecipato ──
$chk = $conn->prepare(
    "SELECT t.stato, t.creato_da,
            (SELECT COUNT(*) FROM squadra sq
             JOIN giocatore_squadra g ON g.squadra_id = sq.id
             WHERE sq.torneo_id = t.id
               AND (sq.capitano_id = ? OR g.utente_id = ?)
               AND sq.stato = 'approvata') AS ha_partecipato
     FROM torneo t WHERE t.id = ? LIMIT 1"
);
$chk->bind_param('iii', $autore_id, $autore_id, $torneo_id);
$chk->execute();
$torneo = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$torneo) {
    header('Location: /index.php?msg=errRecensione'); exit;
}
if ($torneo['stato'] !== 'completato') {
    header('Location: ' . $redirect . '&msg=errTorneoNonCompletato'); exit;
}
if ((int)$torneo['ha_partecipato'] === 0) {
    header('Location: ' . $redirect . '&msg=errNonPartecipante'); exit;
}
if ((int)$torneo['creato_da'] !== $organizzatore_id) {
    header('Location: ' . $redirect . '&msg=errRecensione'); exit;
}

// ── Inserisci o aggiorna (upsert) ─────────────────────────────────────
$stmt = $conn->prepare(
    'INSERT INTO recensione (organizzatore_id, autore_id, torneo_id, voto, testo)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE voto = VALUES(voto), testo = VALUES(testo)'
);
$stmt->bind_param('iiiis', $organizzatore_id, $autore_id, $torneo_id, $voto, $testo);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: ' . $redirect . '&msg=recensioneInviata'); exit;
