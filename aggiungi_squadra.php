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
if(!$torneo_id)
    header("Location: dettagli_torneo.php?msg=err");
    //die("ID torneo mancante");

$stmt = $conn->prepare("SELECT * FROM torneo WHERE id=?");
$stmt->bind_param("i",$torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo) 
    header("Location: dettagli_torneo.php?msg=err");
    //die("Torneo non trovato");
if($torneo['stato'] !== 'aperto') 
    header("Location: dettagli_torneo.php?msg=errTorneoChiuso");
    //die("Torneo chiuso");


$stmt = $conn->prepare("
    SELECT COUNT(*) cnt
    FROM squadra
    WHERE torneo_id=?
    AND stato IN ('approvata','in_attesa')
");
$stmt->bind_param("i",$torneo_id);
$stmt->execute();
$cnt = $stmt->get_result()->fetch_assoc()['cnt'];

if($cnt >= $torneo['numero_squadre'])
    header("Location: dettagli_torneo.php?msg=errTorneoPieno");
    //die("Torneo pieno");


if(!isset($_SESSION['wizard_squadra']) || ($_SESSION['wizard_squadra']['torneo_id'] ?? 0) != $torneo_id){
    $_SESSION['wizard_squadra'] = [
        'torneo_id' => $torneo_id,
        'nome_squadra' => '',
        'giocatori' => [$utente_id]
    ];
}

$w = &$_SESSION['wizard_squadra'];

if(!in_array($utente_id, $w['giocatori'])){
    array_unshift($w['giocatori'], $utente_id);
}

$step = (int)($_GET['step'] ?? 1);
$cerca = trim($_GET['cerca'] ?? '');
$errori = [];

if($_SERVER['REQUEST_METHOD']==='POST'){

    $azione = $_POST['azione'] ?? '';

    /* STEP 1 - salva nome */
    if($step === 1){
        $w['nome_squadra'] = trim($_POST['nome_squadra'] ?? '');
        if($w['nome_squadra'] === ''){
            $errori[] = "Inserisci nome squadra";
        }
    }

    /* AGGIUNGI GIOCATORE */
    if(isset($_POST['aggiungi_id'])){
        $id = (int)$_POST['aggiungi_id'];

        if(!in_array($id,$w['giocatori']) && count($w['giocatori']) < $torneo['max_giocatori_per_squadra'])
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
                $errori[] = "Troppi pochi giocatori";
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
    if($azione === 'crea' && $step==3){
        $conn->begin_transaction();
        try{
            $stmt = $conn->prepare("
                INSERT INTO squadra
                (torneo_id,nome,capitano_id,stato)
                VALUES(?,?,?,'in_attesa')
            ");

            $stmt->bind_param("isi",
                $torneo_id,
                $w['nome_squadra'],
                $utente_id
            );

            $stmt->execute();
            $squadra_id = $conn->insert_id;

            $stmt2 = $conn->prepare("
                INSERT INTO giocatore_squadra
                (squadra_id,utente_id)
                VALUES(?,?)
            ");

            foreach($w['giocatori'] as $uid){
                $stmt2->bind_param("ii",$squadra_id,$uid);
                $stmt2->execute();
            }

            $conn->commit();
            unset($_SESSION['wizard_squadra']);

            header("Location: dettagli_torneo.php?id=$torneo_id&squadra_inviata=1");
            exit;

        }catch(Exception $e){
            $conn->rollback();
            $errori[] = "Errore salvataggio";
        }
    }
}

$giocatori_dati = [];

if(!empty($w['giocatori'])){
    $ids = implode(",", array_map("intval",$w['giocatori']));

    $res = $conn->query("
        SELECT id,nome,cognome,email
        FROM utente
        WHERE id IN ($ids)
    ");

    while($r=$res->fetch_assoc()){
        $giocatori_dati[$r['id']] = $r;
    }
}

$risultati = [];

if($step==2 && $cerca!=''){

    $q = "%$cerca%";

    $exclude = count($w['giocatori']) ? implode(",",array_map("intval",$w['giocatori'])) : "0";

    $stmt = $conn->prepare("
        SELECT id,nome,cognome,email
        FROM utente
        WHERE verified=1
        AND (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)
        AND id NOT IN ($exclude)
        LIMIT 10
    ");

    $stmt->bind_param("sss",$q,$q,$q);
    $stmt->execute();

    $res = $stmt->get_result();
    while($r=$res->fetch_assoc()){
        $risultati[] = $r;
    }
}
require_once('templates/header_riservato.php')
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea squadra</title>

    <style>
        body {
            font-family: Arial;
            max-width: 700px;
            margin: 30px auto;
        }

        .step {
            background: #eee;
            padding: 10px;
            margin-bottom: 15px;
        }

        fieldset {
            padding: 15px;
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .errori {
            background: #ffdede;
            padding: 10px;
            margin-bottom: 10px;
        }

        button {
            padding: 8px 12px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<h1>Crea squadra — <?=$torneo['nome']?></h1>
<div class="step">Step <?=$step?> / 3</div>

<?php if (!empty($errori)): ?>
    <div class="errori">
        <?php foreach ($errori as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<!-- FORM PRINCIPALE -->
<form method="POST">
    <input type="hidden" name="step" value="<?=$step?>">

    <?php if ($step == 1): ?>

        <fieldset>
            <legend>Nome squadra</legend>
            <input type="text" name="nome_squadra"
                   value="<?=htmlspecialchars($w['nome_squadra'])?>">
        </fieldset>

        <button name="azione" value="avanti">Avanti</button>

    <?php elseif ($step == 2): ?>

</form>

<!-- RICERCA -->
<form method="GET">
    <input type="hidden" name="torneo_id" value="<?=$torneo_id?>">
    <input type="hidden" name="step" value="2">

    <input type="text" name="cerca" value="<?=htmlspecialchars($cerca)?>">
    <button type="submit">Cerca</button>
</form>

<?php if ($risultati): ?>
    <table>
        <tr>
            <th>Nome</th>
            <th>Email</th>
            <th></th>
        </tr>

        <?php foreach ($risultati as $r): ?>
            <tr>
                <td><?=$r['nome']?> <?=$r['cognome']?></td>
                <td><?=$r['email']?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="aggiungi_id" value="<?=$r['id']?>">
                        <button>+</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h3>Squadra</h3>
<ul>
    <?php foreach ($w['giocatori'] as $id): ?>
        <li>
            <?=$giocatori_dati[$id]['nome']?> <?=$giocatori_dati[$id]['cognome']?>

            <?php if ($id == $utente_id): ?>
                (Capitano)
            <?php else: ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="rimuovi_id" value="<?=$id?>">
                    <button>X</button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<form method="POST">
    <button name="azione" value="indietro">Indietro</button>
    <button name="azione" value="avanti">Avanti</button>
</form>

    <?php elseif ($step == 3): ?>

        <fieldset>
            <legend>Riepilogo</legend>
            <p>Nome: <?=$w['nome_squadra']?></p>
            <p>Giocatori: <?=count($w['giocatori'])?></p>
        </fieldset>

        <form method="POST">
            <button name="azione" value="indietro">Indietro</button>
            <button name="azione" value="crea">Invia richiesta</button>
        </form>

    <?php endif; ?>

</form>

</body>
</html>

<?php require_once('templates/footer.php') ?>