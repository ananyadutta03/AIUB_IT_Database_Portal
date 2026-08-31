<?php
// =====================================================================
// includes/auth.php
//
// Authentication + Role-Based Authorization
//
// Roles:
//     admin  = Full access
//     user   = Normal record access
//     viewer = Read-only access
//
// Usage:
//
// For any logged-in page:
//     require_once __DIR__ . '/config/db.php';
//     require_once __DIR__ . '/includes/auth.php';
//
// For admin-only pages:
//     requireAdmin();
//
// For pages that allow Admin/User/Viewer:
//     requireViewerOrAbove();
//
// For create pages:
//     requireCreatePermission();
//
// For edit pages:
//     requireEditPermission();
//
// For delete pages:
//     requireDeletePermission();
//
// For user-management pages:
//     requireUserManagement();
//
// For sheet-management pages:
//     requireSheetManagement();
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
//
// If the user is not logged in, redirect to login page.
//
if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ' .
        BASE_URL .
        '/login.php'
    );

    exit;
}


// ---------------------------------------------------------------------
// 4. Make sure role exists
// ---------------------------------------------------------------------
//
// Existing sessions created before Viewer role support may not have
// $_SESSION['role'].
//
// Defaulting to "user" keeps backward compatibility.
//
// IMPORTANT:
// New logins should always load the actual role from the database.
//
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}


// ---------------------------------------------------------------------
// 5. Normalize role
// ---------------------------------------------------------------------
//
// Prevent unexpected values from being treated as valid roles.
//
$_SESSION['role'] = strtolower(
    trim(
        (string) $_SESSION['role']
    )
);


// ---------------------------------------------------------------------
// 6. Current user role
// ---------------------------------------------------------------------

function currentRole(): string
{
    return (string) (
        $_SESSION['role'] ?? ''
    );
}


// ---------------------------------------------------------------------
// 7. Check if user is Admin
// ---------------------------------------------------------------------

function isAdmin(): bool
{
    return currentRole() === 'admin';
}


// ---------------------------------------------------------------------
// 8. Check if user is normal User
// ---------------------------------------------------------------------

function isUser(): bool
{
    return currentRole() === 'user';
}


// ---------------------------------------------------------------------
// 9. Check if user is Viewer
// ---------------------------------------------------------------------

function isViewer(): bool
{
    return currentRole() === 'viewer';
}


// ---------------------------------------------------------------------
// 10. Check if logged in
// ---------------------------------------------------------------------

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


// ---------------------------------------------------------------------
// 11. Get current user information
// ---------------------------------------------------------------------

function currentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,

        'username' =>
            $_SESSION['username'] ?? '',

        'full_name' =>
            $_SESSION['full_name'] ?? '',

        'role' =>
            currentRole(),
    ];
}


// =====================================================================
// PERMISSION FUNCTIONS
// =====================================================================


// ---------------------------------------------------------------------
// 12. Can View
// ---------------------------------------------------------------------
//
// Admin + User + Viewer
//
function canView(): bool
{
    return in_array(
        currentRole(),
        [
            'admin',
            'user',
            'viewer'
        ],
        true
    );
}


// ---------------------------------------------------------------------
// 13. Can Create
// ---------------------------------------------------------------------
//
// Admin + User
//
// Viewer CANNOT create records.
//
function canCreate(): bool
{
    return in_array(
        currentRole(),
        ['admin', 'user'],
        true
    );
}


function canEdit(): bool
{
    return in_array(
        currentRole(),
        ['admin', 'user'],
        true
    );
}


function canDelete(): bool
{
    return in_array(
        currentRole(),
        ['admin', 'user'],
        true
    );
}


// ---------------------------------------------------------------------
// 16. Can Manage Users
// ---------------------------------------------------------------------
//
// Only Admin.
//
function canManageUsers(): bool
{
    return isAdmin();
}


// ---------------------------------------------------------------------
// 17. Can Manage Sheets
// ---------------------------------------------------------------------
//
// Only Admin.
//
function canManageSheets(): bool
{
    return isAdmin();
}


// =====================================================================
// ACCESS CONTROL FUNCTIONS
// =====================================================================


// ---------------------------------------------------------------------
// 18. Generic 403 Access Denied Page
// ---------------------------------------------------------------------

function accessDenied(string $message = 'You do not have permission to access this page.'): void
{
    http_response_code(403);

    die(
        '<!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1"
            >

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

                <div
                    class="row justify-content-center align-items-center"
                    style="min-height: 100vh;"
                >

                    <div class="col-md-6">

                        <div
                            class="card border-0 shadow-sm text-center"
                        >

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

                                    ' .
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . '

                                </p>


                                <a
                                    href="' .
        BASE_URL .
        '/index.php"
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


// ---------------------------------------------------------------------
// 19. Require Admin
// ---------------------------------------------------------------------
//
// Admin only.
//
function requireAdmin(): void
{
    if (!isAdmin()) {

        accessDenied(
            'Administrator access is required.'
        );
    }
}


// ---------------------------------------------------------------------
// 20. Require Viewer or Above
// ---------------------------------------------------------------------
//
// Admin + User + Viewer.
//
// Use this on pages that only display records/details.
//
function requireViewerOrAbove(): void
{
    if (!canView()) {

        accessDenied(
            'You do not have permission to view this content.'
        );
    }
}


// ---------------------------------------------------------------------
// 21. Require Create Permission
// ---------------------------------------------------------------------
//
// Admin + User.
//
// Viewer is blocked.
//
function requireCreatePermission(): void
{
    if (!canCreate()) {

        accessDenied(
            'You do not have permission to create records.'
        );
    }
}


// ---------------------------------------------------------------------
// 22. Require Edit Permission
// ---------------------------------------------------------------------
//
// Admin only.
//
function requireEditPermission(): void
{
    if (!canEdit()) {

        accessDenied(
            'You do not have permission to edit records.'
        );
    }
}


// ---------------------------------------------------------------------
// 23. Require Delete Permission
// ---------------------------------------------------------------------
//
// Admin only.
//
function requireDeletePermission(): void
{
    if (!canDelete()) {

        accessDenied(
            'You do not have permission to delete records.'
        );
    }
}


// ---------------------------------------------------------------------
// 24. Require User Management Permission
// ---------------------------------------------------------------------
//
// Admin only.
//
function requireUserManagement(): void
{
    if (!canManageUsers()) {

        accessDenied(
            'Only administrators can manage users.'
        );
    }
}


// ---------------------------------------------------------------------
// 25. Require Sheet Management Permission
// ---------------------------------------------------------------------
//
// Admin only.
//
function requireSheetManagement(): void
{
    if (!canManageSheets()) {

        accessDenied(
            'Only administrators can manage sheets.'
        );
    }
}


// =====================================================================
// ROLE LABEL / UI HELPERS
// =====================================================================


// ---------------------------------------------------------------------
// 26. Get readable role name
// ---------------------------------------------------------------------

function roleLabel(): string
{
    switch (currentRole()) {

        case 'admin':
            return 'Admin';

        case 'user':
            return 'User';

        case 'viewer':
            return 'Viewer';

        default:
            return 'Unknown';
    }
}


// ---------------------------------------------------------------------
// 27. Get Bootstrap badge class for role
// ---------------------------------------------------------------------

function roleBadgeClass(): string
{
    switch (currentRole()) {

        case 'admin':
            return 'bg-danger';

        case 'user':
            return 'bg-primary';

        case 'viewer':
            return 'bg-secondary';

        default:
            return 'bg-dark';
    }
}


// =====================================================================
// END OF AUTH.PHP
// =====================================================================