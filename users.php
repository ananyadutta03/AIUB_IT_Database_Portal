<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'User Management';

require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query("
    SELECT id, username, full_name, role, created_at, updated_at
    FROM users
    ORDER BY id DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">
            <i class="bi bi-people"></i> User Management
        </h3>
        <p class="text-muted mb-0">
            Manage system users and administrators.
        </p>
    </div>

    <a href="<?= BASE_URL ?>/add_user.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Add User
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">

                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?= (int) $user['id'] ?>
                        </td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($user['username']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </td>

                        <td>

                            <?php if ($user['role'] === 'admin'): ?>

                                <span class="badge bg-danger">
                                    <i class="bi bi-shield-check"></i>
                                    Admin
                                </span>

                            <?php else: ?>

                                <span class="badge bg-primary">
                                    <i class="bi bi-person"></i>
                                    User
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($user['created_at']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['updated_at']) ?>
                        </td>

                        <td class="text-end">

                            <a href="<?= BASE_URL ?>/edit_user.php?id=<?= (int) $user['id'] ?>"
                               class="btn btn-sm btn-outline-primary">

                                <i class="bi bi-pencil"></i>
                                Edit

                            </a>

                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>

                                <a href="<?= BASE_URL ?>/delete_user.php?id=<?= (int) $user['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this user?');">

                                    <i class="bi bi-trash"></i>
                                    Delete

                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>