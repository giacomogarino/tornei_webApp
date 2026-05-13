<?php
include("conf/db_config.php");
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// Dati della squadra + nome torneo
$sql = "SELECT s.id, s.nome, s.stato, s.capitano_id, s.torneo_id,
               t.nome AS nome_torneo
        FROM squadra s
        JOIN torneo t ON t.id = s.torneo_id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$squadra) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// Giocatori della squadra con nome e cognome
$sql_giocatori = "SELECT u.id, u.nome, u.cognome
                  FROM giocatore_squadra gs
                  JOIN utente u ON u.id = gs.utente_id
                  WHERE gs.squadra_id = ?
                  ORDER BY u.cognome ASC, u.nome ASC";
$stmt = $conn->prepare($sql_giocatori);
$stmt->bind_param("i", $id);
$stmt->execute();
$giocatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$utente_id = isset($_SESSION['id_utente']) ? $_SESSION['id_utente'] : null;
$is_capitano = ($utente_id && $utente_id == $squadra['capitano_id']);

require_once('templates/header_riservato.php');
?>
<body>

<h3><?= htmlspecialchars($squadra['nome']) ?> - Dettagli squadra</h3>

<p>
    Torneo: <a href="dettagli_torneo.php?id=<?= $squadra['torneo_id'] ?>">
        <?= htmlspecialchars($squadra['nome_torneo']) ?>
    </a>
</p>

<hr>

<?php if ($is_capitano): ?>
    <form action="modifica_squadra.php?id=<?= $squadra['id'] ?>" method="POST">
        <button type="submit" name="modifica">Modifica squadra</button>
    </form>
    <br>
<?php endif; ?>

<!-- Tabella dati squadra -->
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr>
        <th align="left" width="220">Campo</th>
        <th align="left">Valore</th>
    </tr>
    <tr>
        <td><b>ID</b></td>
        <td><?= $squadra['id'] ?></td>
    </tr>
    <tr>
        <td><b>Nome</b></td>
        <td><?= htmlspecialchars($squadra['nome']) ?></td>
    </tr>
    <tr>
        <td><b>Stato</b></td>
        <td><?= htmlspecialchars($squadra['stato']) ?></td>
    </tr>
</table>

<br>
<h4>Giocatori (<?= count($giocatori) ?>)</h4>

<?php if (empty($giocatori)): ?>
    <p><em>Nessun giocatore nella squadra.</em></p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <tr>
            <th align="left">#</th>
            <th align="left">Nome</th>
            <th align="left">Cognome</th>
            <th align="left">Ruolo</th>
        </tr>
        <?php foreach ($giocatori as $i => $g): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($g['nome']) ?></td>
            <td><?= htmlspecialchars($g['cognome']) ?></td>
            <td><?= ($g['id'] == $squadra['capitano_id']) ? '<b>Capitano</b>' : 'Giocatore' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'err'): ?>
    <div>Errore, riprova più tardi.</div>
<?php endif; ?>

</body>
</html>

<?php require_once('templates/footer.php') ?>