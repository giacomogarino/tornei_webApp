<?php
/**
 * PROFILO_ORGANIZZATORE.PHP — Profilo pubblico con recensioni
 * Posizione: /profilo_organizzatore.php
 * URL:       /profilo_organizzatore.php?id=123
 */
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include 'conf/db_config.php';

$org_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$org_id) { header('Location: /index.php'); exit; }

$me = $_SESSION['id_utente'] ?? null;

// ── Dati organizzatore ────────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, created_at, verified FROM utente WHERE id = ? AND bannato = 0 LIMIT 1'
);
$stmt->bind_param('i', $org_id);
$stmt->execute();
$org = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$org) { header('Location: /index.php?msg=errUtente'); exit; }

// ── Statistiche organizzatore ─────────────────────────────────────────
$stats = $conn->query(
    "SELECT
       COUNT(*)                        AS tornei_totali,
       SUM(stato = 'completato')       AS tornei_completati,
       SUM(stato = 'in_corso')         AS tornei_in_corso
     FROM torneo WHERE creato_da = $org_id"
)->fetch_assoc();

// ── Media e totale recensioni ─────────────────────────────────────────
$recStats = $conn->query(
    "SELECT COUNT(*) AS tot, ROUND(AVG(voto),1) AS media
     FROM recensione WHERE organizzatore_id = $org_id"
)->fetch_assoc();
$tot_rec  = (int)$recStats['tot'];
$media    = $tot_rec > 0 ? (float)$recStats['media'] : null;

// Distribuzione stelle
$dist = [];
for ($i = 1; $i <= 5; $i++) $dist[$i] = 0;
$dRes = $conn->query(
    "SELECT voto, COUNT(*) AS n FROM recensione
     WHERE organizzatore_id = $org_id GROUP BY voto"
);
while ($d = $dRes->fetch_assoc()) $dist[(int)$d['voto']] = (int)$d['n'];

// ── Tornei completati dell'organizzatore ──────────────────────────────
$torneiStmt = $conn->prepare(
    "SELECT id, nome, sport, stato FROM torneo
     WHERE creato_da = ? ORDER BY id DESC LIMIT 10"
);
$torneiStmt->bind_param('i', $org_id);
$torneiStmt->execute();
$tornei_org = $torneiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$torneiStmt->close();

// ── Recensioni esistenti (paginazione) ───────────────────────────────
$rpage   = max(1, (int)($_GET['rp'] ?? 1));
$rPerPag = 5;
$rOffset = ($rpage - 1) * $rPerPag;
$rPages  = $tot_rec > 0 ? (int)ceil($tot_rec / $rPerPag) : 1;

$recStmt = $conn->prepare(
    "SELECT r.id, r.voto, r.testo, r.created_at,
            u.nome AS autore_nome, u.cognome AS autore_cognome,
            t.nome AS torneo_nome, t.id AS torneo_id
     FROM recensione r
     JOIN utente u ON u.id = r.autore_id
     JOIN torneo t ON t.id = r.torneo_id
     WHERE r.organizzatore_id = ?
     ORDER BY r.created_at DESC LIMIT ? OFFSET ?"
);
$recStmt->bind_param('iii', $org_id, $rPerPag, $rOffset);
$recStmt->execute();
$recensioni = $recStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recStmt->close();

// ── L'utente loggato può recensire? ──────────────────────────────────
// Condizioni: loggato, non è l'organizzatore, ha partecipato a un torneo
// completato di questo organizzatore, e non ha già una recensione per quel torneo
$tornei_recensibili = [];
if ($me && $me !== $org_id) {
    $eligStmt = $conn->prepare(
        "SELECT t.id, t.nome
         FROM torneo t
         WHERE t.creato_da = ? AND t.stato = 'completato'
           AND (
               EXISTS (SELECT 1 FROM squadra sq JOIN giocatore_squadra g ON g.squadra_id = sq.id
                       WHERE sq.torneo_id = t.id AND g.utente_id = ? AND sq.stato = 'approvata')
               OR
               EXISTS (SELECT 1 FROM squadra sq2
                       WHERE sq2.torneo_id = t.id AND sq2.capitano_id = ? AND sq2.stato = 'approvata')
           )
           AND NOT EXISTS (
               SELECT 1 FROM recensione r2
               WHERE r2.torneo_id = t.id AND r2.autore_id = ?
           )"
    );
    $eligStmt->bind_param('iiii', $org_id, $me, $me, $me);
    $eligStmt->execute();
    $tornei_recensibili = $eligStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $eligStmt->close();
}

