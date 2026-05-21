<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();
include_once("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;
$view      = $_GET['view'] ?? 'classifica';

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
   CONTROLLA SE IL TURNO SUCCESSIVO DEL PLAYOFF È GIÀ STATO GENERATO
===================================================== */

function turnoSuccessivoPlayoffEsiste($conn, $torneo_id, $turno){
    $next = prossimoTurno($turno);
    if(!$next) return false;

    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND turno = ? AND girone IS NULL
    ");
    $stmt->bind_param("is", $torneo_id, $next);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['tot'] > 0;
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
            'id'   => $sq['id'], 'nome' => $sq['nome'],
            'G'    => 0, 'V'   => 0, 'P' => 0, 'S'   => 0,
            'PF'   => 0, 'PS'  => 0, 'DP'=> 0, 'Pts' => 0
        ];
    }

    foreach($partite as $p){
        $c  = $p['squadra_casa_id'];
        $o  = $p['squadra_ospite_id'];
        $pc = $p['punti_casa'];
        $po = $p['punti_ospite'];

        $classifica[$c]['G']++;       $classifica[$o]['G']++;
        $classifica[$c]['PF'] += $pc; $classifica[$c]['PS'] += $po;
        $classifica[$o]['PF'] += $po; $classifica[$o]['PS'] += $pc;

        if($pc > $po){
            $classifica[$c]['V']++; $classifica[$c]['Pts'] += 3;
            $classifica[$o]['S']++;
        }elseif($pc < $po){
            $classifica[$o]['V']++; $classifica[$o]['Pts'] += 3;
            $classifica[$c]['S']++;
        }else{
            $classifica[$c]['P']++; $classifica[$c]['Pts']++;
            $classifica[$o]['P']++; $classifica[$o]['Pts']++;
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
===================================================== */

function calcolaParametriPlayoff($numGironi){
    $target = 4;
    while($target < $numGironi * 2) $target *= 2;
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
    $candidateExtra = [];

    for($g = 1; $g <= $numGironi; $g++){
        $cls = classificaGirone($conn, $torneo_id, $g);

        for($pos = 0; $pos < min($perGirone, count($cls)); $pos++)
            $qualificate[] = $cls[$pos]['id'];

        if($extras > 0 && isset($cls[$perGirone]))
            $candidateExtra[] = $cls[$perGirone];
    }

    if($extras > 0 && count($candidateExtra) > 0){
        usort($candidateExtra, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts'] ?: $b['DP'] <=> $a['DP'] ?: $b['PF'] <=> $a['PF']
        );
        for($i = 0; $i < min($extras, count($candidateExtra)); $i++)
            $qualificate[] = $candidateExtra[$i]['id'];
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

    if($tot == 0 && $formato === 'gironi_playoff')
        generaGironi($conn, $torneo_id);
}

/* =====================================================
   GESTIONE POST (inserimento e aggiornamento risultati e orari)
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore){

    // SALVATAGGIO ORARIO
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

    // INSERIMENTO / AGGIORNAMENTO RISULTATO
    if(isset($_POST['partita_id'])){
        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

        $stmt = $conn->prepare("SELECT turno, girone FROM partita WHERE id = ?");
        $stmt->bind_param("i", $partita_id);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $redirectView = ($info['girone'] !== null) ? 'gironi' : 'partite';

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
            // Partita di girone: il playoff viene generato solo se non esiste ancora
            generaPlayoff($conn, $torneo_id);
        }else{
            // Partita di playoff: avanza solo se il turno successivo non esiste già
            $turno      = $info['turno'];
            $nextEsiste = turnoSuccessivoPlayoffEsiste($conn, $torneo_id, $turno);

            if(!$nextEsiste){
                $stmt = $conn->prepare("
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

$perGirone = 0;
$extras    = 0;
if($numGironi > 0)
    [$perGirone, $extras,] = calcolaParametriPlayoff($numGironi);

$extra_css = ['css/tabella_tornei.css', 'css/torneo_struttura.css'];
require_once('templates/header.php');
$turno_label_misto = ['ottavi' => 'Ottavi', 'quarti' => 'Quarti', 'semifinale' => 'Semifinale', 'finale' => 'Finale'];
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>"><?= htmlspecialchars($torneo['nome']) ?></a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span><?= htmlspecialchars(ucfirst($view)) ?></span>
        </div>
        <div style="display: flex; gap: 8px; margin-bottom: var(--m-3); flex-wrap: wrap;">
            <span class="t-chip">Gironi + playoff</span>
        </div>
        <h1><?= htmlspecialchars($torneo['nome']) ?></h1>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab">Info torneo</a>
            <a href="struttura_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab m-tab--active">Struttura torneo</a>
            <?php if ($torneo['stato'] === 'in_corso'): ?><a href="gestione_pranzi.php?id=<?= (int)$torneo_id ?>" class="m-tab">Gestione pranzi</a><?php endif; ?>
        </div>

        <?php if($formato === 'gironi_playoff'): ?>
            <div class="m-row m-mb-5">
                <a href="?id=<?= (int)$torneo_id ?>&view=gironi"    class="m-btn <?= $view === 'gironi'    ? 'm-btn--primary' : 'm-btn--ghost' ?> m-btn--sm">Gironi</a>
                <?php if($playoffGenerato): ?>
                    <a href="?id=<?= (int)$torneo_id ?>&view=partite" class="m-btn <?= $view === 'partite' ? 'm-btn--primary' : 'm-btn--ghost' ?> m-btn--sm">Playoff</a>
                <?php endif; ?>
                <a href="?id=<?= (int)$torneo_id ?>&view=classifica" class="m-btn <?= $view === 'classifica' ? 'm-btn--primary' : 'm-btn--ghost' ?> m-btn--sm">Classifica generale</a>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg'])): ?>
            <?php $errs_m = [
                'errRisultato' => 'Le squadre non possono pareggiare in eliminazione diretta.',
                'errPunti'     => 'Valori negativi non validi.',
                'errOrario'    => 'Inserisci un orario valido.'
            ]; ?>
            <?php if (isset($errs_m[$_GET['msg']])): ?>
                <div class="m-alert m-alert--danger m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div><?= htmlspecialchars($errs_m[$_GET['msg']]) ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

<?php

/* =====================================================
   VIEW: CLASSIFICA GENERALE
===================================================== */

if($view === 'classifica'):

// Calcolo migliori terze (una volta sola)
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
for($ei = 0; $ei < min($extras, count($tutteLeTerze)); $ei++)
    $idQualificateExtra[] = $tutteLeTerze[$ei]['id'];

?>

<h2 class="m-mb-5">Classifica generale</h2>

<?php for($g = 1; $g <= $numGironi; $g++):
    $cls = classificaGirone($conn, $torneo_id, $g);
?>
<div class="m-card m-mb-5" style="padding: var(--m-5);">
    <h3 class="m-mb-3" style="display:flex; align-items:center; gap:8px;">
        <span class="m-badge m-badge--gold">Girone <?= $g ?></span>
    </h3>
    <div class="m-table-wrap">
        <table class="m-table">
            <thead>
                <tr>
                    <th>#</th><th>Squadra</th>
                    <th class="m-num">G</th><th class="m-num">V</th><th class="m-num">P</th><th class="m-num">S</th>
                    <th class="m-num">PF</th><th class="m-num">PS</th><th class="m-num">DP</th><th class="m-num">Pts</th>
                    <?php if($playoffGenerato): ?><th>Stato</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($cls as $pos => $sq):
                $rank_class = '';
                if ($pos === 0) $rank_class = 'm-rank--1';
                elseif ($pos === 1) $rank_class = 'm-rank--2';
                elseif ($pos === 2) $rank_class = 'm-rank--3';
            ?>
                <tr>
                    <td><span class="m-rank <?= $rank_class ?>"><?= $pos + 1 ?></span></td>
                    <td>
                        <div class="m-team">
                            <span class="m-avatar m-avatar--sq"><?= strtoupper(mb_substr($sq['nome'], 0, 2)) ?></span>
                            <?= htmlspecialchars($sq['nome']) ?>
                        </div>
                    </td>
                    <td class="m-num"><?= (int)$sq['G'] ?></td>
                    <td class="m-num"><?= (int)$sq['V'] ?></td>
                    <td class="m-num"><?= (int)$sq['P'] ?></td>
                    <td class="m-num"><?= (int)$sq['S'] ?></td>
                    <td class="m-num"><?= (int)$sq['PF'] ?></td>
                    <td class="m-num"><?= (int)$sq['PS'] ?></td>
                    <td class="m-num"><?= (int)$sq['DP'] ?></td>
                    <td class="m-num"><b><?= (int)$sq['Pts'] ?></b></td>
                    <?php if($playoffGenerato): ?>
                    <td>
                        <?php if($pos < $perGirone): ?>
                            <span class="m-badge m-badge--success m-badge--dot">Qualificata</span>
                        <?php elseif($pos === $perGirone && in_array($sq['id'], $idQualificateExtra)): ?>
                            <span class="m-badge m-badge--gold">Miglior terza</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endfor; ?>

<?php

/* =====================================================
   VIEW: GIRONI (partite + classifica)
===================================================== */

elseif($view === 'gironi'):

?>

<?php if($numGironi === 0): ?>
    <div class="m-empty">
        <div class="m-empty__icon">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
        </div>
        <h3>Nessun girone generato</h3>
        <p class="m-muted">I gironi saranno generati automaticamente all'avvio del torneo.</p>
    </div>
<?php else:

    // Calcola le qualificate extra (una sola volta)
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
    for($ei = 0; $ei < min($extras, count($tutteLeTerzeGironi)); $ei++)
        $idQualificateExtraGironi[] = $tutteLeTerzeGironi[$ei]['id'];

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

<div class="m-card m-mb-5" style="padding: var(--m-5);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom: var(--m-4);">
        <h3 style="margin:0; display:flex; align-items:center; gap:8px;">
            <span class="m-badge m-badge--gold">Girone <?= $g ?></span>
            <span class="m-muted" style="font-size:14px; font-weight:400;"><?= count($cls) ?> squadre</span>
        </h3>
    </div>

    <h4 class="m-profile-section-label">Classifica</h4>
    <div class="m-table-wrap m-mb-5">
        <table class="m-table">
            <thead>
                <tr>
                    <th>#</th><th>Squadra</th>
                    <th class="m-num">G</th><th class="m-num">V</th><th class="m-num">P</th><th class="m-num">S</th>
                    <th class="m-num">PF</th><th class="m-num">PS</th><th class="m-num">DP</th><th class="m-num">Pts</th>
                    <?php if($playoffGenerato): ?><th>Stato</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($cls as $pos => $sq):
                $rank_class = '';
                if ($pos === 0) $rank_class = 'm-rank--1';
                elseif ($pos === 1) $rank_class = 'm-rank--2';
                elseif ($pos === 2) $rank_class = 'm-rank--3';
            ?>
                <tr>
                    <td><span class="m-rank <?= $rank_class ?>"><?= $pos + 1 ?></span></td>
                    <td>
                        <div class="m-team">
                            <span class="m-avatar m-avatar--sq"><?= strtoupper(mb_substr($sq['nome'], 0, 2)) ?></span>
                            <?= htmlspecialchars($sq['nome']) ?>
                        </div>
                    </td>
                    <td class="m-num"><?= (int)$sq['G'] ?></td>
                    <td class="m-num"><?= (int)$sq['V'] ?></td>
                    <td class="m-num"><?= (int)$sq['P'] ?></td>
                    <td class="m-num"><?= (int)$sq['S'] ?></td>
                    <td class="m-num"><?= (int)$sq['PF'] ?></td>
                    <td class="m-num"><?= (int)$sq['PS'] ?></td>
                    <td class="m-num"><?= (int)$sq['DP'] ?></td>
                    <td class="m-num"><b><?= (int)$sq['Pts'] ?></b></td>
                    <?php if($playoffGenerato): ?>
                    <td>
                        <?php if($pos < $perGirone): ?>
                            <span class="m-badge m-badge--success m-badge--dot">Qualificata</span>
                        <?php elseif($pos === $perGirone && in_array($sq['id'], $idQualificateExtraGironi)): ?>
                            <span class="m-badge m-badge--gold">Miglior terza</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 class="m-profile-section-label">Partite</h4>
    <div class="m-table-wrap">
        <table class="m-table">
            <thead>
                <tr>
                    <th>Casa</th><th>Ospite</th><th>Orario</th><th>Risultato</th>
                    <?php if($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($partite as $row):
                $finita = $row['stato'] === 'terminata';
            ?>
                <tr>
                    <td><b><?= htmlspecialchars($row['casa']) ?></b></td>
                    <td><b><?= htmlspecialchars($row['ospite']) ?></b></td>
                    <td>
                        <?php if(!empty($row['orario'])): ?>
                            <span class="m-mono"><?= htmlspecialchars(date('d/m H:i', strtotime($row['orario']))) ?></span>
                        <?php else: ?>
                            <span class="m-muted" style="font-style:italic;">non impostato</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($finita): ?>
                            <b class="m-num"><?= (int)$row['punti_casa'] ?> &ndash; <?= (int)$row['punti_ospite'] ?></b>
                        <?php else: ?>
                            <span class="m-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <?php if($isOrganizzatore): ?>
                    <td>
                        <?php if(!$playoffGenerato): ?>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <?php if(!$finita): ?>
                                    <form method="POST" style="display:flex; gap:4px;">
                                        <input type="hidden" name="partita_id_orario" value="<?= (int)$row['id'] ?>">
                                        <input class="m-input" type="datetime-local" name="orario" required style="padding:4px 8px; font-size:12px;">
                                        <button class="m-btn m-btn--secondary m-btn--sm">Orario</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:flex; gap:4px; align-items:center;">
                                    <input type="hidden" name="partita_id" value="<?= (int)$row['id'] ?>">
                                    <input class="m-input m-num" type="number" name="casa" min="0" required
                                           value="<?= $finita ? (int)$row['punti_casa'] : '' ?>"
                                           style="width:50px; padding:4px; text-align:center;">
                                    <span class="m-muted">&ndash;</span>
                                    <input class="m-input m-num" type="number" name="ospite" min="0" required
                                           value="<?= $finita ? (int)$row['punti_ospite'] : '' ?>"
                                           style="width:50px; padding:4px; text-align:center;">
                                    <button class="m-btn <?= $finita ? 'm-btn--secondary' : 'm-btn--primary' ?> m-btn--sm"
                                            title="<?= $finita ? 'Modifica risultato' : 'Inserisci risultato' ?>">
                                        <?= $finita ? '&#9998;' : 'OK' ?>
                                    </button>
                                </form>
                            </div>
                        <?php elseif($finita): ?>
                            <span class="m-badge m-badge--success m-badge--dot">Terminata</span>
                        <?php else: ?>
                            <span class="m-muted" style="font-size:11px; font-style:italic;">Playoff generato</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endfor; endif; ?>

<?php if(!$playoffGenerato && $numGironi > 0): ?>
    <div class="m-alert m-alert--info m-mb-5">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <div>
            Il tabellone playoff verrà generato automaticamente al termine di tutti i gironi.
            <?php if($extras > 0): ?>
                <br>Avanzeranno <b><?= $perGirone ?></b> squadre per girone + le <b><?= $extras ?></b> migliori terze classificate.
            <?php else: ?>
                <br>Avanzeranno le <b>prime <?= $perGirone ?></b> di ogni girone.
            <?php endif; ?>
        </div>
    </div>
<?php elseif($playoffGenerato): ?>
    <div class="m-alert m-alert--success m-mb-5">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <div>
            Fase a gironi completata.
            <a href="?id=<?= (int)$torneo_id ?>&view=partite" style="font-weight:600;">Vai al tabellone playoff &rarr;</a>
        </div>
    </div>
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
    <div class="m-empty">
        <div class="m-empty__icon">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
        </div>
        <h3>Tabellone playoff non ancora generato</h3>
        <p class="m-muted">Sarà disponibile al termine della fase a gironi.</p>
    </div>
<?php else: ?>

<div class="m-bracket-frame">
    <div class="m-bracket-frame__header">
        <h3 style="margin:0;">Tabellone playoff</h3>
    </div>
    <div class="m-bracket-scroll">
        <div class="m-bracket">
            <?php foreach($ordineTurni as $turno):
                if(!isset($partitePerTurno[$turno])) continue;
                $is_final    = ($turno === 'finale');
                $nextEsiste  = turnoSuccessivoPlayoffEsiste($conn, $torneo_id, $turno);
            ?>
                <div class="m-bracket__round">
                    <div class="m-bracket__round-title"><?= htmlspecialchars($turno_label_misto[$turno] ?? $turno) ?></div>
                    <?php foreach($partitePerTurno[$turno] as $row):
                        $finita = $row['stato'] === 'terminata';
                        $cls1 = $cls2 = '';
                        if($finita){
                            if($row['punti_casa'] > $row['punti_ospite']){ $cls1 = 'm-match__row--winner'; $cls2 = 'm-match__row--loser'; }
                            else                                          { $cls2 = 'm-match__row--winner'; $cls1 = 'm-match__row--loser'; }
                        }
                        $puoModificare = $isOrganizzatore && !$nextEsiste;
                    ?>
                        <div class="m-match<?= $is_final ? ' m-match--final' : '' ?>" style="min-width: 240px;">
                            <div class="m-match__head">
                                <span class="m-match__head-id"><?= htmlspecialchars(strtoupper(substr($turno,0,3))) ?>-<?= (int)$row['id'] ?></span>
                                <span>
                                    <?php if(!empty($row['orario'])): ?>
                                        <?= htmlspecialchars(date('d/m H:i', strtotime($row['orario']))) ?>
                                    <?php else: ?>
                                        <?= $finita ? 'Terminata' : 'Da giocare' ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="m-match__row <?= $cls1 ?>">
                                <span class="m-match__team"><?= htmlspecialchars($row['casa']) ?></span>
                                <span class="m-match__score"><?= $finita ? (int)$row['punti_casa'] : '' ?></span>
                            </div>
                            <div class="m-match__row <?= $cls2 ?>">
                                <span class="m-match__team"><?= htmlspecialchars($row['ospite']) ?></span>
                                <span class="m-match__score"><?= $finita ? (int)$row['punti_ospite'] : '' ?></span>
                            </div>
                            <?php if($puoModificare): ?>
                                <div style="padding:8px 10px; border-top:1px solid var(--m-border); display:flex; flex-direction:column; gap:6px;">
                                    <?php if(!$finita): ?>
                                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                                            <input type="hidden" name="partita_id_orario" value="<?= (int)$row['id'] ?>">
                                            <input class="m-input" type="datetime-local" name="orario" required style="padding:4px; font-size:11px;">
                                            <button class="m-btn m-btn--secondary m-btn--sm">Orario</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:flex; gap:4px; align-items:center;">
                                        <input type="hidden" name="partita_id" value="<?= (int)$row['id'] ?>">
                                        <input class="m-input m-num" type="number" name="casa" min="0" required
                                               value="<?= $finita ? (int)$row['punti_casa'] : '' ?>"
                                               style="width:50px; padding:4px; text-align:center;">
                                        <span class="m-muted">&ndash;</span>
                                        <input class="m-input m-num" type="number" name="ospite" min="0" required
                                               value="<?= $finita ? (int)$row['punti_ospite'] : '' ?>"
                                               style="width:50px; padding:4px; text-align:center;">
                                        <button class="m-btn <?= $finita ? 'm-btn--secondary' : 'm-btn--primary' ?> m-btn--sm"
                                                title="<?= $finita ? 'Modifica risultato' : 'Inserisci risultato' ?>">
                                            <?= $finita ? '&#9998;' : 'OK' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php elseif($isOrganizzatore && $nextEsiste): ?>
                                <div style="padding:8px 10px; border-top:1px solid var(--m-border); font-size:11px; color:var(--m-text-mute); font-style:italic;">
                                    Turno successivo già generato
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>