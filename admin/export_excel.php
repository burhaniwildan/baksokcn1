<?php
require 'conn.php';
require '../libs/PhpOffice/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();

if (!isset($_SESSION["user"])) {
    header('location:index.php');
    exit;
}

if (!isset($_SESSION['laporan_data'])) {
    die('Data laporan tidak ditemukan.');
}

$data = $_SESSION['laporan_data'];

$jumlah_produk_terjual = $data['jumlah_produk_terjual'];
$total_transaction = $data['total_transaction'];
$total_penjualan = $data['total_penjualan'];
$total_pengeluaran = $data['total_pengeluaran'];
$laba_bersih = $data['laba_bersih'];
$start = $data['start'];
$end = $data['end'];

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Judul dan format
$sheet->setCellValue('A1', 'LAPORAN LABA RUGI');
$sheet->mergeCells('A1:B1');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$sheet->setCellValue('A2', 'Bakso KCN');
$sheet->mergeCells('A2:B2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', date('d F Y', strtotime($start)) . ' - ' . date('d F Y', strtotime($end)));
$sheet->mergeCells('A3:B3');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Baris kosong
$row = 5;

$items = [
    ['Jumlah Produk Terjual', $jumlah_produk_terjual],
    ['Total Transaksi', $total_transaction],
    ['Total Penjualan Kotor', 'Rp ' . number_format($total_penjualan, 0, ',', '.')],
    ['Total Pengeluaran', 'Rp ' . number_format($total_pengeluaran, 0, ',', '.')],
    ['Laba Bersih', 'Rp ' . number_format($laba_bersih, 0, ',', '.')]
];

foreach ($items as $item) {
    $sheet->setCellValue('A' . $row, $item[0]);
    $sheet->setCellValue('B' . $row, $item[1]);

    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    if ($item[0] === 'Laba Bersih') {
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
    }

    $row++;
}

$sheet->getColumnDimension('A')->setWidth(50);
$sheet->getColumnDimension('B')->setWidth(50);

$filename = 'Laporan_Laba_Rugi_' . date('d-m-Y') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
