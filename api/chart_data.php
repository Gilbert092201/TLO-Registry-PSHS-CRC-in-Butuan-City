<?php
/**
 * api/chart_data.php
 * Returns aggregated JSON for the dashboard charts, honoring active filters.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_filters.php';
require_login_api();

header('Content-Type: application/json');
$pdo = get_db();

[$whereSql, $params] = build_filters($_GET);

// ---- Line graph: count of filings per Year-Month --------------------
$sql = "SELECT entry_year, entry_month, COUNT(*) AS total
        FROM ip_entries $whereSql
        GROUP BY entry_year, entry_month
        ORDER BY entry_year ASC, entry_month ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$monthly = $stmt->fetchAll();

$lineLabels = [];
$lineData = [];
$monthNames = ['', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
foreach ($monthly as $row) {
    $lineLabels[] = $monthNames[(int)$row['entry_month']] . ' ' . $row['entry_year'];
    $lineData[] = (int)$row['total'];
}

// ---- Bar graph: count per IP Type ------------------------------------
$sql = "SELECT ip_type, COUNT(*) AS total FROM ip_entries $whereSql GROUP BY ip_type";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$typeRows = $stmt->fetchAll();
$typeCounts = array_fill_keys(IP_TYPES, 0);
foreach ($typeRows as $row) {
    if (isset($typeCounts[$row['ip_type']])) $typeCounts[$row['ip_type']] = (int)$row['total'];
}

// ---- Bar graph: count per Mode of Transfer ---------------------------
$sql = "SELECT mode_of_transfer, COUNT(*) AS total FROM ip_entries $whereSql GROUP BY mode_of_transfer";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$modeRows = $stmt->fetchAll();
$modeCounts = array_fill_keys(TRANSFER_MODES, 0);
foreach ($modeRows as $row) {
    if (isset($modeCounts[$row['mode_of_transfer']])) $modeCounts[$row['mode_of_transfer']] = (int)$row['total'];
}

// ---- Summary stats -----------------------------------------------------
$sql = "SELECT COUNT(*) AS total, COALESCE(SUM(amount_paid),0) AS total_amount,
        SUM(CASE WHEN ip_type = 'Patent' THEN 1 ELSE 0 END) AS patents,
        SUM(CASE WHEN status_of_application = 'Registered' THEN 1 ELSE 0 END) AS registered
        FROM ip_entries $whereSql";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$summary = $stmt->fetch();

echo json_encode([
    'line' => ['labels' => $lineLabels, 'data' => $lineData],
    'ip_type' => ['labels' => array_keys($typeCounts), 'data' => array_values($typeCounts)],
    'mode_of_transfer' => ['labels' => array_keys($modeCounts), 'data' => array_values($modeCounts)],
    'summary' => [
        'total' => (int)$summary['total'],
        'total_amount' => (float)$summary['total_amount'],
        'patents' => (int)$summary['patents'],
        'registered' => (int)$summary['registered'],
    ],
]);
