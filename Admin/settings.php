<?php require_once('header.php'); ?>
<?php require_once('inc/upload.php'); ?>

<?php
// Ensure extra columns exist
try {
    $pdo->exec("ALTER TABLE tbl_settings ADD COLUMN hero_image VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE tbl_settings ADD COLUMN hero_title VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE tbl_settings ADD COLUMN hero_subtitle TEXT");
} catch (Exception $e) {}

$settings = $pdo->query("SELECT * FROM tbl_settings WHERE id=1")->fetch();
if (!$settings) {
    $pdo->exec("INSERT INTO tbl_settings (id) VALUES (1)");
    $settings = $pdo->query("SELECT * FROM tbl_settings WHERE id=1")->fetch();
}

$upload_dir = __DIR__ . '/../assets/uploads/';
$error_message = '';
$success_message = '';

if (isset($_POST['form1'])) {
    $hero_title = trim($_POST['hero_title'] ?? '');
    $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $footer_about = trim($_POST['footer_about'] ?? '');

    $hero_image = $settings['hero_image'] ?? null;
    $logo = $settings['logo'] ?? null;

    $up_err = null;
    $new_hero = upload_image('hero_image', $upload_dir, $up_err);
    if ($new_hero) {
        // delete old
        if (!empty($hero_image) && file_exists($upload_dir . $hero_image)) {
            @unlink($upload_dir . $hero_image);
        }
        $hero_image = $new_hero;
    } elseif ($up_err) {
        $error_message = $up_err;
    }

    if (!$error_message) {
        $up_err = null;
        $new_logo = upload_image('logo', $upload_dir, $up_err);
        if ($new_logo) {
            if (!empty($logo) && file_exists($upload_dir . $logo)) {
                @unlink($upload_dir . $logo);
            }
            $logo = $new_logo;
        } elseif ($up_err) {
            $error_message = $up_err;
        }
    }

    if (!$error_message) {
        $stmt = $pdo->prepare("UPDATE tbl_settings SET 
            hero_image=?, hero_title=?, hero_subtitle=?, 
            logo=?, contact_email=?, contact_phone=?, footer_about=?
            WHERE id=1");
        $stmt->execute([$hero_image, $hero_title, $hero_subtitle, $logo, $contact_email, $contact_phone, $footer_about]);
        $success_message = 'Settings saved successfully.';
        $settings = $pdo->query("SELECT * FROM tbl_settings WHERE id=1")->fetch();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Site Settings & Images</h4>
</div>

<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- Hero -->
        <div class="col-lg-6">
            <div class="table-card">
                <div class="card-header">Hero Section</div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Hero Title</label>
                        <input type="text" name="hero_title" class="form-control"
                               value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Shop smarter. Live better.'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Hero Subtitle</label>
                        <textarea name="hero_subtitle" class="form-control" rows="3"><?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-medium">Hero Background Image</label>
                        <input type="file" name="hero_image" class="form-control" accept="image/*">
                        <div class="form-text">JPG, PNG, WEBP or GIF · max 3MB. Recommended 1600×900+</div>
                    </div>
                    <?php if (!empty($settings['hero_image'])): ?>
                        <div class="mt-2">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($settings['hero_image']); ?>"
                                 alt="Hero" class="img-fluid rounded border" style="max-height:160px">
                            <div class="small text-muted mt-1">Current hero image</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Logo & Contact -->
        <div class="col-lg-6">
            <div class="table-card mb-4">
                <div class="card-header">Logo</div>
                <div class="card-body p-4">
                    <div class="mb-2">
                        <label class="form-label fw-medium">Site Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <div class="form-text">Transparent PNG recommended</div>
                    </div>
                    <?php if (!empty($settings['logo'])): ?>
                        <div class="mt-2">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($settings['logo']); ?>"
                                 alt="Logo" class="img-fluid rounded border" style="max-height:80px">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <div class="card-header">Contact & Footer</div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control"
                               value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Footer About</label>
                        <textarea name="footer_about" class="form-control" rows="3"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" name="form1" class="btn btn-primary px-4">
            <i class="fas fa-save me-1"></i> Save Settings
        </button>
    </div>
</form>

<?php require_once('footer.php'); ?>
