<?php
include("../conf/db_config.php");

//print_r($_POST);
// dati comuni
$nome = $_POST['nome'];
$cognome = $_POST['cognome'];
$password = cryptpsw($_POST['password']);
$email= $_POST['email'];
$ci = $_POST['ci'];
$telefono = $_POST['telefono'];

if

if(empty($_POST['n_posti'])){
    $stmt = $conn->prepare("INSERT INTO utente(nome, cognome, password, email, CI, telefono) VALUES (?,?,?,?,?,?)"); //prepararla query
    $stmt->bind_param("ssssss", $nome, $cognome, $password,  $email, $ci, $telefono); //serve per evitare la sql injection
}else{
    $stmt = $conn->prepare("INSERT INTO utente(nome, cognome, password, email, CI, telefono, fotografia, n_patente, scadenza_patente, targa, marca_modello, n_posti) VALUES (?,?,?,?,?,?, ?,?,?,?,?,?)"); //prepara la query
    $stmt->bind_param("sssssssssssi", $nome, $cognome, $password,  $email, $ci, $telefono, $foto, $patente, $scad_patente, $targa, $marca_modello, $n_posti); //serve per evitare la sql injection
}

//esecuzione
if($stmt->execute()){
    header("location: ../registrati.php?msg=okMsg");
}else{
    header("location: ../registrati.php?msg=errMsg");
}

$stmt->close();
$conn->close();
?>