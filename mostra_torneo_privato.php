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
        header("location: ../privati.php?msg=err");
        //die("Errore prepare: " . $conn->error);
    

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

<h2>Torneo privato</h2>
<?php include("components/tabella_tornei.php"); ?>

</body>
</html>

<?php
if(isset($stmt)){
    $stmt->close();
}
$conn->close();
?>