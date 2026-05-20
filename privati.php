<?php
require_once 'templates/header.php';
include("conf/db_config.php");

$filtro_ricerca = $_GET['ricerca'] ?? '';

$result = null;

if (!empty($filtro_ricerca)) {

    $sql = "SELECT id, nome, formato, stato, sport, luogo
            FROM torneo
            WHERE visibilita = 'privato'
            AND codice_privato = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("location: privati.php?msg=err");
        exit;
    }

    $stmt->bind_param("s", $filtro_ricerca);
    $stmt->execute();

    $result = $stmt->get_result();
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/privati.css">
    <title>Torneo crazy</title>
</head>
<h1>Tornei privati</h1>


<!-- FORM FILTRO -->
<form method="GET" action="privati.php">

    <label for="ricerca">Cerca per codice torneo:</label>

    <input
        type="text"
        id="ricerca"
        name="ricerca"
        value="<?= htmlspecialchars($filtro_ricerca) ?>"
        placeholder="Codice torneo..."
    >

    <button type="submit">Filtra</button>

    <a href="privati.php">
        Azzera filtri
    </a>

</form>

<hr>

<h2>Risultati</h2>

<?php
if (!empty($filtro_ricerca)) {
    include("components/tabella_tornei.php");
} else {
    echo "<div>Inserisci un codice torneo per cercare</div>";
}
?>

<?php
if (isset($_GET['msg']) && $_GET['msg'] == 'err') {
    echo "<div style='color:red;'>Errore, riprova più tardi</div>";
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

require_once('templates/footer.php');
?>