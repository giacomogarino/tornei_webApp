<?php
require_once 'templates/header.php';
include("conf/db_config.php");

/* FILTRI */
$filtro_ricerca = $_GET['ricerca'] ?? '';
$filtro_stato   = $_GET['stato'] ?? '';
$filtro_luogo = $_GET['luogo'] ?? '';
$filtro_sport = $_GET['sport'] ?? '';

/* QUERY BASE */
$sql = "
SELECT id, nome, formato, stato, sport, luogo
FROM torneo
WHERE visibilita = 'pubblico'
";

$parametri = [];
$tipi = "";

/* FILTRO RICERCA */
if (!empty($filtro_ricerca)) {
    $sql .= " AND nome LIKE ?";
    $parametri[] = "%" . $filtro_ricerca . "%";
    $tipi .= "s";
}

/* FILTRO STATO */
if (!empty($filtro_stato)) {
    $sql .= " AND stato = ?";
    $parametri[] = $filtro_stato;
    $tipi .= "s";
} else {
    $sql .= " AND stato != ?";
    $parametri[] = "completato";
    $tipi .= "s";
}

/* FILTRO LUOGO */
if (!empty($filtro_luogo)) {
    $sql .= " AND luogo LIKE ?";
    $parametri[] = "%" . $filtro_luogo . "%";
    $tipi .= "s";
}

/* FILTRO SPORT */
if (!empty($filtro_sport)) {
    $sql .= " AND sport = ?";
    $parametri[] = $filtro_sport;
    $tipi .= "s";
}

$sql .= " ORDER BY id DESC";

/* PREPARE */
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Errore query");
}

/* BIND PARAMS */
if (!empty($parametri)) {
    $stmt->bind_param($tipi, ...$parametri);
}

/* EXECUTE */
$stmt->execute();

$result = $stmt->get_result();

?>
<link rel="stylesheet" href="css/index_filtri.css">
<link rel="stylesheet" href="css/tabella_tornei.css">

<main class="m-page">
    <div class="m-container">

        <div class="m-page-head">
            <div>
                <h1>Tornei pubblici</h1>
                <div class="m-page-head__sub">Esplora tutti i tornei aperti alle iscrizioni o gi in corso</div>
            </div>
            <a class="m-btn m-btn--primary m-btn--lg" href="crea_torneo.php">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Crea nuovo torneo
            </a>
        </div>

        <form class="m-filters" method="GET" action="index.php">
            <div class="m-input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                <input
                    class="m-input"
                    type="search"
                    id="ricerca"
                    name="ricerca"
                    value="<?= htmlspecialchars($filtro_ricerca) ?>"
                    placeholder="Cerca per nome torneo"
                >
            </div>

            <div class="m-input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                <input
                    class="m-input"
                    type="search"
                    id="luogo"
                    name="luogo"
                    value="<?= htmlspecialchars($filtro_luogo) ?>"
                    placeholder="Cerca per luogo"
                >
            </div>

            <select class="m-select" id="sport" name="sport" aria-label="Sport">
                <option value="">Tutti gli sport</option>
                <option value="calcio" <?= $filtro_sport === 'calcio' ? 'selected' : '' ?>>calcio</option>
                <option value="beachvolley" <?= $filtro_sport === 'beachvolley' ? 'selected' : '' ?>>beachvolley</option>
                <option value="padel" <?= $filtro_sport === 'padel' ? 'selected' : '' ?>>padel</option>
            </select>

            <select class="m-select" id="stato" name="stato" aria-label="Stato">
                <option value="">Tutti gli stati</option>
                <option value="aperto" <?= $filtro_stato === 'aperto' ? 'selected' : '' ?>>Aperto</option>
                <option value="in_corso" <?= $filtro_stato === 'in_corso' ? 'selected' : '' ?>>In corso</option>
                <option value="completato" <?= $filtro_stato === 'completato' ? 'selected' : '' ?>>Completato</option>
            </select>

            <button type="submit" class="m-btn m-btn--primary">Filtra</button>
            <a href="index.php" class="m-btn m-btn--ghost">Azzera</a>
        </form>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'errTorneoNonTrovato'): ?>
                <div class="m-alert m-alert--danger m-mt-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <div>Errore: torneo non trovato.</div>
                </div>
            <?php elseif ($_GET['msg'] === 'err'): ?>
                <div class="m-alert m-alert--danger m-mt-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <div>Errore, riprova pi tardi.</div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="m-mt-6 m-mb-5">Lista tornei</h2>

        <?php include("components/tabella_tornei.php"); ?>

    </div>
</main>

<?php

$stmt->close();
$conn->close();

require_once('templates/footer.php');
?>
