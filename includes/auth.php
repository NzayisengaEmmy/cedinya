<?php
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appRoot = realpath(dirname(__DIR__));
$relativeAppPath = ($documentRoot !== false && $appRoot !== false
    && stripos($appRoot, $documentRoot) === 0)
    ? substr($appRoot, strlen($documentRoot))
    : '';
define('APP_BASE_URL', rtrim('/' . trim(str_replace('\\', '/', $relativeAppPath), '/'), '/'));

session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function base32Encode(string $value): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (unpack('C*', $value) as $byte) {
        $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
    }

    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $encoded;
}

function base32Decode(string $value): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $value = strtoupper(rtrim($value, '='));
    $bits = '';
    foreach (str_split($value) as $character) {
        $position = strpos($alphabet, $character);
        if ($position === false) {
            return '';
        }
        $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
    }

    $decoded = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $decoded .= chr(bindec($byte));
        }
    }
    return $decoded;
}

function generateTotpSecret(): string {
    return base32Encode(random_bytes(20));
}

function verifyTotpCode(string $secret, string $code, ?int $timestamp = null): bool {
    if (!preg_match('/\A\d{6}\z/', $code)) {
        return false;
    }

    $key = base32Decode($secret);
    if ($key === '') {
        return false;
    }

    $counter = intdiv($timestamp ?? time(), 30);
    for ($offset = -1; $offset <= 1; $offset++) {
        $data = pack('N2', ($counter + $offset) >> 32, ($counter + $offset) & 0xffffffff);
        $hash = hash_hmac('sha1', $data, $key, true);
        $index = ord($hash[19]) & 0x0f;
        $binaryCode = ((ord($hash[$index]) & 0x7f) << 24)
            | ((ord($hash[$index + 1]) & 0xff) << 16)
            | ((ord($hash[$index + 2]) & 0xff) << 8)
            | (ord($hash[$index + 3]) & 0xff);
        $expected = str_pad((string)($binaryCode % 1000000), 6, '0', STR_PAD_LEFT);
        if (hash_equals($expected, $code)) {
            return true;
        }
    }
    return false;
}

function finishLogin(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['school_id'] = $user['school_id'];
    $_SESSION['district_id'] = $user['district_id'] ?? null;
    $_SESSION['sector_name'] = $user['sector_name'] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_BASE_URL . '/login');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        die('<h2>Access Denied</h2><p>You do not have permission to view this page.</p>');
    }
}

function roleLevelOptions(string $role): array
{
    return match (strtolower($role)) {
        'admin', 'superadmin' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'L3', 'L4', 'L5'],
        'primaryadmin' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'],
        'oladmin' => ['S1', 'S2', 'S3'],
        'aladmin' => ['S4', 'S5', 'S6'],
        'tvetadmin', 'tvet' => ['L3', 'L4', 'L5'],
        default => [],
    };
}

function isScopedAdminRole(string $role): bool
{
    return roleLevelOptions($role) !== [] && !in_array(strtolower($role), ['admin', 'superadmin'], true);
}

function normalizeSchoolCategory(?string $category): string
{
    return normalizeSchoolCategories($category)[0] ?? 'uncategorised';
}

function normalizeSchoolCategories(?string $category): array
{
    $parts = preg_split('/\s*(?:\+|,|;|\/|\||&|\band\b)\s*/i', trim((string)$category)) ?: [];
    $categories = [];
    foreach ($parts as $part) {
        $value = preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim($part))) ?? '';
        $normalized = match (trim($value)) {
            'primary', 'primary level', 'primaryadmin' => 'primary',
            'ordinary', 'ordinary level', 'ol', 'ol level', 'oladmin' => 'ordinary',
            'advanced', 'advanced level', 'al', 'al level', 'aladmin' => 'advanced',
            'tvet', 'tvetadmin' => 'tvet',
            default => null,
        };
        if ($normalized !== null && !in_array($normalized, $categories, true)) {
            $categories[] = $normalized;
        }
    }

    $order = ['primary', 'ordinary', 'advanced', 'tvet'];
    usort($categories, static fn(string $left, string $right): int => array_search($left, $order, true) <=> array_search($right, $order, true));
    return $categories;
}

function schoolCategoryLevels(?string $category): array
{
    $levels = [];
    foreach (normalizeSchoolCategories($category) as $schoolCategory) {
        $levels = array_merge($levels, match ($schoolCategory) {
            'primary' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'],
            'ordinary' => ['S1', 'S2', 'S3'],
            'advanced' => ['S4', 'S5', 'S6'],
            'tvet' => ['L3', 'L4', 'L5'],
        });
    }
    return array_values(array_unique($levels));
}

function scopedUserIsEligible(array $user, string $adminRole): bool
{
    $adminLevels = roleLevelOptions($adminRole);
    if (!isScopedAdminRole($adminRole) || $adminLevels === []) {
        return false;
    }

    $targetRole = (string)($user['role'] ?? '');
    if (in_array(strtolower($targetRole), ['admin', 'superadmin', 'sei', 'primaryadmin', 'oladmin', 'aladmin', 'tvetadmin', 'tvet'], true)) {
        return false;
    }

    $targetLevels = roleLevelOptions($targetRole);
    if ($targetLevels !== []) {
        return (bool)array_intersect($adminLevels, $targetLevels);
    }

    return (bool)array_intersect($adminLevels, schoolCategoryLevels($user['school_category'] ?? null));
}

function dashboardPathForRole(string $role): string
{
    return strtoupper($role) === 'SEI'
        ? '/admin/logs'
        : (in_array(strtolower($role), ['admin', 'superadmin'], true) || isScopedAdminRole($role) ? '/admin/dashboard' : '/headteacher/dashboard');
}

function currentUser(): ?array {
    global $pdo;
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.email, u.role, u.school_id, u.district_id, u.totp_enabled, s.sectorname AS sector_name FROM users u LEFT JOIN sectors s ON s.district_id = u.district_id WHERE u.id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: ' . APP_BASE_URL . '/login');
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): void
{
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid request token.');
    }
}