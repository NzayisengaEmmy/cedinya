<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Exam Distribution System' ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Your custom CSS -->
    <link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/css/style.css?v=20260927-2">
</head>
<body>
<?php if (isLoggedIn()): ?>
<?php $currentRole = (string)($_SESSION['role'] ?? ''); ?>
<?php $isAdminDashboard = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/dashboard') !== false; ?>
<?php $isSectorMonitoring = (($_SESSION['role'] ?? '') === 'SEI' || isScopedAdminRole($currentRole)) && strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/logs') !== false; ?>
<?php $isAssignedSchools = strtoupper($currentRole) === 'SEI' && strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/schools') !== false; ?>
<div class="app-shell">
    <aside class="app-sidebar" id="appSidebar">
        <div class="brand-lockup">
            <span class="brand-mark"><i class="bi bi-bar-chart-line-fill"></i></span>
            <span>CEDINYA</span>
            <button class="sidebar-toggle d-lg-none" type="button" data-sidebar-toggle aria-label="Close navigation">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="term-switcher">
            <span class="term-icon"><i class="bi bi-calendar3"></i></span>
            <span><strong>Academic year</strong><small><?= date('Y') ?>/<?= date('Y') + 1 ?></small></span>
            <i class="bi bi-chevron-down ms-auto"></i>
        </div>
        <p class="sidebar-label">Workspace</p>
        <nav class="sidebar-nav">
            <a class="<?= $isAdminDashboard || $isSectorMonitoring ? 'active' : '' ?>" href="<?= APP_BASE_URL ?><?= dashboardPathForRole($currentRole) ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
            <?php if (strtoupper($currentRole) === 'SEI'): ?>
                <a class="<?= $isSectorMonitoring ? 'active' : '' ?>" href="<?= APP_BASE_URL ?>/admin/logs"><i class="bi bi-graph-up-arrow"></i> Download Monitoring</a>
                <a class="<?= $isAssignedSchools ? 'active' : '' ?>" href="<?= APP_BASE_URL ?>/admin/schools"><i class="bi bi-building"></i> Assigned Schools</a>
            <?php endif; ?>
            <?php if (in_array(strtolower($currentRole), ['admin', 'superadmin'], true)): ?>
                <a href="<?= APP_BASE_URL ?>/admin/upload"><i class="bi bi-cloud-arrow-up"></i> Upload Exam</a>
                <a href="<?= APP_BASE_URL ?>/admin/files"><i class="bi bi-folder2-open"></i> Manage Exams</a>
                <a href="<?= APP_BASE_URL ?>/admin/logs"><i class="bi bi-graph-up-arrow"></i> Download Monitoring</a>
                <a href="<?= APP_BASE_URL ?>/admin/users"><i class="bi bi-people"></i> Manage Users &amp; Schools</a>
                <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
                    <a href="<?= APP_BASE_URL ?>/admin/schools"><i class="bi bi-building"></i> Manage Schools</a>
                    <a href="<?= APP_BASE_URL ?>/admin/import-schools"><i class="bi bi-building-add"></i> Import Schools</a>
                <?php endif; ?>
                <a href="<?= APP_BASE_URL ?>/headteacher/dashboard"><i class="bi bi-download"></i> Download Exams</a>
            <?php elseif (isScopedAdminRole($currentRole)): ?>
                <a href="<?= APP_BASE_URL ?>/admin/upload"><i class="bi bi-cloud-arrow-up"></i> Upload Exam</a>
                <a href="<?= APP_BASE_URL ?>/admin/files"><i class="bi bi-folder2-open"></i> Manage Exams</a>
                <a href="<?= APP_BASE_URL ?>/admin/logs"><i class="bi bi-graph-up-arrow"></i> Download Monitoring</a>
                <a href="<?= APP_BASE_URL ?>/admin/users"><i class="bi bi-people"></i> Manage Level Users</a>
                <a href="<?= APP_BASE_URL ?>/headteacher/dashboard"><i class="bi bi-download"></i> Download Exams</a>
            <?php else: ?>
                <a class="active" href="<?= APP_BASE_URL ?>/headteacher/dashboard"><i class="bi bi-file-earmark-text"></i> Exams distribution</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span class="user-avatar"><?= strtoupper(substr((string)($_SESSION['full_name'] ?? 'U'), 0, 1)) ?></span>
                <span><strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong><small><?= htmlspecialchars(ucfirst((string)($_SESSION['role'] ?? 'user'))) ?></small></span>
            </div>
            <a class="logout-link" href="<?= APP_BASE_URL ?>/logout"><i class="bi bi-box-arrow-right"></i> Log out</a>
        </div>
    </aside>
    <div class="app-panel">
        <header class="app-topbar">
            <button class="sidebar-toggle d-lg-none" type="button" data-sidebar-toggle aria-label="Open navigation"><i class="bi bi-list"></i></button>
            <div>
                <h1><?= htmlspecialchars($pageTitle ?? 'Exam Distribution') ?></h1>
                <p>Exam distribution workspace</p>
            </div>
            <div class="topbar-meta"><span class="status-dot"></span> <?= htmlspecialchars($_SESSION['sector_name'] ?? 'Central workspace') ?></div>
        </header>
        <main class="app-content">
<?php else: ?>
<main class="public-content container py-4">
<?php endif; ?>