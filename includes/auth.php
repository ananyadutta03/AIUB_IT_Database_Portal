<?php
// =====================================================================
// includes/auth.php — protect a page from unauthenticated access.
// Usage at the top of every protected PHP file:
//     require_once __DIR__ . '/config/db.php';
//     require_once __DIR__ . '/includes/auth.php';
// =====================================================================

if (!defined('BASE_URL')) {
    // db.php must be required before this file (it defines BASE_URL).
    http_response_code(500);
    die('Configuration error: BASE_URL not defined. Include config/db.php first.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
