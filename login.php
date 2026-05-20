<?php
session_start();
session_unset(); 
session_destroy();

require_once("./templates/header.php");
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/register.css">
    <title>Torneo crazy</title>
</head>

<section style="text-align: center;">
    <div id="menusx">
        <form method="POST" action="./php/login_check.php">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" placeholder="email">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="password">
            <input type="submit" value="Accedi">
        </form>
    </div>
    <div>
        Se non sei registrato
        <a href="register.php">registrati</a>
    </div>
    <div>
        Se hai dimanticato la password
        <a href="recupera_password.php">recuperala</a>
    </div>

    <?php
    if(isset($_GET['msg'])){
        if($_GET['msg'] == 'errLogin')
            echo "<div style='color:red;'>Email o password errata"."</div>";
        else if($_GET['msg'] == 'campiVuoti')
            echo "<div style='color:red;'>Compila tutti i campi"."</div>";
        else if($_GET['msg'] == 'emailNonConfermata')
            echo "<div style='color:red;'>Devi confermare la mail per poter accedere"."</div>";
        else if($_GET['msg'] == 'ok')
            echo "<div style='color:red;'>Controlla la email per cambiare la password"."</div>";
        else if($_GET['msg'] == 'passwordAggiornata')
            echo "<div style='color:red;'>Password aggiornata correttamente"."</div>";
        else if($_GET['msg'] == 'errCambioPsw')
            echo "<div style='color:red;'>Errore nel cambio della password"."</div>";
        else if($_GET['msg'] == 'err')
            echo "<div style='color:red;'>Errore nel login riprova più tardi"."</div>";
        else if($_GET['msg'] == 'NecessariaAutentificazione')
            echo "<div style='color:red;'>Devi prima autentificarti"."</div>";
    }
    ?>

</section>

<?php
require_once("./templates/footer.php");
?>