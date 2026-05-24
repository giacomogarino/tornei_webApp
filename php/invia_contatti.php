<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Accetta solo POST
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../contatti.php");
    exit;
}

$nome     = trim($_POST['nome']     ?? '');
$cognome  = trim($_POST['cognome']  ?? '');
$email    = trim($_POST['email']    ?? '');
$oggetto  = trim($_POST['oggetto']  ?? '');
$messaggio= trim($_POST['messaggio']?? '');
$privacy  = $_POST['privacy_ok']    ?? '';

/* =====================================================
   VALIDAZIONI
===================================================== */

if(empty($nome) || empty($cognome) || empty($email) || empty($oggetto) || empty($messaggio)){
    header("Location: ../contatti.php?msg=campiVuoti");
    exit;
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    header("Location: ../contatti.php?msg=emailNonValida");
    exit;
}

if(strlen($messaggio) < 10){
    header("Location: ../contatti.php?msg=messaggioTroppoCorto");
    exit;
}

if(empty($privacy)){
    header("Location: ../contatti.php?msg=privacyNonAccettata");
    exit;
}

// Sanifica l'oggetto scelto
$oggettiValidi = ['supporto', 'account', 'torneo', 'privacy', 'segnalazione', 'altro'];
if(!in_array($oggetto, $oggettiValidi)){
    header("Location: ../contatti.php?msg=oggettoNonValido");
    exit;
}

$oggettoLabel = [
    'supporto'     => 'Supporto tecnico',
    'account'      => 'Problema con l\'account',
    'torneo'       => 'Problema con un torneo',
    'privacy'      => 'Richiesta relativa alla privacy',
    'segnalazione' => 'Segnalazione abuso',
    'altro'        => 'Altro',
][$oggetto];

/* =====================================================
   INVIO EMAIL ALL'AMMINISTRATORE
===================================================== */

// TODO: sostituisci con l'indirizzo email reale del gestore
$destinatario = 'matchora.torneo@gmail.com';

$subject = "[Matchora Contatti] $oggettoLabel — $nome $cognome";

$body  = "Hai ricevuto un nuovo messaggio dal form di contatto di Matchora.\n";
$body .= "=============================================================\n\n";
$body .= "Nome:     $nome $cognome\n";
$body .= "Email:    $email\n";
$body .= "Oggetto:  $oggettoLabel\n\n";
$body .= "Messaggio:\n";
$body .= "-----------------------------------------------------------\n";
$body .= wordwrap(strip_tags($messaggio), 72, "\n", false) . "\n";
$body .= "-----------------------------------------------------------\n\n";
$body .= "Privacy accettata: Sì\n";
$body .= "Data invio: " . date('d/m/Y H:i:s') . "\n";
$body .= "IP mittente: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/D') . "\n";

$headers  = "From: noreply@matchoratorneo.netsons.org\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: Matchora-Contact-Form/1.0\r\n";

/* =====================================================
   INVIO EMAIL DI CONFERMA ALL'UTENTE
===================================================== */

$subjectUtente = "Abbiamo ricevuto il tuo messaggio — Matchora";

$bodyUtente  = "Ciao $nome,\n\n";
$bodyUtente .= "Grazie per averci contattato! Abbiamo ricevuto il tuo messaggio e ti risponderemo entro 48 ore lavorative.\n\n";
$bodyUtente .= "=============================================================\n";
$bodyUtente .= "Riepilogo della tua richiesta\n";
$bodyUtente .= "=============================================================\n\n";
$bodyUtente .= "Oggetto:  $oggettoLabel\n\n";
$bodyUtente .= "Messaggio:\n";
$bodyUtente .= "-----------------------------------------------------------\n";
$bodyUtente .= wordwrap(strip_tags($messaggio), 72, "\n", false) . "\n";
$bodyUtente .= "-----------------------------------------------------------\n\n";
$bodyUtente .= "Se hai inviato questo messaggio per errore o hai un'urgenza, rispondi direttamente a questa email.\n\n";
$bodyUtente .= "— Il team di Matchora\n";
$bodyUtente .= "https://matchoratorneo.netsons.org/staging\n";

$headersUtente  = "From: noreply@matchoratorneo.netsons.org\r\n";
$headersUtente .= "Reply-To: $destinatario\r\n";
$headersUtente .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headersUtente .= "X-Mailer: Matchora-Contact-Form/1.0\r\n";

/* =====================================================
   ESECUZIONE E REDIRECT
===================================================== */

$invioAdmin  = mail($destinatario, $subject, $body, $headers);
$invioUtente = mail($email, $subjectUtente, $bodyUtente, $headersUtente);

if($invioAdmin){
    header("Location: ../contatti.php?msg=inviato");
} else {
    header("Location: ../contatti.php?msg=errMsg");
}
exit;
?>