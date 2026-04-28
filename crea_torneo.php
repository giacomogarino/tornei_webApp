<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("conf/db_config.php");

// Recupera dati dalla sessione (persistenza tra step)
if (!isset($_SESSION['wizard'])) $_SESSION['wizard'] = [];

$step   = intval($_GET['step'] ?? 1);
$errori = [];

// ── Gestione POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lo step viene letto dal campo hidden del form, non da GET
    // (così non ci sono disallineamenti)
    $step   = intval($_POST['step_corrente'] ?? 1);
    $azione = $_POST['azione'] ?? 'avanti';

    // ── Pulsante Indietro: nessuna validazione, torna allo step precedente ──
    if ($azione === 'indietro') {
        $prev = max(1, $step - 1);
        header("Location: crea_torneo.php?step=$prev");
        exit;
    }

    // ── Salva e valida i dati dello step corrente ───────────────────────────
    if ($step === 1) {
        $_SESSION['wizard']['formato']      = $_POST['formato']      ?? '';
        $_SESSION['wizard']['tipo_partita'] = $_POST['tipo_partita'] ?? '';

        if (empty($_SESSION['wizard']['formato']))
            $errori[] = "Seleziona un formato di torneo.";
        if (empty($_SESSION['wizard']['tipo_partita']))
            $errori[] = "Seleziona il tipo di partita.";
    }

    elseif ($step === 2) {
        $_SESSION['wizard']['nome']          = trim($_POST['nome']          ?? '');
        $_SESSION['wizard']['descrizione']   = trim($_POST['descrizione']   ?? '');
        $_SESSION['wizard']['visibilita']    = $_POST['visibilita']         ?? 'pubblico';
        $_SESSION['wizard']['data_chiusura'] = $_POST['data_chiusura']      ?? '';

        if (empty($_SESSION['wizard']['nome']))
            $errori[] = "Il nome del torneo è obbligatorio.";
        if (empty($_SESSION['wizard']['data_chiusura']))
            $errori[] = "La data di chiusura iscrizioni è obbligatoria.";
        elseif (strtotime($_SESSION['wizard']['data_chiusura']) <= time())
            $errori[] = "La data di chiusura deve essere nel futuro.";
    }

    elseif ($step === 3) {
        $_SESSION['wizard']['numero_squadre'] = intval($_POST['numero_squadre'] ?? 0);
        $_SESSION['wizard']['min_squadre']    = intval($_POST['min_squadre']    ?? 0);
        $_SESSION['wizard']['min_giocatori']  = intval($_POST['min_giocatori']  ?? 0);
        $_SESSION['wizard']['max_giocatori']  = intval($_POST['max_giocatori']  ?? 0);

        $ns = $_SESSION['wizard']['numero_squadre'];
        $ms = $_SESSION['wizard']['min_squadre'];
        $mg = $_SESSION['wizard']['min_giocatori'];
        $xg = $_SESSION['wizard']['max_giocatori'];

        if ($ns < 2)   $errori[] = "Il numero massimo di squadre deve essere almeno 2.";
        if ($ms < 2)   $errori[] = "Il numero minimo di squadre deve essere almeno 2.";
        if ($ms > $ns) $errori[] = "Il numero minimo non può superare il massimo.";
        if ($mg < 1)   $errori[] = "Inserisci il numero minimo di giocatori per squadra.";
        if ($xg < $mg) $errori[] = "Il numero massimo di giocatori deve essere ≥ al minimo.";
    }

    elseif ($step === 4 && $azione === 'crea') {
        // ── Salvataggio finale ──────────────────────────────────────────────
        $w = $_SESSION['wizard'];

        $codice_privato = null;
        if (($w['visibilita'] ?? 'pubblico') === 'privato')
            $codice_privato = strtoupper(bin2hex(random_bytes(4)));

        $stmt = $conn->prepare("
            INSERT INTO torneo
            (nome, descrizione, formato, tipo_partita, visibilita,
            numero_squadre, min_squadre,
            min_giocatori_per_squadra, max_giocatori_per_squadra,
            data_chiusura_iscrizioni, codice_privato, creato_da, stato)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aperto')
        ");

        $descrizione = $w['descrizione'] ?: null;
        $stmt->bind_param(
            "sssssiiisssi",
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
            $_SESSION['id_utente']
        );

        $stmt->execute();
        $nuovo_id = $conn->insert_id;

        header("Location: index.php?id=$nuovo_id&nuovo=1");
        exit;
    }

    // ── Se nessun errore avanza allo step successivo ────────────────────────
    if (empty($errori)) {
        $next = min(4, $step + 1);
        header("Location: crea_torneo.php?step=$next");
        exit;
    }
    // altrimenti ricade nel rendering sotto con $errori popolato
}

