<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// header_riservato fa session_start + check login (con exit se non loggato)
require_once 'templates/header_riservato.php';
include("conf/db_config.php");

$nome    = $_SESSION['nome_utente']    ?? '';
$cognome = $_SESSION['cognome_utente'] ?? '';
$email   = $_SESSION['email_utente']   ?? '';
$cod_ci  = $_SESSION['cod_ci_utente']  ?? '';
$created = $_SESSION['created_at_utente'] ?? null;

$initials = strtoupper(
    mb_substr($nome, 0, 1) .
    mb_substr($cognome, 0, 1)
);

$data_registrazione = $created ? date('d F Y', strtotime($created)) : '-';
?>
<link rel="stylesheet" href="css/profilo.css">

<main class="m-page">
    <div class="m-container">

        <div class="m-page-head">
            <div>
                <h1>Il mio profilo</h1>
                <div class="m-page-head__sub">Gestisci i dati del tuo account Matchora</div>
            </div>
        </div>

        <div class="m-profile-grid">
            <section>
                <div class="m-card m-profile-card">
                    <div class="m-profile-card__head">
                        <span class="m-avatar"><?= htmlspecialchars($initials) ?></span>
                        <div>
                            <div class="m-profile-card__name"><?= htmlspecialchars($nome . ' ' . $cognome) ?></div>
                            <div class="m-profile-card__badges">
                                <?php if (!empty($_SESSION['verified_utente'])): ?>
                                    <span class="m-badge m-badge--success">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Verificato
                                    </span>
                                <?php else: ?>
                                    <span class="m-badge">Non verificato</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="m-profile-card__body">
                        <h4 class="m-profile-section-label">Informazioni personali</h4>
                        <div class="m-profile-info">
                            <div class="m-muted">Email</div>
                            <div><?= htmlspecialchars($email) ?></div>

                            <div class="m-muted">Codice carta d'identità</div>
                            <div class="m-mono"><?= htmlspecialchars($cod_ci) ?></div>

                            <div class="m-muted">Membro dal</div>
                            <div><?= htmlspecialchars($data_registrazione) ?></div>

                            <div class="m-muted">Stato account</div>
                            <div>
                                <?php if (!empty($_SESSION['verified_utente'])): ?>
                                    <span class="m-badge m-badge--success m-badge--dot">Attivo  email verificata</span>
                                <?php else: ?>
                                    <span class="m-badge m-badge--warn m-badge--dot">In attesa di verifica</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside>
                <div class="m-card">
                    <h4 class="m-profile-section-label">Azioni</h4>
                    <a href="tornei_creati.php" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                        I miei tornei creati
                    </a>
                    <a href="tornei_seguiti.php" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>
                        Tornei seguiti
                    </a>
                    <a href="logout.php" class="m-btn m-btn--ghost m-btn--block">Logout</a>
                </div>
            </aside>
        </div>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
