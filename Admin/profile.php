<?php require_once('header.php'); ?>

<?php
$error_message = '';
$success_message = '';
$user_id = (int)($_SESSION['user']['id'] ?? 0);

// Refresh user from DB
$stmt = $pdo->prepare("SELECT * FROM tbl_user WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: logout.php');
    exit;
}

// Update profile
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($full_name === '' || $email === '') {
        $error_message = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address.';
    } else {
        // Check email unique
        $chk = $pdo->prepare("SELECT id FROM tbl_user WHERE email=? AND id!=?");
        $chk->execute([$email, $user_id]);
        if ($chk->fetch()) {
            $error_message = 'That email is already used by another account.';
        } else {
            $pdo->prepare("UPDATE tbl_user SET full_name=?, email=? WHERE id=?")->execute([$full_name, $email, $user_id]);
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['email'] = $email;
            $success_message = 'Profile updated successfully.';
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error_message = 'All password fields are required.';
    } elseif (strlen($new) < 6) {
        $error_message = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error_message = 'New passwords do not match.';
    } elseif (!password_verify($current, $user['password']) && md5($current) !== $user['password']) {
        $error_message = 'Current password is incorrect.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE tbl_user SET password=? WHERE id=?")->execute([$hash, $user_id]);
        $_SESSION['user']['password'] = $hash;
        $success_message = 'Password changed successfully.';
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">My Profile</h4>
</div>

<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header">Profile Information</div>
            <div class="card-body p-4">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email (login)</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Role</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-card">
            <div class="card-header">Change Password</div>
            <div class="card-body p-4">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key me-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
