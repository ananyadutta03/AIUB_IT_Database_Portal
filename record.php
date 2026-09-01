<?php

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sheets_list.php';


/*
|--------------------------------------------------------------------------
| CREATE PERMISSION
|--------------------------------------------------------------------------
*/

requireCreatePermission();


/*
|--------------------------------------------------------------------------
| LOAD ACTIVE SHEETS
|--------------------------------------------------------------------------
|
| Only active sheets are available when creating/editing records.
|--------------------------------------------------------------------------
*/

$ALL_SHEETS = [];

try {

    $stmt = $pdo->query("
        SELECT sheet_name
        FROM sheets
        WHERE is_active = 1
        ORDER BY sheet_name ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (is_array($rows)) {
        $ALL_SHEETS = $rows;
    }

} catch (PDOException $e) {

    error_log(
        'Record - Sheet Load Error: ' .
        $e->getMessage()
    );

    $ALL_SHEETS = [];
}


/*
|--------------------------------------------------------------------------
| ALL EDITABLE FIELDS
|--------------------------------------------------------------------------
*/

$FIELDS = [

    'Basic' => [

        'sheet_name' => [
            'label' => 'Sheet',
            'type' => 'select',
            'required' => true
        ],

    ],


    'Identity' => [

        'full_name' => [
            'label' => 'Full Name',
            'type' => 'text',
            'required' => true
        ],

        'employee_id' => [
            'label' => 'Employee ID',
            'type' => 'text',
            'required' => true
        ],

        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'required' => true
        ],

        'username' => [
            'label' => 'Username',
            'type' => 'text'
        ],

        'contact_number' => [
            'label' => 'Contact Number',
            'type' => 'text',
            'required' => true
        ],

        'designation' => [
            'label' => 'Designation',
            'type' => 'text',
            'required' => true
        ],

        'department' => [
            'label' => 'Department',
            'type' => 'text',
            'required' => true
        ],

    ],


    'Location' => [

        'room' => [
            'label' => 'Room',
            'type' => 'text',
            'required' => true
        ],

        'location' => [
            'label' => 'Location',
            'type' => 'text'
        ],

        'building' => [
            'label' => 'Building',
            'type' => 'text'
        ],

    ],


    'Network' => [

        'ip_address' => [
            'label' => 'IP Address',
            'type' => 'text',
            'required' => true
        ],

        'mac_address' => [
            'label' => 'MAC Address',
            'type' => 'text',
            'required' => true
        ],

        'switch_port' => [
            'label' => 'Switch / Port',
            'type' => 'text',
            'required' => true
        ],

        'ip_phone' => [
            'label' => 'IP Phone',
            'type' => 'text'
        ],

        'extension' => [
            'label' => 'Extension',
            'type' => 'text'
        ],

    ],


    'Hardware' => [

        'cpu_model' => [
            'label' => 'CPU Model',
            'type' => 'text'
        ],

        'processor' => [
            'label' => 'Processor',
            'type' => 'text'
        ],

        'ram' => [
            'label' => 'RAM',
            'type' => 'text'
        ],

        'monitor' => [
            'label' => 'Monitor',
            'type' => 'text'
        ],

        'hardware_description' => [
            'label' => 'Hardware Description',
            'type' => 'textarea'
        ],

    ],


    'Peripherals' => [

        'printer' => [
            'label' => 'Printer',
            'type' => 'text'
        ],

        'scanner' => [
            'label' => 'Scanner',
            'type' => 'text'
        ],

        'ups' => [
            'label' => 'UPS',
            'type' => 'text'
        ],

    ],


    'Device-specific' => [

        'device_model' => [
            'label' => 'Device Model',
            'type' => 'text',
            'required' => true
        ],

        'device_serial' => [
            'label' => 'Device Serial',
            'type' => 'text'
        ],

        'status' => [
            'label' => 'Status',
            'type' => 'text'
        ],

    ],


    'Other' => [

        'notes' => [
            'label' => 'Notes',
            'type' => 'textarea'
        ],

    ],

];


