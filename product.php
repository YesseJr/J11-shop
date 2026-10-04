<?php
require_once('inc_config.php');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, e.ecat_name FROM tbl_product p LEFT JOIN tbl_end_category e ON p.ecat_id = e.ecat_id WHERE p.p_id=? AND p.p_is_active=1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Increment view
$pdo->prepare("UPDATE tbl_product SET p_total_view = p_total_view + 1 WHERE p_id=?")->execute([$id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['p_name']); ?> - J11 Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand, .nav-link { color: #fff !important; }
        .price { color: #667eea; font-size: 1.8rem; font-weight: 700; }
        .old-price { text-decoration: line-through; color: #999; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-shopping-bag me-2"></i>J11 Shop</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
            <a class="nav-link" href="index.php">Home</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <img src="<?php echo $product['p_featured_photo'] ? 'assets/uploads/'.$product['p_featured_photo'] : 'https://via.placeholder.com/500x400?text='.urlencode($product['p_name']); ?>"
                 class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($product['p_name']); ?>"
                 onerror="this.src='https://via.placeholder.com/500x400?text=Product'">
        </div>
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['p_name']); ?></li>
                </ol>
            </nav>
            <h1 class="h2"><?php echo htmlspecialchars($product['p_name']); ?></h1>
            <?php if ($product['ecat_name']): ?>
                <p class="text-muted">Category: <?php echo htmlspecialchars($product['ecat_name']); ?></p>
            <?php endif; ?>
            <div class="mb-3">
                <span class="price">$<?php echo number_format($product['p_current_price'], 2); ?></span>
                <?php if ($product['p_old_price'] > $product['p_current_price']): ?>
                    <span class="old-price ms-2">$<?php echo number_format($product['p_old_price'], 2); ?></span>
                <?php endif; ?>
            </div>
            <p><?php echo nl2br(htmlspecialchars($product['p_description'] ?? $product['p_short_description'] ?? '')); ?></p>
            <p class="text-muted"><small>In stock: <?php echo (int)$product['p_qty']; ?> | Views: <?php echo (int)$product['p_total_view']; ?></small></p>

            <form action="cart-add.php" method="post" class="mt-4">
                <input type="hidden" name="p_id" value="<?php echo $product['p_id']; ?>">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-auto">
                        <label class="col-form-label">Qty:</label>
                    </div>
                    <div class="col-auto">
                        <input type="number" name="qty" value="1" min="1" max="<?php echo max(1,$product['p_qty']); ?>" class="form-control" style="width:80px">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-cart-plus me-2"></i>Add to Cart</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
