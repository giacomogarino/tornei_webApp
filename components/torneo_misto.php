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

/* =====================================================
   CARICA TORNEO
===================================================== */

$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

$isOrganizzatore = isset($_SESSION['id_utente']) &&
                   $_SESSION['id_utente'] == $torneo['creato_da'];

$formato = $torneo['formato']; // 'eliminazione_diretta' | 'gironi_playoff' | 'girone_unico'

/* =====================================================
   FUNZIONI DI SUPPORTO
===================================================== */

function prossimoTurno($turno){
    return match($turno) {
        'ottavi'     => 'quarti',
        'quarti'     => 'semifinale',
        'semifinale' => 'finale',
        default      => null
    };
}

function turnoInizialePerN($n){
    if ($n <= 2)  return 'finale';
    if ($n <= 4)  return 'semifinale';
    if ($n <= 8)  return 'quarti';
    return 'ottavi';
}

/* =====================================================
   FUNZIONE: GENERA TURNO SUCCESSIVO (eliminazione diretta)
===================================================== */

function generaTurnoSuccessivo($conn, $torneo_id, $turno){
    $next = prossimoTurno($turno);
    if(!$next) return;

    $stmt = $conn->prepare("
        SELECT CASE WHEN punti_casa > punti_ospite THEN squadra_casa_id ELSE squadra_ospite_id END AS vincitore
        FROM partita
        WHERE torneo_id = ? AND turno = ? AND stato = 'terminata' AND girone IS NULL
    ");
    $stmt->bind_param("is", $torneo_id, $turno);
    $stmt->execute();
    $res = $stmt->get_result();

    $vincitori = [];
    while($r = $res->fetch_assoc()) $vincitori[] = $r['vincitore'];

    if(count($vincitori) < 2) return;
    shuffle($vincitori);

    for($i = 0; $i + 1 < count($vincitori); $i += 2){
        $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $torneo_id, $vincitori[$i], $vincitori[$i+1], $next);
        $stmt->execute();
    }
}

/* =====================================================
   FUNZIONE: CALCOLA GIRONI
===================================================== */

function calcolaGironi($squadre){
    $n = count($squadre);
    $numGironi = 1;
    for($g = 2; $g <= $n; $g++){
        $dim = ceil($n / $g);
        if($dim >= 3 && $dim <= 6){
            $numGironi = $g;
            break;
        }
    }
    if($n <= 6) $numGironi = 1;

    shuffle($squadre);
    $gironi = array_fill(0, $numGironi, []);
    foreach($squadre as $i => $s)
        $gironi[$i % $numGironi][] = $s;

    return $gironi;
}

/* =====================================================
   FUNZIONE: GENERA GIRONI
===================================================== */

