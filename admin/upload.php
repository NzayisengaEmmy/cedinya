<?php
require_once '../includes/auth.php';
requireRole(['admin', 'superadmin', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

$allowedLevels = roleLevelOptions((string)($_SESSION['role'] ?? ''));
$examTypeOptions = ['Question Paper', 'Marking Guide'];

$success = $error = '';
$exam_type = '';
$level = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $academic_year  = trim($_POST['academic_year'] ?? '');
    $exam_type      = trim($_POST['exam_type'] ?? '');
    $level          = trim($_POST['level'] ?? '');
    $subject        = trim($_POST['subject'] ?? '');
    $available_from = $_POST['available_from'] ?: null;
    $available_until= $_POST['available_until'] ?: null;

    if (empty($title) || empty($academic_year) || empty($exam_type)) {
        $error = 'Title, Academic Year and Exam Type are required';
    } elseif (!in_array($exam_type, $examTypeOptions, true)) {
        $error = 'Select a valid exam type';
    } elseif (!in_array($level, $allowedLevels, true)) {
        $error = 'Select a valid level for your administrator role';
    } elseif (!isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid file';
    } else {
        $file = $_FILES['exam_file'];
        $allowedTypes = ['application/pdf'];
        $maxSize = 25 * 1024 * 1024; // 25 MB

        $detectedType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($detectedType, $allowedTypes, true)) {
            $error = 'Only PDF files are allowed';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File is too large (max 25MB)';
        } else {
            $newName = 'exam_' . bin2hex(random_bytes(16)) . '.pdf';
            $uploadDir = __DIR__ . '/../uploads/';
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $stmt = $pdo->prepare("
                    INSERT INTO exam_files 
                    (title, description, academic_year, exam_type, level, subject, 
                     file_name, original_name, file_size, mime_type, 
                     available_from, available_until, uploaded_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
                ");
                $stmt->execute([
                    $title, $description, $academic_year, $exam_type, $level, $subject,
                    $newName, $file['name'], $file['size'], $detectedType,
                    $available_from, $available_until, $_SESSION['user_id']
                ]);
                $success = 'Exam file uploaded successfully!';
            } else {
                $error = 'Failed to move uploaded file';
            }
        }
    }
}

$pageTitle = 'Upload Exam File';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Upload Exam Paper / Marking Guide</h2>
    <a href="<?= APP_BASE_URL . dashboardPathForRole((string)$_SESSION['role']) ?>" class="btn btn-outline-secondary">← Back to Dashboard</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required 
                           placeholder="e.g. Mathematics Paper 1 - P6 2025/2026">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Academic Year *</label>
                    <input type="text" name="academic_year" class="form-control" required 
                           placeholder="2025/2026" value="<?= date('Y') ?>/<?= date('Y')+1 ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Type *</label>
                    <select name="exam_type" class="form-select" required>
                        <option value="">Select...</option>
                        <?php foreach ($examTypeOptions as $examTypeOption): ?>
                            <option value="<?= htmlspecialchars($examTypeOption) ?>" <?= $exam_type === $examTypeOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($examTypeOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select" required>
                        <option value="">Select level...</option>
                        <?php foreach ($allowedLevels as $allowedLevel): ?>
                            <option value="<?= htmlspecialchars($allowedLevel) ?>" <?= $level === $allowedLevel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($allowedLevel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Mathematics, English...">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Available From</label>
                    <input type="datetime-local" name="available_from" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Available Until</label>
                    <input type="datetime-local" name="available_until" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Exam File (PDF only) *</label>
                    <input type="file" name="exam_file" class="form-control" accept=".pdf,application/pdf" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-upload"></i> Upload File
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require '../includes/footer.php'; ?>