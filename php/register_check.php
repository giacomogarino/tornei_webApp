<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../conf/db_config.php");

$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$email = trim($_POST['email'] ?? '');
$psw = $_POST['password'] ?? '';
$ci = trim($_POST['ci'] ?? '');

//Validazioni 
if(empty($nome) || empty($cognome) || empty($email) || empty($psw)){
    header("location: ../register.php?msg=campiVuoti"); 
    exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    header("location: ../register.php?msg=emailNonValida");
    exit;
}
if(strlen($psw) < 8){
    header("location: ../register.php?msg=passwordDebole"); 
    exit;
}
if(!empty($ci) && strlen($ci) < 5){
    header("location: ../register.php?msg=ciNonValida"); 
    exit;
}

$password = cryptPsw($psw);

//Controllo email
$check = $conn->prepare("SELECT id, verified FROM utente WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if($row = $result->fetch_assoc()){
    $existingId = $row['id'];
    $existingVerified = $row['verified'];
}

if($check->num_rows > 0){
    if($existingVerified){
        header("location: ../register.php?msg=emailEsistente"); exit;
    } else {
        $del = $conn->prepare("DELETE FROM utente WHERE id = ?");
        $del->bind_param("i", $existingId);
        $del->execute();
        $del->close();
    }
}
$check->close();

//genera token
$token = bin2hex(random_bytes(32));

//inserisci ma non verificato (0)
$stmt = $conn->prepare(
    "INSERT INTO utente (nome, cognome, password, email, cod_ci, token, verified)
     VALUES (?, ?, ?, ?, ?, ?, 0)"
);
$stmt->bind_param("ssssss", $nome, $cognome, $password, $email, $ci, $token);

if(!$stmt->execute()){
    header("location: ../register.php?msg=errMsg"); exit;
}
$stmt->close();

//link verifica
$baseUrl = "https://matchoratorneo.netsons.org/staging";
$link    = "$baseUrl/php/verify_email.php?token=$token";

//invia email
$subject = 'Conferma la tua registrazione';
$body    = "Ciao $nome,\n\nConferma il tuo account cliccando qui:\n\n$link\n\nIl link scade tra 24 ore.";
$headers = "From: noreply@matchoratorneo.netsons.org\r\nContent-Type: text/plain; charset=UTF-8";

if(mail($email, $subject, $body, $headers)){
    header("location: ../register.php?msg=confermaInviata");
} else {
    $del = $conn->prepare("DELETE FROM utente WHERE token = ?");
    $del->bind_param("s", $token);
    $del->execute();
    $del->close();

    header("location: ../register.php?msg=errMsg");
}

$conn->close();
?>