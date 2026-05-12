<?php

/**
 * Mostra le squadre approvate passate come array.
 *
 * @param array    $squadre    Risultato della query (array di associativi)
 * @param int|null $utente_id  ID utente loggato (opzionale, per label "sei il capitano")
 */
function mostra_squadre_approvate(array $squadre, ?int $utente_id = null): void
{
    if (empty($squadre)): ?>
        <p><em>Nessuna squadra approvata al momento.</em></p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th align="left">#</th>
                <th align="left">Nome squadra</th>
                <th align="left"></th>
            </tr>
            <?php foreach ($squadre as $i => $squadra): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <?= htmlspecialchars($squadra['nome']) ?>
                    <?php if ($utente_id && $squadra['capitano_id'] == $utente_id): ?>
                        <em>(sei il capitano)</em>
                    <?php endif; ?>
                </td>
                <td>
<<<<<<< HEAD
                    <a href="../dettagli_squadra.php?id=<?= $squadra['id'] ?>">Vedi squadra</a>
=======
                    <a href="dettagli_squadra.php?id=<?= $squadra['id'] ?>">Vedi squadra</a>
>>>>>>> 1c9788b6eec4dae6094cfee28cd7ce8c1a7c8bbf
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif;
}