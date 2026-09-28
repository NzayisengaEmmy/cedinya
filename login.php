<?php

require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $dashboardPath = dashboardPathForRole((string)$_SESSION['role']);
    header('Location: ' . APP_BASE_URL . $dashboardPath);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT u.*, s.sectorname AS sector_name FROM users u LEFT JOIN sectors s ON s.district_id = u.district_id WHERE u.username = ? AND u.is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (!empty($user['totp_enabled']) && !empty($user['totp_secret'])) {
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            header('Location: ' . APP_BASE_URL . '/verify-2fa');
            exit;
        }

        finishLogin($user);

        $dashboardPath = dashboardPathForRole((string)$user['role']);
        header('Location: ' . APP_BASE_URL . $dashboardPath);
        exit;
    }
    $error = 'Invalid username or password';
}

$pageTitle = 'Login';
require 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4">Login</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username (Staff ID)</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="text-end mb-3">
                        <a href="<?= APP_BASE_URL ?>/forgot-password">Forgot your password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= APP_BASE_URL ?>/register">Create new account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>