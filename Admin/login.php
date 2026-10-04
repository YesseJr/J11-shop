<?php
ob_start();
session_start();
require_once('inc/config.php');

if (isset($_SESSION['user'])) {
    header('location: index.php');
    exit;
}

$error_message = '';

if (isset($_POST['form1'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Email and password are required.';
    } else {
        $statement = $pdo->prepare("SELECT * FROM tbl_user WHERE email=? AND status=1");
        $statement->execute([$email]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            $error_message = 'Email address does not exist or account is inactive.';
        } else {
            foreach ($result as $row) {
                $stored = $row['password'];
                if (password_verify($password, $stored) || md5($password) === $stored) {
                    $_SESSION['user'] = $row;
                    header('location: index.php');
                    exit;
                }
            }
            $error_message = 'Incorrect password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - J11 Online Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,.25);
            overflow: hidden;
        }
        .login-card .card-header {
            background: #1e1e2d;
            color: #fff;
            text-align: center;
            padding: 1.75rem;
            border: none;
        }
        .login-card .card-header h1 { font-size: 1.4rem; margin: 0; font-weight: 700; }
        .login-card .card-header p { margin: .4rem 0 0; opacity: .7; font-size: .9rem; }
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: .7rem;
            font-weight: 600;
        }
        .btn-login:hover { opacity: .92; background: linear-gradient(135deg, #5a6fd6, #6a4190); }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 .2rem rgba(102,126,234,.25); }
    </style>
</head>
<body>
<div class="login-card card">
    <div class="card-header">
        <h1><i class="fas fa-shopping-bag me-2"></i>J11 Online Shopping</h1>
        <p>Admin Panel Login</p>
    </div>
    <div class="card-body p-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="admin@example.com" required autofocus
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" name="form1" class="btn btn-primary btn-login w-100 text-white">Sign In</button>
        </form>

        <div class="text-center mt-3 small text-muted">
            <a href="../setup.php" class="text-decoration-none">First time? Run Setup</a>
            &nbsp;·&nbsp;
            <a href="../index.php" class="text-decoration-none">Back to Store</a>
        </div>
    </div>
</div>
</body>
</html>
<?php ob_end_flush(); ?>
