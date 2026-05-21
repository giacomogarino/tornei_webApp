<?php
// ==========================
// CONFIG DATABASE
// ==========================
require_once 'templates/header.php';
include("conf/db_config.php");

if ($conn->connect_error) {
    die("Errore connessione: " . $conn->connect_error);
}

// ==========================
// CREAZIONE TABELLA
// ==========================
$conn->query("
    CREATE TABLE IF NOT EXISTS immagini (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_file VARCHAR(255) NOT NULL,
        percorso VARCHAR(255) NOT NULL,
        data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ==========================
// UPLOAD
// ==========================
$msg = "";

if(isset($_POST['upload'])) {

    if(isset($_FILES['immagine']) && $_FILES['immagine']['error'] == 0) {

        $nomeFile = $_FILES['immagine']['name'];
        $tmpName  = $_FILES['immagine']['tmp_name'];
        $size     = $_FILES['immagine']['size'];

        // ESTENSIONE
        $ext = strtolower(pathinfo($nomeFile, PATHINFO_EXTENSION));

        // FORMATI CONSENTITI
        $consentite = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if(!in_array($ext, $consentite)) {

            $msg = "Formato non consentito";

        } elseif($size > 5 * 1024 * 1024) {

            $msg = "File troppo grande (max 5MB)";

        } else {

            // CARTELLA UPLOAD
            $cartella = "uploads/";

            if(!is_dir($cartella)) {
                mkdir($cartella, 0777, true);
            }

            // NOME UNIVOCO
            $nuovoNome = time() . "_" . basename($nomeFile);

            $percorso = $cartella . $nuovoNome;

            // SALVA FILE
            if(move_uploaded_file($tmpName, $percorso)) {

                $stmt = $conn->prepare("
                    INSERT INTO immagini(nome_file, percorso)
                    VALUES(?, ?)
                ");

                $stmt->bind_param("ss", $nuovoNome, $percorso);

                if($stmt->execute()) {
                    $msg = "Upload completato!";
                } else {
                    $msg = "Errore database";
                }

            } else {
                $msg = "Errore upload";
            }
        }

    } else {
        $msg = "Seleziona un file";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>PROVA_img</title>
</head>
<body>

<h1>Upload Immagine</h1>

<form method="POST" enctype="multipart/form-data">

    <input type="file" name="immagine" required>

    <button type="submit" name="upload">
        Carica
    </button>

</form>

<p><?php echo $msg; ?></p>

<hr>

<h2>Immagini Salvate</h2>

<?php

$result = $conn->query("
    SELECT *
    FROM immagini
    ORDER BY id DESC
");

while($row = $result->fetch_assoc()) {

    echo "<div>";

    echo "<p>ID: " . $row['id'] . "</p>";

    echo "<p>Nome file: " . $row['nome_file'] . "</p>";

    echo "<img src='" . $row['percorso'] . "' width='300'>";

    echo "<hr>";

    echo "</div>";
}

?>

</body>
</html>