// ── Label per il riepilogo ────────────────────────────────────────────────────
$fmt_label = [
    'eliminazione_diretta' => '⚡ Eliminazione Diretta',
    'girone_unico'         => '🔄 Girone all\'Italiana',
    'gironi_playoff'       => '🏆 Gironi + Playoff',
];
$tipo_label = [
    'andata'         => '➡️ Solo Andata',
    'andata_ritorno' => '↔️ Andata e Ritorno',
];

$w = $_SESSION['wizard'];

// ── Include header DOPO tutta la logica di redirect ──────────────────────────
require_once('templates/header_riservato.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Torneo – Step <?= $step ?>/4</title>
    <style>
        body        { font-family: sans-serif; max-width: 560px; margin: 40px auto; padding: 0 16px; }
        h1          { margin-bottom: 4px; }

        /* Barra step */
        .step-bar   { display: flex; margin-bottom: 28px; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; }
        .step-bar span {
            flex: 1; text-align: center; padding: 9px 4px;
            font-size: 13px; background: #f5f5f5; color: #888;
            border-right: 1px solid #ccc;
        }
        .step-bar span:last-child { border-right: none; }
        .step-bar span.attivo  { background: #1a1a1a; color: #fff; font-weight: bold; }
        .step-bar span.fatto   { background: #d4edda; color: #155724; }

        /* Fieldset */
        fieldset    { border: 1px solid #ccc; border-radius: 6px; padding: 20px; margin-bottom: 16px; }
        legend      { font-weight: bold; padding: 0 8px; font-size: 15px; }

        /* Campi */
        label       { display: block; margin-bottom: 14px; font-size: 14px; }
        label > span { display: block; font-weight: 600; margin-bottom: 5px; }
        input[type=text],
        input[type=number],
        input[type=datetime-local],
        textarea,
        select {
            width: 100%; padding: 8px 10px; font-size: 14px;
            border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        textarea    { resize: vertical; min-height: 80px; }
        small       { display: block; margin-top: 4px; color: #888; font-size: 12px; }

        /* Radio cards */
        .radio-group label {
            display: flex; align-items: flex-start; gap: 12px;
            border: 2px solid #ddd; border-radius: 6px;
            padding: 12px 14px; margin-bottom: 8px; cursor: pointer;
            font-weight: normal;
        }
        .radio-group label:hover  { border-color: #999; }
        .radio-group label.scelta { border-color: #1a1a1a; background: #f7f7f7; }
        .radio-group input[type=radio] { margin-top: 3px; flex-shrink: 0; }
        .radio-testo strong { display: block; margin-bottom: 2px; }
        .radio-testo small  { color: #666; }

        /* Griglia 2 colonne */
        .griglia2   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* Errori */
        .errori {
            background: #fff3f3; border: 1px solid #f5c6cb;
            border-radius: 6px; padding: 12px 16px; margin-bottom: 18px;
            color: #721c24; font-size: 14px;
        }
        .errori ul  { margin: 6px 0 0 18px; }

        /* Riepilogo */
        .riga-riepilogo {
            display: flex; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px solid #eee; font-size: 14px;
        }
        .riga-riepilogo:last-child { border-bottom: none; }
        .etichetta  { color: #555; }
        .valore     { font-weight: 600; text-align: right; max-width: 60%; }

        /* Bottoni navigazione */
        .bottoni    { display: flex; justify-content: space-between; gap: 10px; margin-top: 6px; }
        button      {
            padding: 10px 26px; font-size: 14px; font-weight: 600;
            border-radius: 5px; cursor: pointer; border: 2px solid #1a1a1a;
        }
        .btn-avanti   { background: #1a1a1a; color: #fff; }
        .btn-avanti:hover { background: #333; }
        .btn-indietro { background: #fff; color: #1a1a1a; }
        .btn-indietro:hover { background: #f0f0f0; }
        .btn-crea     { background: #155724; color: #fff; border-color: #155724; }
        .btn-crea:hover { background: #1e7e34; }
    </style>
</head>
<body>

<h1>Crea Torneo</h1>
<p style="margin-bottom:20px"><a href="index.php">← Torna alla home</a></p>

<!-- Barra step -->
<div class="step-bar">
    <?php
    $nomi = ['1. Formato', '2. Dettagli', '3. Squadre', '4. Riepilogo'];
    for ($i = 1; $i <= 4; $i++):
        $cls = ($i === $step) ? 'attivo' : (($i < $step) ? 'fatto' : '');
    ?>
        <span class="<?= $cls ?>"><?= $nomi[$i-1] ?></span>
    <?php endfor; ?>
</div>

<?php if (!empty($errori)): ?>
<div class="errori">
    <strong>Correggi i seguenti errori:</strong>
    <ul>
        <?php foreach ($errori as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php
// Funzione helper: aggiunge class="scelta" se il radio è selezionato
function selezionato(string $campo, string $valore): string {
    global $w;
    return (($w[$campo] ?? '') === $valore) ? ' scelta' : '';
}
?>

<form method="POST" action="crea_torneo.php">
    <!-- Step corrente passato come hidden così il POST sa da dove viene -->
    <input type="hidden" name="step_corrente" value="<?= $step ?>">

    <?php if ($step === 1): ?>
    <!-- ══ STEP 1: Formato ══════════════════════════════════ -->
    <fieldset>
        <legend>Formato del torneo</legend>

        <div class="radio-group">
            <label class="<?= selezionato('formato','eliminazione_diretta') ?>">
                <input type="radio" name="formato" value="eliminazione_diretta"
                    <?= (($w['formato'] ?? '') === 'eliminazione_diretta') ? 'checked' : '' ?>>
                <div class="radio-testo">
                    <strong>⚡ Eliminazione Diretta</strong>
                    <small>Chi perde è eliminato. Veloce e ad alto rischio.</small>
                </div>
            </label>
            <label class="<?= selezionato('formato','girone_unico') ?>">
                <input type="radio" name="formato" value="girone_unico"
                    <?= (($w['formato'] ?? '') === 'girone_unico') ? 'checked' : '' ?>>
                <div class="radio-testo">
                    <strong>🔄 Girone all'Italiana</strong>
                    <small>Tutti contro tutti. La classifica decide il vincitore.</small>
                </div>
            </label>
            <label class="<?= selezionato('formato','gironi_playoff') ?>">
                <input type="radio" name="formato" value="gironi_playoff"
                    <?= (($w['formato'] ?? '') === 'gironi_playoff') ? 'checked' : '' ?>>
                <div class="radio-testo">
                    <strong>🏆 Gironi + Playoff</strong>
                    <small>Fase a gironi seguita da eliminazione diretta.</small>
                </div>
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tipo di partita</legend>
        <div class="radio-group">
            <label class="<?= selezionato('tipo_partita','andata') ?>">
                <input type="radio" name="tipo_partita" value="andata"
                    <?= (($w['tipo_partita'] ?? 'andata') === 'andata') ? 'checked' : '' ?>>
                <div class="radio-testo">
                    <strong>➡️ Solo Andata</strong>
                    <small>Una partita per ogni scontro diretto.</small>
                </div>
            </label>
            <label class="<?= selezionato('tipo_partita','andata_ritorno') ?>">
                <input type="radio" name="tipo_partita" value="andata_ritorno"
                    <?= (($w['tipo_partita'] ?? '') === 'andata_ritorno') ? 'checked' : '' ?>>
                <div class="radio-testo">
                    <strong>↔️ Andata e Ritorno</strong>
                    <small>Due partite per ogni scontro diretto.</small>
                </div>
            </label>
        </div>
    </fieldset>

    <div class="bottoni">
        <a href="index.php"><button type="button" class="btn-indietro">Annulla</button></a>
        <button type="submit" name="azione" value="avanti" class="btn-avanti">Avanti →</button>
    </div>


    <?php elseif ($step === 2): ?>
    <!-- ══ STEP 2: Dettagli ══════════════════════════════════ -->
    <fieldset>
        <legend>Dettagli del torneo</legend>

        <label>
            <span>Nome del torneo *</span>
            <input type="text" name="nome" maxlength="150" required
                   value="<?= htmlspecialchars($w['nome'] ?? '') ?>"
                   placeholder="es. Champions d'Estate 2025">
        </label>

        <label>
            <span>Descrizione <small style="display:inline;color:#888">(facoltativa, max 255 caratteri)</small></span>
            <textarea name="descrizione" maxlength="255"
                      placeholder="Regole speciali, luogo, info utili..."><?= htmlspecialchars($w['descrizione'] ?? '') ?></textarea>
        </label>

        <label>
            <span>Visibilità</span>
            <select name="visibilita">
                <option value="pubblico"  <?= (($w['visibilita'] ?? 'pubblico') === 'pubblico') ? 'selected' : '' ?>>🌐 Pubblico – visibile a tutti</option>
                <option value="privato"   <?= (($w['visibilita'] ?? '')          === 'privato')  ? 'selected' : '' ?>>🔒 Privato – solo con codice univoco</option>
            </select>
        </label>

        <label>
            <span>Data chiusura iscrizioni *</span>
            <input type="datetime-local" name="data_chiusura" required
                   value="<?= htmlspecialchars($w['data_chiusura'] ?? '') ?>">
        </label>
    </fieldset>

    <div class="bottoni">
        <button type="submit" name="azione" value="indietro" class="btn-indietro" formnovalidate>← Indietro</button>
        <button type="submit" name="azione" value="avanti"   class="btn-avanti">Avanti →</button>
    </div>


    <?php elseif ($step === 3): ?>
    <!-- ══ STEP 3: Squadre ══════════════════════════════════ -->
    <fieldset>
        <legend>Numero di squadre</legend>
        <div class="griglia2">
            <label>
                <span>Max squadre *</span>
                <input type="number" name="numero_squadre" min="2" max="256" required
                       value="<?= htmlspecialchars($w['numero_squadre'] ?? '8') ?>">
            </label>
            <label>
                <span>Min squadre per avviare *</span>
                <input type="number" name="min_squadre" min="2" max="256" required
                       value="<?= htmlspecialchars($w['min_squadre'] ?? '4') ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Giocatori per squadra</legend>
        <div class="griglia2">
            <label>
                <span>Min giocatori *</span>
                <input type="number" name="min_giocatori" min="1" max="50" required
                       value="<?= htmlspecialchars($w['min_giocatori'] ?? '5') ?>">
            </label>
            <label>
                <span>Max giocatori *</span>
                <input type="number" name="max_giocatori" min="1" max="50" required
                       value="<?= htmlspecialchars($w['max_giocatori'] ?? '10') ?>">
                <small>Include i giocatori di panchina</small>
            </label>
        </div>
    </fieldset>

    <div class="bottoni">
        <button type="submit" name="azione" value="indietro" class="btn-indietro" formnovalidate>← Indietro</button>
        <button type="submit" name="azione" value="avanti"   class="btn-avanti">Avanti →</button>
    </div>


    <?php elseif ($step === 4): ?>
    <!-- ══ STEP 4: Riepilogo ══════════════════════════════════ -->
    <fieldset>
        <legend>Controlla i dati prima di creare il torneo</legend>

        <?php
        $dtFmt = !empty($w['data_chiusura'])
            ? date('d/m/Y H:i', strtotime($w['data_chiusura']))
            : '–';

        $righe = [
            'Nome'                  => htmlspecialchars($w['nome']        ?? '–'),
            'Formato'               => $fmt_label[$w['formato']           ?? ''] ?? '–',
            'Tipo partita'          => $tipo_label[$w['tipo_partita']     ?? ''] ?? '–',
            'Visibilità'            => ucfirst($w['visibilita']           ?? '–'),
            'Chiusura iscrizioni'   => $dtFmt,
            'Squadre (min / max)'   => ($w['min_squadre']   ?? '–') . ' / ' . ($w['numero_squadre'] ?? '–'),
            'Giocatori (min / max)' => ($w['min_giocatori'] ?? '–') . ' / ' . ($w['max_giocatori']  ?? '–'),
        ];
        if (!empty($w['descrizione']))
            $righe['Descrizione'] = htmlspecialchars($w['descrizione']);

        foreach ($righe as $etichetta => $valore): ?>
            <div class="riga-riepilogo">
                <span class="etichetta"><?= $etichetta ?></span>
                <span class="valore"><?= $valore ?></span>
            </div>
        <?php endforeach; ?>
    </fieldset>

    <div class="bottoni">
        <button type="submit" name="azione" value="indietro" class="btn-indietro" formnovalidate>← Indietro</button>
        <button type="submit" name="azione" value="crea"     class="btn-crea">🏆 Crea Torneo</button>
    </div>

    <?php endif; ?>

</form>

</body>
</html>