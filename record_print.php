<?php
// =====================================================================
// record_print.php — printable / "Save as PDF" view of a single record.
// Opened in a new tab from the Print button. Auto-triggers the browser
// print dialog, where the user can choose "Save as PDF".
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

$title = $row['full_name'] ?: ($row['device_model'] ?: '(unnamed record)');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> — AIUB IT Database Portal</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 32px 40px;
            background: #fff;
        }
        /* University letterhead (name left, logo right) */
        .letterhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .letterhead .uni-name {
            font-family: "Times New Roman", Georgia, serif;
            font-weight: bold;
            font-size: 22px;
            color: #000;
            line-height: 1.2;
        }
        .letterhead .uni-logo {
            height: 84px;
            width: auto;
            flex-shrink: 0;
        }
        .doc-header {
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .doc-header .brand {
            font-size: 13px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #0d6efd;
            font-weight: 600;
        }
        .doc-header h1 { font-size: 24px; margin: 6px 0 4px; }
        .meta-line { font-size: 13px; color: #555; }
        .meta-line .badge {
            display: inline-block;
            background: #0d6efd;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 6px;
        }
        table.fields {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.fields td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            vertical-align: top;
            font-size: 14px;
        }
        table.fields td.label {
            width: 200px;
            background: #f4f6f9;
            color: #555;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .doc-footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 11px;
            color: #888;
            display: flex;
            justify-content: space-between;
        }
        .toolbar {
            margin-bottom: 20px;
            text-align: right;
        }
        .toolbar button, .toolbar a {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #0d6efd;
            cursor: pointer;
            text-decoration: none;
            margin-left: 8px;
        }
        .toolbar button { background: #0d6efd; color: #fff; }
        .toolbar a { background: #fff; color: #555; border-color: #ccc; }
        /* Hide the on-screen toolbar when actually printing */
        @media print {
            .toolbar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="javascript:window.close()">Close</a>
    <button type="button" onclick="window.print()">🖨 Print / Save as PDF</button>
</div>

<div class="letterhead">
    <div class="uni-name">AMERICAN INTERNATIONAL UNIVERSITY- BANGLADESH</div>
    <img src="<?= BASE_URL ?>/assets/aiub-logo.svg" class="uni-logo" alt="AIUB Logo">
</div>

<div class="doc-header">
    <div class="brand">IT Database Portal — Record Details</div>
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="meta-line">
        <span class="badge"><?= htmlspecialchars($row['sheet_name']) ?></span>
        Record #<?= (int) $row['id'] ?>
    </div>
</div>

<?php
$hasAny = false;
foreach ($FIELD_LABELS as $key => $label) {
    if (!empty($row[$key])) { $hasAny = true; break; }
}
?>

<?php if ($hasAny): ?>
    <table class="fields">
        <?php foreach ($FIELD_LABELS as $key => $label): ?>
            <?php if (!empty($row[$key])): ?>
                <tr>
                    <td class="label"><?= htmlspecialchars($label) ?></td>
                    <td><?= nl2br(htmlspecialchars($row[$key])) ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:#888;">This record has no populated fields.</p>
<?php endif; ?>

<div class="doc-footer">
    <span>AIUB IT Database Portal — Record #<?= (int) $row['id'] ?></span>
    <span>Printed by <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
</div>

<script>
    // Auto-open the print dialog once the page has rendered.
    window.addEventListener('load', function () {
        window.print();
    });
</script>

</body>
</html>
