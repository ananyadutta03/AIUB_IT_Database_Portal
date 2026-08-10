<?php
// =====================================================================
// record_view.php — returns an HTML fragment with full details of one record.
// Called via AJAX from sheet.php to populate the "View" modal.
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo '<div class="alert alert-warning m-3">Invalid record ID.</div>';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo '<div class="alert alert-warning m-3">Record not found.</div>';
    exit;
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
    'section'               => 'Section',
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
    'serial_no'             => 'Serial No.',
    'notes'                 => 'Notes',
];
?>
<div class="record-detail">
    <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
        <div>
            <h4 class="mb-1">
                <?= htmlspecialchars(
                    $row['full_name'] ?: ($row['device_model'] ?: '(unnamed record)')
                ) ?>
            </h4>
            <div class="small">
                <span class="badge bg-primary me-1">
                    <?= htmlspecialchars($row['sheet_name']) ?>
                </span>
                <?php if (!empty($row['section'])): ?>
                    <span class="badge bg-info text-dark">
                        <?= htmlspecialchars($row['section']) ?>
                    </span>
                <?php endif; ?>
                <span class="text-muted ms-2">Record #<?= (int) $row['id'] ?></span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php
        $hasAny = false;
        foreach ($FIELD_LABELS as $key => $label):
            if (empty($row[$key])) continue;
            $hasAny = true;
        ?>
            <div class="col-md-6">
                <div class="field-label"><?= $label ?></div>
                <div class="field-value">
                    <?= nl2br(htmlspecialchars($row[$key])) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasAny): ?>
            <div class="col-12">
                <div class="alert alert-light border text-center mb-0">
                    <i class="bi bi-inbox text-muted"></i>
                    This record has no populated fields.
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
