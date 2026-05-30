<?php
/**
 * GOOGLE_CALLBACK.PHP — OAuth 2.0 callback di Google
 * =====================================================
 * Questo file è al percorso /php/google_callback.php come registrato
 * in Google Cloud Console (GOOGLE_REDIRECT_URI in secrets.php).
 * Quando si passa da staging a produzione, aggiornare anche la URI
 * in Google Cloud Console → Credenziali → Origini JS autorizzate.
 */

require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../conf/db_config.php';

session_secure_start();

// Protezione CSRF (state parameter)
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state'])
    || !hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
    error_log('Google OAuth: state mismatch da IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    header('Location: ../login.php?msg=err');
    exit;
}
unset($_SESSION['oauth_state']);

if (isset($_GET['error']) || !isset($_GET['code'])) {
    header('Location: ../login.php?msg=err');
    exit;
}

// 1. Scambia il code con il token
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
    CURLOPT_SSL_VERIFYPEER => true,
]);
$tokenData = json_decode(curl_exec($ch), true);
$curlErr   = curl_error($ch);
curl_close($ch);

if (!isset($tokenData['access_token'])) {
    error_log('Google OAuth: token exchange failed: ' . $curlErr);
    header('Location: ../login.php?msg=err');
    exit;
}

// 2. Recupera i dati utente da Google
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_SSL_VERIFYPEER => true,
]);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($userInfo['email'])) {
    header('Location: ../login.php?msg=err');
    exit;
}

$email     = $userInfo['email'];
$nome      = $userInfo['given_name']  ?? 'Utente';
$cognome   = $userInfo['family_name'] ?? '';
$google_id = $userInfo['sub'];

// 3. Cerca o crea l'utente nel DB
// ── MODIFICA: aggiunto role e bannato alla SELECT ─────────────────────
$stmt = $conn->prepare(
    'SELECT id, nome, cognome, email, verified, created_at, google_id, role, bannato
     FROM utente WHERE google_id = ? OR email = ? LIMIT 1'
);
$stmt->bind_param('ss', $google_id, $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    // ── MODIFICA: blocca il login se l'utente è bannato ───────────────
    if ((int)$row['bannato'] === 1) {
        $conn->close();
        header('Location: ../login.php?msg=accountBannato');
        exit;
    }

    // Collega il Google ID se l'account esisteva solo con email+password
    if (empty($row['google_id'])) {
        $upd = $conn->prepare(
            'UPDATE utente SET google_id = ?, verified = 1 WHERE id = ?'
        );
        $upd->bind_param('si', $google_id, $row['id']);
        $upd->execute();
        $upd->close();
    }
} else {
    // Nuovo utente via Google (role DEFAULT 'user', bannato DEFAULT 0)
    $stmt = $conn->prepare(
        "INSERT INTO utente (nome, cognome, email, password, token, verified, google_id)
         VALUES (?, ?, ?, '', '', 1, ?)"
    );
    $stmt->bind_param('ssss', $nome, $cognome, $email, $google_id);
    $stmt->execute();
    $newId = $conn->insert_id;
    $stmt->close();

    // ── MODIFICA: rilegge anche role e bannato per il nuovo utente ────
    $stmt = $conn->prepare(
        'SELECT id, nome, cognome, email, verified, created_at, role, bannato
         FROM utente WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $newId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();

// 4. Imposta la sessione (con rigenerazione ID)
session_regenerate_id(true);

$_SESSION['login']             = 'ok';
$_SESSION['id_utente']         = $row['id'];
$_SESSION['nome_utente']       = $row['nome'];
$_SESSION['cognome_utente']    = $row['cognome'];
$_SESSION['email_utente']      = $row['email'];
$_SESSION['verified_utente']   = (bool)($row['verified'] ?? true);
$_SESSION['created_at_utente'] = $row['created_at'] ?? null;
// ── MODIFICA: aggiunto role in sessione ───────────────────────────────
$_SESSION['role_utente']       = $row['role'] ?? 'user';

// ── MODIFICA: redirect in base al ruolo ───────────────────────────────
if (($_SESSION['role_utente']) === 'admin') {
    header('Location: ../admin/index.php');
} else {
    header('Location: ../index.php');
}
exit;