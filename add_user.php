<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Add User';

$error = '';


/*
|--------------------------------------------------------------------------
| Handle Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $fullName = trim(
        $_POST['full_name'] ?? ''
    );

    $password = $_POST['password'] ?? '';

    $role = $_POST['role'] ?? 'user';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $username === '' ||
        $fullName === '' ||
        $password === ''
    ) {

        $error = 'All fields are required.';

    } elseif (
        !in_array(
            $role,
            ['admin', 'user', 'viewer'],
            true
        )
    ) {

        $error = 'Invalid role selected.';

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            'Password must be at least 6 characters long.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check Existing Username
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $check->execute([
                $username
            ]);


            if ($check->fetch()) {

                $error =
                    'Username already exists.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Password Hash
                |--------------------------------------------------------------------------
                */

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | Insert User
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (
                        username,
                        password_hash,
                        full_name,
                        role,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (?, ?, ?, ?, NOW(), NOW())
                ");


                $stmt->execute([
                    $username,
                    $passwordHash,
                    $fullName,
                    $role
                ]);


                $_SESSION['flash'] =
                    'User created successfully.';


                header(
                    'Location: ' .
                    BASE_URL .
                    '/users.php'
                );

                exit;
            }

        } catch (PDOException $e) {

            $error =
                'Unable to create user.';
        }
    }
}


require_once __DIR__ . '/includes/header.php';

?>

<style>

.password-wrapper {
    position: relative;
}

.password-wrapper .form-control {
    padding-right: 48px;
}

.toggle-password {
    position: absolute;
    top: 50%;
    right: 10px;
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
    width: 20px;
    height: 20px;
}

</style>


<div class="row justify-content-center">

    <div class="col-lg-7">

        <!-- Header -->

        <div class="d-flex align-items-center mb-4">

            <a
                href="<?= BASE_URL ?>/users.php"
                class="btn btn-outline-secondary me-3"
            >
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>

                <h3 class="mb-1">
                    Add New User
                </h3>

                <p class="text-muted mb-0">
                    Create a new system user,
                    administrator or viewer.
                </p>

            </div>

        </div>


        <!-- Error -->

        <?php if ($error): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-triangle"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- Card -->

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
                            value="<?= htmlspecialchars(
                                $_POST['username'] ?? ''
                            ) ?>"
                            required
                            autocomplete="username"
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
                            value="<?= htmlspecialchars(
                                $_POST['full_name'] ?? ''
                            ) ?>"
                            required
                            autocomplete="name"
                        >

                    </div>


                    <!-- Password -->

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="userPassword"
                        >
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                name="password"
                                id="userPassword"
                                class="form-control"
                                minlength="6"
                                required
                                autocomplete="new-password"
                            >

                            <button
                                type="button"
                                id="togglePasswordBtn"
                                class="toggle-password"
                                onclick="toggleUserPassword()"
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

                        <div class="form-text">
                            Password must be at least 6 characters.
                        </div>

                    </div>


                    <!-- Role -->

                    <div class="mb-4">

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
                                <?= (
                                    $_POST['role'] ?? 'user'
                                ) === 'user'
                                    ? 'selected'
                                    : '' ?>
                            >
                                User
                            </option>


                            <option
                                value="admin"
                                <?= (
                                    $_POST['role'] ?? ''
                                ) === 'admin'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Admin
                            </option>


                            <option
                                value="viewer"
                                <?= (
                                    $_POST['role'] ?? ''
                                ) === 'viewer'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Viewer
                            </option>

                        </select>

                        <div class="form-text">

                            <strong>Admin:</strong>
                            Full system access.

                            <br>

                            <strong>User:</strong>
                            Normal system access.

                            <br>

                            <strong>Viewer:</strong>
                            Can only view records and details.

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

                            <i class="bi bi-person-plus"></i>

                            Create User

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

function toggleUserPassword() {

    const passwordInput =
        document.getElementById(
            'userPassword'
        );

    const eyeIcon =
        document.getElementById(
            'passwordEye'
        );

    const toggleButton =
        document.getElementById(
            'togglePasswordBtn'
        );


    if (
        passwordInput.type === 'password'
    ) {

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

        passwordInput.type =
            'password';

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


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>