<?php
include("./templates/header.php")
?>
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="css/register.css">

<div class="m-auth-shell">
    <aside class="m-auth-shell__brand">
        <div>
            <img src="assets/matchora_icon.png" alt="" class="m-auth-shell__brand-logo">
            <h2>Crea il tuo account.</h2>
            <p>Bastano due minuti. Potrai creare tornei, iscriverti come squadra e seguire i match preferiti.</p>

            <div class="m-auth-shell__bullets">
                <div class="m-auth-shell__bullet">
                    <span class="m-auth-shell__bullet-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span>Crea tornei illimitati, pubblici o privati</span>
                </div>
                <div class="m-auth-shell__bullet">
                    <span class="m-auth-shell__bullet-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span>Tabellone e classifiche generati automaticamente</span>
                </div>
                <div class="m-auth-shell__bullet">
                    <span class="m-auth-shell__bullet-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span>Gestione squadre, giocatori e pranzi inclusa</span>
                </div>
            </div>
        </div>
        <div class="m-auth-shell__brand-copyright"> <?= date('Y') ?> Matchora  Tornei</div>
    </aside>

    <div class="m-auth-shell__form-wrap">
        <div class="m-auth-card m-auth-card--wide">
            <h1>Crea il tuo account</h1>
            <p class="m-auth-card__sub">Registrati per gestire e seguire i tuoi tornei.</p>

            <form method="POST" action="./php/register_check.php" class="m-stack">
                <div class="m-form-grid-2">
                    <div class="m-field">
                        <label class="m-label" for="nome">Nome</label>
                        <input class="m-input" type="text" id="nome" name="nome" placeholder="Mario" required>
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="cognome">Cognome</label>
                        <input class="m-input" type="text" id="cognome" name="cognome" placeholder="Rossi" required>
                    </div>
                </div>

                <div class="m-field">
                    <label class="m-label" for="email">Email</label>
                    <input class="m-input" type="email" id="email" name="email" placeholder="mario.rossi@esempio.it" required>
                </div>

                <div class="m-field">
                    <label class="m-label" for="ci">N carta d'identità</label>
                    <input class="m-input m-mono" type="text" id="ci" name="ci" placeholder="CA01234AB" required>
                    <span class="m-muted" style="font-size: 12px;">Lo usiamo per verificare la tua identità.</span>
                </div>

                <div class="m-form-grid-2">
                    <div class="m-field">
                        <label class="m-label" for="password">Password</label>
                        <input class="m-input" type="password" id="password" name="password" placeholder="min 8 caratteri" required>
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="password2">Conferma</label>
                        <input class="m-input" type="password" id="password2" name="password2" placeholder="ripeti" required>
                    </div>
                </div>

                <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block m-mt-3">Crea il mio account</button>
            </form>

            <?php if(isset($_GET['msg'])):
                $msg = $_GET['msg'];
                $err = [
                    'campiVuoti'      => 'Compila tutti i campi obbligatori.',
                    'emailNonValida'  => 'Email non valida.',
                    'passwordDebole'  => 'La password deve avere almeno 8 caratteri.',
                    'ciNonValida'     => 'Carta identit non valida.',
                    'emailEsistente'  => 'Email gi registrata.',
                    'errMsg'          => 'Errore durante la registrazione.',
                    'passwordDiverse' => 'Le password non corrispondono.',
                ];
                $ok = [
                    'confermaInviata' => 'Registrazione completata. Conferma la mail per poter accedere.',
                ];
            ?>
                <?php if (isset($err[$msg])): ?>
                    <div class="m-alert m-alert--danger m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <div><?= htmlspecialchars($err[$msg]) ?></div>
                    </div>
                <?php elseif (isset($ok[$msg])): ?>
                    <div class="m-alert m-alert--success m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <div><?= htmlspecialchars($ok[$msg]) ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="m-auth-card__footer">
                Hai gi un account?
                <a href="login.php">Accedi</a>
            </div>
        </div>
    </div>
</div>

<?php
include("./templates/footer.php")
?>