/*
|--------------------------------------------------------------------------
| FLATTEN FIELDS
|--------------------------------------------------------------------------
*/

$FLAT_FIELDS = [];

foreach ($FIELDS as $group => $groupFields) {

    foreach ($groupFields as $key => $meta) {

        $FLAT_FIELDS[$key] = $meta;

    }

}


/*
|--------------------------------------------------------------------------
| REQUEST VARIABLES
|--------------------------------------------------------------------------
*/

$action = $_REQUEST['action'] ?? 'add';

$id = isset($_REQUEST['id'])
    ? (int) $_REQUEST['id']
    : 0;

$sheet = trim(
    $_REQUEST['sheet'] ?? ''
);

$error = '';


/*
|--------------------------------------------------------------------------
| HANDLE POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postAction = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | DELETE RECORD
    |--------------------------------------------------------------------------
    */

    if ($postAction === 'delete' && $id > 0) {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM inventory
                WHERE id = ?
            ");

            $stmt->execute([
                $id
            ]);

            $_SESSION['flash'] =
                "Record #$id deleted.";

        } catch (PDOException $e) {

            error_log(
                'Record - Delete Error: ' .
                $e->getMessage()
            );

            $_SESSION['flash'] =
                "Unable to delete record #$id.";
        }


        /*
        |--------------------------------------------------------------------------
        | SAFE REDIRECT
        |--------------------------------------------------------------------------
        */

        $returnTo = $_POST['return_to'] ?? '';

        if (
            empty($returnTo) ||
            strpos(
                $returnTo,
                BASE_URL . '/'
            ) !== 0
        ) {

            $returnTo =
                BASE_URL . '/index.php';
        }


        header(
            'Location: ' . $returnTo
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE RECORD
    |--------------------------------------------------------------------------
    */

    if ($postAction === 'save') {

        /*
        |--------------------------------------------------------------------------
        | COLLECT FORM DATA
        |--------------------------------------------------------------------------
        */

        $data = [];

        foreach ($FLAT_FIELDS as $key => $meta) {

            $val = trim(
                $_POST[$key] ?? ''
            );

            $data[$key] =
                ($val === '')
                ? null
                : $val;
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE SHEET
        |--------------------------------------------------------------------------
        */

        if (
            empty($data['sheet_name']) ||
            !in_array(
                $data['sheet_name'],
                $ALL_SHEETS,
                true
            )
        ) {

            $error =
                'Please choose a valid active sheet.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING RECORD
            |--------------------------------------------------------------------------
            */

            if ($id > 0) {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Check record exists
                    |--------------------------------------------------------------------------
                    */

                    $checkStmt = $pdo->prepare("
                        SELECT id
                        FROM inventory
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $checkStmt->execute([
                        $id
                    ]);

                    $existingRecord =
                        $checkStmt->fetch();


                    if (!$existingRecord) {

                        $error =
                            "Record #$id not found.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Build UPDATE query
                        |--------------------------------------------------------------------------
                        */

                        $sets = [];

                        foreach (
                            $data as $key => $_
                        ) {

                            $sets[] =
                                "`$key` = :$key";
                        }


                        $params = $data;

                        $params['id'] = $id;


                        $sql =
                            'UPDATE inventory SET ' .
                            implode(', ', $sets) .
                            ' WHERE id = :id';


                        $stmt =
                            $pdo->prepare($sql);


                        $stmt->execute(
                            $params
                        );


                        $_SESSION['flash'] =
                            "Record #$id updated.";


                        /*
                        |--------------------------------------------------------------------------
                        | Redirect to sheet
                        |--------------------------------------------------------------------------
                        */

                        header(
                            'Location: ' .
                            BASE_URL .
                            '/sheet.php?name=' .
                            urlencode(
                                $data['sheet_name']
                            )
                        );

                        exit;
                    }

                } catch (PDOException $e) {

                    error_log(
                        'Record - Update Error: ' .
                        $e->getMessage()
                    );

                    $error =
                        'Database error. Unable to update record.';
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | INSERT NEW RECORD
                |--------------------------------------------------------------------------
                */

                try {

                    $cols =
                        array_keys($data);


                    $colSql =
                        implode(
                            ', ',
                            array_map(
                                fn($c) =>
                                    "`$c`",
                                $cols
                            )
                        );


                    $valSql =
                        implode(
                            ', ',
                            array_map(
                                fn($c) =>
                                    ":$c",
                                $cols
                            )
                        );


                    $sql =
                        "INSERT INTO inventory
                        ($colSql)
                        VALUES
                        ($valSql)";


                    $stmt =
                        $pdo->prepare($sql);


                    $stmt->execute(
                        $data
                    );


                    $newId =
                        (int) $pdo->lastInsertId();


                    $_SESSION['flash'] =
                        "Record #$newId added to {$data['sheet_name']}.";


                    /*
                    |--------------------------------------------------------------------------
                    | Redirect
                    |--------------------------------------------------------------------------
                    */

                    header(
                        'Location: ' .
                        BASE_URL .
                        '/sheet.php?name=' .
                        urlencode(
                            $data['sheet_name']
                        )
                    );

                    exit;

                } catch (PDOException $e) {

                    error_log(
                        'Record - Insert Error: ' .
                        $e->getMessage()
                    );

                    $error =
                        'Database error. Unable to add record.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| INITIAL FORM DATA
|--------------------------------------------------------------------------
*/

$record = array_fill_keys(
    array_keys($FLAT_FIELDS),
    ''
);


/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

if (
    $action === 'edit' &&
    $id > 0
) {

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM inventory
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        $loaded =
            $stmt->fetch();


        if (!$loaded) {

            $_SESSION['flash'] =
                "Record #$id not found.";

            header(
                'Location: ' .
                BASE_URL .
                '/index.php'
            );

            exit;
        }


        foreach (
            $record as $key => $_
        ) {

            $record[$key] =
                $loaded[$key] ?? '';
        }

    } catch (PDOException $e) {

        error_log(
            'Record - Load Error: ' .
            $e->getMessage()
        );

        $error =
            'Unable to load record.';
    }
}


/*
|--------------------------------------------------------------------------
| ADD MODE WITH SELECTED SHEET
|--------------------------------------------------------------------------
*/

elseif (
    $action === 'add' &&
    in_array(
        $sheet,
        $ALL_SHEETS,
        true
    )
) {

    $record['sheet_name'] =
        $sheet;
}


/*
|--------------------------------------------------------------------------
| REPOLULATE FORM AFTER VALIDATION ERROR
|--------------------------------------------------------------------------
*/

if (
    $error &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    foreach (
        $record as $key => $_
    ) {

        $record[$key] =
            $_POST[$key] ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    ($action === 'edit')
        ? "Edit Record #$id"
        : 'Add New Record';


$activeSheet =
    $record['sheet_name']
    ?: null;


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require __DIR__ . '/includes/header.php';

?>

<div class="d-flex align-items-center mb-4">

    <a
        href="<?= BASE_URL ?>/<?= $record['sheet_name']
            ? 'sheet.php?name=' .
              urlencode(
                  $record['sheet_name']
              )
            : 'index.php'
        ?>"
        class="btn btn-sm btn-outline-secondary me-3"
    >

        <i class="bi bi-arrow-left"></i>

        Back

    </a>


    <h3 class="mb-0">

        <?php if ($action === 'edit'): ?>

            <i class="bi bi-pencil"></i>

            Edit Record #<?= (int) $id ?>

        <?php else: ?>

            <i class="bi bi-plus-circle"></i>

            Add New Record

        <?php endif; ?>

    </h3>

</div>


<?php if ($error): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>


<?php if (empty($ALL_SHEETS)): ?>

    <div class="alert alert-warning">

        <strong>No active sheets available.</strong>

        Please ask an administrator to create or activate a sheet
        before adding a record.

    </div>

<?php endif; ?>


<form
    method="post"
    action="<?= BASE_URL ?>/record.php"
    class="card shadow-sm border-0"
>

    <input
        type="hidden"
        name="action"
        value="save"
    >


    <?php if ($id > 0): ?>

        <input
            type="hidden"
            name="id"
            value="<?= (int) $id ?>"
        >

    <?php endif; ?>


    <div class="card-body p-4">


        <?php foreach (
            $FIELDS as $group => $groupFields
        ): ?>


            <h6
                class="text-muted text-uppercase small fw-bold mt-3 mb-3 pb-2 border-bottom"
            >

                <?= htmlspecialchars(
                    $group,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </h6>


            <div class="row g-3 mb-2">


                <?php foreach (
                    $groupFields as $key => $meta
                ):

                    $value =
                        $record[$key] ?? '';

                    $colWidth =
                        $meta['type'] === 'textarea'
                            ? 12
                            : 4;

                ?>


                    <div
                        class="col-md-6 col-lg-<?= $colWidth ?>"
                    >


                        <label
                            class="form-label small"
                        >

                            <?= htmlspecialchars(
                                $meta['label'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>


                            <?php if (
                                !empty(
                                    $meta['required']
                                )
                            ): ?>

                                <span class="text-danger">
                                    *
                                </span>

                            <?php endif; ?>


                        </label>


                        <?php if (
                            $meta['type'] === 'select' &&
                            $key === 'sheet_name'
                        ): ?>


                            <select
                                name="<?= htmlspecialchars(
                                    $key,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="form-select"
                                <?= !empty(
                                    $meta['required']
                                )
                                    ? 'required'
                                    : ''
                                ?>
                            >

                                <option value="">
                                    — choose sheet —
                                </option>


                                <?php foreach (
                                    $ALL_SHEETS as $opt
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $opt,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= $value === $opt
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $opt,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>


                        <?php elseif (
                            $meta['type'] === 'textarea'
                        ): ?>


                            <textarea
                                name="<?= htmlspecialchars(
                                    $key,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="form-control"
                                rows="3"
                            ><?= htmlspecialchars(
                                $value,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>


                        <?php else: ?>


                            <input
                                type="<?= htmlspecialchars(
                                    $meta['type'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                name="<?= htmlspecialchars(
                                    $key,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $value,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= !empty(
                                    $meta['required']
                                )
                                    ? 'required'
                                    : ''
                                ?>
                            >


                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endforeach; ?>


    </div>


    <div
        class="card-footer d-flex justify-content-between bg-white py-3"
    >


        <a
            href="<?= BASE_URL ?>/<?= $record['sheet_name']
                ? 'sheet.php?name=' .
                  urlencode(
                      $record['sheet_name']
                  )
                : 'index.php'
            ?>"
            class="btn btn-outline-secondary"
        >

            Cancel

        </a>


        <div>


            <?php if ($id > 0): ?>


                <button
                    type="button"
                    class="btn btn-outline-danger me-2"
                    onclick="if(confirm('Delete this record permanently? This cannot be undone.')) document.getElementById('delForm').submit();"
                >

                    <i class="bi bi-trash"></i>

                    Delete

                </button>


            <?php endif; ?>


            <button
                type="submit"
                class="btn btn-primary px-4"
                <?= empty($ALL_SHEETS)
                    ? 'disabled'
                    : ''
                ?>
            >

                <i class="bi bi-check-lg"></i>

                <?= $id > 0
                    ? 'Save Changes'
                    : 'Add Record'
                ?>

            </button>


        </div>


    </div>


</form>



<?php if ($id > 0): ?>


    <form
        id="delForm"
        method="post"
        action="<?= BASE_URL ?>/record.php"
        style="display:none"
    >

        <input
            type="hidden"
            name="action"
            value="delete"
        >


        <input
            type="hidden"
            name="id"
            value="<?= (int) $id ?>"
        >


    </form>


<?php endif; ?>


<?php

require __DIR__ . '/includes/footer.php';

?>