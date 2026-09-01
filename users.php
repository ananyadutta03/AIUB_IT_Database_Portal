<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$pageTitle = 'User Management';

require_once __DIR__ . '/includes/header.php';


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        username,
        full_name,
        role,
        created_at,
        updated_at
    FROM users
    ORDER BY id DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h3 class="mb-1">

            <i class="bi bi-people"></i>
            User Management

        </h3>

        <p class="text-muted mb-0">

            Manage system users, administrators and viewers.

        </p>

    </div>


    <a
        href="<?= BASE_URL ?>/add_user.php"
        class="btn btn-primary"
    >

        <i class="bi bi-person-plus"></i>

        Add User

    </a>

</div>


<div class="card shadow-sm border-0">

    <div class="card-body">

        <div class="table-responsive">

            <table
                class="table table-hover align-middle"
                id="usersTable"
            >

                <thead class="table-light">

                    <tr>

                        <th>#</th>

                        <th>Username</th>

                        <th>Full Name</th>

                        <th>Role</th>

                        <th>Created At</th>

                        <th>Updated At</th>

                        <th class="text-end">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($users)): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-4"
                        >

                            <i class="bi bi-people fs-3 d-block mb-2"></i>

                            No users found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($users as $user): ?>

                        <tr>


                            <!-- ID -->

                            <td>

                                <?= (int) $user['id'] ?>

                            </td>


                            <!-- Username -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $user['username'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>

                            </td>


                            <!-- Full Name -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['full_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- Role -->

                            <td>

                                <?php
                                $role = strtolower(
                                    trim(
                                        (string) $user['role']
                                    )
                                );
                                ?>


                                <?php if ($role === 'admin'): ?>


                                    <!-- Admin -->

                                    <span
                                        class="badge bg-danger"
                                        title="Administrator"
                                    >

                                        <i class="bi bi-shield-check me-1"></i>

                                        Admin

                                    </span>


                                <?php elseif ($role === 'viewer'): ?>


                                    <!-- Viewer -->

                                    <span
                                        class="badge bg-secondary"
                                        title="Read-only Viewer"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Viewer

                                    </span>


                                <?php elseif ($role === 'user'): ?>


                                    <!-- User -->

                                    <span
                                        class="badge bg-primary"
                                        title="Normal User"
                                    >

                                        <i class="bi bi-person me-1"></i>

                                        User

                                    </span>


                                <?php else: ?>


                                    <!-- Unknown Role -->

                                    <span
                                        class="badge bg-dark"
                                        title="Unknown role"
                                    >

                                        <i class="bi bi-question-circle me-1"></i>

                                        <?= htmlspecialchars(
                                            ucfirst($role),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>


                                <?php endif; ?>

                            </td>


                            <!-- Created At -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['created_at'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- Updated At -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['updated_at'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- Actions -->

                            <td class="text-end">


                                <!-- Edit -->

                                <a
                                    href="<?= BASE_URL ?>/edit_user.php?id=<?= (int) $user['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Edit user"
                                >

                                    <i class="bi bi-pencil"></i>

                                    Edit

                                </a>


                                <!-- Delete -->

                                <?php if (
                                    (int) $user['id']
                                    !==
                                    (int) $_SESSION['user_id']
                                ): ?>


                                    <form
                                        method="POST"
                                        action="<?= BASE_URL ?>/delete_user.php"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this user?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $user['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete user"
                                        >

                                            <i class="bi bi-trash"></i>

                                            Delete

                                        </button>

                                    </form>


                                <?php endif; ?>


                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
