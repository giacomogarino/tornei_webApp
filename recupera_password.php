<?php
require_once("./templates/header.php");
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/recupera_password.css">
    <title>Torneo crazy</title>
</head>

    <section style="text-align: center;">
        <div id="menusx">
            <form method="POST" action="./php/recovery.php">
                <label for="email">Email:</label>
                <input type="text" id="email" name="email" placeholder="email">
                <input type="submit" value="Recupera password">
            </form>
        </div>

        <?php
        if(isset($_GET) && $_GET['msg'] == 'emptyEmail')
            echo "<div>Inserisci la tua email"."</div>";
        ?>

    </section>

<?php
require_once("./templates/footer.php");
?>