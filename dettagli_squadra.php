<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
require_once 'php/helpers/sport_config.php';
session_secure_start();
include("conf/db_config.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) { header("Location: index.php?msg=errSquadraNonTrovata"); exit; }

$utente_id = $_SESSION['id_utente'] ?? null;
$isLoggato = $utente_id !== null;

// Dati squadra + torneo
$stmt = $conn->prepare("
    SELECT s.id, s.nome, s.stato, s.capitano_id, s.torneo_id, s.persone_pranzo,
           t.nome AS nome_torneo, t.pranzo AS pranzo, t.stato AS torneo_stato, t.sport
    FROM squadra s
    JOIN torneo t ON t.id = s.torneo_id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
if (!$squadra) { header("Location: index.php?msg=errSquadraNonTrovata"); exit; }

$sport     = $squadra['sport'];
$sport_cfg = sport_cfg($sport);

// POST: persone pranzo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['persone_pranzo'])) {
        $persone = max(0, (int)$_POST['persone_pranzo']);
        $stmt = $conn->prepare("UPDATE squadra SET persone_pranzo = ? WHERE id = ?");
        $stmt->bind_param("ii", $persone, $id);
        $stmt->execute();
        header("Location: dettagli_squadra.php?id=$id&msg=ok"); exit;
    }
}

// Giocatori
$stmt = $conn->prepare("
    SELECT u.id, u.nome, u.cognome
    FROM giocatore_squadra gs
    JOIN utente u ON u.id = gs.utente_id
    WHERE gs.squadra_id = ?
    ORDER BY u.cognome ASC, u.nome ASC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$giocatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Statistiche aggregate partite
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT p.id) AS partite,
        SUM(CASE WHEN p.squadra_casa_id   = ? THEN p.punti_casa
                 WHEN p.squadra_ospite_id = ? THEN p.punti_ospite ELSE 0 END) AS pf,
        SUM(CASE WHEN p.squadra_casa_id   = ? THEN p.punti_ospite
                 WHEN p.squadra_ospite_id = ? THEN p.punti_casa ELSE 0 END) AS ps,
        SUM(CASE
            WHEN p.squadra_casa_id   = ? AND p.punti_casa   > p.punti_ospite THEN 1
            WHEN p.squadra_ospite_id = ? AND p.punti_ospite > p.punti_casa   THEN 1
            ELSE 0 END) AS v,
        SUM(CASE
            WHEN p.punti_casa = p.punti_ospite THEN 1
            ELSE 0 END) AS par,
        SUM(CASE
            WHEN p.squadra_casa_id   = ? AND p.punti_casa   < p.punti_ospite THEN 1
            WHEN p.squadra_ospite_id = ? AND p.punti_ospite < p.punti_casa   THEN 1
            ELSE 0 END) AS s
    FROM partita p
    WHERE p.torneo_id = ?
      AND (p.squadra_casa_id = ? OR p.squadra_ospite_id = ?)
      AND p.stato = 'terminata'
