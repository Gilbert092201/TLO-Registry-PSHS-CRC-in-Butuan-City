<?php
/**
 * ============================================================
 * Philippine Science High School - Caraga Region Campus
 * Technology Licensing Office (TLO) Registry
 * config.sample.php - TEMPLATE ONLY, safe to commit to GitHub
 * ============================================================
 * How to use:
 *   1. Copy this file and rename the copy to "config.php"
 *   2. Fill in your real database host/name/user/password below
 *   3. config.php is listed in .gitignore, so your real
 *      credentials will never be uploaded to GitHub
 */

// ---- Database credentials -----------------------------------
define('DB_HOST', 'localhost');        // e.g. 'localhost' or 'localhost:8889' for MAMP
define('DB_NAME', 'tlo_registry');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_PASSWORD_HERE');

// ---- Session ---------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- PDO connection ---------------------------------------------
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

// Allowed dropdown values (kept in one place so PHP + JS agree)
const IP_TYPES = ['Trademark', 'Copyright', 'Industrial Design', 'Utility Model', 'Patent'];
const TRANSFER_MODES = ['Commercialization', 'Deployment', 'Extension', 'No Transfer'];
const STATUS_OPTIONS = ['Filled', 'Registered', 'Formality examination', 'Substantive examination'];
