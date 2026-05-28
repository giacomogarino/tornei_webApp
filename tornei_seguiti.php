<?php
// header_riservato include db_config + check login (con exit se non loggato)
require_once 'templates/header_riservato.php';
include("conf/db_config.php");

$utente_id = $_SESSION['id_utente'];

$sql = "SELECT t.id, t.nome, t.formato, t.stato, t.sport, t.luogo, ts.id
        FROM torneo t
        INNER JOIN torneo_seguito ts
            ON t.id = ts.torneo_id
        WHERE ts.utente_id = ?
        ORDER BY ts.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utente_id);
$stmt->execute();

$result = $stmt->get_result();
?>
<link rel="stylesheet" href="css/tabella_tornei.css">

<main class="m-page">
    <div class="m-container">

        <div class="m-page-head">
            <div>
                <h1>Tornei che segui</h1>
                <div class="m-page-head__sub">Riceverai notifiche quando ci sono nuovi risultati o quando inizia una partita</div>
            </div>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'necessariaAutentificazione'): ?>
            <div class="m-alert m-alert--warn m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>Devi essere loggato per vedere i tornei seguiti.</div>
            </div>
        <?php endif; ?>

        <?php include("components/tabella_tornei.php"); ?>

    </div>
</main>

<?php
$stmt->close();
$conn->close();
require_once('templates/footer.php')
?>
