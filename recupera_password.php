<?php
require_once("./templates/header.php");
?>
<link rel="stylesheet" href="css/login.css">

<main class="m-auth-center">
    <div class="m-card">
        <div class="m-auth-center__icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
        </div>
        <h1 style="font-size: 26px;">Recupera la password</h1>
        <p class="m-muted">Inserisci l'email che hai usato per registrarti. Ti invieremo un link per impostare una nuova password.</p>

        <form method="POST" action="./php/recovery.php" class="m-stack m-mt-5">
            <div class="m-field">
                <label class="m-label" for="email">Email</label>
                <div class="m-input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input class="m-input" type="email" id="email" name="email" placeholder="tua@email.it" required autofocus>
                </div>
            </div>
            <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block">Invia link di recupero</button>
        </form>

        <?php if(isset($_GET['msg']) && $_GET['msg'] === 'emptyEmail'): ?>
            <div class="m-alert m-alert--danger m-mt-4">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <div>Inserisci la tua email.</div>
            </div>
        <?php endif; ?>

        <div class="m-auth-card__footer" style="margin-top: var(--m-6);">
            <a href="login.php" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Torna al login
            </a>
        </div>
    </div>
</main>

<?php
require_once("./templates/footer.php");
?>
