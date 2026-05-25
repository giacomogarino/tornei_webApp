<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

include_once("conf/db_config.php");

$torneo_id = (int)($_GET['torneo_id'] ?? $_POST['torneo_id'] ?? $_GET['id'] ?? 0);

if(!$torneo_id){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

/* CARICA TORNEO */
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

if(!isset($_SESSION['id_utente']) || $_SESSION['id_utente'] != $torneo['creato_da']){
    header("Location: dettagli_torneo.php?id=$torneo_id&msg=err");
    exit;
}

function utente_gia_in_squadra(mysqli $conn, int $torneo_id, int $utente_id): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM giocatore_squadra gs
        JOIN squadra s ON s.id = gs.squadra_id
        WHERE s.torneo_id = ?
          AND gs.utente_id = ?
          AND s.stato IN ('in_attesa', 'approvata')
        LIMIT 1
    ");
    $stmt->bind_param("ii", $torneo_id, $utente_id);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

if(
    !isset($_SESSION['wizard_manuale']) ||
    ($_SESSION['wizard_manuale']['torneo_id'] ?? 0) != $torneo_id
){
    $_SESSION['wizard_manuale'] = [
        'torneo_id'    => $torneo_id,
        'nome_squadra' => '',
        'capitano_id'  => null,
        'giocatori'    => []
    ];
}

$w = &$_SESSION['wizard_manuale'];

$step        = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$cerca       = trim($_POST['cerca'] ?? $_GET['cerca'] ?? '');
$cerca_cap   = trim($_POST['cerca_cap'] ?? $_GET['cerca_cap'] ?? '');
$errori      = [];

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $azione = $_POST['azione'] ?? '';

    /*  STEP 1: salva nome squadra  */
    if($step === 1){
        $nome_squadra = trim($_POST['nome_squadra'] ?? '');

        if($nome_squadra === ''){
            $errori[] = "Inserisci il nome della squadra.";
        } else {
            $stmt_dup = $conn->prepare("
                SELECT COUNT(*) cnt FROM squadra
                WHERE torneo_id = ? AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
                AND stato IN ('in_attesa','approvata')
            ");
            $stmt_dup->bind_param("is", $torneo_id, $nome_squadra);
            $stmt_dup->execute();
            if($stmt_dup->get_result()->fetch_assoc()['cnt'] > 0){
                $errori[] = "Esiste gi una squadra con questo nome. Scegline un altro.";
            }
        }

        if(empty($errori)){
            $w['nome_squadra'] = $nome_squadra;
            if($azione === 'avanti'){
                header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2");
                exit;
            }
        }
    }

    /*  STEP 2: scegli capitano  */
    if(isset($_POST['imposta_capitano'])){
        $cap_id = (int)$_POST['imposta_capitano'];

        $stmt_u = $conn->prepare("SELECT id FROM utente WHERE id = ?");
        $stmt_u->bind_param("i", $cap_id);
        $stmt_u->execute();
        if(!$stmt_u->get_result()->fetch_assoc()){
            $errori[] = "Utente non trovato.";
        } elseif(utente_gia_in_squadra($conn, $torneo_id, $cap_id)) {
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca_cap=" . urlencode($cerca_cap) . "&msg=errCapiOccupato");
            exit;
        } else {
            $w['capitano_id'] = $cap_id;
            if(!in_array($cap_id, $w['giocatori'])){
                array_unshift($w['giocatori'], $cap_id);
            } else {
                $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g != $cap_id));
                array_unshift($w['giocatori'], $cap_id);
            }
        }

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca_cap=" . urlencode($cerca_cap));
        exit;
    }

    if($azione === 'avanti' && $step === 2){
        if(!$w['capitano_id']){
            $errori[] = "Devi selezionare un capitano prima di procedere.";
        } elseif(utente_gia_in_squadra($conn, $torneo_id, $w['capitano_id'])){
            $w['capitano_id'] = null;
            $w['giocatori']   = [];
            $errori[] = "Il capitano selezionato  stato nel frattempo iscritto a un'altra squadra. Selezionane un altro.";
        } else {
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3");
            exit;
        }
    }

    /*  STEP 3: aggiungi/rimuovi giocatori  */
    if(isset($_POST['aggiungi_id'])){
        $id = (int)$_POST['aggiungi_id'];

        $stmt_dbg = $conn->prepare("
            SELECT s.id, s.stato, gs.utente_id
            FROM giocatore_squadra gs
            JOIN squadra s ON s.id = gs.squadra_id
            WHERE s.torneo_id = ? AND gs.utente_id = ?
        ");
        $stmt_dbg->bind_param("ii", $torneo_id, $id);
        $stmt_dbg->execute();
        $dbg_rows = $stmt_dbg->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("DEBUG aggiungi_id=$id torneo_id=$torneo_id rows=" . json_encode($dbg_rows));

        if(utente_gia_in_squadra($conn, $torneo_id, $id)){
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3&cerca=" . urlencode($cerca) . "&msg=errGiocatoreOccupato");
            exit;
        }

        $max = $torneo['max_giocatori_per_squadra'] ?? 999;
        if(!in_array($id, $w['giocatori']) && count($w['giocatori']) < $max)
            $w['giocatori'][] = $id;

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3&cerca=" . urlencode($cerca));
        exit;
    }

    if(isset($_POST['rimuovi_id'])){
        $id = (int)$_POST['rimuovi_id'];
        if($id != $w['capitano_id'])
            $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g != $id));

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3&cerca=" . urlencode($cerca));
        exit;
    }

    if($azione === 'avanti' && $step === 3){
        $min = $torneo['min_giocatori_per_squadra'] ?? 1;
        if(count($w['giocatori']) < $min){
            $errori[] = "Servono almeno $min giocatori (attuali: " . count($w['giocatori']) . ").";
        } else {
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=4");
            exit;
        }
    }

    if($azione === 'indietro'){
        $prev = max(1, $step - 1);
        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=$prev");
        exit;
    }

    /*  STEP 4: CREA SQUADRA  */
    if($azione === 'crea' && $step === 4){

        $stmt_cnt = $conn->prepare("SELECT COUNT(*) cnt FROM squadra WHERE torneo_id = ?");
        $stmt_cnt->bind_param("i", $torneo_id);
        $stmt_cnt->execute();
        $tot = $stmt_cnt->get_result()->fetch_assoc()['cnt'];

        if($tot >= $torneo['numero_squadre']){
            $errori[] = "Limite massimo di squadre raggiunto ({$torneo['numero_squadre']}).";
        } else {
            $stmt_dup2 = $conn->prepare("
                SELECT COUNT(*) cnt FROM squadra
                WHERE torneo_id = ? AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
                AND stato IN ('in_attesa','approvata')
            ");
            $stmt_dup2->bind_param("is", $torneo_id, $w['nome_squadra']);
            $stmt_dup2->execute();

            if($stmt_dup2->get_result()->fetch_assoc()['cnt'] > 0){
                $errori[] = "Il nome squadra  gi stato preso. Torna indietro e scegline un altro.";
            } else {
                $conflitti = [];
                foreach($w['giocatori'] as $uid){
                    if(utente_gia_in_squadra($conn, $torneo_id, $uid)){
                        $st = $conn->prepare("SELECT nome, cognome FROM utente WHERE id=?");
                        $st->bind_param("i", $uid);
                        $st->execute();
                        $u = $st->get_result()->fetch_assoc();
                        $nome_u = $u ? "{$u['nome']} {$u['cognome']}" : "Utente #$uid";

                        if($uid == $w['capitano_id']){
                            $conflitti[] = "Il capitano $nome_u  gi iscritto a un'altra squadra di questo torneo. Devi tornare allo step 2 e sceglierne un altro.";
                            $w['capitano_id'] = null;
                            $w['giocatori']   = [];
                        } else {
                            $conflitti[] = "$nome_u  gi iscritto a un'altra squadra di questo torneo ed  stato rimosso dalla lista.";
                            $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g !== $uid));
                        }
                    }
                }

                if(!empty($conflitti)){
                    foreach($conflitti as $c) $errori[] = $c;
                } else {
                    $conn->begin_transaction();
                    try{
                        $stmt_sq = $conn->prepare("
                            INSERT INTO squadra (torneo_id, nome, capitano_id, stato)
                            VALUES (?, ?, ?, 'approvata')
                        ");
                        $stmt_sq->bind_param("isi", $torneo_id, $w['nome_squadra'], $w['capitano_id']);
                        $stmt_sq->execute();
                        $squadra_id = $conn->insert_id;

                        $stmt_gj = $conn->prepare("
                            INSERT INTO giocatore_squadra (squadra_id, utente_id) VALUES (?, ?)
                        ");
                        foreach($w['giocatori'] as $uid){
                            $stmt_gj->bind_param("ii", $squadra_id, $uid);
                            $stmt_gj->execute();
                        }

                        $conn->commit();
                        unset($_SESSION['wizard_manuale']);

                        header("Location: aggiunta_squadre_manualmente.php?id=$torneo_id&msg=ok");
                        exit;

                    } catch(Exception $e){
                        $conn->rollback();
                        $errori[] = "Errore durante il salvataggio: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

/* CARICA DATI GIOCATORI SELEZIONATI */
$giocatori_dati = [];
if(!empty($w['giocatori'])){
    $ids = implode(",", array_map("intval", $w['giocatori']));
    $res = $conn->query("SELECT id, nome, cognome, email FROM utente WHERE id IN ($ids)");
    while($r = $res->fetch_assoc())
        $giocatori_dati[$r['id']] = $r;
}

/* RICERCA CAPITANO (step 2) */
$risultati_cap = [];
$occupati_cap  = [];
if($step === 2 && $cerca_cap !== ''){
    $q = "%$cerca_cap%";
    $escludi_ids = array_values(array_unique(array_filter(
        array_merge(
            $w['capitano_id'] ? [$w['capitano_id']] : [],
            $w['giocatori']
        )
    )));
    $exclude_sql = count($escludi_ids)
        ? implode(",", array_map("intval", $escludi_ids))
        : "0";

    $stmt_rc = $conn->prepare("
        SELECT id, nome, cognome, email
        FROM utente
        WHERE (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)
          AND id NOT IN ($exclude_sql)
        LIMIT 10
    ");
    $stmt_rc->bind_param("sss", $q, $q, $q);
    $stmt_rc->execute();
    $res_rc = $stmt_rc->get_result();
    while($r = $res_rc->fetch_assoc())
        $risultati_cap[] = $r;

    foreach($risultati_cap as $r){
        if(utente_gia_in_squadra($conn, $torneo_id, $r['id']))
            $occupati_cap[] = $r['id'];
    }
}

/* RICERCA GIOCATORI (step 3) */
$risultati = [];
$occupati  = [];
if($step === 3 && $cerca !== ''){
    $q       = "%$cerca%";
    $exclude = count($w['giocatori'])
               ? implode(",", array_map("intval", $w['giocatori']))
               : "0";

    $stmt_r = $conn->prepare("
        SELECT id, nome, cognome, email
        FROM utente
        WHERE (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)
          AND id NOT IN ($exclude)
        LIMIT 10
    ");
    $stmt_r->bind_param("sss", $q, $q, $q);
    $stmt_r->execute();
    $res_r = $stmt_r->get_result();
    while($r = $res_r->fetch_assoc())
        $risultati[] = $r;

    foreach($risultati as $r){
        if(utente_gia_in_squadra($conn, $torneo_id, $r['id']))
            $occupati[] = $r['id'];
    }
}

/* SQUADRE GI INSERITE (lista in fondo) */
$stmt_sq = $conn->prepare("
    SELECT s.*, u.nome AS n, u.cognome AS c
    FROM squadra s
    JOIN utente u ON s.capitano_id = u.id
    WHERE s.torneo_id = ?
    ORDER BY s.id DESC
");
$stmt_sq->bind_param("i", $torneo_id);
$stmt_sq->execute();
$squadre_inserite = $stmt_sq->get_result()->fetch_all(MYSQLI_ASSOC);

require_once('templates/header_riservato.php');

function iniziali($nome, $cognome=''){
    $a = mb_substr(trim($nome), 0, 1);
    $b = mb_substr(trim($cognome), 0, 1);
    return strtoupper($a . $b) ?: 'U';
}
function step_class($n, $cur){
    if ($n < $cur) return 'm-step m-step--done';
    if ($n === $cur) return 'm-step m-step--current';
    return 'm-step';
}
?>

<main class="m-page">
    <div class="m-container" style="max-width: 980px;">

        <div style="margin-bottom: var(--m-4); font-size: 13px;">
            <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" style="color: var(--m-text-mute);"> Torna al torneo</a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Aggiunta squadra manuale</h1>
                <div class="m-page-head__sub">
                    Torneo: <b style="color: var(--m-primary-700)"><?= htmlspecialchars($torneo['nome']) ?></b>
                     Squadre <b><?= count($squadre_inserite) ?>/<?= (int)$torneo['numero_squadre'] ?></b>
                </div>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] === 'ok'): ?>
                <div class="m-alert m-alert--success m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>Squadra creata con successo.</div>
                </div>
            <?php elseif($_GET['msg'] === 'limite'): ?>
                <div class="m-alert m-alert--danger m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div>Limite massimo di squadre raggiunto.</div>
                </div>
            <?php elseif($_GET['msg'] === 'errCapiOccupato'): ?>
                <div class="m-alert m-alert--warn m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div>Questo utente  gi capitano o giocatore di un'altra squadra in questo torneo.</div>
                </div>
            <?php elseif($_GET['msg'] === 'errGiocatoreOccupato'): ?>
                <div class="m-alert m-alert--warn m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div>Questo giocatore  gi iscritto a un'altra squadra in questo torneo.</div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if(!empty($errori)): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div><?php foreach($errori as $e): ?><p style="margin:0"><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <div class="m-stepper">
            <div class="<?= step_class(1, $step) ?>">
                <span class="m-step__num"><?= $step > 1 ? '' : '1' ?></span>
                <div class="m-step__text"><span class="m-step__label">Step 1</span><span class="m-step__title">Nome</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(2, $step) ?>">
                <span class="m-step__num"><?= $step > 2 ? '' : '2' ?></span>
                <div class="m-step__text"><span class="m-step__label">Step 2</span><span class="m-step__title">Capitano</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(3, $step) ?>">
                <span class="m-step__num"><?= $step > 3 ? '' : '3' ?></span>
                <div class="m-step__text"><span class="m-step__label">Step 3</span><span class="m-step__title">Giocatori</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(4, $step) ?>">
                <span class="m-step__num">4</span>
                <div class="m-step__text"><span class="m-step__label">Step 4</span><span class="m-step__title">Conferma</span></div>
            </div>
        </div>

        <?php if($step === 1): ?>
            <form method="POST" class="m-card" style="padding: var(--m-6); max-width: 520px;">
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                <input type="hidden" name="step" value="1">

                <h3 style="margin: 0 0 var(--m-2);">Nome squadra</h3>
                <p class="m-muted m-mb-5">Scegli un nome unico per la squadra che stai creando.</p>

                <div class="m-field">
                    <label class="m-label" for="nome_squadra">Nome squadra</label>
                    <input class="m-input" type="text" id="nome_squadra" name="nome_squadra"
                           value="<?= htmlspecialchars($w['nome_squadra']) ?>" placeholder="Inserisci il nome" required>
                </div>

                <div class="m-row-between m-mt-6" style="padding-top: var(--m-4); border-top: 1px solid var(--m-border);">
                    <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" class="m-btn m-btn--ghost">Annulla</a>
                    <button name="azione" value="avanti" class="m-btn m-btn--primary">
                        Avanti
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </form>

        <?php elseif($step === 2): ?>
            <div class="m-card" style="padding: var(--m-6);">
                <?php if($w['capitano_id'] && isset($giocatori_dati[$w['capitano_id']])): $cap = $giocatori_dati[$w['capitano_id']]; ?>
                    <div class="m-alert m-alert--success m-mb-5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                        <div>
                            <b>Capitano selezionato:</b> <?= htmlspecialchars($cap['nome'] . ' ' . $cap['cognome']) ?>
                            <span class="m-muted">(<?= htmlspecialchars($cap['email']) ?>)</span>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="m-muted m-mb-5"><em>Nessun capitano selezionato. Cerca un utente e selezionalo.</em></p>
                <?php endif; ?>

                <form method="GET" class="m-mb-5">
                    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                    <input type="hidden" name="step" value="2">
                    <div class="m-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                        <input class="m-input" type="search" name="cerca_cap" value="<?= htmlspecialchars($cerca_cap) ?>" placeholder="Cerca capitano per nome, cognome o email">
                    </div>
                </form>

                <?php if($cerca_cap !== '' && empty($risultati_cap)): ?>
                    <p class="m-muted"><em>Nessun utente trovato.</em></p>
                <?php endif; ?>

                <?php if($risultati_cap): ?>
                    <div style="border: 1px solid var(--m-border); border-radius: var(--m-r-sm); overflow: hidden;">
                        <?php foreach($risultati_cap as $r): $is_occ = in_array($r['id'], $occupati_cap); ?>
                            <div style="display: grid; grid-template-columns: 36px 1fr auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $is_occ ? 'opacity: 0.55;' : '' ?> border-top: 1px solid var(--m-border);">
                                <span class="m-avatar"><?= iniziali($r['nome'], $r['cognome']) ?></span>
                                <div>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></div>
                                    <div class="m-muted" style="font-size: 12px;"><?= htmlspecialchars($r['email']) ?></div>
                                </div>
                                <?php if($is_occ): ?>
                                    <span class="m-badge m-badge--warn">Gi in squadra</span>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                                        <input type="hidden" name="step" value="2">
                                        <input type="hidden" name="cerca_cap" value="<?= htmlspecialchars($cerca_cap) ?>">
                                        <input type="hidden" name="imposta_capitano" value="<?= $r['id'] ?>">
                                        <button class="m-btn m-btn--gold m-btn--sm">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                                            Scegli come capitano
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="m-row-between m-mt-6" style="padding-top: var(--m-4); border-top: 1px solid var(--m-border);">
                    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                    <input type="hidden" name="step" value="2">
                    <button name="azione" value="indietro" class="m-btn m-btn--ghost"> Indietro</button>
                    <button name="azione" value="avanti" class="m-btn m-btn--primary">Avanti </button>
                </form>
            </div>

        <?php elseif($step === 3): ?>
            <div class="m-grid" style="grid-template-columns: 1fr 320px; gap: var(--m-5);">

                <section>
                    <div class="m-card">
                        <div class="m-card__header">
                            <h3 class="m-card__title">Aggiungi altri giocatori</h3>
                        </div>

                        <form method="GET" class="m-mb-5">
                            <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                            <input type="hidden" name="step" value="3">
                            <div class="m-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                                <input class="m-input" type="search" name="cerca" value="<?= htmlspecialchars($cerca) ?>" placeholder="Cerca per nome, cognome o email">
                            </div>
                        </form>

                        <?php if($cerca !== '' && empty($risultati)): ?>
                            <p class="m-muted"><em>Nessun utente trovato.</em></p>
                        <?php endif; ?>

                        <?php if($risultati): ?>
                            <div style="border: 1px solid var(--m-border); border-radius: var(--m-r-sm); overflow: hidden;">
                                <?php foreach($risultati as $r): $is_occ = in_array($r['id'], $occupati); ?>
                                    <div style="display: grid; grid-template-columns: 36px 1fr auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $is_occ ? 'opacity: 0.55;' : '' ?> border-top: 1px solid var(--m-border);">
                                        <span class="m-avatar"><?= iniziali($r['nome'], $r['cognome']) ?></span>
                                        <div>
                                            <div style="font-weight: 500;<?= $is_occ ? ' text-decoration: line-through;' : '' ?>"><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></div>
                                            <div class="m-muted" style="font-size: 12px;"><?= htmlspecialchars($r['email']) ?></div>
                                        </div>
                                        <?php if($is_occ): ?>
                                            <span class="m-badge m-badge--warn">Gi in squadra</span>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                                                <input type="hidden" name="step" value="3">
                                                <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                                <input type="hidden" name="aggiungi_id" value="<?= $r['id'] ?>">
                                                <button class="m-btn m-btn--secondary m-btn--sm">+ Aggiungi</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <aside>
                    <div class="m-card" style="position: sticky; top: calc(var(--m-navbar-h) + var(--m-3));">
                        <h4 class="m-profile-section-label">Squadra in costruzione</h4>
                        <div style="font-family: var(--m-font-display); font-size: 20px; font-weight: 700; margin-bottom: 4px;"><?= htmlspecialchars($w['nome_squadra']) ?></div>
                        <div class="m-muted" style="font-size: 13px; margin-bottom: var(--m-3);">
                            <b style="color: var(--m-text);"><?= count($w['giocatori']) ?> / <?= (int)($torneo['max_giocatori_per_squadra'] ?? 0) ?></b> giocatori
                        </div>

                        <div style="display: flex; flex-direction: column; gap: var(--m-2);">
                            <?php foreach($w['giocatori'] as $id): $g = $giocatori_dati[$id] ?? null; if(!$g) continue; $is_cap = ($id == $w['capitano_id']); ?>
                                <div style="display: grid; grid-template-columns: 32px 1fr auto; gap: 10px; align-items: center; padding: 8px; <?= $is_cap ? 'background: var(--m-gold-50); border-radius: 8px;' : '' ?>">
                                    <span class="m-avatar" style="width: 32px; height: 32px;<?= $is_cap ? ' background: linear-gradient(135deg, var(--m-gold-400), var(--m-gold-600)); color: #2a1d00;' : '' ?>"><?= iniziali($g['nome'], $g['cognome']) ?></span>
                                    <div>
                                        <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?></div>
                                        <?php if($is_cap): ?><div class="m-muted" style="font-size: 11px;">Capitano</div><?php endif; ?>
                                    </div>
                                    <?php if($is_cap): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--m-gold-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                                            <input type="hidden" name="step" value="3">
                                            <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                            <input type="hidden" name="rimuovi_id" value="<?= $id ?>">
                                            <button class="m-btn m-btn--ghost" style="padding: 4px; width: auto; height: auto;" aria-label="Rimuovi">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" style="display: flex; gap: var(--m-2); margin-top: var(--m-4); padding-top: var(--m-4); border-top: 1px dashed var(--m-border);">
                            <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                            <input type="hidden" name="step" value="3">
                            <button name="azione" value="indietro" class="m-btn m-btn--ghost" style="flex: 1;"> Indietro</button>
                            <button name="azione" value="avanti" class="m-btn m-btn--primary" style="flex: 2;">Avanti </button>
                        </form>
                    </div>
                </aside>
            </div>

        <?php elseif($step === 4): ?>
            <div class="m-card" style="padding: var(--m-6);">
                <h3 style="margin: 0 0 var(--m-2);">Riepilogo</h3>
                <p class="m-muted m-mb-5">La squadra verr creata e approvata automaticamente.</p>

                <dl style="display: grid; grid-template-columns: 180px 1fr; gap: var(--m-3) var(--m-4); font-size: 14px; margin: 0;">
                    <dt class="m-muted">Nome squadra</dt><dd style="margin: 0; font-weight: 600;"><?= htmlspecialchars($w['nome_squadra']) ?></dd>
                    <dt class="m-muted">Giocatori (<?= count($w['giocatori']) ?>)</dt>
                    <dd style="margin: 0;">
                        <ul style="margin: 0; padding-left: 18px;">
                            <?php foreach($w['giocatori'] as $id): $g = $giocatori_dati[$id] ?? null; if(!$g) continue; ?>
                                <li>
                                    <?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?>
                                    <?php if($id == $w['capitano_id']): ?><span class="m-badge m-badge--gold" style="margin-left:6px;">Capitano</span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </dd>
                </dl>

                <form method="POST" class="m-row-between" style="margin-top: var(--m-6); padding-top: var(--m-4); border-top: 1px solid var(--m-border);">
                    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                    <input type="hidden" name="step" value="4">
                    <button name="azione" value="indietro" class="m-btn m-btn--ghost"> Indietro</button>
                    <button name="azione" value="crea" class="m-btn m-btn--primary m-btn--lg">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Crea squadra
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <hr>

        <h3>Squadre gi inserite</h3>
        <?php if(empty($squadre_inserite)): ?>
            <p class="m-muted">Nessuna squadra ancora.</p>
        <?php else: ?>
            <div class="m-table-wrap">
                <table class="m-table">
                    <thead>
                        <tr><th>Nome</th><th>Capitano</th><th>Stato</th><th class="m-num">Giocatori</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($squadre_inserite as $sq):
                            $stmt_gc = $conn->prepare("SELECT COUNT(*) cnt FROM giocatore_squadra WHERE squadra_id = ?");
                            $stmt_gc->bind_param("i", $sq['id']);
                            $stmt_gc->execute();
                            $num_g = $stmt_gc->get_result()->fetch_assoc()['cnt'];
                            $stato_class = 'm-state-' . htmlspecialchars($sq['stato']);
                        ?>
                            <tr>
                                <td><b><?= htmlspecialchars($sq['nome']) ?></b></td>
                                <td><?= htmlspecialchars($sq['n'] . ' ' . $sq['c']) ?></td>
                                <td><span class="m-badge m-badge--dot <?= $stato_class ?>"><?= htmlspecialchars($sq['stato']) ?></span></td>
                                <td class="m-num"><?= $num_g ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
