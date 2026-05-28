<?php
require_once 'php/helpers/session.php';
require_once 'php/helpers/csrf.php';
session_secure_start();
include("conf/db_config.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

//recupera dati dalla sessione --> persistenza tra step
if (!isset($_SESSION['wizard'])) $_SESSION['wizard'] = [];

$step   = intval($_GET['step'] ?? 1);
$errori = [];

//controlla che il metodo sia post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $step = intval($_POST['step_corrente'] ?? 1);
    $azione = $_POST['azione'] ?? 'avanti';

    if($azione === 'indietro'){
        $prev = max(1, $step - 1);
        header("Location: crea_torneo.php?step=$prev");
        exit;
    }

    //step 1
    if($step === 1){
        $_SESSION['wizard']['formato'] = $_POST['formato'] ?? '';
        $_SESSION['wizard']['tipo_partita'] = $_POST['tipo_partita'] ?? '';

        if(empty($_SESSION['wizard']['formato']))
            $errori[] = "Seleziona un formato di torneo.";
        if(empty($_SESSION['wizard']['tipo_partita']))
            $errori[] = "Seleziona il tipo di partita.";
    }

    //step 2
    elseif($step === 2){
        $_SESSION['wizard']['nome'] = trim($_POST['nome'] ?? '');
        $_SESSION['wizard']['descrizione'] = trim($_POST['descrizione'] ?? '');
        $_SESSION['wizard']['visibilita'] = $_POST['visibilita'] ?? 'pubblico';
        $_SESSION['wizard']['data_chiusura'] = $_POST['data_chiusura'] ?? '';
        $_SESSION['wizard']['sport'] = $_POST['sport'] ?? '';
        $_SESSION['wizard']['luogo'] = trim($_POST['luogo'] ?? '');
        $_SESSION['wizard']['pranzo'] = isset($_POST['pranzo']) ? 1 : 0;

        // Gestione upload locandina
        if(isset($_FILES['locandina']) && $_FILES['locandina']['error'] === UPLOAD_ERR_OK){
            $file = $_FILES['locandina'];
            $tipi_ammessi = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            if(!in_array($file['type'], $tipi_ammessi)){
                $errori[] = "Formato locandina non valido. Usa JPG, PNG, WebP o GIF.";
            } elseif($file['size'] > $max_size){
                $errori[] = "La locandina supera i 5 MB consentiti.";
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nome_file = 'locandina_' . uniqid() . '.' . $ext;
                $cartella  = 'uploads/locandine/';
                if(!is_dir($cartella)) mkdir($cartella, 0755, true);
                $percorso  = $cartella . $nome_file;

                if(move_uploaded_file($file['tmp_name'], $percorso)){
                    // Rimuovi eventuale locandina precedente dalla sessione
                    if(!empty($_SESSION['wizard']['percorso']) && file_exists($_SESSION['wizard']['percorso'])){
                        @unlink($_SESSION['wizard']['percorso']);
                    }
                    $_SESSION['wizard']['nome_file'] = $nome_file;
                    $_SESSION['wizard']['percorso']  = $percorso;
                } else {
                    $errori[] = "Errore durante il salvataggio della locandina.";
                }
            }
        }

        if(empty($_SESSION['wizard']['nome']))
            $errori[] = "Il nome del torneo  obbligatorio.";
        if(empty($_SESSION['wizard']['data_chiusura']))
            $errori[] = "La data di chiusura iscrizioni  obbligatoria.";
        elseif(strtotime($_SESSION['wizard']['data_chiusura']) <= time())
            $errori[] = "La data di chiusura deve essere nel futuro.";
        if(empty($_SESSION['wizard']['sport']))
            $errori[] = "Seleziona uno sport.";
        if(empty($_SESSION['wizard']['luogo']))
            $errori[] = "Il luogo del torneo  obbligatorio.";
    }

    elseif($step === 3){
        $_SESSION['wizard']['numero_squadre'] = intval($_POST['numero_squadre'] ?? 0);
        $_SESSION['wizard']['min_squadre'] = intval($_POST['min_squadre'] ?? 0);
        $_SESSION['wizard']['min_giocatori'] = intval($_POST['min_giocatori'] ?? 0);
        $_SESSION['wizard']['max_giocatori'] = intval($_POST['max_giocatori'] ?? 0);

        $ns = $_SESSION['wizard']['numero_squadre'];
        $ms = $_SESSION['wizard']['min_squadre'];
        $mg = $_SESSION['wizard']['min_giocatori'];
        $xg = $_SESSION['wizard']['max_giocatori'];

        if($ns < 2) $errori[] = "Il numero massimo di squadre deve essere almeno 2.";
        if($ms < 2) $errori[] = "Il numero minimo di squadre deve essere almeno 2.";
        if($ms > $ns) $errori[] = "Il numero minimo non pu superare il massimo.";
        if($mg < 1) $errori[] = "Inserisci il numero minimo di giocatori per squadra.";
        if($xg < $mg) $errori[] = "Il numero massimo di giocatori deve essere  al minimo.";
    }

    elseif($step === 4 && $azione === 'crea'){
        $w = $_SESSION['wizard'];

        $codice_privato = null;
        if(($w['visibilita'] ?? 'pubblico') === 'privato')
            $codice_privato = strtoupper(bin2hex(random_bytes(4)));

        $stmt = $conn->prepare("
            INSERT INTO torneo
            (nome, descrizione, formato, tipo_partita, visibilita,
            numero_squadre, min_squadre,
            min_giocatori_per_squadra, max_giocatori_per_squadra,
            data_chiusura_iscrizioni, codice_privato, creato_da, stato,
            sport, luogo, nome_file, percorso, pranzo)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aperto', ?, ?, ?, ?,?)
        ");

        $descrizione = $w['descrizione'] ?: null;
        $nome_file   = $w['nome_file'] ?? null;
        $percorso    = $w['percorso'] ?? null;
        $pranzo = (int)($w['pranzo'] ?? 0);
        $stmt->bind_param(
            "sssssiiiississssi",
            $w['nome'],
            $descrizione,
            $w['formato'],
            $w['tipo_partita'],
            $w['visibilita'],
            $w['numero_squadre'],
            $w['min_squadre'],
            $w['min_giocatori'],
            $w['max_giocatori'],
            $w['data_chiusura'],
            $codice_privato,
            $_SESSION['id_utente'],
            $w['sport'],
            $w['luogo'],
            $nome_file,
            $percorso,
            $pranzo
        );

        $stmt->execute();
        $nuovo_id = $conn->insert_id;

        header("Location: index.php?id=$nuovo_id&nuovo=1");
        exit;
    }

    if(empty($errori)){
        $next = min(4, $step + 1);
        header("Location: crea_torneo.php?step=$next");
        exit;
    }
}

$fmt_label = [
    'eliminazione_diretta' => 'Eliminazione Diretta',
    'girone_unico'         => 'Girone all\'Italiana',
    'gironi_playoff'       => 'Gironi + Playoff',
];
$tipo_label = [
    'andata'         => 'Solo Andata',
    'andata_ritorno' => 'Andata e Ritorno',
];
$sport_label = [
    'calcio'       => 'Calcio',
    'beachvolley'  => 'Beach Volley',
    'padel'        => 'Padel',
    'tennis'        => 'Tennis'
];

$w = $_SESSION['wizard'];

require_once('templates/header_riservato.php');

/* Helper per le classi degli step */
function step_class($step_n, $cur){
    if ($step_n < $cur)  return 'm-step m-step--done';
    if ($step_n === $cur) return 'm-step m-step--current';
    return 'm-step';
}
?>

<main class="m-page">
    <div class="m-container" style="max-width: 880px;">

        <div style="margin-bottom: var(--m-5); font-size: 13px;">
            <a href="index.php" style="color: var(--m-text-mute);"> Torna alla home</a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Crea un nuovo torneo</h1>
                <div class="m-page-head__sub">Bastano 4 passaggi rapidi. Potrai sempre modificare le impostazioni dopo.</div>
            </div>
        </div>

        <div class="m-stepper">
            <div class="<?= step_class(1, $step) ?>">
                <span class="m-step__num">
                    <?php if ($step > 1): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>1<?php endif; ?>
                </span>
                <div class="m-step__text"><span class="m-step__label">Step 1</span><span class="m-step__title">Formato</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(2, $step) ?>">
                <span class="m-step__num">
                    <?php if ($step > 2): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>2<?php endif; ?>
                </span>
                <div class="m-step__text"><span class="m-step__label">Step 2</span><span class="m-step__title">Dettagli</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(3, $step) ?>">
                <span class="m-step__num">
                    <?php if ($step > 3): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>3<?php endif; ?>
                </span>
                <div class="m-step__text"><span class="m-step__label">Step 3</span><span class="m-step__title">Squadre e giocatori</span></div>
            </div>
            <svg class="m-stepper__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
            <div class="<?= step_class(4, $step) ?>">
                <span class="m-step__num">4</span>
                <div class="m-step__text"><span class="m-step__label">Step 4</span><span class="m-step__title">Riepilogo</span></div>
            </div>
        </div>

        <?php if(!empty($errori)): ?>
            <div class="m-alert m-alert--danger m-mb-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <ul style="margin: 0; padding-left: 18px;">
                        <?php foreach($errori as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="crea_torneo.php" class="m-card" style="padding: var(--m-6);" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="step_corrente" value="<?= $step ?>">

            <?php if($step===1): ?>
                <h3 style="margin: 0 0 var(--m-2);">Scegli il formato del torneo</h3>
                <p class="m-muted m-mb-5">Definisce come si svolgeranno le partite tra le squadre.</p>

                <div class="m-field">
                    <label class="m-label">Formato</label>
                    <div class="m-tile-group">
                        <label class="m-tile">
                            <input type="radio" name="formato" value="eliminazione_diretta" <?= (($w['formato'] ?? '')=='eliminazione_diretta') ? 'checked' : '' ?>>
                            <div class="m-tile__inner">
                                <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 7 4 7 20 3 20"/><polyline points="11 4 15 4 15 14 11 14"/><polyline points="19 4 21 4 21 10 19 10"/></svg>
                                </span>
                                <span class="m-tile__title">Eliminazione diretta</span>
                                <span class="m-tile__desc">Chi perde  fuori. Il vincitore avanza.</span>
                            </div>
                        </label>
                        <label class="m-tile">
                            <input type="radio" name="formato" value="girone_unico" <?= (($w['formato'] ?? '')=='girone_unico') ? 'checked' : '' ?>>
                            <div class="m-tile__inner">
                                <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
                                </span>
                                <span class="m-tile__title">Girone unico</span>
                                <span class="m-tile__desc">Tutti contro tutti, classifica finale.</span>
                            </div>
                        </label>
                        <label class="m-tile">
                            <input type="radio" name="formato" value="gironi_playoff" <?= (($w['formato'] ?? '')=='gironi_playoff') ? 'checked' : '' ?>>
                            <div class="m-tile__inner">
                                <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16M15 4v16"/></svg>
                                </span>
                                <span class="m-tile__title">Gironi + playoff</span>
                                <span class="m-tile__desc">Fase a gironi seguita da fase ad eliminazione.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="m-field m-mt-5">
                    <label class="m-label">Tipo partita</label>
                    <div class="m-tile-group">
                        <label class="m-tile">
                            <input type="radio" name="tipo_partita" value="andata" <?= (($w['tipo_partita'] ?? 'andata')=='andata') ? 'checked' : '' ?>>
                            <div class="m-tile__inner">
                                <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                                <span class="m-tile__title">Solo andata</span>
                                <span class="m-tile__desc">Una sola partita per match.</span>
                            </div>
                        </label>
                        <label class="m-tile">
                            <input type="radio" name="tipo_partita" value="andata_ritorno" <?= (($w['tipo_partita'] ?? '')=='andata_ritorno') ? 'checked' : '' ?>>
                            <div class="m-tile__inner">
                                <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                </span>
                                <span class="m-tile__title">Andata e ritorno</span>
                                <span class="m-tile__desc">Due partite per match con punteggio aggregato.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="m-row-between" style="margin-top: var(--m-8); padding-top: var(--m-5); border-top: 1px solid var(--m-border);">
                    <a href="index.php" class="m-btn m-btn--ghost">Annulla</a>
                    <button type="submit" name="azione" value="avanti" class="m-btn m-btn--primary">
                        Avanti
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>

            <?php elseif($step===2): ?>
                <h3 style="margin: 0 0 var(--m-2);">Dettagli del torneo</h3>
                <p class="m-muted m-mb-5">Dai un nome al torneo, scegli sport, luogo e quando chiudere le iscrizioni.</p>

                <div class="m-stack">
                    <div class="m-field">
                        <label class="m-label" for="nome">Nome torneo <span style="color: var(--m-danger-500);">*</span></label>
                        <input class="m-input" type="text" id="nome" name="nome" placeholder="Es. Coppa di Cuneo 2026" value="<?= htmlspecialchars($w['nome'] ?? '') ?>" required>
                    </div>

                    <div class="m-field">
                        <label class="m-label" for="descrizione">Descrizione <span class="m-muted" style="font-weight: 400;">(facoltativa)</span></label>
                        <textarea class="m-textarea" id="descrizione" name="descrizione" placeholder="Descrivi brevemente il torneo, le regole speciali, ecc."><?= htmlspecialchars($w['descrizione'] ?? '') ?></textarea>
                    </div>

                    <div class="m-field">
                        <label class="m-label" for="locandina">Locandina <span class="m-muted" style="font-weight: 400;">(facoltativa &mdash; JPG, PNG, WebP, max 5 MB)</span></label>
                        <input class="m-input" type="file" id="locandina" name="locandina" accept="image/jpeg,image/png,image/webp,image/gif">
                        <?php if(!empty($w['percorso'])): ?>
                            <div style="margin-top: var(--m-2); display: flex; align-items: center; gap: var(--m-3);">
                                <img src="<?= htmlspecialchars($w['percorso']) ?>" alt="Anteprima locandina" style="max-height: 80px; border-radius: 6px; border: 1px solid var(--m-border);">
                                <span class="m-muted" style="font-size: 12px;">Locandina caricata. Carica un nuovo file per sostituirla.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                        <div class="m-field">
                            <label class="m-label" for="sport">Sport <span style="color: var(--m-danger-500);">*</span></label>
                            <select class="m-select" id="sport" name="sport" required>
                                <option value="">-- Seleziona sport --</option>
                                <option value="calcio" <?= (($w['sport'] ?? '')=='calcio') ? 'selected' : '' ?>>Calcio</option>
                                <option value="beachvolley" <?= (($w['sport'] ?? '')=='beachvolley') ? 'selected' : '' ?>>Beach Volley</option>
                                <option value="padel" <?= (($w['sport'] ?? '')=='padel') ? 'selected' : '' ?>>Padel</option>
                                <option value="tennis" <?= (($w['sport'] ?? '')=='tennis') ? 'selected' : '' ?>>Tennis</option>
                            </select>
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="luogo">Luogo <span style="color: var(--m-danger-500);">*</span></label>
                            <input class="m-input" type="text" id="luogo" name="luogo" placeholder="Es. Cuneo, campo sportivo via Roma" value="<?= htmlspecialchars($w['luogo'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="m-field">
                        <label class="m-label">Visibilit</label>
                        <div class="m-tile-group">
                            <label class="m-tile">
                                <input type="radio" name="visibilita" value="pubblico" <?= (($w['visibilita'] ?? 'pubblico')=='pubblico') ? 'checked' : '' ?>>
                                <div class="m-tile__inner">
                                    <span class="m-tile__icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/></svg>
                                    </span>
                                    <span class="m-tile__title">Pubblico</span>
                                    <span class="m-tile__desc">Visibile a tutti, chiunque pu iscriversi</span>
                                </div>
                            </label>
                            <label class="m-tile">
                                <input type="radio" name="visibilita" value="privato" <?= (($w['visibilita'] ?? '')=='privato') ? 'checked' : '' ?>>
                                <div class="m-tile__inner">
                                    <span class="m-tile__icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    </span>
                                    <span class="m-tile__title">Privato</span>
                                    <span class="m-tile__desc">Solo chi ha il codice pu iscriversi</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="m-field">
                        <label class="m-label">Pranzo</label>
                        <div class="m-tile-group">
                            <label class="m-tile">
                                <input type="radio" name="pranzo" value="1" <?= (($w['pranzo'] ?? 0) == 1) ? 'checked' : '' ?>>
                                <div class="m-tile__inner">
                                    <span class="m-tile__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>                                    </span>
                                    <span class="m-tile__title">Sì</span>
                                    <span class="m-tile__desc">Il torneo prevede un pranzo organizzato</span>
                                </div>
                            </label>
                            <label class="m-tile">
                                <input type="radio" name="pranzo" value="0" <?= (($w['pranzo'] ?? 0) == 0) ? 'checked' : '' ?>>
                                <div class="m-tile__inner">
                                    <span class="m-tile__icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </span>
                                    <span class="m-tile__title">No</span>
                                    <span class="m-tile__desc">Nessun pranzo previsto</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="m-field">
                        <label class="m-label" for="data_chiusura">Data e ora di chiusura iscrizioni <span style="color: var(--m-danger-500);">*</span></label>
                        <input class="m-input" type="datetime-local" id="data_chiusura" name="data_chiusura" value="<?= htmlspecialchars($w['data_chiusura'] ?? '') ?>" required>
                        <span class="m-muted" style="font-size: 12px;">Dopo questa data nessuno potr pi iscriversi.</span>
                    </div>
                </div>

                <div class="m-row-between" style="margin-top: var(--m-8); padding-top: var(--m-5); border-top: 1px solid var(--m-border);">
                    <button type="submit" name="azione" value="indietro" class="m-btn m-btn--ghost">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Indietro
                    </button>
                    <div class="m-row">
                        <a href="index.php" class="m-btn m-btn--ghost">Annulla</a>
                        <button type="submit" name="azione" value="avanti" class="m-btn m-btn--primary">
                            Avanti
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>

            <?php elseif($step===3): ?>
                <h3 style="margin: 0 0 var(--m-2);">Squadre e giocatori</h3>
                <p class="m-muted m-mb-5">Definisci i limiti minimi e massimi per le squadre e i giocatori.</p>

                <div class="m-stack">
                    <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                        <div class="m-field">
                            <label class="m-label" for="numero_squadre">Numero massimo squadre</label>
                            <input class="m-input m-num" type="number" min="2" id="numero_squadre" name="numero_squadre" value="<?= $w['numero_squadre'] ?? 8 ?>">
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="min_squadre">Numero minimo squadre</label>
                            <input class="m-input m-num" type="number" min="2" id="min_squadre" name="min_squadre" value="<?= $w['min_squadre'] ?? 4 ?>">
                        </div>
                    </div>

                    <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                        <div class="m-field">
                            <label class="m-label" for="min_giocatori">Min giocatori per squadra</label>
                            <input class="m-input m-num" type="number" min="1" id="min_giocatori" name="min_giocatori" value="<?= $w['min_giocatori'] ?? 5 ?>">
                        </div>
                        <div class="m-field">
                            <label class="m-label" for="max_giocatori">Max giocatori per squadra</label>
                            <input class="m-input m-num" type="number" min="1" id="max_giocatori" name="max_giocatori" value="<?= $w['max_giocatori'] ?? 10 ?>">
                        </div>
                    </div>
                </div>

                <div class="m-row-between" style="margin-top: var(--m-8); padding-top: var(--m-5); border-top: 1px solid var(--m-border);">
                    <button type="submit" name="azione" value="indietro" class="m-btn m-btn--ghost">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Indietro
                    </button>
                    <button type="submit" name="azione" value="avanti" class="m-btn m-btn--primary">
                        Avanti
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>

            <?php elseif($step===4): ?>
                <h3 style="margin: 0 0 var(--m-2);">Riepilogo</h3>
                <p class="m-muted m-mb-5">Controlla i dati e conferma per creare il torneo.</p>

                <dl style="display: grid; grid-template-columns: 200px 1fr; gap: var(--m-3) var(--m-4); font-size: 14px; margin: 0;">
                    <dt class="m-muted">Nome</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($w['nome']) ?></dd>
                    <dt class="m-muted">Sport</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($sport_label[$w['sport']] ?? $w['sport']) ?></dd>
                    <dt class="m-muted">Luogo</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($w['luogo']) ?></dd>
                    <dt class="m-muted">Formato</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($fmt_label[$w['formato']] ?? '') ?></dd>
                    <dt class="m-muted">Tipo partita</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($tipo_label[$w['tipo_partita']] ?? '') ?></dd>
                    <dt class="m-muted">Visibilit</dt><dd style="margin:0;"><span class="m-badge m-badge--info"><?= htmlspecialchars($w['visibilita']) ?></span></dd>
                    <dt class="m-muted">Pranzo</dt><dd style="margin:0;"><span class="m-badge <?= ($w['pranzo'] ?? 0) ? 'm-badge--success' : 'm-badge--neutral' ?>"><?= ($w['pranzo'] ?? 0) ? 'Sì' : 'No' ?></span></dd>
                    <dt class="m-muted">Chiusura iscrizioni</dt><dd style="margin:0; font-weight:500;"><?= htmlspecialchars($w['data_chiusura']) ?></dd>
                    <dt class="m-muted">Squadre</dt><dd style="margin:0; font-weight:500;">da <b><?= (int)$w['min_squadre'] ?></b> a <b><?= (int)$w['numero_squadre'] ?></b></dd>
                    <dt class="m-muted">Giocatori per squadra</dt><dd style="margin:0; font-weight:500;">da <b><?= (int)$w['min_giocatori'] ?></b> a <b><?= (int)$w['max_giocatori'] ?></b></dd>
                    <?php if(!empty($w['descrizione'])): ?>
                        <dt class="m-muted">Descrizione</dt><dd style="margin:0;"><?= nl2br(htmlspecialchars($w['descrizione'])) ?></dd>
                    <?php endif; ?>
                    <?php if(!empty($w['percorso'])): ?>
                        <dt class="m-muted">Locandina</dt>
                        <dd style="margin:0;">
                            <img src="<?= htmlspecialchars($w['percorso']) ?>" alt="Locandina torneo" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--m-border);">
                        </dd>
                    <?php endif; ?>
                </dl>

                <div class="m-row-between" style="margin-top: var(--m-8); padding-top: var(--m-5); border-top: 1px solid var(--m-border);">
                    <button type="submit" name="azione" value="indietro" class="m-btn m-btn--ghost">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Indietro
                    </button>
                    <button type="submit" name="azione" value="crea" class="m-btn m-btn--primary m-btn--lg">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Crea torneo
                    </button>
                </div>
            <?php endif; ?>
        </form>

    </div>
</main>

<?php require_once('templates/footer.php') ?>
