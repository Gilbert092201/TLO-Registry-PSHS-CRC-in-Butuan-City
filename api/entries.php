<?php
/**
 * api/entries.php
 * JSON API: list (GET), create (POST), update (PUT), delete (DELETE)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_filters.php';
require_login_api();

header('Content-Type: application/json');
$pdo = get_db();

$method = $_SERVER['REQUEST_METHOD'];

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function validate_entry(array $d, array &$errors): array {
    $clean = [];

    $clean['employee_number'] = trim($d['employee_number'] ?? '');
    if ($clean['employee_number'] === '') $errors[] = 'Employee Number is required.';

    $clean['entry_month'] = (int)($d['entry_month'] ?? 0);
    if ($clean['entry_month'] < 1 || $clean['entry_month'] > 12) $errors[] = 'Month must be between 1 and 12.';

    $clean['entry_day'] = (int)($d['entry_day'] ?? 0);
    if ($clean['entry_day'] < 1 || $clean['entry_day'] > 31) $errors[] = 'Day must be between 1 and 31.';

    $clean['entry_year'] = (int)($d['entry_year'] ?? 0);
    if ($clean['entry_year'] < 2000 || $clean['entry_year'] > 2100) $errors[] = 'Year looks invalid.';

    $clean['ip_name'] = trim($d['ip_name'] ?? '');
    if ($clean['ip_name'] === '') $errors[] = 'IP Name is required.';

    $clean['application_number'] = trim($d['application_number'] ?? '');
    if ($clean['application_number'] === '') $errors[] = 'Application Number is required.';

    $clean['application_code'] = trim($d['application_code'] ?? '');
    if ($clean['application_code'] === '') $errors[] = 'Application Code is required.';

    $clean['status_of_application'] = trim($d['status_of_application'] ?? '');
    if (!in_array($clean['status_of_application'], STATUS_OPTIONS, true)) $errors[] = 'Invalid status of application.';

    $clean['amount_paid'] = isset($d['amount_paid']) ? (float)$d['amount_paid'] : -1;
    if ($clean['amount_paid'] < 0) $errors[] = 'Amount Paid must be zero or greater.';

    $clean['ip_type'] = trim($d['ip_type'] ?? '');
    if (!in_array($clean['ip_type'], IP_TYPES, true)) $errors[] = 'Invalid IP Type.';

    $clean['mode_of_transfer'] = trim($d['mode_of_transfer'] ?? '');
    if (!in_array($clean['mode_of_transfer'], TRANSFER_MODES, true)) $errors[] = 'Invalid Mode of Technology Transfer.';

    $clean['title_of_ip'] = trim($d['title_of_ip'] ?? '');
    if ($clean['title_of_ip'] === '') $errors[] = 'Title of the IP is required.';

    return $clean;
}

try {
    switch ($method) {

        case 'GET': {
            [$whereSql, $params] = build_filters($_GET);
            $sql = "SELECT * FROM ip_entries $whereSql ORDER BY entry_year DESC, entry_month DESC, entry_day DESC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            echo json_encode(['data' => $rows]);
            break;
        }

        case 'POST': {
            $body = read_json_body();
            $errors = [];
            $clean = validate_entry($body, $errors);
            if ($errors) {
                http_response_code(422);
                echo json_encode(['errors' => $errors]);
                break;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO ip_entries
                 (employee_number, entry_month, entry_day, entry_year, ip_name, application_number, application_code,
                  status_of_application, amount_paid, ip_type, mode_of_transfer, title_of_ip, created_by)
                 VALUES
                 (:employee_number, :entry_month, :entry_day, :entry_year, :ip_name, :application_number, :application_code,
                  :status_of_application, :amount_paid, :ip_type, :mode_of_transfer, :title_of_ip, :created_by)'
            );
            $clean['created_by'] = current_user()['id'];
            $stmt->execute($clean);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;
        }

        case 'PUT': {
            $body = read_json_body();
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['errors' => ['Missing entry id.']]);
                break;
            }
            $errors = [];
            $clean = validate_entry($body, $errors);
            if ($errors) {
                http_response_code(422);
                echo json_encode(['errors' => $errors]);
                break;
            }
            $clean['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE ip_entries SET
                    employee_number = :employee_number,
                    entry_month = :entry_month,
                    entry_day = :entry_day,
                    entry_year = :entry_year,
                    ip_name = :ip_name,
                    application_number = :application_number,
                    application_code = :application_code,
                    status_of_application = :status_of_application,
                    amount_paid = :amount_paid,
                    ip_type = :ip_type,
                    mode_of_transfer = :mode_of_transfer,
                    title_of_ip = :title_of_ip
                 WHERE id = :id'
            );
            $stmt->execute($clean);
            echo json_encode(['success' => true]);
            break;
        }

        case 'DELETE': {
            parse_str(file_get_contents('php://input'), $body);
            $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['errors' => ['Missing entry id.']]);
                break;
            }
            $stmt = $pdo->prepare('DELETE FROM ip_entries WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        }

        default:
            http_response_code(405);
            echo json_encode(['errors' => ['Method not allowed.']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['errors' => ['Server error: ' . $e->getMessage()]]);
}
