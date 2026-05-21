<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'templates/header_riservato.php';
include("conf/db_config.php");

$utente_id = isset($_SESSION['id_utente']) ? $_SESSION['id_utente'] : null;

if (!$utente_id) {
    header("location: ../tornei_seguiti.php?msg=necessariaAutentificazione");
    //die("Devi essere loggato per vedere i tornei seguiti");
}

$sql = "SELECT t.id, t.nome, t.formato, t.stato, t.sport, t.luogo
        FROM torneo t
        INNER JOIN torneo_seguito ts 
            ON t.id = ts.torneo_id
        WHERE ts.utente_id = ?";

$stmt = $conn->prepare($sql);


$stmt->bind_param("i", $utente_id);
$stmt->execute();

$result = $stmt->get_result();

require_once('templates/header_riservato.php')
?>

<body>
<h2>I MIEI TORNEI SEGUITI</h2>
<?php include("components/tabella_tornei.php"); ?>
    <?php
        if(isset($_GET['msg'])){
            if($_GET['msg'] == 'necessariaAutentificazione')
                echo "<div>Errore devi essere loggato per vedere i tornei seguiti"."</div>";
        }
    ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
require_once('templates/footer.php')
?>