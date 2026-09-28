<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_BASE_URL . dashboardPathForRole((string)$_SESSION['role']));
} else {
    header('Location: ' . APP_BASE_URL . '/login');
}
exit;