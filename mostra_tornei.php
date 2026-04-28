<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("conf/db_config.php");

$filtro_ricerca = isset($_GET['ricerca']) ? $_GET['ricerca'] : '';
$filtro_stato   = isset($_GET['stato']) ? $_GET['stato'] : '';
$filtro_formato = isset($_GET['formato']) ? $_GET['formato'] : '';

$sql = "SELECT id, nome, formato, stato
        FROM torneo
        WHERE visibilita = 'pubblico'";

$parametri = [];
$tipi = "";

if (!empty($filtro_ricerca)) {
    $sql .= " AND nome LIKE ?";
    $parametri[] = "%" . $filtro_ricerca . "%";
    $tipi .= "s";
}

if (!empty($filtro_formato)) {
    $sql .= " AND formato = ?";
    $parametri[] = $filtro_formato;
    $tipi .= "s";
}

if (!empty($filtro_stato)) {
    $sql .= " AND stato = ?";
    $parametri[] = $filtro_stato;
    $tipi .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Errore nella prepare: " . $conn->error);
}

if (!empty($parametri)) {
    $stmt->bind_param($tipi, ...$parametri);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Lista Tornei</title>
</head>
<body>

<h2>LISTA TORNEI</h2>

<?php include("components/tabella_tornei.php"); ?>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>