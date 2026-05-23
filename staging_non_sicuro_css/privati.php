<?php
require_once 'templates/header.php';
include("conf/db_config.php");

$filtro_ricerca = $_GET['ricerca'] ?? '';

$result = null;

if (!empty($filtro_ricerca)) {

    $sql = "SELECT id, nome, formato, stato, sport, luogo
            FROM torneo
            WHERE visibilita = 'privato'
            AND codice_privato = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("location: privati.php?msg=err");
        exit;
    }

    $stmt->bind_param("s", $filtro_ricerca);
    $stmt->execute();

    $result = $stmt->get_result();
}
?>
<link rel="stylesheet" href="css/privati.css">
<link rel="stylesheet" href="css/tabella_tornei.css">

<main class="m-page">
    <div class="m-container">

        <div class="m-page-head">
            <div>
                <h1>Tornei privati</h1>
                <div class="m-page-head__sub">Hai un codice di invito? Inseriscilo qui sotto per accedere al torneo.</div>
            </div>
        </div>

        <div class="m-card m-private-search">
            <div class="m-private-search__head">
                <span class="m-private-search__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <div>
                    <h3 style="margin: 0;">Inserisci il codice torneo</h3>
                    <div class="m-muted" style="font-size: 13px;">Te lo ha mandato l'organizzatore via email o messaggio</div>
                </div>
            </div>

            <form method="GET" action="privati.php" class="m-private-search__form">
                <input class="m-input m-mono m-private-search__input" type="text" name="ricerca" placeholder="Es. 28C5209C" value="<?= htmlspecialchars($filtro_ricerca) ?>">
                <button type="submit" class="m-btn m-btn--primary m-btn--lg">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                    Cerca torneo
                </button>
                <?php if(!empty($filtro_ricerca)): ?>
                    <a href="privati.php" class="m-btn m-btn--ghost">Azzera</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'err'): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>Errore, riprova pi tardi.</div>
            </div>
        <?php endif; ?>

        <?php if (!empty($filtro_ricerca)): ?>
            <h2 class="m-mb-5">Risultato</h2>
            <?php include("components/tabella_tornei.php"); ?>
        <?php else: ?>
            <div class="m-empty">
                <div class="m-empty__icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Inserisci un codice torneo</h3>
                <p class="m-muted">Digita il codice che hai ricevuto dall'organizzatore per accedere al torneo privato.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

require_once('templates/footer.php');
?>
