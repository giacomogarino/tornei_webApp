<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

include_once("conf/db_config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$torneo_id = $_GET['id'] ?? null;
$view = $_GET['view'] ?? 'partite';

if (!$torneo_id) die("ID torneo mancante");

# PRENDO TORNEO
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();

$torneo = $stmt->get_result()->fetch_assoc();

if (!$torneo) die("Torneo non trovato");

# CHECK ORGANIZZATORE
$isOrganizzatore = false;

if (
    isset($_SESSION['id_utente']) &&
    $_SESSION['id_utente'] == $torneo['creato_da']
) {
    $isOrganizzatore = true;
}

# FUNZIONI
function prossimoTurno($turno) {

    return match($turno) {
        'ottavi' => 'quarti',
        'quarti' => 'semifinale',
        'semifinale' => 'finale',
        default => null
    };
}

function generaIniziale($conn, $torneo_id, $tipo_partita) {

    // prende squadre approvate
    $res = $conn->query("
        SELECT id
        FROM squadra
        WHERE torneo_id = $torneo_id
        AND stato = 'approvata'
    ");

    $squadre = [];

    while ($r = $res->fetch_assoc()) {
        $squadre[] = $r['id'];
    }

    $n = count($squadre);

    // almeno 2 squadre
    if ($n < 2) return;

    // controlla 2 4 8 16
    if (($n & ($n - 1)) != 0) {
        die("Numero squadre non valido");
    }

    // mischia squadre
    shuffle($squadre);

    // decide turno iniziale
    $turno = match($n) {
        2 => 'finale',
        4 => 'semifinale',
        8 => 'quarti',
        16 => 'ottavi',
        default => 'quarti'
    };

    // crea partite
    for ($i = 0; $i < $n; $i += 2) {

        // ANDATA
        $stmt = $conn->prepare("
            INSERT INTO partita
            (
                torneo_id,
                squadra_casa_id,
                squadra_ospite_id,
                turno,
                tipo
            )
            VALUES (?, ?, ?, ?, 'andata')
        ");

        $stmt->bind_param(
            "iiis",
            $torneo_id,
            $squadre[$i],
            $squadre[$i + 1],
            $turno
        );

        $stmt->execute();

        // RITORNO
        if($tipo_partita == 'andata_ritorno') {

            $stmt = $conn->prepare("
                INSERT INTO partita
                (
                    torneo_id,
                    squadra_casa_id,
                    squadra_ospite_id,
                    turno,
                    tipo
                )
                VALUES (?, ?, ?, ?, 'ritorno')
            ");

            $stmt->bind_param(
                "iiis",
                $torneo_id,
                $squadre[$i + 1],
                $squadre[$i],
                $turno
            );

            $stmt->execute();
        }
    }
}

function generaTurnoSuccessivo($conn, $torneo_id, $turno, $tipo_partita) {

    $next = prossimoTurno($turno);

    if (!$next) return;

    // prende vincitori
    $stmt = $conn->prepare("
        SELECT
            CASE
                WHEN punti_casa > punti_ospite
                THEN squadra_casa_id
                ELSE squadra_ospite_id
            END AS vincitore
        FROM partita
        WHERE torneo_id = ?
        AND turno = ?
        AND stato = 'terminata'
        AND tipo = 'andata'
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

    // crea nuovo turno
    for ($i = 0; $i < count($vincitori); $i += 2) {

        if (!isset($vincitori[$i + 1])) continue;

        // ANDATA
        $stmt = $conn->prepare("
            INSERT INTO partita
            (
                torneo_id,
                squadra_casa_id,
                squadra_ospite_id,
                turno,
                tipo
            )
            VALUES (?, ?, ?, ?, 'andata')
        ");

        $stmt->bind_param(
            "iiis",
            $torneo_id,
            $vincitori[$i],
            $vincitori[$i + 1],
            $next
        );

        $stmt->execute();

        // RITORNO
        if($tipo_partita == 'andata_ritorno') {

            $stmt = $conn->prepare("
                INSERT INTO partita
                (
                    torneo_id,
                    squadra_casa_id,
                    squadra_ospite_id,
                    turno,
                    tipo
                )
                VALUES (?, ?, ?, ?, 'ritorno')
            ");

            $stmt->bind_param(
                "iiis",
                $torneo_id,
                $vincitori[$i + 1],
                $vincitori[$i],
                $next
            );

            $stmt->execute();
        }
    }
}

# GENERAZIONE INIZIALE
if ($torneo['stato'] == 'in_corso') {

    $res = $conn->query("
        SELECT COUNT(*) AS tot
        FROM partita
        WHERE torneo_id = $torneo_id
    ");

    $tot = $res->fetch_assoc()['tot'];

    if ($tot == 0) {

        generaIniziale(
            $conn,
            $torneo_id,
            $torneo['tipo_partita']
        );
    }
}

# GESTIONE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore) {

    // SALVATAGGIO ORARIO
    if (isset($_POST['partita_id_orario'])) {

        $partita_id = $_POST['partita_id_orario'];

        $orario = $_POST['orario'];

        if (empty($orario)) {

            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errOrario");

            exit;
        }

        $stmt = $conn->prepare("
            UPDATE partita
            SET orario = ?
            WHERE id = ?
        ");

        $stmt->bind_param("si", $orario, $partita_id);

        $stmt->execute();

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }

    // INSERIMENTO RISULTATO
    if (isset($_POST['partita_id'])) {

        $partita_id = $_POST['partita_id'];

        $casa = $_POST['casa'];

        $ospite = $_POST['ospite'];

        // controlla negativi
        if ($casa < 0 || $ospite < 0) {

            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errPunti");

            exit;
        }

        // controlla pareggio eliminazione diretta
        if($torneo['formato'] == 'eliminazione_diretta' && $torneo['tipo_partita'] == 'andata' && $casa == $ospite) {
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errRisultato");
            exit;
        }

        // salva risultato
        $stmt = $conn->prepare("
            UPDATE partita
            SET
                punti_casa = ?,
                punti_ospite = ?,
                stato = 'terminata'
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iii",
            $casa,
            $ospite,
            $partita_id
        );

        $stmt->execute();

        // prende turno
        $stmt = $conn->prepare("
            SELECT turno
            FROM partita
            WHERE id = ?
        ");

        $stmt->bind_param("i", $partita_id);

        $stmt->execute();

        $turno = $stmt
            ->get_result()
            ->fetch_assoc()['turno'];

        // controlla partite mancanti
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS mancanti
            FROM partita
            WHERE torneo_id = ?
            AND turno = ?
            AND stato != 'terminata'
        ");

        $stmt->bind_param(
            "is",
            $torneo_id,
            $turno
        );

        $stmt->execute();

        $mancanti = $stmt
            ->get_result()
            ->fetch_assoc()['mancanti'];

        // tutte terminate
        if ($mancanti == 0) {

            // finale
            if ($turno == 'finale') {

                $stmt = $conn->prepare("
                    UPDATE torneo
                    SET stato = 'completato'
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "i",
                    $torneo_id
                );

                $stmt->execute();

            } else {

                generaTurnoSuccessivo(
                    $conn,
                    $torneo_id,
                    $turno,
                    $torneo['tipo_partita']
                );
            }
        }

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }
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

<?php if ($torneo['formato'] != 'eliminazione_diretta'): ?>
    <a href="?id=<?= $torneo_id ?>&view=classifica">
        Classifica
    </a> |
<?php endif; ?>

<a href="?id=<?= $torneo_id ?>&view=partite">
    Partite
</a>

<hr>

<?php if ($view === 'classifica'): ?>

<?php

$stmt = $conn->prepare("
    SELECT s.nome, c.*
    FROM classifica c
    JOIN squadra s
    ON c.squadra_id = s.id
    WHERE c.torneo_id = ?
    ORDER BY c.punti DESC
");

$stmt->bind_param("i", $torneo_id);

$stmt->execute();

$result = $stmt->get_result();

?>

<table border="1" cellpadding="10">

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
    SELECT
        p.*,
        sc.nome AS casa,
        so.nome AS ospite
    FROM partita p
    JOIN squadra sc
    ON p.squadra_casa_id = sc.id
    JOIN squadra so
    ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ?
");

$stmt->bind_param("i", $torneo_id);

$stmt->execute();

$result = $stmt->get_result();

?>

<table border="1" cellpadding="10">

<tr>
    <th>Casa</th>
    <th>Ospite</th>
    <th>Turno</th>
    <th>Tipo</th>
    <th>Orario</th>
    <th>Risultato</th>

    <?php if ($isOrganizzatore): ?>
        <th>Gestione</th>
    <?php endif; ?>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>

<tr>

    <td><?= htmlspecialchars($row['casa']) ?></td>

    <td><?= htmlspecialchars($row['ospite']) ?></td>

    <td><?= $row['turno'] ?></td>

    <td><?= $row['tipo'] ?></td>

    <td>
        <?= $row['orario'] ?? 'non impostato' ?>
    </td>

    <td>
        <?= $row['punti_casa'] ?? '-' ?>
        -
        <?= $row['punti_ospite'] ?? '-' ?>
    </td>

    <?php if ($isOrganizzatore): ?>

    <td>

        <?php if ($row['stato'] != 'terminata'): ?>

        <!-- FORM ORARIO -->
        <form method="POST" style="margin-bottom:10px;">

            <input
                type="hidden"
                name="partita_id_orario"
                value="<?= $row['id'] ?>"
            >

            <input
                type="datetime-local"
                name="orario"
                required
            >

            <button>
                Salva orario
            </button>

        </form>

        <!-- FORM RISULTATO -->
        <form method="POST">

            <input
                type="hidden"
                name="partita_id"
                value="<?= $row['id'] ?>"
            >

            <input
                type="number"
                name="casa"
                required
                style="width:50px;"
            >

            <input
                type="number"
                name="ospite"
                required
                style="width:50px;"
            >

            <button>
                OK
            </button>

        </form>

        <?php else: ?>

            ✔

        <?php endif; ?>

    </td>

    <?php endif; ?>

</tr>

<?php endwhile; ?>

</table>

<?php endif; ?>

<?php

if (isset($_GET['msg'])) {

    if ($_GET['msg'] == 'errRisultato') {

        echo "<div>
                Errore: non possono pareggiare
              </div>";
    }

    else if ($_GET['msg'] == 'errPunti') {

        echo "<div>
                Errore: valori negativi non validi
              </div>";
    }

    else if ($_GET['msg'] == 'errOrario') {

        echo "<div>
                Errore: inserisci un orario valido
              </div>";
    }
}

?>

</body>
</html>

<?php
require_once('templates/footer.php');
?>