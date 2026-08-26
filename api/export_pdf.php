<?php
/**
 * api/export_pdf.php
 * Streams a landscape PDF report of the (optionally filtered) registry entries.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_filters.php';
require_login();
require_once __DIR__ . '/../vendor/fpdf.php';

$pdo = get_db();
[$whereSql, $params] = build_filters($_GET);

$sql = "SELECT employee_number, entry_month, entry_day, entry_year, ip_name, application_number, application_code,
               status_of_application, amount_paid, ip_type, mode_of_transfer, title_of_ip
        FROM ip_entries $whereSql
        ORDER BY entry_year DESC, entry_month DESC, entry_day DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$monthNames = ['', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

class TLOPdf extends FPDF {
    function Header() {
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(0, 6, 'Philippine Science High School - Caraga Region Campus, Butuan City', 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 6, 'Technology Licensing Office Registry', 0, 1, 'C');
        $this->SetFont('Helvetica', 'I', 8);
        $this->Cell(0, 5, 'Generated ' . date('F j, Y g:i A'), 0, 1, 'C');
        $this->Ln(2);

        $this->SetFont('Helvetica', 'B', 7);
        $this->SetFillColor(11, 37, 69);
        $this->SetTextColor(255, 255, 255);
        $headers = ['Emp. No.', 'Date', 'IP Name', 'App. No.', 'App. Code', 'Status', 'Amount (PHP)', 'IP Type', 'Transfer Mode', 'Title of IP'];
        $widths  = [16, 16, 24, 22, 22, 30, 20, 20, 26, 74];
        foreach ($headers as $i => $h) {
            $this->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
        $this->widths = $widths;
    }

    function FooterNote() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 7);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new TLOPdf('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 7);

$widths = [16, 16, 24, 22, 22, 30, 20, 20, 26, 74];
$fill = false;

foreach ($rows as $r) {
    $date = $monthNames[(int)$r['entry_month']] . ' ' . $r['entry_day'] . ', ' . $r['entry_year'];
    $amount = number_format((float)$r['amount_paid'], 2);

    $cells = [
        $r['employee_number'], $date, $r['ip_name'], $r['application_number'], $r['application_code'],
        $r['status_of_application'], $amount, $r['ip_type'], $r['mode_of_transfer'], $r['title_of_ip'],
    ];

    $pdf->SetFillColor(244, 247, 251);
    foreach ($cells as $i => $c) {
        $align = ($i === 6) ? 'R' : 'L';
        $pdf->Cell($widths[$i], 6, substr((string)$c, 0, 60), 1, 0, $align, $fill);
    }
    $pdf->Ln();
    $fill = !$fill;
}

if (empty($rows)) {
    $pdf->Cell(array_sum($widths), 8, 'No entries match the current filters.', 1, 1, 'C');
}

$filename = 'tlo_registry_' . date('Ymd_His') . '.pdf';
$pdf->Output('D', $filename);
exit;
