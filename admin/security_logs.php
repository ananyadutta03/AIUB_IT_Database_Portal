
<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';


// ---------------------------------------------------------------------
// Admin only
// ---------------------------------------------------------------------

requireAdmin();


// ---------------------------------------------------------------------
// Statistics
// ---------------------------------------------------------------------

$totalAttempts = $pdo
    ->query("
        SELECT COUNT(*)
        FROM login_logs
    ")
    ->fetchColumn();


$successfulLogins = $pdo
    ->query("
        SELECT COUNT(*)
        FROM login_logs
        WHERE event_type = 'login'
        AND status = 'success'
    ")
    ->fetchColumn();


$failedAttempts = $pdo
    ->query("
        SELECT COUNT(*)
        FROM login_logs
        WHERE status = 'failed'
    ")
    ->fetchColumn();


$todayAttempts = $pdo
    ->query("
        SELECT COUNT(*)
        FROM login_logs
        WHERE DATE(attempted_at) = CURDATE()
    ")
    ->fetchColumn();


// ---------------------------------------------------------------------
// Suspicious IP detection
//
// 10 or more failed attempts within the last 12 hours.
// ---------------------------------------------------------------------

$suspiciousStmt = $pdo->query("
    SELECT
        ip_address,
        COUNT(*) AS failed_attempts,
        MAX(attempted_at) AS last_attempt
    FROM login_logs
    WHERE status = 'failed'
      AND attempted_at >= NOW() - INTERVAL 24 HOUR
    GROUP BY ip_address
    HAVING COUNT(*) >= 10
    ORDER BY failed_attempts DESC
");

$suspiciousAttempts = $suspiciousStmt->fetchAll();


// ---------------------------------------------------------------------
// Recent logs pagination
// ---------------------------------------------------------------------

$perPage = 15;

// Current page
$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

// Prevent invalid page numbers
if ($page < 1) {
    $page = 1;
}


// ---------------------------------------------------------------------
// Total log count
// ---------------------------------------------------------------------

$totalLogs = $pdo
    ->query("
        SELECT COUNT(*)
        FROM login_logs
    ")
    ->fetchColumn();


// ---------------------------------------------------------------------
// Total pages
// ---------------------------------------------------------------------

$totalPages = max(
    1,
    (int) ceil($totalLogs / $perPage)
);


// If page is greater than total pages
if ($page > $totalPages) {
    $page = $totalPages;
}


// ---------------------------------------------------------------------
// Offset
// ---------------------------------------------------------------------

$offset = ($page - 1) * $perPage;


// ---------------------------------------------------------------------
// Get logs for current page
// ---------------------------------------------------------------------

$logsStmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        username,
        event_type,
        status,
        ip_address,
        user_agent,
        attempted_at
    FROM login_logs
    ORDER BY attempted_at DESC
    LIMIT :limit OFFSET :offset
");

$logsStmt->bindValue(
    ':limit',
    $perPage,
    PDO::PARAM_INT
);

$logsStmt->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$logsStmt->execute();

$logs = $logsStmt->fetchAll();


// ---------------------------------------------------------------------
// Page
// ---------------------------------------------------------------------

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container-fluid py-4">

    <!-- =============================================================
         Header
    ============================================================= -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Security & Login Activity
            </h2>

            <p class="text-muted mb-0">
                Authentication and security audit logs
            </p>

        </div>

    </div>


    <!-- =============================================================
         Statistics
    ============================================================= -->

    <div class="row g-3 mb-4">

        <!-- Total -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        TOTAL ATTEMPTS
                    </div>

                    <h2 class="mt-2 mb-0">
                        <?= (int) $totalAttempts ?>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Successful -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        SUCCESSFUL LOGINS
                    </div>

                    <h2 class="mt-2 mb-0 text-success">
                        <?= (int) $successfulLogins ?>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Failed -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        FAILED ATTEMPTS
                    </div>

                    <h2 class="mt-2 mb-0 text-danger">
                        <?= (int) $failedAttempts ?>
                    </h2>

                </div>

            </div>

        </div>


        <!-- Today -->

        <div class="col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        TODAY'S ACTIVITY
                    </div>

                    <h2 class="mt-2 mb-0">
                        <?= (int) $todayAttempts ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- =============================================================
         Suspicious Activity
    ============================================================= -->

    <?php if (!empty($suspiciousAttempts)): ?>

        <div class="card border-danger mb-4">

            <div class="card-header bg-danger text-white">

                <i class="bi bi-shield-exclamation"></i>

                Suspicious Login Activity Detected

            </div>


            <div class="card-body">

                <?php foreach ($suspiciousAttempts as $attempt): ?>

                    <div class="mb-2">

                        <strong>
                            IP:
                        </strong>

                        <?= htmlspecialchars(
                            $attempt['ip_address']
                        ) ?>


                        &nbsp; | &nbsp;


                        <strong>
                            Failed Attempts:
                        </strong>

                        <?= (int) $attempt['failed_attempts'] ?>


                        &nbsp; | &nbsp;


                        <strong>
                            Last Attempt:
                        </strong>

                        <?= htmlspecialchars(
                            $attempt['last_attempt']
                        ) ?>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- =============================================================
         Recent Activity
    ============================================================= -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    Recent Security Activity
                </strong>

                <span class="text-muted small">
                    Showing
                    <?= $totalLogs > 0 ? $offset + 1 : 0 ?>
                    -
                    <?= min($offset + $perPage, $totalLogs) ?>
                    of
                    <?= (int) $totalLogs ?>
                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover table-bordered mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>
                                Date & Time
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Event
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                IP Address
                            </th>

                            <th>
                                Browser / Device
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($logs)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted py-4"
                            >
                                No security activity recorded yet.
                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($logs as $log): ?>

                            <tr>

                                <!-- Date -->

                                <td class="text-nowrap">

                                    <?= htmlspecialchars(
                                        $log['attempted_at']
                                    ) ?>

                                </td>


                                <!-- Username -->

                                <td>

                                    <?= htmlspecialchars(
                                        $log['username']
                                            ?? 'Unknown'
                                    ) ?>

                                </td>


                                <!-- Event -->

                                <td>

                                    <?php

                                    $eventLabels = [

                                        'login'
                                            => 'Login',

                                        'failed_login'
                                            => 'Failed Login',

                                        'logout'
                                            => 'Logout',

                                        'unauthorized_access'
                                            => 'Unauthorized Access',

                                    ];

                                    echo htmlspecialchars(
                                        $eventLabels[
                                            $log['event_type']
                                        ]
                                        ??
                                        $log['event_type']
                                    );

                                    ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php if (
                                        $log['status']
                                        === 'success'
                                    ): ?>

                                        <span
                                            class="badge bg-success"
                                        >
                                            Success
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-danger"
                                        >
                                            Failed
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- IP -->

                                <td>

                                    <code>
                                        <?= htmlspecialchars(
                                            $log['ip_address']
                                        ) ?>
                                    </code>

                                </td>


                                <!-- User Agent -->

                                <td
                                    style="
                                        max-width: 350px;
                                        word-break: break-word;
                                    "
                                >

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $log['user_agent']
                                        ) ?>

                                    </small>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- =====================================================
                 Pagination
            ===================================================== -->

            <?php if ($totalPages > 1): ?>

                <div class="p-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <!-- Previous -->

                        <div>

                            <?php if ($page > 1): ?>

                                <a
                                    href="?page=<?= $page - 1 ?>"
                                    class="btn btn-outline-secondary btn-sm"
                                >
                                    <i class="bi bi-chevron-left"></i>
                                    Previous
                                </a>

                            <?php else: ?>

                                <button
                                    class="btn btn-outline-secondary btn-sm"
                                    disabled
                                >
                                    <i class="bi bi-chevron-left"></i>
                                    Previous
                                </button>

                            <?php endif; ?>

                        </div>


                        <!-- Page Numbers -->

                        <div class="d-flex align-items-center gap-1">

                            <?php

                            /*
                             * Show a limited number of page buttons.
                             *
                             * Example:
                             * 1 2 3 4 5 ... 10
                             */

                            $startPage = max(1, $page - 2);

                            $endPage = min(
                                $totalPages,
                                $page + 2
                            );

                            ?>


                            <?php if ($startPage > 1): ?>

                                <a
                                    href="?page=1"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    1
                                </a>

                                <?php if ($startPage > 2): ?>

                                    <span class="px-1 text-muted">
                                        ...
                                    </span>

                                <?php endif; ?>

                            <?php endif; ?>


                            <?php for (
                                $i = $startPage;
                                $i <= $endPage;
                                $i++
                            ): ?>

                                <?php if ($i == $page): ?>

                                    <span
                                        class="btn btn-sm btn-primary"
                                    >
                                        <?= $i ?>
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="?page=<?= $i ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        <?= $i ?>
                                    </a>

                                <?php endif; ?>

                            <?php endfor; ?>


                            <?php if ($endPage < $totalPages): ?>

                                <?php if ($endPage < $totalPages - 1): ?>

                                    <span class="px-1 text-muted">
                                        ...
                                    </span>

                                <?php endif; ?>

                                <a
                                    href="?page=<?= $totalPages ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    <?= $totalPages ?>
                                </a>

                            <?php endif; ?>

                        </div>


                        <!-- Next -->

                        <div>

                            <?php if ($page < $totalPages): ?>

                                <a
                                    href="?page=<?= $page + 1 ?>"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    Next
                                    <i class="bi bi-chevron-right"></i>
                                </a>

                            <?php else: ?>

                                <button
                                    class="btn btn-outline-primary btn-sm"
                                    disabled
                                >
                                    Next
                                    <i class="bi bi-chevron-right"></i>
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
