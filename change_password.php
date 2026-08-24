<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new']     ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $err = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $err = 'New password and confirmation do not match.';
    } elseif (strlen($new) < 4) {
        $err = 'New password must be at least 4 characters long.';
    } else {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $err = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);

            $upd = $pdo->prepare(
                'UPDATE users SET password_hash = ? WHERE id = ?'
            );

            $upd->execute([
                $newHash,
                $_SESSION['user_id']
            ]);

            $msg = 'Password updated successfully.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Change Password — AIUB IT Database Portal</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #6c757d;
            cursor: pointer;

            padding: 4px 6px;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #212529;
        }

        .toggle-password:focus {
            outline: none;
        }

        .eye-icon {
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">

        <a
            class="navbar-brand"
            href="<?= BASE_URL ?>/index.php"
        >
            AIUB IT Database Portal
        </a>

        <div>
            <span class="text-light me-3 small">
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>

            <a
                href="<?= BASE_URL ?>/index.php"
                class="btn btn-sm btn-outline-light me-2"
            >
                Dashboard
            </a>

            <a
                href="<?= BASE_URL ?>/logout.php"
                class="btn btn-sm btn-outline-light"
            >
                Logout
            </a>
        </div>

    </div>
</nav>


<div class="container">

    <div class="row justify-content-center mt-5">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h4 class="mb-4">
                        Change Password
                    </h4>


                    <?php if ($msg): ?>

                        <div class="alert alert-success py-2 small">
                            <?= htmlspecialchars($msg) ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($err): ?>

                        <div class="alert alert-danger py-2 small">
                            <?= htmlspecialchars($err) ?>
                        </div>

                    <?php endif; ?>


                    <form method="post" novalidate>

                        <!-- Current Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                Current password
                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="current"
                                    id="currentPassword"
                                    class="form-control"
                                    required
                                    autofocus
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword(
                                        'currentPassword',
                                        'currentEye'
                                    )"
                                    aria-label="Show current password"
                                >
                                    <svg
                                        id="currentEye"
                                        class="eye-icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943
                                            7.523 5 12 5c4.478 0
                                            8.268 2.943 9.542 7
                                            -1.274 4.057-5.064 7-9.542
                                            7-4.477 0-8.268-2.943
                                            -9.542-7z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        />
                                    </svg>
                                </button>

                            </div>

                        </div>


                        <!-- New Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                New password
                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="new"
                                    id="newPassword"
                                    class="form-control"
                                    required
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword(
                                        'newPassword',
                                        'newEye'
                                    )"
                                    aria-label="Show new password"
                                >
                                    <svg
                                        id="newEye"
                                        class="eye-icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943
                                            7.523 5 12 5c4.478 0
                                            8.268 2.943 9.542 7
                                            -1.274 4.057-5.064 7-9.542
                                            7-4.477 0-8.268-2.943
                                            -9.542-7z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        />
                                    </svg>
                                </button>

                            </div>

                        </div>


                        <!-- Confirm Password -->
                        <div class="mb-4">

                            <label class="form-label">
                                Confirm new password
                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="confirm"
                                    id="confirmPassword"
                                    class="form-control"
                                    required
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword(
                                        'confirmPassword',
                                        'confirmEye'
                                    )"
                                    aria-label="Show confirm password"
                                >
                                    <svg
                                        id="confirmEye"
                                        class="eye-icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943
                                            7.523 5 12 5c4.478 0
                                            8.268 2.943 9.542 7
                                            -1.274 4.057-5.064 7-9.542
                                            7-4.477 0-8.268-2.943
                                            -9.542-7z"
                                        />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        />
                                    </svg>
                                </button>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Update Password
                        </button>

                        <a
                            href="<?= BASE_URL ?>/index.php"
                            class="btn btn-link"
                        >
                            Cancel
                        </a>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script>
function togglePassword(inputId, eyeId) {

    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);

    if (input.type === 'password') {

        input.type = 'text';

        eye.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 3l18 18M10.584 10.587
                A2 2 0 0012 14a2 2 0 001.414-3.414
                M9.88 4.24A9.94 9.94 0 0112 4
                c4.478 0 8.268 2.943 9.542 7
                a10.05 10.05 0 01-4.042 5.27
                M6.228 6.228A10.05 10.05 0 002.458 12
                C3.732 16.057 7.523 19 12 19
                c1.61 0 3.13-.38 4.47-1.053"
            />
        `;

    } else {

        input.type = 'password';

        eye.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.458 12C3.732 7.943
                7.523 5 12 5c4.478 0
                8.268 2.943 9.542 7
                -1.274 4.057-5.064 7-9.542
                7-4.477 0-8.268-2.943
                -9.542-7z"
            />

            <circle
                cx="12"
                cy="12"
                r="3"
            />
        `;

    }
}
</script>

</body>
</html>