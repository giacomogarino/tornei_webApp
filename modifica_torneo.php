<?php
include("conf/db_config.php");

// Punto 3: verifica CSRF per richieste POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

$id = isset($_GET['id']) ? $_GET['id']: null;

if(!$id)
    header("Location: dettagli_torneo.php?msg=err");
    //die("ID torneo mancante");


// Recupero torneo
$sql = "SELECT id, nome, descrizione, formato, tipo_partita, visibilita,
            numero_squadre, stato, min_giocatori_per_squadra,
            max_giocatori_per_squadra, min_squadre,
            data_chiusura_iscrizioni, codice_privato, sport, luogo
        FROM torneo
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$torneo = $result->fetch_assoc();


// Salvataggio modifiche
if (isset($_POST['salva'])) {
    $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
    $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : '';
    $formato = isset($_POST['formato']) ? $_POST['formato'] : '';
    $tipo_partita = isset($_POST['tipo_partita']) ? $_POST['tipo_partita'] : '';
    $visibilita = isset($_POST['visibilita']) ? $_POST['visibilita'] : '';
    $numero_squadre = isset($_POST['numero_squadre']) ? $_POST['numero_squadre'] : 0;
    $min_giocatori = isset($_POST['min_giocatori']) ? $_POST['min_giocatori'] : 0;
    $max_giocatori = isset($_POST['max_giocatori']) ? $_POST['max_giocatori'] : 0;
    $min_squadre = isset($_POST['min_squadre']) ? $_POST['min_squadre'] : 0;
    $sport = isset($_POST['sport']) ? $_POST['sport'] : '';
    $luogo = isset($_POST['luogo']) ? $_POST['luogo'] : '';




    $update = "UPDATE torneo
        SET nome = ?,
            descrizione = ?,
            formato = ?,
            tipo_partita = ?,
            visibilita = ?,
            numero_squadre = ?,
            min_giocatori_per_squadra = ?,
            max_giocatori_per_squadra = ?,
            min_squadre = ?,
            sport = ?,
            luogo = ?
        WHERE id = ?";

    $stmt = $conn->prepare($update);
    $stmt->bind_param(
        "sssssiiiissi",
        $nome,
        $descrizione,
        $formato,
        $tipo_partita,
        $visibilita,
        $numero_squadre,
        $min_giocatori,
        $max_giocatori,
        $min_squadre,
        $sport,
        $luogo,
        $id
    );

    $stmt->execute();

    header("Location: dettagli_torneo.php?id=" . $id);

    exit;
}
require_once('templates/header_riservato.php');
?>

