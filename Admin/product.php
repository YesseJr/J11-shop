<?php require_once('header.php'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Products</h4>
    <a href="product-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Product</a>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:70px">Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th style="width:140px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM tbl_product ORDER BY p_id DESC");
            $i = 1;
            $rows = $stmt->fetchAll();
            if (empty($rows)):
            ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No products found. <a href="product-add.php">Add one</a></td></tr>
            <?php else:
                foreach ($rows as $row):
                    $img = !empty($row['p_featured_photo'])
                        ? '../assets/uploads/' . htmlspecialchars($row['p_featured_photo'])
                        : 'https://placehold.co/60x60/e2e8f0/94a3b8?text=?';
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td>
                        <img src="<?php echo $img; ?>" alt="" class="rounded border"
                             style="width:48px;height:48px;object-fit:cover"
                             onerror="this.src='https://placehold.co/60x60/e2e8f0/94a3b8?text=?'">
                    </td>
                    <td><strong><?php echo htmlspecialchars($row['p_name']); ?></strong></td>
                    <td>$<?php echo number_format($row['p_current_price'], 2); ?></td>
                    <td><?php echo (int)$row['p_qty']; ?></td>
                    <td><?php echo $row['p_is_featured'] ? '<span class="badge bg-info">Yes</span>' : '<span class="text-muted">No</span>'; ?></td>
                    <td>
                        <?php if ($row['p_is_active']): ?>
                            <span class="badge badge-status-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-status-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="product-add.php?edit=<?php echo $row['p_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <a href="product-delete.php?id=<?php echo $row['p_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('footer.php'); ?>
