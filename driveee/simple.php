<?php
echo "<h1>DRIVEEE работает!</h1>";
echo "<p>Сервер: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>PHP версия: " . phpversion() . "</p>";

try {
    $pdo = new PDO("mysql:host=MySQL-8.0;dbname=driveee_db;charset=utf8mb4", 'root', '');
    echo "<p style='color:green'>✅ База данных подключена</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Ошибка БД: " . $e->getMessage() . "</p>";
}
?>