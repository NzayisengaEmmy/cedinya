<?php
require_once '../includes/auth.php';
requireRole(['admin', 'superadmin', 'SEI', 'Primaryadmin', 'primaryadmin', 'OLadmin', 'oladmin', 'ALadmin', 'aladmin', 'TVETadmin', 'TVET', 'tvetadmin', 'tvet']);

function logQueryParts(): array
{
    $where = [];
    $params = [];

    $role = (string)($_SESSION['role'] ?? '');
    if ($role === 'SEI') {
        $where[] = "u.district_id = ?";
        $params[] = (int)($_SESSION['district_id'] ?? 0);
        $where[] = "l.action = 'download'";
    } elseif (isScopedAdminRole($role)) {
        $allowedLevels = roleLevelOptions($role);
        $where[] = "l.action = 'download'";
        $where[] = 'f.level IN (' . implode(',', array_fill(0, count($allowedLevels), '?')) . ')';
        $params = array_merge($params, $allowedLevels);
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function fetchLogs(PDO $pdo, string $where, array $params, ?int $limit = null, int $offset = 0): array
{
    $sql = "
    SELECT l.*, u.full_name, s.name AS school_name, f.title as file_title
    FROM access_logs l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN schools s ON s.id = u.school_id
    JOIN exam_files f ON l.file_id = f.id
    $where
    ORDER BY l.created_at DESC, l.id DESC";
    if ($limit !== null) {
        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function exportLogText(?string $value): string
{
    $text = trim((string)$value);
    $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
    return $converted === false ? $text : $converted;
}

function csvLogCell(?string $value): string
{
    $text = (string)$value;
    return preg_match('/^[=+\-@]/', $text) === 1 ? "'" . $text : $text;
}

[$logWhere, $logParams] = logQueryParts();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM access_logs l JOIN users u ON l.user_id = u.id JOIN exam_files f ON l.file_id = f.id" . $logWhere);
$countStmt->execute($logParams);
$totalLogs = (int)$countStmt->fetchColumn();

$export = strtolower(trim((string)($_GET['export'] ?? '')));
if (in_array($export, ['csv', 'pdf'], true)) {
    $exportLogs = fetchLogs($pdo, $logWhere, $logParams);
    $filename = 'access_logs_' . date('Y-m-d') . '.' . $export;

    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Date & Time', 'User', 'School', 'File', 'Action', 'IP Address']);
        foreach ($exportLogs as $log) {
            fputcsv($output, [
                csvLogCell($log['created_at']),
                csvLogCell($log['full_name']),
                csvLogCell($log['school_name'] ?? 'Not assigned'),
                csvLogCell($log['file_title']),
                csvLogCell($log['action']),
                csvLogCell($log['ip_address']),
            ]);
        }
        fclose($output);
        exit;
    }

    require_once '../vendor/autoload.php';
    if (!function_exists('get_magic_quotes_runtime')) {
        function get_magic_quotes_runtime(): bool
        {
            return false;
        }
    }
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 9, exportLogText('Download & Access Logs'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 6, exportLogText('Exported ' . date('Y-m-d H:i:s')), 0, 1, 'C');
    $pdf->Ln(3);
    $headers = ['Date & Time', 'User', 'School', 'File', 'Action', 'IP Address'];
    $widths = [38, 42, 38, 105, 25, 29];
    $pdf->SetFont('Arial', 'B', 8);
    foreach ($headers as $index => $header) {
        $pdf->Cell($widths[$index], 7, exportLogText($header), 1, 0, 'C');
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7);
    foreach ($exportLogs as $log) {
        $values = [
            date('d M Y H:i:s', strtotime($log['created_at'])),
            $log['full_name'],
            $log['school_name'] ?? 'Not assigned',
            $log['file_title'],
            ucfirst($log['action']),
            $log['ip_address'],
        ];
        foreach ($values as $index => $value) {
            $pdf->Cell($widths[$index], 6, substr(exportLogText($value), 0, $index === 3 ? 65 : 28), 1);
        }
        $pdf->Ln();
    }
    $pdf->Output('D', $filename);
    exit;
}

$perPage = 5;
$totalPages = max(1, (int)ceil($totalLogs / $perPage));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) ?: 1;
$page = min($page, $totalPages);
$logs = fetchLogs($pdo, $logWhere, $logParams, $perPage, ($page - 1) * $perPage);

$monitoringCategories = [
    'primary' => ['label' => 'Primary', 'downloaded' => [], 'not_downloaded' => []],
    'ordinary' => ['label' => 'Ordinary Level', 'downloaded' => [], 'not_downloaded' => []],
    'advanced' => ['label' => 'Advanced Level', 'downloaded' => [], 'not_downloaded' => []],
    'tvet' => ['label' => 'TVET', 'downloaded' => [], 'not_downloaded' => []],
    'uncategorised' => ['label' => 'Uncategorised', 'downloaded' => [], 'not_downloaded' => []],
];
$selectedMonitoringCategory = trim((string)($_GET['monitor_category'] ?? 'all'));
if ($selectedMonitoringCategory !== 'all' && !array_key_exists($selectedMonitoringCategory, $monitoringCategories)) {
    $selectedMonitoringCategory = 'all';
}
$monitoringPerPage = 10;
$monitoringPage = filter_input(INPUT_GET, 'monitor_page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) ?: 1;
if (($_SESSION['role'] ?? '') === 'SEI') {
    $schoolStmt = $pdo->prepare("
        SELECT s.id, s.name, s.code, s.category, COUNT(DISTINCT l.id) AS download_count
        FROM schools s
        LEFT JOIN users u ON u.school_id = s.id AND u.district_id = ?
        LEFT JOIN access_logs l ON l.user_id = u.id AND l.action = 'download'
        WHERE s.district_id = ?
        GROUP BY s.id, s.name, s.code, s.category
        ORDER BY s.name ASC
    ");
    $sectorId = (int)($_SESSION['district_id'] ?? 0);
    $schoolStmt->execute([$sectorId, $sectorId]);
    foreach ($schoolStmt->fetchAll() as $school) {
        $status = (int)$school['download_count'] > 0 ? 'downloaded' : 'not_downloaded';
        $categories = normalizeSchoolCategories($school['category'] ?? null);
        foreach ($categories ?: ['uncategorised'] as $category) {
            $monitoringCategories[$category][$status][] = $school;
        }
    }
}

function monitoringSchoolLabel(array $school): string
{
    $label = htmlspecialchars((string)$school['name']);
    if (!empty($school['code'])) {
        $label .= ' (' . htmlspecialchars((string)$school['code']) . ')';
    }
    return $label;
}

function renderMonitoringSchools(array $schools, bool $showCount): void
{
    if (!$schools) {
        echo '<p class="text-muted mb-0">None.</p>';
        return;
    }

    echo '<ul class="list-group list-group-flush">';
    foreach ($schools as $school) {
        echo '<li class="list-group-item px-0' . ($showCount ? ' d-flex justify-content-between' : '') . '">';
        echo '<span>' . monitoringSchoolLabel($school) . '</span>';
        if ($showCount) {
            $count = (int)$school['download_count'];
            echo '<small class="text-muted">' . $count . ' download' . ($count === 1 ? '' : 's') . '</small>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

function renderMonitoringPagination(int $totalPages, int $currentPage, string $categoryKey): void
{
    if ($totalPages <= 1) {
        return;
    }

    echo '<nav class="mt-2" aria-label="School list pages"><ul class="pagination pagination-sm mb-0">';
    for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
        $query = http_build_query([
            'monitor_category' => $categoryKey,
            'monitor_page' => $pageNumber,
        ]);
        $active = $pageNumber === $currentPage ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . htmlspecialchars($query) . '">' . $pageNumber . '</a></li>';
    }
    echo '</ul></nav>';
}

$pageTitle = ($_SESSION['role'] ?? '') === 'SEI' || isScopedAdminRole((string)($_SESSION['role'] ?? '')) ? 'Download Monitoring' : 'Access Logs';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= ($_SESSION['role'] ?? '') === 'SEI' || isScopedAdminRole((string)($_SESSION['role'] ?? '')) ? 'Download Monitoring' : 'Download & Access Logs' ?></h2>
    <a href="<?= APP_BASE_URL . dashboardPathForRole((string)($_SESSION['role'] ?? '')) ?>" class="btn btn-outline-secondary">← Dashboard</a>
</div>

<?php if (($_SESSION['role'] ?? '') === 'SEI'): ?>
    <p class="text-muted">Showing downloads recorded for schools in <?= htmlspecialchars($_SESSION['sector_name'] ?? 'your assigned sector') ?>.</p>

    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-sm-6 col-md-4">
            <label for="monitor_category" class="form-label">School category</label>
            <select name="monitor_category" id="monitor_category" class="form-select" onchange="this.form.submit()">
                <option value="all" <?= $selectedMonitoringCategory === 'all' ? 'selected' : '' ?>>All categories</option>
                <?php foreach ($monitoringCategories as $categoryKey => $category): ?>
                    <option value="<?= htmlspecialchars($categoryKey) ?>" <?= $selectedMonitoringCategory === $categoryKey ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <?php foreach ($monitoringCategories as $categoryKey => $category): ?>
            <?php if ($selectedMonitoringCategory !== 'all' && $selectedMonitoringCategory !== $categoryKey) { continue; } ?>
            <?php
            $downloadedPages = max(1, (int)ceil(count($category['downloaded']) / $monitoringPerPage));
            $notDownloadedPages = max(1, (int)ceil(count($category['not_downloaded']) / $monitoringPerPage));
            $categoryPages = max($downloadedPages, $notDownloadedPages);
            $categoryPage = min($monitoringPage, $categoryPages);
            $downloadedOffset = ($categoryPage - 1) * $monitoringPerPage;
            $notDownloadedOffset = ($categoryPage - 1) * $monitoringPerPage;
            ?>
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <strong><?= htmlspecialchars($category['label']) ?></strong>
                </div>
                <div class="card-body row g-3">
                    <div class="col-lg-6">
                        <h6 class="text-success">Downloaded <span class="badge bg-success"><?= count($category['downloaded']) ?></span></h6>
                        <?php renderMonitoringSchools(array_slice($category['downloaded'], $downloadedOffset, $monitoringPerPage), true); ?>
                        <?php renderMonitoringPagination($downloadedPages, $categoryPage, $categoryKey); ?>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-warning-emphasis">Not downloaded <span class="badge bg-warning text-dark"><?= count($category['not_downloaded']) ?></span></h6>
                        <?php renderMonitoringSchools(array_slice($category['not_downloaded'], $notDownloadedOffset, $monitoringPerPage), false); ?>
                        <?php renderMonitoringPagination($notDownloadedPages, $categoryPage, $categoryKey); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group" role="group" aria-label="Export access logs">
        <a href="?export=csv" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Excel (CSV)</a>
        <a href="?export=pdf" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    </div>
    <small class="text-muted">
        <?= $totalLogs === 0 ? 'No log records' : 'Showing ' . (($page - 1) * $perPage + 1) . '-' . min($page * $perPage, $totalLogs) . ' of ' . $totalLogs . ' records' ?>
    </small>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>School</th>
                    <th>File</th>
                    <th>Action</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs): ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <?= htmlspecialchars($log['full_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($log['school_name'] ?? 'Not assigned') ?></td>
                        <td><?= htmlspecialchars($log['file_title']) ?></td>
                        <td>
                            <span class="badge bg-<?= $log['action'] === 'download' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($log['action']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No access log records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3" aria-label="Access log pages">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">&laquo;</a>
            </li>
            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $pageNumber ?>"><?= $pageNumber ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">&raquo;</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>