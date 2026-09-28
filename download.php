<?php
require_once 'includes/auth.php';
requireLogin();
if (!function_exists('get_magic_quotes_runtime')) {
    function get_magic_quotes_runtime(): bool {
        return false;
    }
}
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

class WatermarkedPdf extends Fpdi {
    private float $rotation = 0;

    public function rotate(float $angle, float $x = -1, float $y = -1): void {
        if ($x < 0) {
            $x = $this->x;
        }
        if ($y < 0) {
            $y = $this->y;
        }
        if ($this->rotation !== 0) {
            $this->_out('Q');
        }
        $this->rotation = $angle;
        if ($angle !== 0) {
            $radians = $angle * M_PI / 180;
            $cosine = cos($radians);
            $sine = sin($radians);
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm',
                $cosine,
                $sine,
                -$sine,
                $cosine,
                $x * $this->k,
                ($this->h - $y) * $this->k
            ));
            $this->_out(sprintf(
                '1 0 0 1 %.2F %.2F cm',
                -$x * $this->k,
                -($this->h - $y) * $this->k
            ));
        }
    }

    protected function _endpage(): void {
        if ($this->rotation !== 0) {
            $this->rotation = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}

$fileId = (int)($_GET['id'] ?? 0);
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("
    SELECT * FROM exam_files 
    WHERE id = ? AND status = 'published'
      AND (available_from IS NULL OR available_from <= ?)
      AND (available_until IS NULL OR available_until >= ?)
");
$stmt->execute([$fileId, $now, $now]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('File not found or no longer available.');
}

$allowedLevels = roleLevelOptions((string)($_SESSION['role'] ?? ''));
if ($allowedLevels !== [] && !in_array((string)$file['level'], $allowedLevels, true)) {
    http_response_code(403);
    die('You do not have permission to download this file.');
}

$path = __DIR__ . '/uploads/' . $file['file_name'];

if (!file_exists($path)) {
    die('File missing on server.');
}

$user = currentUser();
$downloadedAt = date('Y-m-d H:i:s');
$downloaderName = trim((string)($user['full_name'] ?? $user['username'] ?? '')) ?: 'User';
$footerText = 'Downloaded by: ' . $downloaderName . ' | Date: ' . $downloadedAt;
$footerText = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $footerText) ?: $footerText;
$temporaryPath = tempnam(sys_get_temp_dir(), 'exam_download_');

if ($temporaryPath === false) {
    http_response_code(500);
    die('Unable to prepare the downloaded file.');
}

try {
    $pdf = new WatermarkedPdf();
    $pdf->SetAutoPageBreak(false);
    $pageCount = $pdf->setSourceFile($path);

    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $templateId = $pdf->importPage($pageNumber);
        $pageSize = $pdf->getTemplateSize($templateId);
        $pdf->AddPage($pageSize['orientation'], [$pageSize['width'], $pageSize['height']]);
        $pdf->useTemplate($templateId);
        $pdf->SetFont('Arial', 'B', 42);
        $pdf->SetTextColor(225, 225, 225);
        $watermarkWidth = $pdf->GetStringWidth('CEDINYA');
        $pdf->rotate(45, $pageSize['width'] / 2, $pageSize['height'] / 2);
        $pdf->SetXY(($pageSize['width'] - $watermarkWidth) / 2, ($pageSize['height'] / 2) - 12);
        $pdf->Cell($watermarkWidth, 24, 'CEDINYA', 0, 0, 'C');
        $pdf->rotate(0);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(10, $pageSize['height'] - 12);
        $pdf->Cell(
            $pageSize['width'] - 20,
            8,
            $footerText,
            0,
            0,
            'C'
        );
    }

    $pdf->Output('F', $temporaryPath);

    $log = $pdo->prepare("
        INSERT INTO access_logs (file_id, user_id, action, ip_address, user_agent)
        VALUES (?, ?, 'download', ?, ?)
    ");
    $log->execute([
        $fileId,
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
} catch (Throwable $exception) {
    @unlink($temporaryPath);
    error_log('Exam PDF stamping failed: ' . $exception->getMessage());
    http_response_code(500);
    die('Unable to prepare the downloaded file.');
}

$downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string)$file['original_name']));
$downloadName = $downloadName ?: 'exam.pdf';
if (strtolower(pathinfo($downloadName, PATHINFO_EXTENSION)) !== 'pdf') {
    $downloadName .= '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($temporaryPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($temporaryPath);
@unlink($temporaryPath);
exit;