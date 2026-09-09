<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security_log.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ---------------------------------------------------------------------
// Save current user information BEFORE destroying session
// ---------------------------------------------------------------------

$userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : null;

$username = $_SESSION['username'] ?? null;


// ---------------------------------------------------------------------
// Record logout
// ---------------------------------------------------------------------

if ($userId !== null || $username !== null) {

    logSecurityEvent(
        $pdo,
        $userId,
        $username,
        'logout',
        'success'
    );
}


// ---------------------------------------------------------------------
// Wipe session data
// ---------------------------------------------------------------------

$_SESSION = [];


// ---------------------------------------------------------------------
// Delete session cookie
// ---------------------------------------------------------------------

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


// ---------------------------------------------------------------------
// Destroy session
// ---------------------------------------------------------------------

session_destroy();


// ---------------------------------------------------------------------
// Redirect to login
// ---------------------------------------------------------------------

header(
    'Location: ' .
    BASE_URL .
    '/login.php'
);

exit;