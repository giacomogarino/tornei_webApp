<?php
session_start();
include("conf/db_config.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// Dati squadra + torneo
$sql = "
    SELECT s.id, s.nome, s.stato, s.capitano_id, s.torneo_id, s.persone_pranzo,
           t.nome AS nome_torneo, t.pranzo AS pranzo
    FROM squadra s
    JOIN torneo t ON t.id = s.torneo_id
    WHERE s.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
$stmt->close();
echo "<script>console.log(" . json_encode($squadra) . ");</script>";

if (!$squadra) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// update persone pranzo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['persone_pranzo'])) {

        $persone = (int)$_POST['persone_pranzo'];

        if ($persone < 0) $persone = 0;

        $stmt = $conn->prepare("
            UPDATE squadra
            SET persone_pranzo = ?
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $persone, $id);
        $stmt->execute();

        header("Location: dettagli_squadra.php?id=$id&msg=ok");
        exit;
    }
}

// giocatori squadra
$sql_giocatori = "
    SELECT u.id, u.nome, u.cognome
    FROM giocatore_squadra gs
    JOIN utente u ON u.id = gs.utente_id
    WHERE gs.squadra_id = ?
    ORDER BY u.cognome ASC, u.nome ASC
";

$stmt = $conn->prepare($sql_giocatori);
$stmt->bind_param("i", $id);
$stmt->execute();
$giocatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$utente_id = $_SESSION['id_utente'] ?? null;
$is_capitano = ($utente_id && $utente_id == $squadra['capitano_id']);

require_once('templates/header_riservato.php');
?>

<body>

<h3><?= htmlspecialchars($squadra['nome']) ?> - Dettagli squadra</h3>

<p>
    Torneo:
    <a href="dettagli_torneo.php?id=<?= $squadra['torneo_id'] ?>">
        <?= htmlspecialchars($squadra['nome_torneo']) ?>
    </a>
</p>

<hr>

<!-- INFO SQUADRA -->
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr><th>Campo</th><th>Valore</th></tr>

    <tr>
        <td>ID</td>
        <td><?= $squadra['id'] ?></td>
    </tr>

    <tr>
        <td>Nome</td>
        <td><?= htmlspecialchars($squadra['nome']) ?></td>
    </tr>

    <tr>
        <td>Stato</td>
        <td><?= htmlspecialchars($squadra['stato']) ?></td>
    </tr>
    <?php if ($squadra['pranzo']==1): ?>
    <tr>
        <td>Persone pranzo <?=$squadra['pranzo']?></td>
        <td><?= (int)$squadra['persone_pranzo'] ?></td>
    </tr>
    <?php endif; ?>
</table>

<br>

<!-- FORM SOLO CAPITANO -->
<?php if ($is_capitano && $squadra['pranzo']==1): ?>
    <h4>Gestione pranzo</h4>

    <form method="POST">
        <label>Numero persone che mangiano</label><br>
        <p>value pranzo<?=$squadra['pranzo']?></p>

        <input
            type="number"
            name="persone_pranzo"
            min="0"
            value="<?= (int)$squadra['persone_pranzo'] ?>"
            required
        >

        <br><br>

        <button type="submit">
            Salva
        </button>
    </form>

    <br>
<?php endif; ?>

<hr>

<h4>Giocatori (<?= count($giocatori) ?>)</h4>

<?php if (empty($giocatori)): ?>
    <p><em>Nessun giocatore nella squadra.</em></p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Cognome</th>
            <th>Ruolo</th>
        </tr>

        <?php foreach ($giocatori as $i => $g): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($g['nome']) ?></td>
            <td><?= htmlspecialchars($g['cognome']) ?></td>
            <td>
                <?= ($g['id'] == $squadra['capitano_id']) ? '<b>Capitano</b>' : 'Giocatore' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
    <div>✔ Dati aggiornati correttamente</div>
<?php endif; ?>

</body>
</html>

<?php require_once('templates/footer.php'); ?>