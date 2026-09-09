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
            'type' => 'text'
        ],

        'designation' => [
            'label' => 'Designation',
            'type' => 'text'
        ],

        'department' => [
            'label' => 'Department',
            'type' => 'text'
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

        'storage' => [
            'label' => 'Storage',
            'type' => 'text'
        ],

        'gpu' => [
            'label' => 'GPU',
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
            'type' => 'text'
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


<!-- ================================================================
     RECORD PAGE DESIGN
================================================================ -->

<style>

    /* ---------------------------------------------------------------
       Page Header
    --------------------------------------------------------------- */

    .record-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .record-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .record-title-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e7f1ff;
        color: #0d6efd;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .record-page-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 650;
        color: #212529;
    }

    .record-page-subtitle {
        margin: 0.2rem 0 0;
        color: #6c757d;
        font-size: 0.85rem;
    }


    /* ---------------------------------------------------------------
       Back Button
    --------------------------------------------------------------- */

    .record-back-btn {
        border-radius: 9px;
        padding: 0.48rem 0.85rem;
        font-weight: 500;
        background: #fff;
    }

    .record-back-btn:hover {
        background: #f8f9fa;
    }


    /* ---------------------------------------------------------------
       Alerts
    --------------------------------------------------------------- */

    .record-alert {
        border: 0;
        border-radius: 10px;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
    }


    /* ---------------------------------------------------------------
       Main Form Card
    --------------------------------------------------------------- */

    .record-form-card {
        background: #fff;
        border: 1px solid #e7e9ed;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.045);
    }

    .record-form-body {
        padding: 1.5rem;
    }


    /* ---------------------------------------------------------------
       Form Sections
    --------------------------------------------------------------- */

    .record-section {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        overflow: hidden;
        background: #fff;
    }

    .record-section:last-child {
        margin-bottom: 0;
    }

    .record-section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        background: #f8f9fb;
        border-bottom: 1px solid #e9ecef;
    }

    .record-section-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e7f1ff;
        color: #0d6efd;
        font-size: 0.95rem;
    }

    .record-section-title {
        margin: 0;
        font-size: 0.88rem;
        font-weight: 650;
        color: #343a40;
    }

    .record-section-description {
        margin: 0.1rem 0 0;
        color: #8a9199;
        font-size: 0.72rem;
    }

    .record-section-body {
        padding: 1.1rem;
    }


    /* ---------------------------------------------------------------
       Form Fields
    --------------------------------------------------------------- */

    .record-field {
        margin-bottom: 0.15rem;
    }

    .record-field .form-label {
        color: #495057;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 0.42rem;
    }

    .record-field .required-mark {
        color: #dc3545;
        margin-left: 2px;
    }

    .record-field .form-control,
    .record-field .form-select {
        min-height: 42px;
        border-radius: 8px;
        border: 1px solid #dfe3e8;
        font-size: 0.88rem;
        padding: 0.55rem 0.75rem;
        box-shadow: none;
        transition: border-color 0.15s ease,
                    box-shadow 0.15s ease;
    }

    .record-field textarea.form-control {
        min-height: 92px;
        resize: vertical;
    }

    .record-field .form-control:focus,
    .record-field .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.18rem rgba(13, 110, 253, 0.10);
    }

    .record-field .form-control::placeholder {
        color: #adb5bd;
    }


    /* ---------------------------------------------------------------
       Footer / Actions
    --------------------------------------------------------------- */

    .record-form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: #fafbfc;
        border-top: 1px solid #e9ecef;
    }

    .record-actions {
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }

    .record-action-btn {
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 550;
        padding: 0.55rem 1rem;
    }

    .record-save-btn {
        min-width: 145px;
    }


    /* ---------------------------------------------------------------
       Empty Sheet Warning
    --------------------------------------------------------------- */

    .record-empty-state {
        border: 1px dashed #ffc107;
        background: #fffaf0;
        border-radius: 10px;
        color: #664d03;
        padding: 1rem 1.1rem;
    }


    /* ---------------------------------------------------------------
       Responsive
    --------------------------------------------------------------- */

    @media (max-width: 768px) {

        .record-page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .record-back-btn {
            order: 2;
        }

        .record-form-body {
            padding: 1rem;
        }

        .record-section-body {
            padding: 0.9rem;
        }

        .record-form-footer {
            align-items: stretch;
            flex-direction: column;
            padding: 1rem;
        }

        .record-actions {
            width: 100%;
            flex-direction: column-reverse;
        }

        .record-action-btn,
        .record-save-btn {
            width: 100%;
        }

    }

</style>


<?php

/*
|--------------------------------------------------------------------------
| Section Icons
|--------------------------------------------------------------------------
*/

$sectionIcons = [

    'Basic'          => 'bi-grid-1x2',
    'Identity'       => 'bi-person-vcard',
    'Location'       => 'bi-geo-alt',
    'Network'        => 'bi-diagram-3',
    'Hardware'       => 'bi-pc-display',
    'Peripherals'    => 'bi-printer',
    'Device-specific'=> 'bi-device-ssd',
    'Other'          => 'bi-sticky',

];

?>


<!-- ================================================================
     PAGE HEADER
================================================================ -->

<div class="record-page-header">

    <div class="record-title-wrap">

        <a
            href="<?= BASE_URL ?>/<?= $record['sheet_name']
                ? 'sheet.php?name=' .
                  urlencode(
                      $record['sheet_name']
                  )
                : 'index.php'
            ?>"
            class="btn btn-outline-secondary btn-sm record-back-btn"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Back

        </a>


        <div class="record-title-icon">

            <i class="bi <?= $action === 'edit'
                ? 'bi-pencil-square'
                : 'bi-plus-lg'
            ?>"></i>

        </div>


        <div>

            <h3 class="record-page-title">

                <?= $action === 'edit'
                    ? 'Edit Record #' . (int) $id
                    : 'Add New Record'
                ?>

            </h3>


            <p class="record-page-subtitle">

                <?= $action === 'edit'
                    ? 'Update the information associated with this inventory record.'
                    : 'Enter the required information to create a new inventory record.'
                ?>

            </p>

        </div>

    </div>

