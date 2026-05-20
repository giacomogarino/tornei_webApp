<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../conf/db_config.php");

$token  = trim($_GET['token']  ?? '');
$azione = trim($_GET['azione'] ?? '');

if(!$token || !in_array($azione, ['approva','rifiuta'])){
    die("Richiesta non valida.");
}

// Cerca la squadra per token
if($azione === 'approva'){
    $stmt = $conn->prepare("SELECT s.*, t.nome AS nome_torneo, t.creato_da
                            FROM squadra s
                            JOIN torneo t ON t.id = s.torneo_id
                            WHERE s.token_approva = ?");
} else {
    $stmt = $conn->prepare("SELECT s.*, t.nome AS nome_torneo, t.creato_da
                            FROM squadra s
                            JOIN torneo t ON t.id = s.torneo_id
                            WHERE s.token_rifiuta = ?");
}
$stmt->bind_param("s", $token);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();

if(!$squadra){
    die("Token non valido o già utilizzato.");
}

if($squadra['stato'] !== 'in_attesa'){
    die("Questa squadra è già stata " .
        ($squadra['stato'] === 'approvata' ? 'approvata' : 'rifiutata') . ".");
}

// Applica l'azione
$nuovo_stato = ($azione === 'approva') ? 'approvata' : 'rifiutata';

$stmt2 = $conn->prepare("
    UPDATE squadra
    SET stato=?, token_approva=NULL, token_rifiuta=NULL
    WHERE id=?
");
$stmt2->bind_param("si", $nuovo_stato, $squadra['id']);
$stmt2->execute();

// Notifica il capitano
$stmt3 = $conn->prepare("SELECT nome, cognome, email FROM utente WHERE id=?");
$stmt3->bind_param("i", $squadra['capitano_id']);
$stmt3->execute();
$capitano = $stmt3->get_result()->fetch_assoc();

$esito   = ($nuovo_stato === 'approvata') ? 'APPROVATA' : 'RIFIUTATA';
$subject = "La tua squadra \"{$squadra['nome']}\" è stata $esito";
$message =
"Ciao {$capitano['nome']},

La tua squadra \"{$squadra['nome']}\" per il torneo \"{$squadra['nome_torneo']}\"
è stata $esito dall'organizzatore.

Accedi alla piattaforma per maggiori dettagli.";

$headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
           "Content-Type: text/plain; charset=UTF-8\r\n";

mail($capitano['email'], $subject, $message, $headers);

if($azione === 'home'){
    header("Location: ../index.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito squadra</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 60px auto; text-align: center; }
        .box { padding: 30px; border-radius: 8px; }
        .approvata { background: #d4edda; color: #155724; }
        .rifiutata  { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="box <?=$nuovo_stato?>">
        <h2>Squadra "<?=htmlspecialchars($squadra['nome'])?>" <?=$esito?></h2>
        <p>Il capitano è stato notificato via email.</p>
    </div>
</body>
</html>