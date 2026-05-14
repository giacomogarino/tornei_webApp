<?php
// Incluso da struttura_torneo.php
// Variabili disponibili: $conn, $torneo, $torneo_id, $isOrganizzatore, $view

$tipo_partita = $torneo['tipo_partita']; // 'andata' | 'andata_ritorno'

# CHECK ORGANIZZATORE
$isOrganizzatore = isset($_SESSION['id_utente']) &&
                    $_SESSION['id_utente'] == $torneo['creato_da'];

/* =====================================================
   FUNZIONI
===================================================== */

function girone_generaPartite($conn, $torneo_id, $tipo_partita) {

    $stmt = $conn->prepare("
        SELECT id FROM squadra
        WHERE torneo_id = ? AND stato = 'approvata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $squadre = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

    $n = count($squadre);
    if ($n < 2) return;

    // Se dispari aggiunge un "bye" (null)
    if ($n % 2 !== 0) {
        $squadre[] = null;
        $n++;
    }

    $meta    = $n / 2;
    $giornate = [];

    // Round-robin: fissa la prima squadra, ruota le altre
    $lista = $squadre;
    $fisso = array_shift($lista);

    for ($g = 0; $g < $n - 1; $g++) {
        $giro    = array_merge([$fisso], $lista);
        $partite = [];

        for ($i = 0; $i < $meta; $i++) {
            $casa   = $giro[$i];
            $ospite = $giro[$n - 1 - $i];

            if ($casa === null || $ospite === null) continue;

            // Alterna chi gioca in casa ogni giornata
            if ($g % 2 === 0) {
                $partite[] = [$casa, $ospite, 'andata'];
            } else {
                $partite[] = [$ospite, $casa, 'andata'];
            }
        }

        $giornate[$g + 1] = $partite;

        // Rotazione
        array_unshift($lista, array_pop($lista));
    }

    // Se andata e ritorno: duplica le giornate invertendo casa/ospite
    if ($tipo_partita === 'andata_ritorno') {
        $tot = count($giornate);
        foreach ($giornate as $g => $partite) {
            $ritorno = [];
            foreach ($partite as [$casa, $ospite, $_]) {
                $ritorno[] = [$ospite, $casa, 'ritorno'];
            }
            $giornate[$g + $tot] = $ritorno;
        }
    }

    // INSERT nel DB nell'ordine giornata per giornata
    $stmt = $conn->prepare("
        INSERT INTO partita (torneo_id, squadra_casa_id, squadra_ospite_id, girone, tipo)
        VALUES (?, ?, ?, 1, ?)
    ");

    foreach ($giornate as $partite) {
        foreach ($partite as [$casa, $ospite, $tipo]) {
            $stmt->bind_param("iiis", $torneo_id, $casa, $ospite, $tipo);
            $stmt->execute();
        }
    }
}

function girone_classifica($conn, $torneo_id) {

    $stmt = $conn->prepare("
        SELECT id, nome FROM squadra
        WHERE torneo_id = ? AND stato = 'approvata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $squadreRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $classifica = [];
    foreach ($squadreRaw as $sq) {
        $classifica[$sq['id']] = [
            'id'   => $sq['id'],
            'nome' => $sq['nome'],
            'G'    => 0, 'V' => 0, 'P' => 0, 'S' => 0,
            'GF'   => 0, 'GS' => 0, 'DR' => 0, 'Pts' => 0
        ];
    }

    $stmt = $conn->prepare("
        SELECT * FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL AND stato = 'terminata'
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $partite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($partite as $p) {
        $c  = $p['squadra_casa_id'];
        $o  = $p['squadra_ospite_id'];
        $gc = (int)$p['punti_casa'];
        $go = (int)$p['punti_ospite'];

        if (!isset($classifica[$c]) || !isset($classifica[$o])) continue;

        $classifica[$c]['G']++;       $classifica[$o]['G']++;
        $classifica[$c]['GF'] += $gc; $classifica[$c]['GS'] += $go;
        $classifica[$o]['GF'] += $go; $classifica[$o]['GS'] += $gc;

        if ($gc > $go) {
            $classifica[$c]['V']++; $classifica[$c]['Pts'] += 3;
            $classifica[$o]['S']++;
        } elseif ($gc < $go) {
            $classifica[$o]['V']++; $classifica[$o]['Pts'] += 3;
            $classifica[$c]['S']++;
        } else {
            $classifica[$c]['P']++; $classifica[$c]['Pts']++;
            $classifica[$o]['P']++; $classifica[$o]['Pts']++;
        }
    }

    foreach ($classifica as &$sq) {
        $sq['DR'] = $sq['GF'] - $sq['GS'];
    }

    usort($classifica, fn($a, $b) =>
        $b['Pts'] <=> $a['Pts']
        ?: $b['DR']  <=> $a['DR']
        ?: $b['GF']  <=> $a['GF']
        ?: strcmp($a['nome'], $b['nome'])
    );

    return array_values($classifica);
}

/* =====================================================
   GENERAZIONE AUTOMATICA
===================================================== */

if ($torneo['stato'] === 'in_corso') {

    $stmt = $conn->prepare("
        SELECT COUNT(*) as tot FROM partita
        WHERE torneo_id = ? AND girone IS NOT NULL
    ");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $tot = $stmt->get_result()->fetch_assoc()['tot'];

    if ($tot == 0) {
        girone_generaPartite($conn, $torneo_id, $tipo_partita);
    }
}

/* =====================================================
   GESTIONE POST
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOrganizzatore) {

    // SALVATAGGIO ORARIO
    if (isset($_POST['partita_id_orario'])) {

        $partita_id = (int)$_POST['partita_id_orario'];
        $orario     = $_POST['orario'];

        if (empty($orario)) {
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errOrario");
            exit;
        }

        $stmt = $conn->prepare("UPDATE partita SET orario = ? WHERE id = ?");
        $stmt->bind_param("si", $orario, $partita_id);
        $stmt->execute();

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }

    // INSERIMENTO RISULTATO
    if (isset($_POST['partita_id'])) {

        $partita_id = (int)$_POST['partita_id'];
        $casa       = (int)$_POST['casa'];
        $ospite     = (int)$_POST['ospite'];

        if ($casa < 0 || $ospite < 0) {
            header("Location: struttura_torneo.php?id=$torneo_id&view=partite&msg=errPunti");
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE partita
            SET punti_casa = ?, punti_ospite = ?, stato = 'terminata'
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $casa, $ospite, $partita_id);
        $stmt->execute();

        // Se tutte le partite del girone sono finite → torneo completato
        $stmt = $conn->prepare("
            SELECT COUNT(*) as mancanti FROM partita
            WHERE torneo_id = ? AND girone IS NOT NULL AND stato != 'terminata'
        ");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $mancanti = $stmt->get_result()->fetch_assoc()['mancanti'];

        if ($mancanti == 0) {
            $stmt = $conn->prepare("UPDATE torneo SET stato = 'completato' WHERE id = ?");
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
        }

        header("Location: struttura_torneo.php?id=$torneo_id&view=partite");
        exit;
    }
}

/* =====================================================
   DATI PER LA VIEW
===================================================== */

$classifica  = girone_classifica($conn, $torneo_id);
$nSquadre    = count($classifica);
$perGiornata = max(1, (int)floor($nSquadre / 2));

$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS casa, so.nome AS ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.girone IS NOT NULL
    ORDER BY p.id
");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$tuttePartite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Raggruppa per giornata in base all'ordine di inserimento
$giornate = [];
foreach ($tuttePartite as $i => $p) {
    $g = (int)floor($i / $perGiornata) + 1;
    $giornate[$g][] = $p;
}

/* =====================================================
   VIEW
===================================================== */
require_once('templates/header.php');
?>

<h2><?= htmlspecialchars($torneo['nome']) ?></h2>
<p>
    <?= htmlspecialchars($torneo['sport']) ?> —
    <?= $tipo_partita === 'andata_ritorno' ? 'Andata e ritorno' : 'Solo andata' ?> —
    <?= htmlspecialchars($torneo['luogo']) ?>
</p>

<a href="?id=<?= $torneo_id ?>&view=classifica">Classifica</a> |
<a href="?id=<?= $torneo_id ?>&view=partite">Partite</a>

<hr>

<?php if ($view === 'classifica'): ?>

    <h3>Classifica</h3>

    <table border="1" cellpadding="8">
    <tr>
        <th>#</th><th>Squadra</th><th>G</th><th>V</th><th>P</th><th>S</th>
        <th>GF</th><th>GS</th><th>DR</th><th>Pts</th>
    </tr>
    <?php foreach ($classifica as $pos => $sq): ?>
    <tr>
        <td><?= $pos + 1 ?></td>
        <td><?= htmlspecialchars($sq['nome']) ?></td>
        <td><?= $sq['G'] ?></td>
        <td><?= $sq['V'] ?></td>
        <td><?= $sq['P'] ?></td>
        <td><?= $sq['S'] ?></td>
        <td><?= $sq['GF'] ?></td>
        <td><?= $sq['GS'] ?></td>
        <td><?= $sq['DR'] ?></td>
        <td><strong><?= $sq['Pts'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
    </table>

<?php else: ?>

    <h3>Partite</h3>

    <?php if (empty($tuttePartite)): ?>
        <p>Nessuna partita ancora generata.</p>
    <?php else: ?>

        <?php foreach ($giornate as $numGiornata => $righe): ?>

        <h4>Giornata <?= $numGiornata ?></h4>

        <table border="1" cellpadding="8">
        <tr>
            <th>Casa</th>
            <th>Ospite</th>
            <?php if ($tipo_partita === 'andata_ritorno'): ?><th>Tipo</th><?php endif; ?>
            <th>Orario</th>
            <th>Risultato</th>
            <?php if ($isOrganizzatore): ?><th>Gestione</th><?php endif; ?>
        </tr>

        <?php foreach ($righe as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['casa']) ?></td>
            <td><?= htmlspecialchars($row['ospite']) ?></td>
            <?php if ($tipo_partita === 'andata_ritorno'): ?>
                <td><?= ucfirst($row['tipo']) ?></td>
            <?php endif; ?>
            <td>
                <?= $row['orario']
                    ? date('d/m/Y H:i', strtotime($row['orario']))
                    : 'non impostato' ?>
            </td>
            <td>
                <?= $row['stato'] === 'terminata'
                    ? $row['punti_casa'] . ' - ' . $row['punti_ospite']
                    : '- - -' ?>
            </td>

            <?php if ($isOrganizzatore): ?>
            <td>
                <?php if ($row['stato'] !== 'terminata'): ?>

                <form method="POST" style="margin-bottom:8px;">
                    <input type="hidden" name="partita_id_orario" value="<?= $row['id'] ?>">
                    <input type="datetime-local" name="orario">
                    <button>Salva orario</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="partita_id" value="<?= $row['id'] ?>">
                    <input type="number" name="casa" min="0" required style="width:50px;">
                    <input type="number" name="ospite" min="0" required style="width:50px;">
                    <button>OK</button>
                </form>

                <?php else: ?>
                    ✔
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </table>

        <?php endforeach; ?>

    <?php endif; ?>

<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'errPunti'): ?>
        <p style="color:red;">Errore: valori negativi non validi.</p>
    <?php elseif ($_GET['msg'] === 'errOrario'): ?>
        <p style="color:red;">Errore: inserisci un orario valido.</p>
    <?php endif; ?>
<?php endif; ?>

<?php require_once('templates/footer.php'); ?>