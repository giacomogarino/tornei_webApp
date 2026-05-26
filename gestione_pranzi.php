<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';

session_secure_start();

include("conf/db_config.php");

$utente_id = $_SESSION['id_utente'] ?? null;

$torneo_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$torneo_id) {
    die("ID torneo mancante");
}

/* ─────────────────────────────────────────────
   PRENDO TORNEO
───────────────────────────────────────────── */
$stmt = $conn->prepare("SELECT * FROM torneo WHERE id = ?");
$stmt->bind_param("i", $torneo_id);
$stmt->execute();

$torneo = $stmt->get_result()->fetch_assoc();

if (!$torneo) {
    die("Torneo non trovato");
}

/* ─────────────────────────────────────────────
   CHECK ORGANIZZATORE
───────────────────────────────────────────── */
$isOrganizzatore = (
    isset($_SESSION['id_utente']) &&
    $_SESSION['id_utente'] == $torneo['creato_da']
);

/* ─────────────────────────────────────────────
   FOLLOW TORNEO
───────────────────────────────────────────── */
$isFollowing = false;

if ($utente_id) {
    $stmt = $conn->prepare("
        SELECT id
        FROM torneo_seguito
        WHERE torneo_id = ? AND utente_id = ?
    ");

    $stmt->bind_param("ii", $torneo_id, $utente_id);
    $stmt->execute();

    $isFollowing = ($stmt->get_result()->num_rows > 0);
}

/* ─────────────────────────────────────────────
   CSRF VERIFY PER TUTTE LE POST
───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

/* ─────────────────────────────────────────────
   TOGGLE FOLLOW
───────────────────────────────────────────── */
if (isset($_POST['toggle_follow']) && $utente_id) {

    if ($isFollowing) {

        $s = $conn->prepare("
            DELETE FROM torneo_seguito
            WHERE torneo_id = ? AND utente_id = ?
        ");

    } else {

        $s = $conn->prepare("
            INSERT INTO torneo_seguito (torneo_id, utente_id)
            VALUES (?, ?)
        ");
    }

    $s->bind_param("ii", $torneo_id, $utente_id);
    $s->execute();

    header("Location: gestione_pranzi.php?id=$torneo_id");
    exit;
}

/* ─────────────────────────────────────────────
   INSERIMENTO / UPDATE PRANZI
───────────────────────────────────────────── */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $isOrganizzatore &&
    isset($_POST['squadra_id'], $_POST['orario'])
) {

    $squadra_id = (int)$_POST['squadra_id'];
    $orario = $_POST['orario'];

    $stmt = $conn->prepare("
        INSERT INTO pranzi (torneo_id, squadra_id, orario)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            orario = VALUES(orario)
    ");

    $stmt->bind_param("iis", $torneo_id, $squadra_id, $orario);
    $stmt->execute();

    header("Location: gestione_pranzi.php?id=$torneo_id");
    exit;
}

/* ─────────────────────────────────────────────
   LABEL NAVBAR
───────────────────────────────────────────── */
$stato_label = [
    'aperto' => 'Aperto',
    'in_corso' => 'In corso',
    'completato' => 'Completato'
];

$formato_label = [
    'eliminazione_diretta' => 'Eliminazione diretta',
    'girone_unico' => 'Girone unico',
    'gironi_playoff' => 'Gironi + playoff',
];

$tipo_label = [
    'andata' => 'Solo andata',
    'andata_ritorno' => 'Andata e ritorno'
];

/* ─────────────────────────────────────────────
   DATI NAVBAR
───────────────────────────────────────────── */
$navbar_data = [
    'torneo' => $torneo,
    'isOrganizzatore' => $isOrganizzatore,
    'isFollowing' => $isFollowing,
    'stato_label' => $stato_label,
    'formato_label' => $formato_label,
    'tipo_label' => $tipo_label,
];

require_once('templates/header.php');

/* ─────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────── */
function pranzi_iniziali($nome) {
    return strtoupper(mb_substr($nome, 0, 2)) ?: 'SQ';
}
?>

<?php
include("components/navbar_torneo.php")
?>

<main class="m-page">
    <div class="m-container">

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Info torneo
            </a>

            <a href="struttura_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                Struttura torneo
            </a>

            <?php if ($torneo['stato'] === 'in_corso'): ?>
                <a href="gestione_pranzi.php?id=<?= (int)$torneo['id'] ?>" class="m-tab m-tab--active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18"/><path d="M5 11V8a7 7 0 1 1 14 0v3"/><path d="M5 11l-1 8h16l-1-8"/></svg>
                    Gestione pranzi
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$isOrganizzatore): ?>

            <?php
            $stmt = $conn->prepare("
                SELECT p.orario, s.nome, s.persone_pranzo
                FROM pranzi p
                JOIN squadra s ON p.squadra_id = s.id
                WHERE p.torneo_id = ?
                ORDER BY p.orario
            ");
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $ha_pranzi = $result->num_rows > 0;
            ?>

            <?php if (!$ha_pranzi): ?>

                <div class="m-alert m-alert--info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>In attesa che l’organizzatore inserisca gli orari dei pranzi.</div>
                </div>

            <?php else: ?>

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
                            <?php while ($row = $result->fetch_assoc()): ?>
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

            <?php endif; ?>

        <?php else: ?>

            <?php if ($torneo['stato'] != 'in_corso'): ?>
                <div class="m-alert m-alert--info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>I pranzi saranno disponibili dopo la chiusura delle iscrizioni.</div>
                </div>
            <?php else: ?>

                <?php
                $stmt = $conn->prepare("
                    SELECT s.id, s.nome, s.persone_pranzo, p.orario
                    FROM squadra s
                    LEFT JOIN pranzi p
                        ON p.squadra_id = s.id AND p.torneo_id = ?
                    WHERE s.torneo_id = ? AND s.stato = 'approvata'
                ");
                $stmt->bind_param("ii", $torneo_id, $torneo_id);
                $stmt->execute();
                $result = $stmt->get_result();
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
                            <?php while ($row = $result->fetch_assoc()): ?>
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
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="squadra_id" value="<?= (int)$row['id'] ?>">
                                            <input class="m-input" type="datetime-local" name="orario"
                                                   value="<?= htmlspecialchars($row['orario'] ?? '') ?>"
                                                   required style="padding: 6px 10px; font-size: 13px;">
                                            <button class="m-btn m-btn--<?= empty($row['orario']) ? 'primary' : 'secondary' ?> m-btn--sm">
                                                <?= empty($row['orario']) ? 'Salva' : 'Aggiorna' ?>
                                            </button>
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
