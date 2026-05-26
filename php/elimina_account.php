<?php
/**
 * ELIMINA_ACCOUNT.PHP — Cancellazione account (art. 17 GDPR)
 * ============================================================
 * Richiede autenticazione + conferma email + token CSRF.
 * Cancella tutti i dati personali dell'utente dal database.
 */

require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/helpers/csrf.php';

session_secure_start();

if (!isset($_SESSION['login'])) {
    header('Location: ../login.php?msg=NecessariaAutentificazione');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profilo.php');
    exit;
}

csrf_verify();

require_once __DIR__ . '/../conf/db_config.php';

$utente_id     = (int)($_SESSION['id_utente'] ?? 0);
$email_digitat  = trim($_POST['conferma_email'] ?? '');
$email_sessione = $_SESSION['email_utente'] ?? '';

if (empty($utente_id)) {
    header('Location: ../profilo.php?msg=errore');
    exit;
}

// Verifica che l'email digitata corrisponda a quella in sessione
if (!hash_equals(strtolower($email_sessione), strtolower($email_digitat))) {
    header('Location: ../profilo.php?msg=emailErrata');
    exit;
}

// Verifica ulteriore sul DB (difesa in profondità)
$chk = $conn->prepare('SELECT id FROM utente WHERE id = ? AND email = ? LIMIT 1');
$chk->bind_param('is', $utente_id, $email_sessione);
$chk->execute();
$user = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$user) {
    header('Location: ../profilo.php?msg=errore');
    exit;
}

// Cancella dati dell'utente in ordine (rispetta FK)
// Adatta le tabelle alla struttura reale del tuo DB
$tables = [
    ['torneo_seguito', 'utente_id'],
    ['squadra',        'capitano_id'],
    // Aggiungi qui altre tabelle che referenziano l'utente
];

foreach ($tables as [$table, $col]) {
    $d = $conn->prepare("DELETE FROM `$table` WHERE `$col` = ?");
    if ($d) {
        $d->bind_param('i', $utente_id);
        $d->execute();
        $d->close();
    }
}

// Elimina l'account
$del = $conn->prepare('DELETE FROM utente WHERE id = ?');
$del->bind_param('i', $utente_id);
$del->execute();
$deleted = $del->affected_rows;
$del->close();
$conn->close();

if ($deleted > 0) {
    // Distrugge la sessione e reindirizza
    session_unset();
    session_destroy();
    header('Location: ../index.php?msg=accountEliminato');
} else {
    header('Location: ../profilo.php?msg=errore');
}
exit;
