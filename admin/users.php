<?php
require_once '../includes/auth.php';
requireRole(['admin', 'superadmin', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

$success = $error = '';
$adminRole = (string)($_SESSION['role'] ?? '');
$isScoped = isScopedAdminRole($adminRole);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $action  = $_POST['action'] ?? '';
    $userId  = (int)($_POST['user_id'] ?? 0);

    if ($userId === $_SESSION['user_id']) {
        $error = 'You cannot modify your own account here.';
    } elseif ($isScoped && $action !== 'toggle_active') {
        $error = 'Scoped administrators may only change access status for eligible users.';
    } else {
        $targetStmt = $pdo->prepare('SELECT u.id, u.role, u.school_id, u.is_active, sc.category AS school_category FROM users u LEFT JOIN schools sc ON sc.id = u.school_id WHERE u.id = ? LIMIT 1');
        $targetStmt->execute([$userId]);
        $targetUser = $targetStmt->fetch();
        if (!$targetUser || ($isScoped && !scopedUserIsEligible($targetUser, $adminRole))) {
            $error = 'This user is outside your assigned level.';
        } elseif ($action === 'toggle_active') {
            if (!$targetUser['is_active'] && !empty($targetUser['school_id'])) {
                try {
                    $pdo->beginTransaction();
                    $deactivateSchoolUsers = $pdo->prepare('UPDATE users SET is_active = 0 WHERE school_id = ? AND id <> ? AND is_active = 1');
                    $deactivateSchoolUsers->execute([(int)$targetUser['school_id'], $userId]);
                    $activateTarget = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?');
                    $activateTarget->execute([$userId]);
                    $pdo->commit();
                    $success = 'User approved. Any previously active user from this school was deactivated.';
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'The user access update could not be completed.';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$userId]);
                $success = 'User status updated.';
            }
        } elseif (!$isScoped && $action === 'change_role') {
            $newRole = $_POST['role'] ?? '';
            $allowedRoles = ['admin', 'primaryadmin', 'OLadmin', 'ALadmin', 'TVETadmin', 'SEI', 'district', 'headteacher', 'teacher'];
            if (in_array($newRole, $allowedRoles)) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                $success = 'User role updated.';
            } else {
                $error = 'Invalid role selected.';
            }
        } elseif (!$isScoped && $action === 'change_sector') {
            $sectorId = (int)($_POST['district_id'] ?? 0);
            $sectorCheck = $pdo->prepare('SELECT 1 FROM sectors WHERE district_id = ?');
            $sectorCheck->execute([$sectorId]);
            if ($sectorCheck->fetchColumn()) {
                $stmt = $pdo->prepare('UPDATE users SET district_id = ? WHERE id = ?');
                $stmt->execute([$sectorId, $userId]);
                $success = 'User sector updated.';
            } else {
                $error = 'Invalid sector selected.';
            }
        }
    }
}

$sectors = $pdo->query('SELECT district_id, sectorname FROM sectors ORDER BY sectorname')->fetchAll();

// Fetch all users
$usersStmt = $pdo->prepare(" 
    SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role, u.district_id, u.school_id, s.sectorname, sc.name AS school_name, sc.category AS school_category,
           u.is_active, u.created_at
    FROM users u
    LEFT JOIN schools sc ON sc.id = u.school_id
        LEFT JOIN sectors s ON s.district_id = COALESCE(sc.district_id, u.district_id)
    ORDER BY u.created_at DESC
");
$usersStmt->execute();
$users = array_values(array_filter($usersStmt->fetchAll(), static fn(array $user): bool => !$isScoped || scopedUserIsEligible($user, $adminRole)));

$pageTitle = 'User Management';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Management</h2>
    <a href="<?= APP_BASE_URL ?>/admin/dashboard" class="btn btn-outline-secondary">← Dashboard</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 user-management-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email / Phone</th>
                    <th>School / Sector</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="<?= $user['is_active'] ? '' : 'table-secondary' ?>">
                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td>
                        <?= htmlspecialchars($user['email'] ?? '-') ?><br>
                        <small class="text-muted"><?= htmlspecialchars($user['phone'] ?? '') ?></small>
                    </td>
                    <td>
                        <?php if (in_array(strtolower((string)$user['role']), ['headteacher', 'teacher'], true)): ?>
                            <?= htmlspecialchars($user['school_name'] ?? 'School not assigned') ?><br>
                            <small class="text-muted"><?= htmlspecialchars($user['sectorname'] ?? 'Sector not assigned') ?></small>
                        <?php elseif ($isScoped): ?>
                            <?= htmlspecialchars($user['sectorname'] ?? '-') ?>
                        <?php else: ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="change_sector">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <select name="district_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Not assigned</option>
                                <?php foreach ($sectors as $sector): ?>
                                    <option value="<?= (int)$sector['district_id'] ?>" <?= (int)$user['district_id'] === (int)$sector['district_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sector['sectorname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isScoped): ?>
                            <?= htmlspecialchars($user['role']) ?>
                        <?php else: ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <select name="role" class="form-select form-select-sm" 
                                    onchange="this.form.submit()" 
                                    <?= $user['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="admin"        <?= $user['role']==='admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="primaryadmin" <?= $user['role']==='primaryadmin' ? 'selected' : '' ?>>Primary Admin</option>
                                <option value="OLadmin"      <?= $user['role']==='OLadmin' ? 'selected' : '' ?>>O-Level Admin</option>
                                <option value="ALadmin"      <?= $user['role']==='ALadmin' ? 'selected' : '' ?>>A-Level Admin</option>
                                <option value="TVETadmin"    <?= $user['role']==='TVETadmin' ? 'selected' : '' ?>>TVET Admin</option>
                                <option value="SEI"          <?= $user['role']==='SEI' ? 'selected' : '' ?>>Sector Education Inspector</option>
                                <option value="district"     <?= $user['role']==='district' ? 'selected' : '' ?>>District</option>
                                <option value="headteacher"  <?= $user['role']==='headteacher' ? 'selected' : '' ?>>Headteacher</option>
                                <option value="teacher"      <?= $user['role']==='teacher' ? 'selected' : '' ?>>Teacher</option>
                            </select>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending approval</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" class="d-inline" 
                              onsubmit="return confirm('Are you sure you want to <?= $user['is_active'] ? 'deactivate' : 'approve' ?> this user?');">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                <?= $user['is_active'] ? 'Deactivate' : 'Approve' ?>
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>