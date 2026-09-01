<?php
// =====================================================================
// includes/sidebar.php
// Dynamic sidebar with database-driven sheet list
// =====================================================================

require_once __DIR__ . '/sheets_list.php';


// Get all active sheets
$ALL_SHEETS = getSheetNames();


// Get row counts per sheet
$rowCounts = [];

$rs = $pdo->query("
    SELECT
        sheet_name,
        COUNT(*) AS c
    FROM inventory
    GROUP BY sheet_name
");

foreach ($rs as $r) {
    $rowCounts[$r['sheet_name']] = (int) $r['c'];
}


// Current navigation states
$activeSheet = $activeSheet ?? null;
$activeNav   = $activeNav ?? null;

?>


<aside class="app-sidebar p-3">

    <!-- Dashboard -->

    <a
        href="<?= BASE_URL ?>/index.php"
        class="sheet-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>"
        style="font-weight: 500;"
    >

        <span>
            <i class="bi bi-speedometer2 me-2"></i>
            Dashboard
        </span>

    </a>


    <!-- Sheets -->

    <div class="sidebar-section-title">

        Sheets (<?= count($ALL_SHEETS) ?>)

    </div>


    <?php foreach ($ALL_SHEETS as $sheetItem): ?>

        <?php
        $count = $rowCounts[$sheetItem] ?? 0;
        ?>

        <a
            href="<?= BASE_URL ?>/sheet.php?name=<?= urlencode($sheetItem) ?>"
            class="sheet-link <?= $activeSheet === $sheetItem ? 'active' : '' ?>"
        >

            <span>
                <?= htmlspecialchars(
                    $sheetItem,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>


            <span
                class="badge <?= $count > 0
                    ? 'bg-secondary'
                    : 'bg-light text-muted' ?>"
            >

                <?= $count ?>

            </span>

        </a>

    <?php endforeach; ?>


    <?php if (empty($ALL_SHEETS)): ?>

        <div class="text-muted small mt-2">
            No active sheets available.
        </div>

    <?php endif; ?>


    <!-- Admin Only -->

    <?php if (function_exists('isAdmin') && isAdmin()): ?>

        <hr>

        <a
            href="<?= BASE_URL ?>/admin/manage_sheets.php"
            class="sheet-link"
        >

            <span>
                <i class="bi bi-gear me-2"></i>
                Manage Sheets
            </span>

        </a>

    <?php endif; ?>


</aside>