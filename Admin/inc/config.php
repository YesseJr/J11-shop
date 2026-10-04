<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Database credentials
$dbhost = 'localhost';
$dbport = 3307;
$dbname = 'j11_shopping';
$dbuser = 'root';
$dbpass = '';

// Base URLs (adjust if project is in a subfolder)
define("BASE_URL", "/");
define("ADMIN_URL", BASE_URL . "Admin/");

// PDO Connection
try {
    $pdo = new PDO(
        "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        die('<h2>Database not found</h2><p>Please run <a href="../setup.php">setup.php</a> first to create the database and admin account.</p>');
    }
    die("Database connection error: " . htmlspecialchars($e->getMessage()));
}
?>
