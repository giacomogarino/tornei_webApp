<?php
/**
 * esporta_calendario.php
 * Esporta le partite di un torneo come file .ics (iCalendar).
 * Funziona anche senza login per i tornei pubblici.
 * GET ?id=TORNEO_ID[&squadra=SQUADRA_ID]
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../conf/db_config.php';

session_secure_start();

$torneo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$squadra_id = isset($_GET['squadra']) ? (int)$_GET['squadra'] : 0;

if (!$torneo_id) { http_response_code(400); exit('ID torneo mancante'); }

// Carica torneo
$stmt = $conn->prepare("SELECT id, nome, luogo, sport, visibilita FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if (!$torneo) { http_response_code(404); exit('Torneo non trovato'); }

// Tornei privati: solo utenti autenticati
if ($torneo['visibilita'] === 'privato' && !isset($_SESSION['id_utente'])) {
    http_response_code(403); exit('Torneo privato');
}

// Carica partite con orario
$sql = "
    SELECT p.id, p.orario, p.turno, p.girone,
           sc.nome AS nome_casa, so.nome AS nome_ospite,
           p.punti_casa, p.punti_ospite, p.stato
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.orario IS NOT NULL
";
$params  = [$torneo_id];
$types   = 'i';

if ($squadra_id) {
    $sql    .= " AND (p.squadra_casa_id = ? OR p.squadra_ospite_id = ?)";
    $params  = [$torneo_id, $squadra_id, $squadra_id];
    $types   = 'iii';
}
$sql .= " ORDER BY p.orario ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Genera iCal ───────────────────────────────────────────────────
function ical_escape(string $s): string {
    $s = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $s);
    // Fold lines > 75 chars (RFC 5545)
    $chunks = [];
    while (mb_strlen($s) > 75) {
        $chunks[] = mb_substr($s, 0, 75);
        $s = ' ' . mb_substr($s, 75);
    }
    $chunks[] = $s;
    return implode("\r\n", $chunks);
}

function ical_dt(string $mysql_dt): string {
    // Converte "2026-06-15 20:00:00" → "20260615T200000"
    return str_replace(['-', ' ', ':'], ['', 'T', ''], $mysql_dt);
}

$nome_file = 'matchora_' . preg_replace('/[^a-z0-9]/i', '_', $torneo['nome']) . '.ics';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_file . '"');
header('Cache-Control: no-cache, no-store');

$output = "BEGIN:VCALENDAR\r\n";
$output .= "VERSION:2.0\r\n";
$output .= "PRODID:-//Matchora Tornei//IT\r\n";
$output .= "CALSCALE:GREGORIAN\r\n";
$output .= "METHOD:PUBLISH\r\n";
$output .= "X-WR-CALNAME:" . ical_escape($torneo['nome']) . "\r\n";
$output .= "X-WR-TIMEZONE:Europe/Rome\r\n";
$output .= "BEGIN:VTIMEZONE\r\n";
$output .= "TZID:Europe/Rome\r\n";
$output .= "BEGIN:STANDARD\r\n";
$output .= "TZOFFSETFROM:+0200\r\n";
$output .= "TZOFFSETTO:+0100\r\n";
$output .= "TZNAME:CET\r\n";
$output .= "DTSTART:19701025T030000\r\n";
$output .= "END:STANDARD\r\n";
$output .= "BEGIN:DAYLIGHT\r\n";
$output .= "TZOFFSETFROM:+0100\r\n";
$output .= "TZOFFSETTO:+0200\r\n";
$output .= "TZNAME:CEST\r\n";
$output .= "DTSTART:19700329T020000\r\n";
$output .= "END:DAYLIGHT\r\n";
$output .= "END:VTIMEZONE\r\n";

foreach ($partite as $p) {
    $dt_start = ical_dt($p['orario']);
    // Durata stimata 90 min
    $ts_end   = strtotime($p['orario']) + 90 * 60;
    $dt_end   = date('Ymd\THis', $ts_end);

    $fase = $p['girone'] !== null
        ? 'Girone ' . $p['girone']
        : ucfirst($p['turno'] ?? '');

    $summary = ical_escape($p['nome_casa'] . ' vs ' . $p['nome_ospite']);
    $desc    = ical_escape($torneo['nome'] . ' — ' . $fase);
    if ($p['stato'] === 'terminata') {
        $desc .= ical_escape(' | Risultato: ' . $p['punti_casa'] . '-' . $p['punti_ospite']);
    }
    $location = ical_escape($torneo['luogo']);
    $uid      = 'matchora-partita-' . $p['id'] . '@matchoratorneo.netsons.org';

    $output .= "BEGIN:VEVENT\r\n";
    $output .= "UID:$uid\r\n";
    $output .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $output .= "DTSTART;TZID=Europe/Rome:$dt_start\r\n";
    $output .= "DTEND;TZID=Europe/Rome:$dt_end\r\n";
    $output .= "SUMMARY:$summary\r\n";
    $output .= "DESCRIPTION:$desc\r\n";
    $output .= "LOCATION:$location\r\n";
    $output .= "STATUS:" . ($p['stato'] === 'terminata' ? 'CONFIRMED' : 'TENTATIVE') . "\r\n";
    $output .= "END:VEVENT\r\n";
}

$output .= "END:VCALENDAR\r\n";
echo $output;
