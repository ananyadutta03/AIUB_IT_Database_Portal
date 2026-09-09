<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security_log.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ---------------------------------------------------------------------
// Already logged in
// ---------------------------------------------------------------------

if (isset($_SESSION['user_id'])) {

    header(
        'Location: ' .
        BASE_URL .
        '/index.php'
    );

    exit;
}


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $password = $_POST['password'] ?? '';


    // -----------------------------------------------------------------
    // Validate input
    // -----------------------------------------------------------------

    if ($username === '' || $password === '') {

        /*
         * Record incomplete login attempt.
         *
         * No user_id because authentication has not happened.
         */
        logSecurityEvent(
            $pdo,
            null,
            $username !== '' ? $username : null,
            'failed_login',
            'failed'
        );

        $error =
            'Please enter both username and password.';

    } else {

        // -------------------------------------------------------------
        // Get user from database
        // -------------------------------------------------------------

        $stmt = $pdo->prepare(
            'SELECT
                id,
                username,
                password_hash,
                full_name,
                role,
                failed_login_attempts,
                locked_until
             FROM users
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([
            $username
        ]);

        $user = $stmt->fetch();


        // -------------------------------------------------------------
        // Check account lock
        // -------------------------------------------------------------

        if ($user && !empty($user['locked_until'])) {

            $lockedUntilTimestamp = strtotime(
                $user['locked_until']
            );

            $currentTimestamp = time();


            // ---------------------------------------------------------
            // Account is still locked
            // ---------------------------------------------------------

            if ($lockedUntilTimestamp > $currentTimestamp) {

                $remainingSeconds =
                    $lockedUntilTimestamp - $currentTimestamp;

                $remainingMinutes =
                    ceil($remainingSeconds / 60);


                // Record login attempt while account is locked
                logSecurityEvent(
                    $pdo,
                    (int) $user['id'],
                    $user['username'],
                    'failed_login',
                    'failed'
                );


                $error =
                    "Your account is temporarily locked. Please try again in {$remainingMinutes} minute(s).";


            } else {

                // -----------------------------------------------------
                // Lock period has expired
                // Automatically unlock the account
                // -----------------------------------------------------

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET failed_login_attempts = 0,
                         locked_until = NULL
                     WHERE id = ?'
                );

                $stmt->execute([
                    $user['id']
                ]);


                // Update local values
                $user['failed_login_attempts'] = 0;
                $user['locked_until'] = null;


                // -----------------------------------------------------
                // Continue with password verification
                // -----------------------------------------------------

                if (
                    password_verify(
                        $password,
                        $user['password_hash']
                    )
                ) {

                    // -------------------------------------------------
                    // Successful login
                    // Reset failed attempts
                    // -------------------------------------------------

                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET failed_login_attempts = 0,
                             locked_until = NULL
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $user['id']
                    ]);


                    // -------------------------------------------------
                    // Regenerate session ID
                    // Prevent session fixation
                    // -------------------------------------------------

                    session_regenerate_id(true);


                    // -------------------------------------------------
                    // Store authenticated user information
                    // -------------------------------------------------

                    $_SESSION['user_id'] =
                        (int) $user['id'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['full_name'] =
                        $user['full_name'];

                    $_SESSION['role'] =
                        $user['role'];


                    // -------------------------------------------------
                    // Record successful login
                    // -------------------------------------------------

                    logSecurityEvent(
                        $pdo,
                        (int) $user['id'],
                        $user['username'],
                        'login',
                        'success'
                    );


                    // -------------------------------------------------
                    // Redirect to dashboard
                    // -------------------------------------------------

                    header(
                        'Location: ' .
                        BASE_URL .
                        '/index.php'
                    );

                    exit;

                } else {

                    // -------------------------------------------------
                    // Failed login after previous lock expired
                    // -------------------------------------------------

                    $failedAttempts = 1;


                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET failed_login_attempts = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $failedAttempts,
                        $user['id']
                    ]);


                    // -------------------------------------------------
                    // Security log
                    // -------------------------------------------------

                    logSecurityEvent(
                        $pdo,
                        (int) $user['id'],
                        $user['username'],
                        'failed_login',
                        'failed'
                    );


                    $remaining =
                        5 - $failedAttempts;


                    $error =
                        "Invalid username or password. {$remaining} attempt(s) remaining.";
                }
            }

        } else {

            // ---------------------------------------------------------
            // Account is not currently locked
            // ---------------------------------------------------------

            if (
                $user &&
                password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {

                // -----------------------------------------------------
                // Successful login
                // Reset failed attempts
                // -----------------------------------------------------

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET failed_login_attempts = 0,
                         locked_until = NULL
                     WHERE id = ?'
                );

                $stmt->execute([
                    $user['id']
                ]);


                // -----------------------------------------------------
                // Regenerate session ID
                // Prevent session fixation
                // -----------------------------------------------------

                session_regenerate_id(true);


                // -----------------------------------------------------
                // Store authenticated user information
                // -----------------------------------------------------

                $_SESSION['user_id'] =
                    (int) $user['id'];

                $_SESSION['username'] =
                    $user['username'];

                $_SESSION['full_name'] =
                    $user['full_name'];

                $_SESSION['role'] =
                    $user['role'];


                // -----------------------------------------------------
                // Record successful login
                // -----------------------------------------------------

                logSecurityEvent(
                    $pdo,
                    (int) $user['id'],
                    $user['username'],
                    'login',
                    'success'
                );


                // -----------------------------------------------------
                // Redirect to dashboard
                // -----------------------------------------------------

                header(
                    'Location: ' .
                    BASE_URL .
                    '/index.php'
                );

                exit;


            } else {

                // -----------------------------------------------------
                // Failed login
                // -----------------------------------------------------

                if ($user) {

                    $failedAttempts =
                        (int) $user['failed_login_attempts'] + 1;


                    // -------------------------------------------------
                    // 5 failed attempts reached
                    // Lock account for 10 minutes
                    // -------------------------------------------------

                    if ($failedAttempts >= 5) {

                        $lockedUntil =
                            date(
                                'Y-m-d H:i:s',
                                time() + (10 * 60)
                            );


                        $stmt = $pdo->prepare(
                            'UPDATE users
                             SET failed_login_attempts = 5,
                                 locked_until = ?
                             WHERE id = ?'
                        );

                        $stmt->execute([
                            $lockedUntil,
                            $user['id']
                        ]);


                        // -------------------------------------------------
                        // Record lock-triggering failed login
                        // -------------------------------------------------

                        logSecurityEvent(
                            $pdo,
                            (int) $user['id'],
                            $user['username'],
                            'failed_login',
                            'failed'
                        );


                        $error =
                            'Too many failed login attempts. Your account has been locked for 10 minutes.';


                    } else {

                        // -------------------------------------------------
                        // Save failed attempt count
                        // -------------------------------------------------

                        $stmt = $pdo->prepare(
                            'UPDATE users
                             SET failed_login_attempts = ?
                             WHERE id = ?'
                        );

                        $stmt->execute([
                            $failedAttempts,
                            $user['id']
                        ]);


                        // -------------------------------------------------
                        // Record failed login
                        // -------------------------------------------------

                        logSecurityEvent(
                            $pdo,
                            (int) $user['id'],
                            $user['username'],
                            'failed_login',
                            'failed'
                        );


                        $remaining =
                            5 - $failedAttempts;


                        $error =
                            "Invalid username or password. {$remaining} attempt(s) remaining.";
                    }


                } else {

                    // -------------------------------------------------
                    // Username does not exist
                    // -------------------------------------------------

                    logSecurityEvent(
                        $pdo,
                        null,
                        $username,
                        'failed_login',
                        'failed'
                    );


                    /*
                     * Keep the message generic.
                     *
                     * This prevents username enumeration.
                     */
                    $error =
                        'Invalid username or password.';
                }
            }
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

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 50px;
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

            padding: 5px;

            display: flex;
            align-items: center;
            justify-content: center;

            z-index: 5;
        }

        .toggle-password:hover {
            color: #212529;
        }

        .toggle-password:focus {
            outline: none;
            box-shadow: none;
        }

        .eye-icon {
            width: 22px;
            height: 22px;
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

                            <label
                                class="form-label small text-muted"
                                for="username"
                            >
                                USERNAME
                            </label>

                            <input
                                type="text"
                                name="username"
                                id="username"
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

                            <label
                                class="form-label small text-muted"
                                for="loginPassword"
                            >
                                PASSWORD
                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="password"
                                    id="loginPassword"
                                    class="form-control form-control-lg"
                                    required
                                    autocomplete="current-password"
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    id="togglePasswordBtn"
                                    onclick="toggleLoginPassword()"
                                    aria-label="Show password"
                                    title="Show password"
                                >

                                    <svg
                                        id="passwordEye"
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
                                            7.523 5 12 5
                                            c4.478 0 8.268 2.943
                                            9.542 7
                                            -1.274 4.057
                                            -5.064 7
                                            -9.542 7
                                            -4.477 0
                                            -8.268-2.943
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


<script>

function toggleLoginPassword() {

    const passwordInput =
        document.getElementById('loginPassword');

    const eyeIcon =
        document.getElementById('passwordEye');

    const toggleButton =
        document.getElementById('togglePasswordBtn');


    if (passwordInput.type === 'password') {

        passwordInput.type = 'text';

        toggleButton.setAttribute(
            'aria-label',
            'Hide password'
        );

        toggleButton.setAttribute(
            'title',
            'Hide password'
        );

        eyeIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 3l18 18
                M10.584 10.587
                A2 2 0 0012 14
                a2 2 0 001.414-3.414
                M9.88 4.24
                A9.94 9.94 0 0112 4
                c4.478 0 8.268 2.943
                9.542 7
                a10.05 10.05 0 01-4.042 5.27
                M6.228 6.228
                A10.05 10.05 0 002.458 12
                C3.732 16.057
                7.523 19 12 19
                c1.61 0 3.13-.38 4.47-1.053"
            />
        `;

    } else {

        passwordInput.type = 'password';

        toggleButton.setAttribute(
            'aria-label',
            'Show password'
        );

        toggleButton.setAttribute(
            'title',
            'Show password'
        );

        eyeIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.458 12C3.732 7.943
                7.523 5 12 5
                c4.478 0 8.268 2.943
                9.542 7
                -1.274 4.057
                -5.064 7
                -9.542 7
                -4.477 0
                -8.268-2.943
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
