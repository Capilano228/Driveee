<?php
define('DB_HOST', 'MySQL-8.0');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'driveee_db');
define('DB_PORT', '3306');

define('SITE_URL', 'http://driveee');

ini_set('session.cookie_httponly', 1);
session_name('driveee_session');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Ошибка: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>