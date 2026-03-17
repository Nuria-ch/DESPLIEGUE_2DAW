<?php
require 'auth.php';
include 'db.php';

// Stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_value    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(price * quantity) as total FROM products"))['total'];
$low_stock      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE quantity <= 5"))['count'];
$categories     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT category) as count FROM products"))['count'];

// Low stock products
$low_stock_products = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= 5 ORDER BY quantity ASC");

// Recent products
$recent_products = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Top Bar -->
    <div class="topbar">
        <h1>Dashboard</h1>
        <div class="topbar-right">
            <a href="add.php" class="btn btn-primary">➕ Add Product</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div class="stat-info">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <h3>$<?php echo number_format($total_value ?? 0, 2); ?></h3>
                <p>Total Value</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-info">
                <h3><?php echo $low_stock; ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">🏷️</div>
            <div class="stat-info">
                <h3><?php echo $categories; ?></h3>
                <p>Categories</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">

        <!-- Low Stock Alert -->
        <div class="card">
            <div class="card-header">
                <h2>⚠️ Low Stock Alert</h2>
                <a href="products.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <?php if (mysqli_num_rows($low_stock_products) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($low_stock_products)): ?>
                <div class="low-stock-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <div style="font-size:12px; color:#888;"><?php echo htmlspecialchars($item['category']); ?></div>
                    </div>
                    <span class="badge badge-danger"><?php echo $item['quantity']; ?> left</span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#888; text-align:center; padding:20px;">✅ All products are well stocked!</p>
            <?php endif; ?>
        </div>

        <!-- Recent Products -->
        <div class="card">
            <div class="card-header">
                <h2>🕐 Recently Added</h2>
                <a href="products.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <?php if (mysqli_num_rows($recent_products) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($recent_products)): ?>
                <div class="low-stock-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <div style="font-size:12px; color:#888;"><?php echo htmlspecialchars($item['category']); ?></div>
                    </div>
                    <span class="badge badge-success">$<?php echo number_format($item['price'], 2); ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#888; text-align:center; padding:20px;">No products yet. Add one!</p>
            <?php endif; ?>
        </div>

    </div>

</div>

</body>
</html>