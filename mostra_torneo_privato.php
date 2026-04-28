<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include("conf/db_config.php");

$filtro_ricerca = $_GET['ricerca'] ?? '';

$result = null;

if(!empty($filtro_ricerca)){

    $sql = "SELECT id, nome, formato, stato
            FROM torneo
            WHERE visibilita='privato'
            AND codice_privato = ?";

    $stmt = $conn->prepare($sql);

    if(!$stmt)
        die("Errore prepare: " . $conn->error);
    

    $cod = ($filtro_ricerca);

    $stmt->bind_param("s", $cod);

    $stmt->execute();

    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Torneo</title>
</head>
<body>

<h2>Torneo</h2>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nome torneo</th>
        <th>Formato</th>
        <th>Stato</th>
        <th>Dettagli</th>
    </tr>

    <?php 
    if($result && $result->num_rows > 0){

        while($row = $result->fetch_assoc()){
            echo '<tr>';
            echo '<td>'.$row['id'].'</td>';
            echo '<td>'.$row['nome'].'</td>';
            echo '<td>'.$row['formato'].'</td>';
            echo '<td>'.$row['stato'].'</td>';
            echo '<td>
                    <form method="GET" action="dettagli_torneo.php">
                        <input type="hidden" name="id" value="' . $row['id'] . '">
                        <input type="submit" value="Dettagli torneo">
                    </form>
                </td>';
            echo '</tr>';
        }

    }
    else{
        echo '<tr><td colspan="5">Nessun torneo trovato</td></tr>';
    }
    ?>

</table>

</body>
</html>

<?php
if(isset($stmt)){
    $stmt->close();
}
$conn->close();
?>