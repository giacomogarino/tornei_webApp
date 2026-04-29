<?php
include("conf/db_config.php");

$id = isset($_GET['id']) ? $_GET['id']: null;

if (!$id) {
    die("ID torneo mancante");
}

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
    $stato = isset($_POST['stato']) ? $_POST['stato'] : '';
    $min_giocatori = isset($_POST['min_giocatori']) ? $_POST['min_giocatori'] : 0;
    $max_giocatori = isset($_POST['max_giocatori']) ? $_POST['max_giocatori'] : 0;
    $min_squadre = isset($_POST['min_squadre']) ? $_POST['min_squadre'] : 0;
    $data_chiusura = isset($_POST['data_chiusura']) ? $_POST['data_chiusura'] : '';
    $codice_privato = isset($_POST['codice_privato']) ? $_POST['codice_privato'] : '';

    $update = "UPDATE torneo
            SET nome = ?,
                descrizione = ?,
                formato = ?,
                tipo_partita = ?,
                visibilita = ?,
                numero_squadre = ?,
                stato = ?,
                min_giocatori_per_squadra = ?,
                max_giocatori_per_squadra = ?,
                min_squadre = ?,
                data_chiusura_iscrizioni = ?,
                codice_privato = ?
            WHERE id = ?";

    $stmt = $conn->prepare($update);
    $stmt->bind_param(
        "sssssisiiissi",
        $nome,
        $descrizione,
        $formato,
        $tipo_partita,
        $visibilita,
        $numero_squadre,
        $stato,
        $min_giocatori,
        $max_giocatori,
        $min_squadre,
        $data_chiusura,
        $codice_privato,
        $id
    );

    $stmt->execute();

    header("Location: dettagli_torneo.php?id=" . $id);
    require_once('templates/header_riservato.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Torneo</title>
</head>
<body>

<h2>Modifica Torneo</h2>

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
        <input type="text" name="formato" value="<?= htmlspecialchars($torneo['formato']) ?>">
    </p>

    <p>
        <label>Tipo partita</label><br>
        <input type="text" name="tipo_partita" value="<?= htmlspecialchars($torneo['tipo_partita']) ?>">
    </p>

    <p>
        <label>Visibilità</label><br>
        <input type="text" name="visibilita" value="<?= htmlspecialchars($torneo['visibilita']) ?>">
    </p>

    <p>
        <label>Numero squadre</label><br>
        <input type="number" name="numero_squadre" value="<?= $torneo['numero_squadre'] ?>">
    </p>

    <p>
        <label>Stato (aperto / chiuso)</label><br>
        <input type="text" name="stato" value="<?= htmlspecialchars($torneo['stato']) ?>">
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

    <p>
        <label>Data chiusura iscrizioni</label><br>
        <input type="date" name="data_chiusura" value="<?= $torneo['data_chiusura_iscrizioni'] ?>">
    </p>

    <p>
        <label>Codice privato</label><br>
        <input type="text" name="codice_privato" value="<?= htmlspecialchars($torneo['codice_privato']) ?>">
    </p>

    <button type="submit" name="salva">Salva modifiche</button>
</form>

</body>
</html>

<?php require_once('templates/footer.php'); ?>
