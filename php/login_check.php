<?php
// Punto 2: sicurezza centralizzata (no display_errors, cookie sicuri, header HTTP, CSRF)
require_once("../conf/security.php");
include("../conf/db_config.php");

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

// Punto 3: verifica CSRF
csrf_verify('../login.php?msg=err');

$email = trim($_POST["email"] ?? '');
$psw   = $_POST["password"] ?? '';

if(empty($email) || empty($psw)){
    header("location: ../login.php?msg=campiVuoti");
    exit;
}

// ── Punto 6: Rate limiting anti brute-force ───────────────────────────────────
// Conta i tentativi falliti per questa email nella sessione.
// (Soluzione senza DB extra; per produzione ad alto traffico considera un approccio basato su DB/Redis)
$chiave_tentativi = 'login_fail_' . md5($email);
$max_tentativi    = 5;
$finestra_secondi = 300; // 5 minuti

if (!isset($_SESSION[$chiave_tentativi])) {
    $_SESSION[$chiave_tentativi] = ['count' => 0, 'first' => time()];
}

$tentativi = &$_SESSION[$chiave_tentativi];

// Reset se la finestra temporale è scaduta
if ((time() - $tentativi['first']) > $finestra_secondi) {
    $tentativi = ['count' => 0, 'first' => time()];
}

if ($tentativi['count'] >= $max_tentativi) {
    $attesa = $finestra_secondi - (time() - $tentativi['first']);
    header("location: ../login.php?msg=troppiTentativi");
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

$stmt = $conn->prepare("SELECT * FROM utente WHERE email = ?");
if(!$stmt){
    header("location: ../login.php?msg=err");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();
$stmt->close();

if ($row && empty($row['password'])) {
    header("location: ../login.php?msg=usaGoogle");
    exit;
}

if($row && password_verify($psw, $row['password'])){

    // Controllo email verificata
    if($row['verified'] == 0){
        header("location: ../login.php?msg=emailNonConfermata");
        exit;
    }

    // Punto 5: rigenera l'ID di sessione dopo il login (previene session fixation)
    session_regenerate_id(true);

    // Reset contatore tentativi falliti
    unset($_SESSION[$chiave_tentativi]);

    $_SESSION['login']             = 'ok';
    $_SESSION['id_utente']         = $row['id'];
    $_SESSION['nome_utente']       = $row['nome'];
    $_SESSION['cognome_utente']    = $row['cognome'];
    $_SESSION['email_utente']      = $row['email'];
    $_SESSION['verified_utente']   = $row['verified'];
    $_SESSION['created_at_utente'] = $row['created_at'];

    header("location: ../index.php");
    exit;

} else {
    // Incrementa contatore tentativi falliti
    $tentativi['count']++;
    header("location: ../login.php?msg=errLogin");
    exit;
}

$conn->close();
