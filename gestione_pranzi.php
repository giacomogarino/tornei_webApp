<?php
session_start();
include("conf/db_config.php");

$torneo_id=$_GET['id'] ?? null;
if(!$torneo_id) die("ID torneo mancante");

// PRENDO TORNEO
$stmt=$conn->prepare("SELECT * FROM torneo WHERE id=?");
$stmt->bind_param("i",$torneo_id);
$stmt->execute();
$torneo=$stmt->get_result()->fetch_assoc();

if(!$torneo) die("Torneo non trovato");

// CHECK ORGANIZZATORE
$isOrganizzatore=isset($_SESSION['id_utente']) && $_SESSION['id_utente']==$torneo['creato_da'];

// INSERIMENTO / UPDATE PRANZO
if($_SERVER['REQUEST_METHOD']==='POST' && $isOrganizzatore){

    $squadra_id=$_POST['squadra_id'];
    $orario=$_POST['orario'];

    $stmt=$conn->prepare("
        INSERT INTO pranzi (torneo_id,squadra_id,orario)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE orario=VALUES(orario)
    ");

    $stmt->bind_param("iis",$torneo_id,$squadra_id,$orario);
    $stmt->execute();

    header("Location: gestione_pranzi.php?id=$torneo_id");
    exit;
}

require_once('templates/header.php');

/* Helper iniziali */
function pranzi_iniziali($nome) {
    return strtoupper(mb_substr($nome, 0, 2)) ?: 'SQ';
}
?>

<main class="m-page">
    <div class="m-container">

        <div style="margin-bottom: var(--m-4); font-size: 13px;">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>" style="color: var(--m-text-mute);"> Torna a <?= htmlspecialchars($torneo['nome']) ?></a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Gestione pranzi</h1>
                <div class="m-page-head__sub">Pianifica gli orari dei pranzi per ogni squadra del torneo</div>
            </div>
        </div>

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab">Info torneo</a>
            <a href="struttura_torneo.php?id=<?= (int)$torneo_id ?>" class="m-tab">Struttura torneo</a>
            <a href="gestione_pranzi.php?id=<?= (int)$torneo_id ?>" class="m-tab m-tab--active">Gestione pranzi</a>
        </div>

        <?php if(!$isOrganizzatore): ?>

            <?php
            $stmt=$conn->prepare("
                SELECT p.orario,s.nome,s.persone_pranzo
                FROM pranzi p
                JOIN squadra s ON p.squadra_id=s.id
                WHERE p.torneo_id=?
                ORDER BY p.orario
            ");
            $stmt->bind_param("i",$torneo_id);
            $stmt->execute();
            $result=$stmt->get_result();
            ?>

            <div class="m-table-wrap">
                <table class="m-table">
                    <thead>
                        <tr>
                            <th>Squadra</th>
                            <th class="m-num">Persone</th>
                            <th>Orario pranzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row=$result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="m-row m-gap-3">
                                        <span class="m-avatar m-avatar--sq"><?= pranzi_iniziali($row['nome']) ?></span>
                                        <b><?= htmlspecialchars($row['nome']) ?></b>
                                    </div>
                                </td>
                                <td class="m-num"><b><?= (int)($row['persone_pranzo'] ?? 0) ?></b></td>
                                <td><span class="m-mono"><?= htmlspecialchars($row['orario']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <?php if($torneo['stato']!='in_corso'): ?>
                <div class="m-alert m-alert--info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>I pranzi saranno disponibili dopo la chiusura delle iscrizioni.</div>
                </div>
            <?php else: ?>

                <?php
                $stmt=$conn->prepare("
                    SELECT s.id,s.nome,s.persone_pranzo,p.orario
                    FROM squadra s
                    LEFT JOIN pranzi p
                        ON p.squadra_id=s.id AND p.torneo_id=?
                    WHERE s.torneo_id=? AND s.stato='approvata'
                ");
                $stmt->bind_param("ii",$torneo_id,$torneo_id);
                $stmt->execute();
                $result=$stmt->get_result();
                ?>

                <div class="m-table-wrap">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th>Squadra</th>
                                <th class="m-num">Persone</th>
                                <th>Orario pranzo</th>
                                <th>Gestione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row=$result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="m-row m-gap-3">
                                            <span class="m-avatar m-avatar--sq"><?= pranzi_iniziali($row['nome']) ?></span>
                                            <b><?= htmlspecialchars($row['nome']) ?></b>
                                        </div>
                                    </td>
                                    <td class="m-num"><b><?= (int)($row['persone_pranzo'] ?? 0) ?></b></td>
                                    <td>
                                        <?php if (!empty($row['orario'])): ?>
                                            <span class="m-mono"><?= htmlspecialchars($row['orario']) ?></span>
                                        <?php else: ?>
                                            <span class="m-muted" style="font-style: italic;">non impostato</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                            <input type="hidden" name="squadra_id" value="<?= (int)$row['id'] ?>">
                                            <input class="m-input" type="datetime-local" name="orario" value="<?= htmlspecialchars($row['orario'] ?? '') ?>" required style="padding: 6px 10px; font-size: 13px;">
                                            <button class="m-btn m-btn--<?= empty($row['orario']) ? 'primary' : 'secondary' ?> m-btn--sm"><?= empty($row['orario']) ? 'Salva' : 'Aggiorna' ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
