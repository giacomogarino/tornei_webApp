<?php
$page_title       = 'Accedi';
$page_description = 'Accedi al tuo account Matchora per gestire i tuoi tornei.';
require_once './templates/header.php'; // avvia sessione sicura
?>
<link rel="stylesheet" href="css/login.css">

<div class="m-auth-shell">
    <aside class="m-auth-shell__brand">
        <div>
            <img src="assets/matchora_icon.png" alt="Logo Matchora" width="200">
            <h2>Bentornato sul campo.</h2>
            <p>Accedi per gestire i tuoi tornei, le squadre e seguire le partite.</p>
        </div>
        <div class="m-auth-shell__brand-copyright">&copy; <?= date('Y') ?> Matchora Tornei</div>
    </aside>

    <div class="m-auth-shell__form-wrap">
        <div class="m-auth-card">
            <h1>Accedi</h1>
            <p class="m-auth-card__sub">Entra con la tua email e password.</p>

            <form method="POST" action="./php/login_check.php" class="m-stack">
                <?= csrf_field() ?>

                <div class="m-field">
                    <label class="m-label" for="email">Email</label>
                    <div class="m-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16v16H4z"/><polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input class="m-input" type="email" id="email" name="email"
                               placeholder="tua@email.it" required autofocus>
                    </div>
                </div>

                <div class="m-field">
                    <div class="m-row-between">
                        <label class="m-label" for="password">Password</label>
                        <a href="recupera_password.php" style="font-size:12px;">Password dimenticata?</a>
                    </div>
                    <div class="m-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input class="m-input" type="password" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block m-mt-3">
                    Accedi
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form>

            <?php if (isset($_GET['msg'])): ?>
                <?php
                $msg = $_GET['msg'];
                $wait = $_GET['wait'] ?? '';
                $rimanenti = isset($_GET['rimanenti']) ? (int)$_GET['rimanenti'] : null;
                
                $messaggi_err = [
                    'errLogin'               => 'Email o password errata.',
                    'campiVuoti'             => 'Compila tutti i campi.',
                    'emailNonConfermata'     => 'Devi confermare l\'indirizzo email prima di accedere.',
                    'errCambioPsw'           => 'Link di recupero non valido o scaduto.',
                    'err'                    => 'Errore nel login, riprova più tardi.',
                    'NecessariaAutentificazione' => 'Devi autenticarti per accedere a questa pagina.',
                    'usaGoogle'              => 'Questo account usa "Accedi con Google". Usa il pulsante qui sotto.',
                    'registrazioneCompletata'=> null, // gestito tra i messaggi ok
                    'accountBannato' => 'Questo account è stato bannato, se è un errore contatta matchora.torneo@gmail.com',
                    'troppiTentativi' => 'Troppi tentativi di accesso. Riprova tra {'.$wait.'}.',
                ];
                $messaggi_ok = [
                    'ok'                  => 'Controlla la tua email per reimpostare la password.',
                    'passwordAggiornata'  => 'Password aggiornata correttamente. Accedi ora.',
                    'registrazioneCompletata' => 'Account verificato! Ora puoi accedere.',
                ];
                ?>
                <?php if (isset($messaggi_err[$msg]) && $messaggi_err[$msg] !== null): ?>
                    <div class="m-alert m-alert--danger m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <?php
                        $testo = $messaggi_err[$msg] ?? '';

                        // sostituzione wait
                        if ($msg === 'troppiTentativi') {
                            $waitSafe = htmlspecialchars($wait, ENT_QUOTES, 'UTF-8');
                            $testo = str_replace('{wait}', $waitSafe, $testo);
                        }

                        // aggiunta rimanenti (opzionale)
                        if ($msg === 'errLogin' && $rimanenti !== null) {
                            $testo .= " Tentativi rimasti: {$rimanenti}.";
                        }
                        ?>

                        <div><?= htmlspecialchars($testo, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php elseif (isset($messaggi_ok[$msg])): ?>
                    <div class="m-alert m-alert--success m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <div><?= htmlspecialchars($messaggi_ok[$msg], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="m-auth-divider"><span>oppure</span></div>

            <a href="php/google_login.php" class="m-btn m-btn--google m-btn--lg m-btn--block">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                    <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                </svg>
                Accedi con Google
            </a>

            <div class="m-auth-card__footer">
                Non hai un account?
                <a href="register.php">Registrati gratis</a>
            </div>
        </div>
    </div>
</div>

<?php require_once './templates/footer.php'; ?>
