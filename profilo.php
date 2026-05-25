<?php
$page_title       = 'Il mio profilo';
$page_description = 'Gestisci il tuo account Matchora, esporta i tuoi dati o elimina l\'account.';

require_once 'templates/header_riservato.php';
include 'conf/db_config.php';

$nome    = $_SESSION['nome_utente']    ?? '';
$cognome = $_SESSION['cognome_utente'] ?? '';
$email   = $_SESSION['email_utente']   ?? '';
$created = $_SESSION['created_at_utente'] ?? null;

$initials = strtoupper(mb_substr($nome, 0, 1) . mb_substr($cognome, 0, 1));
$data_registrazione = $created ? date('d F Y', strtotime($created)) : '–';

// Messaggi flash
$flash_msgs = [
    'emailErrata' => ['type' => 'danger',  'text' => 'L\'email inserita non corrisponde a quella del tuo account.'],
    'errore'      => ['type' => 'danger',  'text' => 'Si è verificato un errore. Riprova.'],
];
$flash_msg = $_GET['msg'] ?? null;
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

        <?php if ($flash_msg && isset($flash_msgs[$flash_msg])): $fm = $flash_msgs[$flash_msg]; ?>
            <div class="m-alert m-alert--<?= $fm['type'] ?>" style="margin-bottom:var(--m-5);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div><?= htmlspecialchars($fm['text'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <div class="m-profile-grid">

            <!-- Scheda profilo -->
            <section>
                <div class="m-card m-profile-card">
                    <div class="m-profile-card__head">
                        <span class="m-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <div class="m-profile-card__name">
                                <?= htmlspecialchars($nome . ' ' . $cognome, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="m-profile-card__badges">
                                <?php if (!empty($_SESSION['verified_utente'])): ?>
                                    <span class="m-badge m-badge--success">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
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
                            <div><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>

                            <div class="m-muted">Membro dal</div>
                            <div><?= htmlspecialchars($data_registrazione, ENT_QUOTES, 'UTF-8') ?></div>

                            <div class="m-muted">Stato account</div>
                            <div>
                                <?php if (!empty($_SESSION['verified_utente'])): ?>
                                    <span class="m-badge m-badge--success m-badge--dot">
                                        Attivo — email verificata
                                    </span>
                                <?php else: ?>
                                    <span class="m-badge m-badge--warn m-badge--dot">
                                        In attesa di verifica
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sidebar azioni -->
            <aside>
                <!-- Navigazione rapida -->
                <div class="m-card" style="margin-bottom:var(--m-4);">
                    <h4 class="m-profile-section-label">Azioni</h4>
                    <a href="tornei_creati.php" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                             stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 4 7 4 7 20 3 20"/>
                            <polyline points="11 4 15 4 15 14 11 14"/>
                            <polyline points="19 4 21 4 21 10 19 10"/>
                        </svg>
                        I miei tornei creati
                    </a>
                    <a href="tornei_seguiti.php" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                             stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/>
                        </svg>
                        Tornei seguiti
                    </a>
                    <a href="logout.php" class="m-btn m-btn--ghost m-btn--block">Logout</a>
                </div>

                <!-- Esportazione dati (art. 20 GDPR) -->
                <div class="m-card" style="margin-bottom:var(--m-4);">
                    <h4 class="m-profile-section-label">
                        I tuoi dati — art.&nbsp;20 GDPR
                    </h4>
                    <p style="font-size:13px;color:var(--m-text-soft);margin-bottom:var(--m-4);">
                        Scarica una copia di tutti i tuoi dati in formato JSON (portabilità).
                    </p>
                    <form method="POST" action="php/esporta_dati.php">
                        <?= csrf_field() ?>
                        <button type="submit" class="m-btn m-btn--secondary m-btn--block">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                                 stroke="currentColor" stroke-width="1.75"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Esporta i miei dati
                        </button>
                    </form>
                </div>

                <!-- Eliminazione account (art. 17 GDPR) -->
                <div class="m-card" style="border:1px solid var(--m-danger-200, #fecaca);">
                    <h4 class="m-profile-section-label" style="color:var(--m-danger-600,#dc2626);">
                        Elimina account — art.&nbsp;17 GDPR
                    </h4>
                    <p style="font-size:13px;color:var(--m-text-soft);margin-bottom:var(--m-4);">
                        L'eliminazione è <strong>irreversibile</strong>. Tutti i tuoi dati
                        (tornei, squadre, partecipazioni) saranno cancellati definitivamente.
                    </p>

                    <!-- Pulsante che apre il modale di conferma -->
                    <button type="button" class="m-btn m-btn--block"
                            style="background:var(--m-danger-600,#dc2626);color:#fff;border-color:transparent;"
                            onclick="document.getElementById('modal-elimina').style.display='flex'">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                             stroke="currentColor" stroke-width="1.75"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                        </svg>
                        Elimina il mio account
                    </button>
                </div>
            </aside>
        </div>
    </div>
</main>

<!-- Modale conferma eliminazione account -->
<div id="modal-elimina"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;
            align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:460px;width:100%;
                box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <h3 style="margin:0 0 8px;font-size:20px;color:var(--m-danger-600,#dc2626);">
            Conferma eliminazione account
        </h3>
        <p style="color:var(--m-text-soft);font-size:14px;margin-bottom:24px;">
            Questa azione è <strong>irreversibile</strong>. Per confermare, digita il tuo
            indirizzo email:
            <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>

        <form method="POST" action="php/elimina_account.php">
            <?= csrf_field() ?>
            <div class="m-field" style="margin-bottom:16px;">
                <label class="m-label" for="conferma_email">La tua email</label>
                <input class="m-input" type="email" id="conferma_email" name="conferma_email"
                       placeholder="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                       required autocomplete="off">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="button" class="m-btn m-btn--secondary"
                        style="flex:1;"
                        onclick="document.getElementById('modal-elimina').style.display='none'">
                    Annulla
                </button>
                <button type="submit" class="m-btn"
                        style="flex:1;background:var(--m-danger-600,#dc2626);color:#fff;border-color:transparent;">
                    Elimina definitivamente
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