// ── Recensione già inviata per questo organizzatore (per mostrare la propria) ──
$mia_rec = null;
if ($me && $me !== $org_id) {
    $mrStmt = $conn->prepare(
        'SELECT r.voto, r.testo, r.created_at, t.nome AS torneo_nome
         FROM recensione r JOIN torneo t ON t.id = r.torneo_id
         WHERE r.organizzatore_id = ? AND r.autore_id = ?
         ORDER BY r.created_at DESC LIMIT 1'
    );
    $mrStmt->bind_param('ii', $org_id, $me);
    $mrStmt->execute();
    $mia_rec = $mrStmt->get_result()->fetch_assoc();
    $mrStmt->close();
}

$flash_msgs = [
    'recensioneInviata'     => ['success', 'Recensione inviata. Grazie!'],
    'errRecensione'         => ['danger',  'Errore nell\'invio della recensione.'],
    'errRecensioneSelf'     => ['danger',  'Non puoi recensire te stesso.'],
    'errTorneoNonCompletato'=> ['warn',    'Puoi recensire solo tornei completati.'],
    'errNonPartecipante'    => ['warn',    'Puoi recensire solo tornei a cui hai partecipato.'],
];
$flash = isset($_GET['msg']) ? ($flash_msgs[$_GET['msg']] ?? null) : null;

$initials = strtoupper(mb_substr($org['nome'],0,1) . mb_substr($org['cognome'],0,1));
$page_title = 'Profilo di ' . htmlspecialchars($org['nome'] . ' ' . $org['cognome']);
require_once 'templates/header.php';

