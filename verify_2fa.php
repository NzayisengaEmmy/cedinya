<?php
require_once __DIR__ . '/includes/auth.php';

$pendingUserId = (int)($_SESSION['pending_2fa_user_id'] ?? 0);
if ($pendingUserId < 1) {
    header('Location: ' . APP_BASE_URL . '/login');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$pendingUserId]);
$user = $stmt->fetch();
if (!$user || empty($user['totp_enabled']) || empty($user['totp_secret'])) {
    unset($_SESSION['pending_2fa_user_id']);
    header('Location: ' . APP_BASE_URL . '/login');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim((string)($_POST['code'] ?? ''));
    if (verifyTotpCode($user['totp_secret'], $code)) {
        unset($_SESSION['pending_2fa_user_id']);
        finishLogin($user);
        $dashboardPath = dashboardPathForRole((string)$user['role']);
        header('Location: ' . APP_BASE_URL . $dashboardPath);
        exit;
    }
    $error = 'Invalid authenticator code.';
}

$pageTitle = 'Verify Authenticator';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-3">Two-factor verification</h1>
                <p class="text-muted">Enter the six-digit code from Google Authenticator.</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label for="code" class="form-label">Authenticator code</label>
                    <input id="code" type="text" name="code" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Verify and login</button>
                </form>
                <a href="<?= APP_BASE_URL ?>/logout" class="btn btn-link w-100 mt-2">Cancel</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
