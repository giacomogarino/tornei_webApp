<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include("conf/db_config.php");
require_once 'templates/header_riservato.php';

$initials = strtoupper(
    mb_substr($_SESSION['nome_utente'], 0, 1) .
    mb_substr($_SESSION['cognome_utente'], 0, 1)
);

$data_registrazione = date('d F Y', strtotime($_SESSION['created_at_utente']));
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/profilo.css">
    <title>Torneo crazy</title>
</head>
<div class="profile-container">

  <div class="profile-card">

    <!-- HEADER PROFILO -->
    <div class="profile-header">

      <div class="profile-avatar">
        <?= htmlspecialchars($initials) ?>
      </div>

      <div class="profile-userinfo">

        <p class="profile-name">
          <?= htmlspecialchars($_SESSION['nome_utente'] . ' ' . $_SESSION['cognome_utente']) ?>
        </p>

        <div class="profile-status">

          <?php if ($_SESSION['verified_utente']): ?>
            <span class="badge verified">✓ Verificato</span>
          <?php else: ?>
            <span class="badge not-verified">✗ Non verificato</span>
          <?php endif; ?>

        </div>

      </div>

    </div>

    <!-- DETTAGLI -->
    <div class="profile-details">

      <div class="profile-row">
        <span class="label">Email</span>
        <span class="value">
          <?= htmlspecialchars($_SESSION['email_utente']) ?>
        </span>
      </div>

      <div class="profile-row">
        <span class="label">Codice carta d'identità</span>
        <span class="value mono">
          <?= htmlspecialchars($_SESSION['cod_ci_utente']) ?>
        </span>
      </div>

      <div class="profile-row">
        <span class="label">Membro dal</span>
        <span class="value">
          <?= $data_registrazione ?>
        </span>
      </div>

    </div>

  </div>

</div>

<?php require_once('templates/footer.php'); ?>