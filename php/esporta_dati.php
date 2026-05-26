<?php
/**
 * ESPORTA_DATI.PHP — Portabilità dati (art. 20 GDPR)
 * =====================================================
 * Genera un file JSON con tutti i dati personali dell'utente.
 * Disponibile solo per utenti autenticati.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/helpers/csrf.php';

session_secure_start();

if (!isset($_SESSION['login'])) {
    header('Location: ../login.php?msg=NecessariaAutentificazione');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profilo.php');
    exit;
}

csrf_verify();

require_once('../conf/db_config.php');

$utente_id = (int)($_SESSION['id_utente'] ?? 0);

if (!$utente_id) {
    header('Location: ../profilo.php');
    exit;
}

// Raccoglie i dati dell'utente
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, email, verified, created_at FROM utente WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $utente_id);
$stmt->execute();
$utente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$utente) {
    $conn->close();
    header('Location: ../profilo.php?msg=errore');
    exit;
}

// Tornei creati
$stmt = $conn->prepare(
    'SELECT id, nome, formato, sport, luogo, stato
     FROM torneo WHERE creato_da = ?'
);
$stmt->bind_param('i', $utente_id);
$stmt->execute();
$tornei = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tornei seguiti
$stmt = $conn->prepare(
    'SELECT t.id, t.nome, t.sport
     FROM torneo_seguito ts
     JOIN torneo t ON t.id = ts.torneo_id
     WHERE ts.utente_id = ?'
);
$stmt->bind_param('i', $utente_id);
$stmt->execute();
$seguiti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Squadre come capitano
$stmt = $conn->prepare(
    'SELECT s.id, s.nome, s.stato, t.nome AS torneo
     FROM squadra s
     JOIN torneo t ON t.id = s.torneo_id
     WHERE s.capitano_id = ?'
);
$stmt->bind_param('i', $utente_id);
$stmt->execute();
$squadre = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Rimuovi la password dall'export
unset($utente['password'], $utente['token'], $utente['token_expiry'], $utente['google_id']);

$export = [
    'esportazione_gdpr' => [
        'data_esportazione' => date('c'),
        'titolare'          => 'Matchora Tornei',
        'riferimento_legale'=> 'Art. 20 Regolamento (UE) 2016/679 (GDPR)',
    ],
    'dati_personali' => $utente,
    'tornei_creati'  => $tornei,
    'tornei_seguiti' => $seguiti,
    'squadre'        => $squadre,
];

$json     = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$filename = 'matchora_dati_' . date('Ymd_His') . '.json';

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($json));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
echo $json;
exit;
