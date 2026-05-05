<?php
include("conf/db_config.php");

$id = isset($_GET['id']) ? $_GET['id']: null;

if(!$id)
    header("Location: dettagli_torneo.php?msg=err");
    //die("ID torneo mancante");


// Recupero torneo
$sql = "SELECT id, nome, descrizione, formato, tipo_partita, visibilita,
            numero_squadre, stato, min_giocatori_per_squadra,
            max_giocatori_per_squadra, min_squadre,
            data_chiusura_iscrizioni, codice_privato
        FROM torneo
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$torneo = $result->fetch_assoc();


// Salvataggio modifiche
if (isset($_POST['salva'])) {
    $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
    $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : '';
    $formato = isset($_POST['formato']) ? $_POST['formato'] : '';
    $tipo_partita = isset($_POST['tipo_partita']) ? $_POST['tipo_partita'] : '';
    $visibilita = isset($_POST['visibilita']) ? $_POST['visibilita'] : '';
    $numero_squadre = isset($_POST['numero_squadre']) ? $_POST['numero_squadre'] : 0;
    $min_giocatori = isset($_POST['min_giocatori']) ? $_POST['min_giocatori'] : 0;
    $max_giocatori = isset($_POST['max_giocatori']) ? $_POST['max_giocatori'] : 0;
    $min_squadre = isset($_POST['min_squadre']) ? $_POST['min_squadre'] : 0;




    $update = "UPDATE torneo
        SET nome = ?,
            descrizione = ?,
            formato = ?,
            tipo_partita = ?,
            visibilita = ?,
            numero_squadre = ?,
            min_giocatori_per_squadra = ?,
            max_giocatori_per_squadra = ?,
            min_squadre = ?
        WHERE id = ?";

    $stmt = $conn->prepare($update);
    $stmt->bind_param(
        "sssssiiiii",
        $nome,
        $descrizione,
        $formato,
        $tipo_partita,
        $visibilita,
        $numero_squadre,
        $min_giocatori,
        $max_giocatori,
        $min_squadre,
        $id
    );

    $stmt->execute();

    header("Location: dettagli_torneo.php?id=" . $id);
    
    exit;
}
require_once('templates/header_riservato.php');
?>

<form method="POST">

    <p>
        <label>Nome torneo</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($torneo['nome']) ?>" required>
    </p>

    <p>
        <label>Descrizione</label><br>
        <textarea name="descrizione" rows="4" cols="50"><?= htmlspecialchars($torneo['descrizione']) ?></textarea>
    </p>

    <p>
        <label>Formato</label><br>
        <select name="formato">
            <option value="eliminazione_diretta" <?= $torneo['formato']=="eliminazione_diretta" ? "selected" : "" ?>>Eliminazione Diretta</option>
            <option value="girone_playoff" <?= $torneo['formato']=="girone_playoff" ? "selected" : "" ?>>Gironi + Playoff</option>
            <option value="girone_unico" <?= $torneo['formato']=="girone_unico" ? "selected" : "" ?>>Girone Unico</option>
        </select>
    </p>

    <p>
        <label>Tipo partita</label><br>
        <select name="tipo_partita">
            <option value="andata" <?= $torneo['tipo_partita']=="andata" ? "selected" : "" ?>>Solo andata</option>
            <option value="andata_ritorno" <?= $torneo['tipo_partita']=="andata_ritorno" ? "selected" : "" ?>>Andata e ritorno</option>
        </select>
    </p>

    <p>
        <label>Visibilità</label><br>
        <select name="visibilita">
            <option value="pubblico" <?= $torneo['visibilita']=="pubblico" ? "selected" : "" ?>>Pubblico</option>
            <option value="privato" <?= $torneo['visibilita']=="privato" ? "selected" : "" ?>>Privato</option>
        </select>
    </p>

    <p>
        <label>Numero squadre</label><br>
        <input type="number" name="numero_squadre" value="<?= $torneo['numero_squadre'] ?>">
    </p>

    <p>
        <label>Min giocatori per squadra</label><br>
        <input type="number" name="min_giocatori" value="<?= $torneo['min_giocatori_per_squadra'] ?>">
    </p>

    <p>
        <label>Max giocatori per squadra</label><br>
        <input type="number" name="max_giocatori" value="<?= $torneo['max_giocatori_per_squadra'] ?>">
    </p>

    <p>
        <label>Min squadre</label><br>
        <input type="number" name="min_squadre" value="<?= $torneo['min_squadre'] ?>">
    </p>


    <button type="submit" name="salva">Salva modifiche</button>
</form>

<?php require_once('templates/footer.php'); ?>
