<?php
// =====================================================================
// record_print.php — Professional A4 printable record view
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireViewerOrAbove();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Invalid record ID.');
}

$stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    die('Record not found.');
}


// =====================================================================
// Field labels
// =====================================================================

$FIELD_LABELS = [
    'employee_id'           => 'Employee ID',
    'email'                 => 'Email',
    'username'              => 'Username',
    'contact_number'        => 'Contact',
    'designation'           => 'Designation',
    'department'            => 'Department',
    'room'                  => 'Room',
    'location'              => 'Location',
    'building'              => 'Building',
    'ip_address'            => 'IP Address',
    'mac_address'           => 'MAC Address',
    'switch_port'           => 'Switch / Port',
    'ip_phone'              => 'IP Phone',
    'extension'             => 'Extension',
    'cpu_model'             => 'CPU Model',
    'processor'             => 'Processor',
    'ram'                   => 'RAM',
    'monitor'               => 'Monitor',
    'hardware_description'  => 'Hardware',
    'printer'               => 'Printer',
    'scanner'               => 'Scanner',
    'ups'                   => 'UPS',
    'device_model'          => 'Device Model',
    'device_serial'         => 'Device S/N',
    'status'                => 'Status',
    'notes'                 => 'Notes',
];


// =====================================================================
// Record title
// =====================================================================

$title = $row['full_name']
    ?: ($row['device_model'] ?: '(Unnamed Record)');

$sheetName = $row['sheet_name'] ?: 'Inventory';


// =====================================================================
// Group fields for better visual layout
// =====================================================================

$identityFields = [
    'employee_id',
    'email',
    'username',
    'contact_number',
    'designation',
    'department',
];

$locationFields = [
    'room',
    'location',
    'building',
];

$networkFields = [
    'ip_address',
    'mac_address',
    'switch_port',
    'ip_phone',
    'extension',
];

$hardwareFields = [
    'device_model',
    'device_serial',
    'cpu_model',
    'processor',
    'ram',
    'monitor',
    'hardware_description',
    'printer',
    'scanner',
    'ups',
];


// =====================================================================
// Helper function
// =====================================================================

function hasValue(array $row, string $key): bool
{
    return isset($row[$key]) && trim((string)$row[$key]) !== '';
}

