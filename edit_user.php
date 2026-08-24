<?php
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireAdmin();


// ---------------------------------------------------------------------
// Get User ID
// ---------------------------------------------------------------------
$userId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId || $userId <= 0) {

    $_SESSION['flash'] = 'Invalid user ID.';

    header('Location: ' . BASE_URL . '/users.php');
    exit;
}


// ---------------------------------------------------------------------
// Fetch User
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


// ---------------------------------------------------------------------
// Check whether admin is editing their own account
// ---------------------------------------------------------------------
$isEditingOwnAccount =
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] === (int) $userId;


// ---------------------------------------------------------------------
// Error message
// ---------------------------------------------------------------------
$error = '';


// ---------------------------------------------------------------------
// Handle Form Submission
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------------------
    // CSRF Protection
    // -------------------------------------------------------------
    require_csrf_token();


    // -------------------------------------------------------------
    // Get submitted values
    // -------------------------------------------------------------
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role     = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';


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

        // ---------------------------------------------------------
        // IMPORTANT SECURITY RULE
        //
        // Admin cannot change their own role.
        //
        // Even if someone manually sends:
        //
        // role=user
        //
        // through a POST request, it will be forced back to admin.
        // ---------------------------------------------------------
        if (
            $isEditingOwnAccount &&
            $_SESSION['role'] === 'admin'
        ) {
            $role = 'admin';
        }


        try {

            // -----------------------------------------------------
            // Check duplicate username
            // -----------------------------------------------------
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

                // -------------------------------------------------
                // Update user
                //
                // If password is empty:
                // keep existing password.
                //
                // If password is provided:
                // update password hash.
                // -------------------------------------------------

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


                // -------------------------------------------------
                // Update current session
                //
                // This is important when admin edits their
                // own username/full name.
                // -------------------------------------------------
                if ($isEditingOwnAccount) {

                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $fullName;

                    // Keep admin role protected
                    $_SESSION['role'] = 'admin';
                }


                // -------------------------------------------------
                // Success message
                // -------------------------------------------------
                $_SESSION['flash'] = 'User updated successfully.';

                header(
                    'Location: ' . BASE_URL . '/users.php'
                );

                exit;
            }

        } catch (PDOException $e) {

            // Do not expose database errors to the user.
            $error = 'Unable to update user. Please try again.';
        }
    }


    // -------------------------------------------------------------
    // Keep submitted values if validation fails
    // -------------------------------------------------------------
    $user['username'] = $username;
    $user['full_name'] = $fullName;
    $user['role'] = $role;
}


// ---------------------------------------------------------------------
// Page Title
// ---------------------------------------------------------------------
$pageTitle = 'Edit User';


// ---------------------------------------------------------------------
// Header
// ---------------------------------------------------------------------
require_once __DIR__ . '/includes/header.php';

?>


<div class="row justify-content-center">

    <div class="col-lg-7">


        <!-- =========================================================
             Page Header
        ========================================================== -->

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


        <!-- =========================================================
             Error Message
        ========================================================== -->

        <?php if ($error): ?>

            <div
                class="alert alert-danger alert-dismissible fade show shadow-sm"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle"></i>

                <?= htmlspecialchars($error) ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- =========================================================
             Edit User Card
        ========================================================== -->

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">


                <form method="POST">


                    <!-- =================================================
                         CSRF Token
                    ================================================== -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(csrf_token()) ?>"
                    >


                    <!-- =================================================
                         Username
                    ================================================== -->

                    <div class="mb-3">

                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                        </label>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($user['username']) ?>"
                            required
                            maxlength="100"
                            autocomplete="username"
                        >

                    </div>


                    <!-- =================================================
                         Full Name
                    ================================================== -->

                    <div class="mb-3">

                        <label
                            for="full_name"
                            class="form-label"
                        >
                            Full Name
                        </label>


                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars($user['full_name']) ?>"
                            required
                            maxlength="150"
                        >

                    </div>


                    <!-- =================================================
                         Role
                    ================================================== -->

                    <div class="mb-3">

                        <label
                            for="role"
                            class="form-label"
                        >
                            Role
                        </label>


                        <?php if ($isEditingOwnAccount): ?>

                            <!--
                                Disabled select is only for display.
                                Hidden input actually submits the role.
                            -->

                            <input
                                type="hidden"
                                name="role"
                                value="admin"
                            >


                            <select
                                id="role"
                                class="form-select"
                                disabled
                            >

                                <option selected>
                                    Admin
                                </option>

                            </select>


                            <div class="form-text text-warning">

                                <i class="bi bi-shield-lock"></i>

                                You cannot change your own role.

                            </div>


                        <?php else: ?>


                            <select
                                id="role"
                                name="role"
                                class="form-select"
                                required
                            >

                                <option
                                    value="user"
                                    <?= $user['role'] === 'user'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    User
                                </option>


                                <option
                                    value="admin"
                                    <?= $user['role'] === 'admin'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Admin
                                </option>

                            </select>


                        <?php endif; ?>

                    </div>


                    <!-- =================================================
                         New Password
                    ================================================== -->

                    <div class="mb-4">

    <label
        for="password"
        class="form-label"
    >
        New Password
    </label>

    <div class="input-group">

        <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            minlength="6"
            autocomplete="new-password"
        >

        <button
            type="button"
            class="btn btn-outline-secondary"
            id="togglePassword"
            aria-label="Show password"
        >
            <i class="bi bi-eye" id="passwordIcon"></i>
        </button>

    </div>

    <div class="form-text">
        Leave this field empty to keep the current password.
        If changing the password, use at least 6 characters.
    </div>

</div>


                    <!-- =================================================
                         Account Information
                    ================================================== -->

                    <div class="alert alert-light border mb-4">

                        <div class="d-flex align-items-center">

                            <i class="bi bi-info-circle me-2"></i>

                            <div>

                                <?php if ($isEditingOwnAccount): ?>

                                    <strong>
                                        You are editing your own account.
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Your administrator role is protected
                                        and cannot be changed.
                                    </small>

                                <?php else: ?>

                                    <strong>
                                        Administrator action
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        You can change this user's role
                                        and password.
                                    </small>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         Buttons
                    ================================================== -->

                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="<?= BASE_URL ?>/users.php"
                            class="btn btn-secondary"
                        >

                            <i class="bi bi-x-circle"></i>

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

<script>
document.addEventListener('DOMContentLoaded', function () {

    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    if (!passwordInput || !togglePassword || !passwordIcon) {
        return;
    }

    togglePassword.addEventListener('click', function () {

        const isPassword =
            passwordInput.type === 'password';

        if (isPassword) {

            passwordInput.type = 'text';

            passwordIcon.classList.remove('bi-eye');
            passwordIcon.classList.add('bi-eye-slash');

            togglePassword.setAttribute(
                'aria-label',
                'Hide password'
            );

        } else {

            passwordInput.type = 'password';

            passwordIcon.classList.remove('bi-eye-slash');
            passwordIcon.classList.add('bi-eye');

            togglePassword.setAttribute(
                'aria-label',
                'Show password'
            );
        }

    });

});
</script>
<?php

require_once __DIR__ . '/includes/footer.php';

?>