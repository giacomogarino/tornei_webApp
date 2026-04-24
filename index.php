<?php
include("../conf/db_config.php");
require_once 'templates/header.php';

// Recupero filtri dalla GET
$filtro_ricerca = $_GET['ricerca'] ?? '';
$filtro_stato   = $_GET['stato']   ?? '';
$filtro_formato = $_GET['formato'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home tornei</title>
</head>
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
            <option value="gironi_playoff"       <?= $filtro_formato === 'gironi_playoff'       ? 'selected' : '' ?>>Gironi + Playoff</option>
        </select>

        <label for="stato">Stato:</label>
        <select id="stato" name="stato">
            <option value="">Tutti</option>
            <option value="aperto"     <?= $filtro_stato === 'aperto'     ? 'selected' : '' ?>>Aperto</option>
            <option value="in_corso"   <?= $filtro_stato === 'in_corso'   ? 'selected' : '' ?>>In corso</option>
            <option value="completato" <?= $filtro_stato === 'completato' ? 'selected' : '' ?>>Completato</option>
        </select>

        <button type="submit">Filtra</button>
        <a href="index.php">Azzera filtri</a>

    </form>

    <hr>

    <!-- Lista tornei filtrati -->
    <?php require_once 'show_tornei.php'; ?>

</body>
</html>