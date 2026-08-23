<?php
// =====================================================================
// includes/auth.php
//
// Protects pages from unauthenticated access.
//
// Usage:
//     require_once __DIR__ . '/config/db.php';
//     require_once __DIR__ . '/includes/auth.php';
//
// For admin-only pages:
//     require_once __DIR__ . '/config/db.php';
//     require_once __DIR__ . '/includes/auth.php';
//     requireAdmin();
// =====================================================================


// ---------------------------------------------------------------------
// 1. BASE_URL check
// ---------------------------------------------------------------------
if (!defined('BASE_URL')) {
    http_response_code(500);
    die(
        'Configuration error: BASE_URL not defined. ' .
        'Include config/db.php before includes/auth.php.'
    );
}


// ---------------------------------------------------------------------
// 2. Start session
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ---------------------------------------------------------------------
// 3. Authentication check
// ---------------------------------------------------------------------
// If the user is not logged in, redirect to login page.
if (!isset($_SESSION['user_id'])) {

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}


// ---------------------------------------------------------------------
// 4. Make sure role exists
// ---------------------------------------------------------------------
// Existing sessions created before role support may not have
// $_SESSION['role']. Default them to "user" for safety.
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}


// ---------------------------------------------------------------------
// 5. Helper: Check if current user is admin
// ---------------------------------------------------------------------
function isAdmin(): bool
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}


// ---------------------------------------------------------------------
// 6. Helper: Protect admin-only pages
// ---------------------------------------------------------------------
function requireAdmin(): void
{
    if (!isAdmin()) {

        http_response_code(403);

        die(
            '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Access Denied</title>

                <link
                    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
                    rel="stylesheet"
                >

                <link
                    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css"
                    rel="stylesheet"
                >
            </head>

            <body class="bg-light">

                <div class="container">
                    <div class="row justify-content-center align-items-center"
                         style="min-height: 100vh;">

                        <div class="col-md-6">

                            <div class="card border-0 shadow-sm text-center">

                                <div class="card-body p-5">

                                    <div class="mb-3">
                                        <i
                                            class="bi bi-shield-lock text-danger"
                                            style="font-size: 4rem;"
                                        ></i>
                                    </div>

                                    <h2 class="mb-3">
                                        Access Denied
                                    </h2>

                                    <p class="text-muted">
                                        You do not have permission to access
                                        this page.
                                    </p>

                                    <a
                                        href="' . BASE_URL . '/index.php"
                                        class="btn btn-primary"
                                    >
                                        <i class="bi bi-house"></i>
                                        Back to Dashboard
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </body>
            </html>'
        );
    }
}