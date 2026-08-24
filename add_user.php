<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pageTitle = 'Add User';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {



    $username  = trim($_POST['username'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'user';

    if ($username === '' || $fullName === '' || $password === '') {

        $error = 'All fields are required.';

    } elseif (!in_array($role, ['admin', 'user'], true)) {

        $error = 'Invalid role selected.';

    } elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters long.';

    } else {

        try {

            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $check->execute([$username]);

            if ($check->fetch()) {

                $error = 'Username already exists.';

            } else {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

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

                $_SESSION['flash'] = 'User created successfully.';

                header('Location: ' . BASE_URL . '/users.php');
                exit;
            }

        } catch (PDOException $e) {

            $error = 'Unable to create user.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">

    <div class="col-lg-7">

        <div class="d-flex align-items-center mb-4">

            <a href="<?= BASE_URL ?>/users.php"
               class="btn btn-outline-secondary me-3">

                <i class="bi bi-arrow-left"></i>

            </a>

            <div>
                <h3 class="mb-1">Add New User</h3>
                <p class="text-muted mb-0">
                    Create a new system user or administrator.
                </p>
            </div>

        </div>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                        <div class="form-text">
                            Password must be at least 6 characters.
                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Role
                        </label>

                        <select name="role" class="form-select" required>

                            <option value="user"
                                <?= ($_POST['role'] ?? '') === 'user' ? 'selected' : '' ?>>
                                User
                            </option>

                            <option value="admin"
                                <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                Admin
                            </option>

                        </select>

                    </div>

                    <div class="d-flex justify-content-end gap-2">

                        <a href="<?= BASE_URL ?>/users.php"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="bi bi-person-plus"></i>
                            Create User

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>