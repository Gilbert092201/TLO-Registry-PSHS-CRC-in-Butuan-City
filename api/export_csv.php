<?php
/**
 * api/export_csv.php
 * Streams a CSV of the (optionally filtered) registry entries.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_filters.php';
require_login();

$pdo = get_db();
[$whereSql, $params] = build_filters($_GET);

$sql = "SELECT employee_number, entry_month, entry_day, entry_year, ip_name, application_number, application_code,
               status_of_application, amount_paid, ip_type, mode_of_transfer, title_of_ip
        FROM ip_entries $whereSql
        ORDER BY entry_year DESC, entry_month DESC, entry_day DESC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'tlo_registry_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

fputcsv($out, [
    'Employee Number', 'Month', 'Day', 'Year', 'IP Name', 'Application Number', 'Application Code',
    'Status of Application', 'Amount Paid', 'IP Type', 'Mode of Technology Transfer', 'Title of the IP'
]);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['employee_number'], $r['entry_month'], $r['entry_day'], $r['entry_year'],
        $r['ip_name'], $r['application_number'], $r['application_code'], $r['status_of_application'],
        number_format((float)$r['amount_paid'], 2, '.', ''), $r['ip_type'],
        $r['mode_of_transfer'], $r['title_of_ip'],
    ]);
}

fclose($out);
exit;
