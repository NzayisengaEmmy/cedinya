<?php
require_once '../includes/auth.php';
requireRole(['admin', 'superadmin', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

$role = (string)($_SESSION['role'] ?? '');
$allowedLevels = roleLevelOptions($role);
$isScoped = isScopedAdminRole($role);
$levelPlaceholders = implode(',', array_fill(0, count($allowedLevels), '?'));
$levelFilter = $isScoped ? " WHERE level IN ($levelPlaceholders)" : '';
$downloadFilter = $isScoped ? " AND f.level IN ($levelPlaceholders)" : '';
$categoryNames = ['primary', 'ordinary', 'advanced', 'tvet'];
$allowedCategories = array_values(array_filter($categoryNames, static fn(string $category): bool => (bool)array_intersect($allowedLevels, schoolCategoryLevels($category))));
$categoryConditions = array_map(static fn(string $category): string => "FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(sc.category), ' ', ''), '+', ','), ';', ','), '/', ','), '|', ','), '&', ',')) > 0", $allowedCategories);
$userFilter = $isScoped ? ' WHERE ' . implode(' OR ', $categoryConditions) : '';
$statsStmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM exam_files$levelFilter) AS total_files,
    (SELECT COUNT(*) FROM access_logs l JOIN exam_files f ON f.id = l.file_id WHERE l.action = 'download'$downloadFilter) AS total_downloads,
    (SELECT COUNT(*) FROM users u LEFT JOIN schools sc ON sc.id = u.school_id$userFilter) AS total_users");
$statsStmt->execute(array_merge($isScoped ? $allowedLevels : [], $isScoped ? $allowedLevels : [], $isScoped ? $allowedCategories : []));
$stats = $statsStmt->fetch();
$totalFiles = (int)$stats['total_files'];
$totalDownloads = (int)$stats['total_downloads'];
$totalUsers = (int)$stats['total_users'];

$pageTitle = 'Admin Dashboard';
require '../includes/header.php';

?>

<div class="workspace-heading">
    <div>
        <span class="eyebrow">Administration / Overview</span>
        <h2><?= $isScoped ? htmlspecialchars(ucfirst(strtolower($role))) . ' dashboard' : 'Distribution control centre' ?></h2>
        <p><?= $isScoped ? 'Manage examination files, downloads and eligible users within your assigned level.' : 'Monitor files, downloads, users and school data from one workspace.' ?></p>
    </div>
</div>

<?php if ($isScoped): ?>
<div class="mb-4">
    <a href="<?= APP_BASE_URL ?>/admin/users" class="btn btn-outline-primary">Manage level user access</a>
    <a href="<?= APP_BASE_URL ?>/admin/logs" class="btn btn-outline-secondary">View download monitoring</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-4 dashboard-stats">
    <div class="col-md-4">
        <div class="stat-panel stat-panel-blue">
            <span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
            <div><span class="stat-label">Total files</span><strong><?= $totalFiles ?></strong><small>Published and managed files</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-panel stat-panel-green">
            <span class="stat-icon"><i class="bi bi-download"></i></span>
            <div><span class="stat-label">Total downloads</span><strong><?= $totalDownloads ?></strong><small>Recorded access events</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-panel stat-panel-amber">
            <span class="stat-icon"><i class="bi bi-people"></i></span>
            <div><span class="stat-label">Registered users</span><strong><?= $totalUsers ?></strong><small>Accounts in the system</small></div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>