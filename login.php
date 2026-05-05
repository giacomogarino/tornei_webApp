<?php
session_start();
session_unset(); 
session_destroy();

require_once("./templates/header.php");
?>

    <section style="text-align: center;">
        <div id="menusx">
            <form method="POST" action="./php/login_check.php">
                <label for="email">Email:</label><br>
                <input type="text" id="email" name="email" placeholder="email"><br>
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" placeholder="password"><br><br>
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
                echo "<div>Email o password errata"."</div>";
            else if($_GET['msg'] == 'campiVuoti')
                echo "<div>Compila tutti i campi"."</div>";
            else if($_GET['msg'] == 'emailNonConfermata')
                echo "<div>Devi confermare la mail per poter accedere"."</div>";
            else if($_GET['msg'] == 'ok')
                echo "<div>Controlla la email per cambiare la password"."</div>";
            else if($_GET['msg'] == 'passwordAggiornata')
                echo "<div>Password aggiornata correttamente"."</div>";
            else if($_GET['msg'] == 'errCambioPsw')
                echo "<div>Errore nel cambio della password"."</div>";
            else if($_GET['msg'] == 'err')
                echo "<div>Errore nel login riprova più tardi"."</div>";
            else if($_GET['msg'] == 'NecessariaAutentificazione')
                echo "<div>Devi prima autentificarti"."</div>";
        }
        ?>

    </section>

<?php
require_once("./templates/footer.php");
?>