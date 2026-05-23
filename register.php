<?php
include("./templates/header.php")
?>
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="css/register.css">

<div class="m-auth-shell">
    <aside class="m-auth-shell__brand"> 
        <div>
            <img src="assets/matchora_icon.png" alt="" width="200">
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
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

                <div style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: var(--m-text-soft);">
                    <input class="m-checkbox" type="checkbox" id="privacy_ok" name="privacy_ok"
                        value="1" required style="margin-top: 1px; flex-shrink: 0;">
                    <label for="privacy_ok">
                         Ho letto e accetto l'<a href="privacy.php">Informativa sulla Privacy</a>.
                        I miei dati saranno trattati esclusivamente per rispondere alla mia richiesta ai sensi dell'art. 6 §1 lett. b) GDPR.
                    </label>
                 </div>

                <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block m-mt-3">Crea il mio account</button>
            </form>

            <?php if(isset($_GET['msg'])):
                $msg = $_GET['msg'];
                $err = [
                    'campiVuoti'      => 'Compila tutti i campi obbligatori.',
                    'emailNonValida'  => 'Email non valida.',
                    'passwordDebole'  => 'La password deve avere almeno 8 caratteri.',
                    'emailEsistente'  => 'Email gi registrata.',
                    'errMsg'          => 'Errore durante la registrazione.',
                    'passwordDiverse' => 'Le password non corrispondono.',
                    'privacyNonAccettata' => 'Accetta la privacy per continuare.'
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
                
            <div class="m-auth-divider">
                <span>oppure</span>
            </div>

            <a href="php/google_login.php" class="m-btn m-btn--google m-btn--lg m-btn--block">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                    <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                </svg>
                Registrati con Google
            </a>

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
