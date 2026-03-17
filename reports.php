<?php
require 'auth.php';
include 'db.php';

// Total stats
$total_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_value     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(price * quantity) as total FROM products"))['total'];
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE quantity <= 5"))['count'];
$total_movements = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_movements"))['count'];

// Stock value by category
$category_report = mysqli_query($conn, "
    SELECT 
        category,
        COUNT(*) as total_products,
        SUM(quantity) as total_quantity,
        SUM(price * quantity) as total_value,
        AVG(price) as avg_price
    FROM products
    GROUP BY category
    ORDER BY total_value DESC
");

// Most stocked products
$most_stocked = mysqli_query($conn, "SELECT * FROM products ORDER BY quantity DESC LIMIT 5");

// Least stocked products
$least_stocked = mysqli_query($conn, "SELECT * FROM products ORDER BY quantity ASC LIMIT 5");

// Most valuable products
$most_valuable = mysqli_query($conn, "SELECT *, (price * quantity) as total_value FROM products ORDER BY total_value DESC LIMIT 5");

// Stock movement summary
$movement_summary = mysqli_query($conn, "
    SELECT 
        type,
        COUNT(*) as total_movements,
        SUM(quantity) as total_quantity
    FROM stock_movements
    GROUP BY type
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Reports</h1>
        <div class="topbar-right">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
        </div>
    </div>

    <!-- Summary Stats -->
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
                <p>Total Inventory Value</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-info">
                <h3><?php echo $low_stock_count; ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">🔄</div>
            <div class="stat-info">
                <h3><?php echo $total_movements; ?></h3>
                <p>Stock Movements</p>
            </div>
        </div>
    </div>

    <!-- Category Report -->
    <div class="card">
        <div class="card-header">
            <h2>📊 Inventory by Category</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Products</th>
                    <th>Total Quantity</th>
                    <th>Avg Price</th>
                    <th>Total Value</th>
                    <th>Value Share</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cat = mysqli_fetch_assoc($category_report)): ?>
                <tr>
                    <td><span class="badge badge-warning"><?php echo htmlspecialchars($cat['category']); ?></span></td>
                    <td><?php echo $cat['total_products']; ?> products</td>
                    <td><?php echo $cat['total_quantity']; ?> units</td>
                    <td>$<?php echo number_format($cat['avg_price'], 2); ?></td>
                    <td><strong>$<?php echo number_format($cat['total_value'], 2); ?></strong></td>
                    <td>
                        <?php $share = $total_value > 0 ? ($cat['total_value'] / $total_value) * 100 : 0; ?>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; background:#f0f2f5; border-radius:10px; height:8px;">
                                <div style="width:<?php echo $share; ?>%; background:#1a3c6e; border-radius:10px; height:8px;"></div>
                            </div>
                            <span style="font-size:12px; color:#888;"><?php echo number_format($share, 1); ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:25px;">

        <!-- Most Valuable Products -->
        <div class="card">
            <div class="card-header">
                <h2>💰 Most Valuable Products</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($most_valuable)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><?php echo $p['quantity']; ?></td>
                        <td><span class="badge badge-success">$<?php echo number_format($p['total_value'], 2); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Stock Movement Summary -->
        <div class="card">
            <div class="card-header">
                <h2>🔄 Stock Movement Summary</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Total Movements</th>
                        <th>Total Units</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($movement_summary, 0);
                    while ($mov = mysqli_fetch_assoc($movement_summary)):
                    ?>
                    <tr>
                        <td>
                            <?php if ($mov['type'] == 'in'): ?>
                                <span class="badge badge-success">📦 Stock In</span>
                            <?php else: ?>
                                <span class="badge badge-danger">📤 Stock Out</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $mov['total_movements']; ?> times</td>
                        <td><strong><?php echo $mov['total_quantity']; ?> units</strong></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total_movements == 0): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#888; padding:20px;">No movements yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Most Stocked -->
        <div class="card">
            <div class="card-header">
                <h2>📈 Most Stocked Products</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($most_stocked)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($p['category']); ?></span></td>
                        <td><span class="badge badge-success"><?php echo $p['quantity']; ?> units</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Least Stocked -->
        <div class="card">
            <div class="card-header">
                <h2>📉 Least Stocked Products</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($least_stocked)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($p['category']); ?></span></td>
                        <td>
                            <?php if ($p['quantity'] <= 5): ?>
                                <span class="badge badge-danger">⚠️ <?php echo $p['quantity']; ?> units</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><?php echo $p['quantity']; ?> units</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>