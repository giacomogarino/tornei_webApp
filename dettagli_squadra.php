<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include("conf/db_config.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// Dati squadra + torneo
$sql = "
    SELECT s.id, s.nome, s.stato, s.capitano_id, s.torneo_id, s.persone_pranzo,
           t.nome AS nome_torneo
    FROM squadra s
    JOIN torneo t ON t.id = s.torneo_id
    WHERE s.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$squadra) {
    header("Location: index.php?msg=errSquadraNonTrovata");
    exit;
}

// update persone pranzo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['persone_pranzo'])) {

        $persone = (int)$_POST['persone_pranzo'];

        if ($persone < 0) $persone = 0;

        $stmt = $conn->prepare("
            UPDATE squadra
            SET persone_pranzo = ?
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $persone, $id);
        $stmt->execute();

        header("Location: dettagli_squadra.php?id=$id&msg=ok");
        exit;
    }
}

// giocatori squadra
$sql_giocatori = "
    SELECT u.id, u.nome, u.cognome
    FROM giocatore_squadra gs
    JOIN utente u ON u.id = gs.utente_id
    WHERE gs.squadra_id = ?
    ORDER BY u.cognome ASC, u.nome ASC
";

$stmt = $conn->prepare($sql_giocatori);
$stmt->bind_param("i", $id);
$stmt->execute();
$giocatori = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$utente_id = $_SESSION['id_utente'] ?? null;
$is_capitano = ($utente_id && $utente_id == $squadra['capitano_id']);

require_once('templates/header.php');

function squadra_iniziali($nome, $cognome=''){
    $a = mb_substr(trim($nome), 0, 1);
    $b = mb_substr(trim($cognome), 0, 1);
    return strtoupper($a . $b) ?: 'U';
}
$stato_class = 'm-state-' . htmlspecialchars($squadra['stato']);
?>

<main class="m-page">
    <div class="m-container" style="max-width: 880px;">

        <div style="margin-bottom: var(--m-4); font-size: 13px;">
            <a href="dettagli_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>" style="color: var(--m-text-mute);"> Torna a <?= htmlspecialchars($squadra['nome_torneo']) ?></a>
        </div>

        <div class="m-page-head">
            <div>
                <h1><?= htmlspecialchars($squadra['nome']) ?></h1>
                <div class="m-page-head__sub">
                    Torneo: <a href="dettagli_torneo.php?id=<?= (int)$squadra['torneo_id'] ?>"><b><?= htmlspecialchars($squadra['nome_torneo']) ?></b></a>
                </div>
            </div>
            <span class="m-badge m-badge--dot <?= $stato_class ?>" style="font-size: 13px; padding: 6px 14px;"><?= htmlspecialchars(ucfirst($squadra['stato'])) ?></span>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
            <div class="m-alert m-alert--success m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <div>Dati aggiornati correttamente.</div>
            </div>
        <?php endif; ?>

        <div class="m-grid" style="grid-template-columns: 1fr 320px; gap: var(--m-5);">

            <section>
                <div class="m-card">
                    <div class="m-card__header">
                        <h3 class="m-card__title">Giocatori <span class="m-muted" style="font-weight: 400;">(<?= count($giocatori) ?>)</span></h3>
                    </div>
                    <?php if (empty($giocatori)): ?>
                        <p class="m-muted"><em>Nessun giocatore nella squadra.</em></p>
                    <?php else: ?>
                        <div>
                            <?php foreach ($giocatori as $i => $g): $is_cap = ($g['id'] == $squadra['capitano_id']); ?>
                                <div style="display: grid; grid-template-columns: 36px 1fr auto; gap: var(--m-3); padding: var(--m-3); align-items: center; <?= $i > 0 ? 'border-top: 1px solid var(--m-border);' : '' ?>">
                                    <span class="m-avatar<?= $is_cap ? '' : '' ?>" style="<?= $is_cap ? 'background: linear-gradient(135deg, var(--m-gold-400), var(--m-gold-600)); color: #2a1d00;' : '' ?>"><?= squadra_iniziali($g['nome'], $g['cognome']) ?></span>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($g['nome']) ?> <?= htmlspecialchars($g['cognome']) ?></div>
                                    </div>
                                    <?php if ($is_cap): ?>
                                        <span class="m-badge m-badge--gold">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/></svg>
                                            Capitano
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside>
                <div class="m-card">
                    <h4 class="m-profile-section-label">Info squadra</h4>
                    <dl style="display: grid; grid-template-columns: 1fr; gap: var(--m-3); font-size: 14px; margin: 0;">
                        <div>
                            <dt class="m-muted" style="font-size: 12px;">ID</dt>
                            <dd style="margin: 0; font-family: var(--m-font-mono);">#<?= (int)$squadra['id'] ?></dd>
                        </div>
                        <div>
                            <dt class="m-muted" style="font-size: 12px;">Persone pranzo</dt>
                            <dd style="margin: 0; font-family: var(--m-font-display); font-size: 22px; font-weight: 700;"><?= (int)$squadra['persone_pranzo'] ?></dd>
                        </div>
                    </dl>
                </div>

                <?php if ($is_capitano): ?>
                    <div class="m-card m-mt-4" style="background: linear-gradient(180deg, var(--m-primary-50), var(--m-surface)); border-color: var(--m-primary-200);">
                        <h4 class="m-profile-section-label">Gestione pranzo</h4>
                        <p class="m-muted m-mb-3" style="font-size: 13px;">Sei il capitano. Aggiorna quante persone della tua squadra mangeranno.</p>
                        <form method="POST" class="m-stack">
                            <div class="m-field">
                                <label class="m-label" for="persone_pranzo">Numero persone</label>
                                <input class="m-input m-num" type="number" id="persone_pranzo" name="persone_pranzo" min="0" value="<?= (int)$squadra['persone_pranzo'] ?>" required>
                            </div>
                            <button type="submit" class="m-btn m-btn--primary m-btn--block">Salva</button>
                        </form>
                    </div>
                <?php endif; ?>
            </aside>
        </div>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
