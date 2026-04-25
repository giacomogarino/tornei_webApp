<?php
// show_tornei.php
// Dipendenze attese dal file chiamante (index.php):
//   $conn            -> connessione mysqli (da db_config.php)
//   $filtro_ricerca  -> stringa di ricerca per nome torneo
//   $filtro_stato    -> 'aperto' | 'in_corso' | 'completato' | ''
//   $filtro_formato  -> 'girone_unico' | 'eliminazione_diretta' | 'gironi_playoff' | ''

// Costruzione query dinamica con prepared statement
$sql    = "SELECT 
                t.id,
                t.nome,
                t.descrizione,
                t.formato,
                t.tipo_partita,
                t.numero_squadre,
                t.stato,
                t.min_giocatori_per_squadra,
                t.max_giocatori_per_squadra,
                CONCAT(u.nome, ' ', u.cognome) AS creatore,
                COUNT(DISTINCT s.id) AS squadre_iscritte
           FROM torneo t
           INNER JOIN utente u ON u.id = t.creato_da
           LEFT JOIN squadra s ON s.torneo_id = t.id AND s.stato = 'approvata'
           WHERE t.visibilita = 'pubblico'";

$params = [];
$types  = '';

if ($filtro_ricerca !== '') {
    $sql     .= " AND t.nome LIKE ?";
    $params[] = '%' . $filtro_ricerca . '%';
    $types   .= 's';
}

if ($filtro_stato !== '') {
    $sql     .= " AND t.stato = ?";
    $params[] = $filtro_stato;
    $types   .= 's';
}

if (!empty($filtro_formato)) {
    $sql     .= " AND t.formato = ?";
    $params[] = $filtro_formato;
    $types   .= 's';
}

$sql .= " GROUP BY t.id
          ORDER BY t.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$risultato = $stmt->get_result();
$tornei    = $risultato->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Etichette leggibili per i valori ENUM
$label_formato = [
    'girone_unico'          => 'Girone unico',
    'eliminazione_diretta'  => 'Eliminazione diretta',
    'gironi_playoff'        => 'Gironi + Playoff',
];

$label_tipo = [
    'andata'         => 'Solo andata',
    'andata_ritorno' => 'Andata e ritorno',
];

$label_stato = [
    'aperto'     => 'Aperto',
    'in_corso'   => 'In corso',
    'completato' => 'Completato',
];
?>

<?php if (empty($tornei)): ?>
    <p>Nessun torneo trovato con i filtri selezionati.</p>
<?php else: ?>
    <p>Tornei trovati: <?= count($tornei) ?></p>
    <table border="1">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Descrizione</th>
                <th>Formato</th>
                <th>Tipo partita</th>
                <th>Stato</th>
                <th>Squadre iscritte</th>
                <th>Max squadre</th>
                <th>Giocatori per squadra</th>
                <th>Creato da</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tornei as $torneo): ?>
                <tr>
                    <td><?= htmlspecialchars($torneo['nome']) ?></td>
                    <td><?= htmlspecialchars($torneo['descrizione'] ?? '-') ?></td>
                    <td><?= $label_formato[$torneo['formato']] ?? $torneo['formato'] ?></td>
                    <td><?= $label_tipo[$torneo['tipo_partita']] ?? $torneo['tipo_partita'] ?></td>
                    <td><?= $label_stato[$torneo['stato']] ?? $torneo['stato'] ?></td>
                    <td><?= $torneo['squadre_iscritte'] ?> / <?= $torneo['numero_squadre'] ?></td>
                    <td><?= $torneo['numero_squadre'] ?></td>
                    <td><?= $torneo['min_giocatori_per_squadra'] ?> - <?= $torneo['max_giocatori_per_squadra'] ?></td>
                    <td><?= htmlspecialchars($torneo['creatore']) ?></td>
                    <td>
                        <a href="dettaglio_torneo.php?id=<?= $torneo['id'] ?>">Dettagli</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>