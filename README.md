# J11 Online Shopping

A fully working PHP + MySQL e-commerce website.

## Features

### Storefront
- Modern responsive homepage with featured products
- Product listing with category filter & search
- Product detail page
- Shopping cart (session-based)
- Customer registration & login
- Checkout (Cash on Delivery)
- Order placement with stock reduction

### Admin Panel
- Secure login (password_hash)
- Dashboard with stats (products, orders, customers...)
- Product management (list / add / delete)
- Order management
- Multi-level categories already seeded

## Quick Start

### 1. Requirements
- PHP 7.4+ (tested on 8.3)
- MySQL / MariaDB
- Apache or PHP built-in server

### 2. Database Setup

Option A – Automatic (recommended):
```
Open in browser: http://localhost/J11-Online-Shopping/setup.php
```
Create the first admin account. Tables & sample products are created automatically.

Option B – Manual:
```bash
mysql -u root -p < database.sql
```
Then open `setup.php` to create the admin user.

### 3. Run the site

Using PHP built-in server:
```bash
cd J11-Online-Shopping
php -S localhost:8080
```

Then open:
- Storefront: http://localhost:8080/
- Admin:     http://localhost:8080/Admin/login.php
- Setup:     http://localhost:8080/setup.php

### 4. Default Admin

Created via setup.php (no default password hardcoded for security).

## Project Structure

```
J11-Online-Shopping/
├── index.php              # Homepage
├── products.php           # Product listing
├── product.php            # Product detail
├── cart.php / cart-add.php
├── checkout.php
├── login.php / register.php / logout.php
├── setup.php              # First-time admin registration
├── database.sql           # Full schema + sample data
├── inc_config.php         # Frontend DB config
├── Admin/
│   ├── login.php
│   ├── index.php          # Dashboard
│   ├── product.php        # Product list
│   ├── product-add.php
│   ├── order.php
│   └── inc/config.php     # Admin DB config
```

## Notes

- After running setup.php, you should delete or protect it.
- Product images are placeholders (upload folder: assets/uploads/).
- The original ZIP mixed a Hospital system with an e-commerce system. This version is a clean, working Online Shopping site.
