<?php
require_once __DIR__ . '/../php/helpers/session.php';
require_once __DIR__ . '/../php/helpers/csrf.php';
require_once __DIR__ . '/../conf/db_config.php';

session_secure_start();

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    header('Location: ../login.php?msg=errCambioPsw');
    exit;
}

$token_hash = hash('sha256', $token);

$stmt = $conn->prepare(
    'SELECT id, token_expiry FROM utente WHERE token = ? LIMIT 1'
);
$stmt->bind_param('s', $token_hash);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ../login.php?msg=errCambioPsw');
    exit;
}

if ($user['token_expiry'] < date('Y-m-d H:i:s')) {
    header('Location: ../login.php?msg=errCambioPsw');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (empty($password) || empty($confirm)) {
        header('Location: ../php/change_password.php?token=' . urlencode($token) . '&msg=campiMancanti');
        exit;
    }
    if ($password !== $confirm) {
        header('Location: ../php/change_password.php?token=' . urlencode($token) . '&msg=passwordDiverse');
        exit;
    }
    if (strlen($password) < 8) {
        header('Location: ../php/change_password.php?token=' . urlencode($token) . '&msg=passwordCorta');
        exit;
    }

    $psw_hash = password_hash($password, PASSWORD_BCRYPT);

    $update = $conn->prepare(
        'UPDATE utente SET password = ?, token = NULL, token_expiry = NULL WHERE id = ?'
    );
    $update->bind_param('si', $psw_hash, $user['id']);

    if ($update->execute()) {
        $conn->close();
        header('Location: ../login.php?msg=passwordAggiornata');
        exit;
    }

    $conn->close();
    header('Location: ../login.php?msg=errCambioPsw');
    exit;
}

$conn->close();

// Variabili per il template
$page_title       = 'Nuova password';
$page_description = 'Imposta una nuova password per il tuo account Matchora.';
require_once __DIR__ . '/../templates/header.php';
?>
<link rel="stylesheet" href="../css/login.css">

<main class="m-auth-center">
    <div class="m-card" style="max-width:460px; margin:0 auto;">
        <div class="m-auth-center__icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
            </svg>
        </div>
        <h1 style="font-size:26px;">Nuova password</h1>
        <p class="m-muted">Scegli una password sicura di almeno 8 caratteri.</p>

        <?php if (isset($_GET['msg'])): ?>
            <?php
            $msgs = [
                'campiMancanti'   => 'Compila tutti i campi.',
                'passwordDiverse' => 'Le password non coincidono.',
                'passwordCorta'   => 'La password deve avere almeno 8 caratteri.',
            ];
            $msgTxt = $msgs[$_GET['msg']] ?? null;
            ?>
            <?php if ($msgTxt): ?>
                <div class="m-alert m-alert--danger m-mt-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <div><?= htmlspecialchars($msgTxt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="" class="m-stack m-mt-5">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="m-field">
                <label class="m-label" for="password">Nuova password</label>
                <input class="m-input" type="password" id="password" name="password"
                       placeholder="min 8 caratteri" required autofocus>
            </div>
            <div class="m-field">
                <label class="m-label" for="confirm">Conferma password</label>
                <input class="m-input" type="password" id="confirm" name="confirm"
                       placeholder="ripeti la password" required>
            </div>
            <button type="submit" class="m-btn m-btn--primary m-btn--lg m-btn--block">
                Salva nuova password
            </button>
        </form>

        <div class="m-auth-card__footer" style="margin-top:var(--m-6);">
            <a href="../login.php" style="display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Torna al login
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
