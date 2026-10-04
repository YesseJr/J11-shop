<?php
require_once('inc_config.php');

// Update qty or remove
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header('Location: cart.php');
    exit;
}

if (isset($_POST['update_cart']) && isset($_SESSION['cart'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $pid = (int)$pid;
        $qty = max(0, (int)$qty);
        if ($qty === 0) {
            unset($_SESSION['cart'][$pid]);
            continue;
        }
        if (!isset($_SESSION['cart'][$pid])) continue;

        // Re-check live stock
        $stmt = $pdo->prepare("SELECT p_qty, p_name FROM tbl_product WHERE p_id=? AND p_is_active=1");
        $stmt->execute([$pid]);
        $prod = $stmt->fetch();
        if (!$prod || (int)$prod['p_qty'] < 1) {
            unset($_SESSION['cart'][$pid]);
            $_SESSION['flash_error'] = '"' . ($prod['p_name'] ?? 'Item') . '" is no longer available and was removed.';
            continue;
        }
        $stock = (int)$prod['p_qty'];
        if ($qty > $stock) {
            $qty = $stock;
            $_SESSION['flash_error'] = 'Only ' . $stock . ' of "' . $prod['p_name'] . '" in stock.';
        }
        $_SESSION['cart'][$pid]['qty'] = $qty;
        $_SESSION['cart'][$pid]['stock'] = $stock;
    }
    header('Location: cart.php');
    exit;
}

// Refresh stock info for display
$cart = $_SESSION['cart'] ?? [];
$total = 0;
$stock_issues = [];
foreach ($cart as $pid => &$item) {
    $stmt = $pdo->prepare("SELECT p_qty FROM tbl_product WHERE p_id=? AND p_is_active=1");
    $stmt->execute([$pid]);
    $row = $stmt->fetch();
    $live_stock = $row ? (int)$row['p_qty'] : 0;
    $item['stock'] = $live_stock;
    if ($live_stock < 1) {
        $stock_issues[] = $item['name'] . ' is out of stock.';
    } elseif ($item['qty'] > $live_stock) {
        $stock_issues[] = 'Only ' . $live_stock . ' of "' . $item['name'] . '" available (you have ' . $item['qty'] . ').';
    }
    $total += $item['price'] * $item['qty'];
}
unset($item);
$_SESSION['cart'] = $cart;

$flash_error = $_SESSION['flash_error'] ?? '';
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - J11 Online Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #5b6cf0, #7c3aed); }
        .navbar-brand, .nav-link { color: #fff !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-shopping-bag me-2"></i>J11 Shop</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php">Continue Shopping</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">Shopping Cart</h2>

    <?php if ($flash_error): ?><div class="alert alert-warning"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
    <?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
    <?php foreach ($stock_issues as $msg): ?>
        <div class="alert alert-danger py-2"><?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>

    <?php if (empty($cart)): ?>
        <div class="alert alert-info">Your cart is empty. <a href="index.php">Start shopping</a></div>
    <?php else: ?>
        <form method="post">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Stock</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $item): ?>
                            <tr class="<?php echo ($item['stock'] < 1 || $item['qty'] > $item['stock']) ? 'table-warning' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td style="width:120px">
                                    <input type="number" name="qty[<?php echo $item['p_id']; ?>]"
                                           value="<?php echo $item['qty']; ?>"
                                           min="0" max="<?php echo max(1, $item['stock']); ?>"
                                           class="form-control form-control-sm"
                                           <?php echo $item['stock'] < 1 ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <?php if ($item['stock'] < 1): ?>
                                        <span class="badge bg-danger">Out of stock</span>
                                    <?php else: ?>
                                        <span class="text-muted small"><?php echo $item['stock']; ?> left</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                                <td><a href="cart.php?remove=<?php echo $item['p_id']; ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total:</td>
                            <td class="fw-bold fs-5">$<?php echo number_format($total, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" name="update_cart" class="btn btn-outline-secondary">Update Cart</button>
                <?php if (empty($stock_issues)): ?>
                    <a href="checkout.php" class="btn btn-primary btn-lg">Proceed to Checkout</a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled title="Fix stock issues first">Proceed to Checkout</button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
