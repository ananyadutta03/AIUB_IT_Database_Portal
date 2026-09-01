<?php
// =====================================================================
// includes/sheets_list.php
// Dynamic Sheet Management
// =====================================================================

require_once __DIR__ . '/../config/db.php';


/**
 * Get all active sheets.
 *
 * Used by:
 * - Sidebar
 * - Add Record form
 * - Sheet dropdown
 * - Sheet navigation
 *
 * @return array
 */
function getAllSheets(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT
            id,
            sheet_name,
            is_active,
            created_at,
            updated_at
        FROM sheets
        WHERE is_active = 1
        ORDER BY id ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Get only active sheet names.
 *
 * Returns:
 *
 * [
 *     'Annex-1',
 *     'Annex-2',
 *     'IT',
 *     'WiFi'
 * ]
 *
 * @return array
 */
function getSheetNames(): array
{
    $sheets = getAllSheets();

    return array_column($sheets, 'sheet_name');
}


/**
 * Get all sheets including inactive sheets.
 *
 * Used by:
 * Admin > Manage Sheets
 *
 * @return array
 */
function getAllSheetsForAdmin(): array
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT
            id,
            sheet_name,
            is_active,
            created_at,
            updated_at
        FROM sheets
        ORDER BY id ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Get a single sheet by ID.
 *
 * @param int $id
 * @return array|null
 */
function getSheetById(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            id,
            sheet_name,
            is_active,
            created_at,
            updated_at
        FROM sheets
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);

    return $sheet ?: null;
}


/**
 * Get an active sheet by name.
 *
 * Used by sheet.php to make sure
 * the requested sheet actually exists
 * and is active.
 *
 * @param string $sheetName
 * @return array|null
 */
function getActiveSheetByName(string $sheetName): ?array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            id,
            sheet_name,
            is_active
        FROM sheets
        WHERE sheet_name = :sheet_name
          AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':sheet_name' => $sheetName
    ]);

    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);

    return $sheet ?: null;
}