<?php
include("./templates/header.php")
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/register.css">
    <title>Torneo crazy</title>
</head>
    <section style="text-align: center;">
        <div id="menusx">
            <form method="POST" action="./php/register_check.php">
                <label for="nome">nome:</label>
                <input type="text" id="nome" name="nome" placeholder="nome" required>

                <label for="cognome">cognome:</label>
                <input type="text" id="cognome" name="cognome" placeholder="cognome" required>

                <label for="email">email:</label>
                <input type="email" id="email" name="email" placeholder="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="password" required>

                <label for="password">Conferma password:</label>
                <input type="password" id="password2" name="password2" placeholder="password" required>

                <label for="n_carta_identita">N° carta identita:</label>
                <input type="text" id="ci" name="ci" placeholder="n_carta_identita" required>

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

            if($msg == "campiVuoti")
                echo "<div style='color:red;'>Compila tutti i campi obbligatori</div>";
            elseif($msg == "emailNonValida")
                echo "<div style='color:red;'>Email non valida</div>";
            elseif($msg == "passwordDebole")
                echo "<div style='color:red;'>La password deve avere almeno 8 caratteri</div>";
            elseif($msg == "ciNonValida")
                echo "<div style='color:red;'>Carta identità non valida</div>";
            elseif($msg == "emailEsistente")
                echo "<div style='color:red;'>Email già registrata</div>";
            elseif($msg == "errMsg")
                echo "<div style='color:red;'>Errore durante la registrazione</div>";
            elseif($msg == "confermaInviata")
                echo "<div style='color:green;'>Registrazione completata con successo, conferma la mail per poter accedere</div>";
            elseif($msg == "passwordDiverse")
                echo "<div style='color:red;'>Le password non corrispondono</div>";
        }
        ?>

    </section>

<?php
include("./templates/footer.php")
?>      