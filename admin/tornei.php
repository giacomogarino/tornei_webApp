<?php
/**
 * ADMIN/TORNEI.PHP — Gestione tornei
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action    = $_POST['action'] ?? '';
    $torneo_id = (int)($_POST['torneo_id'] ?? 0);

    switch ($action) {

        case 'elimina':
            $s = $conn->prepare('DELETE FROM torneo WHERE id = ?');
            $s->bind_param('i', $torneo_id);
            $s->execute();
            $s->close();

            admin_log($conn, 'elimina_torneo', 'torneo', $torneo_id);
            header('Location: /admin/tornei.php?msg=eliminato');
            exit;


        case 'sospendi':
            $s = $conn->prepare("UPDATE torneo SET stato = 'sospeso' WHERE id = ?");
            $s->bind_param('i', $torneo_id);
            $s->execute();
            $s->close();

            admin_log($conn, 'sospendi_torneo', 'torneo', $torneo_id);
            header('Location: /admin/tornei.php?msg=sospeso');
            exit;


        case 'riattiva':

            $s = $conn->prepare('SELECT stato, data_chiusura_iscrizioni FROM torneo WHERE id = ?');
            $s->bind_param('i', $torneo_id);
            $s->execute();
            $row = $s->get_result()->fetch_assoc();
            $s->close();

            if (!$row) {
                header('Location: /admin/tornei.php?msg=errore');
                exit;
            }

            $nuovoStato = 'in_corso';

            if (!empty($row['data_chiusura_iscrizioni'])) {

                $oggi = new DateTime('now', new DateTimeZone('Europe/Rome'));
                $chiusura = new DateTime($row['data_chiusura_iscrizioni'], new DateTimeZone('Europe/Rome'));

                if ($chiusura >= $oggi) {
                    $nuovoStato = 'aperto';
                }
            }

            $s = $conn->prepare("UPDATE torneo SET stato = ? WHERE id = ?");
            $s->bind_param('si', $nuovoStato, $torneo_id);
            $s->execute();
            $s->close();

            admin_log($conn, 'riattiva_torneo', 'torneo', $torneo_id, [
                'nuovo_stato' => $nuovoStato
            ]);

            header('Location: /admin/tornei.php?msg=riattivato');
            exit;


        default:
            header('Location: /admin/tornei.php');
            exit;
    }
}

// ── HTML ──────────────────────────────────────────────────────────────
$page_title = 'Admin — Gestione tornei';
require_once __DIR__ . '/../templates/header.php';

$flash_map = [
    'eliminato'  => ['success', 'Torneo eliminato.'],
    'sospeso'    => ['success', 'Torneo sospeso.'],
    'riattivato' => ['success', 'Torneo riattivato.'],
];
$flash = isset($_GET['msg']) ? ($flash_map[$_GET['msg']] ?? null) : null;

$search  = trim($_GET['q'] ?? '');
$filter  = $_GET['stato'] ?? '';
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$conds = []; $params = []; $types = '';
if ($search !== '') {
    $conds[] = '(t.nome LIKE ? OR t.sport LIKE ? OR t.luogo LIKE ?)';
    $like = '%'.$search.'%';
    $params = array_merge($params, [$like,$like,$like]); $types .= 'sss';
}
if ($filter !== '') {
    $conds[] = 't.stato = ?';
    $params[] = $filter; $types .= 's';
}
$where = $conds ? 'WHERE '.implode(' AND ', $conds) : '';

$cntStmt = $conn->prepare("SELECT COUNT(*) AS n FROM torneo t $where");
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total      = (int)$cntStmt->get_result()->fetch_assoc()['n'];
$cntStmt->close();
$totalPages = (int)ceil($total / $perPage);

$lp = $params; $lp[] = $perPage; $lp[] = $offset;
$listStmt = $conn->prepare(
    "SELECT t.id, t.nome, t.sport, t.formato, t.stato, t.visibilita,
            t.data_chiusura_iscrizioni, t.creato_da,
            u.nome AS org_nome, u.cognome AS org_cognome,
            (SELECT COUNT(*) FROM squadra s WHERE s.torneo_id=t.id AND s.stato='approvata') AS n_squadre
     FROM torneo t
     LEFT JOIN utente u ON u.id = t.creato_da
     $where ORDER BY t.id DESC LIMIT ? OFFSET ?");
$listStmt->bind_param($types.'ii', ...$lp);
$listStmt->execute();
$tornei = $listStmt->get_result();
$listStmt->close();

$stato_label = ['aperto'=>'Aperto','in_corso'=>'In corso','completato'=>'Completato','sospeso'=>'Sospeso'];
$stato_badge = ['aperto'=>'ok','in_corso'=>'warn','completato'=>'','sospeso'=>'danger'];
$formato_label = [
    'eliminazione_diretta'=>'Elim. diretta',
    'girone_unico'=>'Girone unico',
    'gironi_playoff'=>'Gironi+playoff',
];
?>
<link rel="stylesheet" href="/css/admin.css">
<main class="m-page"><div class="m-container">
  <div class="m-page-head">
    <div><h1>Gestione tornei</h1><div class="m-page-head__sub"><?= $total ?> tornei totali</div></div>
    <a href="/admin/index.php" class="m-btn m-btn--ghost">← Dashboard</a>
  </div>
  <?php if ($flash): ?>
    <div class="m-alert m-alert--<?= $flash[0] ?>" style="margin-bottom:1.5rem;"><?= htmlspecialchars($flash[1]) ?></div>
  <?php endif; ?>
  <!-- Filtri -->
  <form method="get" class="adm-search">
    <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cerca per nome, sport, luogo…" class="adm-search__input">
    <select name="stato" class="adm-input-sm" style="width:auto;padding:7px 10px;">
      <option value="">Tutti gli stati</option>
      <?php foreach ($stato_label as $v => $l): ?>
        <option value="<?= $v ?>" <?= $filter===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="m-btn">Filtra</button>
    <a href="/admin/tornei.php" class="m-btn m-btn--ghost">Reset</a>
  </form>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>#</th><th>Nome</th><th>Sport</th><th>Formato</th><th>Stato</th><th>Squadre</th><th>Organizzatore</th><th>Chiusura</th><th>Azioni</th></tr></thead>
    <tbody>
    <?php if ($tornei->num_rows === 0): ?>
      <tr><td colspan="9" class="adm-empty">Nessun torneo trovato.</td></tr>
    <?php else: while ($t = $tornei->fetch_assoc()): ?>
      <tr>
        <td><?= (int)$t['id'] ?></td>
        <td><a href="/dettagli_torneo.php?id=<?= (int)$t['id'] ?>" target="_blank" style="color:var(--m-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($t['nome']) ?></a></td>
        <td><?= htmlspecialchars($t['sport'] ?? '—') ?></td>
        <td><?= htmlspecialchars($formato_label[$t['formato']] ?? $t['formato']) ?></td>
        <td><span class="adm-badge adm-badge--<?= $stato_badge[$t['stato']] ?? '' ?>"><?= $stato_label[$t['stato']] ?? $t['stato'] ?></span></td>
        <td style="text-align:center;"><?= (int)$t['n_squadre'] ?></td>
        <td><?= htmlspecialchars(($t['org_nome']??'').' '.($t['org_cognome']??'')) ?></td>
        <td style="white-space:nowrap;"><?= $t['data_chiusura_iscrizioni'] ? date('d/m/Y', strtotime($t['data_chiusura_iscrizioni'])) : '—' ?></td>
        <td>
          <div class="adm-row-actions">
            <?php if ($t['stato'] !== 'sospeso'): ?>
              <form method="post" action="/admin/tornei.php" onsubmit="return confirm('Sospendere il torneo?')"><?= csrf_field() ?>
                <input type="hidden" name="action" value="sospendi">
                <input type="hidden" name="torneo_id" value="<?= (int)$t['id'] ?>">
                <button class="adm-btn adm-btn--warn">Sospendi</button></form>
            <?php else: ?>
              <form method="post" action="/admin/tornei.php"><?= csrf_field() ?>
                <input type="hidden" name="action" value="riattiva">
                <input type="hidden" name="torneo_id" value="<?= (int)$t['id'] ?>">
                <button class="adm-btn adm-btn--ok">Riattiva</button></form>
            <?php endif; ?>
            <form method="post" action="/admin/tornei.php" onsubmit="return confirm('Eliminare definitivamente il torneo e tutti i suoi dati?')"><?= csrf_field() ?>
              <input type="hidden" name="action" value="elimina">
              <input type="hidden" name="torneo_id" value="<?= (int)$t['id'] ?>">
              <button class="adm-btn adm-btn--danger">Elimina</button></form>
          </div>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>
  <?php if ($totalPages > 1): ?>
    <div class="adm-pagination">
      <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <a href="?q=<?= urlencode($search) ?>&stato=<?= urlencode($filter) ?>&p=<?= $i ?>"
           class="adm-page <?= $i===$page?'adm-page--active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div></main>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>