");
$stmt->bind_param('iiiiiiiiiii', $id,$id,$id,$id,$id,$id,$id,$id,$squadra['torneo_id'],$id,$id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Storico partite
$stmt = $conn->prepare("
    SELECT p.id, p.orario, p.turno, p.girone, p.punti_casa, p.punti_ospite, p.stato,
           sc.nome AS nome_casa, so.nome AS nome_ospite,
           sc.id   AS id_casa,  so.id   AS id_ospite
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id   = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ?
      AND (p.squadra_casa_id = ? OR p.squadra_ospite_id = ?)
      AND p.stato = 'terminata'
    ORDER BY p.id DESC
");
$stmt->bind_param('iii', $squadra['torneo_id'], $id, $id);
$stmt->execute();
$storico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Forma recente (ultime 5)
$forma_recente = [];
foreach (array_slice($storico, 0, 5) as $p) {
    if ($p['id_casa'] == $id)
        $forma_recente[] = $p['punti_casa'] > $p['punti_ospite'] ? 'V' : ($p['punti_casa'] == $p['punti_ospite'] ? 'P' : 'S');
    else
        $forma_recente[] = $p['punti_ospite'] > $p['punti_casa'] ? 'V' : ($p['punti_casa'] == $p['punti_ospite'] ? 'P' : 'S');
}
$forma_recente = array_reverse($forma_recente);

$is_capitano = ($utente_id && $utente_id == $squadra['capitano_id']);

$page_title       = $squadra['nome'] . ' — ' . $squadra['nome_torneo'];
$page_description = 'Profilo squadra ' . $squadra['nome'] . ' nel torneo ' . $squadra['nome_torneo'];
$extra_css = ['/css/tabella_tornei.css', '/css/torneo_struttura.css'];
require_once('templates/header.php');

function squadra_iniziali($nome, $cognome='') {
    return strtoupper(mb_substr(trim($nome),0,1) . mb_substr(trim($cognome),0,1)) ?: 'U';
}

function risultato_class(array $p, int $squadra_id): string {
    if ($p['id_casa'] == $squadra_id)
        return $p['punti_casa'] > $p['punti_ospite'] ? 'V' : ($p['punti_casa'] == $p['punti_ospite'] ? 'P' : 'S');
    return $p['punti_ospite'] > $p['punti_casa'] ? 'V' : ($p['punti_casa'] == $p['punti_ospite'] ? 'P' : 'S');
}

$stato_class = 'm-state-' . htmlspecialchars($squadra['stato']);
$pct_v = ($stats['partite'] > 0) ? round($stats['v'] / $stats['partite'] * 100) : 0;
?>
<style>
.ds-layout { display:grid; grid-template-columns:1fr 300px; gap:var(--m-5); align-items:start; }
.ds-stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:var(--m-3); margin-bottom:var(--m-5); }
.ds-stat { background:var(--m-surface); border:1px solid var(--m-border); border-radius:var(--m-radius); padding:var(--m-3) var(--m-4); text-align:center; }
.ds-stat__num { font-family:var(--m-font-display); font-size:1.8rem; font-weight:800; color:var(--m-primary-400); line-height:1; }
.ds-stat__lbl { font-size:11px; color:var(--m-text-mute); text-transform:uppercase; letter-spacing:.04em; margin-top:2px; }
.ds-player-row { display:grid; grid-template-columns:36px 1fr auto; gap:var(--m-3); padding:var(--m-3); align-items:center; }
.ds-info-grid { display:grid; grid-template-columns:1fr; gap:var(--m-3); font-size:14px; margin:0; }
.forma-pill { display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;font-size:12px;font-weight:700; }
.forma-V { background:#16a34a22;color:#16a34a; }
.forma-S { background:#dc262622;color:#dc2626; }
.forma-P { background:#ca8a0422;color:#ca8a04; }
.ris-badge { display:inline-block;width:24px;height:24px;border-radius:4px;font-size:12px;font-weight:700;text-align:center;line-height:24px; }
.ris-V { background:#16a34a22;color:#16a34a; }
.ris-S { background:#dc262622;color:#dc2626; }
.ris-P { background:#ca8a0422;color:#ca8a04; }
.pct-bar { background:var(--m-surface-2,rgba(255,255,255,.06)); border-radius:99px; height:8px; overflow:hidden; margin-top:6px; }
.pct-bar__fill { height:100%; border-radius:99px; background:linear-gradient(90deg,var(--m-primary-500),var(--m-primary-400)); }
@media(max-width:700px){
    .ds-layout{grid-template-columns:1fr;}
    .ds-layout aside{order:2;}
    .ds-stat-row{grid-template-columns:repeat(2,1fr);}
}
</style>

<main class="m-page">
<div class="m-container" style="max-width:900px;">

    <div style="margin-bottom:var(--m-4);font-size:13px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <a href="dettagli_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>" style="color:var(--m-text-mute);">
            ← Torna a <?= htmlspecialchars($squadra['nome_torneo']) ?>
        </a>
        <!-- iCal squadra -->
        <a href="/php/esporta_calendario.php?id=<?= (int)$squadra['torneo_id'] ?>&squadra=<?= (int)$id ?>"
           class="m-btn m-btn--ghost m-btn--sm" title="Esporta partite in calendario">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
            Calendario .ics
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
        <div class="m-alert m-alert--success m-mb-4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <div>Dati aggiornati correttamente.</div>
        </div>
    <?php endif; ?>

    <!-- Header squadra -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--m-3);flex-wrap:wrap;margin-bottom:var(--m-5);">
        <div style="display:flex;align-items:center;gap:var(--m-4);">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--m-primary-600),var(--m-primary-400));display:flex;align-items:center;justify-content:center;font-family:var(--m-font-display);font-size:1.4rem;font-weight:800;color:#fff;flex-shrink:0;">
                <?= strtoupper(mb_substr($squadra['nome'],0,2)) ?>
            </div>
            <div>
                <h1 style="margin:0 0 4px;"><?= htmlspecialchars($squadra['nome']) ?></h1>
                <div class="m-muted" style="font-size:13px;">
                    <?= htmlspecialchars($sport_cfg['emoji'] . ' ' . $sport_cfg['label']) ?>
                    &mdash;
                    <a href="dettagli_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>">
                        <?= htmlspecialchars($squadra['nome_torneo']) ?>
                    </a>
                </div>
                <?php if (!empty($forma_recente)): ?>
                <div style="display:flex;gap:4px;margin-top:6px;">
                    <?php foreach ($forma_recente as $r): ?>
                        <span class="forma-pill forma-<?= $r ?>"><?= $r ?></span>
                    <?php endforeach; ?>
                    <span class="m-muted" style="font-size:11px;align-self:center;">forma recente</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <span class="m-badge m-badge--dot <?= $stato_class ?>" style="font-size:13px;padding:6px 14px;">
            <?= htmlspecialchars(ucfirst($squadra['stato'])) ?>
        </span>
    </div>

    <!-- Stats aggregate -->
    <?php if ($stats['partite'] > 0): ?>
    <div class="ds-stat-row">
        <div class="ds-stat">
            <div class="ds-stat__num"><?= (int)$stats['partite'] ?></div>
            <div class="ds-stat__lbl">Partite</div>
        </div>
        <div class="ds-stat" style="border-color:#16a34a44;">
            <div class="ds-stat__num" style="color:#16a34a;"><?= (int)$stats['v'] ?></div>
            <div class="ds-stat__lbl">Vittorie</div>
        </div>
        <?php if ($sport_cfg['ha_pareggio']): ?>
        <div class="ds-stat" style="border-color:#ca8a0444;">
            <div class="ds-stat__num" style="color:#ca8a04;"><?= (int)$stats['par'] ?></div>
            <div class="ds-stat__lbl">Pareggi</div>
        </div>
        <?php endif; ?>
        <div class="ds-stat" style="border-color:#dc262644;">
            <div class="ds-stat__num" style="color:#dc2626;"><?= (int)$stats['s'] ?></div>
            <div class="ds-stat__lbl">Sconfitte</div>
        </div>
        <div class="ds-stat">
            <div class="ds-stat__num"><?= (int)$stats['pf'] ?></div>
            <div class="ds-stat__lbl"><?= htmlspecialchars($sport_cfg['score_label']) ?> fatti</div>
        </div>
        <?php if ($sport_cfg['ha_pareggio']): ?>
        <div class="ds-stat">
            <div class="ds-stat__num"><?= (int)$stats['ps'] ?></div>
            <div class="ds-stat__lbl"><?= htmlspecialchars($sport_cfg['score_label']) ?> subiti</div>
        </div>
        <div class="ds-stat" style="<?= ($stats['pf']-$stats['ps']) >= 0 ? 'border-color:#16a34a44;' : 'border-color:#dc262644;' ?>">
            <div class="ds-stat__num" style="<?= ($stats['pf']-$stats['ps']) >= 0 ? 'color:#16a34a;' : 'color:#dc2626;' ?>">
                <?= (($stats['pf']-$stats['ps']) > 0 ? '+' : '') . ($stats['pf']-$stats['ps']) ?>
            </div>
            <div class="ds-stat__lbl">Differenza</div>
        </div>
        <?php endif; ?>
        <div class="ds-stat">
            <div class="ds-stat__num"><?= $pct_v ?>%</div>
            <div class="ds-stat__lbl">% vittorie</div>
            <div class="pct-bar"><div class="pct-bar__fill" style="width:<?= $pct_v ?>%"></div></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="ds-layout">

        <!-- Colonna principale -->
        <section>

            <!-- Storico partite -->
            <?php if (!empty($storico)): ?>
            <div class="m-card m-mb-5">
                <div class="m-card__header">
                    <h3 class="m-card__title">Storico partite</h3>
                </div>
                <div class="m-table-wrap">
                    <table class="m-table">
                        <thead><tr>
                            <th></th><th>Avversario</th><th>Risultato</th><th>Fase</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($storico as $p):
                            $ris  = risultato_class($p, $id);
                            $isCasa = ($p['id_casa'] == $id);
                            $avversario = $isCasa ? $p['nome_ospite'] : $p['nome_casa'];
                            $avv_id     = $isCasa ? $p['id_ospite']   : $p['id_casa'];
                            $score = $isCasa
                                ? $p['punti_casa'] . ' — ' . $p['punti_ospite']
                                : $p['punti_ospite'] . ' — ' . $p['punti_casa'];
                            $fase = $p['girone'] !== null ? 'Girone ' . $p['girone'] : ucfirst($p['turno'] ?? '');
                        ?>
                            <tr>
                                <td><span class="ris-badge ris-<?= $ris ?>"><?= $ris ?></span></td>
                                <td>
                                    <a href="dettagli_squadra.php?id=<?= (int)$avv_id ?>" style="font-weight:500;">
                                        <?= htmlspecialchars($avversario) ?>
                                    </a>
                                    <?php if (!$isCasa): ?>
                                        <span class="m-muted" style="font-size:11px;"> (ospite)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="m-num"><b><?= $score ?></b></td>
                                <td><span class="m-badge m-badge--neutral"><?= htmlspecialchars($fase) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Giocatori -->
            <div class="m-card">
                <div class="m-card__header">
                    <h3 class="m-card__title">Rosa <span class="m-muted" style="font-weight:400;">(<?= count($giocatori) ?>)</span></h3>
                </div>
                <?php if (empty($giocatori)): ?>
                    <p class="m-muted"><em>Nessun giocatore nella squadra.</em></p>
                <?php else: ?>
                    <?php foreach ($giocatori as $i => $g): $is_cap = ($g['id'] == $squadra['capitano_id']); ?>
                        <div class="ds-player-row" style="<?= $i > 0 ? 'border-top:1px solid var(--m-border);' : '' ?>">
                            <span class="m-avatar" style="<?= $is_cap ? 'background:linear-gradient(135deg,var(--m-gold-400),var(--m-gold-600));color:#2a1d00;' : '' ?>">
                                <?= squadra_iniziali($g['nome'], $g['cognome']) ?>
                            </span>
                            <div>
                                <div style="font-weight:500;"><?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?></div>
                                <?php if ($is_cap): ?><div class="m-muted" style="font-size:12px;">Capitano</div><?php endif; ?>
                            </div>
                            <?php if ($is_cap): ?>
                                <span class="m-badge m-badge--gold">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                                    Cap
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </section>

        <!-- Sidebar -->
        <aside>
            <div class="m-card m-mb-4">
                <h4 class="m-profile-section-label">Info squadra</h4>
                <dl class="ds-info-grid">
                    <div>
                        <dt class="m-muted" style="font-size:12px;">Giocatori</dt>
                        <dd style="margin:0;font-family:var(--m-font-display);font-size:22px;font-weight:700;"><?= count($giocatori) ?></dd>
                    </div>
                    <?php if ($is_capitano && in_array($squadra['torneo_stato'] ?? '', ['aperto','in_corso'])): ?>
                        <a href="aggiungi_giocatore.php?squadra_id=<?= (int)$squadra['id'] ?>" class="m-btn m-btn--secondary m-btn--block m-mt-3">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Aggiungi giocatore
                        </a>
                    <?php endif; ?>
                    <?php if ($squadra['pranzo'] == 1): ?>
                    <div>
                        <dt class="m-muted" style="font-size:12px;">Persone pranzo</dt>
                        <dd style="margin:0;font-family:var(--m-font-display);font-size:22px;font-weight:700;"><?= (int)$squadra['persone_pranzo'] ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Link utili -->
            <div class="m-card m-mb-4">
                <h4 class="m-profile-section-label">Link utili</h4>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <a href="/php/esporta_calendario.php?id=<?= (int)$squadra['torneo_id'] ?>&squadra=<?= (int)$id ?>"
                       class="m-btn m-btn--secondary m-btn--block">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
                        Esporta calendario .ics
                    </a>
                    <a href="struttura_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>"
                       class="m-btn m-btn--ghost m-btn--block">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                        Struttura torneo
                    </a>
                    <a href="statistiche_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>"
                       class="m-btn m-btn--ghost m-btn--block">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Statistiche torneo
                    </a>
                </div>
            </div>

            <!-- Gestione pranzo (capitano) -->
            <?php if ($is_capitano && $squadra['pranzo'] == 1 && in_array($squadra['torneo_stato'] ?? '', ['aperto','in_corso'])): ?>
                <div class="m-card m-mb-4" style="background:linear-gradient(180deg,var(--m-primary-50),var(--m-surface));border-color:var(--m-primary-200);">
                    <h4 class="m-profile-section-label">Gestione pranzo</h4>
                    <p class="m-muted m-mb-3" style="font-size:13px;">Aggiorna le persone che mangeranno.</p>
                    <form method="POST" class="m-stack">
                        <?= csrf_field() ?>
                        <div class="m-field">
                            <label class="m-label" for="persone_pranzo">Numero persone</label>
                            <input class="m-input m-num" type="number" id="persone_pranzo" name="persone_pranzo" min="0" value="<?= (int)$squadra['persone_pranzo'] ?>" required>
                        </div>
                        <button type="submit" class="m-btn m-btn--primary m-btn--block">Salva</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Segnalazione -->
            <?php if ($isLoggato && $_SESSION['id_utente'] != $squadra['capitano_id']): ?>
                <div class="m-card" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--m-3);">
                    <div>
                        <div style="font-weight:600;font-size:.875rem;">Problema con questa squadra?</div>
                        <div class="m-muted" style="font-size:.8rem;">Segnalaci comportamenti scorretti.</div>
                    </div>
                    <?php
                    $modal_target_tipo = 'squadra';
                    $modal_target_id   = $squadra['id'];
                    $modal_redirect    = '/dettagli_squadra.php?id=' . $squadra['id'];
                    $modal_label       = 'Segnala squadra';
                    include 'components/segnala_modal.php';
                    ?>
                </div>
            <?php endif; ?>
        </aside>

    </div>
</div>
</main>
<?php require_once('templates/footer.php'); ?>
