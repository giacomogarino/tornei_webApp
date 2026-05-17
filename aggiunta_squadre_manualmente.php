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
   SESSIONE WIZARD MANUALE
   Struttura: [torneo_id, nome_squadra, capitano_id, giocatori[]]
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

$step   = (int)($_GET['step'] ?? $_POST['step'] ?? 1);
$cerca  = trim($_GET['cerca'] ?? '');
$errori = [];

/* =====================================================
   GESTIONE POST
===================================================== */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $azione = $_POST['azione'] ?? '';

    // --- STEP 1: salva nome e capitano ---
    if($step === 1){
        $nome_squadra  = trim($_POST['nome_squadra'] ?? '');
        $capitano_nome = trim($_POST['capitano_nome'] ?? '');

        if($nome_squadra === ''){
            $errori[] = "Inserisci il nome della squadra.";
        }else{
            // Controllo nome duplicato
            $stmt_dup = $conn->prepare("
                SELECT COUNT(*) cnt FROM squadra
                WHERE torneo_id = ? AND LOWER(TRIM(nome)) = LOWER(TRIM(?))
                AND stato IN ('in_attesa','approvata')
            ");
            $stmt_dup->bind_param("is", $torneo_id, $nome_squadra);
            $stmt_dup->execute();
            if($stmt_dup->get_result()->fetch_assoc()['cnt'] > 0)
                $errori[] = "Esiste già una squadra con questo nome. Scegline un altro.";
        }

        if($capitano_nome === ''){
            $errori[] = "Inserisci il nome del capitano.";
        }else{
            $stmt_cap = $conn->prepare("
                SELECT id FROM utente
                WHERE CONCAT(nome,' ',cognome) = ?
                LIMIT 1
            ");
            $stmt_cap->bind_param("s", $capitano_nome);
            $stmt_cap->execute();
            $res_cap = $stmt_cap->get_result()->fetch_assoc();

            if(!$res_cap){
                $errori[] = "Capitano non trovato. Verifica nome e cognome.";
            }else{
                $capitano_id = $res_cap['id'];
            }
        }

        if(empty($errori)){
            $w['nome_squadra'] = $nome_squadra;
            $w['capitano_id']  = $capitano_id;

            // Il capitano è automaticamente il primo giocatore
            if(!in_array($capitano_id, $w['giocatori']))
                $w['giocatori'] = array_merge([$capitano_id], array_filter($w['giocatori'], fn($g) => $g != $capitano_id));

            if($azione === 'avanti'){
                header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2");
                exit;
            }
        }
    }

    // --- AGGIUNGI GIOCATORE ---
    if(isset($_POST['aggiungi_id'])){
        $id = (int)$_POST['aggiungi_id'];

        $max = $torneo['max_giocatori_per_squadra'] ?? 999;
        if(!in_array($id, $w['giocatori']) && count($w['giocatori']) < $max)
            $w['giocatori'][] = $id;

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca=" . urlencode($cerca));
        exit;
    }

    // --- RIMUOVI GIOCATORE ---
    if(isset($_POST['rimuovi_id'])){
        $id = (int)$_POST['rimuovi_id'];

        // Non si può rimuovere il capitano
        if($id != $w['capitano_id'])
            $w['giocatori'] = array_values(array_filter($w['giocatori'], fn($g) => $g != $id));

        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=2&cerca=" . urlencode($cerca));
        exit;
    }

    // --- NAVIGAZIONE ---
    if($azione === 'avanti' && $step === 2){
        $min = $torneo['min_giocatori_per_squadra'] ?? 1;
        if(count($w['giocatori']) < $min){
            $errori[] = "Servono almeno $min giocatori (attuali: " . count($w['giocatori']) . ").";
        }else{
            header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=3");
            exit;
        }
    }

    if($azione === 'indietro'){
        $prev = max(1, $step - 1);
        header("Location: aggiunta_squadre_manualmente.php?torneo_id=$torneo_id&step=$prev");
        exit;
    }

    // --- CREA SQUADRA (step 3) ---
    if($azione === 'crea' && $step === 3){

        // Verifica numero massimo squadre
        $stmt_cnt = $conn->prepare("SELECT COUNT(*) cnt FROM squadra WHERE torneo_id = ?");
        $stmt_cnt->bind_param("i", $torneo_id);
        $stmt_cnt->execute();
        $tot = $stmt_cnt->get_result()->fetch_assoc()['cnt'];

        if($tot >= $torneo['numero_squadre']){
            $errori[] = "Limite massimo di squadre raggiunto ({$torneo['numero_squadre']}).";
        }else{
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
            }else{
                $conn->begin_transaction();
                try{
                    // Inserisci squadra (approvata direttamente perché la aggiunge l'organizzatore)
                    $stmt_sq = $conn->prepare("
                        INSERT INTO squadra (torneo_id, nome, capitano_id, stato)
                        VALUES (?, ?, ?, 'approvata')
                    ");
                    $stmt_sq->bind_param("isi", $torneo_id, $w['nome_squadra'], $w['capitano_id']);
                    $stmt_sq->execute();
                    $squadra_id = $conn->insert_id;

                    // Inserisci giocatori
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

                }catch(Exception $e){
                    $conn->rollback();
                    $errori[] = "Errore durante il salvataggio: " . $e->getMessage();
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
   RICERCA UTENTI (step 2)
===================================================== */

$risultati = [];
if($step === 2 && $cerca !== ''){
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
</head>
<body>

<h2>Aggiunta squadre manuale</h2>
<p>Torneo: <strong><?= htmlspecialchars($torneo['nome']) ?></strong></p>
<p>Squadre massime: <?= $torneo['numero_squadre'] ?> &mdash; Inserite: <?= count($squadre_inserite) ?></p>

<hr>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'ok'): ?>
        <div style="color:green; margin-bottom:12px;">✅ Squadra creata con successo.</div>
    <?php elseif($_GET['msg'] === 'limite'): ?>
        <div style="color:red; margin-bottom:12px;">⚠️ Limite massimo di squadre raggiunto.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if(!empty($errori)): ?>
    <div style="background:#ffdede; padding:10px; margin-bottom:12px;">
        <?php foreach($errori as $e): ?>
            <p style="margin:0">⚠️ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<p><strong>Step <?= $step ?> / 3</strong></p>

<!-- ══════════════════════════════════════
     STEP 1 — Nome squadra + Capitano
═══════════════════════════════════════ -->
<?php if($step === 1): ?>

<form method="POST">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="1">

    <label>Nome squadra</label><br>
    <input type="text" name="nome_squadra" value="<?= htmlspecialchars($w['nome_squadra']) ?>" required>
    <br><br>

    <label>Capitano (nome e cognome)</label><br>
    <input
        type="text"
        name="capitano_nome"
        list="lista_utenti"
        placeholder="Scrivi nome e cognome"
        value="<?php
            if($w['capitano_id'] && isset($giocatori_dati[$w['capitano_id']])){
                $cap = $giocatori_dati[$w['capitano_id']];
                echo htmlspecialchars($cap['nome'] . ' ' . $cap['cognome']);
            }
        ?>"
        required
    >
    <datalist id="lista_utenti">
        <?php
        $stmt_ut = $conn->prepare("SELECT nome, cognome FROM utente ORDER BY nome");
        $stmt_ut->execute();
        $utenti = $stmt_ut->get_result();
        while($u = $utenti->fetch_assoc()):
        ?>
        <option value="<?= htmlspecialchars($u['nome'] . ' ' . $u['cognome']) ?>">
        <?php endwhile; ?>
    </datalist>
    <br><br>

    <button name="azione" value="avanti">Avanti →</button>
</form>

<!-- ══════════════════════════════════════
     STEP 2 — Aggiungi giocatori
═══════════════════════════════════════ -->
<?php elseif($step === 2): ?>

<!-- Ricerca giocatori (GET) -->
<form method="GET">
    <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
    <input type="hidden" name="step" value="2">
    <input type="text" name="cerca" value="<?= htmlspecialchars($cerca) ?>"
           placeholder="Cerca per nome, cognome o email…">
    <button type="submit">Cerca</button>
</form>

<?php if($cerca !== '' && empty($risultati)): ?>
    <p><em>Nessun utente trovato.</em></p>
<?php endif; ?>

<?php if($risultati): ?>
<table border="1">
<tr><th>Nome</th><th>Email</th><th></th></tr>
<?php foreach($risultati as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td>
        <form method="POST">
            <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">
            <input type="hidden" name="step" value="2">
            <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
            <input type="hidden" name="aggiungi_id" value="<?= $r['id'] ?>">
            <button>+ Aggiungi</button>
        </form>
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

<!-- ══════════════════════════════════════
     STEP 3 — Riepilogo e conferma
═══════════════════════════════════════ -->
<?php elseif($step === 3): ?>

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
    <input type="hidden" name="step" value="3">
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
<table border="1">
<tr>
    <th>Nome</th>
    <th>Capitano</th>
    <th>Stato</th>
    <th>Giocatori</th>
</tr>
<?php foreach($squadre_inserite as $sq):
    // Conta giocatori
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