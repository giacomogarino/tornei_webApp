<?php
$page_title       = 'Sei offline';
$page_description = 'Nessuna connessione disponibile.';
require_once('templates/header.php');
?>
<main class="m-page">
  <div class="m-container" style="text-align:center; padding: 80px 20px;">
    <div style="font-size: 64px; margin-bottom: 24px;">📡</div>
    <h1>Sei offline</h1>
    <p class="m-muted" style="max-width: 380px; margin: 12px auto 32px;">
      Nessuna connessione a internet rilevata.<br>
      Controlla il Wi-Fi o i dati mobili e riprova.
    </p>
    <button onclick="window.location.reload()" class="m-btn m-btn--primary">Riprova</button>
    <a href="/index.php" class="m-btn m-btn--ghost" style="margin-left:8px;">Home</a>
  </div>
</main>
<?php require_once('templates/footer.php'); ?>
