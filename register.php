<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_BASE_URL . '/');
    exit;
}

$success = $error = '';
$sectorId = (int)($_POST['district_id'] ?? 0);
$sectors = $pdo->query('SELECT district_id, sectorname FROM sectors ORDER BY sectorname')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = 'headteacher'; // default role

    $validSectorIds = array_column($sectors, 'district_id');

    if (!in_array($sectorId, array_map('intval', $validSectorIds), true)) {
        $error = 'Please select a valid sector';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role, district_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->execute([$username, $hash, $full_name, $email, $phone, $role, $sectorId]);
            $success = 'Account created successfully! A superadmin must approve your account before you can login.';
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? 'Username already exists' : 'Registration failed';
        }
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4">Create Account</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <div class="text-center"><a href="<?= APP_BASE_URL ?>/login" class="btn btn-primary">Go to Login</a></div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username (Staff / Teacher ID)</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="district_id">Sector Name</label>
                            <select name="district_id" id="district_id" class="form-select" required>
                                <option value="">Select your sector...</option>
                                <?php foreach ($sectors as $sector): ?>
                                    <option value="<?= (int)$sector['district_id'] ?>" <?= $sectorId === (int)$sector['district_id'] ? 'selected' : '' ?> >
                                        <?= htmlspecialchars($sector['sectorname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="<?= APP_BASE_URL ?>/login">Already have an account? Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>