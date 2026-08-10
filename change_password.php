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
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$newHash, $_SESSION['user_id']]);
            $msg = 'Password updated successfully.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password — AIUB IT Database Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">AIUB IT Database Portal</a>
        <div>
            <span class="text-light me-3 small">
                <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-4">Change Password</h4>

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
                        <div class="mb-3">
                            <label class="form-label">Current password</label>
                            <input type="password" name="current"
                                   class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="new"
                                   class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm new password</label>
                            <input type="password" name="confirm"
                                   class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Update Password
                        </button>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-link">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
