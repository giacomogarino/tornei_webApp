<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
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

    $azione = $_POST['azione'] ?? '';

    /* STEP 1 - salva nome */
    if($step === 1){
        $w['nome_squadra'] = trim($_POST['nome_squadra'] ?? '');
        if($w['nome_squadra'] === ''){
            $errori[] = "Inserisci il nome della squadra.";
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

        // Ri-valida tutti i giocatori prima dell'INSERT
        $conflitti = [];
        foreach($w['giocatori'] as $uid){
            if(utente_gia_in_squadra($conn, $torneo_id, $uid)){
                if($uid === $utente_id){
                    $conflitti[] = "Sei già iscritto a un'altra squadra di questo torneo.";
                } else {
                    // Recupera nome per messaggio
                    $st = $conn->prepare("SELECT nome, cognome FROM utente WHERE id=?");
                    $st->bind_param("i", $uid);
                    $st->execute();
                    $u = $st->get_result()->fetch_assoc();
                    $nome = $u ? "{$u['nome']} {$u['cognome']}" : "Utente #$uid";
                    $conflitti[] = "$nome è già iscritto a un'altra squadra di questo torneo.";
                    // Rimuovi dalla lista
                    $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g !== $uid));
                }
            }
        }

        if(!empty($conflitti)){
            foreach($conflitti as $c) $errori[] = $c;
            // Non procede: mostra errori allo step 3
        } else {
            $conn->begin_transaction();
            try{
                $stmt = $conn->prepare("
                    INSERT INTO squadra (torneo_id,nome,capitano_id,stato)
                    VALUES(?,?,?,'in_attesa')
                ");
                $stmt->bind_param("isi", $torneo_id, $w['nome_squadra'], $utente_id);
                $stmt->execute();
                $squadra_id = $conn->insert_id;

                $stmt2 = $conn->prepare("
                    INSERT INTO giocatore_squadra (squadra_id,utente_id) VALUES(?,?)
                ");
                foreach($w['giocatori'] as $uid){
                    $stmt2->bind_param("ii", $squadra_id, $uid);
                    $stmt2->execute();
                }

                // Genera token approvazione
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
                $base_url    = "https://" . $_SERVER['HTTP_HOST'];
                $link_approva = "$base_url/staging/php/approva_squadra.php?token=$token_approva&azione=approva";
                $link_rifiuta = "$base_url/staging/php/approva_squadra.php?token=$token_rifiuta&azione=rifiuta";

                $to      = $organizzatore['email'];
                $subject = "Nuova richiesta squadra — {$torneo['nome']}";
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
Questo messaggio è stato generato automaticamente.";

                $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
                           "Content-Type: text/plain; charset=UTF-8\r\n";

                mail($to, $subject, $message, $headers);

                unset($_SESSION['wizard_squadra']);
                header("Location: dettagli_torneo.php?id=$torneo_id&squadra_inviata=1");
                exit;

            }catch(Exception $e){
                $conn->rollback();
                $errori[] = "Errore salvataggio: " . $e->getMessage();
            }
        }
    }
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

// Controlla se un giocatore nei risultati di ricerca è già occupato
// (per mostrarlo in grigio/disabilitato nell'UI)
$occupati = [];
foreach($risultati as $r){
    if(utente_gia_in_squadra($conn, $torneo_id, $r['id']))
        $occupati[] = $r['id'];
}

require_once('templates/header_riservato.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea squadra</title>
    <style>
        body        { font-family: Arial; max-width: 700px; margin: 30px auto; }
        .step       { background: #eee; padding: 10px; margin-bottom: 15px; }
        fieldset    { padding: 15px; margin-bottom: 15px; }
        input       { width: 100%; padding: 8px; box-sizing: border-box; }
        table       { width: 100%; border-collapse: collapse; }
        td, th      { border: 1px solid #ccc; padding: 8px; }
        .errori     { background: #ffdede; padding: 10px; margin-bottom: 10px; }
        .avviso     { background: #fff3cd; padding: 10px; margin-bottom: 10px; }
        button      { padding: 8px 12px; margin-top: 10px; }
        .occupato   { color: #999; font-style: italic; }
    </style>
</head>
<body>

<h1>Crea squadra — <?= htmlspecialchars($torneo['nome']) ?></h1>
<div class="step">Step <?= $step ?> / 3</div>

<?php if(!empty($errori)): ?>
    <div class="errori">
        <?php foreach($errori as $e): ?>
            <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if(($GET_msg = $_GET['msg'] ?? '') === 'errGiocatoreOccupato'): ?>
    <div class="avviso">
        <p>Questo giocatore è già iscritto a un'altra squadra in questo torneo e non può essere aggiunto.</p>
    </div>
<?php endif; ?>

<!-- ══ STEP 1 ══ -->
<?php if($step == 1): ?>

    <form method="POST">
        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
        <input type="hidden" name="step" value="1">
        <fieldset>
            <legend>Nome squadra</legend>
            <input type="text" name="nome_squadra"
                   value="<?= htmlspecialchars($w['nome_squadra']) ?>">
        </fieldset>
        <button name="azione" value="avanti">Avanti →</button>
    </form>

<!-- ══ STEP 2 ══ -->
<?php elseif($step == 2): ?>

    <!-- Form ricerca (GET) -->
    <form method="GET">
        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
        <input type="hidden" name="step" value="2">
        <input type="text" name="cerca" value="<?= htmlspecialchars($cerca) ?>"
               placeholder="Cerca per nome, cognome o email…">
        <button type="submit">Cerca</button>
    </form>

    <?php if($cerca !== '' && empty($risultati) && empty($occupati)): ?>
        <p><em>Nessun utente trovato.</em></p>
    <?php endif; ?>

    <?php if($risultati): ?>
        <table>
            <tr><th>Nome</th><th>Email</th><th></th></tr>
            <?php foreach($risultati as $r): ?>
                <?php $is_occupato = in_array($r['id'], $occupati); ?>
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

    <h3>Squadra corrente
        (<?= count($w['giocatori']) ?> / <?= $torneo['max_giocatori_per_squadra'] ?>)
    </h3>
    <ul>
        <?php foreach($w['giocatori'] as $id): ?>
            <li>
                <?= htmlspecialchars($giocatori_dati[$id]['nome']) ?>
                <?= htmlspecialchars($giocatori_dati[$id]['cognome']) ?>
                <?php if($id == $utente_id): ?>
                    <strong>(Tu — Capitano)</strong>
                <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
                        <input type="hidden" name="step" value="2">
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
        <input type="hidden" name="step" value="2">
        <button name="azione" value="indietro">← Indietro</button>
        <button name="azione" value="avanti">Avanti →</button>
    </form>

<!-- ══ STEP 3 ══ -->
<?php elseif($step == 3): ?>

    <fieldset>
        <legend>Riepilogo</legend>
        <p><strong>Nome squadra:</strong> <?= htmlspecialchars($w['nome_squadra']) ?></p>
        <p><strong>Giocatori (<?= count($w['giocatori']) ?>):</strong></p>
        <ul>
            <?php foreach($w['giocatori'] as $id): ?>
                <li>
                    <?= htmlspecialchars($giocatori_dati[$id]['nome']) ?>
                    <?= htmlspecialchars($giocatori_dati[$id]['cognome']) ?>
                    <?= ($id == $utente_id) ? '<strong>(Capitano)</strong>' : '' ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </fieldset>

    <form method="POST">
        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
        <input type="hidden" name="step" value="3">
        <button name="azione" value="indietro">← Indietro</button>
        <button name="azione" value="crea">✔ Invia richiesta</button>
    </form>

<?php endif; ?>

</body>
</html>
<?php require_once('templates/footer.php'); ?>