<?php
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in? Skip the form and go to dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // -------------------------------------------------------------
    // Validate input
    // -------------------------------------------------------------
    if ($username === '' || $password === '') {

        $error = 'Please enter both username and password.';

    } else {

        // ---------------------------------------------------------
        // Get user from database
        // ---------------------------------------------------------
        $stmt = $pdo->prepare(
            'SELECT
                id,
                username,
                password_hash,
                full_name,
                role
             FROM users
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        // ---------------------------------------------------------
        // Verify username and password
        // ---------------------------------------------------------
        if ($user && password_verify($password, $user['password_hash'])) {

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // -----------------------------------------------------
            // Store authenticated user information in session
            // -----------------------------------------------------
            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // -----------------------------------------------------
            // Redirect to dashboard
            // -----------------------------------------------------
            header('Location: ' . BASE_URL . '/index.php');
            exit;

        } else {

            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Login — AIUB IT Database Portal
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: linear-gradient(
                135deg,
                #1e3a5f 0%,
                #2c5282 100%
            );

            min-height: 100vh;

            display: flex;

            align-items: center;
        }

        .login-card {
            border: 0;
            border-radius: 12px;
        }

        .brand {
            color: #1e3a5f;
            font-weight: 600;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="row justify-content-center">

        <div class="col-md-5 col-lg-4">

            <div class="card shadow-lg login-card">

                <div class="card-body p-4 p-md-5">

                    <h3 class="text-center brand mb-1">
                        AIUB IT Database Portal
                    </h3>

                    <p class="text-center text-muted mb-4 small">
                        IT Admin Sign-in
                    </p>


                    <?php if ($error): ?>

                        <div
                            class="alert alert-danger py-2 small mb-3"
                            role="alert"
                        >
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>


                    <form method="post" novalidate>

                        <!-- Username -->
                        <div class="mb-3">

                            <label class="form-label small text-muted">
                                USERNAME
                            </label>

                            <input
                                type="text"
                                name="username"
                                class="form-control form-control-lg"
                                required
                                autofocus
                                autocomplete="username"
                                value="<?= htmlspecialchars(
                                    $_POST['username'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- Password -->
                        <div class="mb-4">

                            <label class="form-label small text-muted">
                                PASSWORD
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control form-control-lg"
                                required
                                autocomplete="current-password"
                            >

                        </div>


                        <!-- Login button -->
                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >
                            Login
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>