<?php
require_once __DIR__ . '/../php/helpers/sport_config.php';

$tipo_partita = $torneo['tipo_partita']; // 'andata' | 'andata_ritorno'
$sport        = $torneo['sport'] ?? 'calcio';
$sport_cfg    = sport_cfg($sport);

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

    if($n % 2 !== 0){ $squadre[] = null; $n++; }

    $meta = $n / 2;
    $giornate = [];
    $lista = $squadre;
    $fisso = array_shift($lista);

    for($g = 0; $g < $n - 1; $g++){
        $giro   = array_merge([$fisso], $lista);
        $partite = [];
        for($i = 0; $i < $meta; $i++){
            $casa   = $giro[$i];
            $ospite = $giro[$n - 1 - $i];
            if($casa === null || $ospite === null) continue;
            $partite[] = [$casa, $ospite, 'andata'];
        }
        $giornate[$g + 1] = $partite;
        array_unshift($lista, array_pop($lista));
    }

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

function girone_classifica($conn, $torneo_id, $sport){
    $stmt = $conn->prepare("
        SELECT id, nome FROM squadra
        WHERE torneo_id = ? AND stato = 'approvata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $squadreRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("
        SELECT * FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato = 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return calcola_classifica($squadreRaw, $partite, $sport);
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

    if(isset($_POST['partita_id'])){
        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

        if($casa < 0 || $ospite < 0){
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errPunti");
            exit;
        }

        // Sport senza pareggio: blocca parità nella fase a girone
        if (!$sport_cfg['ha_pareggio'] && $casa === $ospite) {
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errRisultato");
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE partita
            SET punti_casa = ?, punti_ospite = ?, stato = 'terminata'
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

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

$classifica  = girone_classifica($conn, $torneo_id, $sport);
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

<?php include("components/navbar_torneo.php") ?>

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

        <?php
        $errs = [
            'errPunti'     => 'Valori negativi non validi.',
            'errOrario'    => 'Inserisci un orario valido.',
            'errRisultato' => 'In ' . htmlspecialchars($sport_cfg['label']) . ' non sono ammessi pareggi.',
        ];
        if (isset($_GET['msg']) && isset($errs[$_GET['msg']])): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div><?= $errs[$_GET['msg']] ?></div>
            </div>
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
                            <th>#</th>
                            <th>Squadra</th>
                            <th class="m-num" title="Partite giocate">G</th>
                            <th class="m-num" title="Vittorie">V</th>
                            <?php if ($sport_cfg['ha_pareggio']): ?>
                                <th class="m-num" title="Pareggi">P</th>
                            <?php endif; ?>
                            <th class="m-num" title="Sconfitte">S</th>
                            <?php if ($sport_cfg['ha_pareggio']): ?>
                                <th class="m-num" title="<?= htmlspecialchars($sport_cfg['score_label']) ?> fatti">PF</th>
                                <th class="m-num" title="<?= htmlspecialchars($sport_cfg['score_label']) ?> subiti">PS</th>
                                <th class="m-num" title="Differenza <?= htmlspecialchars($sport_cfg['score_label']) ?>">D<?= strtoupper(substr($sport_cfg['score_label'], 0, 1)) ?></th>
                            <?php endif; ?>
                            <th class="m-num" title="Punti in classifica">Pts</th>
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
                                <?php if ($sport_cfg['ha_pareggio']): ?>
                                    <td class="m-num"><?= (int)$sq['P'] ?></td>
                                <?php endif; ?>
                                <td class="m-num"><?= (int)$sq['S'] ?></td>
                                <?php if ($sport_cfg['ha_pareggio']): ?>
                                    <td class="m-num"><?= (int)$sq['PF'] ?></td>
                                    <td class="m-num"><?= (int)$sq['PS'] ?></td>
                                    <td class="m-num"><?= (int)$sq['DP'] ?></td>
                                <?php endif; ?>
                                <td class="m-num"><b><?= (int)$sq['Pts'] ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$sport_cfg['ha_pareggio']): ?>
                <p class="m-muted" style="font-size:12px; margin-top:8px;">
                    <?= htmlspecialchars($sport_cfg['emoji']) ?>
                    In <?= htmlspecialchars($sport_cfg['label']) ?> non sono previsti pareggi.
                    Ogni vittoria vale <?= (int)$sport_cfg['pts_vittoria'] ?> punti.
                </p>
            <?php endif; ?>

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
                                                        <form method="POST" style="display: flex; gap: 4px;">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="partita_id_orario" value="<?= (int)$row['id'] ?>">
                                                            <input class="m-input" type="datetime-local" name="orario" style="padding: 4px 8px; font-size: 12px;">
                                                            <button class="m-btn m-btn--secondary m-btn--sm">Orario</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form class="js-risultato-form" method="POST"
                                                          data-partita-id="<?= (int)$row['id'] ?>"
                                                          style="display: flex; gap: 4px; align-items: center;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="partita_id" value="<?= (int)$row['id'] ?>">
                                                        <input class="m-input m-num js-input-casa" type="number" name="casa" min="0" required
                                                            value="<?= $finita ? (int)$row['punti_casa'] : '' ?>"
                                                            style="width: 50px; padding: 4px; text-align: center;">
                                                        <span class="m-muted">&ndash;</span>
                                                        <input class="m-input m-num js-input-ospite" type="number" name="ospite" min="0" required
                                                            value="<?= $finita ? (int)$row['punti_ospite'] : '' ?>"
                                                            style="width: 50px; padding: 4px; text-align: center;">
                                                        <button class="m-btn <?= $finita ? 'm-btn--secondary' : 'm-btn--primary' ?> m-btn--sm js-risultato-btn"
                                                                title="<?= $finita ? 'Modifica risultato' : 'Inserisci risultato' ?>">
                                                            <?= $finita ? '&#9998;' : 'OK' ?>
                                                        </button>
                                                        <span class="js-risultato-msg" style="font-size:11px; display:none;"></span>
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
