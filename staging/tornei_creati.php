<?php
require_once('templates/header_riservato.php')
?>
<link rel="stylesheet" href="css/tabella_tornei.css">

<main class="m-page">
    <div class="m-container">

        <div class="m-page-head">
            <div>
                <h1>Tornei creati</h1>
                <div class="m-page-head__sub">Gestisci, modifica e segui i tuoi tornei da organizzatore</div>
            </div>
            <a href="crea_torneo.php" class="m-btn m-btn--primary m-btn--lg">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Crea nuovo torneo
            </a>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'err'): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div>Errore, riprova pi tardi.</div>
            </div>
        <?php endif; ?>

        <?php require_once 'mostra_tornei_creati.php'; ?>

    </div>
</main>

<?php require_once('templates/footer.php') ?>
