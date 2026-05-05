<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conf/db_config.php");

//recupera dati dalla sessione --> persistenza tra step
if (!isset($_SESSION['wizard'])) $_SESSION['wizard'] = [];

$step   = intval($_GET['step'] ?? 1);
$errori = [];

//controlla che il metodo sia post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $step = intval($_POST['step_corrente'] ?? 1);
    $azione = $_POST['azione'] ?? 'avanti';

    if($azione === 'indietro'){
        $prev = max(1, $step - 1);
        header("Location: crea_torneo.php?step=$prev");
        exit;
    }

    //step 1
    if($step === 1){
        $_SESSION['wizard']['formato'] = $_POST['formato'] ?? '';
        $_SESSION['wizard']['tipo_partita'] = $_POST['tipo_partita'] ?? '';

        if(empty($_SESSION['wizard']['formato']))
            $errori[] = "Seleziona un formato di torneo.";
        if(empty($_SESSION['wizard']['tipo_partita']))
            $errori[] = "Seleziona il tipo di partita.";
    }

    //step 2
    elseif($step === 2){
        $_SESSION['wizard']['nome'] = trim($_POST['nome'] ?? '');
        $_SESSION['wizard']['descrizione'] = trim($_POST['descrizione'] ?? '');
        $_SESSION['wizard']['visibilita'] = $_POST['visibilita'] ?? 'pubblico';
        $_SESSION['wizard']['data_chiusura'] = $_POST['data_chiusura'] ?? '';
        $_SESSION['wizard']['sport'] = $_POST['sport'] ?? '';
        $_SESSION['wizard']['luogo'] = trim($_POST['luogo'] ?? '');

        if(empty($_SESSION['wizard']['nome']))
            $errori[] = "Il nome del torneo è obbligatorio.";
        if(empty($_SESSION['wizard']['data_chiusura']))
            $errori[] = "La data di chiusura iscrizioni è obbligatoria.";
        elseif(strtotime($_SESSION['wizard']['data_chiusura']) <= time())
            $errori[] = "La data di chiusura deve essere nel futuro.";
        if(empty($_SESSION['wizard']['sport']))
            $errori[] = "Seleziona uno sport.";
        if(empty($_SESSION['wizard']['luogo']))
            $errori[] = "Il luogo del torneo è obbligatorio.";
    }

    elseif($step === 3){
        $_SESSION['wizard']['numero_squadre'] = intval($_POST['numero_squadre'] ?? 0);
        $_SESSION['wizard']['min_squadre'] = intval($_POST['min_squadre'] ?? 0);
        $_SESSION['wizard']['min_giocatori'] = intval($_POST['min_giocatori'] ?? 0);
        $_SESSION['wizard']['max_giocatori'] = intval($_POST['max_giocatori'] ?? 0);

        $ns = $_SESSION['wizard']['numero_squadre'];
        $ms = $_SESSION['wizard']['min_squadre'];
        $mg = $_SESSION['wizard']['min_giocatori'];
        $xg = $_SESSION['wizard']['max_giocatori'];

        if($ns < 2) $errori[] = "Il numero massimo di squadre deve essere almeno 2.";
        if($ms < 2) $errori[] = "Il numero minimo di squadre deve essere almeno 2.";
        if($ms > $ns) $errori[] = "Il numero minimo non può superare il massimo.";
        if($mg < 1) $errori[] = "Inserisci il numero minimo di giocatori per squadra.";
        if($xg < $mg) $errori[] = "Il numero massimo di giocatori deve essere ≥ al minimo.";
    }

    elseif($step === 4 && $azione === 'crea'){
        $w = $_SESSION['wizard'];

        $codice_privato = null;
        if(($w['visibilita'] ?? 'pubblico') === 'privato')
            $codice_privato = strtoupper(bin2hex(random_bytes(4)));

        $stmt = $conn->prepare("
            INSERT INTO torneo
            (nome, descrizione, formato, tipo_partita, visibilita,
            numero_squadre, min_squadre,
            min_giocatori_per_squadra, max_giocatori_per_squadra,
            data_chiusura_iscrizioni, codice_privato, creato_da, stato,
            sport, luogo)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aperto', ?, ?)
        ");

        $descrizione = $w['descrizione'] ?: null;
        $stmt->bind_param(
            "sssssiiississs",
            $w['nome'],
            $descrizione,
            $w['formato'],
            $w['tipo_partita'],
            $w['visibilita'],
            $w['numero_squadre'],
            $w['min_squadre'],
            $w['min_giocatori'],
            $w['max_giocatori'],
            $w['data_chiusura'],
            $codice_privato,
            $_SESSION['id_utente'],
            $w['sport'],
            $w['luogo']
        );

        $stmt->execute();
        $nuovo_id = $conn->insert_id;

        header("Location: index.php?id=$nuovo_id&nuovo=1");
        exit;
    }

    if(empty($errori)){
        $next = min(4, $step + 1);
        header("Location: crea_torneo.php?step=$next");
        exit;
    }
}

