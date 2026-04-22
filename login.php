<?php
session_start();
session_unset(); 
session_destroy();

require_once("./templates/header_login.php");
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

        <?php
        if(isset($_GET) && $_GET['msg'] == 'errLogin')
            echo "<div>Email o password errata"."</div>";
        ?>

    </section>

<?php
require_once("./templates/footer.php");
?>