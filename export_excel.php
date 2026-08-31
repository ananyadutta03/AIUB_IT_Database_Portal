<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/autoload.php';

requireAdmin();

$q = trim($_GET['q'] ?? '');

/*
|--------------------------------------------------------------------------
| Searchable columns
|--------------------------------------------------------------------------
*/

$searchCols = [
    'full_name',
    'employee_id',
    'email',
    'username',
    'contact_number',
    'ip_address',
    'mac_address',
    'room',
    'location',
    'designation',
    'department',
    'cpu_model',
    'hardware_description',
    'notes',
    'device_model',
    'device_serial',
    'extension',
    'ip_phone',
    'switch_port',
];

/*
|--------------------------------------------------------------------------
| Build query
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM inventory";
$params = [];

if ($q !== '') {

    $like = '%' . $q . '%';

    $whereParts = [];

    foreach ($searchCols as $column) {
        $whereParts[] = "`$column` LIKE ?";
        $params[] = $like;
    }

    $sql .= " WHERE " . implode(' OR ', $whereParts);
}

$sql .= " ORDER BY (full_name IS NULL), full_name";

/*
|--------------------------------------------------------------------------
| Fetch records
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PhpSpreadsheet
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/*
|--------------------------------------------------------------------------
| Create spreadsheet
|--------------------------------------------------------------------------
*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Inventory');

/*
|--------------------------------------------------------------------------
| Excel column headers
|--------------------------------------------------------------------------
*/

$headers = [
    'id'                    => 'ID',
    'sheet_name'            => 'Sheet',
    'full_name'             => 'Full Name',
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

/*
|--------------------------------------------------------------------------
| Add headers
|--------------------------------------------------------------------------
*/

$columnIndex = 1;

foreach ($headers as $key => $label) {

    $cell = $sheet->getCellByColumnAndRow(
        $columnIndex,
        1
    );

    $cell->setValue($label);

    $columnIndex++;
}

/*
|--------------------------------------------------------------------------
| Header styling
|--------------------------------------------------------------------------
*/

$lastColumn = $sheet->getHighestColumn();

$headerRange = 'A1:' . $lastColumn . '1';

$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ],
    ],

    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => '0D6EFD'
        ],
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],

    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Add data
|--------------------------------------------------------------------------
*/

$rowNumber = 2;

foreach ($results as $row) {

    $columnIndex = 1;

    foreach ($headers as $key => $label) {

        $value = $row[$key] ?? '';

        $sheet->getCellByColumnAndRow(
            $columnIndex,
            $rowNumber
        )->setValue($value);

        $columnIndex++;
    }

    $rowNumber++;
}

/*
|--------------------------------------------------------------------------
| Style data
|--------------------------------------------------------------------------
*/

if ($rowNumber > 2) {

    $dataRange = 'A2:' . $lastColumn . ($rowNumber - 1);

    $sheet->getStyle($dataRange)->applyFromArray([
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP,
        ],

        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);
}

/*
|--------------------------------------------------------------------------
| Freeze header
|--------------------------------------------------------------------------
*/

$sheet->freezePane('A2');

/*
|--------------------------------------------------------------------------
| Auto filter
|--------------------------------------------------------------------------
*/

$sheet->setAutoFilter(
    'A1:' . $lastColumn . max(1, $rowNumber - 1)
);

/*
|--------------------------------------------------------------------------
| Auto column width
|--------------------------------------------------------------------------
*/

foreach (range(1, count($headers)) as $column) {

    $sheet->getColumnDimensionByColumn($column)
        ->setAutoSize(true);
}

/*
|--------------------------------------------------------------------------
| Filename
|--------------------------------------------------------------------------
*/

if ($q !== '') {

    $safeQuery = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        $q
    );

    $filename = 'inventory_search_' . $safeQuery . '.xlsx';

} else {

    $filename = 'inventory_export.xlsx';
}

/*
|--------------------------------------------------------------------------
| Download Excel
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;