<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if(session_status() === PHP_SESSION_NONE)
    session_start();
include_once("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;
$view = $_GET['view'] ?? 'classifica';

if(!$torneo_id){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

# PRENDO TORNEO
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo){
    header("Location: dettagli_torneo.php?msg=err");
    exit;
}

$formato = $torneo['formato'];

switch($formato) {

    case 'eliminazione_diretta':
        require("components/torneo_elim_diretta.php");
        break;

    case 'gironi_playoff':
        require("components/torneo_misto.php");
        break;

    case 'girone_unico':
        require("components/torneo_gironi.php");
        break;

    default:
        header("Location: dettagli_torneo.php?msg=err");
        exit;
}
?>