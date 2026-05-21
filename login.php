<?php
session_start();
session_unset();
session_destroy();

require_once("./templates/header.php");
?>
<link rel="stylesheet" href="css/login.css">

<div class="m-auth-shell">
    <aside class="m-auth-shell__brand">
        <div>
            <img src="assets/matchora_icon.png" alt="" class="m-auth-shell__brand-logo">
            <h2>Bentornato sul campo.</h2>
            <p>Accedi per gestire i tuoi tornei, le squadre, i pranzi e seguire le partite live.</p>
        </div>
        <div class="m-auth-shell__brand-copyright"> <?= date('Y') ?> Matchora  Tornei</div>
    </aside>

    <div class="m-auth-shell__form-wrap">
        <div class="m-auth-card">
            <h1>Accedi</h1>
            <p class="m-auth-card__sub">Entra con la tua email e password.</p>

            <form method="POST" action="./php/login_check.php" class="m-stack">
                <div class="m-field">
                    <label class="m-label" for="email">Email</label>
                    <div class="m-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input class="m-input" type="email" id="email" name="email" placeholder="tua@email.it" required autofocus>
                    </div>
                </div>

                <div class="m-field">
                    <div class="m-row-between">
                        <label class="m-label" for="password">Password</label>
                        <a href="recupera_password.php" style="font-size: 12px;">Password dimenticata?</a>
                    </div>
                    <div class="m-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input class="m-input" type="password" id="password" name="password" placeholder="" required>
                    </div>
                </div>

                <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block m-mt-3">
                    Accedi
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <?php if(isset($_GET['msg'])): ?>
                <?php
                $msg = $_GET['msg'];
                $messaggi_err = [
                    'errLogin'                  => 'Email o password errata.',
                    'campiVuoti'                => 'Compila tutti i campi.',
                    'emailNonConfermata'        => 'Devi confermare la mail per poter accedere.',
                    'errCambioPsw'              => 'Errore nel cambio della password.',
                    'err'                       => 'Errore nel login, riprova pi tardi.',
                    'NecessariaAutentificazione'=> 'Devi prima autenticarti.',
                ];
                $messaggi_ok = [
                    'ok'                => 'Controlla la email per cambiare la password.',
                    'passwordAggiornata'=> 'Password aggiornata correttamente.',
                ];
                ?>
                <?php if (isset($messaggi_err[$msg])): ?>
                    <div class="m-alert m-alert--danger m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <div><?= htmlspecialchars($messaggi_err[$msg]) ?></div>
                    </div>
                <?php elseif (isset($messaggi_ok[$msg])): ?>
                    <div class="m-alert m-alert--success m-mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <div><?= htmlspecialchars($messaggi_ok[$msg]) ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="m-auth-card__footer">
                Non hai un account?
                <a href="register.php">Registrati gratis</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once("./templates/footer.php");
?>
