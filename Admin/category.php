<?php require_once('header.php'); ?>

<?php
$error_message = '';
$success_message = '';

// --- DELETE ---
if (isset($_GET['delete_top'])) {
    $id = (int)$_GET['delete_top'];
    // cascade: delete end -> mid -> top
    $mids = $pdo->prepare("SELECT mcat_id FROM tbl_mid_category WHERE tcat_id=?");
    $mids->execute([$id]);
    foreach ($mids->fetchAll() as $m) {
        $pdo->prepare("DELETE FROM tbl_end_category WHERE mcat_id=?")->execute([$m['mcat_id']]);
    }
    $pdo->prepare("DELETE FROM tbl_mid_category WHERE tcat_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM tbl_top_category WHERE tcat_id=?")->execute([$id]);
    $success_message = 'Top category and its children deleted.';
}
if (isset($_GET['delete_mid'])) {
    $id = (int)$_GET['delete_mid'];
    $pdo->prepare("DELETE FROM tbl_end_category WHERE mcat_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM tbl_mid_category WHERE mcat_id=?")->execute([$id]);
    $success_message = 'Mid category and its end categories deleted.';
}
if (isset($_GET['delete_end'])) {
    $id = (int)$_GET['delete_end'];
    $pdo->prepare("DELETE FROM tbl_end_category WHERE ecat_id=?")->execute([$id]);
    $success_message = 'End category deleted.';
}

// --- ADD TOP ---
if (isset($_POST['add_top'])) {
    $name = trim($_POST['tcat_name'] ?? '');
    $show = isset($_POST['show_on_menu']) ? 1 : 0;
    if ($name === '') {
        $error_message = 'Top category name is required.';
    } else {
        $pdo->prepare("INSERT INTO tbl_top_category (tcat_name, show_on_menu) VALUES (?,?)")->execute([$name, $show]);
        $success_message = 'Top category added.';
    }
}

// --- ADD MID ---
if (isset($_POST['add_mid'])) {
    $name = trim($_POST['mcat_name'] ?? '');
    $tcat = (int)($_POST['tcat_id'] ?? 0);
    if ($name === '' || !$tcat) {
        $error_message = 'Mid category name and parent are required.';
    } else {
        $pdo->prepare("INSERT INTO tbl_mid_category (mcat_name, tcat_id) VALUES (?,?)")->execute([$name, $tcat]);
        $success_message = 'Mid category added.';
    }
}

// --- ADD END ---
if (isset($_POST['add_end'])) {
    $name = trim($_POST['ecat_name'] ?? '');
    $mcat = (int)($_POST['mcat_id'] ?? 0);
    if ($name === '' || !$mcat) {
        $error_message = 'End category name and parent are required.';
    } else {
        $pdo->prepare("INSERT INTO tbl_end_category (ecat_name, mcat_id) VALUES (?,?)")->execute([$name, $mcat]);
        $success_message = 'End category added.';
    }
}

// --- TOGGLE SHOW ON MENU ---
if (isset($_GET['toggle_top'])) {
    $id = (int)$_GET['toggle_top'];
    $pdo->prepare("UPDATE tbl_top_category SET show_on_menu = 1 - show_on_menu WHERE tcat_id=?")->execute([$id]);
    header('Location: category.php');
    exit;
}

