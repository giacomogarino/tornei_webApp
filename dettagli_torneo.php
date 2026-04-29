<?php
include("conf/db_config.php");
session_start();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die("ID torneo mancante");
}

$sql = "SELECT id, nome, descrizione, formato, tipo_partita, visibilita, numero_squadre,
               creato_da, stato, min_giocatori_per_squadra, max_giocatori_per_squadra,
               min_squadre, data_chiusura_iscrizioni, codice_privato
        FROM torneo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$torneo = $result->fetch_assoc();

if (!$torneo) {
    die("Torneo non trovato");
}

$utente_id = $_SESSION['id_utente'] ?? null;

/*if (!$utente_id) {
    die("Devi essere loggato");
}*/

$check = "SELECT id FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
$stmt = $conn->prepare($check);
$stmt->bind_param("ii", $id, $utente_id);
$stmt->execute();
$res = $stmt->get_result();
$isFollowing = ($res->num_rows > 0);

if (isset($_POST['toggle_follow'])) {
    if ($isFollowing) {
        $delete = "DELETE FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();
        $isFollowing = false;
    } else {
        $insert = "INSERT INTO torneo_seguito (torneo_id, utente_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();
        $isFollowing = true;
    }
    header("Location: dettagli_torneo.php?id=" . $id);
    exit;
}

require_once('templates/header_riservato.php')
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($torneo['nome']) ?> - Dettagli Torneo</title>
</head>
<body>

<hr>

<?php if ($torneo['descrizione']): ?>
    <p><?= htmlspecialchars($torneo['descrizione']) ?></p>
    <hr>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr>
        <th align="left" width="220">Campo</th>
        <th align="left">Valore</th>
    </tr>
    <tr>
        <td><b>ID</b></td>
        <td><?= $torneo['id'] ?></td>
    </tr>
    <tr>
        <td><b>Formato</b></td>
        <td><?= htmlspecialchars($torneo['formato']) ?></td>
    </tr>
    <tr>
        <td><b>Tipo partita</b></td>
        <td><?= htmlspecialchars($torneo['tipo_partita']) ?></td>
    </tr>
    <tr>
        <td><b>Visibilità</b></td>
        <td><?= htmlspecialchars($torneo['visibilita']) ?></td>
    </tr>
    <tr>
        <td><b>Stato</b></td>
        <td><?= htmlspecialchars($torneo['stato']) ?></td>
    </tr>
    <tr>
        <td><b>Numero squadre</b></td>
        <td><?= $torneo['numero_squadre'] ?></td>
    </tr>
    <tr>
        <td><b>Squadre minime</b></td>
        <td><?= $torneo['min_squadre'] ?></td>
    </tr>
    <tr>
        <td><b>Giocatori per squadra</b></td>
        <td>min <?= $torneo['min_giocatori_per_squadra'] ?> — max <?= $torneo['max_giocatori_per_squadra'] ?></td>
    </tr>
    <tr>
        <td><b>Chiusura iscrizioni</b></td>
        <td><?= htmlspecialchars($torneo['data_chiusura_iscrizioni']) ?></td>
    </tr>
    <?php if ($torneo['visibilita'] === 'privato' && $torneo['codice_privato']): ?>
    <tr>
        <td><b>Codice privato</b></td>
        <td><?= htmlspecialchars($torneo['codice_privato']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<br>
<?php if ($torneo['stato'] == 'aperto'): ?>
    <a href="aggiungi_squadra.php?torneo_id=<?= $torneo['id'] ?>">
        <button>Aggiungi squadra</button>
    </a>
<?php endif; ?>

<form method="POST" style="display:inline;">
    <button type="submit" name="toggle_follow">
        <?= $isFollowing ? 'Smetti di seguire' : ' Segui torneo' ?>
    </button>
</form>

</body>
</html>

<?php require_once('templates/footer.php') ?>