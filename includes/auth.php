<?php
/**
 * includes/auth.php
 * Session / authentication helpers.
 */

require_once __DIR__ . '/../config.php';

function current_user(): ?array {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
        ];
    }
    return null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_login_api(): void {
    if (!current_user()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}
