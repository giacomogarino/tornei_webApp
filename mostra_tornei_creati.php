<?php
require_once 'php/helpers/session.php';
session_secure_start();
include("conf/db_config.php");

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
