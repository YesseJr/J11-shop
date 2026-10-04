<?php
require_once('inc_config.php');

// Featured products
$stmt = $pdo->query("SELECT * FROM tbl_product WHERE p_is_active=1 ORDER BY p_is_featured DESC, p_id DESC LIMIT 8");
$products = $stmt->fetchAll();

// Categories
$top_cats = $pdo->query("SELECT * FROM tbl_top_category WHERE show_on_menu=1")->fetchAll();

// Site settings (hero, logo, contact)
$settings = $pdo->query("SELECT * FROM tbl_settings WHERE id=1")->fetch() ?: [];
$hero_image = !empty($settings['hero_image']) ? 'assets/uploads/' . $settings['hero_image'] : '';
$hero_title = $settings['hero_title'] ?? 'Shop smarter. Live better.';
$hero_subtitle = $settings['hero_subtitle'] ?? 'Discover quality fashion, electronics and everyday essentials — curated prices, secure checkout, and delivery you can count on.';
$contact_email = $settings['contact_email'] ?? '<?php echo htmlspecialchars($contact_email); ?>';
$contact_phone = $settings['contact_phone'] ?? '<?php echo htmlspecialchars($contact_phone); ?>';
$footer_about = $settings['footer_about'] ?? '<?php echo htmlspecialchars($footer_about); ?>';