function generaGironi($conn, $torneo_id){
    $res = $conn->query("SELECT id FROM squadra WHERE torneo_id = $torneo_id AND stato='approvata'");
    $squadre = [];
    while($r = $res->fetch_assoc()) $squadre[] = $r['id'];

    if(count($squadre) < 2) return;

    $stmt = $conn->prepare("SELECT tipo_partita FROM torneo WHERE id = ?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $tipo = $stmt->get_result()->fetch_assoc()['tipo_partita'];

    $gironi = calcolaGironi($squadre);

    foreach($gironi as $numGirone => $squadreGirone){
        $g   = $numGirone + 1;
        $sq  = $squadreGirone;
        $tot = count($sq);

        $partite = [];
        for($i = 0; $i < $tot; $i++){
            for($j = $i + 1; $j < $tot; $j++){
                $partite[] = [$sq[$i], $sq[$j]];
                if($tipo === 'andata_ritorno')
                    $partite[] = [$sq[$j], $sq[$i]];
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

/* =====================================================
   FUNZIONE: CLASSIFICA GIRONE
===================================================== */

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
            'id'  => $sq['id'],
            'nome'=> $sq['nome'],
            'G'   => 0, 'V'   => 0, 'P' => 0, 'S'  => 0,
            'PF'  => 0, 'PS'  => 0, 'DP'=> 0, 'Pts'=> 0
        ];
    }

    foreach($partite as $p){
        $c  = $p['squadra_casa_id'];
        $o  = $p['squadra_ospite_id'];
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

    foreach($classifica as &$sq)
        $sq['DP'] = $sq['PF'] - $sq['PS'];

    usort($classifica, fn($a, $b) =>
        $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
    );

    return $classifica;
}

/* =====================================================
   FUNZIONE: CALCOLA PARAMETRI PLAYOFF
   Restituisce [perGirone, extras, targetTot]
   - perGirone: quante squadre avanzano di diritto per girone
   - extras:    quante migliori terze (o N+1) servono
   - targetTot: totale squadre nel tabellone (potenza di 2)
===================================================== */

function calcolaParametriPlayoff($numGironi){
    // Punto di partenza: 2 per girone
    $target = 4;
    while($target < $numGironi * 2) $target *= 2;

    // Se il target supera 16 (ottavi) lo limitiamo a 16
    if($target > 16) $target = 16;

    $perGirone = (int)floor($target / $numGironi);
    $extras    = $target - ($perGirone * $numGironi);

    return [$perGirone, $extras, $target];
}

/* =====================================================
   FUNZIONE: GENERA PLAYOFF (con migliori terze)
===================================================== */

function generaPlayoff($conn, $torneo_id){
    $res = $conn->query("SELECT MAX(girone) as mg FROM partita WHERE torneo_id = $torneo_id AND girone IS NOT NULL");
    $numGironi = (int)$res->fetch_assoc()['mg'];

    if($numGironi < 1) return;

    // Tutte le partite di girone devono essere terminate
    $stmt = $conn->prepare("
        SELECT COUNT(*) as mancanti FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato != 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    if($stmt->get_result()->fetch_assoc()['mancanti'] > 0) return;

    // Playoff già generato?
    $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND girone IS NULL");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    if($stmt->get_result()->fetch_assoc()['tot'] > 0) return;

    [$perGirone, $extras, $target] = calcolaParametriPlayoff($numGironi);

    $qualificate    = [];
    $candidateExtra = []; // raccoglie la prima squadra NON qualificata di diritto per ogni girone

    for($g = 1; $g <= $numGironi; $g++){
        $cls = classificaGirone($conn, $torneo_id, $g);

        // Qualificate di diritto
        for($pos = 0; $pos < min($perGirone, count($cls)); $pos++){
            $qualificate[] = $cls[$pos]['id'];
        }

        // Candidata extra (la prima non qualificata di diritto, es. la terza)
        if($extras > 0 && isset($cls[$perGirone])){
            $candidateExtra[] = $cls[$perGirone];
        }
    }

    // Ordina le candidate extra e prendi le migliori $extras
    if($extras > 0 && count($candidateExtra) > 0){
        usort($candidateExtra, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
        );
        for($i = 0; $i < min($extras, count($candidateExtra)); $i++){
            $qualificate[] = $candidateExtra[$i]['id'];
        }
    }

    shuffle($qualificate);
    $turno = turnoInizialePerN(count($qualificate));

    for($i = 0; $i + 1 < count($qualificate); $i += 2){
        $stmt = $conn->prepare("INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $torneo_id, $qualificate[$i], $qualificate[$i+1], $turno);
        $stmt->execute();
    }
}

/* =====================================================
   GENERAZIONE AUTOMATICA GIRONI ALL'AVVIO
===================================================== */

if($torneo['stato'] === 'in_corso'){
    $res = $conn->query("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = $torneo_id");
    $tot = $res->fetch_assoc()['tot'];

    if($tot == 0 && $formato === 'gironi_playoff'){
        generaGironi($conn, $torneo_id);
    }
}

/* =====================================================
   GESTIONE POST (inserimento risultati e orari)
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore){

    // --- SALVATAGGIO ORARIO ---
    if(isset($_POST['partita_id_orario'])){
        $partita_id = (int)$_POST['partita_id_orario'];
        $orario     = $_POST['orario'];

        $stmt = $conn->prepare("SELECT girone FROM partita WHERE id = ?");
        $stmt->bind_param("i", $partita_id);
        $stmt->execute();
        $infoOr = $stmt->get_result()->fetch_assoc();
        $redirectView = ($infoOr['girone'] !== null) ? 'gironi' : 'partite';

        if(empty($orario)){
            header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView&msg=errOrario");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET orario = ? WHERE id = ?");
        $stmt->bind_param("si", $orario, $partita_id);
        $stmt->execute();

        header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView");
        exit;
    }

    // --- INSERIMENTO RISULTATO ---
    if(isset($_POST['partita_id'])){
        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

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

        // Pareggio vietato in eliminazione diretta (partite senza girone, solo andata)
        if($info['girone'] === null && $torneo['tipo_partita'] === 'andata' && $casa == $ospite){
            header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView&msg=errRisultato");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET punti_casa = ?, punti_ospite = ?, stato = 'terminata' WHERE id = ?");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

        if($info['girone'] !== null){
            // Partita di girone → prova a generare playoff
            generaPlayoff($conn, $torneo_id);
        }else{
            // Partita di eliminazione diretta
            $turno = $info['turno'];
            $stmt  = $conn->prepare("
                SELECT COUNT(*) as mancanti FROM partita
                WHERE torneo_id = ? AND turno = ? AND stato != 'terminata' AND girone IS NULL
            ");
            $stmt->bind_param("is", $torneo_id, $turno);
            $stmt->execute();
            $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];

            if($mancanti == 0){
                if($turno === 'finale'){
                    $stmt = $conn->prepare("UPDATE torneo SET stato = 'completato' WHERE id = ?");
                    $stmt->bind_param("i", $torneo_id);
                    $stmt->execute();
                }else{
                    generaTurnoSuccessivo($conn, $torneo_id, $turno);
                }
            }
        }

        header("Location: struttura_torneo.php?id=$torneo_id&view=$redirectView");
        exit;
    }
}

/* =====================================================
   DATI PER LA VISUALIZZAZIONE
===================================================== */

$playoffGenerato = false;
if($formato === 'gironi_playoff'){
    $stmt = $conn->prepare("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = ? AND girone IS NULL");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $playoffGenerato = $stmt->get_result()->fetch_assoc()['tot'] > 0;
}

$numGironi = 0;
if($formato === 'gironi_playoff'){
    $res = $conn->query("SELECT MAX(girone) as mg FROM partita WHERE torneo_id = $torneo_id AND girone IS NOT NULL");
    $numGironi = (int)($res->fetch_assoc()['mg'] ?? 0);
}

// Parametri playoff (usati sia per generazione che per display "qualificata")
$perGirone = 0;
$extras    = 0;
if($numGironi > 0){
    [$perGirone, $extras,] = calcolaParametriPlayoff($numGironi);
}

require_once('templates/header.php');
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Struttura torneo</title>
</head>
<body>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>

<?php if($formato === 'gironi_playoff'): ?>
    <a href="?id=<?= $torneo_id ?>&view=gironi">Gironi</a> |
    <?php if($playoffGenerato): ?>
        <a href="?id=<?= $torneo_id ?>&view=partite">Tabellone Playoff</a> |
    <?php endif; ?>
    <a href="?id=<?= $torneo_id ?>&view=classifica">Classifica generale</a>
<?php endif; ?>

<hr>

<?php

/* =====================================================
   VIEW: CLASSIFICA GENERALE (gironi_playoff)
===================================================== */

if($view === 'classifica'):

?>

<h3>Classifica generale (per gironi)</h3>

<?php for($g = 1; $g <= $numGironi; $g++):
    $cls = classificaGirone($conn, $torneo_id, $g);

    // Determina quali posizioni sono "garantite" e quali "extra/terze candidate"
    // Le migliori $extras terze tra tutti i gironi vengono calcolate globalmente,
    // quindi qui mostriamo solo chi è qualificato di diritto e chi è candidato extra.
    // Per sapere se una terza specifica è effettivamente qualificata, serve il confronto globale.
    
    // Raccoglie le terze candidate di tutti i gironi per determinare le qualificate extra
    if($g === 1){
        $tutteLeTerze = [];
        for($gg = 1; $gg <= $numGironi; $gg++){
            $tmpCls = classificaGirone($conn, $torneo_id, $gg);
            if(isset($tmpCls[$perGirone])){
                $tmpCls[$perGirone]['girone_origine'] = $gg;
                $tutteLeTerze[] = $tmpCls[$perGirone];
            }
        }
        usort($tutteLeTerze, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
        );
        $idQualificateExtra = [];
        for($ei = 0; $ei < min($extras, count($tutteLeTerze)); $ei++){
            $idQualificateExtra[] = $tutteLeTerze[$ei]['id'];
        }
    }
?>

<h4>Girone <?= $g ?></h4>
<table border="1">
<tr>
    <th>#</th><th>Squadra</th><th>G</th><th>V</th><th>P</th><th>S</th>
    <th>PF</th><th>PS</th><th>DP</th><th>Pts</th>
    <?php if($playoffGenerato): ?><th>Stato</th><?php endif; ?>
</tr>
<?php foreach($cls as $pos => $sq): ?>
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
    <?php if($playoffGenerato): ?>
    <td>
        <?php
        if($pos < $perGirone){
            echo '✅ Qualificata';
        }elseif($pos === $perGirone && in_array($sq['id'], $idQualificateExtra)){
            echo '✅ Qualificata (miglior terza)';
        }
        ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>

<?php endfor; ?>

<?php

/* =====================================================
   VIEW: GIRONI (partite + classifica)
===================================================== */

elseif($view === 'gironi'):

?>

<?php if($numGironi === 0): ?>
    <p>Nessun girone generato. Il torneo non è ancora iniziato.</p>

<?php else:

    // Calcola le qualificate extra globalmente (una sola volta)
    $tutteLeTerzeGironi = [];
    for($gg = 1; $gg <= $numGironi; $gg++){
        $tmpCls = classificaGirone($conn, $torneo_id, $gg);
        if(isset($tmpCls[$perGirone])){
            $tmpCls[$perGirone]['girone_origine'] = $gg;
            $tutteLeTerzeGironi[] = $tmpCls[$perGirone];
        }
    }
    usort($tutteLeTerzeGironi, fn($a, $b) =>
        $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
    );
    $idQualificateExtraGironi = [];
    for($ei = 0; $ei < min($extras, count($tutteLeTerzeGironi)); $ei++){
        $idQualificateExtraGironi[] = $tutteLeTerzeGironi[$ei]['id'];
    }

    for($g = 1; $g <= $numGironi; $g++):
        $cls = classificaGirone($conn, $torneo_id, $g);

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
    <?php if($playoffGenerato): ?><th>Stato</th><?php endif; ?>
</tr>
<?php foreach($cls as $pos => $sq): ?>
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
    <?php if($playoffGenerato): ?>
    <td>
        <?php
        if($pos < $perGirone){
            echo '✅ Qualificata';
        }elseif($pos === $perGirone && in_array($sq['id'], $idQualificateExtraGironi)){
            echo '✅ Qualificata (miglior terza)';
        }
        ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>

<h4>Partite</h4>
<table border="1">
<tr>
    <th>Casa</th><th>Ospite</th><th>Turno</th><th>Orario</th><th>Risultato</th>
    <?php if($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
</tr>
<?php foreach($partite as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['casa']) ?></td>
    <td><?= htmlspecialchars($row['ospite']) ?></td>
    <td>Girone <?= $g ?></td>
    <td><?= $row['orario'] ?? 'non impostato' ?></td>
    <td><?= $row['punti_casa'] ?? '-' ?> - <?= $row['punti_ospite'] ?? '-' ?></td>
    <?php if($isOrganizzatore): ?>
    <td>
        <?php if($row['stato'] !== 'terminata'): ?>
        <form method="POST" style="margin-bottom:10px;">
            <input type="hidden" name="partita_id_orario" value="<?= $row['id'] ?>">
            <input type="datetime-local" name="orario" required>
            <button>Salva orario</button>
        </form>
        <form method="POST">
            <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
            <input type="number" name="casa"   min="0" required style="width:50px;">
            <input type="number" name="ospite" min="0" required style="width:50px;">
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

<?php if(!$playoffGenerato): ?>
    <p><em>Il tabellone playoff verrà generato automaticamente al termine di tutti i gironi.</em></p>
    <?php if($numGironi > 0 && $extras > 0): ?>
        <p><em>Nota: avanzeranno <?= $perGirone ?> squadre per girone + le migliori <?= $extras ?> terze classificate.</em></p>
    <?php endif; ?>
<?php else: ?>
    <p>✅ Fase a gironi completata. <a href="?id=<?= $torneo_id ?>&view=partite">Vai al tabellone playoff →</a></p>
<?php endif; ?>

<?php

/* =====================================================
   VIEW: TABELLONE PLAYOFF
===================================================== */

else:

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
while($row = $result->fetch_assoc())
    $partitePerTurno[$row['turno']][] = $row;

$ordineTurni = ['ottavi', 'quarti', 'semifinale', 'finale'];

?>

<?php if(empty($partitePerTurno)): ?>
    <p>Il tabellone playoff non è ancora stato generato.</p>
<?php else: ?>

<?php foreach($ordineTurni as $turno):
    if(!isset($partitePerTurno[$turno])) continue;
?>
<h3><?= ucfirst($turno) ?></h3>
<table border="1">
<tr>
    <th>Casa</th><th>Ospite</th><th>Orario</th><th>Risultato</th>
    <?php if($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
</tr>
<?php foreach($partitePerTurno[$turno] as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['casa']) ?></td>
    <td><?= htmlspecialchars($row['ospite']) ?></td>
    <td><?= $row['orario'] ?? 'non impostato' ?></td>
    <td><?= $row['punti_casa'] ?? '-' ?> - <?= $row['punti_ospite'] ?? '-' ?></td>
    <?php if($isOrganizzatore): ?>
    <td>
        <?php if($row['stato'] !== 'terminata'): ?>
        <form method="POST" style="margin-bottom:10px;">
            <input type="hidden" name="partita_id_orario" value="<?= $row['id'] ?>">
            <input type="datetime-local" name="orario" required>
            <button>Salva orario</button>
        </form>
        <form method="POST">
            <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
            <input type="number" name="casa"   min="0" required style="width:50px;">
            <input type="number" name="ospite" min="0" required style="width:50px;">
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

<?php endif; ?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'errRisultato'): ?>
        <div style="color:red">Errore: le squadre non possono pareggiare in eliminazione diretta.</div>
    <?php elseif($_GET['msg'] === 'errPunti'): ?>
        <div style="color:red">Errore: i valori negativi non sono validi.</div>
    <?php elseif($_GET['msg'] === 'errOrario'): ?>
        <div style="color:red">Errore: inserisci un orario valido.</div>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>

<?php require_once('templates/footer.php'); ?>