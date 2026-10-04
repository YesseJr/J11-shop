<?php
require_once('inc_config.php');

$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim($_GET['q'] ?? '');

$sql = "SELECT p.* FROM tbl_product p WHERE p.p_is_active=1";
$params = [];

if ($cat > 0) {
    $sql .= " AND p.ecat_id IN (SELECT ecat_id FROM tbl_end_category e 
              JOIN tbl_mid_category m ON e.mcat_id = m.mcat_id 
              WHERE m.tcat_id = ?)";
    $params[] = $cat;
}
if ($search !== '') {
    $sql .= " AND (p.p_name LIKE ? OR p.p_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.p_is_featured DESC, p.p_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$top_cats = $pdo->query("SELECT * FROM tbl_top_category WHERE show_on_menu=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - J11 Online Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand, .nav-link { color: #fff !important; }
        .product-card { transition: transform .2s; border: none; border-radius: 12px; }
        .product-card:hover { transform: translateY(-5px); }
        .product-card img { height: 200px; object-fit: cover; }
        .price { color: #667eea; font-weight: 700; }
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

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Products</h2>
        </div>
        <div class="col-md-4">
            <form class="d-flex">
                <input type="text" name="q" class="form-control me-2" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="list-group">
                <a href="products.php" class="list-group-item list-group-item-action <?php echo $cat===0?'active':''; ?>">All Categories</a>
                <?php foreach ($top_cats as $c): ?>
                    <a href="products.php?cat=<?php echo $c['tcat_id']; ?>" class="list-group-item list-group-item-action <?php echo $cat===$c['tcat_id']?'active':''; ?>">
                        <?php echo htmlspecialchars($c['tcat_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-9">
            <div class="row g-3">
                <?php if (empty($products)): ?>
                    <div class="col-12"><div class="alert alert-info">No products found.</div></div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <div class="col-6 col-lg-4">
                            <div class="card product-card h-100 shadow-sm">
                                <img src="<?php echo $p['p_featured_photo'] ? 'assets/uploads/'.$p['p_featured_photo'] : 'https://via.placeholder.com/300x200?text='.urlencode($p['p_name']); ?>"
                                     class="card-img-top" alt="" onerror="this.src='https://via.placeholder.com/300x200?text=Product'">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($p['p_name']); ?></h6>
                                    <p class="price mb-2">$<?php echo number_format($p['p_current_price'], 2); ?></p>
                                    <a href="product.php?id=<?php echo $p['p_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <form action="cart-add.php" method="post" class="d-inline">
                                        <input type="hidden" name="p_id" value="<?php echo $p['p_id']; ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button class="btn btn-sm btn-primary"><i class="fas fa-cart-plus"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
