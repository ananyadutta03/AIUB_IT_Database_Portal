<?php
// =====================================================================
// delete_user.php
// Delete an existing user/admin.
// Admin access only.
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();


// ---------------------------------------------------------------------
// Only allow POST requests
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method Not Allowed.');
}


// ---------------------------------------------------------------------
// Get user ID
// ---------------------------------------------------------------------
$userId = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId || $userId <= 0) {

    $_SESSION['flash'] = 'Invalid user ID.';

    header('Location: ' . BASE_URL . '/users.php');
    exit;
}


// ---------------------------------------------------------------------
// Prevent admin from deleting their own account
// ---------------------------------------------------------------------
if (
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] === $userId
) {

    $_SESSION['flash'] = 'You cannot delete your own account.';

    header('Location: ' . BASE_URL . '/users.php');
    exit;
}


try {

    // -----------------------------------------------------------------
    // Check whether user exists
    // -----------------------------------------------------------------
    $checkStmt = $pdo->prepare("
        SELECT id, username
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $checkStmt->execute([$userId]);

    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);


    if (!$user) {

        $_SESSION['flash'] = 'User not found.';

        header('Location: ' . BASE_URL . '/users.php');
        exit;
    }


    // -----------------------------------------------------------------
    // Delete user
    // -----------------------------------------------------------------
    $deleteStmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $deleteStmt->execute([$userId]);


    // -----------------------------------------------------------------
    // Success
    // -----------------------------------------------------------------
    $_SESSION['flash'] =
        'User "' . $user['username'] . '" deleted successfully.';

    header('Location: ' . BASE_URL . '/users.php');
    exit;


} catch (PDOException $e) {

    $_SESSION['flash'] =
        'Unable to delete the user. Please try again.';

    header('Location: ' . BASE_URL . '/users.php');
    exit;
}