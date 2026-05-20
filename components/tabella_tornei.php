<!--
pagine che utilizzano tabella_tornei.php
- index.php
- seguiti.php
- mostra_torneo_privato.php
-->

<?php
if (!isset($result)) {
    die("Nessun torneo trovato");
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/tabella_tornei.css">
    <title>Torneo crazy</title>
</head>

<div class="tornei-container">

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="torneo-card">

                <div class="torneo-header">
                    <h3>
                        <?= htmlspecialchars($row['nome']) ?>
                    </h3>

                    <span class="torneo-stato">
                        <?= htmlspecialchars($row['stato']) ?>
                    </span>
                </div>

                <div class="torneo-info">

                    <div class="info-item">
                        <span class="label">Sport</span>
                        <span class="value">
                            <?= htmlspecialchars($row['sport']) ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="label">Luogo</span>
                        <span class="value">
                            <?= htmlspecialchars($row['luogo']) ?>
                        </span>
                    </div>
 
                    <div class="info-item">
                        <span class="label">Formato</span>
                        <span class="value">
                            <?= htmlspecialchars($row['formato']) ?>
                        </span>
                    </div>

                </div>

                <div class="torneo-actions">

                    <form method="GET" action="dettagli_torneo.php">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $row['id'] ?>"
                        >

                        <input
                            type="submit"
                            value="Dettagli"
                        >

                    </form>

                    <form method="GET" action="struttura_torneo.php">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $row['id'] ?>"
                        >

                        <input
                            type="submit"
                            value="Struttura"
                        >

                    </form>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-state">
            Nessun torneo trovato
        </div>

    <?php endif; ?>

</div>