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
   CONTROLLA SE IL TURNO SUCCESSIVO È GIÀ STATO GENERATO
   Restituisce true se esiste già almeno una partita per $next
===================================================== */

function turnoSuccessivoEsiste($conn, $torneo_id, $turno){
    $next = prossimoTurno($turno);
    if(!$next) return false; // finale: non c'è un successivo

    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND turno = ?
    ");
    $stmt->bind_param("is", $torneo_id, $next);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['tot'] > 0;
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

    // SALVATAGGIO ORARIO
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

    // INSERIMENTO / AGGIORNAMENTO RISULTATO
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

        // Se il turno successivo esiste già, non rigenerare (si sta correggendo un risultato)
        $nextEsiste = turnoSuccessivoEsiste($conn, $torneo_id, $turno);

        if(!$nextEsiste && turnoTerminato($conn, $torneo_id, $turno, $tipo_partita)){
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

$ordineTurni  = ['ottavi', 'quarti', 'semifinale', 'finale'];
$turno_label  = ['ottavi' => 'Ottavi', 'quarti' => 'Quarti', 'semifinale' => 'Semifinale', 'finale' => 'Finale'];

$extra_css = ['css/tabella_tornei.css', 'css/torneo_struttura.css'];
require_once('templates/header.php');
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>"><?= htmlspecialchars($torneo['nome']) ?></a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Tabellone</span>
        </div>
        <div style="display: flex; gap: 8px; margin-bottom: var(--m-3); flex-wrap: wrap;">
            <span class="t-chip">Eliminazione diretta &nbsp;<?= $tipo_partita === 'andata_ritorno' ? 'A/R' : 'Andata secca' ?></span>
            <?php if($torneo['stato'] === 'completato'): ?><span class="t-chip" style="background: rgba(31,157,85,0.2); color: #b3f0c8;">&#10003; Completato</span><?php endif; ?>
        </div>
        <h1><?= htmlspecialchars($torneo['nome']) ?> &mdash; Tabellone</h1>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab">Info torneo</a>
            <a href="struttura_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab m-tab--active">Struttura torneo</a>
            <?php if ($torneo['stato'] === 'in_corso'): ?><a href="gestione_pranzi.php?id=<?= (int)$torneo_id ?>" class="m-tab">Gestione pranzi</a><?php endif; ?>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php $err_msgs = [
                'errRisultato' => 'In andata secca non è ammesso il pareggio.',
                'errPunti'     => 'I valori negativi non sono validi.',
                'errOrario'    => 'Inserisci un orario valido.'
            ]; ?>
            <?php if (isset($err_msgs[$_GET['msg']])): ?>
                <div class="m-alert m-alert--danger m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div><?= htmlspecialchars($err_msgs[$_GET['msg']]) ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($erroreGenerazione): ?>
            <div class="m-alert m-alert--warn m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div><?= htmlspecialchars($erroreGenerazione) ?></div>
            </div>
        <?php endif; ?>

        <?php if($torneo['stato'] === 'completato'): ?>
            <div class="m-alert m-alert--success m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                <div>Torneo completato!</div>
            </div>
        <?php endif; ?>

        <?php if(empty($partitePerTurno)): ?>
            <div class="m-empty">
                <div class="m-empty__icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                </div>
                <h3>Tabellone non ancora generato</h3>
                <p class="m-muted">Verrà generato all'avvio del torneo.</p>
            </div>
        <?php else: ?>

        <div class="m-bracket-frame">
            <div class="m-bracket-frame__header">
                <h3 style="margin:0;">Tabellone &mdash; <?= $tipo_partita === 'andata_ritorno' ? 'Andata e ritorno' : 'Andata secca' ?></h3>
            </div>
            <div class="m-bracket-scroll">
                <div class="m-bracket">
                    <?php foreach($ordineTurni as $turno):
                        if(!isset($partitePerTurno[$turno])) continue;
                        $is_final = ($turno === 'finale');
                        // Verifica se il turno successivo esiste (per bloccare le modifiche)
                        $nextEsisteTurno = turnoSuccessivoEsiste($conn, $torneo_id, $turno);
                    ?>
                        <div class="m-bracket__round">
                            <div class="m-bracket__round-title"><?= htmlspecialchars($turno_label[$turno] ?? $turno) ?></div>

                            <?php if($tipo_partita === 'andata_ritorno'):
                                // Raggruppa andata + ritorno per coppia
                                $coppie = [];
                                foreach($partitePerTurno[$turno] as $p){
                                    $k = min($p['squadra_casa_id'], $p['squadra_ospite_id']) . '-' . max($p['squadra_casa_id'], $p['squadra_ospite_id']);
                                    $coppie[$k][$p['tipo']] = $p;
                                }
                                foreach($coppie as $coppia):
                                    $a = $coppia['andata']  ?? null;
                                    $r = $coppia['ritorno'] ?? null;
                                    $sq1Nome = $a ? $a['casa']   : '?';
                                    $sq2Nome = $a ? $a['ospite'] : '?';
                                    $andataTerminata  = $a && $a['stato'] === 'terminata';
                                    $ritornoTerminato = $r && $r['stato'] === 'terminata';
                                    $coppiaTerminata  = $andataTerminata && $ritornoTerminato;
                                    $tot1 = $tot2 = null;
                                    if($coppiaTerminata){
                                        $tot1 = $a['punti_casa']   + $r['punti_ospite'];
                                        $tot2 = $a['punti_ospite'] + $r['punti_casa'];
                                    }
                                    $cls1 = $cls2 = '';
                                    if($coppiaTerminata){
                                        if($tot1 >= $tot2){ $cls1 = 'm-match__row--winner'; $cls2 = 'm-match__row--loser'; }
                                        else              { $cls2 = 'm-match__row--winner'; $cls1 = 'm-match__row--loser'; }
                                    }
                                    // Può modificare solo se il turno successivo non è già generato
                                    $puoModificare = $isOrganizzatore && !$nextEsisteTurno;
                            ?>
                                <div class="m-match<?= $is_final ? ' m-match--final' : '' ?>" style="min-width: 280px;">
                                    <div class="m-match__head">
                                        <span class="m-match__head-id"><?= htmlspecialchars(strtoupper(substr($turno,0,3))) ?>-<?= (int)$a['id'] ?></span>
                                        <span><?= $coppiaTerminata ? 'Terminata' : 'In corso' ?></span>
                                    </div>
                                    <div class="m-match__row <?= $cls1 ?>">
                                        <span class="m-match__seed"></span>
                                        <span class="m-match__team"><?= htmlspecialchars($sq1Nome) ?></span>
                                        <span class="m-match__score"><?= $coppiaTerminata ? $tot1 : '' ?></span>
                                    </div>
                                    <div class="m-match__row <?= $cls2 ?>">
                                        <span class="m-match__seed"></span>
                                        <span class="m-match__team"><?= htmlspecialchars($sq2Nome) ?></span>
                                        <span class="m-match__score"><?= $coppiaTerminata ? $tot2 : '' ?></span>
                                    </div>
                                    <div style="padding: 6px 10px; background: var(--m-surface-2); border-top: 1px solid var(--m-border); font-size: 11px; color: var(--m-text-mute);">
                                        Andata: <b><?= $andataTerminata ? $a['punti_casa'].'-'.$a['punti_ospite'] : '-' ?></b>
                                        &nbsp; Ritorno: <b><?= $ritornoTerminato ? $r['punti_casa'].'-'.$r['punti_ospite'] : '-' ?></b>
                                    </div>
                                    <?php if($puoModificare): ?>
                                        <div style="padding: 10px; border-top: 1px solid var(--m-border); display: flex; flex-direction: column; gap: 8px;">
                                            <?php if($a && !$andataTerminata): ?>
                                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                    <input type="hidden" name="partita_id" value="<?= (int)$a['id'] ?>">
                                                    <span style="font-size: 11px; color: var(--m-text-mute); margin-right: 4px;">AND</span>
                                                    <input class="m-input m-num" type="number" name="casa" min="0" required style="width: 50px; padding: 4px; text-align: center;">
                                                    <span style="color: var(--m-text-mute);">&ndash;</span>
                                                    <input class="m-input m-num" type="number" name="ospite" min="0" required style="width: 50px; padding: 4px; text-align: center;">
                                                    <button class="m-btn m-btn--primary m-btn--sm">OK</button>
                                                </form>
                                            <?php elseif($a && $andataTerminata): ?>
                                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                    <input type="hidden" name="partita_id" value="<?= (int)$a['id'] ?>">
                                                    <span style="font-size: 11px; color: var(--m-text-mute); margin-right: 4px;">AND</span>
                                                    <input class="m-input m-num" type="number" name="casa" min="0" required
                                                           value="<?= (int)$a['punti_casa'] ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <span style="color: var(--m-text-mute);">&ndash;</span>
                                                    <input class="m-input m-num" type="number" name="ospite" min="0" required
                                                           value="<?= (int)$a['punti_ospite'] ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <button class="m-btn m-btn--secondary m-btn--sm" title="Modifica andata">&#9998;</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if($r && !$ritornoTerminato): ?>
                                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                    <input type="hidden" name="partita_id" value="<?= (int)$r['id'] ?>">
                                                    <span style="font-size: 11px; color: var(--m-text-mute); margin-right: 4px;">RIT</span>
                                                    <input class="m-input m-num" type="number" name="casa" min="0" required style="width: 50px; padding: 4px; text-align: center;">
                                                    <span style="color: var(--m-text-mute);">&ndash;</span>
                                                    <input class="m-input m-num" type="number" name="ospite" min="0" required style="width: 50px; padding: 4px; text-align: center;">
                                                    <button class="m-btn m-btn--primary m-btn--sm">OK</button>
                                                </form>
                                            <?php elseif($r && $ritornoTerminato): ?>
                                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                    <input type="hidden" name="partita_id" value="<?= (int)$r['id'] ?>">
                                                    <span style="font-size: 11px; color: var(--m-text-mute); margin-right: 4px;">RIT</span>
                                                    <input class="m-input m-num" type="number" name="casa" min="0" required
                                                           value="<?= (int)$r['punti_casa'] ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <span style="color: var(--m-text-mute);">&ndash;</span>
                                                    <input class="m-input m-num" type="number" name="ospite" min="0" required
                                                           value="<?= (int)$r['punti_ospite'] ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <button class="m-btn m-btn--secondary m-btn--sm" title="Modifica ritorno">&#9998;</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif($isOrganizzatore && $nextEsisteTurno): ?>
                                        <div style="padding: 8px 10px; border-top: 1px solid var(--m-border); font-size: 11px; color: var(--m-text-mute); font-style: italic;">
                                            Turno successivo già generato
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php else: /* ANDATA SECCA */ ?>
                                <?php foreach($partitePerTurno[$turno] as $row):
                                    $finita = $row['stato'] === 'terminata';
                                    $cls1 = $cls2 = '';
                                    if($finita){
                                        if($row['punti_casa'] > $row['punti_ospite']){ $cls1 = 'm-match__row--winner'; $cls2 = 'm-match__row--loser'; }
                                        else                                          { $cls2 = 'm-match__row--winner'; $cls1 = 'm-match__row--loser'; }
                                    }
                                    $puoModificare = $isOrganizzatore && !$nextEsisteTurno;
                                ?>
                                    <div class="m-match<?= $is_final ? ' m-match--final' : '' ?>" style="min-width: 240px;">
                                        <div class="m-match__head">
                                            <span class="m-match__head-id"><?= htmlspecialchars(strtoupper(substr($turno,0,3))) ?>-<?= (int)$row['id'] ?></span>
                                            <span><?= !empty($row['orario']) ? htmlspecialchars(date('d/m H:i', strtotime($row['orario']))) : ($finita ? 'Terminata' : 'Da giocare') ?></span>
                                        </div>
                                        <div class="m-match__row <?= $cls1 ?>">
                                            <span class="m-match__seed"></span>
                                            <span class="m-match__team"><?= htmlspecialchars($row['casa']) ?></span>
                                            <span class="m-match__score"><?= $finita ? (int)$row['punti_casa'] : '' ?></span>
                                        </div>
                                        <div class="m-match__row <?= $cls2 ?>">
                                            <span class="m-match__seed"></span>
                                            <span class="m-match__team"><?= htmlspecialchars($row['ospite']) ?></span>
                                            <span class="m-match__score"><?= $finita ? (int)$row['punti_ospite'] : '' ?></span>
                                        </div>
                                        <?php if($puoModificare): ?>
                                            <div style="padding: 8px 10px; border-top: 1px solid var(--m-border); display: flex; flex-direction: column; gap: 6px;">
                                                <?php if(!$finita): ?>
                                                    <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                        <input type="hidden" name="partita_id_orario" value="<?= (int)$row['id'] ?>">
                                                        <input class="m-input" type="datetime-local" name="orario" required style="padding: 4px; font-size: 11px;">
                                                        <button class="m-btn m-btn--secondary m-btn--sm">Orario</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                    <input type="hidden" name="partita_id" value="<?= (int)$row['id'] ?>">
                                                    <input class="m-input m-num" type="number" name="casa" min="0" required
                                                           value="<?= $finita ? (int)$row['punti_casa'] : '' ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <span style="color: var(--m-text-mute);">&ndash;</span>
                                                    <input class="m-input m-num" type="number" name="ospite" min="0" required
                                                           value="<?= $finita ? (int)$row['punti_ospite'] : '' ?>"
                                                           style="width: 50px; padding: 4px; text-align: center;">
                                                    <button class="m-btn <?= $finita ? 'm-btn--secondary' : 'm-btn--primary' ?> m-btn--sm"
                                                            title="<?= $finita ? 'Modifica risultato' : 'Inserisci risultato' ?>">
                                                        <?= $finita ? '&#9998;' : 'OK' ?>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif($isOrganizzatore && $nextEsisteTurno): ?>
                                            <div style="padding: 8px 10px; border-top: 1px solid var(--m-border); font-size: 11px; color: var(--m-text-mute); font-style: italic;">
                                                Turno successivo già generato
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>