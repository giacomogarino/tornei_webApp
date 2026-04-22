<?php
include("../conf/db_config.php");
// home.php - Pagina principale con lista tornei pubblici
require_once 'templates/header.php';

// parte di interrogazione al db con query



?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home – Tornei</title>
</head>
<body>

    <h1>Tornei Pubblici</h1>

    <!-- Tasto creazione torneo -->
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

        <label for="sport">Sport:</label>
        <select id="sport" name="sport">
            <option value="">Tutti</option>
            <option value="calcio"   <?= $filtro_stato === 'calcio'   ? 'selected' : '' ?>>calcio</option>
            <option value="pallavolo"   <?= $filtro_stato === 'pallavolo'   ? 'selected' : '' ?>>pallavolo</option>
            <?php foreach ($sport_disponibili as $sport): ?>
                <option value="<?= htmlspecialchars($sport) ?>" <?= $filtro_sport === $sport ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sport) ?>
                </option>
            <?php endforeach; ?>
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

    <!-- Lista tornei (include la pagina dedicata alla visualizzazione) -->
    <?php require_once 'VISUALIZZA_LISTA_TORNEI.php'; ?>

    <?php if (empty($torneiFiltrati)): ?>
        <p>Nessun torneo trovato con i filtri selezionati.</p>
    <?php else: ?>
        <p>Tornei trovati: <?= count($torneiFiltrati) ?></p>
        <table border="1">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Sport</th>
                    <th>Data inizio</th>
                    <th>Stato</th>
                    <th>Partecipanti</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($torneiFiltrati as $torneo): ?>
                    <tr>
                        <td><?= htmlspecialchars($torneo['nome']) ?></td>
                        <td><?= htmlspecialchars($torneo['sport']) ?></td>
                        <td><?= htmlspecialchars($torneo['data_inizio']) ?></td>
                        <td><?= htmlspecialchars($torneo['stato']) ?></td>
                        <td><?= $torneo['partecipanti'] ?> / <?= $torneo['max_partecipanti'] ?></td>
                        <td>
                            <a href="dettaglio_torneo.php?id=<?= $torneo['id'] ?>">Dettagli</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>