<?php
session_start();
require_once '../staging/conf/db_config.php';

// Protezione CSRF
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: ../staging/login.php?msg=err'); exit;
}
unset($_SESSION['oauth_state']);

if (isset($_GET['error']) || !isset($_GET['code'])) {
    header('Location: ../staging/login.php?msg=err'); exit;
}

// 1. Scambia il codice con il token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
]);
$tokenData = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($tokenData['access_token'])) {
    header('Location: ../staging/login.php?msg=err'); exit;
}

// 2. Recupera i dati utente da Google
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
]);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($userInfo['email'])) {
    header('Location: ../staging/login.php?msg=err'); exit;
}

$email     = $userInfo['email'];
$nome      = $userInfo['given_name'] ?? 'Utente';
$cognome   = $userInfo['family_name'] ?? '';
$google_id = $userInfo['sub']; // ← definito qui, prima di usarlo

// 3. Cerca utente nel DB per google_id o email
$stmt = $conn->prepare("SELECT * FROM utente WHERE google_id = ? OR email = ?");
$stmt->bind_param("ss", $google_id, $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    if (empty($row['google_id'])) {
        $upd = $conn->prepare("UPDATE utente SET google_id = ?, verified = 1 WHERE id = ?");
        $upd->bind_param("si", $google_id, $row['id']);
        $upd->execute();
        $upd->close();
    }
} else {
    $stmt = $conn->prepare(
        "INSERT INTO utente (nome, cognome, email, password, token, verified, google_id)
         VALUES (?, ?, ?, '', '', 1, ?)"
    );
    $stmt->bind_param("ssss", $nome, $cognome, $email, $google_id);
    $stmt->execute();
    $newId = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM utente WHERE id = ?");
    $stmt->bind_param("i", $newId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 4. Imposta sessione
$_SESSION['login']             = 'ok';
$_SESSION['id_utente']         = $row['id'];
$_SESSION['nome_utente']       = $row['nome'];
$_SESSION['cognome_utente']    = $row['cognome'];
$_SESSION['email_utente']      = $row['email'];
$_SESSION['verified_utente']   = $row['cognome'];
$_SESSION['created_at_utente'] = $row['created_at'];

$conn->close();
header('Location: ../staging/index.php');
exit;