<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? trim($_POST['token'] ?? '')
    : trim($_GET['token'] ?? '');
$tokenIsWellFormed = (bool) preg_match('/\A[a-f0-9]{64}\z/i', $token);
$error = '';
$success = false;

if (empty($_SESSION['reset_password_csrf'])) {
    $_SESSION['reset_password_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['reset_password_csrf'], $csrfToken)) {
        http_response_code(400);
        $error = 'The request could not be verified. Refresh the page and try again.';
    } elseif (!$tokenIsWellFormed) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();
            $lookup = $pdo->prepare(
                'SELECT pr.user_id FROM password_resets pr
                 JOIN users u ON u.id = pr.user_id AND u.is_active = 1
                 WHERE pr.token_hash = ? AND pr.expires_at > NOW() FOR UPDATE'
            );
            $lookup->execute([hash('sha256', $token)]);
            $reset = $lookup->fetch();

            if (!$reset) {
                $pdo->rollBack();
                $error = 'This reset link is invalid or has expired. Request a new one.';
            } else {
                $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND is_active = 1');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
                $delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $delete->execute([$reset['user_id']]);
                $pdo->commit();
                $success = true;
                $_SESSION['reset_password_csrf'] = bin2hex(random_bytes(32));
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Password reset failed: ' . $exception->getMessage());
            $error = 'The password could not be changed. Please request a new reset link.';
        }
    }
}

$tokenIsValid = false;
if (!$success && $tokenIsWellFormed) {
    $check = $pdo->prepare('SELECT 1 FROM password_resets WHERE token_hash = ? AND expires_at > NOW()');
    $check->execute([hash('sha256', $token)]);
    $tokenIsValid = (bool) $check->fetchColumn();
}

$pageTitle = 'Choose a New Password';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-3">Choose a new password</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="status">Your password has been reset. You can now sign in with your new password.</div>
                    <a href="<?= APP_BASE_URL ?>/login" class="btn btn-primary w-100">Go to login</a>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($tokenIsValid): ?>
                        <form method="POST" action="<?= APP_BASE_URL ?>/reset-password">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['reset_password_csrf']) ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New password</label>
                                <input id="password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required autofocus>
                                <div class="form-text">Use at least 8 characters.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm-password" class="form-label">Confirm new password</label>
                                <input id="confirm-password" type="password" name="confirm_password" class="form-control" minlength="8" autocomplete="new-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Save new password</button>
                        </form>
                    <?php elseif (!$error): ?>
                        <div class="alert alert-warning">This reset link is invalid or has expired. Request a new one.</div>
                    <?php endif; ?>

                    <div class="text-center mt-3"><a href="<?= APP_BASE_URL ?>/forgot-password">Request another reset link</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
