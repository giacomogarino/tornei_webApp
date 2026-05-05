<?php
require_once 'templates/header.php';
?>

<body>

    <h1>Tornei privati</h1>

    <hr>

    <form method="GET" action="privati.php">

        <label for="ricerca">Cerca per codice torneo:</label>
        <input
            type="text"
            id="ricerca"
            name="ricerca"
            value="<?= htmlspecialchars($filtro_ricerca) ?>"
            placeholder="Codice torneo..."
        >

        <button type="submit">Filtra</button>
        <a href="privati.php">Azzera filtri</a>

    </form>

    <hr>
    <?php require_once 'mostra_torneo_privato.php'; ?>

    <?php
        if(isset($_GET['msg'])){
            if($_GET['msg'] == 'err')
                echo "<div>Errore riprova più tardi"."</div>";
        }
    ?>

</body>
</html>

<?php require_once('templates/footer.php') ?>