<?php
require_once '../includes/auth.php';
requireRole(['superadmin']);

const SCHOOL_IMPORT_MAX_BYTES = 10 * 1024 * 1024;

$success = '';
$error = '';

function normalizeSchoolHeader(string $header): string
{
    $header = strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
    return trim($header, '_');
}

function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
{
    $value = (string)($cell->v ?? '');
    $type = (string)($cell['t'] ?? '');

    if ($type === 's') {
        return $sharedStrings[(int)$value] ?? '';
    }
    if ($type === 'inlineStr') {
        return trim(implode('', array_map('strval', iterator_to_array($cell->is->t ?? []))));
    }
    return trim($value);
}

function columnNumber(string $reference): int
{
    preg_match('/^[A-Z]+/i', $reference, $matches);
    $letters = strtoupper($matches[0] ?? 'A');
    $number = 0;
    for ($index = 0; $index < strlen($letters); $index++) {
        $number = ($number * 26) + ord($letters[$index]) - 64;
    }
    return $number - 1;
}

function readCsvRows(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('The uploaded file could not be opened.');
    }

    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count(array_filter($row, static fn($value): bool => trim((string)$value) !== '')) > 0) {
            $rows[] = array_map(static fn($value): string => trim((string)$value), $row);
        }
    }
    fclose($handle);
    return $rows;
}

function readXlsxRows(string $path): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('XLSX import requires the PHP ZIP extension to be enabled.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('The Excel workbook could not be opened.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        foreach ($shared->si ?? [] as $item) {
            $sharedStrings[] = trim(implode('', array_map('strval', iterator_to_array($item->t ?? []))));
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('The workbook does not contain a readable first worksheet.');
    }

    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        throw new RuntimeException('The first worksheet is not valid XML.');
    }

    $rows = [];
    foreach ($sheet->sheetData->row ?? [] as $xmlRow) {
        $row = [];
        foreach ($xmlRow->c ?? [] as $cell) {
            $position = columnNumber((string)$cell['r']);
            $row[$position] = cellValue($cell, $sharedStrings);
        }
        if ($row !== []) {
            ksort($row);
            $rows[] = array_map('strval', $row);
        }
    }
    return $rows;
}

function readSchoolRows(string $path, string $extension): array
{
    return $extension === 'csv' ? readCsvRows($path) : readXlsxRows($path);
}

function schoolCategoryParts(string $category): array
{
    return array_values(array_filter(
        preg_split('/\s*(?:\+|,|;|\/|\||&|\band\b)\s*/i', trim($category)) ?: [],
        static fn(string $part): bool => trim($part) !== ''
    ));
}

if (($_GET['download'] ?? '') === 'school-template') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="school-list-template.csv"');
    header('Cache-Control: no-store');
    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['name', 'sectorname', 'code', 'category', 'combination_trade']);
    fclose($output);
    exit;
}

function validateSchoolRows(array $rawRows, PDO $pdo): array
{
    if (count($rawRows) < 2) {
        throw new RuntimeException('The file must contain a header row and at least one school.');
    }

    $headers = array_map('normalizeSchoolHeader', array_shift($rawRows));
    $headerMap = array_flip($headers);
    if (!isset($headerMap['name'])) {
        throw new RuntimeException('The file must include a name column.');
    }
    if (!isset($headerMap['district_id']) && !isset($headerMap['sector_id']) && !isset($headerMap['sector']) && !isset($headerMap['sectorname'])) {
        throw new RuntimeException('The file must include district_id, sector_id, sector, or sectorname.');
    }

    $sectors = $pdo->query('SELECT district_id, sectorname FROM sectors')->fetchAll(PDO::FETCH_KEY_PAIR);
    $sectorIdsByName = [];
    foreach ($sectors as $sectorId => $sectorName) {
        $sectorIdsByName[strtolower(trim($sectorName))] = (int)$sectorId;
    }

    $validated = [];
    foreach ($rawRows as $line => $row) {
        $lineNumber = $line + 2;
        $name = trim((string)($row[$headerMap['name']] ?? ''));
        $code = trim((string)($row[$headerMap['code'] ?? -1] ?? ''));
        $category = trim((string)($row[$headerMap['category'] ?? -1] ?? ''));
        $combinationTradeHeader = $headerMap['combination_trade'] ?? $headerMap['combination'] ?? $headerMap['trade'] ?? -1;
        $combinationTrade = trim((string)($row[$combinationTradeHeader] ?? ''));
        $sectorValue = trim((string)($row[$headerMap['district_id'] ?? $headerMap['sector_id'] ?? $headerMap['sector'] ?? $headerMap['sectorname']] ?? ''));

        if ($name === '' || $sectorValue === '') {
            throw new RuntimeException("Row $lineNumber must include a school name and sector.");
        }
        if (ctype_digit($sectorValue) && isset($sectors[(int)$sectorValue]) ) {
            $districtId = (int)$sectorValue;
        } elseif (isset($sectorIdsByName[strtolower($sectorValue)])) {
            $districtId = $sectorIdsByName[strtolower($sectorValue)];
        } else {
            throw new RuntimeException("Row $lineNumber has an unknown sector.");
        }
        $schoolCategories = normalizeSchoolCategories($category);
        if ($category !== '' && (count($schoolCategories) !== count(array_unique(array_map(
            static fn(string $part): string => strtolower(trim($part)),
            schoolCategoryParts($category)
        ))) || $schoolCategories === [])) {
            throw new RuntimeException("Row $lineNumber has an invalid category.");
        }
        $validated[] = [$name, $districtId, $code !== '' ? $code : null, $schoolCategories ? implode(',', $schoolCategories) : null, $combinationTrade !== '' ? $combinationTrade : null];
    }
    return $validated;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? null);
        if (!isset($_FILES['school_list']) || $_FILES['school_list']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please select an Excel or CSV school list.');
        }
        $file = $_FILES['school_list'];
        if ($file['size'] > SCHOOL_IMPORT_MAX_BYTES) {
            throw new RuntimeException('The school list must be 10 MB or smaller.');
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            throw new RuntimeException('Only .xlsx and .csv files are supported.');
        }

        $rows = validateSchoolRows(readSchoolRows($file['tmp_name'], $extension), $pdo);
        $pdo->beginTransaction();
        $upsert = $pdo->prepare('INSERT INTO schools (name, district_id, code, category, combination_trade) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), district_id = VALUES(district_id), category = VALUES(category), combination_trade = VALUES(combination_trade)');
        foreach ($rows as [$name, $districtId, $code, $category, $combinationTrade]) {
            $upsert->execute([$name, $districtId, $code, $category, $combinationTrade]);
        }
        $pdo->commit();
        $success = count($rows) . ' school(s) imported successfully.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Import Schools';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Import School List</h2>
    <a href="<?= APP_BASE_URL ?>/admin/dashboard" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p class="text-muted">Start with the template to keep the column names correct. Open it in Excel, add your schools, save it as CSV or XLSX, then upload it.</p>
        <a href="?download=school-template" class="btn btn-outline-success mb-3">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel Template
        </a>
        <p class="small text-muted">Required: <code>name</code> and <code>sectorname</code>. Optional: <code>code</code>, <code>category</code> (for example <code>primary + ordinary</code>; also supports advanced and tvet), and <code>combination_trade</code>.</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <label class="form-label" for="school_list">School list</label>
            <input type="file" name="school_list" id="school_list" class="form-control" accept=".xlsx,.csv" required>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-upload"></i> Import School List
            </button>
        </form>
    </div>
</div>

<?php require '../includes/footer.php'; ?>