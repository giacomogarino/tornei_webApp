<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * AGGIORNA_PROFILO.PHP — Gestione modifiche profilo utente
 * =========================================================
 * Gestisce tre azioni distinte (action nel POST):
 *   - anagrafica  → aggiorna nome e cognome
 *   - password    → aggiorna la password (con verifica password attuale)
 */

require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
require_once __DIR__ . '/../conf/db_config.php';
require_once __DIR__ . '/../conf/app_config.php';

session_secure_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profilo.php');
    exit;
}

csrf_verify();

$user_id = $_SESSION['id_utente'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

$action = $_POST['action'] ?? '';  

if ($action === 'password') {
    $stmt_g = $conn->prepare('SELECT password FROM utente WHERE id = ? LIMIT 1');
    $stmt_g->bind_param('i', $user_id);
    $stmt_g->execute();
    $row_g = $stmt_g->get_result()->fetch_assoc();
    $stmt_g->close();

    if (empty($row_g['password'])) {
        header('Location: ../profilo.php?msg=errore');
        exit;
    }
}

// ── 1. AGGIORNA ANAGRAFICA (nome + cognome) ─────────────────────────
if ($action === 'anagrafica') {
    $nome    = trim($_POST['nome']    ?? '');
    $cognome = trim($_POST['cognome'] ?? '');

    if ($nome === '' || $cognome === '') {
        header('Location: ../profilo.php?msg=campiMancanti');
        exit;
    }
    if (mb_strlen($nome) > 50 || mb_strlen($cognome) > 50) {
        header('Location: ../profilo.php?msg=errore');
        exit;
    }

    $stmt = $conn->prepare('UPDATE utente SET nome = ?, cognome = ? WHERE id = ?');
    $stmt->bind_param('ssi', $nome, $cognome, $user_id);

    if ($stmt->execute()) {
        $_SESSION['nome_utente']    = $nome;
        $_SESSION['cognome_utente'] = $cognome;
        $stmt->close();
        $conn->close();
        header('Location: ../profilo.php?msg=anagraficaOk');
        exit;
    }

    $stmt->close();
    $conn->close();
    header('Location: ../profilo.php?msg=errore');
    exit;
}

// ── 3. AGGIORNA PASSWORD ────────────────────────────────────────────
if ($action === 'password') {
    $psw_attuale = $_POST['psw_attuale']  ?? '';
    $nuova_psw   = $_POST['nuova_psw']    ?? '';
    $conferma    = $_POST['conferma_psw'] ?? '';

    if ($psw_attuale === '' || $nuova_psw === '' || $conferma === '') {
        header('Location: ../profilo.php?msg=campiMancanti');
        exit;
    }
    if ($nuova_psw !== $conferma) {
        header('Location: ../profilo.php?msg=passwordDiverse');
        exit;
    }
    if (strlen($nuova_psw) < 8) {
        header('Location: ../profilo.php?msg=passwordCorta');
        exit;
    }

    $stmt = $conn->prepare('SELECT password FROM utente WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($psw_attuale, $row['password'])) {
        header('Location: ../profilo.php?msg=passwordErrata');
        exit;
    }

    $nuovo_hash = password_hash($nuova_psw, PASSWORD_BCRYPT);
    $stmt2 = $conn->prepare('UPDATE utente SET password = ? WHERE id = ?');
    $stmt2->bind_param('si', $nuovo_hash, $user_id);

    if ($stmt2->execute()) {
        $stmt2->close();
        $conn->close();
        header('Location: ../profilo.php?msg=passwordOk');
        exit;
    }

    $stmt2->close();
    $conn->close();
    header('Location: ../profilo.php?msg=errore');
    exit;
}

header('Location: ../profilo.php?msg=errore');
exit;