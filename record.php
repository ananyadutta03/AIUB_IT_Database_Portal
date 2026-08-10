<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sheets_list.php';

// All editable fields, grouped for the form layout
$FIELDS = [
    // group => [field_key => [label, type]]
    'Basic' => [
        'sheet_name' => ['label' => 'Sheet',      'type' => 'select', 'required' => true],
        'section'    => ['label' => 'Section',    'type' => 'text'],
        'serial_no'  => ['label' => 'Serial No.', 'type' => 'text'],
    ],
    'Identity' => [
        'full_name'      => ['label' => 'Full Name',     'type' => 'text'],
        'employee_id'    => ['label' => 'Employee ID',   'type' => 'text'],
        'email'          => ['label' => 'Email',         'type' => 'email'],
        'username'       => ['label' => 'Username',      'type' => 'text'],
        'contact_number' => ['label' => 'Contact Number','type' => 'text'],
        'designation'    => ['label' => 'Designation',   'type' => 'text'],
        'department'     => ['label' => 'Department',    'type' => 'text'],
    ],
    'Location' => [
        'room'     => ['label' => 'Room',     'type' => 'text'],
        'location' => ['label' => 'Location', 'type' => 'text'],
        'building' => ['label' => 'Building', 'type' => 'text'],
    ],
    'Network' => [
        'ip_address'  => ['label' => 'IP Address',    'type' => 'text'],
        'mac_address' => ['label' => 'MAC Address',   'type' => 'text'],
        'switch_port' => ['label' => 'Switch / Port', 'type' => 'text'],
        'ip_phone'    => ['label' => 'IP Phone',      'type' => 'text'],
        'extension'   => ['label' => 'Extension',     'type' => 'text'],
    ],
    'Hardware' => [
        'cpu_model'            => ['label' => 'CPU Model',     'type' => 'text'],
        'processor'            => ['label' => 'Processor',     'type' => 'text'],
        'ram'                  => ['label' => 'RAM',           'type' => 'text'],
        'monitor'              => ['label' => 'Monitor',       'type' => 'text'],
        'hardware_description' => ['label' => 'Hardware Description', 'type' => 'textarea'],
    ],
    'Peripherals' => [
        'printer' => ['label' => 'Printer', 'type' => 'text'],
        'scanner' => ['label' => 'Scanner', 'type' => 'text'],
        'ups'     => ['label' => 'UPS',     'type' => 'text'],
    ],
    'Device-specific' => [
        'device_model'  => ['label' => 'Device Model',  'type' => 'text'],
        'device_serial' => ['label' => 'Device Serial', 'type' => 'text'],
        'status'        => ['label' => 'Status',        'type' => 'text'],
    ],
    'Other' => [
        'notes' => ['label' => 'Notes', 'type' => 'textarea'],
    ],
];

// Flatten for easy iteration
$FLAT_FIELDS = [];
foreach ($FIELDS as $group => $groupFields) {
    foreach ($groupFields as $key => $meta) {
        $FLAT_FIELDS[$key] = $meta;
    }
}

$action = $_REQUEST['action'] ?? 'add';
$id     = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$sheet  = trim($_REQUEST['sheet'] ?? '');
$error  = '';

// ----- Handle POST (delete or save) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM inventory WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash'] = "Record #$id deleted.";

        // Where to go after deletion (validate to prevent open redirects)
        $returnTo = $_POST['return_to'] ?? '';
        if (strpos($returnTo, BASE_URL . '/') !== 0) {
            $returnTo = BASE_URL . '/index.php';
        }
        header('Location: ' . $returnTo);
        exit;
    }

    if ($postAction === 'save') {
        // Collect every editable field from the POST
        $data = [];
        foreach ($FLAT_FIELDS as $key => $meta) {
            $val = trim($_POST[$key] ?? '');
            $data[$key] = ($val === '') ? null : $val;
        }

        if (!in_array($data['sheet_name'], $ALL_SHEETS, true)) {
            $error = 'Please choose a valid sheet.';
        } else {
            if ($id > 0) {
                // UPDATE existing
                $sets = [];
                foreach ($data as $k => $_) $sets[] = "`$k` = :$k";
                $params = $data;
                $params['id'] = $id;
                $sql = 'UPDATE inventory SET ' . implode(', ', $sets) . ' WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash'] = "Record #$id updated.";
            } else {
                // INSERT new
                $cols   = array_keys($data);
                $colSql = implode(', ', array_map(fn($c) => "`$c`", $cols));
                $valSql = implode(', ', array_map(fn($c) => ":$c", $cols));
                $sql = "INSERT INTO inventory ($colSql) VALUES ($valSql)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                $newId = (int) $pdo->lastInsertId();
                $_SESSION['flash'] = "Record #$newId added to {$data['sheet_name']}.";
            }
            header('Location: ' . BASE_URL . '/sheet.php?name=' . urlencode($data['sheet_name']));
            exit;
        }
    }
}

