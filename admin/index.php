<?php
/**
 * ADMIN/INDEX.PHP — Dashboard pannello amministratore
 * =====================================================
 * Posizione: /admin/index.php
 */

$page_title       = 'Pannello Admin';
$page_description = 'Pannello di amministrazione Matchora.';

require_once __DIR__ . '/../templates/header_admin.php';
?>
<link rel="stylesheet" href="/css/admin.css">

<main class="m-page">
  <div class="m-container">

    <!-- Testata -->
    <div class="m-page-head">
      <div>
        <h1>Pannello Amministratore</h1>
        <div class="m-page-head__sub">
          Benvenuto, <?= htmlspecialchars($_SESSION['nome_utente']) ?> —
          <?= date('d F Y, H:i') ?>
        </div>
      </div>
    </div>

    <!-- Statistiche rapide -->
    <div class="adm-stats">
      <?php
      // Utenti totali
      $r = $conn->query('SELECT COUNT(*) AS n FROM utente');
      $tot_utenti = $r->fetch_assoc()['n'] ?? 0;

      // Utenti registrati oggi
      $r = $conn->query("SELECT COUNT(*) AS n FROM utente WHERE DATE(created_at) = CURDATE()");
      $utenti_oggi = $r->fetch_assoc()['n'] ?? 0;

      // Tornei attivi
      $r = $conn->query("SELECT COUNT(*) AS n FROM torneo WHERE stato IN ('aperto','in_corso')");
      $tornei_attivi = $r->fetch_assoc()['n'] ?? 0;

      // Segnalazioni aperte
      $r = $conn->query("SELECT COUNT(*) AS n FROM segnalazione WHERE stato = 'aperta'");
      $segn_aperte = $r->fetch_assoc()['n'] ?? 0;

      // Utenti bannati
      $r = $conn->query("SELECT COUNT(*) AS n FROM utente WHERE bannato = 1");
      $tot_bannati = $r->fetch_assoc()['n'] ?? 0;
      ?>

      <div class="adm-stat">
        <div class="adm-stat__val"><?= $tot_utenti ?></div>
        <div class="adm-stat__lbl">Utenti totali</div>
        <div class="adm-stat__sub">+<?= $utenti_oggi ?> oggi</div>
      </div>
      <div class="adm-stat">
        <div class="adm-stat__val"><?= $tornei_attivi ?></div>
        <div class="adm-stat__lbl">Tornei attivi</div>
      </div>
      <div class="adm-stat adm-stat--warn">
        <div class="adm-stat__val"><?= $segn_aperte ?></div>
        <div class="adm-stat__lbl">Segnalazioni aperte</div>
        <?php if ($segn_aperte > 0): ?>
          <a href="segnalazioni.php" class="adm-stat__link">Gestisci →</a>
        <?php endif; ?>
      </div>
      <div class="adm-stat">
        <div class="adm-stat__val"><?= $tot_bannati ?></div>
        <div class="adm-stat__lbl">Utenti bannati</div>
      </div>
    </div>

    <!-- Azioni rapide -->
    <h2 class="adm-section-title">Azioni rapide</h2>
    <div class="adm-actions">
      <a href="utenti.php" class="adm-card">
        <div class="adm-card__icon">👥</div>
        <div class="adm-card__title">Gestione utenti</div>
        <div class="adm-card__desc">Cerca, modifica, banna o elimina account</div>
      </a>
      <a href="tornei.php" class="adm-card">
        <div class="adm-card__icon">🏆</div>
        <div class="adm-card__title">Gestione tornei</div>
        <div class="adm-card__desc">Modifica, sospendi o elimina tornei</div>
      </a>
      <a href="segnalazioni.php" class="adm-card">
        <div class="adm-card__icon">🚩</div>
        <div class="adm-card__title">Segnalazioni</div>
        <div class="adm-card__desc">Revisiona le segnalazioni degli utenti</div>
      </a>
      <a href="log.php" class="adm-card">
        <div class="adm-card__icon">📋</div>
        <div class="adm-card__title">Audit log</div>
        <div class="adm-card__desc">Storico di tutte le azioni degli admin</div>
      </a>
    </div>

    <!-- Ultimi log -->
    <h2 class="adm-section-title">Ultime azioni admin</h2>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr>
            <th>Data</th><th>Admin</th><th>Azione</th><th>Target</th><th>IP</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $logs = $conn->query(
            'SELECT l.created_at, u.nome, u.cognome, l.azione, l.target_tipo, l.target_id, l.ip
             FROM admin_log l
             JOIN utente u ON u.id = l.admin_id
             ORDER BY l.created_at DESC
             LIMIT 20'
        );
        if ($logs && $logs->num_rows > 0):
            while ($log = $logs->fetch_assoc()):
        ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
            <td><?= htmlspecialchars($log['nome'] . ' ' . $log['cognome']) ?></td>
            <td><span class="adm-badge"><?= htmlspecialchars($log['azione']) ?></span></td>
            <td><?= htmlspecialchars($log['target_tipo'] . ($log['target_id'] ? ' #'.$log['target_id'] : '')) ?></td>
            <td class="adm-ip"><?= htmlspecialchars($log['ip'] ?? '—') ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="5" class="adm-empty">Nessuna azione registrata</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <a href="log.php" class="adm-link-more">Vedi tutto il log →</a>

  </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>