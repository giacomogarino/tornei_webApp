<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include("conf/db_config.php");

$utente_id = $_SESSION['id_utente'] ?? null;
if (!$utente_id) { header("Location: login.php"); exit; }

$squadra_id = isset($_GET['squadra_id']) ? (int)$_GET['squadra_id'] : null;
if (!$squadra_id) { header("Location: index.php"); exit; }

// Dati squadra + torneo
$stmt = $conn->prepare("
    SELECT s.*, t.stato AS torneo_stato, t.max_giocatori_per_squadra, t.nome AS nome_torneo
    FROM squadra s
    JOIN torneo t ON t.id = s.torneo_id
    WHERE s.id = ?
");
$stmt->bind_param("i", $squadra_id);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$squadra) { header("Location: index.php"); exit; }

// Solo capitano
if ($utente_id != $squadra['capitano_id']) { header("Location: dettagli_squadra.php?id=$squadra_id"); exit; }

// Solo se torneo aperto o in corso
if (!in_array($squadra['torneo_stato'], ['aperto', 'in_corso'])) {
    header("Location: dettagli_squadra.php?id=$squadra_id");
    exit;
}

$cerca = trim($_GET['cerca'] ?? '');
$errori = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // AGGIUNGI
    if (isset($_POST['aggiungi_id'])) {
        $nuovo_id = (int)$_POST['aggiungi_id'];

        // Conta giocatori attuali
        $stmt = $conn->prepare("SELECT COUNT(*) cnt FROM giocatore_squadra WHERE squadra_id = ?");
        $stmt->bind_param("i", $squadra_id);
        $stmt->execute();
        $cnt = $stmt->get_result()->fetch_assoc()['cnt'];

        if ($cnt >= $squadra['max_giocatori_per_squadra']) {
            $errori[] = "Squadra al completo.";
        } else {
            // Controlla che non sia già in una squadra del torneo
            $stmt = $conn->prepare("
                SELECT 1 FROM giocatore_squadra gs
                JOIN squadra s ON s.id = gs.squadra_id
                WHERE s.torneo_id = ? AND gs.utente_id = ? AND s.stato IN ('in_attesa','approvata')
                LIMIT 1
            ");
            $stmt->bind_param("ii", $squadra['torneo_id'], $nuovo_id);
            $stmt->execute();
            $gia = $stmt->get_result()->fetch_row();

            if ($gia) {
                $errori[] = "Questo giocatore è già in una squadra del torneo.";
            } else {
                $stmt = $conn->prepare("INSERT INTO giocatore_squadra (squadra_id, utente_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $squadra_id, $nuovo_id);
                $stmt->execute();
                header("Location: aggiungi_giocatore.php?squadra_id=$squadra_id&msg=ok&cerca=" . urlencode($cerca));
                exit;
            }
        }
    }

    // RIMUOVI
    if (isset($_POST['rimuovi_id'])) {
        $rimuovi_id = (int)$_POST['rimuovi_id'];
        if ($rimuovi_id != $squadra['capitano_id']) {
            $stmt = $conn->prepare("DELETE FROM giocatore_squadra WHERE squadra_id = ? AND utente_id = ?");
            $stmt->bind_param("ii", $squadra_id, $rimuovi_id);
            $stmt->execute();
        }
        header("Location: aggiungi_giocatore.php?squadra_id=$squadra_id&cerca=" . urlencode($cerca));
        exit;
    }
}

// Giocatori attuali
$stmt = $conn->prepare("
    SELECT u.id, u.nome, u.cognome
    FROM giocatore_squadra gs
    JOIN utente u ON u.id = gs.utente_id
    WHERE gs.squadra_id = ?
    ORDER BY u.cognome, u.nome
");
$stmt->bind_param("i", $squadra_id);
$stmt->execute();
$giocatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ricerca utenti
$risultati = [];
if ($cerca !== '') {
    $like = "%$cerca%";
    $stmt = $conn->prepare("
        SELECT id, nome, cognome, email FROM utente
        WHERE (nome LIKE ? OR cognome LIKE ? OR email LIKE ?)
        AND id != ?
        LIMIT 20
    ");
    $stmt->bind_param("sssi", $like, $like, $like, $utente_id);
    $stmt->execute();
    $risultati = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // IDs già in squadra del torneo
    $stmt = $conn->prepare("
        SELECT gs.utente_id FROM giocatore_squadra gs
        JOIN squadra s ON s.id = gs.squadra_id
        WHERE s.torneo_id = ? AND s.stato IN ('in_attesa','approvata')
    ");
    $stmt->bind_param("i", $squadra['torneo_id']);
    $stmt->execute();
    $occupati = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'utente_id');
    $stmt->close();
}

function iniziali($nome, $cognome = '') {
    return strtoupper(mb_substr($nome, 0, 1) . mb_substr($cognome, 0, 1)) ?: 'U';
}

require_once('templates/header.php');
?>

<main class="m-page">
    <div class="m-container" style="max-width: 880px;">

        <div style="margin-bottom: var(--m-4); font-size: 13px;">
            <a href="dettagli_squadra.php?id=<?= $squadra_id ?>" style="color: var(--m-text-mute);">
                ← Torna a <?= htmlspecialchars($squadra['nome']) ?>
            </a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Aggiungi giocatori</h1>
                <div class="m-page-head__sub"><?= htmlspecialchars($squadra['nome']) ?> · <?= htmlspecialchars($squadra['nome_torneo']) ?></div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
            <div class="m-alert m-alert--success m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <div>Giocatore aggiunto correttamente.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($errori as $e): ?>
            <div class="m-alert m-alert--danger m-mb-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                <div><?= htmlspecialchars($e) ?></div>
            </div>
        <?php endforeach; ?>

        <div style="display: grid; grid-template-columns: 1fr 300px; gap: var(--m-5); align-items: start;">

            <section>
                <div class="m-card">
                    <div class="m-card__header">
                        <h3 class="m-card__title">Cerca giocatore</h3>
                    </div>
                    <form method="GET" style="display: flex; gap: var(--m-2); margin-bottom: var(--m-4);">
                        <input type="hidden" name="squadra_id" value="<?= $squadra_id ?>">
                        <input class="m-input" type="text" name="cerca" placeholder="Nome, cognome o email..." value="<?= htmlspecialchars($cerca) ?>" style="flex: 1;">
                        <button class="m-btn m-btn--primary" type="submit">Cerca</button>
                    </form>

                    <?php if ($cerca !== '' && empty($risultati)): ?>
                        <p class="m-muted" style="font-style: italic;">Nessun utente trovato per "<?= htmlspecialchars($cerca) ?>".</p>
                    <?php endif; ?>

                    <?php if ($risultati): ?>
                        <div style="border: 1px solid var(--m-border); border-radius: var(--m-r-sm); overflow: hidden;">
                            <?php foreach ($risultati as $i => $r):
                                $is_occ = in_array($r['id'], $occupati ?? []);
                            ?>
                                <div style="display: grid; grid-template-columns: 36px 1fr auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $i > 0 ? 'border-top: 1px solid var(--m-border);' : '' ?> <?= $is_occ ? 'opacity: 0.55;' : '' ?>">
                                    <span class="m-avatar"><?= iniziali($r['nome'], $r['cognome']) ?></span>
                                    <div>
                                        <div style="font-weight: 500; <?= $is_occ ? 'text-decoration: line-through;' : '' ?>"><?= htmlspecialchars($r['nome']) ?> <?= htmlspecialchars($r['cognome']) ?></div>
                                        <div class="m-muted" style="font-size: 12px;"><?= htmlspecialchars($r['email']) ?></div>
                                    </div>
                                    <?php if ($is_occ): ?>
                                        <span class="m-badge m-badge--warn">Già in squadra</span>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="aggiungi_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                            <button class="m-btn m-btn--secondary m-btn--sm">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                                Aggiungi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside>
                <div class="m-card" style="position: sticky; top: calc(var(--m-navbar-h) + var(--m-3));">
                    <h4 class="m-profile-section-label">Squadra attuale</h4>
                    <div class="m-muted" style="font-size: 13px; margin-bottom: var(--m-3);">
                        <b style="color: var(--m-text);"><?= count($giocatori) ?> / <?= (int)$squadra['max_giocatori_per_squadra'] ?></b> giocatori
                    </div>
                    <?php $pct = $squadra['max_giocatori_per_squadra'] ? min(100, round(count($giocatori) / $squadra['max_giocatori_per_squadra'] * 100)) : 0; ?>
                    <div style="height: 6px; background: var(--m-surface-2); border-radius: 999px; overflow: hidden; margin-bottom: var(--m-4);">
                        <div style="height: 100%; width: <?= $pct ?>%; background: linear-gradient(90deg, var(--m-primary-400), var(--m-primary-600));"></div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: var(--m-2);">
                        <?php foreach ($giocatori as $g):
                            $is_cap = ($g['id'] == $squadra['capitano_id']);
                        ?>
                            <div style="display: grid; grid-template-columns: 32px 1fr auto; gap: 10px; align-items: center; padding: 8px; <?= $is_cap ? 'background: var(--m-primary-50); border-radius: 8px;' : '' ?>">
                                <span class="m-avatar" style="width:32px;height:32px;font-size:12px;"><?= iniziali($g['nome'], $g['cognome']) ?></span>
                                <div>
                                    <div style="font-weight: 500; font-size: 13px;"><?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?></div>
                                    <?php if ($is_cap): ?><div class="m-muted" style="font-size:11px;">Capitano</div><?php endif; ?>
                                </div>
                                <?php if (!$is_cap): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="rimuovi_id" value="<?= $g['id'] ?>">
                                        <input type="hidden" name="cerca" value="<?= htmlspecialchars($cerca) ?>">
                                        <button class="m-btn m-btn--ghost" style="padding:4px;width:auto;height:auto;" aria-label="Rimuovi">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

<?php require_once('templates/footer.php'); ?>