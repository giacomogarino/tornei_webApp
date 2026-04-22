<?php
include("./templates/header_login.php")
?>

    <section style="text-align: center;">
        <div id="menusx">
            <form method="POST" action="./php/register_check.php">
                <label for="nome">nome:</label><br>
                <input type="text" id="nome" name="nome" placeholder="nome" required><br>

                <label for="cognome">cognome:</label><br>
                <input type="text" id="cognome" name="cognome" placeholder="cognome" required><br>

                <label for="email">email:</label><br>
                <input type="email" id="email" name="email" placeholder="email" required><br>

                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" placeholder="password" required><br>

                <label for="n_carta_identita">N° carta identita:</label><br>
                <input type="text" id="ci" name="ci" placeholder="n_carta_identita" required><br>

                <input type="submit" value="Registrati">
            </form>
        </div>
        <div>
            Se sei registrato fai il
            <a href="login.php">login</a>
        </div>

        <?php
        if(isset($_GET['msg'])){
            $msg = $_GET['msg'];

            if($msg == "campiVuoti"){
                echo "<div style='color:red;'>Compila tutti i campi obbligatori</div>";
            }
            elseif($msg == "emailNonValida"){
                echo "<div style='color:red;'>Email non valida</div>";
            }
            elseif($msg == "passwordDebole"){
                echo "<div style='color:red;'>La password deve avere almeno 8 caratteri</div>";
            }
            elseif($msg == "ciNonValida"){
                echo "<div style='color:red;'>Carta identità non valida</div>";
            }
            elseif($msg == "emailEsistente"){
                echo "<div style='color:red;'>Email già registrata</div>";
            }
            elseif($msg == "errMsg"){
                echo "<div style='color:red;'>Errore durante la registrazione</div>";
            }
            elseif($msg == "okMsg"){
                echo "<div style='color:green;'>Registrazione completata con successo</div>";
            }
        }
        ?>

    </section>

<?php
include("./templates/footer.php")
?>      