<?php
require('fpdf/fpdf.php');

// 1. Koneksi DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "dbmoosra1";
$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Kolom kriteria
$columnsToShow = ['C1','C2','C3','C4','C5'];

// 2. PDF init
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Laporan Perhitungan MOORA',0,1,'C');

// ====================
// TABEL ALTERNATIF
// ====================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Data Alternatif (C1 - C5):',0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Alternatif',1,0,'C');
foreach ($columnsToShow as $col) {
    $pdf->Cell(25,7,$col,1,0,'C');
}
$pdf->Ln();

$pdf->SetFont('Arial','',10);
$q_data = $koneksi->query("SELECT * FROM tbhitung");
while ($row = $q_data->fetch_assoc()) {
    $pdf->Cell(30,7,'A'.$row['idalternatif'],1,0,'C');
    foreach ($columnsToShow as $col) {
        $pdf->Cell(25,7,(int)$row[$col],1,0,'C'); // bilangan bulat
    }
    $pdf->Ln();
}

// ====================
// NORMALISASI MATRİKS
// ====================
// Hitung pembagi akar jumlah kuadrat
$nilai_kriteria = [];
$q = $koneksi->query("SELECT * FROM tbhitung");
while ($data = $q->fetch_assoc()) {
    foreach ($columnsToShow as $col) {
        $nilai_kriteria[$col] = isset($nilai_kriteria[$col]) ? $nilai_kriteria[$col]+pow($data[$col],2) : pow($data[$col],2);
    }
}
foreach ($nilai_kriteria as $col => $val) {
    $nilai_kriteria[$col] = sqrt($val);
}

// Tabel Normalisasi
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Normalisasi Matriks:',0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(50,7,'Kriteria',1,0,'C'); $pdf->Cell(50,7,'Nilai Normalisasi',1,1,'C');
$pdf->SetFont('Arial','',10);
foreach ($columnsToShow as $col) {
    $pdf->Cell(50,7,$col,1,0,'C');
    $pdf->Cell(50,7,number_format($nilai_kriteria[$col],4),1,1,'C');
}

// ====================
// NORMALISASI TERBOBOT
// ====================
// Bobot
$bobot_kriteria = [];
$q_bobot = $koneksi->query("SELECT * FROM tbkriteria");
while ($row = $q_bobot->fetch_assoc()) {
    $bobot_kriteria['C'.$row['idkriteria']] = $row['bobot'];
}

// Isi tabel Normalisasi Terbobot
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Normalisasi Terbobot:',0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Alternatif',1,0,'C');
foreach ($columnsToShow as $col) $pdf->Cell(25,7,$col,1,0,'C');
$pdf->Ln();

$q_data = $koneksi->query("SELECT * FROM tbhitung");
$pdf->SetFont('Arial','',10);
while ($row = $q_data->fetch_assoc()) {
    $pdf->Cell(30,7,'A'.$row['idalternatif'],1,0,'C');
    foreach ($columnsToShow as $col) {
        $norm = $row[$col]/$nilai_kriteria[$col];
        $weighted = $norm * $bobot_kriteria[$col];
        $pdf->Cell(25,7,number_format($weighted,4),1,0,'C');
    }
    $pdf->Ln();
}

// ====================
// PERINGKAT MOORA
// ====================
// Benefit dan Cost
$columnsBenefit = $columnsCost = [];
$q_kriteria = $koneksi->query("SELECT * FROM tbkriteria");
while ($row = $q_kriteria->fetch_assoc()) {
    $col = 'C'.$row['idkriteria'];
    if ($row['jeniskriteria'] == 'Benefit') $columnsBenefit[] = $col;
    else $columnsCost[] = $col;
}

// Hitung nilai akhir
$nilai_akhir = [];
$q_data = $koneksi->query("SELECT * FROM tbhitung");
while ($row = $q_data->fetch_assoc()) {
    $benefit = 0; $cost = 0;
    foreach ($columnsBenefit as $col) {
        $benefit += ($row[$col]/$nilai_kriteria[$col])*$bobot_kriteria[$col];
    }
    foreach ($columnsCost as $col) {
        $cost += ($row[$col]/$nilai_kriteria[$col])*$bobot_kriteria[$col];
    }
    $nilai_akhir[$row['idalternatif']] = $benefit - $cost;
}
arsort($nilai_akhir);

// Tabel Peringkat
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Peringkat MOORA:',0,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(50,7,'Alternatif',1,0,'C');
$pdf->Cell(50,7,'Nilai Akhir',1,0,'C');
$pdf->Cell(50,7,'Peringkat',1,1,'C');

$peringkat = 1;
$pdf->SetFont('Arial','',10);
foreach ($nilai_akhir as $id => $nilai) {
    $pdf->Cell(50,7,'A'.$id,1,0,'C');
    $pdf->Cell(50,7,number_format($nilai,4),1,0,'C');
    $pdf->Cell(50,7,$peringkat,1,1,'C');
    $peringkat++;
}

// Output
$pdf->Output('I','Laporan_MOORA.pdf');
$koneksi->close();
