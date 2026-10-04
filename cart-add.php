<?php
require_once('inc_config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = (int)($_POST['p_id'] ?? 0);
    $qty  = max(1, (int)($_POST['qty'] ?? 1));

    $stmt = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=? AND p_is_active=1");
    $stmt->execute([$p_id]);
    $product = $stmt->fetch();

    if ($product) {
        $stock = (int)$product['p_qty'];
        if ($stock < 1) {
            $_SESSION['flash_error'] = 'Sorry, "' . $product['p_name'] . '" is out of stock.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit;
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $already = isset($_SESSION['cart'][$p_id]) ? (int)$_SESSION['cart'][$p_id]['qty'] : 0;
        $new_qty = $already + $qty;

        if ($new_qty > $stock) {
            $new_qty = $stock;
            $_SESSION['flash_error'] = 'Only ' . $stock . ' of "' . $product['p_name'] . '" available. Cart updated to max stock.';
        }

        $_SESSION['cart'][$p_id] = [
            'p_id'   => $product['p_id'],
            'name'   => $product['p_name'],
            'price'  => $product['p_current_price'],
            'photo'  => $product['p_featured_photo'],
            'qty'    => $new_qty,
            'stock'  => $stock
        ];

        if (empty($_SESSION['flash_error'])) {
            $_SESSION['flash_success'] = '"' . $product['p_name'] . '" added to cart.';
        }
    } else {
        $_SESSION['flash_error'] = 'Product not found or unavailable.';
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
