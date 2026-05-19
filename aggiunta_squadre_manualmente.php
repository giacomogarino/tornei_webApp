<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

include_once("conf/db_config.php");

$torneo_id = (int)($_GET['torneo_id'] ?? $_POST['torneo_id'] ?? $_GET['id'] ?? 0);

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

// Solo l'organizzatore può usare questa pagina
if(!isset($_SESSION['id_utente']) || $_SESSION['id_utente'] != $torneo['creato_da']){
    header("Location: dettagli_torneo.php?id=$torneo_id&msg=err");
    exit;
}

/* =====================================================
   HELPER: controlla se un utente è già in una squadra
   approvata/in_attesa di questo torneo
===================================================== */

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

/* =====================================================
   SESSIONE WIZARD MANUALE
   Struttura: [torneo_id, nome_squadra, capitano_id, giocatori[]]
   
   Step 1: nome squadra
   Step 2: scelta capitano (come aggiungi giocatori)
   Step 3: aggiungi altri giocatori
   Step 4: riepilogo e conferma
===================================================== */

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

/* =====================================================
   GESTIONE POST
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $azione = $_POST['azione'] ?? '';

    /* ── STEP 1: salva nome squadra ── */
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
                $errori[] = "Esiste già una squadra con questo nome. Scegline un altro.";
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

    /* ── STEP 2: scegli capitano ── */

    // Imposta capitano
    if(isset($_POST['imposta_capitano'])){
        $cap_id = (int)$_POST['imposta_capitano'];

        // Verifica che l'utente esista
        $stmt_u = $conn->prepare("SELECT id FROM utente WHERE id = ?");
        $stmt_u->bind_param("i", $cap_id);
        $stmt_u->execute();
        if(!$stmt_u->get_result()->fetch_assoc()){
            $errori[] = "Utente non trovato.";
        } elseif(utente_gia_in_squadra($conn, $torneo_id, $cap_id)) {
            // Il capitano è già in un'altra squadra del torneo
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca_cap=" . urlencode($cerca_cap) . "&msg=errCapiOccupato");
            exit;
        } else {
            $w['capitano_id'] = $cap_id;
            // Il capitano è automaticamente il primo giocatore
            if(!in_array($cap_id, $w['giocatori'])){
                array_unshift($w['giocatori'], $cap_id);
            } else {
                // Sposta il capitano in prima posizione
                $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g != $cap_id));
                array_unshift($w['giocatori'], $cap_id);
            }
        }

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca_cap=" . urlencode($cerca_cap));
        exit;
    }

    // Navigazione da step 2 ad avanti
    if($azione === 'avanti' && $step === 2){
        if(!$w['capitano_id']){
            $errori[] = "Devi selezionare un capitano prima di procedere.";
        } elseif(utente_gia_in_squadra($conn, $torneo_id, $w['capitano_id'])){
            // Il capitano è diventato occupato nel frattempo (race condition)
            $w['capitano_id'] = null;
            $w['giocatori']   = [];
            $errori[] = "Il capitano selezionato è stato nel frattempo iscritto a un'altra squadra. Selezionane un altro.";
        } else {
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3");
            exit;
        }
    }

    /* ── STEP 3: aggiungi/rimuovi giocatori ── */

    // Aggiungi giocatore
    if(isset($_POST['aggiungi_id'])){
        $id = (int)$_POST['aggiungi_id'];

        // DEBUG TEMPORANEO — rimuovere dopo il test
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
        // FINE DEBUG

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

    // Rimuovi giocatore (non si può rimuovere il capitano)
    if(isset($_POST['rimuovi_id'])){
        $id = (int)$_POST['rimuovi_id'];

        if($id != $w['capitano_id'])
            $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g != $id));

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3&cerca=" . urlencode($cerca));
        exit;
    }

    // Navigazione da step 3
    if($azione === 'avanti' && $step === 3){
        $min = $torneo['min_giocatori_per_squadra'] ?? 1;
        if(count($w['giocatori']) < $min){
            $errori[] = "Servono almeno $min giocatori (attuali: " . count($w['giocatori']) . ").";
        } else {
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=4");
            exit;
        }
    }

    /* ── NAVIGAZIONE INDIETRO ── */
    if($azione === 'indietro'){
        $prev = max(1, $step - 1);
        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=$prev");
        exit;
    }

    /* ── STEP 4: CREA SQUADRA ── */
    if($azione === 'crea' && $step === 4){

        // Verifica numero massimo squadre
        $stmt_cnt = $conn->prepare("SELECT COUNT(*) cnt FROM squadra WHERE torneo_id = ?");
        $stmt_cnt->bind_param("i", $torneo_id);
        $stmt_cnt->execute();
        $tot = $stmt_cnt->get_result()->fetch_assoc()['cnt'];

        if($tot >= $torneo['numero_squadre']){
            $errori[] = "Limite massimo di squadre raggiunto ({$torneo['numero_squadre']}).";
        } else {
            // Doppio controllo nome (race condition)
            $stmt_dup2 = $conn->prepare("
                SELECT COUNT(*) cnt FROM squadra
                WHERE torneo_id = ? AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
                AND stato IN ('in_attesa','approvata')
            ");
            $stmt_dup2->bind_param("is", $torneo_id, $w['nome_squadra']);
            $stmt_dup2->execute();

            if($stmt_dup2->get_result()->fetch_assoc()['cnt'] > 0){
                $errori[] = "Il nome squadra è già stato preso. Torna indietro e scegline un altro.";
            } else {
                // Ri-valida tutti i giocatori prima dell'INSERT (race condition)
                $conflitti = [];
                foreach($w['giocatori'] as $uid){
                    if(utente_gia_in_squadra($conn, $torneo_id, $uid)){
                        $st = $conn->prepare("SELECT nome, cognome FROM utente WHERE id=?");
                        $st->bind_param("i", $uid);
                        $st->execute();
                        $u = $st->get_result()->fetch_assoc();
                        $nome_u = $u ? "{$u['nome']} {$u['cognome']}" : "Utente #$uid";

                        if($uid == $w['capitano_id']){
                            $conflitti[] = "Il capitano $nome_u è già iscritto a un'altra squadra di questo torneo. Devi tornare allo step 2 e sceglierne un altro.";
                            // Azzera capitano e lista giocatori: l'utente deve ripartire dallo step 2
                            $w['capitano_id'] = null;
                            $w['giocatori']   = [];
                        } else {
                            $conflitti[] = "$nome_u è già iscritto a un'altra squadra di questo torneo ed è stato rimosso dalla lista.";
                            // Rimuovi solo il giocatore in conflitto
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

/* =====================================================
   CARICA DATI GIOCATORI SELEZIONATI
===================================================== */

$giocatori_dati = [];
if(!empty($w['giocatori'])){
    $ids = implode(",", array_map("intval", $w['giocatori']));
    $res = $conn->query("SELECT id, nome, cognome, email FROM utente WHERE id IN ($ids)");
    while($r = $res->fetch_assoc())
        $giocatori_dati[$r['id']] = $r;
}

/* =====================================================
   RICERCA CAPITANO (step 2)
===================================================== */

$risultati_cap = [];
$occupati_cap  = [];
if($step === 2 && $cerca_cap !== ''){
    $q = "%$cerca_cap%";

    // Escludi: capitano corrente + tutti i giocatori già aggiunti in sessione
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

    // Marca chi è già occupato in un'altra squadra del torneo
    foreach($risultati_cap as $r){
        if(utente_gia_in_squadra($conn, $torneo_id, $r['id']))
            $occupati_cap[] = $r['id'];
    }
}

/* =====================================================
   RICERCA GIOCATORI (step 3)
===================================================== */

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

/* =====================================================
   SQUADRE GIÀ INSERITE (per la lista in fondo)
===================================================== */

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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiunta squadre manuale</title>
    <style>
        body        { font-family: Arial; max-width: 750px; margin: 30px auto; }
        .step-bar   { background: #eee; padding: 10px; margin-bottom: 15px; }
        fieldset    { padding: 15px; margin-bottom: 15px; }
        input[type=text] { width: 100%; padding: 8px; box-sizing: border-box; }
        table       { width: 100%; border-collapse: collapse; }
        td, th      { border: 1px solid #ccc; padding: 8px; }
        .errori     { background: #ffdede; padding: 10px; margin-bottom: 10px; }
        .avviso     { background: #fff3cd; padding: 10px; margin-bottom: 10px; }
        .ok         { background: #d4edda; padding: 10px; margin-bottom: 10px; }
        button      { padding: 8px 12px; margin-top: 8px; }
        .occupato   { color: #999; font-style: italic; }
        .capitano-corrente { background: #e8f5e9; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px; display: inline-block; }
    </style>
</head>
<body>

<h2>Aggiunta squadre manuale</h2>
<p>Torneo: <strong><?= htmlspecialchars($torneo['nome']) ?></strong></p>
<p>Squadre massime: <?= $torneo['numero_squadre'] ?> &mdash; Inserite: <?= count($squadre_inserite) ?></p>

<hr>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'ok'): ?>
        <div class="ok">✅ Squadra creata con successo.</div>
    <?php elseif($_GET['msg'] === 'limite'): ?>
        <div class="errori">⚠️ Limite massimo di squadre raggiunto.</div>
    <?php elseif($_GET['msg'] === 'errCapiOccupato'): ?>
        <div class="avviso">⚠️ Questo utente è già capitano o giocatore di un'altra squadra in questo torneo.</div>
    <?php elseif($_GET['msg'] === 'errGiocatoreOccupato'): ?>
        <div class="avviso">⚠️ Questo giocatore è già iscritto a un'altra squadra in questo torneo e non può essere aggiunto.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if(!empty($errori)): ?>
    <div class="errori">
        <?php foreach($errori as $e): ?>
            <p style="margin:0">⚠️ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="step-bar"><strong>Step <?= $step ?> / 4</strong></div>

<!-- ══════════════════════════════════════
     STEP 1 — Nome squadra
═══════════════════════════════════════ -->
<?php if($step === 1): ?>

<form method="POST">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="1">

    <fieldset>
        <legend>Nome squadra</legend>
        <input type="text" name="nome_squadra"
               value="<?= htmlspecialchars($w['nome_squadra']) ?>"
               placeholder="Inserisci il nome della squadra"
               required>
    </fieldset>

    <button name="azione" value="avanti">Avanti →</button>
</form>

<!-- ══════════════════════════════════════
     STEP 2 — Scegli capitano
═══════════════════════════════════════ -->
<?php elseif($step === 2): ?>

<?php if($w['capitano_id'] && isset($giocatori_dati[$w['capitano_id']])): ?>
    <?php $cap = $giocatori_dati[$w['capitano_id']]; ?>
    <p>
        Capitano selezionato:
        <span class="capitano-corrente">
            ⭐ <?= htmlspecialchars($cap['nome'] . ' ' . $cap['cognome']) ?>
            (<?= htmlspecialchars($cap['email']) ?>)
        </span>
    </p>
<?php else: ?>
    <p><em>Nessun capitano selezionato.</em></p>
<?php endif; ?>

<!-- Ricerca capitano (GET) -->
<form method="GET">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="2">
    <input type="text" name="cerca_cap"
           value="<?= htmlspecialchars($cerca_cap) ?>"
           placeholder="Cerca capitano per nome, cognome o email…">
    <button type="submit">Cerca</button>
</form>

<?php if($cerca_cap !== '' && empty($risultati_cap)): ?>
    <p><em>Nessun utente trovato.</em></p>
<?php endif; ?>

<?php if($risultati_cap): ?>
<table>
<tr><th>Nome</th><th>Email</th><th></th></tr>
<?php foreach($risultati_cap as $r):
    $is_occupato = in_array($r['id'], $occupati_cap);
?>
<tr class="<?= $is_occupato ? 'occupato' : '' ?>">
    <td><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td>
        <?php if($is_occupato): ?>
            <span title="Già in una squadra di questo torneo">Occupato</span>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="cerca_cap" value="<?= htmlspecialchars($cerca_cap) ?>">
                <input type="hidden" name="imposta_capitano" value="<?= $r['id'] ?>">
                <button>⭐ Scegli come capitano</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="2">
    <button name="azione" value="indietro">← Indietro</button>
    <button name="azione" value="avanti">Avanti →</button>
</form>

<!-- ══════════════════════════════════════
     STEP 3 — Aggiungi giocatori
═══════════════════════════════════════ -->
<?php elseif($step === 3): ?>

<!-- Ricerca giocatori (GET) -->
<form method="GET">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="3">
    <input type="text" name="cerca"
           value="<?= htmlspecialchars($cerca) ?>"
           placeholder="Cerca per nome, cognome o email…">
    <button type="submit">Cerca</button>
</form>

<?php if($cerca !== '' && empty($risultati)): ?>
    <p><em>Nessun utente trovato.</em></p>
<?php endif; ?>

<?php if($risultati): ?>
<table>
<tr><th>Nome</th><th>Email</th><th></th></tr>
<?php foreach($risultati as $r):
    $is_occupato = in_array($r['id'], $occupati);
?>
<tr class="<?= $is_occupato ? 'occupato' : '' ?>">
    <td><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td>
        <?php if($is_occupato): ?>
            <span title="Già in una squadra di questo torneo">Occupato</span>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                <input type="hidden" name="aggiungi_id" value="<?= $r['id'] ?>">
                <button>+ Aggiungi</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>
    Giocatori nella squadra
    (<?= count($w['giocatori']) ?> / <?= $torneo['max_giocatori_per_squadra'] ?? '∞' ?>)
</h3>
<ul>
<?php foreach($w['giocatori'] as $id): ?>
    <li>
        <?= htmlspecialchars(($giocatori_dati[$id]['nome'] ?? '') . ' ' . ($giocatori_dati[$id]['cognome'] ?? '')) ?>
        <?php if($id == $w['capitano_id']): ?>
            <strong>(Capitano)</strong>
        <?php else: ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                <input type="hidden" name="rimuovi_id" value="<?= $id ?>">
                <button>✕ Rimuovi</button>
            </form>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>

<form method="POST">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="3">
    <button name="azione" value="indietro">← Indietro</button>
    <button name="azione" value="avanti">Avanti →</button>
</form>

<!-- ══════════════════════════════════════
     STEP 4 — Riepilogo e conferma
═══════════════════════════════════════ -->
<?php elseif($step === 4): ?>

<fieldset>
    <legend>Riepilogo</legend>
    <p><strong>Nome squadra:</strong> <?= htmlspecialchars($w['nome_squadra']) ?></p>
    <p><strong>Giocatori (<?= count($w['giocatori']) ?>):</strong></p>
    <ul>
        <?php foreach($w['giocatori'] as $id): ?>
        <li>
            <?= htmlspecialchars(($giocatori_dati[$id]['nome'] ?? '') . ' ' . ($giocatori_dati[$id]['cognome'] ?? '')) ?>
            <?= ($id == $w['capitano_id']) ? '<strong>(Capitano)</strong>' : '' ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <p><em>La squadra verrà approvata automaticamente.</em></p>
</fieldset>

<form method="POST">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="4">
    <button name="azione" value="indietro">← Indietro</button>
    <button name="azione" value="crea">✔ Crea squadra</button>
</form>

<?php endif; ?>

<hr>

<!-- Lista squadre già inserite -->
<h3>Squadre inserite</h3>
<?php if(empty($squadre_inserite)): ?>
    <p>Nessuna squadra ancora.</p>
<?php else: ?>
<table>
<tr>
    <th>Nome</th>
    <th>Capitano</th>
    <th>Stato</th>
    <th>Giocatori</th>
</tr>
<?php foreach($squadre_inserite as $sq):
    $stmt_gc = $conn->prepare("SELECT COUNT(*) cnt FROM giocatore_squadra WHERE squadra_id = ?");
    $stmt_gc->bind_param("i", $sq['id']);
    $stmt_gc->execute();
    $num_g = $stmt_gc->get_result()->fetch_assoc()['cnt'];
?>
<tr>
    <td><?= htmlspecialchars($sq['nome']) ?></td>
    <td><?= htmlspecialchars($sq['n'] . ' ' . $sq['c']) ?></td>
    <td><?= $sq['stato'] ?></td>
    <td><?= $num_g ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br>
<a href="dettagli_torneo.php?id=<?= $torneo_id ?>">← Torna al torneo</a>

</body>
</html>
<?php require_once('templates/footer.php'); ?>