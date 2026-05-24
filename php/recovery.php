<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../conf/db_config.php");

$email = trim($_POST['email'] ?? '');

if(empty($email)){
    header("location: ../recupera_password.php?msg=emptyEmail");
    exit;
}

$check = $conn->prepare("SELECT nome FROM utente WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
$row = $result->fetch_assoc();

if($row){
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $date = date("Y-m-d H:i:s", strtotime("+24 hours"));

    $stmt = $conn->prepare(
        "UPDATE utente
         SET token = ?, token_expiry = ?
         WHERE email = ?"
    );
    $stmt->bind_param("sss", $token_hash, $date, $email);
    $stmt->execute();
    $stmt->close();

    $baseUrl = "https://matchoratorneo.netsons.org/staging";
    $link = "$baseUrl/php/change_password.php?token=$token";

    $subject = 'Recupera la tua password';
    $body = "Ciao {$row['nome']},\n\nRecupera la tua password cliccando qui:\n\n$link\n\nIl link scade tra 24 ore.";
    $headers = "From: noreply@matchoratorneo.netsons.org\r\nContent-Type: text/plain; charset=UTF-8";

    if(!mail($email, $subject, $body, $headers)){
        $del = $conn->prepare(
            "UPDATE utente
             SET token = NULL, token_expiry = NULL
             WHERE email = ?"
        );
        $del->bind_param("s", $email);
        $del->execute();
        $del->close();
    }
}

$conn->close();

// risposta sempre uguale
header("location: ../login.php?msg=ok");
exit;
?>