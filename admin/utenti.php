<?php
/**
 * ADMIN/UTENTI.PHP — Gestione utenti
 * Posizione: /admin/utenti.php
 */

$page_title = 'Admin — Gestione utenti';
require_once __DIR__ . '/../templates/header_admin.php';

// ── Azioni POST (Post/Redirect/Get) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action    = $_POST['action']    ?? '';
    $target_id = (int)($_POST['user_id'] ?? 0);

    if ($target_id === (int)$_SESSION['id_utente']) {
        header('Location: /admin/utenti.php?msg=selfAction');
        exit;
    }

    // Verifica che il target non sia un altro admin
    $chk = $conn->prepare('SELECT role FROM utente WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $target_id);
    $chk->execute();
    $target_row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($target_row && $target_row['role'] === 'admin') {
        header('Location: /admin/utenti.php?msg=adminProtected');
        exit;
    }

    switch ($action) {
        case 'banna':
            $motivo = trim($_POST['motivo'] ?? 'Violazione termini');
            if ($motivo === '') $motivo = 'Violazione termini';
            $stmt = $conn->prepare('UPDATE utente SET bannato = 1, ban_motivo = ? WHERE id = ?');
            $stmt->bind_param('si', $motivo, $target_id);
            $stmt->execute();
            $stmt->close();
            admin_log($conn, 'ban_utente', 'utente', $target_id, ['motivo' => $motivo]);
            header('Location: /admin/utenti.php?msg=bannato');
            exit;

        case 'sbanna':
            $stmt = $conn->prepare('UPDATE utente SET bannato = 0, ban_motivo = NULL WHERE id = ?');
            $stmt->bind_param('i', $target_id);
            $stmt->execute();
            $stmt->close();
            admin_log($conn, 'unban_utente', 'utente', $target_id);
            header('Location: /admin/utenti.php?msg=sbannato');
            exit;

        case 'elimina':
            $stmt = $conn->prepare('DELETE FROM utente WHERE id = ? AND role != "admin"');
            $stmt->bind_param('i', $target_id);
            $stmt->execute();
            $stmt->close();
            admin_log($conn, 'elimina_utente', 'utente', $target_id);
            header('Location: /admin/utenti.php?msg=eliminato');
            exit;
    }

    header('Location: /admin/utenti.php');
    exit;
}

// ── Messaggi flash da GET ─────────────────────────────────────────────
$flash_map = [
    'bannato'       => ['success', 'Utente bannato con successo.'],
    'sbannato'      => ['success', 'Utente sbannato.'],
    'eliminato'     => ['success', 'Utente eliminato.'],
    'selfAction'    => ['danger',  'Non puoi eseguire questa azione sul tuo account.'],
    'adminProtected'=> ['danger',  'Non puoi modificare un altro amministratore.'],
];
$flash = isset($_GET['msg']) ? ($flash_map[$_GET['msg']] ?? null) : null;

// ── Ricerca + paginazione ─────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where  = 'WHERE (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)';
    $like   = '%' . $search . '%';
    $params = [$like, $like, $like];
    $types  = 'sss';
}

// Conta totale
$cntStmt = $conn->prepare("SELECT COUNT(*) AS n FROM utente $where");
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total      = (int)$cntStmt->get_result()->fetch_assoc()['n'];
$cntStmt->close();
$totalPages = (int)ceil($total / $perPage);

// Lista utenti
$listStmt = $conn->prepare(
    "SELECT id, nome, cognome, email, role, bannato, ban_motivo, verified, created_at
     FROM utente $where
     ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt->bind_param($types . 'ii', ...$listParams);
$listStmt->execute();
$utenti = $listStmt->get_result();
$listStmt->close();
?>
<link rel="stylesheet" href="/css/admin.css">

<main class="m-page">
  <div class="m-container">

    <div class="m-page-head">
      <div>
        <h1>Gestione utenti</h1>
        <div class="m-page-head__sub"><?= $total ?> account totali</div>
      </div>
      <a href="/admin/index.php" class="m-btn m-btn--ghost">← Dashboard</a>
    </div>

    <?php if ($flash): ?>
      <div class="m-alert m-alert--<?= $flash[0] ?>" style="margin-bottom:1.5rem;">
        <?= htmlspecialchars($flash[1]) ?>
      </div>
    <?php endif; ?>

    <!-- Ricerca -->
    <form method="get" class="adm-search">
      <input type="search" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="Cerca per nome, cognome o email…" class="adm-search__input">
      <button type="submit" class="m-btn">Cerca</button>
      <?php if ($search): ?>
        <a href="/admin/utenti.php" class="m-btn m-btn--ghost">Tutti</a>
      <?php endif; ?>
    </form>

    <!-- Tabella -->
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr>
            <th>#</th><th>Nome</th><th>Email</th><th>Ruolo</th>
            <th>Stato</th><th>Registrato</th><th>Azioni</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($utenti->num_rows === 0): ?>
          <tr><td colspan="7" class="adm-empty">Nessun utente trovato.</td></tr>
        <?php else: while ($u = $utenti->fetch_assoc()): ?>
          <tr class="<?= $u['bannato'] ? 'adm-row--banned' : '' ?>">
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['nome'] . ' ' . $u['cognome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span class="adm-badge adm-badge--admin">Admin</span>
              <?php else: ?>
                <span class="adm-badge">User</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['bannato']): ?>
                <span class="adm-badge adm-badge--danger"
                      title="<?= htmlspecialchars($u['ban_motivo'] ?? '') ?>">Bannato</span>
              <?php elseif (!$u['verified']): ?>
                <span class="adm-badge adm-badge--warn">Non verificato</span>
              <?php else: ?>
                <span class="adm-badge adm-badge--ok">Attivo</span>
              <?php endif; ?>
            </td>
            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['role'] !== 'admin'): ?>
                <div class="adm-row-actions">
                  <?php if ($u['bannato']): ?>
                    <form method="post" action="/admin/utenti.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="sbanna">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button class="adm-btn adm-btn--ok" type="submit">Sbanna</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/admin/utenti.php" class="adm-ban-form">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="banna">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <input type="text" name="motivo" placeholder="Motivo"
                             class="adm-input-sm" required maxlength="200">
                      <button class="adm-btn adm-btn--warn" type="submit">Banna</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="/admin/utenti.php"
                        onsubmit="return confirm('Eliminare definitivamente questo utente?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="elimina">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button class="adm-btn adm-btn--danger" type="submit">Elimina</button>
                  </form>
                </div>
              <?php else: ?>
                <span class="adm-ip">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginazione -->
    <?php if ($totalPages > 1): ?>
      <div class="adm-pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?q=<?= urlencode($search) ?>&p=<?= $i ?>"
             class="adm-page <?= $i === $page ? 'adm-page--active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>