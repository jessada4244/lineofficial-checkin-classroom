<?php
// config/db.php

$host = 'localhost';
$db   = 'lineof_checkin_db'; // ชื่อ Database ที่คุณสร้างใน MySQL
$user = 'root';            // Username เข้า DB (XAMPP ปกติคือ root)
$pass = '';                // Password เข้า DB (XAMPP ปกติคือว่างไว้)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>