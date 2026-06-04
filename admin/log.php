<?php
/**
 * ADMIN/LOG.PHP — Audit log completo delle azioni admin
 * Posizione: /admin/log.php
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

// ── Filtri ────────────────────────────────────────────────────────────
$f_admin  = (int)($_GET['admin']  ?? 0);
$f_azione = trim($_GET['azione']  ?? '');
$f_tipo   = trim($_GET['tipo']    ?? '');
$f_da     = trim($_GET['da']      ?? '');
$f_a      = trim($_GET['a']       ?? '');
$page     = max(1, (int)($_GET['p'] ?? 1));
$perPage  = 30;
$offset   = ($page - 1) * $perPage;

// Costruisci WHERE dinamico
$conds  = []; $params = []; $types  = '';
if ($f_admin > 0) {
    $conds[] = 'l.admin_id = ?'; $params[] = $f_admin; $types .= 'i';
}
if ($f_azione !== '') {
    $conds[] = 'l.azione = ?'; $params[] = $f_azione; $types .= 's';
}
if ($f_tipo !== '') {
    $conds[] = 'l.target_tipo = ?'; $params[] = $f_tipo; $types .= 's';
}
if ($f_da !== '') {
    $conds[] = 'l.created_at >= ?'; $params[] = $f_da . ' 00:00:00'; $types .= 's';
}
if ($f_a !== '') {
    $conds[] = 'l.created_at <= ?'; $params[] = $f_a . ' 23:59:59'; $types .= 's';
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

// Conta totale
$cntStmt = $conn->prepare(
    "SELECT COUNT(*) AS n FROM admin_log l $where"
);
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total      = (int)$cntStmt->get_result()->fetch_assoc()['n'];
$cntStmt->close();
$totalPages = (int)ceil($total / $perPage);

// Lista log
$lp = $params; $lp[] = $perPage; $lp[] = $offset;
$listStmt = $conn->prepare(
    "SELECT l.id, l.azione, l.target_tipo, l.target_id,
            l.dettagli, l.ip, l.created_at,
            u.nome AS admin_nome, u.cognome AS admin_cognome
     FROM admin_log l
     LEFT JOIN utente u ON u.id = l.admin_id
     $where
     ORDER BY l.created_at DESC
     LIMIT ? OFFSET ?"
);
$listStmt->bind_param($types . 'ii', ...$lp);
$listStmt->execute();
$logs = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

// Liste per i filtri dropdown
$admins = $conn->query(
    "SELECT DISTINCT u.id, u.nome, u.cognome
     FROM admin_log l JOIN utente u ON u.id = l.admin_id
     ORDER BY u.nome"
)->fetch_all(MYSQLI_ASSOC);

$azioni = $conn->query(
    "SELECT DISTINCT azione FROM admin_log ORDER BY azione"
)->fetch_all(MYSQLI_ASSOC);

$tipi = $conn->query(
    "SELECT DISTINCT target_tipo FROM admin_log WHERE target_tipo IS NOT NULL ORDER BY target_tipo"
)->fetch_all(MYSQLI_ASSOC);

// ── HTML ──────────────────────────────────────────────────────────────
$page_title = 'Admin — Audit log';
require_once __DIR__ . '/../templates/header.php';

// Colori/icone per azione
function azione_badge(string $azione): string {
    $map = [
        'ban_utente'      => ['danger', '🚫'],
        'unban_utente'    => ['ok',     '✅'],
        'elimina_utente'  => ['danger', '🗑️'],
        'elimina_torneo'  => ['danger', '🗑️'],
        'sospendi_torneo' => ['warn',   '⏸️'],
        'riattiva_torneo' => ['ok',     '▶️'],
        'segn_in_revisione'=> ['warn',  '🔍'],
        'segn_chiusa'     => ['ok',     '✔️'],
    ];
    $cfg   = $map[$azione] ?? ['', '⚡'];
    $label = htmlspecialchars($azione);
    return "<span class='adm-badge adm-badge--{$cfg[0]}'>{$cfg[1]} {$label}</span>";
}

function target_link(string $tipo, int $id): string {
    if (!$tipo || !$id) return '—';
    $links = [
        'utente'      => "/admin/utenti.php?q=$id",
        'torneo'      => "/dettagli_torneo.php?id=$id",
        'segnalazione'=> "/admin/segnalazioni.php",
    ];
    $url = $links[$tipo] ?? '#';
    return "<a href='$url' style='color:var(--m-primary);text-decoration:none;font-size:.8rem;'>
                $tipo #$id
            </a>";
}
?>
<link rel="stylesheet" href="/css/admin.css">
<style>
.log-filters {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m-3);
    margin-bottom: var(--m-5);
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius-lg);
    padding: var(--m-4);
    align-items: flex-end;
}
.log-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 140px;
    flex: 1;
}
.log-filter-group label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--m-text-secondary);
}
.log-filter-group select,
.log-filter-group input {
    padding: 7px 10px;
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius);
    font-size: .85rem;
    background: var(--m-surface);
    color: var(--m-text);
    width: 100%;
}
.log-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--m-3);
    margin-bottom: var(--m-5);
}
.log-stat {
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius-lg);
    padding: var(--m-3) var(--m-4);
    display: flex;
    align-items: center;
    gap: var(--m-3);
}
.log-stat__icon { font-size: 1.4rem; line-height: 1; }
.log-stat__val  { font-size: 1.3rem; font-weight: 700; color: var(--m-primary); line-height: 1; }
.log-stat__lbl  { font-size: .72rem; color: var(--m-text-secondary); text-transform: uppercase;
                  font-weight: 600; letter-spacing: .04em; }
.log-dettagli   { font-size: .72rem; font-family: monospace; color: var(--m-text-secondary);
                  background: var(--m-surface-2, #f5f5f5); padding: 2px 6px;
                  border-radius: 4px; max-width: 180px; overflow: hidden;
                  text-overflow: ellipsis; white-space: nowrap; display: block; }
.log-export-btn { margin-left: auto; }
@media(max-width: 700px) {
    .log-filters { flex-direction: column; }
    .log-filter-group { min-width: 100%; }
}
</style>

<main class="m-page">
  <div class="m-container">

    <div class="m-page-head">
      <div>
        <h1>Audit Log</h1>
        <div class="m-page-head__sub">
          Storico completo di tutte le azioni degli amministratori
        </div>
      </div>
      <div style="display:flex;gap:var(--m-3);">
        <a href="/admin/log.php?export=csv&admin=<?= $f_admin ?>&azione=<?= urlencode($f_azione) ?>&tipo=<?= urlencode($f_tipo) ?>&da=<?= urlencode($f_da) ?>&a=<?= urlencode($f_a) ?>"
           class="m-btn m-btn--ghost m-btn--sm">
          ⬇️ Esporta CSV
        </a>
        <a href="/admin/index.php" class="m-btn m-btn--ghost">← Dashboard</a>
      </div>
    </div>

    <?php
    // ── Export CSV (gestito prima dell'HTML) ──────────────────────────
    // Nota: l'export va in cima alla pagina ma dopo il check admin.
    // Se arriva ?export=csv, invia il file e termina.
    if (isset($_GET['export']) && $_GET['export'] === 'csv'):
        // Ri-esegue la query senza LIMIT per avere tutti i dati
        $expStmt = $conn->prepare(
            "SELECT l.created_at, u.nome, u.cognome, l.azione,
                    l.target_tipo, l.target_id, l.dettagli, l.ip
             FROM admin_log l
             LEFT JOIN utente u ON u.id = l.admin_id
             $where ORDER BY l.created_at DESC"
        );
        if ($types) $expStmt->bind_param($types, ...$params);
        $expStmt->execute();
        $rows = $expStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $expStmt->close();
        // Output CSV inline (in un blocco nascosto — il JS lo scarica)
        $csv  = "Data,Admin,Azione,Target tipo,Target ID,Dettagli,IP\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [
                '"' . $r['created_at'] . '"',
                '"' . $r['nome'] . ' ' . $r['cognome'] . '"',
                '"' . $r['azione'] . '"',
                '"' . ($r['target_tipo'] ?? '') . '"',
                '"' . ($r['target_id']   ?? '') . '"',
                '"' . addslashes($r['dettagli'] ?? '') . '"',
                '"' . ($r['ip'] ?? '') . '"',
            ]) . "\n";
        }
        $fname = 'audit_log_' . date('Ymd_His') . '.csv';
    ?>
    <div id="csv-download" data-csv="<?= htmlspecialchars(base64_encode($csv)) ?>"
         data-filename="<?= $fname ?>"></div>
    <script>
    (function(){
        var el  = document.getElementById('csv-download');
        var csv = atob(el.dataset.csv);
        var bom = '\uFEFF'; // BOM per Excel UTF-8
        var blob= new Blob([bom + csv], {type:'text/csv;charset=utf-8;'});
        var url = URL.createObjectURL(blob);
        var a   = document.createElement('a');
        a.href  = url; a.download = el.dataset.filename; a.click();
        URL.revokeObjectURL(url);
    })();
    </script>
    <?php endif; ?>

    <!-- Statistiche rapide -->
    <?php
    $totaleAzioni = $conn->query("SELECT COUNT(*) AS n FROM admin_log")->fetch_assoc()['n'];
    $oggi         = $conn->query("SELECT COUNT(*) AS n FROM admin_log WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['n'];
    $settimana    = $conn->query("SELECT COUNT(*) AS n FROM admin_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['n'];
    $totBan       = $conn->query("SELECT COUNT(*) AS n FROM admin_log WHERE azione = 'ban_utente'")->fetch_assoc()['n'];
    ?>
    <div class="log-stats">
      <div class="log-stat">
        <div class="log-stat__icon">📋</div>
        <div><div class="log-stat__val"><?= number_format($totaleAzioni) ?></div>
             <div class="log-stat__lbl">Azioni totali</div></div>
      </div>
      <div class="log-stat">
        <div class="log-stat__icon">📅</div>
        <div><div class="log-stat__val"><?= $oggi ?></div>
             <div class="log-stat__lbl">Oggi</div></div>
      </div>
      <div class="log-stat">
        <div class="log-stat__icon">📆</div>
        <div><div class="log-stat__val"><?= $settimana ?></div>
             <div class="log-stat__lbl">Ultimi 7 giorni</div></div>
      </div>
      <div class="log-stat">
        <div class="log-stat__icon">🚫</div>
        <div><div class="log-stat__val"><?= $totBan ?></div>
             <div class="log-stat__lbl">Ban totali</div></div>
      </div>
    </div>

    <!-- Filtri -->
    <form method="get" action="/admin/log.php" class="log-filters">
      <div class="log-filter-group">
        <label>Admin</label>
        <select name="admin">
          <option value="">Tutti</option>
          <?php foreach ($admins as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $f_admin===$a['id']?'selected':'' ?>>
              <?= htmlspecialchars($a['nome'].' '.$a['cognome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="log-filter-group">
        <label>Azione</label>
        <select name="azione">
          <option value="">Tutte</option>
          <?php foreach ($azioni as $az): ?>
            <option value="<?= htmlspecialchars($az['azione']) ?>"
                    <?= $f_azione===$az['azione']?'selected':'' ?>>
              <?= htmlspecialchars($az['azione']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="log-filter-group">
        <label>Tipo target</label>
        <select name="tipo">
          <option value="">Tutti</option>
          <?php foreach ($tipi as $t): ?>
            <option value="<?= htmlspecialchars($t['target_tipo']) ?>"
                    <?= $f_tipo===$t['target_tipo']?'selected':'' ?>>
              <?= htmlspecialchars($t['target_tipo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="log-filter-group">
        <label>Dal</label>
        <input type="date" name="da" value="<?= htmlspecialchars($f_da) ?>">
      </div>
      <div class="log-filter-group">
        <label>Al</label>
        <input type="date" name="a" value="<?= htmlspecialchars($f_a) ?>">
      </div>
      <div style="display:flex;gap:var(--m-2);align-items:flex-end;">
        <button type="submit" class="m-btn">Filtra</button>
        <a href="/admin/log.php" class="m-btn m-btn--ghost">Reset</a>
      </div>
    </form>

    <!-- Risultati -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--m-3);">
      <div class="m-muted" style="font-size:.85rem;">
        <?= number_format($total) ?> risultat<?= $total===1?'o':'i' ?>
        <?php if ($where): ?> con i filtri applicati<?php endif; ?>
      </div>
    </div>

    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Data e ora</th>
            <th>Admin</th>
            <th>Azione</th>
            <th>Target</th>
            <th>Dettagli</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" class="adm-empty">Nessuna azione trovata con i filtri applicati.</td></tr>
        <?php else: foreach ($logs as $log): ?>
          <tr>
            <td class="adm-ip"><?= (int)$log['id'] ?></td>
            <td style="white-space:nowrap;">
              <div style="font-weight:500;font-size:.85rem;">
                <?= date('d/m/Y', strtotime($log['created_at'])) ?>
              </div>
              <div class="adm-ip">
                <?= date('H:i:s', strtotime($log['created_at'])) ?>
              </div>
            </td>
            <td>
              <span style="font-weight:600;font-size:.875rem;">
                <?= htmlspecialchars(($log['admin_nome'] ?? '?') . ' ' . ($log['admin_cognome'] ?? '')) ?>
              </span>
            </td>
            <td><?= azione_badge($log['azione']) ?></td>
            <td>
              <?php if ($log['target_tipo'] && $log['target_id']): ?>
                <?= target_link($log['target_tipo'], (int)$log['target_id']) ?>
              <?php else: ?>
                <span class="adm-ip">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($log['dettagli'] && $log['dettagli'] !== '0'): ?>
                <?php $det = json_decode($log['dettagli'], true); ?>
                <?php if ($det): ?>
                  <span class="log-dettagli" title="<?= htmlspecialchars(json_encode($det, JSON_UNESCAPED_UNICODE)) ?>">
                    <?php foreach ($det as $k => $v): ?>
                      <?= htmlspecialchars($k) ?>: <?= htmlspecialchars((string)$v) ?>
                    <?php endforeach; ?>
                  </span>
                <?php else: ?>
                  <span class="log-dettagli"><?= htmlspecialchars($log['dettagli']) ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="adm-ip">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="adm-ip" title="<?= htmlspecialchars($log['ip'] ?? '') ?>">
                <?= htmlspecialchars($log['ip'] ?? '—') ?>
              </span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginazione -->
    <?php if ($totalPages > 1): ?>
      <div class="adm-pagination" style="margin-top:var(--m-4);">
        <?php
        // Mostra al massimo 7 pagine attorno a quella corrente
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
        if ($start > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p'=>1])) ?>"
             class="adm-page">1</a>
          <?php if ($start > 2): ?><span class="adm-ip" style="padding:5px;">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$i])) ?>"
             class="adm-page <?= $i===$page?'adm-page--active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><span class="adm-ip" style="padding:5px;">…</span><?php endif; ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$totalPages])) ?>"
             class="adm-page"><?= $totalPages ?></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
