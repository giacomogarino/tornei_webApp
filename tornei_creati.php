<?php
require_once('templates/header_riservato.php')
?>

<body>

    <h1>Tornei creati</h1>

    <?php require_once 'mostra_tornei_creati.php'; ?>

    <?php
        if(isset($_GET['msg'])){
            if($_GET['msg'] == 'err')
                echo "<div>Errore riprova più tardi"."</div>";
        }
    ?>

</body>
</html>

<?php require_once('templates/footer.php') ?>