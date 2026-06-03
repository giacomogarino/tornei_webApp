<?php
/**
 * ADMIN/SEGNALAZIONI.PHP — Gestione segnalazioni utenti
 */
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
session_secure_start();

if (!isset($_SESSION['login']) || !isset($_SESSION['id_utente'])) {
    header('Location: /login.php'); exit;
}
require_once __DIR__ . '/../conf/db_config.php';
$chkAdmin = $conn->prepare('SELECT role, bannato FROM utente WHERE id = ? LIMIT 1');
$chkAdmin->bind_param('i', $_SESSION['id_utente']);
$chkAdmin->execute();
$meRow = $chkAdmin->get_result()->fetch_assoc(); $chkAdmin->close();
if (!$meRow || $meRow['role'] !== 'admin' || (int)$meRow['bannato'] === 1) {
    http_response_code(403); header('Location: /index.php'); exit;
}
$_SESSION['role_utente'] = 'admin';

if (!function_exists('admin_log')) {
    function admin_log(mysqli $conn, string $azione, string $targetTipo = '',
                       int $targetId = 0, array $dettagli = []): void {
        $adminId = $_SESSION['id_utente'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $det = $dettagli ? json_encode($dettagli, JSON_UNESCAPED_UNICODE) : null;
        $s = $conn->prepare('INSERT INTO admin_log
            (admin_id,azione,target_tipo,target_id,dettagli,ip) VALUES (?,?,?,?,?,?)');
        $s->bind_param('issiis', $adminId, $azione, $targetTipo, $targetId, $det, $ip);
        $s->execute(); $s->close();
    }
}

// ── POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action  = $_POST['action']  ?? '';
    $segn_id = (int)($_POST['segn_id'] ?? 0);

    switch ($action) {
        case 'in_revisione':
            $s = $conn->prepare("UPDATE segnalazione SET stato='in_revisione', gestita_da=? WHERE id=?");
            $s->bind_param('ii', $_SESSION['id_utente'], $segn_id); $s->execute(); $s->close();
            admin_log($conn, 'segn_in_revisione', 'segnalazione', $segn_id);
            header('Location: /admin/segnalazioni.php?msg=revisione'); exit;

        case 'chiudi':
            $nota = trim($_POST['nota'] ?? '');
            $s = $conn->prepare("UPDATE segnalazione SET stato='chiusa', nota_admin=?, gestita_da=? WHERE id=?");
            $s->bind_param('sii', $nota, $_SESSION['id_utente'], $segn_id); $s->execute(); $s->close();
            admin_log($conn, 'segn_chiusa', 'segnalazione', $segn_id, ['nota' => $nota]);
            header('Location: /admin/segnalazioni.php?msg=chiusa'); exit;

        case 'banna_segnalato':
            // Legge chi è il target della segnalazione
            $s = $conn->prepare('SELECT target_tipo, target_id FROM segnalazione WHERE id=? LIMIT 1');
            $s->bind_param('i', $segn_id); $s->execute();
            $seg = $s->get_result()->fetch_assoc(); $s->close();

            if ($seg && $seg['target_tipo'] === 'utente') {
                $motivo = trim($_POST['nota'] ?? '') ?: 'Segnalazione accolta dagli admin';
                $s = $conn->prepare('UPDATE utente SET bannato=1, ban_motivo=? WHERE id=? AND role!="admin"');
                $s->bind_param('si', $motivo, $seg['target_id']); $s->execute(); $s->close();
                admin_log($conn, 'ban_utente', 'utente', (int)$seg['target_id'], ['motivo' => $motivo, 'da_segnalazione' => $segn_id]);
            }
            // Chiude anche la segnalazione
            $nota = trim($_POST['nota'] ?? '') ?: 'Utente bannato';
            $s = $conn->prepare("UPDATE segnalazione SET stato='chiusa', nota_admin=?, gestita_da=? WHERE id=?");
            $s->bind_param('sii', $nota, $_SESSION['id_utente'], $segn_id); $s->execute(); $s->close();
            header('Location: /admin/segnalazioni.php?msg=bannato'); exit;
    }
    header('Location: /admin/segnalazioni.php'); exit;
}

// ── HTML ──────────────────────────────────────────────────────────────
$page_title = 'Admin — Segnalazioni';
require_once __DIR__ . '/../templates/header.php';

$flash_map = [
    'revisione' => ['success', 'Segnalazione presa in carico.'],
    'chiusa'    => ['success', 'Segnalazione chiusa.'],
    'bannato'   => ['success', 'Utente bannato e segnalazione chiusa.'],
];
$flash  = isset($_GET['msg']) ? ($flash_map[$_GET['msg']] ?? null) : null;
$filter = $_GET['stato'] ?? 'aperta';
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$params = []; $types = ''; $conds = [];
if ($filter !== '') {
    $conds[] = 'sg.stato = ?'; $params[] = $filter; $types .= 's';
}
$where = $conds ? 'WHERE '.implode(' AND ', $conds) : '';

$cntStmt = $conn->prepare("SELECT COUNT(*) AS n FROM segnalazione sg $where");
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total      = (int)$cntStmt->get_result()->fetch_assoc()['n'];
$cntStmt->close();
$totalPages = (int)ceil($total / $perPage);

$lp = $params; $lp[] = $perPage; $lp[] = $offset;
$listStmt = $conn->prepare(
    "SELECT sg.id, sg.target_tipo, sg.target_id, sg.motivo, sg.stato,
            sg.nota_admin, sg.created_at,
            u.nome AS da_nome, u.cognome AS da_cognome,
            adm.nome AS adm_nome, adm.cognome AS adm_cognome
     FROM segnalazione sg
     LEFT JOIN utente u   ON u.id   = sg.segnalato_da
     LEFT JOIN utente adm ON adm.id = sg.gestita_da
     $where ORDER BY sg.created_at DESC LIMIT ? OFFSET ?");
$listStmt->bind_param($types.'ii', ...$lp);
$listStmt->execute();
$segnalazioni = $listStmt->get_result();
$listStmt->close();

$stato_badge = ['aperta'=>'danger','in_revisione'=>'warn','chiusa'=>'ok'];
$stato_label = ['aperta'=>'Aperta','in_revisione'=>'In revisione','chiusa'=>'Chiusa'];
?>
<link rel="stylesheet" href="/css/admin.css">
<style>
.adm-segn-detail { font-size:.8rem; color:var(--m-text-secondary); margin-top:3px; }
.adm-nota-form   { display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:6px; }
.adm-nota-input  { flex:1; min-width:140px; padding:4px 8px; border:1px solid var(--m-border);
                   border-radius:var(--m-radius); font-size:.78rem; }
.adm-filter-tabs { display:flex; gap:6px; margin-bottom:var(--m-4); flex-wrap:wrap; }
.adm-filter-tab  { padding:5px 14px; border:1px solid var(--m-border); border-radius:999px;
                   font-size:.82rem; text-decoration:none; color:var(--m-text); }
.adm-filter-tab--active { background:var(--m-primary); color:#fff; border-color:var(--m-primary); }
</style>
<main class="m-page"><div class="m-container">
  <div class="m-page-head">
    <div><h1>Segnalazioni</h1><div class="m-page-head__sub"><?= $total ?> in questa vista</div></div>
    <a href="/admin/index.php" class="m-btn m-btn--ghost">← Dashboard</a>
  </div>
  <?php if ($flash): ?>
    <div class="m-alert m-alert--<?= $flash[0] ?>" style="margin-bottom:1.5rem;"><?= htmlspecialchars($flash[1]) ?></div>
  <?php endif; ?>

  <!-- Filtro stato -->
  <div class="adm-filter-tabs">
    <?php foreach ([''=>'Tutte','aperta'=>'Aperte','in_revisione'=>'In revisione','chiusa'=>'Chiuse'] as $v=>$l): ?>
      <a href="?stato=<?= $v ?>" class="adm-filter-tab <?= $filter===$v?'adm-filter-tab--active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr>
      <th>#</th><th>Segnalato da</th><th>Target</th><th>Motivo</th>
      <th>Stato</th><th>Data</th><th>Azioni</th>
    </tr></thead>
    <tbody>
    <?php if ($segnalazioni->num_rows === 0): ?>
      <tr><td colspan="7" class="adm-empty">Nessuna segnalazione in questa categoria.</td></tr>
    <?php else: while ($sg = $segnalazioni->fetch_assoc()): ?>
      <tr>
        <td><?= (int)$sg['id'] ?></td>
        <td><?= htmlspecialchars(($sg['da_nome']??'?').' '.($sg['da_cognome']??'')) ?></td>
        <td>
          <span class="adm-badge"><?= htmlspecialchars($sg['target_tipo']) ?></span>
          <div class="adm-segn-detail">ID #<?= (int)$sg['target_id'] ?>
            <?php if ($sg['target_tipo']==='utente'): ?>
              — <a href="/admin/utenti.php?q=<?= (int)$sg['target_id'] ?>" style="color:var(--m-primary);">vedi utente</a>
            <?php elseif ($sg['target_tipo']==='torneo'): ?>
              — <a href="/dettagli_torneo.php?id=<?= (int)$sg['target_id'] ?>" target="_blank" style="color:var(--m-primary);">vedi torneo</a>
            <?php endif; ?>
          </div>
        </td>
        <td style="max-width:200px;"><?= htmlspecialchars($sg['motivo']) ?>
          <?php if ($sg['nota_admin']): ?>
            <div class="adm-segn-detail">📝 <?= htmlspecialchars($sg['nota_admin']) ?></div>
          <?php endif; ?>
          <?php if ($sg['adm_nome']): ?>
            <div class="adm-segn-detail">Gestita da: <?= htmlspecialchars($sg['adm_nome'].' '.$sg['adm_cognome']) ?></div>
          <?php endif; ?>
        </td>
        <td><span class="adm-badge adm-badge--<?= $stato_badge[$sg['stato']] ?? '' ?>"><?= $stato_label[$sg['stato']] ?? $sg['stato'] ?></span></td>
        <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($sg['created_at'])) ?></td>
        <td>
          <?php if ($sg['stato'] !== 'chiusa'): ?>
            <div style="display:flex;flex-direction:column;gap:6px;">
              <?php if ($sg['stato'] === 'aperta'): ?>
                <form method="post" action="/admin/segnalazioni.php"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="in_revisione">
                  <input type="hidden" name="segn_id" value="<?= (int)$sg['id'] ?>">
                  <button class="adm-btn adm-btn--warn" style="width:100%;">Prendi in carico</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/admin/segnalazioni.php" class="adm-nota-form"><?= csrf_field() ?>
                <input type="hidden" name="action" value="chiudi">
                <input type="hidden" name="segn_id" value="<?= (int)$sg['id'] ?>">
                <input type="text" name="nota" placeholder="Nota (opz.)" class="adm-nota-input" maxlength="255">
                <button class="adm-btn adm-btn--ok">Chiudi</button>
              </form>
              <?php if ($sg['target_tipo'] === 'utente'): ?>
                <form method="post" action="/admin/segnalazioni.php"
                      onsubmit="return confirm('Bannare l\'utente e chiudere la segnalazione?')"
                      class="adm-nota-form"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="banna_segnalato">
                  <input type="hidden" name="segn_id" value="<?= (int)$sg['id'] ?>">
                  <input type="text" name="nota" placeholder="Motivo ban" class="adm-nota-input" maxlength="255">
                  <button class="adm-btn adm-btn--danger">Banna &amp; chiudi</button>
                </form>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <span class="adm-ip">Chiusa</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>
  <?php if ($totalPages > 1): ?>
    <div class="adm-pagination">
      <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <a href="?stato=<?= urlencode($filter) ?>&p=<?= $i ?>"
           class="adm-page <?= $i===$page?'adm-page--active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div></main>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>