function renderField(array $row, array $labels, string $key): void
{
    if (!hasValue($row, $key)) {
        return;
    }
    ?>
    <div class="field">
        <div class="field-label">
            <?= htmlspecialchars($labels[$key] ?? $key) ?>
        </div>

        <div class="field-value">
            <?= nl2br(htmlspecialchars($row[$key])) ?>
        </div>
    </div>
    <?php
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
        <?= htmlspecialchars($title) ?> —
        AIUB IT Database Portal
    </title>


    <style>

        /* =============================================================
           BASE
        ============================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #eef1f5;
            color: #1f2937;
            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            font-size: 13px;
            line-height: 1.45;
        }


        /* =============================================================
           SCREEN TOOLBAR
        ============================================================= */

        .toolbar {
            width: 210mm;
            margin: 20px auto 12px;

            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .toolbar button,
        .toolbar a {
            border-radius: 7px;
            padding: 9px 16px;

            font-size: 13px;
            font-weight: 600;

            text-decoration: none;
            cursor: pointer;

            transition: 0.15s ease;
        }

        .btn-print {
            background: #0d6efd;
            border: 1px solid #0d6efd;
            color: #fff;
        }

        .btn-print:hover {
            background: #0b5ed7;
        }

        .btn-close {
            background: #fff;
            border: 1px solid #cfd4da;
            color: #495057;
        }

        .btn-close:hover {
            background: #f1f3f5;
        }


        /* =============================================================
           A4 DOCUMENT
        ============================================================= */

        .page {
            width: 210mm;
            min-height: 297mm;

            margin: 0 auto 30px;

            background: #fff;

            padding: 13mm 14mm 12mm;

            box-shadow:
                0 4px 18px rgba(0, 0, 0, 0.12);

            position: relative;
        }


        /* =============================================================
           HEADER / LETTERHEAD
        ============================================================= */

        .letterhead {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding-bottom: 10px;

            border-bottom: 2px solid #111827;

            margin-bottom: 13px;
        }

        .university-block {
            flex: 1;
        }

        .university-name {
            font-family:
                "Times New Roman",
                Georgia,
                serif;

            font-size: 20px;
            font-weight: 700;

            color: #111827;

            line-height: 1.15;
        }

        .university-subtitle {
            margin-top: 4px;

            font-size: 10px;
            color: #6b7280;

            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .logo {
            width: 65px;
            height: 65px;

            object-fit: contain;

            flex-shrink: 0;
        }


        /* =============================================================
           DOCUMENT TITLE
        ============================================================= */

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;

            margin-bottom: 12px;
        }

        .document-title {
            flex: 1;
        }

        .portal-label {
            font-size: 9px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.12em;

            color: #0d6efd;

            margin-bottom: 3px;
        }

        .record-title {
            margin: 0;

            font-size: 22px;
            line-height: 1.15;

            color: #111827;
        }

        .record-subtitle {
            margin-top: 5px;

            font-size: 11px;
            color: #6b7280;
        }

        .record-meta {
            min-width: 135px;

            text-align: right;
        }

        .sheet-badge {
            display: inline-block;

            padding: 4px 9px;

            border-radius: 5px;

            background: #0d6efd;
            color: #fff;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 0.03em;
        }

        .record-number {
            margin-top: 5px;

            font-size: 10px;
            color: #6b7280;
        }


        /* =============================================================
           SECTION
        ============================================================= */

        .section {
            margin-top: 11px;

            border: 1px solid #dfe3e8;

            border-radius: 7px;

            overflow: hidden;

            break-inside: avoid;
            page-break-inside: avoid;
        }

        .section-title {
            padding: 6px 10px;

            background: #f4f6f8;

            border-bottom: 1px solid #dfe3e8;

            font-size: 10px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.08em;

            color: #374151;
        }

        .section-body {
            padding: 8px 10px;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            column-gap: 20px;
            row-gap: 7px;
        }


        /* =============================================================
           FIELD
        ============================================================= */

        .field {
            min-width: 0;

            padding-bottom: 5px;

            border-bottom: 1px solid #f0f1f3;
        }

        .field:nth-last-child(-n + 2) {
            border-bottom: none;
        }

        .field-label {
            font-size: 8.5px;

            font-weight: 700;

            color: #6b7280;

            text-transform: uppercase;

            letter-spacing: 0.05em;

            margin-bottom: 2px;
        }

        .field-value {
            font-size: 12px;

            color: #111827;

            overflow-wrap: anywhere;
        }


        /* =============================================================
           FULL WIDTH FIELD
        ============================================================= */

        .full-width {
            grid-column: 1 / -1;
        }

        .full-width .field-value {
            min-height: 28px;
        }


        /* =============================================================
           STATUS
        ============================================================= */

        .status-value {
            display: inline-block;

            padding: 3px 9px;

            border-radius: 12px;

            background: #e8f5e9;

            color: #176b35;

            font-size: 10px;
            font-weight: 700;
        }


        /* =============================================================
           FOOTER
        ============================================================= */

        .document-footer {
            margin-top: 14px;

            padding-top: 8px;

            border-top: 1px solid #dfe3e8;

            display: flex;
            justify-content: space-between;
            align-items: center;

            font-size: 9px;

            color: #7b8490;
        }

        .footer-right {
            text-align: right;
        }


        /* =============================================================
           PRINT SETTINGS
        ============================================================= */

        @page {
            size: A4;
            margin: 0;
        }

        @media print {

            html,
            body {
                background: #fff;
            }

            body {
                width: 210mm;
            }

            .toolbar {
                display: none !important;
            }

            .page {
                width: 210mm;
                min-height: 297mm;

                margin: 0;

                padding: 13mm 14mm 12mm;

                box-shadow: none;

                page-break-after: avoid;
                break-after: avoid;
            }

            .section {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .letterhead {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .document-header {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .document-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            a {
                color: inherit;
                text-decoration: none;
            }
        }


        /* =============================================================
           SMALL SCREEN PREVIEW
        ============================================================= */

        @media screen and (max-width: 850px) {

            .toolbar {
                width: auto;
                margin: 12px;

                justify-content: center;
            }

            .page {
                width: calc(100% - 24px);
                min-height: auto;

                margin: 0 12px 20px;

                padding: 25px 20px;
            }

            .section-body {
                grid-template-columns: 1fr;
            }

            .field:nth-last-child(-n + 2) {
                border-bottom: 1px solid #f0f1f3;
            }

            .field:last-child {
                border-bottom: none;
            }

            .document-header {
                flex-direction: column;
            }

            .record-meta {
                text-align: left;
            }
        }

    </style>
</head>


<body>


<!-- ================================================================
     SCREEN TOOLBAR
================================================================ -->

<div class="toolbar">

    <a
        href="javascript:window.close()"
        class="btn-close"
    >
        Close
    </a>

    <button
        type="button"
        class="btn-print"
        onclick="window.print()"
    >
        🖨 Print / Save as PDF
    </button>

</div>


<!-- ================================================================
     A4 PAGE
================================================================ -->

<div class="page">


    <!-- ============================================================
         UNIVERSITY LETTERHEAD
    ============================================================= -->

    <div class="letterhead">

        <div class="university-block">

            <div class="university-name">
                AMERICAN INTERNATIONAL UNIVERSITY-BANGLADESH
            </div>

            <div class="university-subtitle">
                Office of Information Technology
            </div>

        </div>


        <img
            src="<?= BASE_URL ?>/assets/aiub-logo.svg"
            class="logo"
            alt="AIUB Logo"
        >

    </div>


    <!-- ============================================================
         DOCUMENT HEADER
    ============================================================= -->

    <div class="document-header">

        <div class="document-title">

            <div class="portal-label">
                IT Database Portal
            </div>

            <h1 class="record-title">
                <?= htmlspecialchars($title) ?>
            </h1>

            <div class="record-subtitle">
                Inventory / Personnel Record
            </div>

        </div>


        <div class="record-meta">

            <div class="sheet-badge">
                <?= htmlspecialchars($sheetName) ?>
            </div>

            <div class="record-number">
                Record #<?= (int)$row['id'] ?>
            </div>

        </div>

    </div>


    <!-- ============================================================
         PERSONAL / EMPLOYEE INFORMATION
    ============================================================= -->

    <?php
    $hasIdentity = false;

    foreach ($identityFields as $key) {
        if (hasValue($row, $key)) {
            $hasIdentity = true;
            break;
        }
    }
    ?>

    <?php if ($hasIdentity): ?>

        <div class="section">

            <div class="section-title">
                Personnel Information
            </div>

            <div class="section-body">

                <?php foreach ($identityFields as $key): ?>

                    <?php
                    if (hasValue($row, $key)) {
                        renderField($row, $FIELD_LABELS, $key);
                    }
                    ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         LOCATION
    ============================================================= -->

    <?php
    $hasLocation = false;

    foreach ($locationFields as $key) {
        if (hasValue($row, $key)) {
            $hasLocation = true;
            break;
        }
    }
    ?>

    <?php if ($hasLocation): ?>

        <div class="section">

            <div class="section-title">
                Location
            </div>

            <div class="section-body">

                <?php foreach ($locationFields as $key): ?>

                    <?php
                    if (hasValue($row, $key)) {
                        renderField($row, $FIELD_LABELS, $key);
                    }
                    ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         NETWORK INFORMATION
    ============================================================= -->

    <?php
    $hasNetwork = false;

    foreach ($networkFields as $key) {
        if (hasValue($row, $key)) {
            $hasNetwork = true;
            break;
        }
    }
    ?>

    <?php if ($hasNetwork): ?>

        <div class="section">

            <div class="section-title">
                Network & Connectivity
            </div>

            <div class="section-body">

                <?php foreach ($networkFields as $key): ?>

                    <?php
                    if (hasValue($row, $key)) {
                        renderField($row, $FIELD_LABELS, $key);
                    }
                    ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         HARDWARE INFORMATION
    ============================================================= -->

    <?php
    $hasHardware = false;

    foreach ($hardwareFields as $key) {
        if (hasValue($row, $key)) {
            $hasHardware = true;
            break;
        }
    }
    ?>

    <?php if ($hasHardware): ?>

        <div class="section">

            <div class="section-title">
                Hardware & Equipment
            </div>

            <div class="section-body">

                <?php foreach ($hardwareFields as $key): ?>

                    <?php if (hasValue($row, $key)): ?>

                        <?php
                        $isLongField = in_array(
                            $key,
                            [
                                'hardware_description',
                                'printer',
                                'scanner',
                                'ups'
                            ],
                            true
                        );
                        ?>

                        <div class="field <?= $isLongField ? 'full-width' : '' ?>">

                            <div class="field-label">
                                <?= htmlspecialchars(
                                    $FIELD_LABELS[$key] ?? $key
                                ) ?>
                            </div>

                            <div class="field-value">

                                <?php if ($key === 'status'): ?>

                                    <span class="status-value">
                                        <?= htmlspecialchars($row[$key]) ?>
                                    </span>

                                <?php else: ?>

                                    <?= nl2br(
                                        htmlspecialchars($row[$key])
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         STATUS + NOTES
    ============================================================= -->

    <?php
    $hasStatus = hasValue($row, 'status');
    $hasNotes  = hasValue($row, 'notes');
    ?>

    <?php if ($hasStatus || $hasNotes): ?>

        <div class="section">

            <div class="section-title">
                Additional Information
            </div>

            <div class="section-body">

                <?php if ($hasStatus): ?>

                    <div class="field">

                        <div class="field-label">
                            Status
                        </div>

                        <div class="field-value">

                            <span class="status-value">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>

                        </div>

                    </div>

                <?php endif; ?>


                <?php if ($hasNotes): ?>

                    <div class="field full-width">

                        <div class="field-label">
                            Notes
                        </div>

                        <div class="field-value">
                            <?= nl2br(
                                htmlspecialchars($row['notes'])
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- ============================================================
         FOOTER
    ============================================================= -->

    <div class="document-footer">

        <div>
            AIUB IT Database Portal
            &nbsp;•&nbsp;
            Record #<?= (int)$row['id'] ?>
        </div>

        <div class="footer-right">

            Printed by
            <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>

            <br>

            <?= date('d M Y, h:i A') ?>

        </div>

    </div>


</div>


<!-- ================================================================
     AUTO PRINT
================================================================ -->

<script>

    window.addEventListener('load', function () {

        setTimeout(function () {

            window.print();

        }, 300);

    });

</script>


</body>
</html>