<?php
session_start();
include("../conf/db_config.php");
require_once('templates/header_riservato.php');

$errori = [];
$successo = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = trim($_POST['nome'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $formato = $_POST['formato'] ?? '';
    $tipo_partita = $_POST['tipo_partita'] ?? '';
    $visibilita = $_POST['visibilita'] ?? 'pubblico';
    $numero_squadre = intval($_POST['numero_squadre'] ?? 0);
    $min_squadre = intval($_POST['min_squadre'] ?? 0);
    $min_giocatori = intval($_POST['min_giocatori_per_squadra'] ?? 0);
    $max_giocatori = intval($_POST['max_giocatori_per_squadra'] ?? 0);
    $data_chiusura = $_POST['data_chiusura'] ?? '';

    if (empty($nome)) $errori[] = "Il nome del torneo è obbligatorio.";
    if (empty($formato)) $errori[] = "Seleziona un formato di torneo.";
    if (empty($tipo_partita)) $errori[] = "Seleziona il tipo di partita.";
    if ($numero_squadre < 2) $errori[] = "Il numero massimo di squadre deve essere almeno 2.";
    if ($min_squadre < 2) $errori[] = "Il numero minimo di squadre deve essere almeno 2.";
    if ($min_squadre > $numero_squadre) $errori[] = "Il numero minimo di squadre non può superare il massimo.";
    if ($min_giocatori < 1) $errori[] = "Inserisci il numero minimo di giocatori per squadra.";
    if ($max_giocatori < $min_giocatori) $errori[] = "Il numero massimo di giocatori deve essere ≥ al minimo.";
    if (empty($data_chiusura)) $errori[] = "La data di chiusura iscrizioni è obbligatoria.";
    if (!empty($data_chiusura) && strtotime($data_chiusura) <= time()) $errori[] = "La data di chiusura deve essere futura.";

    if(empty($errori)){
        $codice_privato = null;
        if($visibilita === 'privato')
            $codice_privato = strtoupper(bin2hex(random_bytes(4))); // es. A3F1CC92

        $stmt = $pdo->prepare("
            INSERT INTO torneo 
              (nome, descrizione, formato, tipo_partita, visibilita, numero_squadre,
               min_squadre, min_giocatori_per_squadra, max_giocatori_per_squadra,
               data_chiusura_iscrizioni, codice_privato, creato_da, stato)
            VALUES 
              (:nome, :descrizione, :formato, :tipo_partita, :visibilita, :numero_squadre,
               :min_squadre, :min_giocatori, :max_giocatori,
               :data_chiusura, :codice_privato, :creato_da, 'aperto')
        ");
        $stmt->execute([
            ':nome'           => $nome,
            ':descrizione'    => $descrizione,
            ':formato'        => $formato,
            ':tipo_partita'   => $tipo_partita,
            ':visibilita'     => $visibilita,
            ':numero_squadre' => $numero_squadre,
            ':min_squadre'    => $min_squadre,
            ':min_giocatori'  => $min_giocatori,
            ':max_giocatori'  => $max_giocatori,
            ':data_chiusura'  => $data_chiusura,
            ':codice_privato' => $codice_privato,
            ':creato_da'      => $_SESSION['utente_id'],
        ]);

        $nuovo_id = $pdo->lastInsertId();

        // Redirect a dettaglio torneo
        header("Location: dettaglio_torneo.php?id=" . $nuovo_id . "&nuovo=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Torneo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        /* ── RESET & BASE ─────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0d0d0f;
            --surface:   #141417;
            --card:      #1a1a1f;
            --border:    #2a2a33;
            --accent:    #e8ff47;
            --accent2:   #47ffe8;
            --text:      #f0f0ee;
            --muted:     #7a7a88;
            --danger:    #ff4d4d;
            --radius:    12px;
            --step:      48px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            min-height: 100vh;
            padding: 0 0 80px;
        }

        /* ── PAGE HEADER ──────────────────────────────────────── */
        .page-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 28px 40px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-header a.back {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            transition: color .2s;
        }
        .page-header a.back:hover { color: var(--text); }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.5px;
        }
        .page-header h1 span { color: var(--accent); }

        /* ── WIZARD LAYOUT ────────────────────────────────────── */
        .wizard-wrap {
            max-width: 760px;
            margin: 48px auto 0;
            padding: 0 24px;
        }

        /* ── STEP INDICATORS ──────────────────────────────────── */
        .steps-bar {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 44px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 22px;
            left: calc(50% + 16px);
            right: calc(-50% + 16px);
            height: 2px;
            background: var(--border);
            z-index: 0;
            transition: background .4s;
        }
        .step-item.done:not(:last-child)::after { background: var(--accent); }

        .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 15px;
            color: var(--muted);
            position: relative;
            z-index: 1;
            transition: all .3s;
        }
        .step-item.active .step-circle {
            border-color: var(--accent);
            background: var(--accent);
            color: #0d0d0f;
        }
        .step-item.done .step-circle {
            border-color: var(--accent);
            background: transparent;
            color: var(--accent);
        }

        .step-label {
            margin-top: 8px;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--muted);
            text-align: center;
        }
        .step-item.active .step-label { color: var(--accent); }

        /* ── CARD PANEL ───────────────────────────────────────── */
        .wizard-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px;
            display: none;
            animation: fadeUp .35s ease;
        }
        .wizard-panel.active { display: block; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .panel-title {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .panel-subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* ── FORM ELEMENTS ────────────────────────────────────── */
        .field {
            margin-bottom: 24px;
        }
        .field label {
            display: block;
            font-size: 13px;
            letter-spacing: .3px;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .field label span.req { color: var(--accent); margin-left: 2px; }

        .field input[type="text"],
        .field input[type="number"],
        .field input[type="datetime-local"],
        .field textarea,
        .field select {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            padding: 12px 16px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            -webkit-appearance: none;
        }
        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,255,71,.12);
        }

        .field textarea { resize: vertical; min-height: 90px; }

        /* SELECT arrow */
        .field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237a7a88' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
            cursor: pointer;
        }
        .field select option { background: var(--surface); }

        /* HINT sotto il campo */
        .field .hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        /* GRID 2 colonne */
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* ── TIPOLOGIA RADIO CARDS ────────────────────────────── */
        .format-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .format-card {
            position: relative;
            cursor: pointer;
        }
        .format-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
        }
        .format-card .card-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px 12px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            transition: all .2s;
            text-align: center;
        }
        .format-card:hover .card-inner {
            border-color: var(--muted);
            background: var(--bg);
        }
        .format-card input:checked + .card-inner {
            border-color: var(--accent);
            background: rgba(232,255,71,.07);
        }
        .format-card .icon {
            font-size: 26px;
            line-height: 1;
        }
        .format-card .label {
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }
        .format-card .desc {
            font-size: 11px;
            color: var(--muted);
            line-height: 1.4;
        }

        /* ── TOGGLE VISIBILITÀ ────────────────────────────────── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .toggle-info { display: flex; flex-direction: column; gap: 3px; }
        .toggle-info strong { font-size: 14px; font-family: 'Syne', sans-serif; font-weight: 600; }
        .toggle-info span { font-size: 12px; color: var(--muted); }

        .toggle-switch { position: relative; width: 48px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: var(--border);
            border-radius: 26px;
            cursor: pointer;
            transition: background .3s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            left: 4px; top: 4px;
            background: #fff;
            border-radius: 50%;
            transition: transform .3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: var(--accent); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); background: #0d0d0f; }

        /* ── TIPO PARTITA ─────────────────────────────────────── */
        .tipo-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; }
        .tipo-card { position: relative; cursor: pointer; }
        .tipo-card input { position: absolute; opacity: 0; }
        .tipo-card .inner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            transition: all .2s;
        }
        .tipo-card:hover .inner { border-color: var(--muted); }
        .tipo-card input:checked + .inner { border-color: var(--accent2); background: rgba(71,255,232,.06); }
        .tipo-card .t-icon { font-size: 22px; }
        .tipo-card .t-label { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; }
        .tipo-card .t-sub { font-size: 11px; color: var(--muted); }

        /* ── NAVIGATION BUTTONS ───────────────────────────────── */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 36px;
            gap: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all .2s;
            letter-spacing: .3px;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--accent);
            color: #0d0d0f;
        }
        .btn-primary:hover { background: #d4eb3a; transform: translateY(-1px); }
        .btn-secondary {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { color: var(--text); border-color: var(--muted); }
        .btn-success {
            background: var(--accent2);
            color: #0d0d0f;
        }
        .btn-success:hover { background: #3ae8d4; transform: translateY(-1px); }

        /* ── ERRORI ───────────────────────────────────────────── */
        .error-box {
            background: rgba(255,77,77,.1);
            border: 1px solid rgba(255,77,77,.4);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .error-box ul { list-style: none; display: flex; flex-direction: column; gap: 6px; }
        .error-box li { font-size: 13px; color: #ff7f7f; display: flex; align-items: center; gap: 8px; }
        .error-box li::before { content: '✕'; font-size: 11px; }

        /* ── RIEPILOGO STEP 4 ─────────────────────────────────── */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        .summary-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 18px;
        }
        .summary-item .s-label {
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 5px;
        }
        .summary-item .s-val {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
        }
        .summary-item.full { grid-column: 1 / -1; }

        .badge-privato {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(232,255,71,.12);
            border: 1px solid rgba(232,255,71,.3);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: var(--accent);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
        }
        .badge-pubblico {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(71,255,232,.1);
            border: 1px solid rgba(71,255,232,.3);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: var(--accent2);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
        }

        .field-error-msg {
            font-size: 12px;
            color: var(--danger);
            margin-top: 5px;
            display: none;
        }
        .field-error-msg.visible { display: block; }
        .field input.invalid,
        .field select.invalid,
        .field textarea.invalid {
            border-color: var(--danger);
        }

        /* ── RESPONSIVE ───────────────────────────────────────── */
        @media (max-width: 600px) {
            .wizard-panel { padding: 28px 20px; }
            .format-grid { grid-template-columns: 1fr; }
            .field-grid { grid-template-columns: 1fr; }
            .tipo-row { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr; }
            .summary-item.full { grid-column: unset; }
            .page-header { padding: 20px 20px; }
        }
    </style>
</head>
<body>

<header class="page-header">
    <a href="index.php" class="back">
        ← Torna alla Home
    </a>
    <h1>Crea <span>Torneo</span></h1>
</header>

<div class="wizard-wrap">

    <?php if (!empty($errori)): ?>
    <div class="error-box">
        <ul>
            <?php foreach ($errori as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Indicatori step -->
    <div class="steps-bar">
        <div class="step-item active" id="si1">
            <div class="step-circle">1</div>
            <div class="step-label">Formato</div>
        </div>
        <div class="step-item" id="si2">
            <div class="step-circle">2</div>
            <div class="step-label">Dettagli</div>
        </div>
        <div class="step-item" id="si3">
            <div class="step-circle">3</div>
            <div class="step-label">Squadre</div>
        </div>
        <div class="step-item" id="si4">
            <div class="step-circle">4</div>
            <div class="step-label">Riepilogo</div>
        </div>
    </div>

    <form method="POST" action="crea_torneo.php" id="wizardForm" novalidate>

        <!-- ── STEP 1: Formato ─────────────────────────────────── -->
        <div class="wizard-panel active" id="step1">
            <div class="panel-title">Formato del torneo</div>
            <div class="panel-subtitle">Scegli come si strutturano le partite. Questa scelta non potrà essere modificata dopo la creazione.</div>

            <div class="format-grid">
                <label class="format-card">
                    <input type="radio" name="formato" value="eliminazione_diretta"
                        <?= (($_POST['formato'] ?? '') === 'eliminazione_diretta') ? 'checked' : '' ?>>
                    <div class="card-inner">
                        <div class="icon">⚡</div>
                        <div class="label">Eliminazione Diretta</div>
                        <div class="desc">Chi perde è eliminato. Veloce e spettacolare.</div>
                    </div>
                </label>
                <label class="format-card">
                    <input type="radio" name="formato" value="girone_unico"
                        <?= (($_POST['formato'] ?? '') === 'girone_unico') ? 'checked' : '' ?>>
                    <div class="card-inner">
                        <div class="icon">🔄</div>
                        <div class="label">Girone all'Italiana</div>
                        <div class="desc">Tutti contro tutti. La classifica decide il vincitore.</div>
                    </div>
                </label>
                <label class="format-card">
                    <input type="radio" name="formato" value="gironi_playoff"
                        <?= (($_POST['formato'] ?? '') === 'gironi_playoff') ? 'checked' : '' ?>>
                    <div class="card-inner">
                        <div class="icon">🏆</div>
                        <div class="label">Gironi + Playoff</div>
                        <div class="desc">Fase a gironi seguita da eliminazione diretta.</div>
                    </div>
                </label>
            </div>
            <div class="field-error-msg" id="err-formato">Seleziona un formato per continuare.</div>

            <div class="field" style="margin-bottom:0">
                <label>Tipo di partita</label>
            </div>
            <div class="tipo-row">
                <label class="tipo-card">
                    <input type="radio" name="tipo_partita" value="andata"
                        <?= (($_POST['tipo_partita'] ?? 'andata') === 'andata') ? 'checked' : '' ?>>
                    <div class="inner">
                        <div class="t-icon">➡️</div>
                        <div>
                            <div class="t-label">Solo Andata</div>
                            <div class="t-sub">Una partita per scontro diretto</div>
                        </div>
                    </div>
                </label>
                <label class="tipo-card">
                    <input type="radio" name="tipo_partita" value="andata_ritorno"
                        <?= (($_POST['tipo_partita'] ?? '') === 'andata_ritorno') ? 'checked' : '' ?>>
                    <div class="inner">
                        <div class="t-icon">↔️</div>
                        <div>
                            <div class="t-label">Andata e Ritorno</div>
                            <div class="t-sub">Due partite per ogni scontro</div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="nav-buttons">
                <a href="index.php" class="btn btn-secondary">Annulla</a>
                <button type="button" class="btn btn-primary" onclick="goTo(2)">Avanti →</button>
            </div>
        </div>

        <!-- ── STEP 2: Dettagli ────────────────────────────────── -->
        <div class="wizard-panel" id="step2">
            <div class="panel-title">Dettagli del torneo</div>
            <div class="panel-subtitle">Dai un nome al tuo torneo e scegli se renderlo accessibile a tutti o riservato.</div>

            <div class="field">
                <label for="nome">Nome del torneo <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" maxlength="150"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    placeholder="es. Champions d'Estate 2025">
                <div class="field-error-msg" id="err-nome">Il nome è obbligatorio.</div>
            </div>

            <div class="field">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" maxlength="255"
                    placeholder="Regole speciali, luogo, info utili..."><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
                <div class="hint">Facoltativo · max 255 caratteri</div>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <strong id="vis-label">Torneo Pubblico</strong>
                    <span id="vis-desc">Visibile e seguibile da tutti gli utenti</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="visToggle" onchange="toggleVisibilita(this)">
                    <span class="toggle-slider"></span>
                </label>
                <input type="hidden" name="visibilita" id="visInput"
                    value="<?= htmlspecialchars($_POST['visibilita'] ?? 'pubblico') ?>">
            </div>

            <div class="field">
                <label for="data_chiusura">Data chiusura iscrizioni <span class="req">*</span></label>
                <input type="datetime-local" id="data_chiusura" name="data_chiusura"
                    value="<?= htmlspecialchars($_POST['data_chiusura'] ?? '') ?>">
                <div class="field-error-msg" id="err-data">Inserisci una data futura.</div>
            </div>

            <div class="nav-buttons">
                <button type="button" class="btn btn-secondary" onclick="goTo(1)">← Indietro</button>
                <button type="button" class="btn btn-primary" onclick="goTo(3)">Avanti →</button>
            </div>
        </div>

        <!-- ── STEP 3: Squadre ─────────────────────────────────── -->
        <div class="wizard-panel" id="step3">
            <div class="panel-title">Composizione squadre</div>
            <div class="panel-subtitle">Definisci quante squadre possono partecipare e quanti giocatori compongono ogni rosa.</div>

            <div class="field-grid">
                <div class="field">
                    <label for="numero_squadre">Max squadre <span class="req">*</span></label>
                    <input type="number" id="numero_squadre" name="numero_squadre" min="2" max="256"
                        value="<?= htmlspecialchars($_POST['numero_squadre'] ?? '8') ?>">
                    <div class="field-error-msg" id="err-maxsq">Minimo 2 squadre.</div>
                </div>
                <div class="field">
                    <label for="min_squadre">Min squadre per avviare <span class="req">*</span></label>
                    <input type="number" id="min_squadre" name="min_squadre" min="2" max="256"
                        value="<?= htmlspecialchars($_POST['min_squadre'] ?? '4') ?>">
                    <div class="field-error-msg" id="err-minsq">Deve essere ≤ al massimo.</div>
                </div>
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="min_giocatori_per_squadra">Min giocatori per squadra <span class="req">*</span></label>
                    <input type="number" id="min_giocatori_per_squadra" name="min_giocatori_per_squadra" min="1" max="50"
                        value="<?= htmlspecialchars($_POST['min_giocatori_per_squadra'] ?? '5') ?>">
                    <div class="field-error-msg" id="err-mingk">Minimo 1.</div>
                </div>
                <div class="field">
                    <label for="max_giocatori_per_squadra">Max giocatori per squadra <span class="req">*</span></label>
                    <input type="number" id="max_giocatori_per_squadra" name="max_giocatori_per_squadra" min="1" max="50"
                        value="<?= htmlspecialchars($_POST['max_giocatori_per_squadra'] ?? '10') ?>">
                    <div class="hint">Include i giocatori di panchina</div>
                    <div class="field-error-msg" id="err-maxgk">Deve essere ≥ al minimo.</div>
                </div>
            </div>

            <div class="nav-buttons">
                <button type="button" class="btn btn-secondary" onclick="goTo(2)">← Indietro</button>
                <button type="button" class="btn btn-primary" onclick="goTo(4)">Avanti →</button>
            </div>
        </div>

        <!-- ── STEP 4: Riepilogo ───────────────────────────────── -->
        <div class="wizard-panel" id="step4">
            <div class="panel-title">Riepilogo</div>
            <div class="panel-subtitle">Controlla i dati prima di creare il torneo. Potrai modificarli finché le iscrizioni sono aperte.</div>

            <div class="summary-grid" id="summaryGrid">
                <!-- popolato via JS -->
            </div>

            <div class="nav-buttons">
                <button type="button" class="btn btn-secondary" onclick="goTo(3)">← Indietro</button>
                <button type="submit" class="btn btn-success">🏆 Crea Torneo</button>
            </div>
        </div>

    </form>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;

// Ripristina step se c'erano errori PHP
<?php if (!empty($errori)): ?>
currentStep = 4; // mostra tutti i pannelli al post-back in caso di errore
<?php endif; ?>

function showStep(n) {
    document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('step' + n).classList.add('active');

    // Aggiorna indicatori
    for (let i = 1; i <= totalSteps; i++) {
        const si = document.getElementById('si' + i);
        si.classList.remove('active', 'done');
        if (i < n) si.classList.add('done');
        else if (i === n) si.classList.add('active');
    }

    currentStep = n;
    if (n === 4) buildSummary();
}

function goTo(n) {
    if (n > currentStep && !validateStep(currentStep)) return;
    showStep(n);
}

function validateStep(step) {
    let ok = true;
    clearErrors();

    if (step === 1) {
        const fmt = document.querySelector('input[name="formato"]:checked');
        if (!fmt) { show('err-formato'); ok = false; }
    }

    if (step === 2) {
        const nome = document.getElementById('nome').value.trim();
        if (!nome) { markInvalid('nome', 'err-nome'); ok = false; }

        const dt = document.getElementById('data_chiusura').value;
        if (!dt || new Date(dt) <= new Date()) { markInvalid('data_chiusura', 'err-data'); ok = false; }
    }

    if (step === 3) {
        const maxSq = parseInt(document.getElementById('numero_squadre').value);
        const minSq = parseInt(document.getElementById('min_squadre').value);
        const minGk = parseInt(document.getElementById('min_giocatori_per_squadra').value);
        const maxGk = parseInt(document.getElementById('max_giocatori_per_squadra').value);

        if (!maxSq || maxSq < 2) { markInvalid('numero_squadre', 'err-maxsq'); ok = false; }
        if (!minSq || minSq < 2 || minSq > maxSq) { markInvalid('min_squadre', 'err-minsq'); ok = false; }
        if (!minGk || minGk < 1) { markInvalid('min_giocatori_per_squadra', 'err-mingk'); ok = false; }
        if (!maxGk || maxGk < minGk) { markInvalid('max_giocatori_per_squadra', 'err-maxgk'); ok = false; }
    }

    return ok;
}

function markInvalid(fieldId, errId) {
    const f = document.getElementById(fieldId);
    if (f) f.classList.add('invalid');
    show(errId);
}

function show(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('visible');
}

function clearErrors() {
    document.querySelectorAll('.field-error-msg').forEach(e => e.classList.remove('visible'));
    document.querySelectorAll('.invalid').forEach(e => e.classList.remove('invalid'));
}

function toggleVisibilita(cb) {
    const inp = document.getElementById('visInput');
    const lbl = document.getElementById('vis-label');
    const dsc = document.getElementById('vis-desc');
    if (cb.checked) {
        inp.value = 'privato';
        lbl.textContent = 'Torneo Privato';
        dsc.textContent = 'Accessibile solo tramite codice univoco';
    } else {
        inp.value = 'pubblico';
        lbl.textContent = 'Torneo Pubblico';
        dsc.textContent = 'Visibile e seguibile da tutti gli utenti';
    }
}

// Inizializza toggle se ritorno da POST
window.addEventListener('DOMContentLoaded', () => {
    const vis = document.getElementById('visInput').value;
    if (vis === 'privato') {
        document.getElementById('visToggle').checked = true;
        toggleVisibilita(document.getElementById('visToggle'));
    }
});

const formatLabels = {
    eliminazione_diretta: 'Eliminazione Diretta',
    girone_unico:         'Girone all\'Italiana',
    gironi_playoff:       'Gironi + Playoff'
};
const tipoLabels = {
    andata:         'Solo Andata',
    andata_ritorno: 'Andata e Ritorno'
};

function buildSummary() {
    const fmt   = (document.querySelector('input[name="formato"]:checked') || {}).value || '–';
    const tipo  = (document.querySelector('input[name="tipo_partita"]:checked') || {}).value || '–';
    const nome  = document.getElementById('nome').value.trim() || '–';
    const desc  = document.getElementById('descrizione').value.trim() || 'Nessuna';
    const vis   = document.getElementById('visInput').value;
    const dt    = document.getElementById('data_chiusura').value;
    const maxSq = document.getElementById('numero_squadre').value || '–';
    const minSq = document.getElementById('min_squadre').value || '–';
    const minGk = document.getElementById('min_giocatori_per_squadra').value || '–';
    const maxGk = document.getElementById('max_giocatori_per_squadra').value || '–';

    const dtFmt = dt ? new Date(dt).toLocaleString('it-IT', {dateStyle:'medium', timeStyle:'short'}) : '–';
    const visBadge = vis === 'privato'
        ? `<span class="badge-privato">🔒 Privato</span>`
        : `<span class="badge-pubblico">🌐 Pubblico</span>`;

    document.getElementById('summaryGrid').innerHTML = `
        <div class="summary-item full">
            <div class="s-label">Nome torneo</div>
            <div class="s-val">${escHtml(nome)}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Formato</div>
            <div class="s-val">${formatLabels[fmt] || fmt}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Tipo partita</div>
            <div class="s-val">${tipoLabels[tipo] || tipo}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Visibilità</div>
            <div class="s-val">${visBadge}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Chiusura iscrizioni</div>
            <div class="s-val">${dtFmt}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Squadre (min / max)</div>
            <div class="s-val">${escHtml(minSq)} / ${escHtml(maxSq)}</div>
        </div>
        <div class="summary-item">
            <div class="s-label">Giocatori per squadra (min / max)</div>
            <div class="s-val">${escHtml(minGk)} / ${escHtml(maxGk)}</div>
        </div>
        ${desc !== 'Nessuna' ? `<div class="summary-item full">
            <div class="s-label">Descrizione</div>
            <div class="s-val" style="font-weight:400;font-size:14px;font-family:'DM Sans'">${escHtml(desc)}</div>
        </div>` : ''}
    `;
}

function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

showStep(currentStep);
</script>

</body>
</html>