<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include("conf/db_config.php");

$utente_id = $_SESSION['id_utente'] ?? null;
if(!$utente_id){
    header("Location: login.php");
    exit;
}

$torneo_id = (int)($_GET['torneo_id'] ?? $_POST['torneo_id'] ?? 0);
if(!$torneo_id){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM torneo WHERE id=?");
$stmt->bind_param("i",$torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}
if($torneo['stato'] !== 'aperto'){
    header("Location: dettagli_torneo.php?id=$torneo_id&msg=errTorneoChiuso");
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) cnt
    FROM squadra
    WHERE torneo_id=?
    AND stato IN ('approvata','in_attesa')
");
$stmt->bind_param("i",$torneo_id);
$stmt->execute();
$cnt = $stmt->get_result()->fetch_assoc()['cnt'];

if($cnt >= $torneo['numero_squadre']){
    header("Location: dettagli_torneo.php?id=$torneo_id&msg=errTorneoPieno");
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

if(utente_gia_in_squadra($conn, $torneo_id, $utente_id)){
    header("Location: dettagli_torneo.php?id=$torneo_id&msg=errGiaInSquadra");
    exit;
}

if(!isset($_SESSION['wizard_squadra']) || ($_SESSION['wizard_squadra']['torneo_id'] ?? 0) != $torneo_id){
    $_SESSION['wizard_squadra'] = [
        'torneo_id'   => $torneo_id,
        'nome_squadra'=> '',
        'giocatori'   => [$utente_id]
    ];
}

$w = &$_SESSION['wizard_squadra'];

if(!in_array($utente_id, $w['giocatori'])){
    array_unshift($w['giocatori'], $utente_id);
}

$step  = (int)($_GET['step'] ?? 1);
$cerca = trim($_GET['cerca'] ?? '');
$errori = [];
$msg_ricerca = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();

    $azione = $_POST['azione'] ?? '';

    /* STEP 1 - salva nome */
    if($step === 1){
        $w['nome_squadra'] = trim($_POST['nome_squadra'] ?? '');
        if($w['nome_squadra'] === ''){
            $errori[] = "Inserisci il nome della squadra.";
        } else {
            // Controlla nome duplicato nello stesso torneo
            $stmt_dup = $conn->prepare("
                SELECT COUNT(*) cnt
                FROM squadra
                WHERE torneo_id = ?
                AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
                AND stato IN ('in_attesa', 'approvata')
            ");
            $stmt_dup->bind_param("is", $torneo_id, $w['nome_squadra']);
            $stmt_dup->execute();
            $dup = $stmt_dup->get_result()->fetch_assoc()['cnt'];
            if($dup > 0){
                $errori[] = "Esiste gi una squadra con questo nome in questo torneo. Scegline un altro.";
            }
        }
    }

    /* AGGIUNGI GIOCATORE */
    if(isset($_POST['aggiungi_id'])){
        $id = (int)$_POST['aggiungi_id'];

        if(utente_gia_in_squadra($conn, $torneo_id, $id)){
            header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=2&cerca=".urlencode($cerca)."&msg=errGiocatoreOccupato");
            exit;
        }

        if(!in_array($id, $w['giocatori']) && count($w['giocatori']) < $torneo['max_giocatori_per_squadra'])
            $w['giocatori'][] = $id;

        header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=2&cerca=".urlencode($cerca));
        exit;
    }

    /* RIMUOVI GIOCATORE */
    if(isset($_POST['rimuovi_id'])){
        $id = (int)$_POST['rimuovi_id'];

        if($id != $utente_id)
            $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g)=>$g!=$id));

        header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=2&cerca=".urlencode($cerca));
        exit;
    }

    /* NAVIGAZIONE */
    if($azione === 'avanti'){
        if($step == 1){
            if(empty($errori)){
                header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=2");
                exit;
            }
        }

        if($step == 2){
            if(count($w['giocatori']) < $torneo['min_giocatori_per_squadra'])
                $errori[] = "Troppi pochi giocatori (minimo {$torneo['min_giocatori_per_squadra']}).";
            else{
                header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=3");
                exit;
            }
        }
    }

    if($azione === 'indietro'){
        $prev = max(1, $step-1);
        header("Location: aggiungi_squadra.php?torneo_id=$torneo_id&step=$prev");
        exit;
    }

    /* CREA SQUADRA */
    if($azione === 'crea' && $step == 3){

        // 1. Controlla nome duplicato (race condition)
        $stmt_dup2 = $conn->prepare("
            SELECT COUNT(*) cnt
            FROM squadra
            WHERE torneo_id = ?
            AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
            AND stato IN ('in_attesa', 'approvata')
        ");
        $stmt_dup2->bind_param("is", $torneo_id, $w['nome_squadra']);
        $stmt_dup2->execute();

        if($stmt_dup2->get_result()->fetch_assoc()['cnt'] > 0){

            $errori[] = "Il nome squadra  stato preso da un'altra squadra nel frattempo. Torna indietro e scegline un altro.";

        } else {

            // 2. Ri-valida tutti i giocatori prima dell'INSERT
            $conflitti = [];
            foreach($w['giocatori'] as $uid){
                if(utente_gia_in_squadra($conn, $torneo_id, $uid)){
                    if($uid === $utente_id){
                        $conflitti[] = "Sei gi iscritto a un'altra squadra di questo torneo.";
                    } else {
                        $st = $conn->prepare("SELECT nome, cognome FROM utente WHERE id=?");
                        $st->bind_param("i", $uid);
                        $st->execute();
                        $u = $st->get_result()->fetch_assoc();
                        $nome = $u ? "{$u['nome']} {$u['cognome']}" : "Utente #$uid";
                        $conflitti[] = "$nome  gi iscritto a un'altra squadra di questo torneo.";
                        $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g !== $uid));
                    }
                }
            }

            if(!empty($conflitti)){

                foreach($conflitti as $c) $errori[] = $c;

            } else {

                // 3. Tutto ok: salva
                $conn->begin_transaction();
                try{
                    $stmt = $conn->prepare("
                        INSERT INTO squadra (torneo_id, nome, capitano_id, stato)
                        VALUES (?, ?, ?, 'in_attesa')
                    ");
                    $stmt->bind_param("isi", $torneo_id, $w['nome_squadra'], $utente_id);
                    $stmt->execute();
                    $squadra_id = $conn->insert_id;

                    $stmt2 = $conn->prepare("
                        INSERT INTO giocatore_squadra (squadra_id, utente_id) VALUES (?, ?)
                    ");
                    foreach($w['giocatori'] as $uid){
                        $stmt2->bind_param("ii", $squadra_id, $uid);
                        $stmt2->execute();
                    }

                    // Genera token approvazione/rifiuto
                    $token_approva = bin2hex(random_bytes(32));
                    $token_rifiuta = bin2hex(random_bytes(32));

                    $stmt_tok = $conn->prepare("
                        UPDATE squadra SET token_approva=?, token_rifiuta=? WHERE id=?
                    ");
                    $stmt_tok->bind_param("ssi", $token_approva, $token_rifiuta, $squadra_id);
                    $stmt_tok->execute();

                    $conn->commit();

                    // Recupera dati organizzatore
                    $stmt_org = $conn->prepare("SELECT nome, cognome, email FROM utente WHERE id=?");
                    $stmt_org->bind_param("i", $torneo['creato_da']);
                    $stmt_org->execute();
                    $organizzatore = $stmt_org->get_result()->fetch_assoc();

                    // Recupera nomi giocatori
                    $ids_str = implode(",", array_map("intval", $w['giocatori']));
                    $res_g = $conn->query("SELECT nome, cognome FROM utente WHERE id IN ($ids_str)");
                    $lista_giocatori = "";
                    while($g = $res_g->fetch_assoc())
                        $lista_giocatori .= "- {$g['nome']} {$g['cognome']}\n";

                    // Costruisci e invia mail
                    $base_url     = "https://" . $_SERVER['HTTP_HOST'];
                    $link_approva = "$base_url/php/approva_squadra.php?token=$token_approva&azione=approva";
                    $link_rifiuta = "$base_url/php/approva_squadra.php?token=$token_rifiuta&azione=rifiuta";

                    $to      = $organizzatore['email'];
                    $subject = "Nuova richiesta squadra  {$torneo['nome']}";
                    $message =
                        "Ciao {$organizzatore['nome']},

                        Una nuova squadra ha richiesto di partecipare al torneo \"{$torneo['nome']}\".

                        Nome squadra : {$w['nome_squadra']}
                        Giocatori    :
                        $lista_giocatori
                        Per APPROVARE la squadra clicca qui:
                        $link_approva

                        Per RIFIUTARE la squadra clicca qui:
                        $link_rifiuta

                        ---
                        Questo messaggio  stato generato automaticamente.";

                    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
                            "Content-Type: text/plain; charset=UTF-8\r\n";

                    mail($to, $subject, $message, $headers);

                    unset($_SESSION['wizard_squadra']);
                    header("Location: dettagli_torneo.php?id=$torneo_id&squadra_inviata=1");
                    exit;

                } catch(Exception $e){
                    $conn->rollback();
                    $errori[] = "Errore salvataggio: " . $e->getMessage();
                }

            } // fine else $conflitti

        } // fine else nome duplicato

    } // fine if $azione === 'crea'
}

// Carica dati giocatori selezionati
$giocatori_dati = [];
if(!empty($w['giocatori'])){
    $ids = implode(",", array_map("intval", $w['giocatori']));
    $res = $conn->query("SELECT id,nome,cognome,email FROM utente WHERE id IN ($ids)");
    while($r = $res->fetch_assoc())
        $giocatori_dati[$r['id']] = $r;
}

// Ricerca giocatori (step 2)
$risultati = [];
if($step == 2 && $cerca !== ''){
    $q       = "%$cerca%";
    $exclude = count($w['giocatori']) ? implode(",", array_map("intval", $w['giocatori'])) : "0";

    $stmt = $conn->prepare("
        SELECT id, nome, cognome, email
        FROM utente
        WHERE verified = 1
          AND (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)
          AND id NOT IN ($exclude)
        LIMIT 10
    ");
    $stmt->bind_param("sss", $q, $q, $q);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc())
        $risultati[] = $r;
}

// Controlla se un giocatore nei risultati di ricerca  gi occupato
$occupati = [];
foreach($risultati as $r){
    if(utente_gia_in_squadra($conn, $torneo_id, $r['id']))
        $occupati[] = $r['id'];
}

require_once('templates/header_riservato.php');

/* Helper iniziali avatar */
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
            <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" style="color: var(--m-text-mute);"> Torna a <?= htmlspecialchars($torneo['nome']) ?></a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Iscrivi la tua squadra</h1>
                <div class="m-page-head__sub">
                    Torneo: <b style="color: var(--m-primary-700)"><?= htmlspecialchars($torneo['nome']) ?></b>
                     min <?= (int)$torneo['min_giocatori_per_squadra'] ?> / max <?= (int)$torneo['max_giocatori_per_squadra'] ?> giocatori
                </div>
            </div>
        </div>

        <div class="m-stepper">
            <div class="<?= step_class(1, $step) ?>">
                <span class="m-step__num">
                    <?php if ($step > 1): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>1<?php endif; ?>
                </span>
                <div class="m-step__text"><span class="m-step__label">Step 1</span><span class="m-step__title">Nome squadra</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(2, $step) ?>">
                <span class="m-step__num">
                    <?php if ($step > 2): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>2<?php endif; ?>
                </span>
                <div class="m-step__text"><span class="m-step__label">Step 2</span><span class="m-step__title">Aggiungi giocatori</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(3, $step) ?>">
                <span class="m-step__num">3</span>
                <div class="m-step__text"><span class="m-step__label">Step 3</span><span class="m-step__title">Riepilogo e invio</span></div>
            </div>
        </div>

        <?php if(!empty($errori)): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <?php foreach($errori as $e): ?><p style="margin:0"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(($_GET['msg'] ?? '') === 'errGiocatoreOccupato'): ?>
            <div class="m-alert m-alert--warn m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>Questo giocatore  gi iscritto a un'altra squadra in questo torneo.</div>
            </div>
        <?php endif; ?>

        <?php if($step == 1): ?>
            <form method="POST" class="m-card" style="padding: var(--m-6); max-width: 520px;">
                <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                <input type="hidden" name="step" value="1">

                <h3 style="margin: 0 0 var(--m-2);">Come si chiama la tua squadra?</h3>
                <p class="m-muted m-mb-5">Scegli un nome riconoscibile, sar visibile a tutti.</p>

                <div class="m-field">
                    <label class="m-label" for="nome_squadra">Nome squadra</label>
                    <input class="m-input" type="text" id="nome_squadra" name="nome_squadra"
                           value="<?= htmlspecialchars($w['nome_squadra']) ?>" placeholder="Es. I Falchi di Cuneo" required>
                </div>

                <div class="m-row-between m-mt-6" style="padding-top: var(--m-4); border-top: 1px solid var(--m-border);">
                    <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" class="m-btn m-btn--ghost">Annulla</a>
                    <button name="azione" value="avanti" class="m-btn m-btn--primary">
                        Avanti
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </form>

        <?php elseif($step == 2): ?>
            <div class="m-grid" style="grid-template-columns: 1fr 320px; gap: var(--m-5);">

                <section>
                    <div class="m-card">
                        <div class="m-card__header">
                            <h3 class="m-card__title">Cerca giocatori da invitare</h3>
                        </div>

                        <form method="GET" class="m-mb-5">
                            <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                            <input type="hidden" name="step" value="2">
                            <div class="m-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                                <input class="m-input" type="search" name="cerca" placeholder="Cerca per nome, cognome, email" value="<?= htmlspecialchars($cerca) ?>">
                            </div>
                        </form>

                        <?php if($cerca !== '' && empty($risultati)): ?>
                            <p class="m-muted" style="font-style: italic;">Nessun utente trovato per "<?= htmlspecialchars($cerca) ?>".</p>
                        <?php endif; ?>

                        <?php if($risultati): ?>
                            <p class="m-muted" style="font-size: 13px;"><?= count($risultati) ?> risultati per "<?= htmlspecialchars($cerca) ?>"</p>
                            <div style="border: 1px solid var(--m-border); border-radius: var(--m-r-sm); overflow: hidden;">
                                <?php foreach($risultati as $r): $is_occ = in_array($r['id'], $occupati); ?>
                                    <div style="display: grid; grid-template-columns: 36px 1fr auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $is_occ ? 'opacity: 0.55;' : '' ?> border-top: 1px solid var(--m-border);">
                                        <span class="m-avatar<?= $is_occ ? '' : '' ?>" style="<?= $is_occ ? 'background: linear-gradient(135deg, #b0a8cc, #888a9c);' : '' ?>"><?= iniziali($r['nome'], $r['cognome']) ?></span>
                                        <div>
                                            <div style="font-weight: 500;<?= $is_occ ? ' text-decoration: line-through;' : '' ?>"><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></div>
                                            <div class="m-muted" style="font-size: 12px;"><?= htmlspecialchars($r['email']) ?></div>
                                        </div>
                                        <?php if($is_occ): ?>
                                            <span class="m-badge m-badge--warn">Già in una squadra</span>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                                                <input type="hidden" name="step" value="2">
                                                <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                                <input type="hidden" name="aggiungi_id" value="<?= $r['id'] ?>">
                                                <button class="m-btn m-btn--secondary m-btn--sm">
                                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                                    Aggiungi
                                                </button>
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
                        <h4 class="m-profile-section-label">La tua squadra</h4>
                        <div style="font-family: var(--m-font-display); font-size: 20px; font-weight: 700; margin-bottom: 4px;"><?= htmlspecialchars($w['nome_squadra'] ?: 'Senza nome') ?></div>
                        <div class="m-muted" style="font-size: 13px; margin-bottom: var(--m-3);">
                            <b style="color: var(--m-text);"><?= count($w['giocatori']) ?> / <?= (int)$torneo['max_giocatori_per_squadra'] ?></b> giocatori
                        </div>
                        <?php $pct = $torneo['max_giocatori_per_squadra'] ? min(100, round(count($w['giocatori']) / $torneo['max_giocatori_per_squadra'] * 100)) : 0; ?>
                        <div style="height: 6px; background: var(--m-surface-2); border-radius: 999px; overflow: hidden; margin-bottom: var(--m-4);">
                            <div style="height: 100%; width: <?= $pct ?>%; background: linear-gradient(90deg, var(--m-primary-400), var(--m-primary-600));"></div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: var(--m-2);">
                            <?php foreach($w['giocatori'] as $id): $g = $giocatori_dati[$id] ?? null; if (!$g) continue; $is_me = ($id == $utente_id); ?>
                                <div style="display: grid; grid-template-columns: 32px 1fr auto; gap: 10px; align-items: center; padding: 8px; <?= $is_me ? 'background: var(--m-primary-50); border-radius: 8px;' : '' ?>">
                                    <span class="m-avatar" style="width: 32px; height: 32px; font-size: 12px;"><?= iniziali($g['nome'], $g['cognome']) ?></span>
                                    <div>
                                        <div style="font-weight: <?= $is_me ? '600' : '500' ?>; font-size: 13px;"><?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?></div>
                                        <?php if($is_me): ?>
                                            <div class="m-muted" style="font-size: 11px;">Capitano  Tu</div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($is_me): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--m-gold-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                                            <input type="hidden" name="step" value="2">
                                            <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                            <input type="hidden" name="rimuovi_id" value="<?= $id ?>">
                                            <button class="m-btn m-btn--ghost" style="padding: 4px; width: auto; height: auto;" aria-label="Rimuovi">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php for($i = count($w['giocatori']); $i < (int)$torneo['max_giocatori_per_squadra']; $i++): ?>
                                <div style="display: grid; grid-template-columns: 32px 1fr; gap: 10px; align-items: center; padding: 8px; opacity: 0.5; border: 1px dashed var(--m-border); border-radius: 8px;">
                                    <span class="m-avatar" style="width: 32px; height: 32px; background: transparent; border: 1px dashed var(--m-border-strong); color: var(--m-text-mute); font-size: 14px;">+</span>
                                    <div class="m-muted" style="font-size: 12px;">Slot libero (opzionale)</div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <form method="POST" style="display: flex; gap: var(--m-2); margin-top: var(--m-4); padding-top: var(--m-4); border-top: 1px dashed var(--m-border);">
                            <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                            <input type="hidden" name="step" value="2">
                            <button name="azione" value="indietro" class="m-btn m-btn--ghost" style="flex: 1;"> Indietro</button>
                            <button name="azione" value="avanti" class="m-btn m-btn--primary" style="flex: 2;">Avanti </button>
                        </form>
                    </div>
                </aside>
            </div>

        <?php elseif($step == 3): ?>
            <div class="m-card" style="padding: var(--m-6);">
                <div class="m-card__header">
                    <h3 class="m-card__title">Riepilogo</h3>
                    <span class="m-badge m-badge--info"><?= count($w['giocatori']) ?> giocatori</span>
                </div>

                <dl style="display: grid; grid-template-columns: 180px 1fr; gap: var(--m-3) var(--m-4); font-size: 14px; margin: 0;">
                    <dt class="m-muted">Nome squadra</dt><dd style="margin: 0; font-weight: 600;"><?= htmlspecialchars($w['nome_squadra']) ?></dd>
                    <dt class="m-muted">Giocatori</dt>
                    <dd style="margin: 0;">
                        <ul style="margin: 0; padding-left: 18px;">
                            <?php foreach($w['giocatori'] as $id): $g = $giocatori_dati[$id] ?? null; if(!$g) continue; ?>
                                <li>
                                    <?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?>
                                    <?php if($id == $utente_id): ?>
                                        <span class="m-badge m-badge--gold" style="margin-left: 6px;">Capitano</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </dd>
                </dl>

                <div class="m-alert m-alert--info m-mt-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>La richiesta verrà inviata all'organizzatore via email. Riceverai una notifica appena verrà approvata o rifiutata.</div>
                </div>

                <form method="POST" class="m-row-between" style="margin-top: var(--m-6); padding-top: var(--m-4); border-top: 1px solid var(--m-border);">
                    <?= csrf_field() ?>
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                    <input type="hidden" name="step" value="3">
                    <button name="azione" value="indietro" class="m-btn m-btn--ghost"> Indietro</button>
                    <button name="azione" value="crea" class="m-btn m-btn--primary m-btn--lg">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Invia richiesta
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
