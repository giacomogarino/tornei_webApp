<?php
include("../conf/db_config.php");

$token = trim($_GET['token'] ?? '');

if(empty($token) || strlen($token) !== 64){
    header("location: ../register.php?msg=tokenNonValido");
    exit;
}

// cerca utente con token non verificato
$stmt = $conn->prepare(
    "SELECT id, created_at FROM utente WHERE token = ? AND verified = 0"
);

$stmt->bind_param("s", $token);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 0){
    header("location: ../register.php?msg=tokenNonValido");
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();


// controllo scadenza 24 ore (CORRETTO)
$createdAt = new DateTime($row['created_at']);

$diff = time() - $createdAt->getTimestamp();

if($diff > 86400){

    $del = $conn->prepare(
        "DELETE FROM utente WHERE id = ?"
    );

    $del->bind_param("i", $row['id']);
    $del->execute();

    header("location: ../register.php?msg=tokenScaduto");
    exit;
}


// verifica account
$upd_user = $conn->prepare(
    "UPDATE utente
     SET verified = 1,
         token = NULL
     WHERE id = ?"
);

$upd_user->bind_param("i", $row['id']);

if($upd_user->execute()){
    header("location: ../login.php?msg=registrazioneCompletata");
}else{
    header("location: ../register.php?msg=errMsg");
}

$upd_user->close();
$conn->close();

?>