<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("conf/db_config.php");

$torneo_id = $_GET['id'] ?? null;

if(!$torneo_id)
    die("ID torneo mancante");

# PRENDO TORNEO
$stmt = $conn->prepare("
    SELECT *
    FROM torneo
    WHERE id = ?
");

$stmt->bind_param("i", $torneo_id);
$stmt->execute();

$torneo = $stmt->get_result()->fetch_assoc();

if(!$torneo)
    die("Torneo non trovato");


# AGGIUNTA SQUADRA
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nome = trim($_POST['nome']);

    if($nome == ""){

        header("Location: aggiunta_squadre_manualmente.php?id=".$torneo_id."&msg=errNome");
        exit;
    }

    # NUMERO MASSIMO SQUADRE
    $max_squadre = $torneo['numero_squadre'];

    # CONTO SQUADRE
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot
        FROM squadra
        WHERE torneo_id = ?
    ");

    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();

    $tot = $stmt->get_result()->fetch_assoc()['tot'];

    # CONTROLLO LIMITE
    if($tot >= $max_squadre){

        header("Location: aggiunta_squadre_manualmente.php?id=".$torneo_id."&msg=limite");
        exit;
    }

    # CAPITANO
    $capitano_id = $_SESSION['id_utente'];

    # INSERT SQUADRA
    $stmt = $conn->prepare("
        INSERT INTO squadra
        (nome, torneo_id, capitano_id, stato)
        VALUES (?, ?, ?, 'approvata')
    ");

    $stmt->bind_param(
        "sii",
        $nome,
        $torneo_id,
        $capitano_id
    );

    $stmt->execute();

    header("Location: aggiunta_squadre_manualmente.php?id=".$torneo_id."&msg=ok");
    exit;
}

require_once('templates/header_riservato.php');
?>

<body>

<h2>Aggiunta squadre manuale</h2>

<p>
    Torneo:
    <strong><?= htmlspecialchars($torneo['nome']) ?></strong>
</p>

<p>
    Squadre massime:
    <?= $torneo['numero_squadre'] ?>
</p>

<hr>

<form method="POST">

    <label>Nome squadra</label>
    <br>

    <input
        type="text"
        name="nome"
        required
    >

    <br><br>

    <button type="submit">
        Aggiungi squadra
    </button>

</form>

<hr>

<?php
$stmt = $conn->prepare("
    SELECT *
    FROM squadra
    WHERE torneo_id = ?
");

$stmt->bind_param("i", $torneo_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<h3>Squadre inserite</h3>

<table border="1">

<tr>
    <th>Nome</th>
    <th>Stato</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>

<tr>
    <td><?= htmlspecialchars($row['nome']) ?></td>
    <td><?= $row['stato'] ?></td>
</tr>

<?php endwhile; ?>

</table>

<br>

<a href="dettaglio_torneo.php?id=<?= $torneo_id ?>">
    Torna al torneo
</a>

<?php
if(isset($_GET['msg'])){

    if($_GET['msg'] == 'errNome'){

        echo "<div>Errore nome non valido</div>";
    }

    else if($_GET['msg'] == 'limite'){

        echo "<div>Numero massimo squadre raggiunto</div>";
    }

    else if($_GET['msg'] == 'ok'){

        echo "<div>Squadra aggiunta correttamente</div>";
    }
}
?>

</body>
</html>

<?php
require_once('templates/footer.php');
?>