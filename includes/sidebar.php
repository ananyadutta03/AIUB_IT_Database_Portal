<?php
// Left sidebar — list of all 25 sheets with row counts.
// Highlights the current sheet if $activeSheet is set by the page.
// Highlights "Dashboard" if $activeNav === 'dashboard'.

require_once __DIR__ . '/sheets_list.php';

// Get row counts per sheet from DB (joined with the master list)
$rowCounts = [];
$rs = $pdo->query('SELECT sheet_name, COUNT(*) AS c FROM inventory GROUP BY sheet_name');
foreach ($rs as $r) {
    $rowCounts[$r['sheet_name']] = (int) $r['c'];
}

$activeSheet = $activeSheet ?? null;
$activeNav   = $activeNav   ?? null;
?>
<aside class="app-sidebar p-3">

    <a href="<?= BASE_URL ?>/index.php"
       class="sheet-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>"
       style="font-weight: 500;">
        <span><i class="bi bi-speedometer2 me-2"></i> Dashboard</span>
    </a>

    <div class="sidebar-section-title">Sheets (<?= count($ALL_SHEETS) ?>)</div>

    <?php foreach ($ALL_SHEETS as $sheetItem): ?>
        <?php $count = $rowCounts[$sheetItem] ?? 0; ?>
        <a href="<?= BASE_URL ?>/sheet.php?name=<?= urlencode($sheetItem) ?>"
           class="sheet-link <?= $activeSheet === $sheetItem ? 'active' : '' ?>">
            <span><?= htmlspecialchars($sheetItem) ?></span>
            <span class="badge <?= $count > 0 ? 'bg-secondary' : 'bg-light text-muted' ?>">
                <?= $count ?>
            </span>
        </a>
    <?php endforeach; ?>

</aside>
