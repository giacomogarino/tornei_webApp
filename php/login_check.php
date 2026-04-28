<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../conf/db_config.php");

$email = trim($_POST["email"] ?? '');
$psw = $_POST["password"] ?? '';

if(empty($email) || empty($psw)){
    header("location: ../index.php?msg=campiVuoti");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM utente WHERE email = ?");
if(!$stmt){
    die("Errore prepare: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$password = cryptPsw($psw);

if($row && ($password == $row['password'])){

    //controllo email verificata
    if($row['verified'] == 0){
        header("location: ../login.php?msg=emailNonConfermata");
        exit;
    }

    $_SESSION['login'] = 'ok';
    $_SESSION['id_utente'] = $row['id'];
    $_SESSION['nome_utente'] = $row['nome'];

    header("location: ../index.php");
    exit;

} else {
    header("location: ../login.php?msg=errLogin");
    exit;
}

$stmt->close();
$conn->close();
?>