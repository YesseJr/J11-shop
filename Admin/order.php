<?php require_once('header.php'); ?>

<?php
$success_message = '';

// Update payment or shipping status
if (isset($_POST['update_status'])) {
    $id = (int)($_POST['order_id'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? '';
    $shipping_status = $_POST['shipping_status'] ?? '';
    $allowed = ['Pending', 'Completed', 'Cancelled'];
    if ($id > 0 && in_array($payment_status, $allowed) && in_array($shipping_status, $allowed)) {
        $pdo->prepare("UPDATE tbl_payment SET payment_status=?, shipping_status=? WHERE id=?")
            ->execute([$payment_status, $shipping_status, $id]);
        $success_message = 'Order status updated.';
    }
}

// View single order detail
$view_id = (int)($_GET['view'] ?? 0);
$view_order = null;
$view_items = [];
if ($view_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_payment WHERE id=?");
    $stmt->execute([$view_id]);
    $view_order = $stmt->fetch();
    if ($view_order) {
        $stmt = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $stmt->execute([$view_order['payment_id']]);
        $view_items = $stmt->fetchAll();
    }
}

$rows = $pdo->query("SELECT * FROM tbl_payment ORDER BY id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Orders</h4>
    <span class="text-muted small"><?php echo count($rows); ?> total</span>
</div>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

<?php if ($view_order): ?>
<!-- Order detail modal-like card -->
<div class="table-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Order #<?php echo htmlspecialchars($view_order['payment_id']); ?></span>
        <a href="order.php" class="btn btn-sm btn-outline-secondary">Back to list</a>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-bold">Customer</h6>
                <p class="mb-1"><strong><?php echo htmlspecialchars($view_order['customer_name']); ?></strong></p>
                <p class="mb-1 text-muted"><?php echo htmlspecialchars($view_order['customer_email']); ?></p>
                <p class="mb-0 text-muted small">Date: <?php echo htmlspecialchars($view_order['payment_date']); ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-bold">Status</h6>
                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                    <div class="col-5">
                        <label class="form-label small mb-1">Payment</label>
                        <select name="payment_status" class="form-select form-select-sm">
                            <?php foreach (['Pending','Completed','Cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $view_order['payment_status']===$s?'selected':''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label small mb-1">Shipping</label>
                        <select name="shipping_status" class="form-select form-select-sm">
                            <?php foreach (['Pending','Completed','Cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $view_order['shipping_status']===$s?'selected':''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <h6 class="text-muted text-uppercase small fw-bold mb-2">Items</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($view_items as $item):
                    $sub = $item['unit_price'] * $item['quantity'];
                    $total += $sub;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>$<?php echo number_format($sub, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($view_items)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No line items</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th>$<?php echo number_format($view_order['paid_amount'] ?: $total, 2); ?></th>
                </tr>
            </tfoot>
        </table>
        <p class="small text-muted mb-0">Payment method: <?php echo htmlspecialchars($view_order['payment_method']); ?></p>
    </div>
</div>
<?php endif; ?>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Shipping</th>
                    <th>Date</th>
                    <th style="width:180px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            if (empty($rows)):
            ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No orders yet</td></tr>
            <?php else:
                foreach ($rows as $row):
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong>
                        <div class="small text-muted"><?php echo htmlspecialchars($row['customer_email']); ?></div>
                    </td>
                    <td class="fw-medium">$<?php echo number_format($row['paid_amount'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $row['payment_status']==='Completed' ? 'bg-success' : ($row['payment_status']==='Cancelled' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                            <?php echo htmlspecialchars($row['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $row['shipping_status']==='Completed' ? 'bg-success' : ($row['shipping_status']==='Cancelled' ? 'bg-danger' : 'bg-secondary'); ?>">
                            <?php echo htmlspecialchars($row['shipping_status']); ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?php echo htmlspecialchars($row['payment_date']); ?></td>
                    <td>
                        <a href="order.php?view=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <!-- Quick status form -->
                        <form method="post" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="payment_status" value="<?php echo $row['payment_status']==='Pending'?'Completed':$row['payment_status']; ?>">
                            <input type="hidden" name="shipping_status" value="<?php echo $row['shipping_status']==='Pending'?'Completed':$row['shipping_status']; ?>">
                            <?php if ($row['payment_status']==='Pending' || $row['shipping_status']==='Pending'): ?>
                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-success" title="Mark completed"
                                    onclick="return confirm('Mark payment & shipping as Completed?')">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('footer.php'); ?>
