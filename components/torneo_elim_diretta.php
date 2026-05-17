<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

include_once("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;

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

$tipo_partita = $torneo['tipo_partita']; // 'andata' | 'andata_ritorno'

/* =====================================================
   FUNZIONI DI SUPPORTO
===================================================== */

function prossimoTurno($turno){
    return match($turno){
        'ottavi'     => 'quarti',
        'quarti'     => 'semifinale',
        'semifinale' => 'finale',
        default      => null
    };
}

function turnoIniziale($n){
    return match($n){
        2  => 'finale',
        4  => 'semifinale',
        8  => 'quarti',
        16 => 'ottavi',
        default => null
    };
}

function isPotenzaDiDue($n){
    return $n >= 2 && ($n & ($n - 1)) === 0;
}

/* =====================================================
   INSERISCE UNA COPPIA DI PARTITE (andata + eventuale ritorno)
===================================================== */

function inserisciCoppia($conn, $torneo_id, $casa, $ospite, $turno, $tipo_partita){
    $stmt = $conn->prepare("
        INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno, tipo)
        VALUES (?, ?, ?, ?, 'andata')
    ");
    $stmt->bind_param("iiis", $torneo_id, $casa, $ospite, $turno);
    $stmt->execute();

    if($tipo_partita === 'andata_ritorno'){
        $stmt = $conn->prepare("
            INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno, tipo)
            VALUES (?, ?, ?, ?, 'ritorno')
        ");
        $stmt->bind_param("iiis", $torneo_id, $ospite, $casa, $turno);
        $stmt->execute();
    }
}

/* =====================================================
   GENERA IL TURNO INIZIALE
   Richiede obbligatoriamente una potenza di 2 di squadre
===================================================== */

function generaIniziale($conn, $torneo_id, $tipo_partita){
    $res = $conn->query("
        SELECT id FROM squadra
        WHERE torneo_id = $torneo_id AND stato = 'approvata'
    ");
    $squadre = [];
    while($r = $res->fetch_assoc()) $squadre[] = $r['id'];

    $n = count($squadre);

    if($n < 2)
        return ['ok' => false, 'msg' => "Servono almeno 2 squadre approvate (trovate: $n)."];

    if(!isPotenzaDiDue($n))
        return ['ok' => false, 'msg' => "Il numero di squadre approvate ($n) deve essere una potenza di 2 (2, 4, 8 o 16)."];

    $turno = turnoIniziale($n);
    if(!$turno)
        return ['ok' => false, 'msg' => "Numero di squadre non supportato ($n)."];

    shuffle($squadre);

    for($i = 0; $i < $n; $i += 2)
        inserisciCoppia($conn, $torneo_id, $squadre[$i], $squadre[$i+1], $turno, $tipo_partita);

    return ['ok' => true];
}

/* =====================================================
   CONTROLLA SE UN TURNO È COMPLETAMENTE TERMINATO
===================================================== */

function turnoTerminato($conn, $torneo_id, $turno, $tipo_partita){
    if($tipo_partita === 'andata_ritorno'){
        // Aspetta che sia andata che ritorno di ogni coppia siano terminate
        $stmt = $conn->prepare("
            SELECT COUNT(*) as mancanti
            FROM partita a
            LEFT JOIN partita r
                ON  r.torneo_id         = a.torneo_id
                AND r.turno             = a.turno
                AND r.tipo              = 'ritorno'
                AND r.squadra_casa_id   = a.squadra_ospite_id
                AND r.squadra_ospite_id = a.squadra_casa_id
            WHERE a.torneo_id = ?
              AND a.turno     = ?
              AND a.tipo      = 'andata'
              AND (
                    a.stato != 'terminata'
                 OR r.id     IS NULL
                 OR r.stato  != 'terminata'
              )
        ");
    }else{
        $stmt = $conn->prepare("
            SELECT COUNT(*) as mancanti
            FROM partita
            WHERE torneo_id = ? AND turno = ? AND stato != 'terminata'
        ");
    }
    $stmt->bind_param("is", $torneo_id, $turno);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['mancanti'] == 0;
}

/* =====================================================
   CALCOLA I VINCITORI DI UN TURNO TERMINATO
   andata secca    → vince chi ha più punti nella singola partita
   andata_ritorno  → vince chi ha più punti aggregati;
                     parità aggregata: avanza la casa dell'andata
===================================================== */

function calcolaVincitori($conn, $torneo_id, $turno, $tipo_partita){
    if($tipo_partita === 'andata_ritorno'){
        $stmt = $conn->prepare("
            SELECT
                a.squadra_casa_id                 AS sq1,
                a.squadra_ospite_id               AS sq2,
                (a.punti_casa   + r.punti_ospite) AS tot_sq1,
                (a.punti_ospite + r.punti_casa)   AS tot_sq2
            FROM partita a
            JOIN partita r
                ON  r.torneo_id         = a.torneo_id
                AND r.turno             = a.turno
                AND r.tipo              = 'ritorno'
                AND r.squadra_casa_id   = a.squadra_ospite_id
                AND r.squadra_ospite_id = a.squadra_casa_id
            WHERE a.torneo_id = ?
              AND a.turno     = ?
              AND a.tipo      = 'andata'
              AND a.stato     = 'terminata'
              AND r.stato     = 'terminata'
        ");
        $stmt->bind_param("is", $torneo_id, $turno);
        $stmt->execute();
        $coppie = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $vincitori = [];
        foreach($coppie as $c){
            // Parità: vantaggio alla squadra di casa nell'andata
            $vincitori[] = ($c['tot_sq1'] >= $c['tot_sq2']) ? $c['sq1'] : $c['sq2'];
        }
        return $vincitori;
    }else{
        $stmt = $conn->prepare("
            SELECT
                CASE
                    WHEN punti_casa > punti_ospite THEN squadra_casa_id
                    ELSE squadra_ospite_id
                END AS vincitore
            FROM partita
            WHERE torneo_id = ? AND turno = ? AND stato = 'terminata' AND tipo = 'andata'
        ");
        $stmt->bind_param("is", $torneo_id, $turno);
        $stmt->execute();
        $res = $stmt->get_result();
        $vincitori = [];
        while($r = $res->fetch_assoc()) $vincitori[] = $r['vincitore'];
        return $vincitori;
    }
}

/* =====================================================
   GENERA IL TURNO SUCCESSIVO
===================================================== */

function generaTurnoSuccessivo($conn, $torneo_id, $turno, $tipo_partita){
    $next = prossimoTurno($turno);
    if(!$next) return;

    $vincitori = calcolaVincitori($conn, $torneo_id, $turno, $tipo_partita);
    if(count($vincitori) < 2) return;

    shuffle($vincitori);

    for($i = 0; $i + 1 < count($vincitori); $i += 2)
        inserisciCoppia($conn, $torneo_id, $vincitori[$i], $vincitori[$i+1], $next, $tipo_partita);
}

/* =====================================================
   GENERAZIONE AUTOMATICA ALL'AVVIO
===================================================== */

$erroreGenerazione = null;

if($torneo['stato'] === 'in_corso'){
    $res = $conn->query("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = $torneo_id");
    if($res->fetch_assoc()['tot'] == 0){
        $ris = generaIniziale($conn, $torneo_id, $tipo_partita);
        if(!$ris['ok']) $erroreGenerazione = $ris['msg'];
    }
}

/* =====================================================
   GESTIONE POST
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore){

    // --- SALVATAGGIO ORARIO ---
    if(isset($_POST['partita_id_orario'])){
        $partita_id = (int)$_POST['partita_id_orario'];
        $orario     = $_POST['orario'];

        if(empty($orario)){
            header("Location: struttura_torneo.php?id=$torneo_id&msg=errOrario");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET orario = ? WHERE id = ?");
        $stmt->bind_param("si", $orario, $partita_id);
        $stmt->execute();

        header("Location: struttura_torneo.php?id=$torneo_id");
        exit;
    }

    // --- INSERIMENTO RISULTATO ---
    if(isset($_POST['partita_id'])){
        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

        if($casa < 0 || $ospite < 0){
            header("Location: struttura_torneo.php?id=$torneo_id&msg=errPunti");
            exit;
        }

        // Pareggio vietato solo in andata secca
        if($tipo_partita === 'andata' && $casa == $ospite){
            header("Location: struttura_torneo.php?id=$torneo_id&msg=errRisultato");
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE partita SET punti_casa = ?, punti_ospite = ?, stato = 'terminata' WHERE id = ?
        ");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

        // Recupera il turno della partita appena aggiornata
        $stmt = $conn->prepare("SELECT turno FROM partita WHERE id = ?");
        $stmt->bind_param("i", $partita_id);
        $stmt->execute();
        $turno = $stmt->get_result()->fetch_assoc()['turno'];

        // Se il turno è completamente terminato, avanza o chiudi il torneo
        if(turnoTerminato($conn, $torneo_id, $turno, $tipo_partita)){
            if($turno === 'finale'){
                $stmt = $conn->prepare("UPDATE torneo SET stato = 'completato' WHERE id = ?");
                $stmt->bind_param("i", $torneo_id);
                $stmt->execute();
            }else{
                generaTurnoSuccessivo($conn, $torneo_id, $turno, $tipo_partita);
            }
        }

        header("Location: struttura_torneo.php?id=$torneo_id");
        exit;
    }
}

/* =====================================================
   CARICA PARTITE PER IL TABELLONE
===================================================== */

$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS casa, so.nome AS ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ?
    ORDER BY FIELD(p.turno, 'ottavi', 'quarti', 'semifinale', 'finale'), p.id
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();

$partitePerTurno = [];
while($row = $result->fetch_assoc())
    $partitePerTurno[$row['turno']][] = $row;

$ordineTurni = ['ottavi', 'quarti', 'semifinale', 'finale'];

require_once('templates/header.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Struttura torneo – <?= htmlspecialchars($torneo['nome']) ?></title>
</head>
<body>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>
<p>
    Formato: <strong>Eliminazione diretta</strong> &mdash;
    <?= $tipo_partita === 'andata_ritorno' ? 'Andata e ritorno' : 'Andata secca' ?>
</p>

<hr>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'errRisultato'): ?>
        <div style="color:red; margin-bottom:12px;">⚠️ In andata secca non è ammesso il pareggio.</div>
    <?php elseif($_GET['msg'] === 'errPunti'): ?>
        <div style="color:red; margin-bottom:12px;">⚠️ I valori negativi non sono validi.</div>
    <?php elseif($_GET['msg'] === 'errOrario'): ?>
        <div style="color:red; margin-bottom:12px;">⚠️ Inserisci un orario valido.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if($erroreGenerazione): ?>
    <div style="color:red; margin-bottom:12px;">⚠️ <?= htmlspecialchars($erroreGenerazione) ?></div>
<?php endif; ?>

<?php if($torneo['stato'] === 'completato'): ?>
    <div style="color:green; margin-bottom:12px; font-weight:bold;">🏆 Torneo completato!</div>
<?php endif; ?>

<?php if(empty($partitePerTurno)): ?>
    <p>Il tabellone non è ancora stato generato.</p>
<?php else: ?>

<?php foreach($ordineTurni as $turno):
    if(!isset($partitePerTurno[$turno])) continue;
?>

<h3><?= ucfirst($turno) ?></h3>

<?php if($tipo_partita === 'andata_ritorno'):
    // Raggruppa per coppia (andata + ritorno insieme)
    $coppie = [];
    foreach($partitePerTurno[$turno] as $p){
        $k = min($p['squadra_casa_id'], $p['squadra_ospite_id'])
           . '-'
           . max($p['squadra_casa_id'], $p['squadra_ospite_id']);
        $coppie[$k][$p['tipo']] = $p;
    }
?>

<table border="1">
<tr>
    <th>Coppia</th>
    <th>Andata</th>
    <th>Orario andata</th>
    <th>Ritorno</th>
    <th>Orario ritorno</th>
    <th>Aggregato</th>
    <?php if($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
</tr>

<?php foreach($coppie as $coppia):
    $a = $coppia['andata']  ?? null;
    $r = $coppia['ritorno'] ?? null;

    $sq1Nome = $a ? htmlspecialchars($a['casa'])   : '?';
    $sq2Nome = $a ? htmlspecialchars($a['ospite'])  : '?';

    $andataTerminata  = $a && $a['stato'] === 'terminata';
    $ritornoTerminato = $r && $r['stato'] === 'terminata';
    $coppiaTerminata  = $andataTerminata && $ritornoTerminato;

    $tot1 = $tot2 = null;
    if($coppiaTerminata){
        $tot1 = $a['punti_casa']   + $r['punti_ospite'];
        $tot2 = $a['punti_ospite'] + $r['punti_casa'];
    }
?>
<tr>
    <td><?= $sq1Nome ?> vs <?= $sq2Nome ?></td>

    <td><?= $andataTerminata ? ($a['punti_casa'] . ' - ' . $a['punti_ospite']) : ($a ? '- (da giocare)' : '-') ?></td>
    <td><?= $a ? ($a['orario'] ?? 'non impostato') : '-' ?></td>

    <td><?= $ritornoTerminato ? ($r['punti_casa'] . ' - ' . $r['punti_ospite']) : ($r ? '- (da giocare)' : '-') ?></td>
    <td><?= $r ? ($r['orario'] ?? 'non impostato') : '-' ?></td>

    <td>
        <?php if($coppiaTerminata): ?>
            <?= $sq1Nome ?>: <strong><?= $tot1 ?></strong> &mdash;
            <?= $sq2Nome ?>: <strong><?= $tot2 ?></strong><br>
            <?php if($tot1 > $tot2): ?>
                ✅ Avanza: <strong><?= $sq1Nome ?></strong>
            <?php elseif($tot2 > $tot1): ?>
                ✅ Avanza: <strong><?= $sq2Nome ?></strong>
            <?php else: ?>
                ✅ Avanza: <strong><?= $sq1Nome ?></strong> <em>(parità – vantaggio casa andata)</em>
            <?php endif; ?>
        <?php elseif($andataTerminata): ?>
            Parziale: <?= $sq1Nome ?> <?= $a['punti_casa'] ?> – <?= $sq2Nome ?> <?= $a['punti_ospite'] ?>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>

    <?php if($isOrganizzatore): ?>
    <td>
        <?php if($a && !$andataTerminata): ?>
            <strong>Andata:</strong><br>
            <form method="POST" style="margin-bottom:6px;">
                <input type="hidden" name="partita_id_orario" value="<?= $a['id'] ?>">
                <input type="datetime-local" name="orario" required>
                <button>Salva orario</button>
            </form>
            <form method="POST" style="margin-bottom:12px;">
                <input type="hidden" name="partita_id" value="<?= $a['id'] ?>">
                <?= $sq1Nome ?> <input type="number" name="casa"   min="0" required style="width:50px;">
                <?= $sq2Nome ?> <input type="number" name="ospite" min="0" required style="width:50px;">
                <button>OK</button>
            </form>
        <?php elseif($andataTerminata): ?>
            Andata ✔<br>
        <?php endif; ?>

        <?php if($r && !$ritornoTerminato): ?>
            <strong>Ritorno:</strong><br>
            <form method="POST" style="margin-bottom:6px;">
                <input type="hidden" name="partita_id_orario" value="<?= $r['id'] ?>">
                <input type="datetime-local" name="orario" required>
                <button>Salva orario</button>
            </form>
            <form method="POST">
                <input type="hidden" name="partita_id" value="<?= $r['id'] ?>">
                <?= $sq2Nome ?> <input type="number" name="casa"   min="0" required style="width:50px;">
                <?= $sq1Nome ?> <input type="number" name="ospite" min="0" required style="width:50px;">
                <button>OK</button>
            </form>
        <?php elseif($ritornoTerminato): ?>
            Ritorno ✔
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>

<?php else: /* ===== ANDATA SECCA ===== */ ?>

<table border="1">
<tr>
    <th>Casa</th>
    <th>Ospite</th>
    <th>Orario</th>
    <th>Risultato</th>
    <?php if($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
</tr>
<?php foreach($partitePerTurno[$turno] as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['casa']) ?></td>
    <td><?= htmlspecialchars($row['ospite']) ?></td>
    <td><?= $row['orario'] ?? 'non impostato' ?></td>
    <td>
        <?= ($row['punti_casa'] ?? '-') . ' - ' . ($row['punti_ospite'] ?? '-') ?>
        <?php if($row['stato'] === 'terminata'): ?>
            &nbsp;✅ <strong>
            <?= $row['punti_casa'] > $row['punti_ospite']
                ? htmlspecialchars($row['casa'])
                : htmlspecialchars($row['ospite']) ?>
            </strong>
        <?php endif; ?>
    </td>
    <?php if($isOrganizzatore): ?>
    <td>
        <?php if($row['stato'] !== 'terminata'): ?>
        <form method="POST" style="margin-bottom:6px;">
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

<?php endif; ?>

<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
<?php require_once('templates/footer.php'); ?>