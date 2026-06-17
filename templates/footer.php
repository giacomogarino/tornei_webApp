<footer class="m-footer">
    <div class="m-container">
        <div class="m-footer__grid">
            <div class="m-footer__brand-block">
                <a href="index.php" class="m-navbar__brand">
                    <span class="m-navbar__brand-mark"><img src="assets/matchora_icon.png" alt="Logo Matchora"></span>
                    <span class="m-navbar__brand-name">MATCHORA<span class="m-navbar__brand-sub">Tornei</span></span>
                </a>
                <p>Piattaforma per organizzare e seguire tornei sportivi in modo semplice e professionale.</p>
            </div>

            <div>
                <h5>Naviga</h5>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="privati.php">Tornei privati</a></li>
                    <li><a href="crea_torneo.php">Crea torneo</a></li>
                </ul>
            </div>

            <div>
                <h5>Account</h5>
                <ul>
                    <li><a href="profilo.php">Profilo</a></li>
                    <li><a href="tornei_creati.php">Tornei creati</a></li>
                    <li><a href="tornei_seguiti.php">Seguiti</a></li>
                </ul>
            </div>

            <div>
                <h5>Legale &amp; Aiuto</h5>
                <ul>
                    <li><a href="contatti.php">Contatti</a></li>
                    <li><a href="team.php">Il Team</a></li>
                    <li><a href="termini.php">Termini di servizio</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="m-footer__bottom">
            <span>&copy; <?= date('Y') ?> Matchora Tornei</span>
            <span>
                <a href="privacy.php" style="color:inherit;text-decoration:none;margin-right:12px;">Privacy</a>
                <a href="termini.php" style="color:inherit;text-decoration:none;">Termini</a>
            </span>
        </div>
    </div>
</footer>

<?php if (isset($extra_css) && in_array('/css/torneo_struttura.css', $extra_css)): ?>
<!-- AJAX risultati: caricato solo sulle pagine struttura torneo -->
<script src="/js/matchora-risultato.js" defer></script>
<?php endif; ?>

<!-- PWA Install Banner -->
<div id="pwa-banner" style="
    display:none; position:fixed; bottom:16px; left:50%; transform:translateX(-50%);
    background:var(--m-surface); border:1px solid var(--m-border); border-radius:12px;
    padding:12px 18px; box-shadow:0 8px 32px rgba(0,0,0,.35);
    display:flex; align-items:center; gap:12px; z-index:9999;
    font-size:14px; max-width:360px; width:calc(100% - 32px);" id="pwa-banner">
    <img src="/assets/matchora_icon.png" width="36" height="36" style="border-radius:8px;" alt="">
    <div style="flex:1;">
        <div style="font-weight:600;">Installa Matchora</div>
        <div style="font-size:12px; color:var(--m-text-mute);">Accesso rapido dalla home</div>
    </div>
    <button id="pwa-install-btn" class="m-btn m-btn--primary m-btn--sm">Installa</button>
    <button id="pwa-dismiss-btn" style="background:none;border:none;cursor:pointer;color:var(--m-text-mute);padding:4px;font-size:18px;line-height:1;" aria-label="Chiudi">&times;</button>
</div>
<script>
(function(){
    let deferredPrompt;
    const banner = document.getElementById('pwa-banner');
    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-dismiss-btn');

    if (!banner || !installBtn || !dismissBtn) return;
    if (localStorage.getItem('pwa-dismissed')) return;

    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
        banner.style.display = 'flex';
    });

    installBtn.addEventListener('click', function() {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function(c) {
            if (c.outcome === 'accepted') localStorage.setItem('pwa-dismissed','1');
            banner.style.display = 'none';
            deferredPrompt = null;
        });
    });

    dismissBtn.addEventListener('click', function() {
        banner.style.display = 'none';
        localStorage.setItem('pwa-dismissed','1');
    });

    window.addEventListener('appinstalled', function() {
        banner.style.display = 'none';
    });
}());
</script>
</body>
</html>
