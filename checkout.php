<?php
require_once('inc_config.php');

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Live stock validation
    $stock_ok = true;
    foreach ($cart as $pid => $item) {
        $st = $pdo->prepare("SELECT p_qty, p_name FROM tbl_product WHERE p_id=? AND p_is_active=1");
        $st->execute([$pid]);
        $row = $st->fetch();
        $avail = $row ? (int)$row['p_qty'] : 0;
        if ($avail < 1 || $item['qty'] > $avail) {
            $error = 'Insufficient stock for "' . ($row['p_name'] ?? $item['name']) . '". Available: ' . $avail . '. Please update your cart.';
            $stock_ok = false;
            break;
        }
    }

    if ($stock_ok) {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name) || empty($email) || empty($address)) {
            $error = 'Please fill in all required fields.';
        } else {
            $payment_id = time() . rand(100, 999);
            $customer_id = $_SESSION['customer']['cust_id'] ?? 0;

            $stmt = $pdo->prepare("INSERT INTO tbl_payment (customer_id, customer_name, customer_email, payment_date, paid_amount, payment_method, payment_status, shipping_status, payment_id) VALUES (?,?,?,?,?,'Cash on Delivery','Pending','Pending',?)");
            $stmt->execute([$customer_id, $name, $email, date('Y-m-d H:i:s'), $total, $payment_id]);

            foreach ($cart as $item) {
                $stmt = $pdo->prepare("INSERT INTO tbl_order (product_id, product_name, quantity, unit_price, payment_id) VALUES (?,?,?,?,?)");
                $stmt->execute([$item['p_id'], $item['name'], $item['qty'], $item['price'], $payment_id]);
                // Reduce stock
                $pdo->prepare("UPDATE tbl_product SET p_qty = GREATEST(0, p_qty - ?) WHERE p_id=?")->execute([$item['qty'], $item['p_id']]);
            }

            unset($_SESSION['cart']);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - J11 Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #5b6cf0, #7c3aed); }
        .navbar-brand, .nav-link { color: #fff !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">J11 Shop</a>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <h4>Thank you for your order!</h4>
            <p>Your order has been placed successfully. We will contact you soon.</p>
            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            <?php if (isset($_SESSION['customer'])): ?>
                <a href="customer-dashboard.php" class="btn btn-outline-primary ms-2">View My Orders</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="row">
            <div class="col-md-7">
                <div class="card mb-4">
                    <div class="card-header">Shipping Information</div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($_SESSION['customer']['cust_name'] ?? $_POST['name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($_SESSION['customer']['cust_email'] ?? $_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($_SESSION['customer']['cust_phone'] ?? $_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Shipping Address *</label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <input type="text" class="form-control" value="Cash on Delivery" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                            <a href="cart.php" class="btn btn-outline-secondary ms-2">Back to Cart</a>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">Order Summary</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($cart as $item): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['qty']; ?></span>
                                    <span>$<?php echo number_format($item['price']*$item['qty'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
