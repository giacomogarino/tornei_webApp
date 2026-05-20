<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../conf/db_config.php");

$token = $_GET['token'] ?? '';

if(empty($token))
    header("Location: ../login.php?msg=errCambioPsw");


// hash del token ricevuto
$token_hash = hash('sha256', $token);

// cerco utente
$stmt = $conn->prepare(
    "SELECT id, token_expiry 
     FROM utente 
     WHERE token = ?"
);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if(!$user)
    header("Location: ../login.php?msg=errCambioPsw");


if($user['token_expiry'] < date("Y-m-d H:i:s"))
    header("Location: ../login.php?msg=errCambioPsw");


$stmt->close();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';


    if(empty($password) || empty($confirm))
        header("Location: ../change_password.php?msg=campiMancanti");
        //die("Compila tutti i campi.");
    

    if($password !== $confirm)
        header("Location: ../change_password.php?msg=passwordDiverse");
        //die("Le password non coincidono.");
    

    if(strlen($password) < 8)
        header("Location: ../change_password.php?msg=passwordCorta");
        //die("Password troppo corta (min 8 caratteri).");
    

    // hash password
    $psw = cryptPsw($password);

    // aggiorno password e invalido token
    $update = $conn->prepare(
        "UPDATE utente
         SET password = ?, token = NULL, token_expiry = NULL
         WHERE id = ?"
    );
    $update->bind_param("si", $psw, $user['id']);

    if($update->execute()){
        header("Location: ../login.php?msg=passwordAggiornata");
        exit;
    }else
        header("Location: ../login.php?msg=errCambioPsw");
        //die("Errore aggiornamento password.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cambia password</title>
</head>
<body>

<h2>Imposta una nuova password</h2>

<form method="POST">
    <label>Nuova password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Conferma password:</label><br>
    <input type="password" name="confirm" required><br><br>

    <button type="submit">Aggiorna password</button>
</form>

    <?php
        if(isset($_GET['msg'])){
            if($_GET['msg'] == 'campiMancanti')
                echo "<div>Compila tutti i campi"."</div>";
            else if($_GET['msg'] == 'passwordDiverse')
                echo "<div>Le password non coincidono"."</div>";
            else if($_GET['msg'] == 'passwordCorta')
                echo "<div>Inserisci una password da almeno 8 caratteri"."</div>";
        }
    ?>

</body>
</html>