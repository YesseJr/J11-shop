<?php
ob_start();
session_start();
include("inc/config.php");
include("inc/functions.php");
include("inc/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$error_message = '';
$success_message = '';

if (!isset($_SESSION['user'])) {
    header('location: login.php');
    exit;
}

$cur_page = basename($_SERVER['SCRIPT_NAME']);
$admin_name = $_SESSION['user']['full_name'] ?? 'Admin';
$admin_photo = !empty($_SESSION['user']['photo'])
    ? '../assets/uploads/' . $_SESSION['user']['photo']
    : 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=667eea&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - J11 Online Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #667eea;
            --primary-dark: #5a6fd6;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: #2a2a3c;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f6f9; min-height: 100vh; }
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .2s;
        }
        .admin-sidebar .brand {
            padding: 1.25rem 1.5rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.15rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .admin-sidebar .brand i { color: var(--primary); }
        .sidebar-nav { padding: 1rem 0; list-style: none; margin: 0; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .7rem 1.5rem;
            color: #a2a3b7;
            text-decoration: none;
            font-size: .92rem;
            transition: all .15s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-nav a.active { border-left: 3px solid var(--primary); }
        .sidebar-nav a i { width: 20px; text-align: center; font-size: .95rem; }
        .sidebar-nav .nav-section {
            padding: 1rem 1.5rem .4rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #5e6278;
            font-weight: 600;
        }
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-content { padding: 1.5rem; }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            transition: transform .15s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .stat-card h3 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        .table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .badge-status-active { background: #d1e7dd; color: #0f5132; }
        .badge-status-inactive { background: #f8d7da; color: #842029; }
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-main { margin-left: 0; }
        }
    </style>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
    <div class="brand">
        <i class="fas fa-shopping-bag"></i> J11 Admin
    </div>
    <ul class="sidebar-nav">
        <li class="nav-section">Main</li>
        <li><a href="index.php" class="<?php echo $cur_page=='index.php'?'active':''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

        <li class="nav-section">Catalog</li>
        <li><a href="product.php" class="<?php echo in_array($cur_page,['product.php','product-add.php','product-edit.php'])?'active':''; ?>"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="category.php" class="<?php echo $cur_page=='category.php'?'active':''; ?>"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="order.php" class="<?php echo $cur_page=='order.php'?'active':''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>

        <li class="nav-section">Configuration</li>
        <li><a href="settings.php" class="<?php echo $cur_page=='settings.php'?'active':''; ?>"><i class="fas fa-cog"></i> Site Settings</a></li>

        <li class="nav-section">Account</li>
        <li><a href="profile.php" class="<?php echo $cur_page=='profile.php'?'active':''; ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Store</a></li>
    </ul>
</aside>

<div class="admin-main">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <span class="text-muted small d-none d-md-inline">Administration Panel</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <img src="<?php echo htmlspecialchars($admin_photo); ?>" class="user-avatar" alt="Admin">
            <span class="fw-medium"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
    </div>
    <div class="admin-content">