function stelle(float $v, bool $small = false): string {
    $sz  = $small ? '14' : '18';
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= round($v) ? '#f59e0b' : '#d1d5db';
        $out .= "<svg width='$sz' height='$sz' viewBox='0 0 24 24' fill='$fill'
                      style='flex-shrink:0'><polygon points='12 2 15.09 8.26 22 9.27
                      17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27
                      8.91 8.26 12 2'/></svg>";
    }
    return "<span style='display:inline-flex;gap:2px;align-items:center;'>$out</span>";
}
?>
<link rel="stylesheet" href="/css/profilo.css">
<style>
.po-layout     { display:grid; grid-template-columns:1fr 340px; gap:var(--m-6); align-items:start; }
.po-stars-dist { display:flex; flex-direction:column; gap:4px; margin-top:var(--m-3); }
.po-dist-row   { display:grid; grid-template-columns:16px 1fr 32px; gap:8px; align-items:center; font-size:.8rem; }
.po-dist-bar   { height:8px; background:var(--m-border); border-radius:4px; overflow:hidden; }
.po-dist-fill  { height:100%; background:#f59e0b; border-radius:4px; transition:width .3s; }
.po-rec-card   { border:1px solid var(--m-border); border-radius:var(--m-radius-lg);
                 padding:var(--m-4); margin-bottom:var(--m-3); background:var(--m-surface); }
.po-rec-head   { display:flex; justify-content:space-between; align-items:flex-start;
                 margin-bottom:var(--m-2); flex-wrap:wrap; gap:6px; }
.po-author     { font-weight:600; font-size:.9rem; }
.po-date       { font-size:.75rem; color:var(--m-text-secondary); }
.po-text       { font-size:.875rem; color:var(--m-text); line-height:1.55; margin-top:6px; }
.po-tornei-grid{ display:flex; flex-direction:column; gap:8px; }
.po-torneo-row { display:flex; justify-content:space-between; align-items:center;
                 padding:8px 12px; border:1px solid var(--m-border);
                 border-radius:var(--m-radius); font-size:.875rem; }
/* Stelle interattive nel form */
.star-rating       { display:flex; flex-direction:row-reverse; gap:4px; justify-content:flex-end; }
.star-rating input { display:none; }
.star-rating label {
    font-size:0; cursor:pointer;
    display:inline-block; width:28px; height:28px;
}
.star-rating label svg { width:28px; height:28px; fill:#d1d5db; transition:fill .1s; }
.star-rating input:checked ~ label svg,
.star-rating label:hover svg,
.star-rating label:hover ~ label svg { fill:#f59e0b; }
@media(max-width:780px){
    .po-layout { grid-template-columns:1fr; }
}
</style>

<main class="m-page">
  <div class="m-container">

    <?php if ($flash): ?>
      <div class="m-alert m-alert--<?= $flash[0] ?>" style="margin-bottom:var(--m-5);">
        <?= htmlspecialchars($flash[1]) ?>
      </div>
    <?php endif; ?>

    <!-- Intestazione profilo -->
    <div class="m-card" style="margin-bottom:var(--m-5);display:flex;align-items:center;
         gap:var(--m-5);flex-wrap:wrap;">
      <span class="m-avatar" style="width:64px;height:64px;font-size:1.5rem;flex-shrink:0;">
        <?= $initials ?>
      </span>
      <div style="flex:1;">
        <h1 style="margin:0;font-size:1.5rem;">
          <?= htmlspecialchars($org['nome'] . ' ' . $org['cognome']) ?>
        </h1>
        <div class="m-muted" style="font-size:.85rem;margin-top:4px;">
          Organizzatore · Membro dal <?= date('F Y', strtotime($org['created_at'])) ?>
        </div>
        <?php if ($media !== null): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
            <?= stelle($media) ?>
            <span style="font-weight:700;font-size:1.1rem;"><?= number_format($media,1) ?></span>
            <span class="m-muted" style="font-size:.85rem;">(<?= $tot_rec ?> recension<?= $tot_rec===1?'e':'i' ?>)</span>
          </div>
        <?php else: ?>
          <div class="m-muted" style="margin-top:8px;font-size:.85rem;">Nessuna recensione ancora</div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:var(--m-3);flex-wrap:wrap;">
        <div style="text-align:center;padding:0 var(--m-4);">
          <div style="font-size:1.5rem;font-weight:700;"><?= (int)$stats['tornei_totali'] ?></div>
          <div class="m-muted" style="font-size:.75rem;">Tornei organizzati</div>
        </div>
        <div style="text-align:center;padding:0 var(--m-4);border-left:1px solid var(--m-border);">
          <div style="font-size:1.5rem;font-weight:700;"><?= (int)$stats['tornei_completati'] ?></div>
          <div class="m-muted" style="font-size:.75rem;">Completati</div>
        </div>
      </div>
    </div>

    <div class="po-layout">

      <!-- COLONNA SX: recensioni -->
      <section>

        <!-- Form nuova recensione -->
        <?php if (!empty($tornei_recensibili)): ?>
          <div class="m-card" style="margin-bottom:var(--m-5);
               border:2px solid var(--m-primary);border-style:dashed;">
            <h3 style="margin:0 0 var(--m-4);">⭐ Lascia una recensione</h3>
            <form method="post" action="/php/invia_recensione.php">
              <?= csrf_field() ?>
              <input type="hidden" name="organizzatore_id" value="<?= $org_id ?>">

              <!-- Selezione torneo -->
              <div class="m-field" style="margin-bottom:var(--m-4);">
                <label class="m-label">Torneo di riferimento</label>
                <select name="torneo_id" class="m-input" required>
                  <option value="">Seleziona un torneo…</option>
                  <?php foreach ($tornei_recensibili as $tr): ?>
                    <option value="<?= (int)$tr['id'] ?>"><?= htmlspecialchars($tr['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Stelle interattive -->
              <div class="m-field" style="margin-bottom:var(--m-4);">
                <label class="m-label">Voto</label>
                <div class="star-rating">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="voto" id="star<?= $i ?>" value="<?= $i ?>" required>
                    <label for="star<?= $i ?>" aria-label="<?= $i ?> stelle">
                      <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27
                        17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14
                        2 9.27 8.91 8.26 12 2"/></svg>
                    </label>
                  <?php endfor; ?>
                </div>
              </div>

              <!-- Testo opzionale -->
              <div class="m-field" style="margin-bottom:var(--m-4);">
                <label class="m-label">
                  Commento <span class="m-muted">(opzionale, max 500 caratteri)</span>
                </label>
                <textarea name="testo" rows="3" maxlength="500"
                          placeholder="Racconta la tua esperienza con questo organizzatore…"
                          class="m-input" style="resize:vertical;"
                          oninput="document.getElementById('cnt-rec').textContent=this.value.length"></textarea>
                <div style="text-align:right;font-size:.75rem;color:var(--m-text-secondary);">
                  <span id="cnt-rec">0</span>/500
                </div>
              </div>

              <button type="submit" class="m-btn m-btn--primary">Invia recensione</button>
            </form>
          </div>
        <?php endif; ?>

        <!-- La mia recensione esistente -->
        <?php if ($mia_rec): ?>
          <div class="m-alert m-alert--info" style="margin-bottom:var(--m-4);">
            <div>
              <strong>La tua recensione</strong> (<?= htmlspecialchars($mia_rec['torneo_nome']) ?>)<br>
              <?= stelle((float)$mia_rec['voto'], true) ?>
              <?php if ($mia_rec['testo']): ?>
                <div style="margin-top:4px;font-size:.85rem;"><?= htmlspecialchars($mia_rec['testo']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Lista recensioni -->
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:var(--m-4);">
          Recensioni (<?= $tot_rec ?>)
        </h2>

        <?php if (empty($recensioni)): ?>
          <div class="m-empty">
            <div class="m-empty__icon">⭐</div>
            <h3>Nessuna recensione ancora</h3>
            <p class="m-muted">Le recensioni appariranno qui dopo i tornei completati.</p>
          </div>
        <?php else: ?>
          <?php foreach ($recensioni as $r): ?>
            <div class="po-rec-card">
              <div class="po-rec-head">
                <div>
                  <div class="po-author">
                    <?= htmlspecialchars($r['autore_nome'] . ' ' . $r['autore_cognome']) ?>
                  </div>
                  <div class="po-date">
                    per <a href="/dettagli_torneo.php?id=<?= (int)$r['torneo_id'] ?>"
                           style="color:var(--m-primary);text-decoration:none;">
                      <?= htmlspecialchars($r['torneo_nome']) ?>
                    </a>
                    · <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                  </div>
                </div>
                <?= stelle((float)$r['voto'], true) ?>
              </div>
              <?php if ($r['testo']): ?>
                <div class="po-text"><?= nl2br(htmlspecialchars($r['testo'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- Paginazione recensioni -->
          <?php if ($rPages > 1): ?>
            <div class="adm-pagination" style="margin-top:var(--m-4);">
              <?php for ($i = 1; $i <= $rPages; $i++): ?>
                <a href="?id=<?= $org_id ?>&rp=<?= $i ?>#recensioni"
                   class="adm-page <?= $i===$rpage?'adm-page--active':'' ?>"><?= $i ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

      </section>

      <!-- COLONNA DX: sidebar -->
      <aside>

        <!-- Distribuzione stelle -->
        <?php if ($tot_rec > 0): ?>
          <div class="m-card" style="margin-bottom:var(--m-4);">
            <h4 class="m-profile-section-label">Distribuzione voti</h4>
            <div style="display:flex;align-items:center;gap:var(--m-4);margin-bottom:var(--m-3);">
              <div style="font-size:3rem;font-weight:800;line-height:1;"><?= number_format($media,1) ?></div>
              <div>
                <?= stelle($media) ?>
                <div class="m-muted" style="font-size:.8rem;margin-top:4px;"><?= $tot_rec ?> recension<?= $tot_rec===1?'e':'i' ?></div>
              </div>
            </div>
            <div class="po-stars-dist">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php $pct = $tot_rec > 0 ? round($dist[$i]/$tot_rec*100) : 0; ?>
                <div class="po-dist-row">
                  <span style="font-size:.8rem;font-weight:600;"><?= $i ?></span>
                  <div class="po-dist-bar">
                    <div class="po-dist-fill" style="width:<?= $pct ?>%;"></div>
                  </div>
                  <span style="color:var(--m-text-secondary);"><?= $dist[$i] ?></span>
                </div>
              <?php endfor; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Tornei organizzati -->
        <div class="m-card">
          <h4 class="m-profile-section-label">Tornei organizzati</h4>
          <?php if (empty($tornei_org)): ?>
            <p class="m-muted" style="font-size:.875rem;">Nessun torneo ancora.</p>
          <?php else: ?>
            <div class="po-tornei-grid">
              <?php
              $stato_badge = ['aperto'=>'ok','in_corso'=>'warn','completato'=>'','sospeso'=>'danger'];
              $stato_label = ['aperto'=>'Aperto','in_corso'=>'In corso','completato'=>'Completato','sospeso'=>'Sospeso'];
              foreach ($tornei_org as $t): ?>
                <div class="po-torneo-row">
                  <div>
                    <a href="/dettagli_torneo.php?id=<?= (int)$t['id'] ?>"
                       style="font-weight:600;text-decoration:none;color:var(--m-text);">
                      <?= htmlspecialchars($t['nome']) ?>
                    </a>
                    <?php if ($t['sport']): ?>
                      <div class="m-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['sport']) ?></div>
                    <?php endif; ?>
                  </div>
                  <span class="adm-badge adm-badge--<?= $stato_badge[$t['stato']] ?? '' ?>">
                    <?= $stato_label[$t['stato']] ?? $t['stato'] ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </aside>
    </div>
  </div>
</main>

<?php require_once 'templates/footer.php'; ?>
