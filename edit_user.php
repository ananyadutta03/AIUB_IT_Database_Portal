<?php
// =====================================================================
// edit_user.php
// Edit an existing user/admin.
// Admin access only.
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();


// ---------------------------------------------------------------------
// Get user ID
// ---------------------------------------------------------------------
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId || $userId <= 0) {
    $_SESSION['flash'] = 'Invalid user ID.';
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}


// ---------------------------------------------------------------------
// Fetch existing user
// ---------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        full_name,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash'] = 'User not found.';
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}


$error = '';


// ---------------------------------------------------------------------
// Handle form submission
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $role      = $_POST['role'] ?? 'user';
    $password  = $_POST['password'] ?? '';


    // -------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------
    if ($username === '' || $fullName === '') {

        $error = 'Username and full name are required.';

    } elseif (!in_array($role, ['admin', 'user'], true)) {

        $error = 'Invalid role selected.';

    } elseif ($password !== '' && strlen($password) < 6) {

        $error = 'Password must be at least 6 characters long.';

    } else {

        try {

            // ---------------------------------------------------------
            // Check duplicate username
            // ---------------------------------------------------------
            $checkStmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                  AND id != ?
                LIMIT 1
            ");

            $checkStmt->execute([
                $username,
                $userId
            ]);

            if ($checkStmt->fetch()) {

                $error = 'Username already exists.';

            } else {

                // -----------------------------------------------------
                // Update with password
                // -----------------------------------------------------
                if ($password !== '') {

                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $updateStmt = $pdo->prepare("
                        UPDATE users
                        SET
                            username = ?,
                            full_name = ?,
                            role = ?,
                            password_hash = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");

                    $updateStmt->execute([
                        $username,
                        $fullName,
                        $role,
                        $passwordHash,
                        $userId
                    ]);

                } else {

                    // -------------------------------------------------
                    // Update without changing password
                    // -------------------------------------------------
                    $updateStmt = $pdo->prepare("
                        UPDATE users
                        SET
                            username = ?,
                            full_name = ?,
                            role = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");

                    $updateStmt->execute([
                        $username,
                        $fullName,
                        $role,
                        $userId
                    ]);
                }


                // -----------------------------------------------------
                // If admin edits their own account, update session
                // -----------------------------------------------------
                if (
                    isset($_SESSION['user_id']) &&
                    (int) $_SESSION['user_id'] === $userId
                ) {
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['role'] = $role;
                }


                $_SESSION['flash'] = 'User updated successfully.';

                header('Location: ' . BASE_URL . '/users.php');
                exit;
            }

        } catch (PDOException $e) {

            $error = 'Unable to update user. Please try again.';
        }
    }


    // Keep submitted values in the form if validation fails
    $user['username'] = $username;
    $user['full_name'] = $fullName;
    $user['role'] = $role;
}


// ---------------------------------------------------------------------
// Page header
// ---------------------------------------------------------------------
$pageTitle = 'Edit User';

require_once __DIR__ . '/includes/header.php';
?>


<div class="row justify-content-center">

    <div class="col-lg-7">

        <!-- Page Header -->
        <div class="d-flex align-items-center mb-4">

            <a
                href="<?= BASE_URL ?>/users.php"
                class="btn btn-outline-secondary me-3"
            >
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>
                <h3 class="mb-1">
                    <i class="bi bi-person-gear"></i>
                    Edit User
                </h3>

                <p class="text-muted mb-0">
                    Update user information and account permissions.
                </p>
            </div>

        </div>


        <!-- Error -->
        <?php if ($error): ?>

            <div class="alert alert-danger shadow-sm">
                <i class="bi bi-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- Edit Form -->
        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form method="POST">

                    <!-- Username -->
                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($user['username']) ?>"
                            required
                        >

                    </div>


                    <!-- Full Name -->
                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars($user['full_name']) ?>"
                            required
                        >

                    </div>


                    <!-- Role -->
                    <div class="mb-3">

                        <label class="form-label">
                            Role
                        </label>

                        <select
                            name="role"
                            class="form-select"
                            required
                        >

                            <option
                                value="user"
                                <?= $user['role'] === 'user' ? 'selected' : '' ?>
                            >
                                User
                            </option>

                            <option
                                value="admin"
                                <?= $user['role'] === 'admin' ? 'selected' : '' ?>
                            >
                                Admin
                            </option>

                        </select>

                    </div>


                    <!-- Password -->
                    <div class="mb-4">

                        <label class="form-label">
                            New Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6"
                            autocomplete="new-password"
                        >

                        <div class="form-text">
                            Leave this field empty to keep the current password.
                            If changing the password, use at least 6 characters.
                        </div>

                    </div>


                    <!-- Buttons -->
                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="<?= BASE_URL ?>/users.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-check-circle"></i>
                            Update User
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>