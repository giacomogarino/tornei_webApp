<?php
session_start();
include("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;
$view = $_GET['view'] ?? 'classifica';

if (!$torneo_id) die("ID torneo mancante");


# PRENDO TORNEO
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();

if (!$torneo) die("Torneo non trovato");

# CHECK ORGANIZZATORE
$isOrganizzatore = isset($_SESSION['utente_id']) &&
                   $_SESSION['utente_id'] == $torneo['creato_da'];


# FUNZIONI
function prossimoTurno($turno) {
    return match($turno) {
        'ottavi' => 'quarti',
        'quarti' => 'semifinale',
        'semifinale' => 'finale',
        default => null
    };
}

function generaIniziale($conn, $torneo_id) {

    $res = $conn->query("SELECT id FROM squadra WHERE torneo_id = $torneo_id AND stato='approvata'");
    $squadre = [];

    while ($r = $res->fetch_assoc()) $squadre[] = $r['id'];

    $n = count($squadre);
    if ($n < 2) return;

    if (($n & ($n - 1)) != 0) die("Numero squadre non valido");

    shuffle($squadre);

    $turno = match($n) {
        2 => 'finale',
        4 => 'semifinale',
        8 => 'quarti',
        16 => 'ottavi',
        default => 'quarti'
    };

    for ($i = 0; $i < $n; $i += 2) {
        $stmt = $conn->prepare("
            INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $torneo_id, $squadre[$i], $squadre[$i+1], $turno);
        $stmt->execute();
    }
}

function generaTurnoSuccessivo($conn, $torneo_id, $turno) {

    $next = prossimoTurno($turno);
    if (!$next) return;

    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN punti_casa > punti_ospite THEN squadra_casa_id
                ELSE squadra_ospite_id
            END AS vincitore
        FROM partita
        WHERE torneo_id = ? AND turno = ? AND stato = 'terminata'
    ");
    $stmt->bind_param("is", $torneo_id, $turno);
    $stmt->execute();
    $res = $stmt->get_result();

    $vincitori = [];
    while ($r = $res->fetch_assoc()) {
        $vincitori[] = $r['vincitore'];
    }

    if (count($vincitori) < 2) return;

    shuffle($vincitori);

    for ($i = 0; $i < count($vincitori); $i += 2) {
        if (!isset($vincitori[$i+1])) continue;

        $stmt = $conn->prepare("
            INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, turno)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $torneo_id, $vincitori[$i], $vincitori[$i+1], $next);
        $stmt->execute();
    }
}

/* =========================
   GENERAZIONE INIZIALE
========================= */
if ($torneo['stato'] === 'in_corso') {

    $res = $conn->query("SELECT COUNT(*) as tot FROM partita WHERE torneo_id = $torneo_id");
    $tot = $res->fetch_assoc()['tot'];

    if ($tot == 0) {
        generaIniziale($conn, $torneo_id);
    }
}

/* =========================
   INSERIMENTO RISULTATO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore) {

    $partita_id = $_POST['partita_id'];
    $casa = $_POST['casa'];
    $ospite = $_POST['ospite'];

    $stmt = $conn->prepare("
        UPDATE partita
        SET punti_casa = ?, punti_ospite = ?, stato='terminata'
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $casa, $ospite, $partita_id);
    $stmt->execute();

    // prendo turno
    $stmt = $conn->prepare("SELECT turno FROM partita WHERE id = ?");
    $stmt->bind_param("i", $partita_id);
    $stmt->execute();
    $turno = $stmt->get_result()->fetch_assoc()['turno'];

    // controllo se tutte finite
    $stmt = $conn->prepare("
        SELECT COUNT(*) as mancanti
        FROM partita
        WHERE torneo_id = ? AND turno = ? AND stato != 'terminata'
    ");
    $stmt->bind_param("is", $torneo_id, $turno);
    $stmt->execute();
    $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];

    if ($mancanti == 0) {
        generaTurnoSuccessivo($conn, $torneo_id, $turno);
    }

    header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
    exit;
}
require_once('templates/header.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struttura torneo</title>
</head>
<body>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>

<a href="?id=<?= $torneo_id ?>&view=classifica">Classifica</a> |
<a href="?id=<?= $torneo_id ?>&view=partite">Partite</a>

<hr>

<?php if ($view === 'classifica'): ?>

<?php
$stmt = $conn->prepare("
    SELECT s.nome, c.*
    FROM classifica c
    JOIN squadra s ON c.squadra_id = s.id
    WHERE c.torneo_id = ?
    ORDER BY c.punti DESC
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1">
<tr>
    <th>Squadra</th>
    <th>Punti</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['nome']) ?></td>
    <td><?= $row['punti'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php else: ?>

<?php
$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS casa, so.nome AS ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ?
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1">
<tr>
    <th>Casa</th>
    <th>Ospite</th>
    <th>Turno</th>
    <th>Risultato</th>
    <?php if ($isOrganizzatore): ?>
        <th>Inserisci</th>
    <?php endif; ?>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['casa']) ?></td>
    <td><?= htmlspecialchars($row['ospite']) ?></td>
    <td><?= $row['turno'] ?></td>
    <td><?= $row['punti_casa'] ?? '-' ?> - <?= $row['punti_ospite'] ?? '-' ?></td>

    <?php if ($isOrganizzatore && $row['stato'] !== 'terminata'): ?>
    <td>
        <form method="POST">
            <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
            <input type="number" name="casa" required style="width:50px;">
            <input type="number" name="ospite" required style="width:50px;">
            <button>OK</button>
        </form>
    </td>
    <?php elseif ($isOrganizzatore): ?>
        <td>✔</td>
    <?php endif; ?>
</tr>
<?php endwhile; ?>
</table>

<?php endif; ?>

</body>
</html>

<?php
require_once('templates/footer.php');
?>