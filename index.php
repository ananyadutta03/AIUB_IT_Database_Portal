<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sheets_list.php';

$q       = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    // Columns to search across. Each one becomes a "col LIKE ?" clause.
    $searchCols = [
        'full_name', 'employee_id', 'email', 'username',
        'contact_number', 'ip_address', 'mac_address',
        'room', 'location', 'designation', 'department',
        'cpu_model', 'hardware_description', 'notes',
        'device_model', 'device_serial', 'extension',
        'ip_phone', 'switch_port',
    ];
    $whereClause = implode(
        ' OR ',
        array_map(fn($c) => "`$c` LIKE ?", $searchCols)
    );
    $sql = "
        SELECT *
        FROM inventory
        WHERE $whereClause
        ORDER BY (full_name IS NULL), full_name
        LIMIT 100
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_fill(0, count($searchCols), $like));
    $results = $stmt->fetchAll();
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM inventory')->fetchColumn();

// Field labels for displaying record details
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

$pageTitle  = 'Dashboard';
$activeNav  = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h3>
        <a href="<?= BASE_URL ?>/record.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add New Record
        </a>
    </div>
    <form method="get" action="<?= BASE_URL ?>/index.php">
        <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="q"
                   class="form-control border-start-0"
                   placeholder="Search by name, employee ID, email, IP, MAC, room, designation..."
                   value="<?= htmlspecialchars($q) ?>" autofocus>
            <button class="btn btn-primary px-4" type="submit">Search</button>
            <?php if ($q !== ''): ?>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
        <div class="form-text mt-2">
            Try: <code>MAHFUJUR</code>, <code>2007-2079-2</code>, <code>172.16.6.76</code>,
            <code>mahfuj@aiub.edu</code>, <code>DN0601D</code>
        </div>
    </form>
</div>

<?php if ($q === ''): ?>
    <!-- Welcome / stats panel -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Total Records</div>
                    <div class="display-6 fw-bold"><?= number_format($total) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Sheets</div>
                    <div class="display-6 fw-bold"><?= count($ALL_SHEETS) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-secondary h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Searchable Fields</div>
                    <div class="display-6 fw-bold">20+</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border">
        <i class="bi bi-info-circle text-primary"></i>
        Type a keyword above to search across all <strong><?= number_format($total) ?> records</strong>.
        Or click any sheet on the left sidebar to view, add, edit, or delete its rows.
    </div>

<?php elseif (count($results) === 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        No results found for <strong>"<?= htmlspecialchars($q) ?>"</strong>.
        Try a different keyword or check spelling.
    </div>

<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted">
            Found <strong class="text-dark"><?= count($results) ?></strong>
            <?= count($results) === 100 ? '(showing first 100)' : '' ?>
            result<?= count($results) === 1 ? '' : 's' ?>
            for <strong class="text-dark">"<?= htmlspecialchars($q) ?>"</strong>
        </div>
    </div>

    <?php foreach ($results as $row): ?>
        <div class="card mb-3 result-card shadow-sm border-0">
            <div class="card-body">
                <!-- Header row: name + sheet badge + actions -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">
                            <?= htmlspecialchars($row['full_name'] ?: ($row['device_model'] ?: '(unnamed record)')) ?>
                        </h5>
                        <div class="small text-muted">
                            <span class="badge bg-primary me-1"><?= htmlspecialchars($row['sheet_name']) ?></span>
                            <span class="ms-2">Record #<?= (int) $row['id'] ?></span>
                        </div>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/record.php?action=edit&id=<?= (int) $row['id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?= BASE_URL ?>/record_print.php?id=<?= (int) $row['id'] ?>"
                           target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer"></i> Print
                        </a>
                    </div>
                </div>

                <!-- Field grid -->
                <div class="row g-3">
                    <?php foreach ($FIELD_LABELS as $key => $label): ?>
                        <?php if (!empty($row[$key])): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="field-label"><?= $label ?></div>
                                <div class="field-value">
                                    <?= nl2br(htmlspecialchars($row[$key])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
