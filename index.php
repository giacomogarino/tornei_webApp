<?php
require_once 'templates/header.php';
?>

<body>

    <h1>Tornei pubblici</h1>

    <!-- Tasto crea torneo -->
    <a href="crea_torneo.php">
        <button>Crea nuovo torneo</button>
    </a>

    <hr>

    <!-- Filtri -->
    <form method="GET" action="index.php">

        <label for="ricerca">Cerca per nome:</label>
        <input
            type="text"
            id="ricerca"
            name="ricerca"
            value="<?= htmlspecialchars($filtro_ricerca) ?>"
            placeholder="Nome torneo..."
        >

        <label for="formato">Formato:</label>
        <select id="formato" name="formato">
            <option value="">Tutti</option>
            <option value="girone_unico"         <?= $filtro_formato === 'girone_unico'         ? 'selected' : '' ?>>Girone unico</option>
            <option value="eliminazione_diretta" <?= $filtro_formato === 'eliminazione_diretta' ? 'selected' : '' ?>>Eliminazione diretta</option>
            <option value="gironi_playoff"       <?= $filtro_formato === 'gironi_playoff'       ? 'selected' : '' ?>>gironi + playoff</option>
        </select>

        <label for="stato">Stato:</label>
        <select id="stato" name="stato">
            <option value="">Tutti</option>
            <option value="aperto"     <?= $filtro_stato === 'aperto'     ? 'selected' : '' ?>>Aperto</option>
            <option value="in_corso"   <?= $filtro_stato === 'in_corso'   ? 'selected' : '' ?>>In corso</option>
            <option value="completato" <?= $filtro_stato === 'completato' ? 'selected' : '' ?>>Completato</option>
        </select>

        <button type="submit">Filtra</button>
        <button type="submit" href="index.php">Azzera filtri</button>

    </form>

    <hr>
    
    <?php require_once 'mostra_tornei.php'; ?>

    <?php
        if(isset($_GET['msg'])){
            if($_GET['msg'] == 'errTorneoNonTrovato')
                echo "<div>Errore torneo non trovato riprova più tardi"."</div>";
            else if($_GET['msg'] == 'err')
                echo "<div>Errore riprova più tardi"."</div>";
        }
    ?>
</body>
</html>

<?php require_once('templates/footer.php') ?>