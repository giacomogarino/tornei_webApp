<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
require_once 'php/helpers/sport_config.php';
session_secure_start();
include('conf/db_config.php');

$torneo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$torneo_id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare("
    SELECT t.*, u.nome AS org_nome, u.cognome AS org_cognome
    FROM torneo t
    JOIN utente u ON u.id = t.creato_da
    WHERE t.id = ?
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();
if (!$torneo) { header('Location: index.php'); exit; }

// Accesso: tornei privati richiedono login
if ($torneo['visibilita'] === 'privato' && !isset($_SESSION['id_utente'])) {
    header('Location: login.php?msg=NecessariaAutentificazione'); exit;
}

$sport     = $torneo['sport'];
$sport_cfg = sport_cfg($sport);

/* ─────────────────────────────────────────────
   1. Partite giocate totali
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT COUNT(*) as tot, SUM(punti_casa + punti_ospite) as tot_punti,
           AVG(punti_casa + punti_ospite) as media_punti,
           MAX(punti_casa + punti_ospite) as max_punti,
           MIN(punti_casa + punti_ospite) as min_punti
    FROM partita
    WHERE torneo_id = ? AND stato = 'terminata'
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$gen = $stmt->get_result()->fetch_assoc();

/* ─────────────────────────────────────────────
   2. Partita più combattuta (scarto minore)
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS nome_casa, so.nome AS nome_ospite,
           ABS(punti_casa - punti_ospite) AS scarto
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.stato = 'terminata'
    ORDER BY scarto ASC, (punti_casa + punti_ospite) DESC
    LIMIT 1
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$combattuta = $stmt->get_result()->fetch_assoc();

/* ─────────────────────────────────────────────
   3. Partita più prolifera (più punti totali)
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT p.*, sc.nome AS nome_casa, so.nome AS nome_ospite,
           (punti_casa + punti_ospite) AS tot
    FROM partita p
    JOIN squadra sc ON p.squadra_casa_id = sc.id
    JOIN squadra so ON p.squadra_ospite_id = so.id
    WHERE p.torneo_id = ? AND p.stato = 'terminata'
    ORDER BY tot DESC
    LIMIT 1
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$prolifera = $stmt->get_result()->fetch_assoc();

/* ─────────────────────────────────────────────
   4. Classifica marcatori per squadra (fase gironi)
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT s.id, s.nome,
           SUM(CASE WHEN p.squadra_casa_id   = s.id THEN p.punti_casa
                    WHEN p.squadra_ospite_id = s.id THEN p.punti_ospite
                    ELSE 0 END) AS punti_fatti,
           SUM(CASE WHEN p.squadra_casa_id   = s.id THEN p.punti_ospite
                    WHEN p.squadra_ospite_id = s.id THEN p.punti_casa
                    ELSE 0 END) AS punti_subiti,
           COUNT(DISTINCT p.id) AS partite
    FROM squadra s
    JOIN partita p ON (p.squadra_casa_id = s.id OR p.squadra_ospite_id = s.id)
    WHERE s.torneo_id = ? AND s.stato = 'approvata' AND p.stato = 'terminata'
    GROUP BY s.id, s.nome
    ORDER BY punti_fatti DESC
    LIMIT 10
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$marcatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$maxPunti  = !empty($marcatori) ? max(array_column($marcatori, 'punti_fatti')) : 1;

/* ─────────────────────────────────────────────
   5. % vittorie per squadra
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT
        s.id,
        s.nome,
        COUNT(DISTINCT p.id) AS g,
        SUM(
            CASE
                WHEN p.squadra_casa_id = s.id
                     AND p.punti_casa > p.punti_ospite THEN 1
                WHEN p.squadra_ospite_id = s.id
                     AND p.punti_ospite > p.punti_casa THEN 1
                ELSE 0
            END
        ) AS v,
        (
            SUM(
                CASE
                    WHEN p.squadra_casa_id = s.id
                         AND p.punti_casa > p.punti_ospite THEN 1
                    WHEN p.squadra_ospite_id = s.id
                         AND p.punti_ospite > p.punti_casa THEN 1
                    ELSE 0
                END
            ) / COUNT(DISTINCT p.id)
        ) AS pct
    FROM squadra s
    JOIN partita p
        ON (p.squadra_casa_id = s.id OR p.squadra_ospite_id = s.id)
    WHERE s.torneo_id = ?
      AND s.stato = 'approvata'
      AND p.stato = 'terminata'
    GROUP BY s.id, s.nome
    HAVING g > 0
    ORDER BY pct DESC, v DESC
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$vittorie_pct = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ─────────────────────────────────────────────
   6. Forma recente (ultime 5 partite) di ogni squadra
───────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT s.id, s.nome FROM squadra s
    WHERE s.torneo_id = ? AND s.stato = 'approvata'
    ORDER BY s.nome
");
$stmt->bind_param('i', $torneo_id);
$stmt->execute();
$tutte_squadre = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$forma = [];
foreach ($tutte_squadre as $sq) {
    $sid = $sq['id'];
    $stmt = $conn->prepare("
        SELECT CASE
            WHEN squadra_casa_id   = ? AND punti_casa   > punti_ospite THEN 'V'
            WHEN squadra_ospite_id = ? AND punti_ospite > punti_casa   THEN 'V'
            WHEN punti_casa = punti_ospite                              THEN 'P'
            ELSE 'S'
        END AS ris
        FROM partita
        WHERE torneo_id = ? AND stato = 'terminata'
          AND (squadra_casa_id = ? OR squadra_ospite_id = ?)
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->bind_param('iiiii', $sid, $sid, $torneo_id, $sid, $sid);
    $stmt->execute();
    $rs = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'ris');
    if (!empty($rs)) {
        $forma[] = [
            'id'   => $sid,
            'nome' => $sq['nome'],
            'forma'=> array_reverse($rs), // cronologico
        ];
    }
}

// Ordina per numero di V nelle ultime 5
usort($forma, function($a, $b) {
    $a_v = array_count_values($a['forma'])['V'] ?? 0;
    $b_v = array_count_values($b['forma'])['V'] ?? 0;
    return $b_v <=> $a_v;
});

/* ─────────────────────────────────────────────
   Titolo & OG
───────────────────────────────────────────── */
$page_title       = 'Statistiche — ' . $torneo['nome'];
$page_description = 'Statistiche del torneo ' . $torneo['nome'] . ': gol, marcatori, forma, record.';
$extra_css        = ['/css/tabella_tornei.css', '/css/torneo_struttura.css'];

/* ─────────────────────────────────────────────
   PREPARAZIONE DATI PER NAVBAR
───────────────────────────────────────────── */
$stato_label = [
    'in_attesa' => 'In attesa',
    'in_corso' => 'In corso',
    'completato' => 'Completato',
    'terminato' => 'Terminato'
];

$formato_label = [
    'gironi' => 'Gironi',
    'eliminazione' => 'Eliminazione diretta',
    'andata_ritorno' => 'Andata e Ritorno'
];

$tipo_label = [
    'singolo' => 'Singolo',
    'doppio' => 'Doppio',
    'squadra' => 'Squadra'
];

// Verifica se l'utente sta seguendo il torneo
$isFollowing = false;
if (isset($_SESSION['id_utente'])) {
    // Verifica se la tabella esiste
    $check_table = $conn->query("SHOW TABLES LIKE 'torneo_seguito'");
    if ($check_table && $check_table->num_rows > 0) {
        $stmt = $conn->prepare("SELECT 1 FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?");
        $stmt->bind_param('ii', $torneo_id, $_SESSION['id_utente']);
        $stmt->execute();
        $isFollowing = $stmt->get_result()->num_rows > 0;
    }
}

$navbar_data = [
    'torneo' => $torneo,
    'isOrganizzatore' => (isset($_SESSION['id_utente']) && $_SESSION['id_utente'] == $torneo['creato_da']),
    'stato_label' => $stato_label,
    'formato_label' => $formato_label,
    'tipo_label' => $tipo_label,
    'isFollowing' => $isFollowing
];

require_once('templates/header.php');
?>
<style>
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--m-3);
    margin-bottom: var(--m-5);
}
.stat-box {
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius);
    padding: var(--m-4);
    text-align: center;
}
.stat-box__num {
    font-family: var(--m-font-display);
    font-size: 2rem;
    font-weight: 800;
    color: var(--m-primary-400);
    line-height: 1;
    margin-bottom: 4px;
}
.stat-box__label {
    font-size: 12px;
    color: var(--m-text-mute);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.bar-row {
    display: flex;
    align-items: center;
    gap: var(--m-3);
    margin-bottom: 10px;
}
.bar-row__name {
    width: 130px;
    flex-shrink: 0;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bar-row__track {
    flex: 1;
    background: var(--m-surface-2, rgba(255,255,255,.05));
    border-radius: 99px;
    height: 14px;
    overflow: hidden;
}
.bar-row__fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--m-primary-500), var(--m-primary-400));
    transition: width .4s ease;
}
.bar-row__val {
    width: 40px;
    text-align: right;
    font-size: 13px;
    font-weight: 700;
    font-family: var(--m-font-display);
}
.forma-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
}
.forma-V { background: #16a34a22; color: #16a34a; }
.forma-S { background: #dc262622; color: #dc2626; }
.forma-P { background: #ca8a0422; color: #ca8a04; }
.match-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    background: var(--m-surface);
    border: 1px solid var(--m-border);
    border-radius: var(--m-radius);
}
.match-card__score {
    font-family: var(--m-font-display);
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: .05em;
    color: var(--m-primary-300);
}
.match-card__team {
    font-weight: 600;
    font-size: 14px;
    max-width: 120px;
    text-align: center;
}
</style>

<?php include('components/navbar_torneo.php') ?>

<main class="m-page">
<div class="m-container">

    <!-- Breadcrumb -->
    <div style="font-size:13px; margin-bottom: var(--m-4);">
        <a href="dettagli_torneo.php?id=<?= $torneo_id ?>" style="color:var(--m-text-mute);">
            ← <?= htmlspecialchars((string)($torneo['nome'] ?? '')) ?>
        </a>
    </div>

    <div class="m-page-head" style="margin-bottom: var(--m-5);">
        <h1 class="m-page-head__title">
            <?= htmlspecialchars((string)($sport_cfg['emoji'] ?? '') . ' ' . ($torneo['nome'] ?? '')) ?>
        </h1>
        <p class="m-page-head__sub">Statistiche torneo</p>
    </div>

    <!-- KPI generali -->
    <div class="stat-grid">
        <div class="stat-box">
            <div class="stat-box__num"><?= (int)$gen['tot'] ?></div>
            <div class="stat-box__label">Partite giocate</div>
        </div>
        <div class="stat-box">
            <div class="stat-box__num"><?= (int)($gen['tot_punti'] ?? 0) ?></div>
            <div class="stat-box__label"><?= htmlspecialchars((string)($sport_cfg['score_label'] ?? '')) ?> totali</div>
        </div>
        <div class="stat-box">
            <div class="stat-box__num"><?= number_format((float)($gen['media_punti'] ?? 0), 1) ?></div>
            <div class="stat-box__label">Media per partita</div>
        </div>
        <div class="stat-box">
            <div class="stat-box__num"><?= (int)($gen['max_punti'] ?? 0) ?></div>
            <div class="stat-box__label">Partita più ricca</div>
        </div>
        <div class="stat-box">
            <div class="stat-box__num"><?= count($tutte_squadre) ?></div>
            <div class="stat-box__label">Squadre</div>
        </div>
    </div>

    <?php if ($gen['tot'] == 0): ?>
        <div class="m-empty">
            <div class="m-empty__icon">📊</div>
            <h3>Nessuna partita ancora giocata</h3>
            <p class="m-muted">Le statistiche appariranno man mano che vengono inseriti i risultati.</p>
        </div>
    <?php else: ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--m-5); margin-bottom:var(--m-5);">

        <!-- Partita più combattuta -->
        <div class="m-card">
            <div class="m-card__header">
                <h3 class="m-card__title">⚔️ Partita più combattuta</h3>
            </div>
            <?php if ($combattuta): ?>
            <div class="match-card">
                <div class="match-card__team"><?= htmlspecialchars((string)($combattuta['nome_casa'] ?? '')) ?></div>
                <div class="match-card__score"><?= $combattuta['punti_casa'] ?? 0 ?> — <?= $combattuta['punti_ospite'] ?? 0 ?></div>
                <div class="match-card__team"><?= htmlspecialchars((string)($combattuta['nome_ospite'] ?? '')) ?></div>
            </div>
            <p class="m-muted" style="font-size:12px;margin-top:8px;">
                Scarto: <?= abs(($combattuta['punti_casa'] ?? 0) - ($combattuta['punti_ospite'] ?? 0)) ?>
                <?= htmlspecialchars((string)(strtolower($sport_cfg['score_label'] ?? ''))) ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Partita più prolifera -->
        <div class="m-card">
            <div class="m-card__header">
                <h3 class="m-card__title">🚀 Partita più prolifera</h3>
            </div>
            <?php if ($prolifera): ?>
            <div class="match-card">
                <div class="match-card__team"><?= htmlspecialchars((string)($prolifera['nome_casa'] ?? '')) ?></div>
                <div class="match-card__score"><?= $prolifera['punti_casa'] ?? 0 ?> — <?= $prolifera['punti_ospite'] ?? 0 ?></div>
                <div class="match-card__team"><?= htmlspecialchars((string)($prolifera['nome_ospite'] ?? '')) ?></div>
            </div>
            <p class="m-muted" style="font-size:12px;margin-top:8px;">
                Totale: <?= $prolifera['tot'] ?? 0 ?> <?= htmlspecialchars((string)(strtolower($sport_cfg['score_label'] ?? ''))) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Marcatori per squadra -->
    <div class="m-card m-mb-5">
        <div class="m-card__header">
            <h3 class="m-card__title">📈 <?= htmlspecialchars((string)($sport_cfg['score_label'] ?? '')) ?> fatti per squadra</h3>
        </div>
        <?php foreach ($marcatori as $sq):
            $pct = $maxPunti > 0 ? ($sq['punti_fatti'] / $maxPunti * 100) : 0;
        ?>
        <div class="bar-row">
            <div class="bar-row__name" title="<?= htmlspecialchars((string)($sq['nome'] ?? '')) ?>">
                <?= htmlspecialchars((string)($sq['nome'] ?? '')) ?>
            </div>
            <div class="bar-row__track">
                <div class="bar-row__fill" style="width: <?= number_format($pct, 1) ?>%"></div>
            </div>
            <div class="bar-row__val"><?= (int)($sq['punti_fatti'] ?? 0) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- % Vittorie -->
    <?php if (!empty($vittorie_pct)): ?>
    <div class="m-card m-mb-5">
        <div class="m-card__header">
            <h3 class="m-card__title">🏅 % Vittorie</h3>
        </div>
        <div class="m-table-wrap">
            <table class="m-table">
                <thead><tr>
                    <th>#</th><th>Squadra</th>
                    <th class="m-num">G</th><th class="m-num">V</th><th class="m-num">%</th>
                </tr></thead>
                <tbody>
                <?php foreach ($vittorie_pct as $pos => $sq):
                    $pct_v = $sq['g'] > 0 ? round($sq['v'] / $sq['g'] * 100) : 0;
                    $rank_class = $pos === 0 ? 'm-rank--1' : ($pos === 1 ? 'm-rank--2' : ($pos === 2 ? 'm-rank--3' : ''));
                ?>
                    <tr>
                        <td><span class="m-rank <?= $rank_class ?>"><?= $pos+1 ?></span></td>
                        <td>
                            <div class="m-team">
                                <span class="m-avatar m-avatar--sq"><?= strtoupper(mb_substr((string)($sq['nome'] ?? ''), 0, 2)) ?></span>
                                <?= htmlspecialchars((string)($sq['nome'] ?? '')) ?>
                            </div>
                        </td>
                        <td class="m-num"><?= (int)($sq['g'] ?? 0) ?></td>
                        <td class="m-num"><?= (int)($sq['v'] ?? 0) ?></td>
                        <td class="m-num"><b><?= $pct_v ?>%</b></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Forma recente -->
    <?php if (!empty($forma)): ?>
    <div class="m-card">
        <div class="m-card__header">
            <h3 class="m-card__title">🔥 Forma recente <span class="m-muted" style="font-weight:400;font-size:13px;">(ultime 5 partite)</span></h3>
        </div>
        <div class="m-table-wrap">
            <table class="m-table">
                <thead><tr>
                    <th>Squadra</th>
                    <th>Ultime partite</th>
                    <th class="m-num">V</th>
                </tr></thead>
                <tbody>
                <?php foreach ($forma as $sq):
                    $counts = array_count_values($sq['forma']);
                    $nv = $counts['V'] ?? 0;
                ?>
                    <tr>
                        <td>
                            <div class="m-team">
                                <span class="m-avatar m-avatar--sq"><?= strtoupper(mb_substr((string)($sq['nome'] ?? ''), 0, 2)) ?></span>
                                <?= htmlspecialchars((string)($sq['nome'] ?? '')) ?>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <?php foreach ($sq['forma'] as $r): ?>
                                <span class="forma-pill forma-<?= $r ?>"><?= $r ?></span>
                            <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="m-num"><b><?= $nv ?>/<?= count($sq['forma']) ?></b></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; /* gen['tot'] > 0 */ ?>

</div>
</main>
<?php require_once('templates/footer.php'); ?>