</div>


<!-- ================================================================
     ERROR MESSAGE
================================================================ -->

<?php if ($error): ?>

    <div class="alert alert-danger record-alert" role="alert">

        <i class="bi bi-exclamation-triangle-fill me-2"></i>

        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>


<!-- ================================================================
     NO ACTIVE SHEETS
================================================================ -->

<?php if (empty($ALL_SHEETS)): ?>

    <div class="record-empty-state mb-3">

        <div class="d-flex align-items-start">

            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>

            <div>

                <strong>No active sheets available.</strong>

                <div class="small mt-1">

                    Please ask an administrator to create or activate
                    a sheet before adding a record.

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>


<!-- ================================================================
     MAIN FORM
================================================================ -->

<form
    method="post"
    action="<?= BASE_URL ?>/record.php"
    class="record-form-card"
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


    <div class="record-form-body">


        <?php foreach (
            $FIELDS as $group => $groupFields
        ): ?>


            <?php

            $sectionIcon =
                $sectionIcons[$group]
                ?? 'bi-folder';

            ?>


            <!-- ====================================================
                 FORM SECTION
            ===================================================== -->

            <section class="record-section">


                <div class="record-section-header">

                    <div class="record-section-icon">

                        <i class="bi <?= $sectionIcon ?>"></i>

                    </div>


                    <div>

                        <h6 class="record-section-title">

                            <?= htmlspecialchars(
                                $group,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h6>


                        <p class="record-section-description">

                            <?php

                            $descriptions = [

                                'Basic' =>
                                    'Select the inventory sheet for this record.',

                                'Identity' =>
                                    'Employee and user identification information.',

                                'Location' =>
                                    'Physical location and room information.',

                                'Network' =>
                                    'Network addressing and connectivity information.',

                                'Hardware' =>
                                    'Computer hardware and system specifications.',

                                'Peripherals' =>
                                    'Connected peripheral devices and accessories.',

                                'Device-specific' =>
                                    'Device model, serial number and current status.',

                                'Other' =>
                                    'Additional notes and relevant information.',

                            ];

                            echo htmlspecialchars(
                                $descriptions[$group]
                                ?? 'Additional information.',
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </p>

                    </div>

                </div>


                <div class="record-section-body">

                    <div class="row g-3">


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
                                class="col-12 col-md-6 col-lg-<?= $colWidth ?>"
                            >

                                <div class="record-field">


                                    <!-- Field Label -->

                                    <label
                                        for="field_<?= htmlspecialchars(
                                            $key,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        class="form-label"
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

                                            <span class="required-mark">
                                                *
                                            </span>

                                        <?php endif; ?>

                                    </label>


                                    <!-- Sheet Select -->

                                    <?php if (
                                        $meta['type'] === 'select' &&
                                        $key === 'sheet_name'
                                    ): ?>


                                        <select
                                            id="field_<?= htmlspecialchars(
                                                $key,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
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
                                                — Choose Sheet —
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


                                    <!-- Textarea -->

                                    <?php elseif (
                                        $meta['type'] === 'textarea'
                                    ): ?>


                                        <textarea
                                            id="field_<?= htmlspecialchars(
                                                $key,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            name="<?= htmlspecialchars(
                                                $key,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            class="form-control"
                                            rows="3"
                                            placeholder="Enter <?= htmlspecialchars(
                                                strtolower(
                                                    $meta['label']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        ><?= htmlspecialchars(
                                            $value,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?></textarea>


                                    <!-- Normal Input -->

                                    <?php else: ?>


                                        <input
                                            id="field_<?= htmlspecialchars(
                                                $key,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
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
                                            placeholder="Enter <?= htmlspecialchars(
                                                strtolower(
                                                    $meta['label']
                                                ),
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

                            </div>


                        <?php endforeach; ?>


                    </div>

                </div>

            </section>


        <?php endforeach; ?>


    </div>


    <!-- ============================================================
         FORM FOOTER
    ============================================================= -->

    <div class="record-form-footer">


        <!-- Cancel -->

        <a
            href="<?= BASE_URL ?>/<?= $record['sheet_name']
                ? 'sheet.php?name=' .
                  urlencode(
                      $record['sheet_name']
                  )
                : 'index.php'
            ?>"
            class="btn btn-outline-secondary record-action-btn"
        >

            <i class="bi bi-x-lg me-1"></i>

            Cancel

        </a>


        <div class="record-actions">


            <!-- Delete -->

            <?php if ($id > 0): ?>

                <button
                    type="button"
                    class="btn btn-outline-danger record-action-btn"
                    onclick="if(confirm('Delete this record permanently? This cannot be undone.')) document.getElementById('delForm').submit();"
                >

                    <i class="bi bi-trash3 me-1"></i>

                    Delete

                </button>

            <?php endif; ?>


            <!-- Save -->

            <button
                type="submit"
                class="btn btn-primary record-action-btn record-save-btn"
                <?= empty($ALL_SHEETS)
                    ? 'disabled'
                    : ''
                ?>
            >

                <i class="bi bi-check2-circle me-1"></i>

                <?= $id > 0
                    ? 'Save Changes'
                    : 'Add Record'
                ?>

            </button>


        </div>


    </div>


</form>


<!-- ================================================================
     DELETE FORM
================================================================ -->

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

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require __DIR__ . '/includes/footer.php';

?>