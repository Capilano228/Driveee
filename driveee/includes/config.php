<?php
define('DB_HOST', 'MySQL-8.0');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'driveee_db');
define('DB_PORT', '3306');

define('SITE_URL', 'http://driveee');
define('SITE_NAME', 'DRIVEEE');
define('SITE_TIMEZONE', 'Europe/Moscow');

define('YANDEX_MAPS_API_KEY', '57a9d61f-c1da-45c6-97ed-1538bf977fbc');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_name('driveee_session');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set(SITE_TIMEZONE);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("❌ Ошибка: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>