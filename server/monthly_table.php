<?php
require 'vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

$db_path = '/app/skud.db';

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    echo "Error: Unable to connect to database: " . $e->getMessage();
    exit();
}

$current_month = date('m');
$current_year = date('Y');
if (isset($_POST['selected_month']) && !empty($_POST['selected_month'])) {
    $selected_date = explode('-', $_POST['selected_month']);
    $current_year = $selected_date[0];
    $current_month = $selected_date[1];
}

if (isset($_POST['export_excel'])) {
    exportToExcel($db, $current_month, $current_year);
}

function exportToExcel($db, $month, $year) {
    $sql = "SELECT DISTINCT first_name, last_name FROM visits ORDER BY last_name, first_name";
    $result = $db->query($sql);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Фамилия')->setCellValue('B1', 'Имя');
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    for ($day = 1; $day <= $days_in_month; $day++) {
        $col = Coordinate::stringFromColumnIndex(2 + $day); // C, D, E, ...
        $sheet->setCellValue($col . '1', $day);
    }

    $sheet->setCellValue(Coordinate::stringFromColumnIndex(3 + $days_in_month) . '1', 'Рабочие дни');

    $headerStyle = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFC0CB'], // Светло-розовый цвет
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(3 + $days_in_month) . '1')->applyFromArray($headerStyle);

    $row = 2;
    while ($user = $result->fetchArray(SQLITE3_ASSOC)) {
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
        $sheet->setCellValue('A' . $row, $last_name)
              ->setCellValue('B' . $row, $first_name);

        $work_days = 0;
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%02d.%02d.%04d', $day, $month, $year);
            $sql = "SELECT COUNT(*) as count FROM visits
                    WHERE first_name = '$first_name' AND last_name = '$last_name' AND date = '$date'";
            $visit_result = $db->querySingle($sql);
            $col = Coordinate::stringFromColumnIndex(2 + $day);
            $status = ($visit_result > 0) ? 'Я' : '-';
            $sheet->setCellValue($col . $row, $status);
            if ($status == 'Я') {
                $work_days++;
            }

            $date_timestamp = strtotime($date);
            if (date('N', $date_timestamp) >= 6) { // 6 - суббота, 7 - воскресенье
                $weekendStyle = [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFF00'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle($col . $row)->applyFromArray($weekendStyle);
            } else {
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
            }
        }
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(3 + $days_in_month) . $row, $work_days);
        $row++;
    }

    for ($col = 'A'; $col != Coordinate::stringFromColumnIndex(4 + $days_in_month); $col++) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $allCells = $sheet->calculateWorksheetDimension();
    $sheet->getStyle($allCells)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="monthly_report_' . $month . '_' . $year . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

$sql = "SELECT DISTINCT first_name, last_name FROM visits ORDER BY last_name, first_name";
$users = $db->query($sql);
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Табель посещаемости</title>
    <link rel="icon" href="./bio.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .table-wrapper {
            overflow-x: auto; 
        }
        h1 {
            color: rgb(255, 92, 52);
            text-align: center;
            font-size: 28px;
        }
        form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        input[type="month"] {
            padding: 10px;
            font-size: 16px;
            margin-right: 15px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: rgb(255, 92, 52);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px; 
        }
        button:hover {
            background-color: rgb(118, 187, 34);
        }
        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: rgb(118, 187, 34);
            color: #fff;
        }
        th:nth-child(n+3):nth-child(-n+<?= 3 + $days_in_month ?>) {
            width: 30px; 
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        td.name-cell {
            text-align: left;
            font-size: 12px;
            padding: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Табель посещаемости за <?= $current_month ?>/<?= $current_year ?></h1>
    <form method="post">
        <input type="month" name="selected_month" value="<?= $current_year . '-' . $current_month ?>" onchange="this.form.submit()">
        <button type="submit" name="export_excel">Выгрузить в Excel</button>
        <a href="index.php">
            <button type="button">Назад</button>
        </a>
    </form>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Фамилия</th>
                <th>Имя</th>
                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <th><?= $day ?></th>
                <?php endfor; ?>
                <th>Рабочие дни</th>
            </tr>

            <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td class="name-cell"><?= $user['last_name'] ?></td>
                    <td class="name-cell"><?= $user['first_name'] ?></td>
                    <?php
                        $work_days = 0;
                        for ($day = 1; $day <= $days_in_month; $day++):
                            $date = sprintf('%02d.%02d.%04d', $day, $current_month, $current_year);
                            $sql = "SELECT COUNT(*) as count FROM visits
                                    WHERE first_name = '{$user['first_name']}' AND last_name = '{$user['last_name']}' AND date = '$date'";
                            $count = $db->querySingle($sql);
                            $status = ($count > 0) ? 'Я' : '-';
                            if ($status == 'Я') {
                                $work_days++;
                            }
                    ?>
                        <td><?= $status ?></td>
                    <?php endfor; ?>
                    <td><?= $work_days ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php $db->close(); ?>