<main class="m-page">
    <div class="m-container" style="max-width: 880px;">

        <div style="margin-bottom: var(--m-5); font-size: 13px;">
            <a href="dettagli_torneo.php?id=<?= (int)$id ?>" style="color: var(--m-text-mute);"> Torna al torneo</a>
        </div>

        <div class="m-page-head">
            <div>
                <h1>Modifica torneo</h1>
                <div class="m-page-head__sub">Aggiorna le impostazioni di <b style="color: var(--m-primary-700)"><?= htmlspecialchars($torneo['nome']) ?></b></div>
            </div>
        </div>

        <form method="POST" class="m-card" style="padding: var(--m-6);">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="m-stack">
                <div class="m-field">
                    <label class="m-label" for="nome">Nome torneo <span style="color: var(--m-danger-500);">*</span></label>
                    <input class="m-input" type="text" id="nome" name="nome" value="<?= htmlspecialchars($torneo['nome']) ?>" required>
                </div>

                <div class="m-field">
                    <label class="m-label" for="descrizione">Descrizione</label>
                    <textarea class="m-textarea" id="descrizione" name="descrizione"><?= htmlspecialchars($torneo['descrizione']) ?></textarea>
                </div>

                <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                    <div class="m-field">
                        <label class="m-label" for="sport">Sport</label>
                        <select class="m-select" id="sport" name="sport">
                            <option value="calcio" <?= $torneo['sport']=="calcio" ? "selected" : "" ?>>Calcio</option>
                            <option value="beachvolley" <?= $torneo['sport']=="beachvolley" ? "selected" : "" ?>>Beach Volley</option>
                            <option value="padel" <?= $torneo['sport']=="padel" ? "selected" : "" ?>>Padel</option>
                            <option value="tennis" <?= $torneo['sport']=="tennis" ? "selected" : "" ?>>Tennis</option>
                        </select>
                        </select>
                    </div>

                    <div class="m-field">
                        <label class="m-label" for="luogo">Luogo</label>
                        <input class="m-input" type="text" id="luogo" name="luogo" value="<?= htmlspecialchars($torneo['luogo']) ?>" required>
                    </div>
                </div>

                <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                    <div class="m-field">
                        <label class="m-label" for="formato">Formato</label>
                        <select class="m-select" id="formato" name="formato">
                            <option value="eliminazione_diretta" <?= $torneo['formato']=="eliminazione_diretta" ? "selected" : "" ?>>Eliminazione Diretta</option>
                            <option value="girone_playoff" <?= $torneo['formato']=="girone_playoff" ? "selected" : "" ?>>Gironi + Playoff</option>
                            <option value="girone_unico" <?= $torneo['formato']=="girone_unico" ? "selected" : "" ?>>Girone Unico</option>
                        </select>
                    </div>

                    <div class="m-field">
                        <label class="m-label" for="tipo_partita">Tipo partita</label>
                        <select class="m-select" id="tipo_partita" name="tipo_partita">
                            <option value="andata" <?= $torneo['tipo_partita']=="andata" ? "selected" : "" ?>>Solo andata</option>
                            <option value="andata_ritorno" <?= $torneo['tipo_partita']=="andata_ritorno" ? "selected" : "" ?>>Andata e ritorno</option>
                        </select>
                    </div>
                </div>

                <div class="m-field">
                    <label class="m-label" for="visibilita">Visibilit</label>
                    <select class="m-select" id="visibilita" name="visibilita">
                        <option value="pubblico" <?= $torneo['visibilita']=="pubblico" ? "selected" : "" ?>>Pubblico</option>
                        <option value="privato" <?= $torneo['visibilita']=="privato" ? "selected" : "" ?>>Privato</option>
                    </select>
                </div>

                <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                    <div class="m-field">
                        <label class="m-label" for="numero_squadre">Numero massimo squadre</label>
                        <input class="m-input m-num" type="number" id="numero_squadre" name="numero_squadre" value="<?= (int)$torneo['numero_squadre'] ?>">
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="min_squadre">Numero minimo squadre</label>
                        <input class="m-input m-num" type="number" id="min_squadre" name="min_squadre" value="<?= (int)$torneo['min_squadre'] ?>">
                    </div>
                </div>

                <div class="m-grid" style="grid-template-columns: 1fr 1fr; gap: var(--m-3);">
                    <div class="m-field">
                        <label class="m-label" for="min_giocatori">Min giocatori per squadra</label>
                        <input class="m-input m-num" type="number" id="min_giocatori" name="min_giocatori" value="<?= (int)$torneo['min_giocatori_per_squadra'] ?>">
                    </div>
                    <div class="m-field">
                        <label class="m-label" for="max_giocatori">Max giocatori per squadra</label>
                        <input class="m-input m-num" type="number" id="max_giocatori" name="max_giocatori" value="<?= (int)$torneo['max_giocatori_per_squadra'] ?>">
                    </div>
                </div>
            </div>

            <div class="m-row-between" style="margin-top: var(--m-8); padding-top: var(--m-5); border-top: 1px solid var(--m-border);">
                <a href="dettagli_torneo.php?id=<?= (int)$id ?>" class="m-btn m-btn--ghost">Annulla</a>
                <button type="submit" name="salva" class="m-btn m-btn--primary m-btn--lg">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Salva modifiche
                </button>
            </div>
        </form>

    </div>
</main>

<?php require_once('templates/footer.php'); ?>
