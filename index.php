<?php

require_once 'templates/header.php';
include("conf/db_config.php");

/* FILTRI */
$filtro_ricerca = $_GET['ricerca'] ?? '';
$filtro_stato   = $_GET['stato'] ?? '';
$filtro_formato = $_GET['formato'] ?? '';

/* QUERY BASE */
$sql = "
SELECT id, nome, formato, stato, sport, luogo
FROM torneo
WHERE visibilita = 'pubblico'
";

$parametri = [];
$tipi = "";

/* FILTRO RICERCA */
if (!empty($filtro_ricerca)) {
    $sql .= " AND nome LIKE ?";
    $parametri[] = "%" . $filtro_ricerca . "%";
    $tipi .= "s";
}

/* FILTRO FORMATO */
if (!empty($filtro_formato)) {
    $sql .= " AND formato = ?";
    $parametri[] = $filtro_formato;
    $tipi .= "s";
}

/* FILTRO STATO */
if (!empty($filtro_stato)) {
    $sql .= " AND stato = ?";
    $parametri[] = $filtro_stato;
    $tipi .= "s";
}

$sql .= " ORDER BY id DESC";

/* PREPARE */
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Errore query");
}

/* BIND PARAMS */
if (!empty($parametri)) {
    $stmt->bind_param($tipi, ...$parametri);
}

/* EXECUTE */
$stmt->execute();

$result = $stmt->get_result();

?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index_filtri.css">
    <title>Torneo crazy</title>
</head>
<h1>Tornei pubblici</h1>

<a class="btn-create" href="crea_torneo.php" >
    Crea nuovo torneo
</a>


<form method="GET" action="index.php">

    <label for="ricerca">Cerca per nome:</label>

    <input
        type="text"
        id="ricerca"
        name="ricerca"
        value="<?= htmlspecialchars($filtro_ricerca) ?>"
        placeholder="Nome torneo..."
    >

    <label for="formato">Formato:</label>

    <select id="formato" name="formato">

        <option value="">Tutti</option>

        <option value="girone_unico"
            <?= $filtro_formato === 'girone_unico' ? 'selected' : '' ?>>
            Girone unico
        </option>

        <option value="eliminazione_diretta"
            <?= $filtro_formato === 'eliminazione_diretta' ? 'selected' : '' ?>>
            Eliminazione diretta
        </option>

        <option value="gironi_playoff"
            <?= $filtro_formato === 'gironi_playoff' ? 'selected' : '' ?>>
            Gironi + playoff
        </option>

    </select>

    <label for="stato">Stato:</label>

    <select id="stato" name="stato">

        <option value="">Tutti</option>

        <option value="aperto"
            <?= $filtro_stato === 'aperto' ? 'selected' : '' ?>>
            Aperto
        </option>

        <option value="in_corso"
            <?= $filtro_stato === 'in_corso' ? 'selected' : '' ?>>
            In corso
        </option>

        <option value="completato"
            <?= $filtro_stato === 'completato' ? 'selected' : '' ?>>
            Completato
        </option>

    </select>

    <button type="submit">Filtra</button>

    <a href="index.php">
        <button type="button">
            Azzera filtri
        </button>
    </a>

</form>

<br>
<h2>Lista tornei</h2>

<?php include("components/tabella_tornei.php"); ?>

<?php

if(isset($_GET['msg'])) {

    if($_GET['msg'] == 'errTorneoNonTrovato')
        echo "<div style='color:red;'>Errore torneo non trovato</div>";

    else if($_GET['msg'] == 'err')
        echo "<div style='color:red;'>Errore, riprova più tardi</div>";
}

/* CLOSE */
$stmt->close();
$conn->close();

require_once('templates/footer.php');

?>