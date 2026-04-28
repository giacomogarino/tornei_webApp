<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("conf/db_config.php");

# Recupero filtri dalla GET
$filtro_ricerca = $_GET['ricerca'] ?? '';
$filtro_stato   = $_GET['stato'] ?? '';
$filtro_formato = $_GET['formato'] ?? '';

# Query base
$sql = "SELECT id, nome, formato, stato
        FROM torneo
        WHERE 1=1";

# Array per prepared statement
$parametri = [];
$tipi = "";

# Filtro ricerca nome torneo
if (!empty($filtro_ricerca)) {
    $sql .= " AND nome LIKE ?";
    $parametri[] = "%" . $filtro_ricerca . "%";
    $tipi .= "s";
}

# Filtro formato
if (!empty($filtro_formato)) {
    $sql .= " AND formato = ?";
    $parametri[] = $filtro_formato;
    $tipi .= "s";
}

# Filtro stato
if (!empty($filtro_stato)) {
    $sql .= " AND stato = ?";
    $parametri[] = $filtro_stato;
    $tipi .= "s";
}

# Ordinamento finale
$sql .= " ORDER BY id DESC";

# Preparazione query
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Errore nella prepare: " . $conn->error);
}

# Bind parametri se presenti
if (!empty($parametri)) {
    $stmt->bind_param($tipi, ...$parametri);
}

# Esecuzione
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

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nome torneo</th>
        <th>Formato</th>
        <th>Stato</th>
        <th>Dettagli</th>
    </tr>

    <?php 
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['formato']) . '</td>';
            echo '<td>' . htmlspecialchars($row['stato']) . '</td>';
            echo '<td>
                    <form method="GET" action="dettagli_torneo.php">
                        <input type="hidden" name="id" value="' . $row['id'] . '">
                        <input type="submit" value="Dettagli torneo">
                    </form>
                </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">Nessun torneo trovato</td></tr>';
    }
    ?>

</table>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>