// Stats for trust bar
$total_products = $pdo->query("SELECT COUNT(*) FROM tbl_product WHERE p_is_active=1")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM tbl_customer WHERE cust_status=1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>J11 Online Shopping — Premium Deals, Fast Delivery</title>
    <meta name="description" content="Shop fashion, electronics and more at J11 Online Shopping. Quality products, competitive prices, secure checkout.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5b6cf0;
            --primary-dark: #4553d4;
            --secondary: #7c3aed;
            --dark: #0f172a;
            --muted: #64748b;
            --surface: #f8fafc;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--dark);
            background: #fff;
        }

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            padding: 0.85rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
            color: #fff !important;
        }
        .navbar-brand span { color: #a5b4fc; }
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            font-size: 0.92rem;
            padding: 0.5rem 0.9rem !important;
            transition: color 0.15s;
        }
        .nav-link:hover { color: #fff !important; }
        .btn-nav {
            background: var(--primary);
            color: #fff !important;
            border-radius: 8px;
            padding: 0.45rem 1.1rem !important;
            font-weight: 600;
        }
        .btn-nav:hover { background: var(--primary-dark); color: #fff !important; }
        .cart-badge {
            font-size: 0.7rem;
            position: relative;
            top: -8px;
            left: -4px;
        }

        /* Hero */
        .hero {
            position: relative;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            background-size: cover;
            background-position: center;
            color: #fff;
            padding: 110px 0 100px;
            overflow: hidden;
        }
        .hero.has-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(105deg, rgba(15,23,42,0.88) 0%, rgba(15,23,42,0.55) 60%, rgba(15,23,42,0.4) 100%);
        }
        .hero:not(.has-image)::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 70% 20%, rgba(91,108,240,0.35), transparent),
                radial-gradient(ellipse 50% 40% at 20% 80%, rgba(124,58,237,0.25), transparent);
        }
        .hero .container { position: relative; z-index: 1; }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 50px;
            padding: 0.35rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }
        .hero h1 {
            font-weight: 800;
            font-size: clamp(2.2rem, 5vw, 3.4rem);
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 1.1rem;
        }
        .hero h1 em {
            font-style: normal;
            background: linear-gradient(90deg, #a5b4fc, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero .lead {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            max-width: 520px;
            margin-bottom: 2rem;
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .btn-hero {
            background: #fff;
            color: var(--dark);
            font-weight: 700;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            border: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            color: var(--dark);
        }
        .btn-hero-outline {
            background: transparent;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            border: 1.5px solid rgba(255,255,255,0.3);
        }
        .btn-hero-outline:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-color: rgba(255,255,255,0.5);
        }

        /* Trust bar */
        .trust-bar {
            background: var(--surface);
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 0;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            justify-content: center;
        }
        .trust-item i {
            width: 40px; height: 40px;
            border-radius: 10px;
            background: #eef2ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .trust-item strong { display: block; font-size: 0.95rem; }
        .trust-item span { font-size: 0.8rem; color: var(--muted); }

        /* Categories */
        .section-title {
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }
        .section-sub { color: var(--muted); font-size: 0.95rem; margin-bottom: 2rem; }
        .cat-card {
            background: var(--surface);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            padding: 1.5rem 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s;
            display: block;
            height: 100%;
        }
        .cat-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(91,108,240,0.12);
            transform: translateY(-3px);
            color: var(--dark);
        }
        .cat-card .cat-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin: 0 auto 0.85rem;
        }
        .cat-card h6 { font-weight: 700; margin: 0; font-size: 0.95rem; }

        /* Products */
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            overflow: hidden;
            background: #fff;
            transition: all 0.22s;
            height: 100%;
        }
        .product-card:hover {
            border-color: transparent;
            box-shadow: 0 16px 40px rgba(15,23,42,0.1);
            transform: translateY(-5px);
        }
        .product-card .img-wrap {
            position: relative;
            background: var(--surface);
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }
        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .product-card:hover img { transform: scale(1.04); }
        .badge-featured {
            position: absolute;
            top: 12px; left: 12px;
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .product-card .card-body { padding: 1.1rem 1.15rem 1.25rem; }
        .product-card .card-title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.35rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .card-text {
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }
        .price {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.15rem;
        }
        .old-price {
            text-decoration: line-through;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .btn-add {
            background: var(--dark);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 0;
            transition: background 0.15s;
        }
        .btn-add:hover { background: var(--primary); color: #fff; }
        .btn-view {
            border: 1.5px solid #e2e8f0;
            color: var(--dark);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 0;
        }
        .btn-view:hover { border-color: var(--primary); color: var(--primary); background: #eef2ff; }

        /* CTA band */
        .cta-band {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
        }
        .cta-band h2 { font-weight: 800; letter-spacing: -0.02em; }
        .cta-band p { opacity: 0.85; max-width: 480px; margin: 0.75rem auto 1.5rem; }

        /* Footer */
        footer {
            background: var(--dark);
            color: #94a3b8;
            padding: 3.5rem 0 1.5rem;
            margin-top: 0;
        }
        footer h5 {
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        footer a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.15s;
        }
        footer a:hover { color: #fff; }
        footer .footer-brand {
            color: #fff;
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 2.5rem;
            padding-top: 1.25rem;
            font-size: 0.85rem;
        }

        /* Utility */
        .section-pad { padding: 4rem 0; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-shopping-bag me-1"></i> J11<span>Shop</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <i class="fas fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu border-0 shadow">
                        <?php foreach ($top_cats as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="products.php?cat=<?php echo $cat['tcat_id']; ?>">
                                    <?php echo htmlspecialchars($cat['tcat_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="products.php">All Products</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="products.php">Shop</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        $cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
                        if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['customer'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="customer-dashboard.php">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer']['cust_name']); ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn-nav ms-lg-2" href="register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero<?php echo $hero_image ? ' has-image' : ''; ?>"
         <?php if ($hero_image): ?>style="background-image: url('<?php echo htmlspecialchars($hero_image); ?>')"<?php endif; ?>>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-badge">
                    <i class="fas fa-bolt text-warning"></i> New season collection is live
                </div>
                <h1><?php
                    // Allow simple line break via |
                    $parts = explode('|', $hero_title, 2);
                    echo htmlspecialchars(trim($parts[0]));
                    if (isset($parts[1])) {
                        echo '<br><em>' . htmlspecialchars(trim($parts[1])) . '</em>';
                    }
                ?></h1>
                <p class="lead">
                    <?php echo htmlspecialchars($hero_subtitle); ?>
                </p>
                <div class="hero-actions">
                    <a href="products.php" class="btn btn-hero">
                        Browse Collection <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    <a href="products.php?cat=4" class="btn btn-hero-outline">Electronics</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div style="font-size:8rem;opacity:0.15;line-height:1">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust bar -->
<div class="trust-bar">
    <div class="container">
        <div class="row g-3 text-center text-md-start">
            <div class="col-6 col-md-3">
                <div class="trust-item">
                    <i class="fas fa-truck"></i>
                    <div>
                        <strong>Free Shipping</strong>
                        <span>On orders over $50</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-item">
                    <i class="fas fa-shield-halved"></i>
                    <div>
                        <strong>Secure Payment</strong>
                        <span>100% protected</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-item">
                    <i class="fas fa-rotate-left"></i>
                    <div>
                        <strong>Easy Returns</strong>
                        <span>30-day policy</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-item">
                    <i class="fas fa-headset"></i>
                    <div>
                        <strong>24/7 Support</strong>
                        <span>We're here to help</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories -->
<section class="section-pad">
    <div class="container">
        <div class="text-center mb-2">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-sub">Find exactly what you're looking for</p>
        </div>
        <div class="row g-3 justify-content-center">
            <?php
            $cat_icons = ['fa-mars', 'fa-venus', 'fa-child', 'fa-laptop'];
            $i = 0;
            foreach ($top_cats as $cat):
                $icon = $cat_icons[$i % count($cat_icons)];
                $i++;
            ?>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="products.php?cat=<?php echo $cat['tcat_id']; ?>" class="cat-card">
                        <div class="cat-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                        <h6><?php echo htmlspecialchars($cat['tcat_name']); ?></h6>
                    </a>
                </div>
            <?php endforeach; ?>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="products.php" class="cat-card">
                    <div class="cat-icon"><i class="fas fa-th"></i></div>
                    <h6>View All</h6>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="section-pad" style="background: var(--surface);">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
            <div>
                <h2 class="section-title mb-1">Featured Products</h2>
                <p class="section-sub mb-0">Hand-picked items just for you</p>
            </div>
            <a href="products.php" class="btn btn-outline-dark btn-sm px-3">
                View all <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No products yet. Check back soon or visit the Admin panel.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <div class="img-wrap">
                                <?php if ($p['p_is_featured']): ?>
                                    <span class="badge-featured">Featured</span>
                                <?php endif; ?>
                                <img src="<?php echo $p['p_featured_photo'] ? 'assets/uploads/'.$p['p_featured_photo'] : 'https://placehold.co/400x400/e2e8f0/64748b?text='.urlencode(substr($p['p_name'],0,12)); ?>"
                                     alt="<?php echo htmlspecialchars($p['p_name']); ?>"
                                     onerror="this.src='https://placehold.co/400x400/e2e8f0/64748b?text=Product'">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?php echo htmlspecialchars($p['p_name']); ?></h6>
                                <?php if (!empty($p['p_short_description'])): ?>
                                    <p class="card-text"><?php echo htmlspecialchars(mb_substr($p['p_short_description'], 0, 55)); ?>…</p>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <span class="price">$<?php echo number_format($p['p_current_price'], 2); ?></span>
                                    <?php if ($p['p_old_price'] > $p['p_current_price']): ?>
                                        <span class="old-price ms-1">$<?php echo number_format($p['p_old_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto d-grid gap-2">
                                    <a href="product.php?id=<?php echo $p['p_id']; ?>" class="btn btn-view btn-sm">View Details</a>
                                    <form action="cart-add.php" method="post">
                                        <input type="hidden" name="p_id" value="<?php echo $p['p_id']; ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn btn-add btn-sm w-100">
                                            <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section-pad">
    <div class="container">
        <div class="cta-band">
            <h2>Ready to start shopping?</h2>
            <p>Join thousands of happy customers. Create a free account and get exclusive deals.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="register.php" class="btn btn-hero">Create Account</a>
                <a href="products.php" class="btn btn-hero-outline">Browse Products</a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand"><i class="fas fa-shopping-bag me-1"></i> J11Shop</div>
                <p class="mb-3" style="font-size:0.9rem;max-width:280px">
                    <?php echo htmlspecialchars($footer_about); ?>
                </p>
            </div>
            <div class="col-6 col-md-2">
                <h5>Shop</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="products.php">All Products</a></li>
                    <?php foreach (array_slice($top_cats, 0, 3) as $cat): ?>
                        <li><a href="products.php?cat=<?php echo $cat['tcat_id']; ?>"><?php echo htmlspecialchars($cat['tcat_name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <h5>Account</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="Admin/login.php">Admin</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact</h5>
                <ul class="list-unstyled d-flex flex-column gap-2" style="font-size:0.9rem">
                    <li><i class="fas fa-envelope me-2 opacity-50"></i> <?php echo htmlspecialchars($contact_email); ?></li>
                    <li><i class="fas fa-phone me-2 opacity-50"></i> <?php echo htmlspecialchars($contact_phone); ?></li>
                    <li><i class="fas fa-clock me-2 opacity-50"></i> Mon–Fri, 9am–6pm</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?php echo date('Y'); ?> J11 Online Shopping. All rights reserved.</span>
            <span><?php echo (int)$total_products; ?> products available</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
