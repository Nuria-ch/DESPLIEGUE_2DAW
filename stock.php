<?php
require 'auth.php';
include 'db.php';

$error   = "";
$success = "";

// Handle Stock In / Stock Out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $product_id = (int) $_POST['product_id'];
    $type       = $_POST['type'];
    $quantity   = (int) $_POST['quantity'];
    $note       = trim($_POST['note']);

    if (empty($product_id) || empty($quantity) || $quantity <= 0) {
        $error = "Please select a product and enter a valid quantity.";
    } elseif (!in_array($type, ['in', 'out'])) {
        $error = "Invalid stock movement type.";
    } else {
        // Check if enough stock for stock out
        if ($type == 'out') {
            $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT quantity FROM products WHERE id = $product_id"));
            if ($current['quantity'] < $quantity) {
                $error = "Not enough stock! Current stock is " . $current['quantity'] . " units.";
            }
        }

        if (empty($error)) {
            // Update product quantity
            if ($type == 'in') {
                $sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            } else {
                $sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
            }

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $quantity, $product_id);

            if (mysqli_stmt_execute($stmt)) {
                // Record stock movement
                $move_stmt = mysqli_prepare($conn, "INSERT INTO stock_movements (product_id, type, quantity, note) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($move_stmt, "isis", $product_id, $type, $quantity, $note);
                mysqli_stmt_execute($move_stmt);

                // Log activity
                $product_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM products WHERE id = $product_id"))['name'];
                $action = "Stock " . strtoupper($type) . ": $quantity units for $product_name";
                mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");

                $success = "Stock updated successfully!";
            }
        }
    }
}

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");

// Get stock movements history
$movements = mysqli_query($conn, "
    SELECT sm.*, p.name as product_name, p.sku
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    ORDER BY sm.created_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Stock Management</h1>
        <div class="topbar-right">
            <a href="products.php" class="btn btn-secondary">← Back to Products</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px;">

        <!-- Stock In Form -->
        <div class="card">
            <div class="card-header">
                <h2>📦 Stock In</h2>
                <span class="badge badge-success">Receiving Stock</span>
            </div>
            <form method="POST" action="stock.php">
                <input type="hidden" name="type" value="in">
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Select Product</label>
                    <select name="product_id" required>
                        <option value="">Choose product...</option>
                        <?php
                        $products_copy = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
                        while ($p = mysqli_fetch_assoc($products_copy)):
                        ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (Current: <?php echo $p['quantity']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Quantity to Add</label>
                    <input type="number" name="quantity" min="1" placeholder="Enter quantity" required>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Note <span style="color:#888; font-weight:400;">(optional)</span></label>
                    <input type="text" name="note" placeholder="e.g. Received from supplier">
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;">📦 Add Stock</button>
            </form>
        </div>

        <!-- Stock Out Form -->
        <div class="card">
            <div class="card-header">
                <h2>📤 Stock Out</h2>
                <span class="badge badge-danger">Removing Stock</span>
            </div>
            <form method="POST" action="stock.php">
                <input type="hidden" name="type" value="out">
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Select Product</label>
                    <select name="product_id" required>
                        <option value="">Choose product...</option>
                        <?php
                        $products_copy2 = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
                        while ($p = mysqli_fetch_assoc($products_copy2)):
                        ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (Current: <?php echo $p['quantity']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Quantity to Remove</label>
                    <input type="number" name="quantity" min="1" placeholder="Enter quantity" required>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Note <span style="color:#888; font-weight:400;">(optional)</span></label>
                    <input type="text" name="note" placeholder="e.g. Sold to customer">
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%;">📤 Remove Stock</button>
            </form>
        </div>

    </div>

    <!-- Stock Movement History -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Stock Movement History</h2>
            <span style="color:#888; font-size:13px;">Last 20 movements</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Note</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($movements) > 0): ?>
                    <?php while ($mov = mysqli_fetch_assoc($movements)): ?>
                    <tr>
                        <td><?php echo $mov['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($mov['product_name']); ?></strong></td>
                        <td><code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:12px;"><?php echo htmlspecialchars($mov['sku']); ?></code></td>
                        <td>
                            <?php if ($mov['type'] == 'in'): ?>
                                <span class="badge badge-success">📦 Stock In</span>
                            <?php else: ?>
                                <span class="badge badge-danger">📤 Stock Out</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $mov['quantity']; ?> units</strong></td>
                        <td><?php echo htmlspecialchars($mov['note'] ?? '—'); ?></td>
                        <td><?php echo $mov['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#888;">
                            No stock movements yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>