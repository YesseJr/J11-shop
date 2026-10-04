<?php
// Frontend config (shared)
$dbhost = 'localhost';
$dbport = 3307;
$dbname = 'j11_shopping';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("Database connection failed. Please run <a href='setup.php'>setup.php</a> first.<br>" . htmlspecialchars($e->getMessage()));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
