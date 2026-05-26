<?php
require_once __DIR__ . '/../conf/db_config.php';

$token  = trim($_GET['token']  ?? '');
$azione = trim($_GET['azione'] ?? '');

if (!$token || !in_array($azione, ['approva', 'rifiuta'], true)) {
    http_response_code(400);
    die('Richiesta non valida.');
}

// Cerca la squadra per il token corretto
$col  = ($azione === 'approva') ? 'token_approva' : 'token_rifiuta';
$stmt = $conn->prepare(
    "SELECT s.*, t.nome AS nome_torneo, t.creato_da
     FROM squadra s
     JOIN torneo t ON t.id = s.torneo_id
     WHERE s.$col = ?"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$squadra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$squadra) {
    http_response_code(404);
    die('Token non valido o già utilizzato.');
}

if ($squadra['stato'] !== 'in_attesa') {
    $esitoTxt = ($squadra['stato'] === 'approvata') ? 'approvata' : 'rifiutata';
    die("Questa squadra è già stata $esitoTxt.");
}

// Aggiorna stato e invalida entrambi i token
$nuovo_stato = ($azione === 'approva') ? 'approvata' : 'rifiutata';
$stmt2 = $conn->prepare(
    'UPDATE squadra SET stato = ?, token_approva = NULL, token_rifiuta = NULL WHERE id = ?'
);
$stmt2->bind_param('si', $nuovo_stato, $squadra['id']);
$stmt2->execute();
$stmt2->close();

// Notifica il capitano
$stmt3 = $conn->prepare(
    'SELECT nome, cognome, email FROM utente WHERE id = ? LIMIT 1'
);
$stmt3->bind_param('i', $squadra['capitano_id']);
$stmt3->execute();
$capitano = $stmt3->get_result()->fetch_assoc();
$stmt3->close();
$conn->close();

if ($capitano) {
    $esito   = ($nuovo_stato === 'approvata') ? 'APPROVATA' : 'RIFIUTATA';
    $subject = "La tua squadra \"{$squadra['nome']}\" è stata $esito — Matchora";
    $message = "Ciao {$capitano['nome']},\n\n"
             . "La tua squadra \"{$squadra['nome']}\" per il torneo \"{$squadra['nome_torneo']}\""
             . " è stata $esito dall'organizzatore.\n\n"
             . "Accedi alla piattaforma per maggiori dettagli.\n\n"
             . "— Il team di Matchora";
    $headers = "From: noreply@matchoratorneo.netsons.org\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    mail($capitano['email'], $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esito iscrizione squadra — Matchora</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               max-width: 520px; margin: 60px auto; padding: 0 20px; text-align: center; }
        .box { padding: 32px; border-radius: 12px; }
        .approvata { background: #d1fae5; color: #065f46; }
        .rifiutata  { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 24px;
               border-radius: 8px; background: #2563eb; color: #fff;
               text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box <?= htmlspecialchars($nuovo_stato, ENT_QUOTES, 'UTF-8') ?>">
        <h2>Squadra "<?= htmlspecialchars($squadra['nome'], ENT_QUOTES, 'UTF-8') ?>"</h2>
        <p><?= ($nuovo_stato === 'approvata') ? '✅ Approvata con successo.' : '❌ Rifiutata.' ?></p>
        <p>Il capitano è stato notificato via email.</p>
    </div>
    <a href="../index.php" class="btn">Vai alla home</a>
</body>
</html>
