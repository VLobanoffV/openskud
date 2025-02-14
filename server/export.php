<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$servername = "localhost";
$username = "YOU_DB_USERNAME"; 
$password = "YOU_DB_PASSWORD"; 
$dbname = "YOU_DB_NAME"; 

$startDate = $_GET['start_date'];
$endDate = $_GET['end_date'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM visits WHERE date BETWEEN '$startDate' AND '$endDate'";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'ID')
      ->setCellValue('B1', 'Date')
      ->setCellValue('C1', 'RFID ID')
      ->setCellValue('D1', 'First Name')
      ->setCellValue('E1', 'Last Name')
      ->setCellValue('F1', 'Time In')
      ->setCellValue('G1', 'Time Out')
      ->setCellValue('H1', 'Work Time');

$rowNum = 2;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['date']);
        $sheet->setCellValue('C' . $rowNum, $row['rfid_id']);
        $sheet->setCellValue('D' . $rowNum, $row['first_name']);
        $sheet->setCellValue('E' . $rowNum, $row['last_name']);
        $sheet->setCellValue('F' . $rowNum, $row['time_in']);
        $sheet->setCellValue('G' . $rowNum, $row['time_out']);
        $sheet->setCellValue('H' . $rowNum, $row['work_time']);
        $rowNum++;
    }
} else {
    echo "No data found for the selected period.";
    exit;
}

$writer = new Xlsx($spreadsheet);
$filename = 'visits_report_' . $startDate . '_to_' . $endDate . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
$conn->close();
exit;

