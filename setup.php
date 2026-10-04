<?php
/**
 * J11 Online Shopping - First-time Setup
 * Creates database tables (if needed) and registers the first admin user.
 * Delete or rename this file after setup for security.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbhost = 'localhost';
$dbport = 3307;
$dbuser = 'root';
$dbpass = '';
$dbname = 'j11_shopping';

$message = '';
$error = '';
$setup_done = false;

try {
    $pdo = new PDO("mysql:host={$dbhost};port={$dbport}", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    // Check if tbl_user exists and has any admin
    $stmt = $pdo->query("SHOW TABLES LIKE 'tbl_user'");
    $table_exists = $stmt->rowCount() > 0;

    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_user");
        $admin_count = (int)$stmt->fetchColumn();
        if ($admin_count > 0) {
            $setup_done = true;
            $message = "Setup already completed. There is already an admin account. Please login at <a href='Admin/login.php'>Admin Login</a>.";
        }
    }

    if (!$setup_done && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // Import schema if tables missing
            if (!$table_exists) {
                $sql_file = __DIR__ . '/database.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    // Remove CREATE DATABASE and USE statements for safety
                    $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
                    $sql = preg_replace('/USE `.*?`;/is', '', $sql);
                    $pdo->exec($sql);
                } else {
                    // Minimal fallback tables
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS tbl_user (
                            id int(11) NOT NULL AUTO_INCREMENT,
                            full_name varchar(100) NOT NULL,
                            email varchar(100) NOT NULL UNIQUE,
                            password varchar(255) NOT NULL,
                            photo varchar(255) DEFAULT NULL,
                            role varchar(20) NOT NULL DEFAULT 'Admin',
                            status tinyint(1) NOT NULL DEFAULT 1,
                            PRIMARY KEY (id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                }
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO tbl_user (full_name, email, password, role, status) VALUES (?, ?, ?, 'Admin', 1)");
            $stmt->execute([$full_name, $email, $hashed]);

            $setup_done = true;
            $message = "Admin account created successfully! You can now <a href='Admin/login.php'><strong>login to the Admin Panel</strong></a>.";
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>J11 Online Shopping - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .setup-card { max-width: 480px; margin: 40px auto; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .setup-header { background: #343a40; color: #fff; border-radius: 15px 15px 0 0; padding: 25px; text-align: center; }
        .setup-header h1 { font-size: 1.6rem; margin: 0; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,.25); }
        .btn-setup { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-setup:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="container">
    <div class="card setup-card">
        <div class="setup-header">
            <h1>J11 Online Shopping</h1>
            <p class="mb-0 mt-2 opacity-75">First-time Admin Setup</p>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (!$setup_done): ?>
                <p class="text-muted small mb-4">Create the first administrator account. This page will only work once.</p>
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (used as login)</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary btn-setup w-100 py-2">Create Admin Account</button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="Admin/login.php" class="btn btn-primary btn-setup">Go to Admin Login</a>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">View Storefront</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center text-muted small">
            Database: <code><?php echo htmlspecialchars($dbname); ?></code> &nbsp;|&nbsp; After setup, delete or protect <code>setup.php</code>
        </div>
    </div>
</div>
</body>
</html>