$fmt_label = [
    'eliminazione_diretta' => 'Eliminazione Diretta',
    'girone_unico'         => 'Girone all\'Italiana',
    'gironi_playoff'       => 'Gironi + Playoff',
];
$tipo_label = [
    'andata'         => 'Solo Andata',
    'andata_ritorno' => 'Andata e Ritorno',
];
$sport_label = [
    'calcio'       => 'Calcio',
    'beachvolley'  => 'Beach Volley',
];

$w = $_SESSION['wizard'];

require_once('templates/header_riservato.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea torneo</title>
    <style>
        body{
            font-family:Arial;
            max-width:600px;
            margin:30px auto;
            padding:20px;
        }

        .step{
            margin-bottom:20px;
            padding:10px;
            background:#eee;
        }

        .step strong{
            margin-right:10px;
        }

        fieldset{
            margin-bottom:20px;
            padding:20px;
        }

        label{
            display:block;
            margin-top:15px;
        }

        input,
        select,
        textarea{
            width:100%;
            padding:8px;
            margin-top:5px;
            box-sizing:border-box;
        }

        input[type=radio]{
            width:auto;
            margin-right:8px;
        }

        .radio{
            margin-top:10px;
        }

        .errori{
            background:#ffdede;
            padding:15px;
            margin-bottom:20px;
        }

        .bottoni{
            margin-top:20px;
            display:flex;
            justify-content:space-between;
        }

        button{
            padding:10px 20px;
            cursor:pointer;
        }
    </style>

</head>
<body>

<h1>Crea Torneo</h1>
<p><a href="index.php">Torna alla home</a></p>

<div class="step">
    <strong>Step <?= $step ?>/4</strong>
    <?php
    for($i=1; $i<=4; $i++){
        if($i==$step)
            echo "[Step $i] ";
        else
            echo "Step $i ";
    }
    ?>
</div>

<?php if(!empty($errori)): ?>
<div class="errori">
    <ul>
        <?php foreach($errori as $e): ?>
            <li><?= $e ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="crea_torneo.php">
    <input type="hidden"
           name="step_corrente"
           value="<?= $step ?>">

<?php if($step===1): ?>
    <fieldset>
        <legend>Formato torneo</legend>
        <div class="radio">
            <label>
                <input type="radio"
                       name="formato"
                       value="eliminazione_diretta"
                       <?= (($w['formato'] ?? '')=='eliminazione_diretta') ? 'checked' : '' ?>>
                Eliminazione diretta
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio"
                       name="formato"
                       value="girone_unico"
                       <?= (($w['formato'] ?? '')=='girone_unico') ? 'checked' : '' ?>>
                Girone unico
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio"
                       name="formato"
                       value="gironi_playoff"
                       <?= (($w['formato'] ?? '')=='gironi_playoff') ? 'checked' : '' ?>>
                Gironi + playoff
            </label>
        </div>
    </fieldset>
    <fieldset>
        <legend>Tipo partita</legend>
        <div class="radio">
            <label>
                <input type="radio"
                       name="tipo_partita"
                       value="andata"
                       <?= (($w['tipo_partita'] ?? 'andata')=='andata') ? 'checked' : '' ?>>
                Solo andata
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio"
                       name="tipo_partita"
                       value="andata_ritorno"
                       <?= (($w['tipo_partita'] ?? '')=='andata_ritorno') ? 'checked' : '' ?>>
                Andata e ritorno
            </label>
        </div>
    </fieldset>
    <div class="bottoni">
        <a href="index.php">
            <button type="button">
                Annulla
            </button>
        </a>
        <button type="submit"
                name="azione"
                value="avanti">
            Avanti
        </button>
    </div>

<?php elseif($step===2): ?>
    <fieldset>
        <legend>Dettagli torneo</legend>
        <label>
            Nome torneo
            <input type="text"
                   name="nome"
                   value="<?= htmlspecialchars($w['nome'] ?? '') ?>">
        </label>
        <label>
            Descrizione
            <textarea name="descrizione"><?= htmlspecialchars($w['descrizione'] ?? '') ?></textarea>
        </label>
        <label>
            Sport
            <select name="sport">
                <option value="">-- Seleziona sport --</option>
                <option value="calcio"
                    <?= (($w['sport'] ?? '')=='calcio') ? 'selected' : '' ?>>
                    Calcio
                </option>
                <option value="beachvolley"
                    <?= (($w['sport'] ?? '')=='beachvolley') ? 'selected' : '' ?>>
                    Beach Volley
                </option>
            </select>
        </label>
        <label>
            Luogo
            <input type="text"
                   name="luogo"
                   placeholder="Es. Milano, Campo Sportivo Centro"
                   value="<?= htmlspecialchars($w['luogo'] ?? '') ?>">
        </label>
        <label>
            Visibilità
            <select name="visibilita">
                <option value="pubblico"
                    <?= (($w['visibilita'] ?? 'pubblico')=='pubblico') ? 'selected' : '' ?>>
                    Pubblico
                </option>
                <option value="privato"
                    <?= (($w['visibilita'] ?? '')=='privato') ? 'selected' : '' ?>>
                    Privato
                </option>
            </select>
        </label>
        <label>
            Data chiusura iscrizioni
            <input type="datetime-local"
                   name="data_chiusura"
                   value="<?= htmlspecialchars($w['data_chiusura'] ?? '') ?>">
        </label>
    </fieldset>
    <div class="bottoni">
        <button type="submit"
                name="azione"
                value="indietro">
            Indietro
        </button>
        <button type="submit"
                name="azione"
                value="avanti">
            Avanti
        </button>
    </div>

<?php elseif($step===3): ?>
    <fieldset>
        <legend>Squadre</legend>
        <label>
            Numero massimo squadre
            <input type="number"
                   name="numero_squadre"
                   value="<?= $w['numero_squadre'] ?? 8 ?>">
        </label>
        <label>
            Numero minimo squadre
            <input type="number"
                   name="min_squadre"
                   value="<?= $w['min_squadre'] ?? 4 ?>">
        </label>
    </fieldset>
    <fieldset>
        <legend>Giocatori per squadra</legend>
        <label>
            Min giocatori
            <input type="number"
                   name="min_giocatori"
                   value="<?= $w['min_giocatori'] ?? 5 ?>">
        </label>
        <label>
            Max giocatori
            <input type="number"
                   name="max_giocatori"
                   value="<?= $w['max_giocatori'] ?? 10 ?>">
        </label>
    </fieldset>
    <div class="bottoni">
        <button type="submit"
                name="azione"
                value="indietro">
            Indietro
        </button>
        <button type="submit"
                name="azione"
                value="avanti">
            Avanti
        </button>
    </div>

<?php elseif($step===4): ?>
    <fieldset>
        <legend>Riepilogo</legend>
        <p>
            <b>Nome:</b>
            <?= htmlspecialchars($w['nome']) ?>
        </p>
        <p>
            <b>Sport:</b>
            <?= $sport_label[$w['sport']] ?? $w['sport'] ?>
        </p>
        <p>
            <b>Luogo:</b>
            <?= htmlspecialchars($w['luogo']) ?>
        </p>
        <p>
            <b>Formato:</b>
            <?= $fmt_label[$w['formato']] ?>
        </p>
        <p>
            <b>Tipo partita:</b>
            <?= $tipo_label[$w['tipo_partita']] ?>
        </p>
        <p>
            <b>Visibilità:</b>
            <?= $w['visibilita'] ?>
        </p>
        <p>
            <b>Chiusura iscrizioni:</b>
            <?= $w['data_chiusura'] ?>
        </p>
        <p>
            <b>Squadre:</b>da
            <?= $w['min_squadre'] ?> a 
            <?= $w['numero_squadre'] ?>
        </p>
        <p>
            <b>Giocatori:</b> da
            <?= $w['min_giocatori'] ?> a 
            <?= $w['max_giocatori'] ?>
        </p>
        <?php if(!empty($w['descrizione'])): ?>
            <p>
                <b>Descrizione:</b>
                <?= htmlspecialchars($w['descrizione']) ?>
            </p>
        <?php endif; ?>
    </fieldset>
    <div class="bottoni">
        <button type="submit"
                name="azione"
                value="indietro">
            Indietro
        </button>
        <button type="submit"
                name="azione"
                value="crea">
            Crea torneo
        </button>
    </div>
<?php endif; ?>
</form>
</body>
</html>

<?php require_once('templates/footer.php') ?>
