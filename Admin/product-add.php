<?php require_once('header.php'); ?>
<?php require_once('inc/upload.php'); ?>

<?php
$edit_id = (int)($_GET['edit'] ?? 0);
$product = null;
if ($edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
    $stmt->execute([$edit_id]);
    $product = $stmt->fetch();
}

$upload_dir = __DIR__ . '/../assets/uploads/';
$error_message = '';
$success_message = '';

if (isset($_POST['form1'])) {
    $name = trim($_POST['p_name'] ?? '');
    $price = (float)($_POST['p_current_price'] ?? 0);
    $old_price = (float)($_POST['p_old_price'] ?? 0);
    $qty = (int)($_POST['p_qty'] ?? 0);
    $desc = trim($_POST['p_description'] ?? '');
    $short = trim($_POST['p_short_description'] ?? '');
    $featured = isset($_POST['p_is_featured']) ? 1 : 0;
    $active = isset($_POST['p_is_active']) ? 1 : 0;
    $ecat = (int)($_POST['ecat_id'] ?? 0) ?: null;

    $photo = $product['p_featured_photo'] ?? null;
    $up_err = null;
    $new_photo = upload_image('p_featured_photo', $upload_dir, $up_err);
    if ($new_photo) {
        if (!empty($photo) && file_exists($upload_dir . $photo)) {
            @unlink($upload_dir . $photo);
        }
        $photo = $new_photo;
    } elseif ($up_err) {
        $error_message = $up_err;
    }

    if (!$error_message && $name && $price > 0) {
        if ($edit_id > 0 && $product) {
            $stmt = $pdo->prepare("UPDATE tbl_product SET p_name=?, p_old_price=?, p_current_price=?, p_qty=?, p_featured_photo=?, p_description=?, p_short_description=?, p_is_featured=?, p_is_active=?, ecat_id=? WHERE p_id=?");
            $stmt->execute([$name, $old_price, $price, $qty, $photo, $desc, $short, $featured, $active, $ecat, $edit_id]);
            $success_message = 'Product updated successfully!';
            $stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
            $stmt->execute([$edit_id]);
            $product = $stmt->fetch();
        } else {
            $stmt = $pdo->prepare("INSERT INTO tbl_product (p_name, p_old_price, p_current_price, p_qty, p_featured_photo, p_description, p_short_description, p_is_featured, p_is_active, ecat_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $old_price, $price, $qty, $photo, $desc, $short, $featured, $active, $ecat]);
            $success_message = 'Product added successfully!';
            $edit_id = 0;
            $product = null;
        }
    } elseif (!$error_message) {
        $error_message = 'Name and price are required.';
    }
}

$categories = $pdo->query("SELECT e.ecat_id, e.ecat_name, m.mcat_name, t.tcat_name 
    FROM tbl_end_category e 
    JOIN tbl_mid_category m ON e.mcat_id=m.mcat_id 
    JOIN tbl_top_category t ON m.tcat_id=t.tcat_id 
    ORDER BY t.tcat_name, m.mcat_name, e.ecat_name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold"><?php echo $product ? 'Edit Product' : 'Add Product'; ?></h4>
    <a href="product.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
</div>

<div class="table-card">
    <div class="card-body p-4">
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?> <a href="product.php">View all</a></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Product Name *</label>
                        <input type="text" name="p_name" class="form-control" required value="<?php echo htmlspecialchars($product['p_name'] ?? ''); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Current Price *</label>
                            <input type="number" step="0.01" name="p_current_price" class="form-control" required value="<?php echo htmlspecialchars($product['p_current_price'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Old Price</label>
                            <input type="number" step="0.01" name="p_old_price" class="form-control" value="<?php echo htmlspecialchars($product['p_old_price'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Quantity</label>
                            <input type="number" name="p_qty" class="form-control" value="<?php echo htmlspecialchars($product['p_qty'] ?? '10'); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Category</label>
                        <select name="ecat_id" class="form-select">
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['ecat_id']; ?>" <?php echo (($product['ecat_id'] ?? '') == $c['ecat_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['tcat_name'].' › '.$c['mcat_name'].' › '.$c['ecat_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Short Description</label>
                        <textarea name="p_short_description" class="form-control" rows="2"><?php echo htmlspecialchars($product['p_short_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Full Description</label>
                        <textarea name="p_description" class="form-control" rows="4"><?php echo htmlspecialchars($product['p_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3 d-flex gap-4">
                        <div class="form-check">
                            <input type="checkbox" name="p_is_featured" value="1" class="form-check-input" id="feat" <?php echo !empty($product['p_is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="feat">Featured</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="p_is_active" value="1" class="form-check-input" id="act" <?php echo ($product === null || !empty($product['p_is_active'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="act">Active</label>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded p-3 bg-light">
                        <label class="form-label fw-medium">Product Image</label>
                        <input type="file" name="p_featured_photo" class="form-control mb-2" accept="image/*">
                        <div class="form-text mb-3">JPG, PNG, WEBP · max 3MB</div>
                        <?php if (!empty($product['p_featured_photo'])): ?>
                            <img src="../assets/uploads/<?php echo htmlspecialchars($product['p_featured_photo']); ?>"
                                 alt="Product" class="img-fluid rounded border w-100"
                                 style="max-height:220px;object-fit:cover"
                                 onerror="this.style.display='none'">
                            <div class="small text-muted mt-1">Current image</div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 border rounded bg-white">
                                <i class="fas fa-image fa-2x mb-2 d-block opacity-50"></i>
                                No image uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="form1" class="btn btn-primary px-4"><?php echo $product ? 'Update Product' : 'Add Product'; ?></button>
                <a href="product.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>
