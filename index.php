<?php
include("../conf/db_config.php");
require_once 'templates/header.php';

// Recupero filtri dalla GET
$filtro_ricerca = $_GET['ricerca'] ?? '';
$filtro_sport   = $_GET['sport']   ?? '';
$filtro_stato   = $_GET['stato']   ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Tornei</title>
</head>
<body>

    <h1>Tornei pubblici</h1>

    <!-- Tasto crea torneo -->
    <a href="crea_torneo.php">
        <button>Crea nuovo torneo</button>
    </a>

    <hr>

    <!-- filtri -->
    <form method="GET" action="index.php">

        <label for="ricerca">Cerca per nome:</label>
        <input
            type="text"
            id="ricerca"
            name="ricerca"
            value="<?= htmlspecialchars($filtro_ricerca) ?>"
            placeholder="Nome torneo..."
        >

        <label for="sport">Sport:</label>
        <select id="sport" name="sport">
            <option value="">Tutti</option>
            <option value="calcio"    <?= $filtro_sport === 'calcio'    ? 'selected' : '' ?>>Calcio</option>
            <option value="pallavolo" <?= $filtro_sport === 'pallavolo' ? 'selected' : '' ?>>Pallavolo</option>
        </select>

        <label for="stato">Stato:</label>
        <select id="stato" name="stato">
            <option value="">Tutti</option>
            <option value="aperto"   <?= $filtro_stato === 'aperto'   ? 'selected' : '' ?>>Aperto</option>
            <option value="in_corso" <?= $filtro_stato === 'in_corso' ? 'selected' : '' ?>>In corso</option>
            <option value="concluso" <?= $filtro_stato === 'concluso' ? 'selected' : '' ?>>Concluso</option>
        </select>

        <button type="submit">Filtra</button>
        <a href="index.php">Azzera filtri</a>

    </form>

    <hr>

    <!-- Lista tornei filtrati -->
    <?php require_once 'show_tornei.php'; ?>

</body>
</html>