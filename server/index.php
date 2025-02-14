<?php
require 'vendor/autoload.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db_path = '/app/skud.db';

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    echo "Error: Unable to connect to database: " . $e->getMessage();
    exit();
}

$current_date = date('d.m.Y');
if (isset($_POST['selected_date']) && !empty($_POST['selected_date'])) {
    $current_date = date('d.m.Y', strtotime($_POST['selected_date']));
}

if (isset($_POST['export_excel'])) {
    exportToExcel($db, $current_date);
}

function exportToExcel($db, $date) {
    $sql = "SELECT * FROM visits WHERE date = '$date'";
    $result = $db->query($sql);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Имя')
          ->setCellValue('B1', 'Фамилия')
          ->setCellValue('C1', 'Время прихода')
          ->setCellValue('D1', 'Время ухода')
          ->setCellValue('E1', 'Рабочее время');

    $row = 2;
    while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
        $time_in = $data['time_in'];
        $time_out = $data['time_out'];
        $work_time = '';

        if ($time_in && $time_out) {
            $time_in_timestamp = strtotime($time_in);
            $time_out_timestamp = strtotime($time_out);
            $diff = $time_out_timestamp - $time_in_timestamp;
            $work_time = gmdate("H:i:s", $diff);
        }

        $sheet->setCellValue('A' . $row, $data['first_name'])
              ->setCellValue('B' . $row, $data['last_name'])
              ->setCellValue('C' . $row, $data['time_in'])
              ->setCellValue('D' . $row, $data['time_out'])
              ->setCellValue('E' . $row, $work_time);
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="visits_' . $date . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

$sql = "SELECT * FROM visits WHERE date = '$current_date'";
$result = $db->query($sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BioStyle Visits</title>
    <link rel="icon" href="./bio.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            width: 80%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            color: rgb(255, 92, 52);
            text-align: center;
            padding: 20px 0;
            font-size: 28px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }
        form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }
        input[type="date"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
            width: 180px;
            transition: border-color 0.3s;
        }
        input[type="date"]:focus {
            border-color: rgb(255, 92, 52);
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: rgb(255, 92, 52);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        button:hover {
            background-color: rgb(118, 187, 34);
        }
        .data-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }
        th {
            background-color: rgb(118, 187, 34);
            color: #fff;
            font-weight: bold;
        }
        td {
            background-color: #fff;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Записи за <?= $current_date ?></h1>
    <form method="post">
        <input type="date" name="selected_date" value="<?= isset($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d') ?>" onchange="this.form.submit()">
        <button type="submit" name="export_excel">Выгрузить в Excel</button>
        <a href="monthly_table.php">
            <button type="button">Перейти к табелю посещаемости</button>
        </a>
    </form>
    <div class="data-container">
        <table>
            <tr>
                <th>Имя</th>
                <th>Фамилия</th>
                <th>Время прихода</th>
                <th>Время ухода</th>
                <th>Рабочее время</th>
            </tr>
            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                <?php
                $time_in = $row['time_in'];
                $time_out = $row['time_out'];
                $work_time = '';

                if ($time_in && $time_out) {
                    $time_in_timestamp = strtotime($time_in);
                    $time_out_timestamp = strtotime($time_out);
                    $diff = $time_out_timestamp - $time_in_timestamp;
                    $work_time = gmdate("H:i:s", $diff);
                }
                ?>
                <tr>
                    <td><?= $row['first_name'] ?></td>
                    <td><?= $row['last_name'] ?></td>
                    <td><?= $time_in ?></td>
                    <td><?= $time_out ?></td>
                    <td><?= $work_time ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php $db->close(); ?>

