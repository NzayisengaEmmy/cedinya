<?php
require_once '../includes/auth.php';
if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'SEI') {
    header('Location: ' . APP_BASE_URL . '/admin/logs');
    exit;
}
requireRole(['headteacher', 'teacher', 'district', 'admin', 'superadmin', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

$now = date('Y-m-d H:i:s');

// Get filter values from GET
$search     = trim($_GET['search'] ?? '');
$year       = trim($_GET['year'] ?? '');
$exam_type  = trim($_GET['exam_type'] ?? '');
$level      = trim($_GET['level'] ?? '');
$subject    = trim($_GET['subject'] ?? '');

// Build dynamic query
$sql = "
    SELECT id, title, original_name, academic_year, exam_type, level, subject, available_until
    FROM exam_files
    WHERE status = 'published'
      AND (available_from IS NULL OR available_from <= ?)
      AND (available_until IS NULL OR available_until >= ?)
";
$params = [$now, $now];
$allowedLevels = roleLevelOptions((string)($_SESSION['role'] ?? ''));
if (isScopedAdminRole((string)($_SESSION['role'] ?? ''))) {
    $sql .= ' AND level IN (' . implode(',', array_fill(0, count($allowedLevels), '?')) . ')';
    $params = array_merge($params, $allowedLevels);
}

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR original_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($year !== '') {
    $sql .= " AND academic_year = ?";
    $params[] = $year;
}
if ($exam_type !== '') {
    $sql .= " AND exam_type = ?";
    $params[] = $exam_type;
}
if ($level !== '') {
    $sql .= " AND level LIKE ?";
    $params[] = "%$level%";
}
if ($subject !== '') {
    $sql .= " AND subject LIKE ?";
    $params[] = "%$subject%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$files = $stmt->fetchAll();

// Get unique values for filter dropdowns
$yearSql = "SELECT DISTINCT academic_year FROM exam_files WHERE status = 'published'";
$yearParams = [];
if (isScopedAdminRole((string)($_SESSION['role'] ?? ''))) {
    $yearSql .= ' AND level IN (' . implode(',', array_fill(0, count($allowedLevels), '?')) . ')';
    $yearParams = $allowedLevels;
}
$yearSql .= ' ORDER BY academic_year DESC';
$yearStmt = $pdo->prepare($yearSql);
$yearStmt->execute($yearParams);
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
$types = ['Question Paper', 'Marking Guide'];

$pageTitle = 'Available Exam Files';
require '../includes/header.php';
?>

<div class="workspace-heading">
    <div>
        <span class="eyebrow">Distribution / Library</span>
        <h2>Available exam papers & guides</h2>
        <p>Find, review and securely download published examination files.</p>
    </div>
    <?php if (in_array(strtolower((string)$_SESSION['role']), ['admin', 'primaryadmin', 'oladmin', 'aladmin', 'tvetadmin', 'tvet'], true)): ?>
        <a href="<?= APP_BASE_URL ?>/admin/upload" class="btn btn-primary">Upload New File</a>
    <?php endif; ?>
</div>

<!-- Search & Filter Form -->
<div class="filter-panel mb-4">
    <div class="filter-panel-header"><strong>Browse files</strong><span><?= count($files) ?> result(s)</span></div>
    <div class="filter-panel-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search title or filename..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Academic year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= htmlspecialchars($y) ?>" <?= $year === $y ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">File type</label>
                <select name="exam_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $exam_type === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Level</label>
                <input type="text" name="level" class="form-control" 
                       placeholder="Level (P6, S3...)" 
                       value="<?= htmlspecialchars($level) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" 
                       placeholder="Subject" 
                       value="<?= htmlspecialchars($subject) ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?= APP_BASE_URL ?>/headteacher/dashboard" class="btn btn-outline-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($files)): ?>
    <div class="alert alert-info">No exam files match your filters.</div>
<?php else: ?>
    <div class="data-panel">
        <div class="data-panel-header"><div><h3>Published files</h3><span>Available for your role and sector</span></div><span class="record-count"><?= count($files) ?> records</span></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Year</th>
                        <th>Type</th>
                        <th>Level</th>
                        <th>Subject</th>
                        <th>Available Until</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($file['title']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($file['original_name']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($file['academic_year']) ?></td>
                        <td><?= htmlspecialchars($file['exam_type']) ?></td>
                        <td><?= htmlspecialchars($file['level'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($file['subject'] ?? '-') ?></td>
                        <td>
                            <?= $file['available_until'] 
                                ? date('d M Y H:i', strtotime($file['available_until'])) 
                                : 'No limit' ?>
                        </td>
                        <td>
                            <a href="<?= APP_BASE_URL ?>/download?id=<?= $file['id'] ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted mt-2"><?= count($files) ?> file(s) found</p>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>