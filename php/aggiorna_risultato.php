<?php
/**
 * aggiorna_risultato.php — endpoint AJAX per inserimento/modifica risultati
 * Risponde sempre con JSON { ok: bool, msg: string, [data: {}] }
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
require_once __DIR__ . '/../php/helpers/sport_config.php';
require_once __DIR__ . '/../conf/db_config.php';

session_secure_start();

header('Content-Type: application/json; charset=utf-8');

function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg]);
    exit;
}
function json_ok(array $data = [], string $msg = 'ok'): never {
    echo json_encode(['ok' => true, 'msg' => $msg, 'data' => $data]);
    exit;
}

// ── Solo POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Metodo non consentito', 405);

// ── CSRF ──────────────────────────────────────────────────────────
$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!csrf_check($token)) json_err('Token CSRF non valido', 403);

// ── Auth ──────────────────────────────────────────────────────────
$utente_id = $_SESSION['id_utente'] ?? null;
if (!$utente_id) json_err('Non autenticato', 401);

// ── Input ─────────────────────────────────────────────────────────
$partita_id = (int)($_POST['partita_id'] ?? 0);
$punti_casa  = $_POST['casa']   ?? null;
$punti_ospite = $_POST['ospite'] ?? null;

if (!$partita_id || $punti_casa === null || $punti_ospite === null) json_err('Dati mancanti');

$punti_casa   = (int)$punti_casa;
$punti_ospite = (int)$punti_ospite;

if ($punti_casa < 0 || $punti_ospite < 0) json_err('Valori negativi non validi');

// ── Carica partita + torneo ───────────────────────────────────────
$stmt = $conn->prepare("
    SELECT p.*, t.creato_da, t.sport, t.tipo_partita
    FROM partita p
    JOIN torneo t ON t.id = p.torneo_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $partita_id);
$stmt->execute();
$partita = $stmt->get_result()->fetch_assoc();

if (!$partita) json_err('Partita non trovata', 404);
if ($partita['creato_da'] != $utente_id) json_err('Non autorizzato', 403);

$sport     = $partita['sport'];
$sport_cfg = sport_cfg($sport);

// ── Validazione sport-specifica ────────────────────────────────────
// 1. Se lo sport non ammette pareggi, blocca i pareggi
if (!$sport_cfg['ha_pareggio'] && $punti_casa === $punti_ospite) {
    json_err('In ' . $sport_cfg['label'] . ' non sono ammessi pareggi');
}

// 2. In eliminazione diretta (girone = NULL e turno != NULL) 
//    i pareggi NON sono ammessi per tutti gli sport che li prevedono
//    (es. calcio: non ci sono pareggi in eliminazione diretta)
if ($punti_casa === $punti_ospite && $partita['girone'] === null && !empty($partita['turno'])) {
    json_err('Niente pareggi in eliminazione diretta');
}

// ── Salva risultato ───────────────────────────────────────────────
$stmt = $conn->prepare("
    UPDATE partita SET punti_casa = ?, punti_ospite = ?, stato = 'terminata' WHERE id = ?
");
$stmt->bind_param("iii", $punti_casa, $punti_ospite, $partita_id);
$stmt->execute();

$torneo_id = $partita['torneo_id'];

// ── Playoff: genera turno successivo se necessario ────────────────
if ($partita['girone'] === null && $partita['turno'] !== null) {
    $turno = $partita['turno'];

    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND turno = ? AND stato != 'terminata' AND girone IS NULL
    ");
    $stmt->bind_param("is", $torneo_id, $turno);
    $stmt->execute();
    $mancanti = $stmt->get_result()->fetch_assoc()['tot'];

    if ($mancanti == 0) {
        $next_map  = ['ottavi' => 'quarti', 'quarti' => 'semifinale', 'semifinale' => 'finale'];

        if ($turno === 'finale') {
            $conn->query("UPDATE torneo SET stato = 'completato' WHERE id = $torneo_id");
        } elseif (isset($next_map[$turno])) {
            $next = $next_map[$turno];
            // Controlla se esiste già
            $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND turno = ? AND girone IS NULL");
            $stmt->bind_param("is", $torneo_id, $next);
            $stmt->execute();
            $esiste = $stmt->get_result()->fetch_assoc()['tot'];

            if (!$esiste) {
                // Prendi vincitori del turno corrente
                $stmt = $conn->prepare("
                    SELECT CASE WHEN punti_casa > punti_ospite THEN squadra_casa_id ELSE squadra_ospite_id END AS vincitore
                    FROM partita
                    WHERE torneo_id = ? AND turno = ? AND stato = 'terminata' AND girone IS NULL
                ");
                $stmt->bind_param("is", $torneo_id, $turno);
                $stmt->execute();
                $res = $stmt->get_result();
                $vincitori = [];
                while ($r = $res->fetch_assoc()) $vincitori[] = $r['vincitore'];

                for ($i = 0; $i + 1 < count($vincitori); $i += 2) {
                    $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $torneo_id, $vincitori[$i], $vincitori[$i+1], $next);
                    $stmt->execute();
                }
            }
        }
    }
}

// ── Gironi: prova a generare playoff ─────────────────────────────
if ($partita['girone'] !== null) {
    // Controlla se tutti i gironi sono finiti
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato != 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $pendenti = $stmt->get_result()->fetch_assoc()['tot'];

    if ($pendenti == 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND girone IS NULL");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['tot'] == 0) {
            // Qui andrebbe la logica per generare i playoff
            // (per ora non implementata)
            $playoff_generato = true;
        }
    }
}

// ── Restituisci dati aggiornati per la UI ─────────────────────────
$stmt = $conn->prepare("SELECT punti_casa, punti_ospite, stato FROM partita WHERE id = ?");
$stmt->bind_param("i", $partita_id);
$stmt->execute();
$updated = $stmt->get_result()->fetch_assoc();

json_ok([
    'partita_id'   => $partita_id,
    'punti_casa'   => (int)$updated['punti_casa'],
    'punti_ospite' => (int)$updated['punti_ospite'],
    'stato'        => $updated['stato'],
]);