<?php
require_once 'php/helpers/csrf.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'php/helpers/session.php';
session_secure_start();
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

$stato_label = ['aperto' => 'Aperto', 'in_corso' => 'In corso', 'completato' => 'Completato'];
$formato_label = [
    'eliminazione_diretta' => 'Eliminazione diretta',
    'girone_unico'         => 'Girone unico',
    'gironi_playoff'       => 'Gironi + playoff',
];
$tipo_label = ['andata' => 'Solo andata', 'andata_ritorno' => 'Andata e ritorno'];

$utente_id = $_SESSION['id_utente'] ?? null;
$check = $conn->prepare("SELECT id FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?");
$check->bind_param("ii", $torneo_id, $utente_id);
$check->execute();
$isFollowing = ($check->get_result()->num_rows > 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    csrf_verify();
    if ($isFollowing) {
        $s = $conn->prepare("DELETE FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?");
    } else {
        $s = $conn->prepare("INSERT INTO torneo_seguito (torneo_id, utente_id) VALUES (?, ?)");
    }
    $s->bind_param("ii", $torneo_id, $utente_id);
    $s->execute();
    header("Location: struttura_torneo.php?id=$torneo_id");
    exit;
}

$navbar_data = [
    'torneo'          => $torneo,
    'isOrganizzatore' => $isOrganizzatore ?? false,
    'isFollowing'     => $isFollowing,
    'stato_label'     => $stato_label,
    'formato_label'   => $formato_label,
    'tipo_label'      => $tipo_label,
];

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