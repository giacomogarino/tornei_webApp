<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$tipo_partita = $torneo['tipo_partita']; // 'andata' | 'andata_ritorno'

$isOrganizzatore = isset($_SESSION['id_utente']) &&
                    $_SESSION['id_utente'] == $torneo['creato_da'];

/* =====================================================
   FUNZIONI
===================================================== */

function girone_genera_partite($conn, $torneo_id, $tipo_partita){

    $stmt = $conn->prepare("
        SELECT id FROM squadra
        WHERE torneo_id = ? AND stato = 'approvata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $squadre = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

    $n = count($squadre);
    if ($n < 2) return;

    // Se dispari aggiunge un null --> turno di riposo
    if($n % 2 !== 0){
        $squadre[] = null;
        $n++;
    }

    $meta = $n / 2;
    $giornate = [];

    // fissa prima squadra e ruotano le altre
    $lista = $squadre;
    $fisso = array_shift($lista);

    for($g = 0; $g < $n - 1; $g++){
        $giro = array_merge([$fisso], $lista);
        $partite = [];

        for($i = 0; $i < $meta; $i++){
            $casa   = $giro[$i];
            $ospite = $giro[$n - 1 - $i];

            if($casa === null || $ospite === null) continue;

            if($g % 2 === 0)
                $partite[] = [$casa, $ospite, 'andata'];
            else
                $partite[] = [$ospite, $casa, 'andata'];
        }
        $giornate[$g + 1] = $partite;

        // Rotazione
        array_unshift($lista, array_pop($lista));
    }

    // Se andata e ritorno duplica le giornate invertendo casa/ospite
    if($tipo_partita === 'andata_ritorno'){
        $tot = count($giornate);
        foreach($giornate as $g => $partite){
            $ritorno = [];
            foreach($partite as [$casa, $ospite, $_]){
                $ritorno[] = [$ospite, $casa, 'ritorno'];
            }
            $giornate[$g + $tot] = $ritorno;
        }
    }

    // INSERT nel DB nell'ordine giornata per giornata
    $stmt = $conn->prepare("
        INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, girone, tipo)
        VALUES (?, ?, ?, 1, ?)
    ");

    foreach($giornate as $partite){
        foreach ($partite as [$casa, $ospite, $tipo]) {
            $stmt->bind_param("iiis", $torneo_id, $casa, $ospite, $tipo);
            $stmt->execute();
        }
    }
}

function girone_classifica($conn, $torneo_id){

    $stmt = $conn->prepare("
        SELECT id, nome FROM squadra
        WHERE torneo_id = ? AND stato = 'approvata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $squadreRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $classifica = [];
    foreach($squadreRaw as $sq){
        $classifica[$sq['id']] = [
            'id' => $sq['id'], 'nome' => $sq['nome'],
            'G' => 0, 'V' => 0, 'P' => 0, 'S' => 0,
            'PF' => 0, 'PS' => 0, 'DP' => 0, 'Pts' => 0
        ];
    }

    $stmt = $conn->prepare("
        SELECT * FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato = 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach($partite as $p){
        $c  = $p['squadra_casa_id'];
        $o  = $p['squadra_ospite_id'];
        $pc = (int)$p['punti_casa'];
        $po = (int)$p['punti_ospite'];

        if(!isset($classifica[$c]) || !isset($classifica[$o])) continue;

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
        $b['Pts'] <=> $a['Pts']
        ?: $b['DP']  <=> $a['DP']
        ?: $b['PF']  <=> $a['PF']
        ?: strcmp($a['nome'], $b['nome'])
    );

    return array_values($classifica);
}

/* =====================================================
   GENERAZIONE AUTOMATICA
===================================================== */

if($torneo['stato'] === 'in_corso'){

    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $tot = $stmt->get_result()->fetch_assoc()['tot'];

    if($tot == 0)
        girone_genera_partite($conn, $torneo_id, $tipo_partita);
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
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errOrario");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET orario = ? WHERE id = ?");
        $stmt->bind_param("si", $orario, $partita_id);
        $stmt->execute();

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }

    // INSERIMENTO / AGGIORNAMENTO RISULTATO
    if(isset($_POST['partita_id'])){

        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

        if($casa < 0 || $ospite < 0){
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errPunti");
            exit;
        }

        // UPDATE: imposta sempre terminata (anche se si sta correggendo)
        $stmt = $conn->prepare("
            UPDATE partita
            SET punti_casa = ?, punti_ospite = ?, stato = 'terminata'
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

        // Controlla quante partite mancano ancora
        $stmt = $conn->prepare("
            SELECT COUNT(*) as mancanti FROM partita
            WHERE torneo_id = ? AND girone IS NOT NULL AND stato != 'terminata'
        ");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];

        if($mancanti == 0){
            $stmt = $conn->prepare("UPDATE torneo SET stato = 'completato' WHERE id = ?");
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
        } else {
            // Se il torneo era stato marcato completato ma ora si sta correggendo un risultato,
            // riportalo in_corso
            $stmt = $conn->prepare("UPDATE torneo SET stato = 'in_corso' WHERE id = ? AND stato = 'completato'");
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
        }

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }
}

/* =====================================================
   DATI PER LA VIEW
===================================================== */

$classifica  = girone_classifica($conn, $torneo_id);
$nSquadre    = count($classifica);
$perGiornata = max(1, (int)floor($nSquadre / 2));

$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS casa, so.nome AS ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.girone IS NOT NULL
    ORDER BY p.id
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$tuttePartite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Raggruppa per giornata in base all'ordine di inserimento
$giornate = [];
foreach($tuttePartite as $i => $p){
    $g = (int)floor($i / $perGiornata) + 1;
    $giornate[$g][] = $p;
}

/* =====================================================
   VIEW
===================================================== */
$extra_css = ['/css/tabella_tornei.css', '/css/torneo_struttura.css'];
require_once('templates/header.php');
?>



<?php
include("components/navbar_torneo.php")
?>

<main class="m-page">
    <div class="m-container">

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Info torneo
            </a>
            <a href="struttura_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab m-tab--active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                Struttura torneo
            </a>
            <?php if ($torneo['stato'] === 'in_corso' && $torneo['pranzo']==1): ?>
                <a href="gestione_pranzi.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                Gestione pranzi
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php $errs = ['errPunti' => 'Valori negativi non validi.', 'errOrario' => 'Inserisci un orario valido.']; ?>
            <?php if (isset($errs[$_GET['msg']])): ?>
                <div class="m-alert m-alert--danger m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div><?= htmlspecialchars($errs[$_GET['msg']]) ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="m-row m-mb-5">
            <a href="?id=<?= (int)$torneo_id ?>&view=classifica" class="m-btn <?= $view === 'classifica' ? 'm-btn--primary' : 'm-btn--ghost' ?> m-btn--sm">Classifica</a>
            <a href="?id=<?= (int)$torneo_id ?>&view=partite"    class="m-btn <?= $view === 'partite'    ? 'm-btn--primary' : 'm-btn--ghost' ?> m-btn--sm">Partite</a>
        </div>

        <?php if ($view === 'classifica'): ?>

            <div class="m-table-wrap">
                <table class="m-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Squadra</th>
                            <th class="m-num">G</th><th class="m-num">V</th><th class="m-num">P</th><th class="m-num">S</th>
                            <th class="m-num">PF</th><th class="m-num">PS</th><th class="m-num">DP</th><th class="m-num">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classifica as $pos => $sq):
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: /* VIEW PARTITE */ ?>

            <?php if (empty($tuttePartite)): ?>
                <div class="m-empty">
                    <div class="m-empty__icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
                    </div>
                    <h3>Nessuna partita generata</h3>
                    <p class="m-muted">Le partite saranno generate all'avvio del torneo.</p>
                </div>
            <?php else: ?>

                <?php foreach ($giornate as $numGiornata => $righe): ?>
                    <h3 class="m-mb-3">Giornata <?= (int)$numGiornata ?></h3>
                    <div class="m-table-wrap m-mb-5">
                        <table class="m-table">
                            <thead>
                                <tr>
                                    <th>Casa</th>
                                    <th>Ospite</th>
                                    <?php if ($tipo_partita === 'andata_ritorno'): ?><th>Tipo</th><?php endif; ?>
                                    <th>Orario</th>
                                    <th>Risultato</th>
                                    <?php if ($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($righe as $row):
                                    $finita = $row['stato'] === 'terminata';
                                ?>
                                    <tr>
                                        <td><b><?= htmlspecialchars($row['casa']) ?></b></td>
                                        <td><b><?= htmlspecialchars($row['ospite']) ?></b></td>
                                        <?php if ($tipo_partita === 'andata_ritorno'): ?>
                                            <td><span class="m-badge m-badge--neutral"><?= htmlspecialchars(ucfirst($row['tipo'])) ?></span></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if (!empty($row['orario'])): ?>
                                                <span class="m-mono"><?= htmlspecialchars(date('d/m H:i', strtotime($row['orario']))) ?></span>
                                            <?php else: ?>
                                                <span class="m-muted" style="font-style: italic;">non impostato</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($finita): ?>
                                                <b class="m-num"><?= (int)$row['punti_casa'] ?> &ndash; <?= (int)$row['punti_ospite'] ?></b>
                                            <?php else: ?>
                                                <span class="m-muted">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($isOrganizzatore): ?>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                                    <?php if (!$finita): ?>
                                                        <?php var_dump(function_exists('csrf_field')); ?>
                                                        <form method="POST" style="display: flex; gap: 4px;">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="partita_id_orario" value="<?= (int)$row['id'] ?>">
                                                            <input class="m-input" type="datetime-local" name="orario" style="padding: 4px 8px; font-size: 12px;">
                                                            <button class="m-btn m-btn--secondary m-btn--sm">Orarioo</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="partita_id" value="<?= (int)$row['id'] ?>">
                                                        <input class="m-input m-num" type="number" name="casa" min="0" required
                                                            value="<?= $finita ? (int)$row['punti_casa'] : '' ?>"
                                                            style="width: 50px; padding: 4px; text-align: center;">
                                                        <span class="m-muted">&ndash;</span>
                                                        <input class="m-input m-num" type="number" name="ospite" min="0" required
                                                            value="<?= $finita ? (int)$row['punti_ospite'] : '' ?>"
                                                            style="width: 50px; padding: 4px; text-align: center;">
                                                        <button class="m-btn <?= $finita ? 'm-btn--secondary' : 'm-btn--primary' ?> m-btn--sm"
                                                                title="<?= $finita ? 'Modifica risultato' : 'Inserisci risultato' ?>">
                                                            <?= $finita ? '&#9998;' : 'OK' ?>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>