<?php
require_once('inc_config.php');

if (!isset($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}

$cust = $_SESSION['customer'];
$cust_id = (int)$cust['cust_id'];

// Fetch orders for this customer (by customer_id or email)
$stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE customer_id=? OR customer_email=? ORDER BY id DESC");
$stmt->execute([$cust_id, $cust['cust_email']]);
$orders = $stmt->fetchAll();

// View one order
$view_id = (int)($_GET['order'] ?? 0);
$view_order = null;
$view_items = [];
if ($view_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE id=? AND (customer_id=? OR customer_email=?)");
    $stmt->execute([$view_id, $cust_id, $cust['cust_email']]);
    $view_order = $stmt->fetch();
    if ($view_order) {
        $stmt = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $stmt->execute([$view_order['payment_id']]);
        $view_items = $stmt->fetchAll();
    }
}

$top_cats = $pdo->query("SELECT * FROM tbl_top_category WHERE show_on_menu=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - J11 Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #5b6cf0; --dark: #0f172a; --muted: #64748b; --surface: #f8fafc; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--surface); color: var(--dark); }
        .navbar { background: rgba(15,23,42,0.92); backdrop-filter: blur(12px); padding: 0.85rem 0; }
        .navbar-brand { font-weight: 800; color: #fff !important; }
        .navbar-brand span { color: #a5b4fc; }
        .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 500; font-size: 0.92rem; }
        .account-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
        .account-card .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; font-weight: 700; padding: 1rem 1.25rem; }
        .status-badge { font-size: 0.75rem; font-weight: 600; padding: 0.3rem 0.65rem; border-radius: 6px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-shopping-bag me-1"></i> J11<span>Shop</span></a>
        <div class="navbar-nav ms-auto flex-row gap-3">
            <a class="nav-link" href="products.php">Shop</a>
            <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i></a>
            <a class="nav-link" href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="account-card">
                <div class="p-4 text-center border-bottom">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-2"
                         style="width:64px;height:64px;font-size:1.5rem;font-weight:700">
                        <?php echo strtoupper(substr($cust['cust_name'], 0, 1)); ?>
                    </div>
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($cust['cust_name']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($cust['cust_email']); ?></small>
                </div>
                <div class="list-group list-group-flush">
                    <a href="customer-dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-box me-2"></i> My Orders
                    </a>
                    <a href="products.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-store me-2"></i> Continue Shopping
                    </a>
                    <a href="cart.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i> Cart
                    </a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main -->
        <div class="col-lg-9">
            <?php if ($view_order): ?>
                <div class="account-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Order #<?php echo htmlspecialchars($view_order['payment_id']); ?></span>
                        <a href="customer-dashboard.php" class="btn btn-sm btn-outline-secondary">Back</a>
                    </div>
                    <div class="p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <small class="text-muted text-uppercase fw-bold">Order Date</small>
                                <div><?php echo htmlspecialchars($view_order['payment_date']); ?></div>
                            </div>
                            <div class="col-sm-3">
                                <small class="text-muted text-uppercase fw-bold">Payment</small>
                                <div>
                                    <span class="status-badge <?php echo $view_order['payment_status']==='Completed'?'bg-success text-white':'bg-warning text-dark'; ?>">
                                        <?php echo htmlspecialchars($view_order['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <small class="text-muted text-uppercase fw-bold">Shipping</small>
                                <div>
                                    <span class="status-badge <?php echo $view_order['shipping_status']==='Completed'?'bg-success text-white':'bg-secondary text-white'; ?>">
                                        <?php echo htmlspecialchars($view_order['shipping_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($view_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo (int)$item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>$<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th>$<?php echo number_format($view_order['paid_amount'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <p class="small text-muted mb-0">Payment method: <?php echo htmlspecialchars($view_order['payment_method']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="account-card">
                    <div class="card-header">My Orders</div>
                    <?php if (empty($orders)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                            <p class="mb-3">You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Shipping</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="fw-medium">#<?php echo htmlspecialchars($o['payment_id']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($o['payment_date']); ?></td>
                                            <td class="fw-bold">$<?php echo number_format($o['paid_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $o['payment_status']==='Completed'?'bg-success text-white':($o['payment_status']==='Cancelled'?'bg-danger text-white':'bg-warning text-dark'); ?>">
                                                    <?php echo htmlspecialchars($o['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $o['shipping_status']==='Completed'?'bg-success text-white':($o['shipping_status']==='Cancelled'?'bg-danger text-white':'bg-secondary text-white'); ?>">
                                                    <?php echo htmlspecialchars($o['shipping_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="customer-dashboard.php?order=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
