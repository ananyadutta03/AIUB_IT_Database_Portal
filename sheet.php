<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sheets_list.php';

$sheet = trim($_GET['name'] ?? '');

// Validate sheet name against the master list
if (!in_array($sheet, $ALL_SHEETS, true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM inventory WHERE sheet_name = ? ORDER BY id'
);
$stmt->execute([$sheet]);
$rows = $stmt->fetchAll();

$pageTitle   = $sheet;
$activeSheet = $sheet;
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="mb-1">
            <i class="bi bi-table"></i>
            <?= htmlspecialchars($sheet) ?>
            <span class="badge bg-secondary fs-6 ms-2"><?= count($rows) ?> rows</span>
        </h3>
        <div class="text-muted small">
            <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/record.php?action=add&sheet=<?= urlencode($sheet) ?>"
       class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Record
    </a>
</div>

<?php if (count($rows) === 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center p-5">
            <i class="bi bi-inbox display-4 text-muted"></i>
            <p class="mt-3 mb-3 text-muted">No records yet in this sheet.</p>
            <a href="<?= BASE_URL ?>/record.php?action=add&sheet=<?= urlencode($sheet) ?>"
               class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add the first record
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm border-0 p-3">
        <table class="table table-hover align-middle small datatable" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Name / Device</th>
                    <th>Employee ID</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Room / Location</th>
                    <th>IP</th>
                    <th>MAC</th>
                    <th>Dept / Designation</th>
                    <th class="text-end no-sort" style="width:160px;">Actions</th>
                </tr>
            </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-muted">#<?= (int) $r['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars(
                                $r['full_name'] ?: ($r['device_model'] ?: '—')
                            ) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($r['employee_id'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['email'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['contact_number'] ?: '—') ?></td>
                        <td><?= htmlspecialchars(
                                $r['room'] ?: ($r['location'] ?: '—')
                            ) ?></td>
                        <td><?= htmlspecialchars($r['ip_address'] ?: '—') ?></td>
                        <td><small><?= htmlspecialchars($r['mac_address'] ?: '—') ?></small></td>
                        <td>
                            <?php if (!empty($r['department'])): ?>
                                <?= htmlspecialchars($r['department']) ?><br>
                            <?php endif; ?>
                            <small class="text-muted">
                                <?= htmlspecialchars($r['designation'] ?: '') ?>
                            </small>
                        </td>
                        <td class="text-end">
                            <button type="button"
                                    class="btn btn-sm btn-outline-info btn-view"
                                    data-id="<?= (int) $r['id'] ?>"
                                    title="View details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="<?= BASE_URL ?>/record.php?action=edit&id=<?= (int) $r['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/record_print.php?id=<?= (int) $r['id'] ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-secondary"
                               title="Print / Save as PDF">
                                <i class="bi bi-printer"></i>
                            </a>
                            <form method="post" action="<?= BASE_URL ?>/record.php"
                                  style="display:inline"
                                  onsubmit="return confirm('Delete this record permanently? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="return_to"
                                       value="<?= htmlspecialchars(BASE_URL . '/sheet.php?name=' . urlencode($sheet)) ?>">
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    </div>
<?php endif; ?>

<!-- View modal: populated via AJAX when an Eye button is clicked -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle"></i> Record Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <a href="#" target="_blank" class="btn btn-outline-secondary" id="viewModalPrint">
                    <i class="bi bi-printer"></i> Print
                </a>
                <a href="#" class="btn btn-primary" id="viewModalEdit">
                    <i class="bi bi-pencil"></i> Edit Record
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl   = document.getElementById('viewModal');
    var modalBody = document.getElementById('viewModalBody');
    var modalEdit = document.getElementById('viewModalEdit');
    var modalPrint = document.getElementById('viewModalPrint');
    if (!modalEl || !window.bootstrap) return;
    var modal = new bootstrap.Modal(modalEl);

    // Event delegation: works even after DataTables paginates
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-view');
        if (!btn) return;
        e.preventDefault();

        var id = btn.dataset.id;
        modalBody.innerHTML =
            '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modalEdit.href = '<?= BASE_URL ?>/record.php?action=edit&id=' + encodeURIComponent(id);
        modalPrint.href = '<?= BASE_URL ?>/record_print.php?id=' + encodeURIComponent(id);
        modal.show();

        fetch('<?= BASE_URL ?>/record_view.php?id=' + encodeURIComponent(id))
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) { modalBody.innerHTML = html; })
            .catch(function () {
                modalBody.innerHTML =
                    '<div class="alert alert-danger m-3">Failed to load record details.</div>';
            });
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