// ----- Load data for the form -----
$record = array_fill_keys(array_keys($FLAT_FIELDS), '');

if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ?');
    $stmt->execute([$id]);
    $loaded = $stmt->fetch();
    if (!$loaded) {
        $_SESSION['flash'] = "Record #$id not found.";
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    foreach ($record as $k => $_) {
        $record[$k] = $loaded[$k] ?? '';
    }
} elseif ($action === 'add' && in_array($sheet, $ALL_SHEETS, true)) {
    $record['sheet_name'] = $sheet;
}

// On validation error after POST, repopulate from POST values
if ($error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($record as $k => $_) {
        $record[$k] = $_POST[$k] ?? '';
    }
}

$pageTitle   = ($action === 'edit') ? "Edit Record #$id" : 'Add New Record';
$activeSheet = $record['sheet_name'] ?: null;
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/<?= $record['sheet_name']
        ? 'sheet.php?name=' . urlencode($record['sheet_name'])
        : 'index.php' ?>"
       class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <h3 class="mb-0">
        <?php if ($action === 'edit'): ?>
            <i class="bi bi-pencil"></i> Edit Record #<?= (int) $id ?>
        <?php else: ?>
            <i class="bi bi-plus-circle"></i> Add New Record
        <?php endif; ?>
    </h3>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/record.php" class="card shadow-sm border-0">
    <input type="hidden" name="action" value="save">
    <?php if ($id > 0): ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
    <?php endif; ?>

    <div class="card-body p-4">
        <?php foreach ($FIELDS as $group => $groupFields): ?>
            <h6 class="text-muted text-uppercase small fw-bold mt-3 mb-3 pb-2 border-bottom">
                <?= htmlspecialchars($group) ?>
            </h6>
            <div class="row g-3 mb-2">
                <?php foreach ($groupFields as $key => $meta):
                    $value = $record[$key] ?? '';
                    $colWidth = $meta['type'] === 'textarea' ? 12 : 4;
                ?>
                    <div class="col-md-6 col-lg-<?= $colWidth ?>">
                        <label class="form-label small">
                            <?= htmlspecialchars($meta['label']) ?>
                            <?php if (!empty($meta['required'])): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($meta['type'] === 'select' && $key === 'sheet_name'): ?>
                            <select name="<?= $key ?>" class="form-select"
                                    <?= !empty($meta['required']) ? 'required' : '' ?>>
                                <option value="">— choose sheet —</option>
                                <?php foreach ($ALL_SHEETS as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                            <?= $value === $opt ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($meta['type'] === 'textarea'): ?>
                            <textarea name="<?= $key ?>" class="form-control"
                                      rows="3"><?= htmlspecialchars($value) ?></textarea>
                        <?php else: ?>
                            <input type="<?= htmlspecialchars($meta['type']) ?>"
                                   name="<?= $key ?>"
                                   class="form-control"
                                   value="<?= htmlspecialchars($value) ?>"
                                   <?= !empty($meta['required']) ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card-footer d-flex justify-content-between bg-white py-3">
        <a href="<?= BASE_URL ?>/<?= $record['sheet_name']
            ? 'sheet.php?name=' . urlencode($record['sheet_name'])
            : 'index.php' ?>"
           class="btn btn-outline-secondary">
            Cancel
        </a>
        <div>
            <?php if ($id > 0): ?>
                <button type="button" class="btn btn-outline-danger me-2"
                        onclick="if(confirm('Delete this record permanently? This cannot be undone.')) document.getElementById('delForm').submit();">
                    <i class="bi bi-trash"></i> Delete
                </button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg"></i>
                <?= $id > 0 ? 'Save Changes' : 'Add Record' ?>
            </button>
        </div>
    </div>
</form>

<?php if ($id > 0): ?>
    <form id="delForm" method="post" action="<?= BASE_URL ?>/record.php" style="display:none">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
    </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
