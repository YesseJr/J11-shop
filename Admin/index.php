<?php require_once('header.php'); ?>

<?php
$total_product = $pdo->query("SELECT COUNT(*) FROM tbl_product")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM tbl_customer WHERE cust_status=1")->fetchColumn();
$total_order_pending = $pdo->query("SELECT COUNT(*) FROM tbl_payment WHERE payment_status='Pending'")->fetchColumn();
$total_order_completed = $pdo->query("SELECT COUNT(*) FROM tbl_payment WHERE payment_status='Completed'")->fetchColumn();
$total_top_category = $pdo->query("SELECT COUNT(*) FROM tbl_top_category")->fetchColumn();
$total_subscriber = $pdo->query("SELECT COUNT(*) FROM tbl_subscriber WHERE subs_active=1")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM tbl_payment WHERE payment_status='Completed'")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Dashboard</h4>
    <a href="product-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Product</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-box"></i></div>
                <div>
                    <h3><?php echo $total_product; ?></h3>
                    <small class="text-muted">Products</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                <div>
                    <h3><?php echo $total_order_pending; ?></h3>
                    <small class="text-muted">Pending Orders</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                <div>
                    <h3><?php echo $total_order_completed; ?></h3>
                    <small class="text-muted">Completed Orders</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-users"></i></div>
                <div>
                    <h3><?php echo $total_customers; ?></h3>
                    <small class="text-muted">Customers</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <small class="text-muted">Categories</small>
                <h3 class="mt-1"><?php echo $total_top_category; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <small class="text-muted">Subscribers</small>
                <h3 class="mt-1"><?php echo $total_subscriber; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <small class="text-muted">Revenue (Completed)</small>
                <h3 class="mt-1">$<?php echo number_format($revenue, 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<?php
// Recent orders
$recent = $pdo->query("SELECT * FROM tbl_payment ORDER BY id DESC LIMIT 5")->fetchAll();
?>
<div class="table-card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Orders</span>
        <a href="order.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No orders yet</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td>$<?php echo number_format($o['paid_amount'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $o['payment_status']==='Completed' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo htmlspecialchars($o['payment_status']); ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?php echo htmlspecialchars($o['payment_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('footer.php'); ?>
