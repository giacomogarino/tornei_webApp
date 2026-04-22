<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include("../conf/db_config.php");

$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$email = trim($_POST['email'] ?? '');
$psw = $_POST['password'] ?? '';
$ci = trim($_POST['ci'] ?? '');

//controllo campi e validazione mail e psq
if(empty($nome) || empty($cognome) || empty($email) || empty($psw)){
    header("location: ../registrati.php?msg=campiVuoti");
    exit;
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    header("location: ../registrati.php?msg=emailNonValida");
    exit;
}

if(strlen($psw) < 8){
    header("location: ../registrati.php?msg=passwordDebole");
    exit;
}

if(!empty($ci) && strlen($ci) < 5){
    header("location: ../registrati.php?msg=ciNonValida");
    exit;
}

$password = cryptPsw($psw);

// controllo che non esista già
$check = $conn->prepare("SELECT id FROM utente WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if($check->num_rows > 0){
    header("location: ../registrati.php?msg=emailEsistente");
    exit;
}
$check->close();


//inserimento nel database
$stmt = $conn->prepare("INSERT INTO utente(nome, cognome, password, email, cod_ci) VALUES (?,?,?,?,?)");

if(!$stmt){
    die("Errore prepare: " . $conn->error);
}

$stmt->bind_param("sssss", $nome, $cognome, $password, $email, $ci);

if($stmt->execute()){
    header("location: ../login.php");
} else {
    header("location: ../registrati.php?msg=errMsg");
}

$stmt->close();
$conn->close();
?>