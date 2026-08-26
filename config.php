<?php
/**
 * ============================================================
 * Philippine Science High School - Caraga Region Campus
 * Technology Licensing Office (TLO) Registry
 * config.php - database connection & shared settings
 * ============================================================
 * Edit the four constants below to match your MySQL setup.
 */

// ---- Database credentials -----------------------------------
define('DB_HOST', 'localhost:8889');
define('DB_NAME', 'tlo_registry');
define('DB_USER', 'root');
define('DB_PASS', 'root');

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
const STATUS_OPTIONS = ['Filed', 'Registered', 'Formality examination', 'Substantive examination'];
