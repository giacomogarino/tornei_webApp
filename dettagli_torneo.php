<?php
include("conf/db_config.php");

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID torneo mancante");
}

$sql = "SELECT * FROM torneo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$torneo = $result->fetch_assoc();

if (!$torneo) {
    die("Torneo non trovato");
}

echo "<h2>" . htmlspecialchars($torneo['nome']) . "</h2>";
echo "<p>Formato: " . htmlspecialchars($torneo['formato']) . "</p>";
echo "<p>Stato: " . htmlspecialchars($torneo['stato']) . "</p>";
?>