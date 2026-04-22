<?php
// show_tornei.php
// Si aspetta che $conn, $filtro_ricerca, $filtro_sport, $filtro_stato
// siano già definiti dal file che lo include (index.php).

// Costruzione query dinamica con prepared statement
$sql    = "SELECT id, nome, sport, data_inizio, stato, partecipanti, max_partecipanti
           FROM tornei
           WHERE pubblico = 1";
$params = [];
$types  = '';

if ($filtro_ricerca !== '') {
    $sql     .= " AND nome LIKE ?";
    $params[] = '%' . $filtro_ricerca . '%';
    $types   .= 's';
}

if ($filtro_sport !== '') {
    $sql     .= " AND sport = ?";
    $params[] = $filtro_sport;
    $types   .= 's';
}

if ($filtro_stato !== '') {
    $sql     .= " AND stato = ?";
    $params[] = $filtro_stato;
    $types   .= 's';
}

$sql .= " ORDER BY data_inizio ASC";

// Esecuzione con prepared statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$risultato = $stmt->get_result();
$tornei    = $risultato->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if (empty($tornei)): ?>
    <p>Nessun torneo trovato con i filtri selezionati.</p>
<?php else: ?>
    <p>Tornei trovati: <?= count($tornei) ?></p>
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
            <?php foreach ($tornei as $torneo): ?>
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