<?php
session_start();
include("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;
if (!$torneo_id) die("ID torneo mancante");

// PRENDO TORNEO
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if (!$torneo) die("Torneo non trovato");

// CHECK ORGANIZZATORE
$isOrganizzatore = isset($_SESSION['id_utente']) && $_SESSION['id_utente'] == $torneo['creato_da'];

// INSERIMENTO / UPDATE PRANZO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore) {

    $squadra_id = $_POST['squadra_id'];
    $orario = $_POST['orario'];

    $stmt = $conn->prepare("
        INSERT INTO pranzi (torneo_id, squadra_id, orario)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE orario = VALUES(orario)
    ");
    $stmt->bind_param("iis", $torneo_id, $squadra_id, $orario);
    $stmt->execute();

    header("Location: gestione_pranzi.php?id=$torneo_id");
    exit;
}

require_once('templates/header.php');
?>

<h2>Gestione Pranzi</h2>

<?php if (!$isOrganizzatore): ?>

<!-- VISUALIZZAZIONE UTENTE -->
<?php
$stmt = $conn->prepare("
    SELECT p.orario, s.nome
    FROM pranzi p
    JOIN squadra s ON p.squadra_id = s.id
    WHERE p.torneo_id = ?
    ORDER BY p.orario
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1">
<tr>
    <th>Squadra</th>
    <th>Orario pranzo</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['nome']) ?></td>
    <td><?= $row['orario'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php else: ?>

<!-- ORGANIZZATORE -->
<?php if ($torneo['stato'] != 'in_corso'): ?>

    <p>I pranzi saranno disponibili dopo la chiusura delle iscrizioni.</p>

<?php else: ?>

<?php
$stmt = $conn->prepare("
    SELECT s.id, s.nome, p.orario
    FROM squadra s
    LEFT JOIN pranzi p 
        ON p.squadra_id = s.id AND p.torneo_id = ?
    WHERE s.torneo_id = ? AND s.stato = 'approvata'
");
$stmt->bind_param("ii", $torneo_id, $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1">
<tr>
    <th>Squadra</th>
    <th>Orario pranzo</th>
    <th>Gestione</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['nome']) ?></td>

    <td><?= $row['orario'] ?? 'non impostato' ?></td>

    <td>
        <form method="POST">
            <input type="hidden" name="squadra_id" value="<?= $row['id'] ?>">
            <input type="datetime-local" name="orario" required>
            <button>Salva</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php endif; ?>

<?php endif; ?>

<?php require_once('templates/footer.php'); ?>