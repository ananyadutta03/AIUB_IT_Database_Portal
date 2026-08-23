<?php
// Shared page header. Expects:
//   $pageTitle (string, optional)
//   $hideSidebar (bool, optional) — set true to skip the left sidebar
//   $pdo (PDO instance, required if sidebar is shown)
//
// Always include AFTER config/db.php and includes/auth.php.

$pageTitle   = $pageTitle ?? 'AIUB IT Database Portal';
$hideSidebar = $hideSidebar ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> — AIUB IT Database Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .navbar-brand { font-weight: 600; }
        .app-sidebar {
            width: 260px;
            min-width: 260px;
            background: #fff;
            border-right: 1px solid #dee2e6;
            min-height: calc(100vh - 56px);
        }
        .app-sidebar .sheet-link {
            color: #495057;
            font-size: 0.9rem;
            padding: 0.45rem 0.9rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            margin-bottom: 2px;
        }
        .app-sidebar .sheet-link:hover { background: #f0f3f7; color: #0d6efd; }
        .app-sidebar .sheet-link.active {
            background: #e7f1ff; color: #0d6efd; font-weight: 500;
        }
        .app-sidebar .sheet-link .badge { font-size: 0.7rem; }
        .app-content { flex-grow: 1; padding: 1.5rem 2rem; }
        .result-card { transition: box-shadow 0.15s; }
        .result-card:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.08) !important; }
        .field-label {
            color: #6c757d; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.03em;
        }
        .field-value { font-size: 0.95rem; word-break: break-word; }
        .sidebar-section-title {
            font-size: 0.7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #6c757d; padding: 0 0.9rem; margin-top: 1rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
        <i class="bi bi-hdd-network"></i> AIUB IT Database Portal
    </a>
    <div>
    <span class="text-light me-3 small">
        Hello, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
    </span>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/users.php"
           class="btn btn-sm btn-outline-light me-2">
            <i class="bi bi-people"></i> User Management
        </a>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/change_password.php"
       class="btn btn-sm btn-outline-light me-2">
        <i class="bi bi-key"></i> Change Password
    </a>

    <a href="<?= BASE_URL ?>/logout.php"
       class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>
</nav>

<div class="d-flex">
    <?php if (!$hideSidebar): require __DIR__ . '/sidebar.php'; endif; ?>
    <main class="app-content">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= htmlspecialchars($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
