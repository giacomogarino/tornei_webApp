<?php
if (!isset($result)) {
    die("Errore: nessun result passato alla tabella");
}
?>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nome torneo</th>
        <th>Formato</th>
        <th>Stato</th>
        <th>Dettagli</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['formato']) ?></td>
                <td><?= htmlspecialchars($row['stato']) ?></td>
                <td>
                    <form method="GET" action="dettagli_torneo.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="submit" value="Dettagli torneo">
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>

    <?php else: ?>
        <tr>
            <td colspan="5">Nessun torneo trovato</td>
        </tr>
    <?php endif; ?>
</table>