<?php
include("./templates/header.php")
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
                <input type="text" id="n_carta_identita" name="n_carta_identita" placeholder="n_carta_identita" required><br>

                <input type="submit" value="Registrati">
            </form>
        </div>
        <div>
            Se sei registrato fai il
            <a href="index.php">login</a>
        </div>

        <?php
        if(isset($_GET) && $_GET['msg'] == 'errRegister')
            echo "<div>Non hai inserito tutti i dati o non sono corretti"."</div>";
        ?>

    </section>

<?php
include("./templates/footer.php")
?>      