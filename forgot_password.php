<?php
require_once __DIR__ . '/includes/auth.php';

$message = '';
if (empty($_SESSION['forgot_password_csrf'])) {
    $_SESSION['forgot_password_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['forgot_password_csrf'], $csrfToken)) {
        http_response_code(400);
        $message = 'The request could not be verified. Refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $rateLimit = $pdo->prepare(
                    'SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)'
                );
                $rateLimit->execute([$user['id']]);

                if (!$rateLimit->fetchColumn()) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $saveToken = $pdo->prepare(
                        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
                         ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = NOW()'
                    );
                    $saveToken->execute([$user['id'], $tokenHash]);

                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    if (!preg_match('/\A[a-zA-Z0-9.-]+(?::[0-9]{1,5})?\z/', $host)) {
                        $host = 'localhost';
                    }
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $resetUrl = $scheme . '://' . $host . APP_BASE_URL . '/reset-password?token=' . urlencode($token);
                    $subject = 'Reset your Exam Distribution password';
                    $body = "A password reset was requested for your account.\n\n"
                        . "Use this link within one hour to choose a new password:\n$resetUrl\n\n"
                        . "If you did not request this change, you can ignore this email.";
                    $from = getenv('APP_MAIL_FROM') ?: 'no-reply@localhost';
                    $from = str_replace(["\r", "\n"], '', $from);
                    $headers = "From: Exam Distribution <$from>\r\nContent-Type: text/plain; charset=UTF-8";

                    if (!@mail($user['email'], $subject, $body, $headers)) {
                        error_log('Password reset email delivery failed for user ID ' . (int) $user['id']);
                    }
                }
            }
        }

        // Do not reveal whether the email belongs to an account.
        $message = 'If an active account matches that email address, a password reset link will be sent. Check your inbox and spam folder.';
        $_SESSION['forgot_password_csrf'] = bin2hex(random_bytes(32));
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-3">Reset your password</h1>
                <p class="text-muted">Enter the email address linked to your account. We will send a one-time reset link if it matches an active account.</p>

                <?php if ($message): ?>
                    <div class="alert alert-info" role="status"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_BASE_URL ?>/forgot-password">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['forgot_password_csrf']) ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email" class="form-control" autocomplete="email" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send reset link</button>
                </form>
                <div class="text-center mt-3"><a href="<?= APP_BASE_URL ?>/login">Back to login</a></div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
