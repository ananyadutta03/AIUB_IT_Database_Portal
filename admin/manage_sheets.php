<?php

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sheets_list.php';


/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| CSRF FUNCTIONS
|--------------------------------------------------------------------------
| Define only if they don't already exist.
|--------------------------------------------------------------------------
*/

if (!function_exists('generateCsrfToken')) {

    function generateCsrfToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($_SESSION['csrf_token']) ||
            !is_string($_SESSION['csrf_token'])
        ) {
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['csrf_token'];
    }
}


if (!function_exists('validateCsrfToken')) {

    function validateCsrfToken($token)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($token) ||
            empty($_SESSION['csrf_token'])
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

$csrfToken = generateCsrfToken();


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

$editSheet = null;


/*
|--------------------------------------------------------------------------
| HANDLE POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        !validateCsrfToken(
            $_POST['csrf_token'] ?? null
        )
    ) {

        $error =
            'Invalid security token. Please refresh the page and try again.';

    } else {

        $action = $_POST['action'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | ADD SHEET
        |--------------------------------------------------------------------------
        */

        if ($action === 'add') {

            $sheetName = trim(
                $_POST['sheet_name'] ?? ''
            );


            if ($sheetName === '') {

                $error = 'Sheet name is required.';

            } elseif (mb_strlen($sheetName) > 255) {

                $error =
                    'Sheet name cannot exceed 255 characters.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        INSERT INTO sheets
                            (sheet_name, is_active)
                        VALUES
                            (:sheet_name, 1)
                    ");

                    $stmt->execute([
                        ':sheet_name' => $sheetName
                    ]);

                    $success =
                        'Sheet added successfully.';

                } catch (PDOException $e) {

                    if ($e->getCode() === '23000') {

                        $error =
                            'A sheet with this name already exists.';

                    } else {

                        error_log(
                            'Manage Sheets - Add Error: ' .
                            $e->getMessage()
                        );

                        $error =
                            'Database error. Unable to add sheet.';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE SHEET
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'update') {

            $sheetId = filter_input(
                INPUT_POST,
                'sheet_id',
                FILTER_VALIDATE_INT
            );

            $sheetName = trim(
                $_POST['sheet_name'] ?? ''
            );


            if (!$sheetId) {

                $error =
                    'Invalid sheet ID.';

            } elseif ($sheetName === '') {

                $error =
                    'Sheet name is required.';

            } elseif (mb_strlen($sheetName) > 255) {

                $error =
                    'Sheet name cannot exceed 255 characters.';

            } else {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Check if sheet exists
                    |--------------------------------------------------------------------------
                    */

                    $checkStmt = $pdo->prepare("
                        SELECT id
                        FROM sheets
                        WHERE id = :id
                        LIMIT 1
                    ");

                    $checkStmt->execute([
                        ':id' => $sheetId
                    ]);

                    $existingSheet =
                        $checkStmt->fetch();


                    if (!$existingSheet) {

                        $error =
                            'Sheet not found.';

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Update
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $pdo->prepare("
                            UPDATE sheets
                            SET sheet_name = :sheet_name
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            ':sheet_name' => $sheetName,
                            ':id' => $sheetId
                        ]);

                        $success =
                            'Sheet updated successfully.';
                    }

                } catch (PDOException $e) {

                    if ($e->getCode() === '23000') {

                        $error =
                            'Another sheet already uses this name.';

                    } else {

                        error_log(
                            'Manage Sheets - Update Error: ' .
                            $e->getMessage()
                        );

                        $error =
                            'Database error. Unable to update sheet.';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DEACTIVATE SHEET
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'deactivate') {

            $sheetId = filter_input(
                INPUT_POST,
                'sheet_id',
                FILTER_VALIDATE_INT
            );


            if (!$sheetId) {

                $error =
                    'Invalid sheet ID.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE sheets
                        SET is_active = 0
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':id' => $sheetId
                    ]);


                    if ($stmt->rowCount() > 0) {

                        $success =
                            'Sheet removed from the active list.';

                    } else {

                        $checkStmt = $pdo->prepare("
                            SELECT id, is_active
                            FROM sheets
                            WHERE id = :id
                            LIMIT 1
                        ");

                        $checkStmt->execute([
                            ':id' => $sheetId
                        ]);

                        $sheet =
                            $checkStmt->fetch();


                        if (!$sheet) {

                            $error =
                                'Sheet not found.';

                        } else {

                            $success =
                                'Sheet is already inactive.';
                        }
                    }

                } catch (PDOException $e) {

                    error_log(
                        'Manage Sheets - Deactivate Error: ' .
                        $e->getMessage()
                    );

                    $error =
                        'Database error. Unable to remove sheet.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ACTIVATE SHEET
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'activate') {

            $sheetId = filter_input(
                INPUT_POST,
                'sheet_id',
                FILTER_VALIDATE_INT
            );


            if (!$sheetId) {

                $error =
                    'Invalid sheet ID.';

            } else {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE sheets
                        SET is_active = 1
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':id' => $sheetId
                    ]);


                    if ($stmt->rowCount() > 0) {

                        $success =
                            'Sheet activated successfully.';

                    } else {

                        $checkStmt = $pdo->prepare("
                            SELECT id, is_active
                            FROM sheets
                            WHERE id = :id
                            LIMIT 1
                        ");

                        $checkStmt->execute([
                            ':id' => $sheetId
                        ]);

                        $sheet =
                            $checkStmt->fetch();


                        if (!$sheet) {

                            $error =
                                'Sheet not found.';

                        } else {

                            $success =
                                'Sheet is already active.';
                        }
                    }

                } catch (PDOException $e) {

                    error_log(
                        'Manage Sheets - Activate Error: ' .
                        $e->getMessage()
                    );

                    $error =
                        'Database error. Unable to activate sheet.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | INVALID ACTION
        |--------------------------------------------------------------------------
        */

        else {

            $error =
                'Invalid request.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD ALL SHEETS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            sheet_name,
            is_active,
            created_at
        FROM sheets
        ORDER BY id DESC
    ");

    $sheets = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        'Manage Sheets - Load Error: ' .
        $e->getMessage()
    );

    $sheets = [];

    $error =
        'Unable to load sheets from database.';
}


/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['edit']) &&
    filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    )
) {

    $editId = (int) $_GET['edit'];


    try {

        $stmt = $pdo->prepare("
            SELECT
                id,
                sheet_name,
                is_active,
                created_at
            FROM sheets
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $editId
        ]);

        $editSheet =
            $stmt->fetch();


        if (!$editSheet) {

            $error =
                'Sheet not found.';
        }

    } catch (PDOException $e) {

        error_log(
            'Manage Sheets - Edit Error: ' .
            $e->getMessage()
        );

        $error =
            'Unable to load sheet information.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Sheets | AIUB IT Database Portal
    </title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;

            color: #1f2937;
        }

        .container {
            width: 95%;

            max-width: 1200px;

            margin: 40px auto;
        }

        .header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

            gap: 20px;
        }

        .header h1 {
            margin: 0;

            font-size: 28px;
        }

        .back-btn {
            text-decoration: none;

            background: #374151;

            color: white;

            padding: 10px 16px;

            border-radius: 6px;

            transition: 0.2s;
        }

        .back-btn:hover {
            background: #1f2937;
        }

        .card {
            background: white;

            border-radius: 10px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, .06);
        }

        .card h2 {
            margin-top: 0;

            font-size: 20px;
        }

        .form-row {
            display: flex;

            gap: 10px;
        }

        input[type="text"] {
            flex: 1;

            padding: 12px;

            border:
                1px solid #d1d5db;

            border-radius: 6px;

            font-size: 15px;

            outline: none;
        }

        input[type="text"]:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .1);
        }

        button,
        .btn {
            border: none;

            cursor: pointer;

            padding: 10px 15px;

            border-radius: 6px;

            font-size: 14px;

            text-decoration: none;

            display: inline-block;

            transition: 0.2s;
        }

        .btn-primary {
            background: #2563eb;

            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-warning {
            background: #f59e0b;

            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #dc2626;

            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-success {
            background: #16a34a;

            color: white;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-secondary {
            background: #6b7280;

            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 14px 18px;

            border-radius: 6px;

            margin-bottom: 20px;
        }

        .alert-success {
            background: #dcfce7;

            color: #166534;

            border:
                1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;

            color: #991b1b;

            border:
                1px solid #fecaca;
        }

        table {
            width: 100%;

            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;

            text-align: left;
        }

        th {
            background: #f9fafb;

            font-weight: 600;
        }

        tr:hover {
            background: #fafafa;
        }

        .status {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }

        .active {
            background: #dcfce7;

            color: #166534;
        }

        .inactive {
            background: #fee2e2;

            color: #991b1b;
        }

        .actions {
            display: flex;

            gap: 6px;

            flex-wrap: wrap;
        }

        .inline-form {
            display: inline;
        }

        .edit-box {
            background: #eff6ff;

            border:
                1px solid #bfdbfe;
        }

        .empty-message {
            color: #6b7280;

            margin: 0;
        }

        @media (max-width: 700px) {

            .header {
                flex-direction: column;

                align-items: flex-start;
            }

            .form-row {
                flex-direction: column;
            }

            table {
                font-size: 13px;
            }

            th,
            td {
                padding: 8px;
            }

            .card {
                padding: 18px;
            }

            .actions {
                flex-direction: column;

                align-items: flex-start;
            }

        }

    </style>

</head>


<body>

<div class="container">


    <!-- HEADER -->

    <div class="header">

        <h1>
            Manage Sheets
        </h1>


        <a
            href="../index.php"
            class="back-btn"
        >
            ← Back to Dashboard
        </a>

    </div>


    <!-- ALERTS -->

    <?php if ($success): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <!-- ADD SHEET -->

    <div class="card">

        <h2>
            Add New Sheet
        </h2>


        <form
            method="POST"
            action=""
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $csrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="form-row">

                <input
                    type="text"
                    name="sheet_name"
                    placeholder="Enter sheet name"
                    maxlength="255"
                    required
                >


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    + Add Sheet
                </button>

            </div>

        </form>

    </div>


    <!-- EDIT SHEET -->

    <?php if ($editSheet): ?>

        <div class="card edit-box">

            <h2>
                Edit Sheet
            </h2>


            <form
                method="POST"
                action=""
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="update"
                >


                <input
                    type="hidden"
                    name="sheet_id"
                    value="<?= (int) $editSheet['id'] ?>"
                >


                <div class="form-row">

                    <input
                        type="text"
                        name="sheet_name"
                        value="<?= htmlspecialchars(
                            $editSheet['sheet_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        maxlength="255"
                        required
                    >


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>


                    <a
                        href="manage_sheets.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    <?php endif; ?>


    <!-- SHEET LIST -->

    <div class="card">

        <h2>
            All Sheets
        </h2>


        <?php if (empty($sheets)): ?>

            <p class="empty-message">
                No sheets found.
            </p>

        <?php else: ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Sheet Name
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($sheets as $sheet): ?>

                        <tr>

                            <td>
                                <?= (int) $sheet['id'] ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $sheet['sheet_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>


                            <td>

                                <?php if (
                                    (int) $sheet['is_active'] === 1
                                ): ?>

                                    <span class="status active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $sheet['created_at'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td>

                                <div class="actions">


                                    <!-- EDIT -->

                                    <a
                                        href="?edit=<?= (int) $sheet['id'] ?>"
                                        class="btn btn-warning"
                                    >
                                        Edit
                                    </a>


                                    <?php if (
                                        (int) $sheet['is_active'] === 1
                                    ): ?>


                                        <!-- DEACTIVATE -->

                                        <form
                                            method="POST"
                                            class="inline-form"
                                            onsubmit="return confirm('Are you sure you want to remove this sheet from the active list?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    $csrfToken,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="deactivate"
                                            >


                                            <input
                                                type="hidden"
                                                name="sheet_id"
                                                value="<?= (int) $sheet['id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-danger"
                                            >
                                                Remove
                                            </button>

                                        </form>


                                    <?php else: ?>


                                        <!-- ACTIVATE -->

                                        <form
                                            method="POST"
                                            class="inline-form"
                                            onsubmit="return confirm('Activate this sheet?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    $csrfToken,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="activate"
                                            >


                                            <input
                                                type="hidden"
                                                name="sheet_id"
                                                value="<?= (int) $sheet['id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-success"
                                            >
                                                Activate
                                            </button>

                                        </form>


                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>