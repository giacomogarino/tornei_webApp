<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("conf/db_config.php");
require_once('components/squadre_torneo.php');
session_start();

$id = isset($_GET['id']) ? $_GET['id'] : null;


$sql = "SELECT id, nome, descrizione, formato, tipo_partita, visibilita, numero_squadre,
               creato_da, stato, min_giocatori_per_squadra, max_giocatori_per_squadra,
               min_squadre, data_chiusura_iscrizioni, codice_privato, sport, luogo
        FROM torneo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$torneo = $result->fetch_assoc();

if(!$torneo)
    header("Location: index.php?msg=errTorneoNonTrovato");


$utente_id = isset($_SESSION['id_utente']) ? $_SESSION['id_utente']: null;

$check = "SELECT id, utente_id FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
$stmt = $conn->prepare($check);
$stmt->bind_param("ii", $id, $utente_id);
$stmt->execute();
$res = $stmt->get_result();
$isFollowing = ($res->num_rows > 0);
$author = $torneo['creato_da'];
$isOrganizzatore = ($author == ($_SESSION['id_utente'] ?? null));

# per seguire o smettere di seguire il torneo
if (isset($_POST['toggle_follow'])) {
    if ($isFollowing) {
        $delete = "DELETE FROM torneo_seguito WHERE torneo_id = ? AND utente_id = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();
        $isFollowing = false;
    } else {
        $insert = "INSERT INTO torneo_seguito (torneo_id, utente_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("ii", $id, $utente_id);
        $stmt->execute();
        $isFollowing = true;
    }
    header("Location: dettagli_torneo.php?id=" . $id);
    exit;
}

// Eliminazione torneo (solo organizzatore)
if (isset($_POST['elimina_torneo']) && $isOrganizzatore) {
    // Le FK con ON DELETE CASCADE gestiscono automaticamente:
    // partita, classifica, squadra (e giocatore_squadra, pranzi via squadra), torneo_seguito
    $del = $conn->prepare("DELETE FROM torneo WHERE id = ? AND creato_da = ?");
    $del->bind_param("ii", $id, $utente_id);
    $del->execute();
    if ($del->affected_rows > 0) {
        header("Location: index.php?msg=torneoEliminato");
    } else {
        header("Location: dettagli_torneo.php?id=" . $id . "&msg=errEliminazione");
    }
    $del->close();
    exit;
}

// Dopo la query del torneo, prima dell'HTML
$sql_squadre = "SELECT id, nome, capitano_id
                FROM squadra
                WHERE torneo_id = ? AND stato = 'approvata'
                ORDER BY nome ASC";
$stmt = $conn->prepare($sql_squadre);
$stmt->bind_param("i", $id);
$stmt->execute();
$squadre = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Dati organizzatore per la sidebar */
$organizzatore = null;
$stmt = $conn->prepare("SELECT nome, cognome FROM utente WHERE id = ?");
$stmt->bind_param("i", $author);
$stmt->execute();
$organizzatore = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once('templates/header_riservato.php');

$stato_label = [
    'aperto' => 'Aperto',
    'in_corso' => 'In corso',
    'completato' => 'Completato',
];
$stato_class = 'm-state-' . htmlspecialchars($torneo['stato']);
$formato_label = [
    'eliminazione_diretta' => 'Eliminazione diretta',
    'girone_unico' => 'Girone unico',
    'gironi_playoff' => 'Gironi + playoff',
];
$tipo_label = [
    'andata' => 'Solo andata',
    'andata_ritorno' => 'Andata e ritorno',
];
?>

