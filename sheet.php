<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sheets_list.php';


/*
|--------------------------------------------------------------------------
| Read Access
|--------------------------------------------------------------------------
|
| Admin + User + Viewer can access this page.
|
*/
requireViewerOrAbove();


/*
|--------------------------------------------------------------------------
| Get Sheet Name
|--------------------------------------------------------------------------
*/

$sheet = trim($_GET['name'] ?? '');


/*
|--------------------------------------------------------------------------
| Validate Sheet Name
|--------------------------------------------------------------------------
|
| Sheet names are now managed from the database.
|
| Only ACTIVE sheets can be opened.
|
| This replaces the old:
|
| in_array($sheet, $ALL_SHEETS, true)
|
*/

$sheetInfo = getActiveSheetByName($sheet);


if (!$sheetInfo) {

    header(
        'Location: ' .
        BASE_URL .
        '/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Use Official Sheet Name From Database
|--------------------------------------------------------------------------
*/

$sheet = $sheetInfo['sheet_name'];


/*
|--------------------------------------------------------------------------
| Load Records
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT *
     FROM inventory
     WHERE sheet_name = ?
     ORDER BY id'
);

$stmt->execute([$sheet]);

$rows = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Page Variables
|--------------------------------------------------------------------------
*/

$pageTitle   = $sheet;
$activeSheet = $sheet;


require __DIR__ . '/includes/header.php';

?>


<!-- ================================================================== -->
<!-- Page Header -->
<!-- ================================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    <div>

        <h3 class="mb-1">

            <i class="bi bi-table"></i>

            <?= htmlspecialchars(
                $sheet,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

            <span class="badge bg-secondary fs-6 ms-2">

                <?= count($rows) ?> rows

            </span>

        </h3>


        <div class="text-muted small">

            <a
                href="<?= BASE_URL ?>/index.php"
                class="text-decoration-none"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Dashboard

            </a>

        </div>

    </div>


    <!-- ============================================================= -->
    <!-- Add Record -->
    <!-- ============================================================= -->

    <?php if (canCreate()): ?>

        <a
            href="<?= BASE_URL ?>/record.php?action=add&sheet=<?= urlencode($sheet) ?>"
            class="btn btn-primary"
        >

            <i class="bi bi-plus-lg"></i>

            Add New Record

        </a>

    <?php endif; ?>

</div>



<!-- ================================================================== -->
<!-- Empty State -->
<!-- ================================================================== -->

<?php if (count($rows) === 0): ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body text-center p-5">

            <i class="bi bi-inbox display-4 text-muted"></i>

            <p class="mt-3 mb-3 text-muted">

                No records yet in this sheet.

            </p>


            <?php if (canCreate()): ?>

                <a
                    href="<?= BASE_URL ?>/record.php?action=add&sheet=<?= urlencode($sheet) ?>"
                    class="btn btn-primary"
                >

                    <i class="bi bi-plus-lg"></i>

                    Add the first record

                </a>

            <?php else: ?>

                <span class="text-muted small">

                    No records available.

                </span>

            <?php endif; ?>

        </div>

    </div>


<?php else: ?>


    <!-- ============================================================= -->
    <!-- Records Table -->
    <!-- ============================================================= -->

    <div class="card shadow-sm border-0 p-3">

        <table
            class="table table-hover align-middle small datatable"
            style="width:100%"
        >

            <thead class="table-light">

                <tr>

                    <th style="width:60px;">
                        #
                    </th>

                    <th>
                        Name / Device
                    </th>

                    <th>
                        Employee ID
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Contact
                    </th>

                    <th>
                        Room / Location
                    </th>

                    <th>
                        IP
                    </th>

                    <th>
                        MAC
                    </th>

                    <th>
                        Dept / Designation
                    </th>

                    <th
                        class="text-end no-sort"
                        style="width:160px;"
                    >
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($rows as $r): ?>

                    <tr>

                        <!-- ID -->

                        <td class="text-muted">

                            #<?= (int) $r['id'] ?>

                        </td>


                        <!-- Name / Device -->

                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $r['full_name']
                                    ?: (
                                        $r['device_model']
                                        ?: '—'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </strong>

                        </td>


                        <!-- Employee ID -->

                        <td>

                            <?= htmlspecialchars(
                                $r['employee_id']
                                ?: '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <!-- Email -->

                        <td>

                            <?= htmlspecialchars(
                                $r['email']
                                ?: '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <!-- Contact -->

                        <td>

                            <?= htmlspecialchars(
                                $r['contact_number']
                                ?: '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <!-- Room / Location -->

                        <td>

                            <?= htmlspecialchars(
                                $r['room']
                                ?: (
                                    $r['location']
                                    ?: '—'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <!-- IP -->

                        <td>

                            <?= htmlspecialchars(
                                $r['ip_address']
                                ?: '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <!-- MAC -->

                        <td>

                            <small>

                                <?= htmlspecialchars(
                                    $r['mac_address']
                                    ?: '—',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </small>

                        </td>


                        <!-- Department / Designation -->

                        <td>

                            <?php if (!empty($r['department'])): ?>

                                <?= htmlspecialchars(
                                    $r['department'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <br>

                            <?php endif; ?>


                            <small class="text-muted">

                                <?= htmlspecialchars(
                                    $r['designation']
                                    ?: '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </small>

                        </td>


                        <!-- ================================================= -->
                        <!-- Actions -->
                        <!-- ================================================= -->

                        <td class="text-end">


                            <!-- View -->

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-info btn-view"
                                data-id="<?= (int) $r['id'] ?>"
                                title="View details"
                            >

                                <i class="bi bi-eye"></i>

                            </button>



                            <!-- Edit -->
                            <!-- Admin + User -->

                            <?php if (canEdit()): ?>

                                <a
                                    href="<?= BASE_URL ?>/record.php?action=edit&id=<?= (int) $r['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Edit"
                                >

                                    <i class="bi bi-pencil"></i>

                                </a>

                            <?php endif; ?>



                            <!-- Print -->
                            <!-- Everyone -->

                            <a
                                href="<?= BASE_URL ?>/record_print.php?id=<?= (int) $r['id'] ?>"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary"
                                title="Print / Save as PDF"
                            >

                                <i class="bi bi-printer"></i>

                            </a>



                            <!-- Delete -->
                            <!-- Admin + User -->

                            <?php if (canDelete()): ?>

                                <form
                                    method="post"
                                    action="<?= BASE_URL ?>/record.php"
                                    style="display:inline"
                                    onsubmit="return confirm('Delete this record permanently? This cannot be undone.');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $r['id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="<?= htmlspecialchars(
                                            BASE_URL .
                                            '/sheet.php?name=' .
                                            urlencode($sheet),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>



<!-- ================================================================== -->
<!-- View Modal -->
<!-- ================================================================== -->

<div
    class="modal fade"
    id="viewModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content">


            <!-- Header -->

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-info-circle"></i>

                    Record Details

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <!-- Body -->

            <div
                class="modal-body"
                id="viewModalBody"
            >

                <div class="text-center py-5">

                    <div class="spinner-border text-primary"></div>

                </div>

            </div>


            <!-- Footer -->

            <div class="modal-footer">


                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal"
                >

                    Close

                </button>



                <!-- Print -->
                <!-- Everyone -->

                <a
                    href="#"
                    target="_blank"
                    class="btn btn-outline-secondary"
                    id="viewModalPrint"
                >

                    <i class="bi bi-printer"></i>

                    Print

                </a>



                <!-- Edit -->
                <!-- Admin + User -->

                <?php if (canEdit()): ?>

                    <a
                        href="#"
                        class="btn btn-primary"
                        id="viewModalEdit"
                    >

                        <i class="bi bi-pencil"></i>

                        Edit Record

                    </a>

                <?php endif; ?>


            </div>

        </div>

    </div>

</div>



<!-- ================================================================== -->
<!-- JavaScript -->
<!-- ================================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        var modalEl =
            document.getElementById(
                'viewModal'
            );


        var modalBody =
            document.getElementById(
                'viewModalBody'
            );


        var modalPrint =
            document.getElementById(
                'viewModalPrint'
            );


        /*
        |--------------------------------------------------------------------------
        | Edit Button
        |--------------------------------------------------------------------------
        |
        | Only exists for Admin/User.
        |
        */

        var modalEdit =
            document.getElementById(
                'viewModalEdit'
            );


        if (
            !modalEl ||
            !window.bootstrap
        ) {
            return;
        }


        var modal =
            new bootstrap.Modal(
                modalEl
            );


        /*
        |--------------------------------------------------------------------------
        | Event Delegation
        |--------------------------------------------------------------------------
        |
        | Works with DataTables pagination.
        |
        */

        document.addEventListener(
            'click',
            function (e) {

                var btn =
                    e.target.closest(
                        '.btn-view'
                    );


                if (!btn) {
                    return;
                }


                e.preventDefault();


                var id =
                    btn.dataset.id;


                /*
                |--------------------------------------------------------------------------
                | Loading State
                |--------------------------------------------------------------------------
                */

                modalBody.innerHTML =
                    '<div class="text-center py-5">' +
                    '<div class="spinner-border text-primary"></div>' +
                    '</div>';


                /*
                |--------------------------------------------------------------------------
                | Print URL
                |--------------------------------------------------------------------------
                */

                if (modalPrint) {

                    modalPrint.href =
                        '<?= BASE_URL ?>/record_print.php?id=' +
                        encodeURIComponent(id);

                }


                /*
                |--------------------------------------------------------------------------
                | Edit URL
                |--------------------------------------------------------------------------
                |
                | Only Admin/User have this button.
                |
                */

                if (modalEdit) {

                    modalEdit.href =
                        '<?= BASE_URL ?>/record.php?action=edit&id=' +
                        encodeURIComponent(id);

                }


                /*
                |--------------------------------------------------------------------------
                | Show Modal
                |--------------------------------------------------------------------------
                */

                modal.show();


                /*
                |--------------------------------------------------------------------------
                | Load Details
                |--------------------------------------------------------------------------
                */

                fetch(
                    '<?= BASE_URL ?>/record_view.php?id=' +
                    encodeURIComponent(id)
                )

                .then(
                    function (r) {

                        if (!r.ok) {

                            throw new Error(
                                'HTTP ' + r.status
                            );

                        }

                        return r.text();

                    }
                )

                .then(
                    function (html) {

                        modalBody.innerHTML =
                            html;

                    }
                )

                .catch(
                    function () {

                        modalBody.innerHTML =
                            '<div class="alert alert-danger m-3">' +
                            'Failed to load record details.' +
                            '</div>';

                    }
                );

            }
        );

    }
);

</script>


<?php

require __DIR__ . '/includes/footer.php';

?>
