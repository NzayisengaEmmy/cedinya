<?php
require_once '../includes/auth.php';
requireRole(['admin', 'superadmin', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

$success = $error = '';

// Handle revoke / restore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $fileId = (int)($_POST['file_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $fileCheck = $pdo->prepare('SELECT level FROM exam_files WHERE id = ? LIMIT 1');
    $fileCheck->execute([$fileId]);
    $fileLevel = $fileCheck->fetchColumn();
    $allowedLevels = roleLevelOptions((string)($_SESSION['role'] ?? ''));
    if ($fileLevel === false || ($allowedLevels !== [] && !in_array((string)$fileLevel, $allowedLevels, true))) {
        http_response_code(403);
        die('You do not have permission to manage this file.');
    }

    if ($action === 'revoke') {
        $stmt = $pdo->prepare("UPDATE exam_files SET status = 'revoked' WHERE id = ?");
        $stmt->execute([$fileId]);
        $success = 'File has been revoked. It is no longer downloadable.';
    } elseif ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE exam_files SET status = 'published' WHERE id = ?");
        $stmt->execute([$fileId]);
        $success = 'File has been restored and is now available again.';
    }
}

// Fetch all files
$fileSql = "
    SELECT f.*, u.full_name as uploaded_by_name
    FROM exam_files f
    LEFT JOIN users u ON f.uploaded_by = u.id";
$fileParams = [];
$allowedLevels = roleLevelOptions((string)($_SESSION['role'] ?? ''));
if ($allowedLevels !== [] && strtolower((string)($_SESSION['role'] ?? '')) !== 'admin' && strtolower((string)($_SESSION['role'] ?? '')) !== 'superadmin') {
    $fileSql .= ' WHERE f.level IN (' . implode(',', array_fill(0, count($allowedLevels), '?')) . ')';
    $fileParams = $allowedLevels;
}
$fileSql .= ' ORDER BY f.created_at DESC';
$fileStmt = $pdo->prepare($fileSql);
$fileStmt->execute($fileParams);
$files = $fileStmt->fetchAll();

$pageTitle = 'Manage Exam Files';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Exam Files</h2>
    <div>
        <a href="<?= APP_BASE_URL ?>/admin/upload" class="btn btn-primary me-2">+ Upload New</a>
        <a href="<?= APP_BASE_URL . dashboardPathForRole((string)$_SESSION['role']) ?>" class="btn btn-outline-secondary">← Dashboard</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Year / Type</th>
                    <th>Level / Subject</th>
                    <th>Status</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <tr class="<?= $file['status'] === 'revoked' ? 'table-danger' : '' ?>">
                    <td>
                        <strong><?= htmlspecialchars($file['title']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($file['original_name']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($file['academic_year']) ?><br>
                        <small><?= htmlspecialchars($file['exam_type']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($file['level'] ?? '-') ?><br>
                        <small><?= htmlspecialchars($file['subject'] ?? '-') ?></small>
                    </td>
                    <td>
                        <?php if ($file['status'] === 'published'): ?>
                            <span class="badge bg-success">Published</span>
                        <?php elseif ($file['status'] === 'revoked'): ?>
                            <span class="badge bg-danger">Revoked</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= ucfirst($file['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($file['uploaded_by_name'] ?? 'Unknown') ?></td>
                    <td><?= date('d M Y', strtotime($file['created_at'])) ?></td>
                    <td>
                        <?php if ($file['status'] === 'published'): ?>
                            <form method="POST" class="d-inline" 
                                  onsubmit="return confirm('Revoke this file? Users will no longer be able to download it.');">
                                <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <button type="submit" class="btn btn-sm btn-success">Restore</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>