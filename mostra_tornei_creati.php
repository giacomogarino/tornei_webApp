<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include("conf/db_config.php");
if(session_status() === PHP_SESSION_NONE)
    session_start();

$filtro_ricerca = $_SESSION['id_utente'];

$result = null;

if(!empty($filtro_ricerca)){

    $sql = "SELECT id, nome, formato, stato, sport, luogo
            FROM torneo
            WHERE creato_da = ?";

    $stmt = $conn->prepare($sql);

    if(!$stmt)
        header("location: ../privati.php?msg=err");
        //die("Errore prepare: " . $conn->error);
    

    $cod = ($filtro_ricerca);

    $stmt->bind_param("s", $cod);

    $stmt->execute();

    $result = $stmt->get_result();
}

include("components/tabella_tornei.php");

if(isset($stmt)){
    $stmt->close();
}
$conn->close();
?>