$tops = $pdo->query("SELECT * FROM tbl_top_category ORDER BY tcat_name")->fetchAll();
$mids = $pdo->query("SELECT m.*, t.tcat_name FROM tbl_mid_category m JOIN tbl_top_category t ON m.tcat_id=t.tcat_id ORDER BY t.tcat_name, m.mcat_name")->fetchAll();
$ends = $pdo->query("SELECT e.*, m.mcat_name, t.tcat_name FROM tbl_end_category e JOIN tbl_mid_category m ON e.mcat_id=m.mcat_id JOIN tbl_top_category t ON m.tcat_id=t.tcat_id ORDER BY t.tcat_name, m.mcat_name, e.ecat_name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Categories</h4>
</div>

<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

<div class="row g-4">
    <!-- TOP -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Top Level</span>
                <span class="badge bg-primary"><?php echo count($tops); ?></span>
            </div>
            <div class="card-body p-3 border-bottom">
                <form method="post" class="row g-2">
                    <div class="col-12">
                        <input type="text" name="tcat_name" class="form-control form-control-sm" placeholder="New top category" required>
                    </div>
                    <div class="col-8">
                        <div class="form-check mt-1">
                            <input type="checkbox" name="show_on_menu" value="1" class="form-check-input" id="showm" checked>
                            <label class="form-check-label small" for="showm">Show on menu</label>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <button type="submit" name="add_top" class="btn btn-primary btn-sm w-100">Add</button>
                    </div>
                </form>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($tops as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                        <span>
                            <?php echo htmlspecialchars($t['tcat_name']); ?>
                            <?php if ($t['show_on_menu']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success ms-1" style="font-size:0.65rem">Menu</span>
                            <?php endif; ?>
                        </span>
                        <span class="d-flex gap-1">
                            <a href="category.php?toggle_top=<?php echo $t['tcat_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle menu"><?php echo $t['show_on_menu'] ? 'Hide' : 'Show'; ?></a>
                            <a href="category.php?delete_top=<?php echo $t['tcat_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this top category and ALL its children?')"><i class="fas fa-trash"></i></a>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($tops)): ?><li class="list-group-item text-muted small">No top categories</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- MID -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Mid Level</span>
                <span class="badge bg-primary"><?php echo count($mids); ?></span>
            </div>
            <div class="card-body p-3 border-bottom">
                <form method="post" class="row g-2">
                    <div class="col-12">
                        <select name="tcat_id" class="form-select form-select-sm" required>
                            <option value="">Parent top…</option>
                            <?php foreach ($tops as $t): ?>
                                <option value="<?php echo $t['tcat_id']; ?>"><?php echo htmlspecialchars($t['tcat_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <input type="text" name="mcat_name" class="form-control form-control-sm" placeholder="New mid category" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" name="add_mid" class="btn btn-primary btn-sm w-100">Add</button>
                    </div>
                </form>
            </div>
            <ul class="list-group list-group-flush" style="max-height:360px;overflow-y:auto">
                <?php foreach ($mids as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                        <span>
                            <small class="text-muted"><?php echo htmlspecialchars($m['tcat_name']); ?> ›</small>
                            <?php echo htmlspecialchars($m['mcat_name']); ?>
                        </span>
                        <a href="category.php?delete_mid=<?php echo $m['mcat_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this mid category and its end categories?')"><i class="fas fa-trash"></i></a>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($mids)): ?><li class="list-group-item text-muted small">No mid categories</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- END -->
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>End Level</span>
                <span class="badge bg-primary"><?php echo count($ends); ?></span>
            </div>
            <div class="card-body p-3 border-bottom">
                <form method="post" class="row g-2">
                    <div class="col-12">
                        <select name="mcat_id" class="form-select form-select-sm" required>
                            <option value="">Parent mid…</option>
                            <?php foreach ($mids as $m): ?>
                                <option value="<?php echo $m['mcat_id']; ?>"><?php echo htmlspecialchars($m['tcat_name'].' › '.$m['mcat_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <input type="text" name="ecat_name" class="form-control form-control-sm" placeholder="New end category" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" name="add_end" class="btn btn-primary btn-sm w-100">Add</button>
                    </div>
                </form>
            </div>
            <ul class="list-group list-group-flush" style="max-height:360px;overflow-y:auto">
                <?php foreach ($ends as $e): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                        <span>
                            <small class="text-muted"><?php echo htmlspecialchars($e['tcat_name'].' › '.$e['mcat_name']); ?> ›</small>
                            <?php echo htmlspecialchars($e['ecat_name']); ?>
                        </span>
                        <a href="category.php?delete_end=<?php echo $e['ecat_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this end category?')"><i class="fas fa-trash"></i></a>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($ends)): ?><li class="list-group-item text-muted small">No end categories</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
