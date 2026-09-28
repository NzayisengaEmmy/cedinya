<?php
require_once '../includes/auth.php';
requireRole(['superadmin', 'SEI']);

$isSuperadmin = ($_SESSION['role'] ?? '') === 'superadmin';

$success = '';
$error = '';
$editingSchool = null;
$formValues = [
    'name' => '',
    'district_id' => '',
    'code' => '',
    'category' => '',
    'combination_trade' => '',
];

function schoolManagementCategoryParts(string $category): array
{
    return array_values(array_filter(
        preg_split('/\s*(?:\+|,|;|\/|\||&|\band\b)\s*/i', trim($category)) ?: [],
        static fn(string $part): bool => trim($part) !== ''
    ));
}

function validateSchoolManagementInput(array $input, PDO $pdo): array
{
    $name = trim((string)($input['name'] ?? ''));
    $districtId = (int)($input['district_id'] ?? 0);
    $code = trim((string)($input['code'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $combinationTrade = trim((string)($input['combination_trade'] ?? ''));

    if ($name === '' || strlen($name) > 255) {
        throw new RuntimeException('School name is required and must be 255 characters or fewer.');
    }
    if ($code !== '' && strlen($code) > 100) {
        throw new RuntimeException('School code must be 100 characters or fewer.');
    }
    if ($combinationTrade !== '' && strlen($combinationTrade) > 150) {
        throw new RuntimeException('Combination or trade must be 150 characters or fewer.');
    }

    $sectorCheck = $pdo->prepare('SELECT 1 FROM sectors WHERE district_id = ? LIMIT 1');
    $sectorCheck->execute([$districtId]);
    if (!$sectorCheck->fetchColumn()) {
        throw new RuntimeException('Select a valid sector.');
    }

    $categoryParts = schoolManagementCategoryParts($category);
    $categories = normalizeSchoolCategories($category);
    $rawCategories = array_map(static fn(string $part): string => strtolower(trim($part)), $categoryParts);
    if ($category !== '' && ($categories === [] || count($categories) !== count(array_unique($rawCategories)))) {
        throw new RuntimeException('Category must use primary, ordinary, advanced, tvet, or a combination separated by +, comma, /, &, or and.');
    }

    return [
        $name,
        $districtId,
        $code !== '' ? $code : null,
        $categories ? implode(',', $categories) : null,
        $combinationTrade !== '' ? $combinationTrade : null,
    ];
}

$sectors = $isSuperadmin
    ? $pdo->query('SELECT district_id, sectorname FROM sectors ORDER BY sectorname')->fetchAll()
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSuperadmin) {
        http_response_code(403);
        die('<h2>Access Denied</h2><p>You do not have permission to change schools.</p>');
    }
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    $schoolId = (int)($_POST['school_id'] ?? 0);

    try {
        if ($action === 'save') {
            $formValues = [
                'name' => trim((string)($_POST['name'] ?? '')),
                'district_id' => (string)($_POST['district_id'] ?? ''),
                'code' => trim((string)($_POST['code'] ?? '')),
                'category' => trim((string)($_POST['category'] ?? '')),
                'combination_trade' => trim((string)($_POST['combination_trade'] ?? '')),
            ];
            [$name, $districtId, $code, $category, $combinationTrade] = validateSchoolManagementInput($formValues, $pdo);
            if ($schoolId < 1) {
                throw new RuntimeException('The selected school is invalid.');
            }

            $update = $pdo->prepare('UPDATE schools SET name = ?, district_id = ?, code = ?, category = ?, combination_trade = ? WHERE id = ?');
            $update->execute([$name, $districtId, $code, $category, $combinationTrade, $schoolId]);
            if ($update->rowCount() < 1) {
                $exists = $pdo->prepare('SELECT 1 FROM schools WHERE id = ? LIMIT 1');
                $exists->execute([$schoolId]);
                if (!$exists->fetchColumn()) {
                    throw new RuntimeException('The selected school could not be found.');
                }
            }
            $success = 'School details updated successfully.';
            $formValues = ['name' => '', 'district_id' => '', 'code' => '', 'category' => '', 'combination_trade' => ''];
        } elseif ($action === 'delete') {
            if ($schoolId < 1) {
                throw new RuntimeException('The selected school is invalid.');
            }
            $assignedUsers = $pdo->prepare('SELECT COUNT(*) FROM users WHERE school_id = ?');
            $assignedUsers->execute([$schoolId]);
            $assignedCount = (int)$assignedUsers->fetchColumn();
            if ($assignedCount > 0) {
                throw new RuntimeException("This school cannot be deleted because it has $assignedCount assigned user(s). Reassign them first.");
            }

            $delete = $pdo->prepare('DELETE FROM schools WHERE id = ?');
            $delete->execute([$schoolId]);
            $success = $delete->rowCount() === 1 ? 'School deleted successfully.' : 'The selected school could not be found.';
        }
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        $error = 'The school change could not be completed.';
    }
}

$editId = (int)($_GET['edit'] ?? 0);
if ($isSuperadmin && $editId > 0 && $error === '') {
    $editStmt = $pdo->prepare('SELECT id, name, district_id, code, category, combination_trade FROM schools WHERE id = ? LIMIT 1');
    $editStmt->execute([$editId]);
    $editingSchool = $editStmt->fetch() ?: null;
    if ($editingSchool) {
        $formValues = $editingSchool;
    } elseif (!$success) {
        $error = 'The selected school could not be found.';
    }
}

$schoolsQuery = 'SELECT s.id, s.name, s.district_id, s.code, s.category, s.combination_trade, sec.sectorname, COUNT(u.id) AS assigned_users FROM schools s LEFT JOIN sectors sec ON sec.district_id = s.district_id LEFT JOIN users u ON u.school_id = s.id';
if ($isSuperadmin) {
    $schoolsQuery .= ' GROUP BY s.id, s.name, s.district_id, s.code, s.category, s.combination_trade, sec.sectorname ORDER BY sec.sectorname, s.name';
    $schoolsStmt = $pdo->query($schoolsQuery);
} else {
    $schoolsQuery .= ' WHERE s.district_id = ? GROUP BY s.id, s.name, s.district_id, s.code, s.category, s.combination_trade, sec.sectorname ORDER BY s.name';
    $schoolsStmt = $pdo->prepare($schoolsQuery);
    $schoolsStmt->execute([(int)($_SESSION['district_id'] ?? 0)]);
}
$schools = $schoolsStmt->fetchAll();

$pageTitle = $isSuperadmin ? 'Manage Schools' : 'Assigned Schools';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($pageTitle) ?></h2>
    <?php if ($isSuperadmin): ?>
    <div>
        <a href="<?= APP_BASE_URL ?>/admin/import-schools" class="btn btn-primary me-2"><i class="bi bi-upload"></i> Import Schools</a>
        <a href="<?= APP_BASE_URL ?>/admin/dashboard" class="btn btn-outline-secondary">Dashboard</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($isSuperadmin && $editingSchool): ?>
<div class="card mb-4">
    <div class="card-body">
        <h3 class="h5 mb-3">Edit School</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="school_id" value="<?= (int)$editingSchool['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">School name</label><input class="form-control" id="name" name="name" required maxlength="255" value="<?= htmlspecialchars((string)$formValues['name']) ?>"></div>
                <div class="col-md-6"><label class="form-label" for="district_id">Sector</label><select class="form-select" id="district_id" name="district_id" required><option value="">Select sector...</option><?php foreach ($sectors as $sector): ?><option value="<?= (int)$sector['district_id'] ?>" <?= (int)$formValues['district_id'] === (int)$sector['district_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sector['sectorname']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label" for="code">School code</label><input class="form-control" id="code" name="code" maxlength="100" value="<?= htmlspecialchars((string)$formValues['code']) ?>"></div>
                <div class="col-md-4"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" maxlength="100" value="<?= htmlspecialchars((string)$formValues['category']) ?>"><small class="text-muted">Example: primary + ordinary</small></div>
                <div class="col-md-4"><label class="form-label" for="combination_trade">Combination / trade</label><input class="form-control" id="combination_trade" name="combination_trade" maxlength="150" value="<?= htmlspecialchars((string)$formValues['combination_trade']) ?>"></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2"></i> Save Changes</button>
            <a href="<?= APP_BASE_URL ?>/admin/schools" class="btn btn-outline-secondary mt-3">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>School</th><th>Sector</th><th>Code</th><th>Category</th><th>Combination / Trade</th><th>Assigned users</th><?php if ($isSuperadmin): ?><th>Actions</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($schools as $school): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($school['name']) ?></strong></td>
                    <td><?= htmlspecialchars($school['sectorname'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($school['code'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($school['category'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($school['combination_trade'] ?? '-') ?></td>
                    <td><?= (int)$school['assigned_users'] ?></td>
                    <?php if ($isSuperadmin): ?><td class="text-nowrap"><a href="?edit=<?= (int)$school['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a><?php if ((int)$school['assigned_users'] === 0): ?><form method="POST" class="d-inline" onsubmit="return confirm('Delete this school? This cannot be undone.');"><input type="hidden" name="action" value="delete"><input type="hidden" name="school_id" value="<?= (int)$school['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form><?php else: ?><span class="text-muted small ms-1">Reassign users to delete</span><?php endif; ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$schools): ?><tr><td colspan="<?= $isSuperadmin ? 7 : 6 ?>" class="text-center text-muted py-4">No schools found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>