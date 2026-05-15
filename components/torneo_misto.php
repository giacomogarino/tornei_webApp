<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();
include_once("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;
$view = $_GET['view'] ?? 'classifica';

if(!$torneo_id){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

# PRENDO TORNEO
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo_id){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

$isOrganizzatore = isset($_SESSION['id_utente']) &&
                   $_SESSION['id_utente'] == $torneo['creato_da'];

$formato = $torneo['formato']; // 'eliminazione_diretta' | 'gironi_playoff' | 'girone_unico'

/* =====================================================
   FUNZIONI COMUNI
===================================================== */

function prossimoTurno($turno){
    return match($turno) {
        'ottavi' => 'quarti',
        'quarti' => 'semifinale',
        'semifinale' => 'finale',
        default => null
    };
}

// Dato il numero di squadre nei playoff, restituisce il turno iniziale
function turnoInizialePerN($n){
    if ($n <= 2)  return 'finale';
    if ($n <= 4)  return 'semifinale';
    if ($n <= 8)  return 'quarti';
    return 'ottavi';
}

/* =====================================================
   FUNZIONI
===================================================== */

function generaTurnoSuccessivo($conn, $torneo_id, $turno) {
    $next = prossimoTurno($turno);
    if (!$next) return;

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

    if (count($vincitori) < 2) return;
    shuffle($vincitori);

    for ($i = 0; $i + 1 < count($vincitori); $i += 2) {
        $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $torneo_id, $vincitori[$i], $vincitori[$i+1], $next);
        $stmt->execute();
    }
}

/*
 Divide N squadre in gironi il più possibile uguali.
 Restituisce array di array di squadra_id.

 Logica:
   - Numero di gironi = massimo divisore di N che porta a gironi di 3-6 squadre
   - Se non esiste divisore perfetto, usa ceil() e gestisce il girone corto
*/
function calcolaGironi($squadre){
    $n = count($squadre);

    // Gironi da 3 a 6 squadre
    $numGironi = 1;
    for($g = 2; $g <= $n; $g++){
        $dim = ceil($n / $g);
        if ($dim >= 3 && $dim <= 6){
            $numGironi = $g;
            break;
        }
    }
    // Se n <= 6 un girone unico va bene (sarà gestito come girone_unico)
    if ($n <= 6) $numGironi = 1;

    shuffle($squadre);
    $gironi = array_fill(0, $numGironi, []);
    foreach ($squadre as $i => $s)
        $gironi[$i % $numGironi][] = $s;
    
    return $gironi;
}

/*
 Dato il numero di gironi, decide quante squadre avanzano per girone
 in modo che il totale sia una potenza di 2 (per il tabellone KO).
 Restituisce [squadre_per_girone_che_avanzano, totale_playoff]
*/
function squadreAvanzanoPerGirone($numGironi) {
    // Vogliamo trovare il k più piccolo tale che:
    //   k * numGironi sia una potenza di 2 E >= 4 (minimo quarti).
    // Preferenza: 8 (ottavi) > 4 (quarti) > 2 (semifinale solo se impossibile fare di meglio).
    for($k = 1; $k <= 16; $k++){
        $tot = $k * $numGironi;
        if($tot >= 4 && ($tot & ($tot - 1)) === 0)
            return [$k, $tot];
    }
    // Fallback arrotondiamo alla potenza di 2 >= 4 più vicina
    $pot = 4;
    while ($pot < $numGironi) $pot *= 2;
    $k = (int)ceil($pot / $numGironi);
    return [$k, $k * $numGironi];
}
/*
function generaGironi($conn, $torneo_id) {
    $res = $conn->query("SELECT id FROM squadra WHERE torneo_id = $torneo_id AND stato='approvata'");
    $squadre = [];
    while ($r = $res->fetch_assoc()) $squadre[] = $r['id'];

    if(count($squadre) < 2) return;

    $gironi = calcolaGironi($squadre);

    foreach($gironi as $numGirone => $squadreGirone){
        $g = $numGirone + 1;
        $sq = $squadreGirone;
        $tot = count($sq);

        // Genera tutte le coppie
        $partite = [];
        for($i = 0; $i < $tot; $i++){
            for ($j = $i + 1; $j < $tot; $j++)
                $partite[] = [$sq[$i], $sq[$j]];
        }

        // Mischia le partite così nessuna squadra gioca N volte di fila
        shuffle($partite);

        // Inserisci in ordine casuale
        foreach($partite as [$casa, $ospite]){
            $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, girone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $torneo_id, $casa, $ospite, $g);
            $stmt->execute();
        }
    }
}
*/
function generaGironi($conn, $torneo_id) {
    $res = $conn->query("SELECT id FROM squadra WHERE torneo_id = $torneo_id AND stato='approvata'");
    $squadre = [];
    while ($r = $res->fetch_assoc()) $squadre[] = $r['id'];

    if(count($squadre) < 2) return;

    // Recupera tipo_partita del torneo
    $stmt = $conn->prepare("SELECT tipo_partita FROM torneo WHERE id = ?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $tipo = $stmt->get_result()->fetch_assoc()['tipo_partita'];

    $gironi = calcolaGironi($squadre);

    foreach($gironi as $numGirone => $squadreGirone){
        $g = $numGirone + 1;
        $sq = $squadreGirone;
        $tot = count($sq);

        $partite = [];
        for($i = 0; $i < $tot; $i++){
            for($j = $i + 1; $j < $tot; $j++){
                $partite[] = [$sq[$i], $sq[$j]]; // andata
                if($tipo === 'andata_ritorno')
                    $partite[] = [$sq[$j], $sq[$i]]; // ritorno (casa/ospite invertiti)
            }
        }

        shuffle($partite);

        foreach($partite as [$casa, $ospite]){
            $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, girone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $torneo_id, $casa, $ospite, $g);
            $stmt->execute();
        }
    }
}

/**
 * Calcola la classifica di un girone basandosi sulle partite terminate.
 * Restituisce array ordinato per punti DESC, differenza reti DESC.
 */
function classificaGirone($conn, $torneo_id, $girone){
    $stmt = $conn->prepare("
        SELECT p.*, sc.nome AS nome_casa, so.nome AS nome_ospite
        FROM partita p
        JOIN squadra sc ON p.squadra_casa_id = sc.id
        JOIN squadra so ON p.squadra_ospite_id = so.id
        WHERE p.torneo_id = ? AND p.girone = ? AND p.stato = 'terminata'
    ");
    $stmt->bind_param("ii", $torneo_id, $girone);
    $stmt->execute();
    $partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Raccogli tutte le squadre del girone
    $stmt2 = $conn->prepare("
        SELECT DISTINCT s.id, s.nome FROM squadra s
        JOIN partita p ON (p.squadra_casa_id = s.id OR p.squadra_ospite_id = s.id)
        WHERE p.torneo_id = ? AND p.girone = ?
    ");
    $stmt2->bind_param("ii", $torneo_id, $girone);
    $stmt2->execute();
    $squadreRaw = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    $classifica = [];
    foreach($squadreRaw as $sq){
        $classifica[$sq['id']] = [
            'id' => $sq['id'],
            'nome' => $sq['nome'],
            'G' => 0, 'V' => 0, 'P' => 0, 'S' => 0,
            'PF' => 0, 'PS' => 0, 'DP' => 0, 'Pts' => 0
        ];
    }

    foreach ($partite as $p) {
        $c = $p['squadra_casa_id'];
        $o = $p['squadra_ospite_id'];
        $pc = $p['punti_casa'];
        $po = $p['punti_ospite'];

        $classifica[$c]['G']++;
        $classifica[$o]['G']++;
        $classifica[$c]['PF'] += $pc;
        $classifica[$c]['PS'] += $po;
        $classifica[$o]['PF'] += $po;
        $classifica[$o]['PS'] += $pc;

        if($pc > $po){
            $classifica[$c]['V']++; 
            $classifica[$c]['Pts'] += 3;
            $classifica[$o]['S']++;
        }elseif($pc < $po){
            $classifica[$o]['V']++; 
            $classifica[$o]['Pts'] += 3;
            $classifica[$c]['S']++;
        }else{
            $classifica[$c]['P']++; 
            $classifica[$c]['Pts']++;
            $classifica[$o]['P']++; 
            $classifica[$o]['Pts']++;
        }
    }

    foreach ($classifica as &$sq)
        $sq['DP'] = $sq['PF'] - $sq['PS'];

    usort($classifica, fn($a, $b) =>
        $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
    );

    return $classifica;
}

/**
 * Controlla se tutti i gironi sono finiti e genera il tabellone playoff.
 */
function generaPlayoff($conn, $torneo_id){
    $res = $conn->query("SELECT MAX(girone) as mg FROM partita WHERE torneo_id = $torneo_id AND girone IS NOT NULL");
    $numGironi = (int)$res->fetch_assoc()['mg'];

    if ($numGironi < 1) return;

    // Controlla che tutti i gironi abbiano finito tutte le partite
    $stmt = $conn->prepare("
        SELECT COUNT(*) as mancanti FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato != 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];
    if ($mancanti > 0) return;

    // Controlla che il playoff non sia già stato generato
    $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND girone IS NULL");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['tot'] > 0) return;

    // Determina quante squadre avanzano per girone
    [$kPerGirone, $totPlayoff] = squadreAvanzanoPerGirone($numGironi);

    // Prendi i top-k di ogni girone
    $qualificate = [];
    for($g = 1; $g <= $numGironi; $g++){
        $cls = classificaGirone($conn, $torneo_id, $g);
        for ($pos = 0; $pos < min($kPerGirone, count($cls)); $pos++){
            $qualificate[] = $cls[$pos]['id'];
        }
    }

    // Se il totale non è potenza di 2 esatta, taglia o integra (non dovrebbe succedere con la logica sopra)
    // Shuffle per evitare che gironi vicini si incontrino subito
    shuffle($qualificate);

    $turno = turnoInizialePerN(count($qualificate));

    for($i = 0; $i + 1 < count($qualificate); $i += 2){
        $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $torneo_id, $qualificate[$i], $qualificate[$i+1], $turno);
        $stmt->execute();
    }
}

/* =====================================================
   GENERAZIONE AUTOMATICA ALL'AVVIO
===================================================== */

if($torneo['stato'] === 'in_corso'){
    $res = $conn->query("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = $torneo_id");
    $tot = $res->fetch_assoc()['tot'];

    if($tot == 0){
        if ($formato === 'gironi_playoff')
            generaGironi($conn, $torneo_id);
    }
}

/* =====================================================
   INSERIMENTO RISULTATO (POST)
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore){

    // SALVATAGGIO ORARIO
    if(isset($_POST['partita_id_orario'])){

        $partita_id = (int)$_POST['partita_id_orario'];
        $orario = $_POST['orario'];

        if(empty($orario)){
            // Prendo info girone per redirect corretto
            $stmt = $conn->prepare("SELECT girone FROM partita WHERE id = ?");
            $stmt->bind_param("i", $partita_id);
            $stmt->execute();
            $infoOr = $stmt->get_result()->fetch_assoc();
            $redirectView = ($infoOr['girone'] !== null) ? 'gironi' : 'partite';
            header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView&msg=errOrario");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET orario = ? WHERE id = ?");
        $stmt->bind_param("si", $orario, $partita_id);
        $stmt->execute();

        // Prendo info girone per redirect corretto
        $stmt = $conn->prepare("SELECT girone FROM partita WHERE id = ?");
        $stmt->bind_param("i", $partita_id);
        $stmt->execute();
        $infoOr = $stmt->get_result()->fetch_assoc();
        $redirectView = ($infoOr['girone'] !== null) ? 'gironi' : 'partite';
        header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView");
        exit;
    }

    // INSERIMENTO RISULTATO
    if(isset($_POST['partita_id'])){

        $partita_id = (int)$_POST['partita_id'];
        $casa = (int)$_POST['casa'];
        $ospite = (int)$_POST['ospite'];

        // Prendo info partita prima di tutto
        $stmt = $conn->prepare("SELECT turno, girone FROM partita WHERE id = ?");
        $stmt->bind_param("i", $partita_id);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $redirectView = ($info['girone'] !== null) ? 'gironi' : 'partite';

        // Valori negativi
        if($casa < 0 || $ospite < 0){
            header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView&msg=errPunti");
            exit;
        }

        // Pareggio vietato in eliminazione diretta (partite senza girone, tipo andata)
        if($info['girone'] === null && $torneo['tipo_partita'] === 'andata' && $casa == $ospite){
            header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView&msg=errRisultato");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET punti_casa = ?, punti_ospite = ?, stato='terminata' WHERE id = ?");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

        if($info['girone'] !== null){
            // Partita di girone: prova a generare playoff se tutti i gironi sono finiti
            generaPlayoff($conn, $torneo_id);
        }else{
            // Partita di eliminazione diretta
            $turno = $info['turno'];
            $stmt = $conn->prepare("
                SELECT COUNT(*) as mancanti FROM partita
                WHERE torneo_id = ? AND turno = ? AND stato != 'terminata' AND girone IS NULL
            ");
            $stmt->bind_param("is", $torneo_id, $turno);
            $stmt->execute();
            $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];

            if($mancanti == 0){
                if ($turno === 'finale') {
                    $stmt = $conn->prepare("UPDATE torneo SET stato = 'completato' WHERE id = ?");
                    $stmt->bind_param("i", $torneo_id);
                    $stmt->execute();
                } else {
                    generaTurnoSuccessivo($conn, $torneo_id, $turno);
                }
            }
        }

        header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView");
        exit;
    }
}

/* =====================================================
   DATI PER VISUALIZZAZIONE
===================================================== */

// Controlla se esiste già la fase playoff (per gironi_playoff)
$playoffGenerato = false;
if($formato === 'gironi_playoff'){
    $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND girone IS NULL");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $playoffGenerato = $stmt->get_result()->fetch_assoc()['tot'] > 0;
}

// Numero di gironi
$numGironi = 0;
if(in_array($formato, ['gironi_playoff'])){
    $res = $conn->query("SELECT MAX(girone) as mg FROM partita WHERE torneo_id = $torneo_id AND girone IS NOT NULL");
    $numGironi = (int)($res->fetch_assoc()['mg'] ?? 0);
}

require_once('templates/header.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struttura torneo</title>
</head>
<body>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>

<?php if ($formato === 'gironi_playoff'): ?>
    <a href="?id=<?= $torneo_id ?>&view=gironi">Gironi</a> |
    <?php if ($playoffGenerato): ?>
        <a href="?id=<?= $torneo_id ?>&view=partite">Tabellone Playoff</a> |
    <?php endif; ?>
    <a href="?id=<?= $torneo_id ?>&view=classifica">Classifica generale</a>
<?php endif; ?>

<hr>

<?php
/* =====================================================
   VIEW: CLASSIFICA GENERALE gironi_playoff
===================================================== */
if ($view === 'classifica'):
?>

<h3>Classifica generale (per gironi)</h3>

<?php for ($g = 1; $g <= $numGironi; $g++):
    $cls = classificaGirone($conn, $torneo_id, $g);
    [$kPerGirone,] = squadreAvanzanoPerGirone($numGironi);
?>
<h4>Girone <?= $g ?></h4>
<table border="1">
<tr>
    <th>#</th><th>Squadra</th><th>G</th><th>V</th><th>P</th><th>S</th>
    <th>GF</th><th>GS</th><th>DR</th><th>Pts</th>
    <?php if ($playoffGenerato): ?><th>Stato</th><?php endif; ?>
</tr>
<?php foreach ($cls as $pos => $sq): ?>
<tr>
    <td><?= $pos + 1 ?></td>
    <td><?= htmlspecialchars($sq['nome']) ?></td>
    <td><?= $sq['G'] ?></td>
    <td><?= $sq['V'] ?></td>
    <td><?= $sq['P'] ?></td>
    <td><?= $sq['S'] ?></td>
    <td><?= $sq['PF'] ?></td>
    <td><?= $sq['PS'] ?></td>
    <td><?= $sq['DP'] ?></td>
    <td><?= $sq['Pts'] ?></td>
    <?php if ($playoffGenerato): ?>
        <td><?= ($pos < $kPerGirone) ? '✅ Qualificata' : '' ?></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php endfor; ?>

<?php
/* =====================================================
   VIEW: GIRONI (partite + classifica)
===================================================== */
elseif ($view === 'gironi'):
?>

<?php if ($numGironi === 0): ?>
    <p>Nessun girone generato. Il torneo non è ancora iniziato.</p>

<?php else:
    for ($g = 1; $g <= $numGironi; $g++):
        $cls = classificaGirone($conn, $torneo_id, $g);
        [$kPerGirone,] = squadreAvanzanoPerGirone($numGironi);

        $stmt = $conn->prepare("
            SELECT p.*, sc.nome AS casa, so.nome AS ospite
            FROM partita p
            JOIN squadra sc ON p.squadra_casa_id = sc.id
            JOIN squadra so ON p.squadra_ospite_id = so.id
            WHERE p.torneo_id = ? AND p.girone = ?
            ORDER BY p.id
        ");
        $stmt->bind_param("ii", $torneo_id, $g);
        $stmt->execute();
        $partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <h3>Girone <?= $g ?></h3>

    <h4>Classifica</h4>
    <table border="1">
    <tr>
        <th>#</th><th>Squadra</th><th>G</th><th>V</th><th>P</th><th>S</th>
        <th>PF</th><th>PS</th><th>DP</th><th>Pts</th>
        <?php if ($playoffGenerato): ?><th>Stato</th><?php endif; ?>
    </tr>
    <?php foreach ($cls as $pos => $sq): ?>
    <tr>
        <td><?= $pos + 1 ?></td>
        <td><?= htmlspecialchars($sq['nome']) ?></td>
        <td><?= $sq['G'] ?></td>
        <td><?= $sq['V'] ?></td>
        <td><?= $sq['P'] ?></td>
        <td><?= $sq['S'] ?></td>
        <td><?= $sq['PF'] ?></td>
        <td><?= $sq['PS'] ?></td>
        <td><?= $sq['DP'] ?></td>
        <td><?= $sq['Pts'] ?></td>
        <?php if ($playoffGenerato): ?>
            <td><?= ($pos < $kPerGirone) ? '✅ Qualificata' : '' ?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>

    <h4>Partite</h4>
    <table border="1">
    <tr>
        <th>Casa</th><th>Ospite</th><th>Turno</th><th>Orario</th><th>Risultato</th>
        <?php if ($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
    </tr>
    <?php foreach ($partite as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['casa']) ?></td>
        <td><?= htmlspecialchars($row['ospite']) ?></td>
        <td>Girone <?= $g ?></td>
        <td><?= $row['orario'] ?? 'non impostato' ?></td>
        <td><?= $row['punti_casa'] ?? '-' ?> - <?= $row['punti_ospite'] ?? '-' ?></td>

        <?php if ($isOrganizzatore): ?>
        <td>
            <?php if ($row['stato'] !== 'terminata'): ?>
            <form method="POST" style="margin-bottom:10px;">
                <input type="hidden" name="partita_id_orario" value="<?= $row['id'] ?>">
                <input type="datetime-local" name="orario" required>
                <button>Salva orario</button>
            </form>
            <form method="POST">
                <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
                <input type="number" name="casa" required style="width:50px;">
                <input type="number" name="ospite" required style="width:50px;">
                <button>OK</button>
            </form>
            <?php else: ?>
                ✔
            <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>
    <hr>

<?php endfor; endif; ?>

<?php if (!$playoffGenerato): ?>
    <p><em>Il tabellone playoff verrà generato automaticamente al termine di tutti i gironi.</em></p>
<?php else: ?>
    <p>✅ Fase a gironi completata. <a href="?id=<?= $torneo_id ?>&view=partite">Vai al tabellone playoff →</a></p>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'errRisultato'): ?>
        <div>Errore: non possono pareggiare</div>
    <?php elseif ($_GET['msg'] === 'errPunti'): ?>
        <div>Errore: valori negativi non validi</div>
    <?php elseif ($_GET['msg'] === 'errOrario'): ?>
        <div>Errore: inserisci un orario valido</div>
    <?php endif; ?>
<?php endif; ?>

<?php
/* =====================================================
   VIEW: TABELLONE PLAYOFF
===================================================== */
else:
?>

<?php
$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS casa, so.nome AS ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.girone IS NULL
    ORDER BY FIELD(p.turno, 'ottavi', 'quarti', 'semifinale', 'finale'), p.id
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();

$partitePerTurno = [];
while ($row = $result->fetch_assoc()) {
    $partitePerTurno[$row['turno']][] = $row;
}

$ordineTurni = ['ottavi', 'quarti', 'semifinale', 'finale'];
?>

<?php if (empty($partitePerTurno)): ?>
    <p>Il tabellone playoff non è ancora stato generato.</p>
<?php else: ?>

<?php foreach ($ordineTurni as $turno):
    if (!isset($partitePerTurno[$turno])) continue;
?>
    <h3><?= ucfirst($turno) ?></h3>
    <table border="1">
    <tr>
        <th>Casa</th><th>Ospite</th><th>Orario</th><th>Risultato</th>
        <?php if ($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
    </tr>
    <?php foreach ($partitePerTurno[$turno] as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['casa']) ?></td>
        <td><?= htmlspecialchars($row['ospite']) ?></td>
        <td><?= $row['orario'] ?? 'non impostato' ?></td>
        <td><?= $row['punti_casa'] ?? '-' ?> - <?= $row['punti_ospite'] ?? '-' ?></td>

        <?php if ($isOrganizzatore): ?>
        <td>
            <?php if ($row['stato'] !== 'terminata'): ?>
            <form method="POST" style="margin-bottom:10px;">
                <input type="hidden" name="partita_id_orario" value="<?= $row['id'] ?>">
                <input type="datetime-local" name="orario" required>
                <button>Salva orario</button>
            </form>
            <form method="POST">
                <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
                <input type="number" name="casa" required style="width:50px;">
                <input type="number" name="ospite" required style="width:50px;">
                <button>OK</button>
            </form>
            <?php else: ?>
                ✔
            <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'errRisultato'): ?>
        <div>Errore: non possono pareggiare</div>
    <?php elseif ($_GET['msg'] === 'errPunti'): ?>
        <div>Errore: valori negativi non validi</div>
    <?php elseif ($_GET['msg'] === 'errOrario'): ?>
        <div>Errore: inserisci un orario valido</div>
    <?php endif; ?>
<?php endif; ?>

<?php endif; ?>

</body>
</html>

<?php require_once('templates/footer.php'); ?>