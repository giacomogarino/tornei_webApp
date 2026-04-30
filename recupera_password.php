<?php
require_once("./templates/header.php");
?>

    <section style="text-align: center;">
        <div id="menusx">
            <form method="POST" action="./php/recovery.php">
                <label for="email">Email:</label><br>
                <input type="text" id="email" name="email" placeholder="email"><br>
                <input type="submit" value="Recupera password">
            </form>
        </div>
        <div>
            Se non sei registrato
            <a href="register.php">registrati</a>
        </div>

        <?php
        if(isset($_GET) && $_GET['msg'] == 'errLogin')
            echo "<div>Email o password errata"."</div>";
        ?>

    </section>

<?php
require_once("./templates/footer.php");
?>