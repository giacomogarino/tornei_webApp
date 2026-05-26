<?php
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';

session_secure_start();

// Accetta solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contatti.php');
    exit;
}

csrf_verify();

require_once __DIR__ . '/../conf/app_config.php';

$nome      = trim($_POST['nome']      ?? '');
$cognome   = trim($_POST['cognome']   ?? '');
$email     = trim($_POST['email']     ?? '');
$oggetto   = trim($_POST['oggetto']   ?? '');
$messaggio = trim($_POST['messaggio'] ?? '');
$privacy   = $_POST['privacy_ok']     ?? '';

// Validazioni
if (empty($nome) || empty($cognome) || empty($email) || empty($oggetto) || empty($messaggio)) {
    header('Location: ../contatti.php?msg=campiVuoti');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../contatti.php?msg=emailNonValida');
    exit;
}
if (strlen($messaggio) < 10) {
    header('Location: ../contatti.php?msg=messaggioTroppoCorto');
    exit;
}
if (empty($privacy)) {
    header('Location: ../contatti.php?msg=privacyNonAccettata');
    exit;
}

$oggettiValidi = ['supporto', 'account', 'torneo', 'privacy', 'segnalazione', 'altro'];
if (!in_array($oggetto, $oggettiValidi, true)) {
    header('Location: ../contatti.php?msg=oggettoNonValido');
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

// Email all'amministratore
$subjectAdmin = '[Matchora Contatti] ' . $oggettoLabel . ' — ' . $nome . ' ' . $cognome;
$bodyAdmin    = "Nuovo messaggio dal form di contatto di Matchora.\n"
              . "=============================================================\n\n"
              . "Nome:     $nome $cognome\n"
              . "Email:    $email\n"
              . "Oggetto:  $oggettoLabel\n\n"
              . "Messaggio:\n"
              . "-----------------------------------------------------------\n"
              . wordwrap(strip_tags($messaggio), 72, "\n", false) . "\n"
              . "-----------------------------------------------------------\n\n"
              . "Privacy accettata: Sì\n"
              . "Data invio: " . date('d/m/Y H:i:s') . "\n"
              . "IP mittente: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/D') . "\n";

$headersAdmin = "From: " . MAIL_FROM . "\r\n"
              . "Reply-To: $email\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "X-Mailer: Matchora-Contact-Form/1.0\r\n";

// Email di conferma all'utente
$subjectUtente = 'Abbiamo ricevuto il tuo messaggio — Matchora';
$bodyUtente    = "Ciao $nome,\n\n"
               . "Grazie per averci contattato!\n"
               . "Abbiamo ricevuto il tuo messaggio e ti risponderemo entro 48 ore lavorative.\n\n"
               . "=============================================================\n"
               . "Riepilogo della tua richiesta\n"
               . "=============================================================\n\n"
               . "Oggetto:  $oggettoLabel\n\n"
               . "Messaggio:\n"
               . "-----------------------------------------------------------\n"
               . wordwrap(strip_tags($messaggio), 72, "\n", false) . "\n"
               . "-----------------------------------------------------------\n\n"
               . "Se hai bisogno di assistenza urgente, rispondi direttamente a questa email.\n\n"
               . "— Il team di Matchora\n"
               . BASE_URL;

$headersUtente = "From: " . MAIL_FROM . "\r\n"
               . "Reply-To: " . MAIL_ADMIN . "\r\n"
               . "Content-Type: text/plain; charset=UTF-8\r\n"
               . "X-Mailer: Matchora-Contact-Form/1.0\r\n";

$invioAdmin = mail(MAIL_ADMIN, $subjectAdmin, $bodyAdmin, $headersAdmin);
mail($email, $subjectUtente, $bodyUtente, $headersUtente); // conferma utente

if ($invioAdmin) {
    header('Location: ../contatti.php?msg=inviato');
} else {
    header('Location: ../contatti.php?msg=errMsg');
}
exit;
