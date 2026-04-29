<?php
// Assumendo che $utente sia già caricato dalla sessione o dal DB
// Es: $utente = $_SESSION['utente'];
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    include("conf/db_config.php");
    require_once 'templates/header_riservato.php';

$initials = strtoupper(mb_substr($_SESSION['nome_utente'], 0, 1) . mb_substr($_SESSION['cognome_utente'], 0, 1));
$data_registrazione = date('d F Y', strtotime($_SESSION['created_at_utente']));
?>

<div style="max-width: 640px; margin: 2rem auto; padding: 0 1rem;">
  <div style="background: #fff; border-radius: 12px; border: 0.5px solid #e5e5e5; padding: 2rem 1.75rem;">

    <!-- Avatar + nome -->
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: #e6f1fb; display: flex; align-items: center; justify-content: center; font-weight: 500; font-size: 18px; color: #185fa5; flex-shrink: 0;">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div>
        <p style="font-weight: 500; font-size: 18px; margin: 0;"><?= htmlspecialchars($_SESSION['nome_utente'] . ' ' . $_SESSION['cognome_utente']) ?></p>
        <p style="margin: 4px 0 0;">
          <?php if ($_SESSION['verified_utente']): ?>
            <span style="font-size: 12px; padding: 2px 8px; border-radius: 6px; background: #eaf3de; color: #3b6d11;">✓ Verificato</span>
          <?php else: ?>
            <span style="font-size: 12px; padding: 2px 8px; border-radius: 6px; background: #faeeda; color: #854f0b;">✗ Non verificato</span>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Dettagli -->
    <div style="border-top: 0.5px solid #e5e5e5; padding-top: 1.25rem; display: grid; gap: 0.75rem;">

      <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 0.5px solid #e5e5e5;">
        <span style="font-size: 13px; color: #666;">Email</span>
        <span style="font-size: 14px; font-weight: 500;"><?= htmlspecialchars($_SESSION['email_utente']) ?></span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 0.5px solid #e5e5e5;">
        <span style="font-size: 13px; color: #666;">Codice carta d'identità</span>
        <span style="font-size: 14px; font-family: monospace;"><?= htmlspecialchars($_SESSION['cod_ci_utente']) ?></span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
        <span style="font-size: 13px; color: #666;">Membro dal</span>
        <span style="font-size: 14px;"><?= $data_registrazione ?></span>
      </div>

    </div>
  </div>
</div>

<?php require_once('templates/footer.php') ?>