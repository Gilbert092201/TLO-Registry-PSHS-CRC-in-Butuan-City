<?php
/**
 * api/_filters.php
 * Builds a WHERE clause + params array from $_GET filter inputs.
 * Shared by entries.php, chart_data.php, export_csv.php, export_pdf.php
 */

function build_filters(array $q): array {
    $where = [];
    $params = [];

    $map_text = [
        'employee_number'    => 'employee_number',
        'ip_name'            => 'ip_name',
        'application_number' => 'application_number',
        'application_code'   => 'application_code',
        'title_of_ip'        => 'title_of_ip',
    ];
    foreach ($map_text as $key => $col) {
        if (!empty($q[$key])) {
            $where[] = "$col LIKE :$key";
            $params[$key] = '%' . $q[$key] . '%';
        }
    }

    $map_exact = [
        'entry_month'           => 'entry_month',
        'entry_day'             => 'entry_day',
        'entry_year'            => 'entry_year',
        'status_of_application' => 'status_of_application',
        'ip_type'                => 'ip_type',
        'mode_of_transfer'      => 'mode_of_transfer',
    ];
    foreach ($map_exact as $key => $col) {
        if (!empty($q[$key])) {
            $where[] = "$col = :$key";
            $params[$key] = $q[$key];
        }
    }

    if (isset($q['amount_min']) && $q['amount_min'] !== '') {
        $where[] = "amount_paid >= :amount_min";
        $params['amount_min'] = $q['amount_min'];
    }
    if (isset($q['amount_max']) && $q['amount_max'] !== '') {
        $where[] = "amount_paid <= :amount_max";
        $params['amount_max'] = $q['amount_max'];
    }

    $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    return [$sql, $params];
}
