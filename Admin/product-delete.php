<?php
require_once('inc/config.php');
session_start();
if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT p_featured_photo FROM tbl_product WHERE p_id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['p_featured_photo'])) {
        $path = __DIR__ . '/../assets/uploads/' . $row['p_featured_photo'];
        if (file_exists($path)) {
            @unlink($path);
        }
    }
    $pdo->prepare("DELETE FROM tbl_product WHERE p_id=?")->execute([$id]);
}
header('Location: product.php');
exit;