<header class="t-hero">
    <div class="m-container">
        <div class="t-breadcrumb">
            <a href="index.php">Home</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <span><?= htmlspecialchars($torneo['nome']) ?></span>
        </div>

        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: var(--m-4); flex-wrap: wrap;">
            <div>
                <div style="display: flex; gap: 8px; margin-bottom: var(--m-3); flex-wrap: wrap;">
                    <span class="t-chip<?= $torneo['stato'] === 'in_corso' ? ' t-chip--live' : '' ?>">
                        <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background: currentColor;"></span>
                        <?= htmlspecialchars($stato_label[$torneo['stato']] ?? $torneo['stato']) ?>
                    </span>
                    <span class="t-chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><?php if ($torneo['visibilita'] === 'privato'): ?><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><?php else: ?><circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/><?php endif; ?></svg>
                        <?= $torneo['visibilita'] === 'privato' ? 'Privato' : 'Pubblico' ?>
                    </span>
                    <?php if (!empty($torneo['sport']) || !empty($torneo['luogo'])): ?>
                        <span class="t-chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M5 7l14 10"/></svg>
                            <?= htmlspecialchars(($torneo['sport'] ?? '') . (($torneo['sport'] && $torneo['luogo']) ? '  ' : '') . ($torneo['luogo'] ?? '')) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($torneo['nome']) ?></h1>
                <?php if (!empty($torneo['descrizione'])): ?>
                    <p class="desc"><?= htmlspecialchars($torneo['descrizione']) ?></p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: var(--m-2);">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="toggle_follow" class="m-btn <?= $isFollowing ? 'm-btn--secondary' : 'm-btn--gold' ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="<?= $isFollowing ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>
                        <?= $isFollowing ? 'Stai seguendo' : 'Segui torneo' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<main class="m-page">
    <div class="m-container">

        <div class="m-tabs">
            <a href="dettagli_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab m-tab--active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Info torneo
            </a>
            <a href="struttura_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                Struttura torneo
            </a>
            <?php if ($torneo['stato'] === 'in_corso'): ?>
                <a href="gestione_pranzi.php?id=<?= (int)$torneo['id'] ?>" class="m-tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18"/><path d="M5 11V8a7 7 0 1 1 14 0v3"/><path d="M5 11l-1 8h16l-1-8"/></svg>
                    Gestione pranzi
                </a>
            <?php endif; ?>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php
            $msg_map = [
                'err' => ['danger', "Errore, riprova pi tardi."],
                'errTorneoChiuso' => ['warn', "Torneo chiuso."],
                'errTorneoPieno' => ['warn', "Torneo pieno."],
                'errGiaInSquadra' => ['warn', "Sei gi in una squadra di questo torneo."],
                'errEliminazione' => ['danger', "Errore durante l'eliminazione del torneo. Riprova."],
            ];
            $msg_key = $_GET['msg'];
            ?>
            <?php if (isset($msg_map[$msg_key])): ?>
                <div class="m-alert m-alert--<?= $msg_map[$msg_key][0] ?> m-mb-5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                    <div><?= $msg_map[$msg_key][1] ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if(isset($_GET['squadra_inviata'])): ?>
            <div class="m-alert m-alert--success m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <div>Richiesta inviata! L'organizzatore valuter la tua iscrizione.</div>
            </div>
        <?php endif; ?>

        <div class="m-grid" style="grid-template-columns: 1fr 360px; gap: var(--m-6);">

            <section>
                <?php if ($isOrganizzatore): ?>
                    <div class="m-alert m-alert--info m-mb-5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>Sei l'organizzatore di questo torneo. Puoi <a href="modifica_torneo.php?id=<?= (int)$torneo['id'] ?>">modificare le impostazioni</a> e gestire le iscrizioni.</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($torneo['descrizione'])): ?>
                    <div class="m-card">
                        <div class="m-card__header"><h3 class="m-card__title">Descrizione</h3></div>
                        <p style="margin: 0;"><?= nl2br(htmlspecialchars($torneo['descrizione'])) ?></p>
                    </div>
                <?php endif; ?>

                <div class="m-card m-mt-4">
                    <div class="m-card__header">
                        <h3 class="m-card__title">Dati torneo</h3>
                        <span class="m-mono m-muted" style="font-size: 12px;">ID #<?= (int)$torneo['id'] ?></span>
                    </div>
                    <dl style="display: grid; grid-template-columns: 200px 1fr; gap: var(--m-3) var(--m-4); font-size: 14px; margin: 0;">
                        <dt class="m-muted">Formato</dt><dd style="margin: 0; font-weight: 500;"><?= htmlspecialchars($formato_label[$torneo['formato']] ?? $torneo['formato']) ?></dd>
                        <dt class="m-muted">Tipo partita</dt><dd style="margin: 0; font-weight: 500;"><?= htmlspecialchars($tipo_label[$torneo['tipo_partita']] ?? $torneo['tipo_partita']) ?></dd>
                        <?php if (!empty($torneo['sport'])): ?>
                            <dt class="m-muted">Sport</dt><dd style="margin: 0; font-weight: 500;"><?= htmlspecialchars($torneo['sport']) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($torneo['luogo'])): ?>
                            <dt class="m-muted">Luogo</dt><dd style="margin: 0; font-weight: 500;"><?= htmlspecialchars($torneo['luogo']) ?></dd>
                        <?php endif; ?>
                        <dt class="m-muted">Visibilit</dt><dd style="margin: 0;"><span class="m-badge m-badge--info"><?= htmlspecialchars($torneo['visibilita']) ?></span></dd>
                        <dt class="m-muted">Stato</dt><dd style="margin: 0;"><span class="m-badge m-badge--dot <?= $stato_class ?>"><?= htmlspecialchars($stato_label[$torneo['stato']] ?? $torneo['stato']) ?></span></dd>
                        <dt class="m-muted">Numero squadre</dt><dd style="margin: 0; font-weight: 500;"><?= (int)$torneo['numero_squadre'] ?> (min <?= (int)$torneo['min_squadre'] ?>)</dd>
                        <dt class="m-muted">Giocatori per squadra</dt><dd style="margin: 0; font-weight: 500;">min <b><?= (int)$torneo['min_giocatori_per_squadra'] ?></b>  max <b><?= (int)$torneo['max_giocatori_per_squadra'] ?></b></dd>
                        <dt class="m-muted">Chiusura iscrizioni</dt><dd style="margin: 0; font-weight: 500;"><?= htmlspecialchars($torneo['data_chiusura_iscrizioni']) ?></dd>
                        <?php if ($torneo['visibilita'] === 'privato' && $torneo['codice_privato']): ?>
                            <dt class="m-muted">Codice privato</dt><dd style="margin: 0;"><span class="m-mono" style="font-weight: 600; letter-spacing: 0.1em;"><?= htmlspecialchars($torneo['codice_privato']) ?></span></dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="m-card m-mt-4">
                    <div class="m-card__header">
                        <h3 class="m-card__title">Squadre iscritte <span class="m-muted" style="font-weight: 400;">(<?= count($squadre) ?> di <?= (int)$torneo['numero_squadre'] ?>)</span></h3>
                        <?php if ($torneo['stato'] == 'aperto' && ($torneo['visibilita'] === 'pubblico' || $torneo['visibilita'] === 'privato' && $author != ($_SESSION["id_utente"] ?? null))): ?>
                            <a href="aggiungi_squadra.php?torneo_id=<?= (int)$torneo['id'] ?>" class="m-btn m-btn--secondary m-btn--sm">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Iscrivi squadra
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php mostra_squadre_approvate($squadre, $utente_id); ?>
                </div>
            </section>

            <aside>
                <div class="m-card" style="background: linear-gradient(180deg, var(--m-primary-50), var(--m-surface)); border-color: var(--m-primary-200);">
                    <h4 class="m-profile-section-label">Azioni rapide</h4>
                    <a href="struttura_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-btn m-btn--primary m-btn--block m-mb-3">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                        Vedi struttura
                    </a>
                    <?php if ($isOrganizzatore): ?>
                        <a href="modifica_torneo.php?id=<?= (int)$torneo['id'] ?>" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            Modifica impostazioni
                        </a>
                        <form method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare il torneo «<?= htmlspecialchars(addslashes($torneo['nome'])) ?>»?\nQuesta azione è irreversibile e rimuoverà tutte le squadre, partite e classifiche associate.');" style="display: contents;">
                            <button type="submit" name="elimina_torneo" class="m-btn m-btn--block m-mb-3" style="background: var(--m-danger, #dc2626); color: #fff; border-color: transparent;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                Elimina torneo
                            </button>
                        </form>
                        <?php if ($torneo['stato'] === 'aperto' && $torneo['visibilita'] === 'privato'): ?>
                            <a href="aggiunta_squadre_manualmente.php?id=<?= (int)$torneo['id'] ?>" class="m-btn m-btn--secondary m-btn--block m-mb-3">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Aggiungi squadra manualmente
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($torneo['stato'] === 'in_corso'): ?>
                        <a href="gestione_pranzi.php?id=<?= (int)$torneo['id'] ?>" class="m-btn m-btn--secondary m-btn--block">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18"/><path d="M5 11V8a7 7 0 1 1 14 0v3"/><path d="M5 11l-1 8h16l-1-8"/></svg>
                            Gestisci pranzi
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($organizzatore): ?>
                    <div class="m-card m-mt-4">
                        <h4 class="m-profile-section-label">Organizzatore</h4>
                        <div style="display: flex; align-items: center; gap: var(--m-3);">
                            <span class="m-avatar m-avatar--lg"><?= strtoupper(mb_substr($organizzatore['nome'], 0, 1) . mb_substr($organizzatore['cognome'], 0, 1)) ?></span>
                            <div>
                                <div style="font-family: var(--m-font-display); font-weight: 600;"><?= htmlspecialchars($organizzatore['nome'] . ' ' . $organizzatore['cognome']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>

    </div>
</main>

<?php require_once('templates/footer.php') ?>
