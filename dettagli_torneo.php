<?php
include("conf/db_config.php");
session_start();

$id = isset($_GET['id']) ? $_GET['id'] : null;

# Recupero torneo
$sql = "SELECT * FROM torneo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$torneo = $result->fetch_assoc();

if (!$torneo) {
    die("Torneo non trovato");
}

$utente_id = $_SESSION['id_utente'] ?? null;

if (!$utente_id) {
    die("Devi essere loggato");
}

$check = "SELECT id FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
$stmt = $conn->prepare($check);
$stmt->bind_param("ii", $id, $utente_id);
$stmt->execute();
$res = $stmt->get_result();

$isFollowing = ($res->num_rows > 0);



if (isset($_POST['toggle_follow'])) {

    if ($isFollowing) {
        $delete = "DELETE FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();

        $isFollowing = false;

    } else {
        # FOLLOW
      $insert = "INSERT INTO torneo_seguito (torneo_id, utente_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();

        $isFollowing = true;
    }

    # refresh pagina per aggiornare stato
    header("Location: dettagli_torneo.php?id=" . $id);
    exit;
}
?>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>
<p>Formato: <?= htmlspecialchars($torneo['formato']) ?></p>
<p>Stato: <?= htmlspecialchars($torneo['stato']) ?></p>

<form method="POST">
    <input type="submit" name="toggle_follow"
           value="<?= $isFollowing ? 'Smetti di seguire il torneo' : 'Segui torneo' ?>">
</form>