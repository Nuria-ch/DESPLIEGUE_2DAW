<?php
require 'auth.php';
include 'db.php';

// Pagination
$per_page    = 10;
$page        = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $per_page;

// Search & Filter
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$where = "WHERE 1=1";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where .= " AND (name LIKE '%$search_safe%' OR sku LIKE '%$search_safe%')";
}
if (!empty($category)) {
    $category_safe = mysqli_real_escape_string($conn, $category);
    $where .= " AND category = '$category_safe'";
}

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_result = mysqli_query($conn, "SELECT * FROM products $where ORDER BY created_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'SKU', 'Name', 'Category', 'Quantity', 'Price', 'Total Value', 'Date Added']);
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($output, [
            $row['id'], $row['sku'], $row['name'], $row['category'],
            $row['quantity'], $row['price'],
            $row['price'] * $row['quantity'], $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

// Bulk Delete
if (isset($_POST['bulk_delete']) && !empty($_POST['selected_products'])) {
    $ids     = array_map('intval', $_POST['selected_products']);
    $ids_str = implode(',', $ids);
    $action  = "Bulk deleted " . count($ids) . " products";
    mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
    mysqli_query($conn, "DELETE FROM products WHERE id IN ($ids_str)");
    header('Location: products.php?success=deleted');
    exit();
}

// Total count for pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products $where");
$total_rows   = mysqli_fetch_assoc($total_result)['count'];
$total_pages  = ceil($total_rows / $per_page);

// Get products
$result = mysqli_query($conn, "SELECT * FROM products $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

// Get categories for filter
$categories_result = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Products</h1>
        <div class="topbar-right">
            <a href="products.php?export=csv&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" class="btn btn-success">📥 Export CSV</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
            <a href="add.php" class="btn btn-primary">➕ Add Product</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Product has been <?php echo $_GET['success']; ?> successfully!</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>All Products</h2>
            <span style="color:#888; font-size:14px;"><?php echo $total_rows; ?> products found</span>
        </div>

        <!-- Search & Filter -->
        <div class="filters">
            <form method="GET" action="products.php" style="display:flex; gap:15px; flex:1; flex-wrap:wrap;">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name or SKU..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="category" class="filter-select">
                    <option value="">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                            <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="products.php" class="btn btn-secondary">✖ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Delete Form -->
        <form method="POST" action="products.php" id="bulkForm">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    <label for="selectAll" style="font-size:13px; color:#666; text-transform:none; letter-spacing:0;">Select All</label>
                </div>
                <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm"
                    onclick="return confirmBulkDelete()">🗑️ Delete Selected</button>
            </div>

            <!-- Table -->
            <table id="productsTable">
                <thead>
                    <tr>
                        <th width="40"></th>
                        <th>#</th>
                        <th>Image</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_products[]" value="<?php echo $row['id']; ?>" class="product-checkbox">
                            </td>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php if (!empty($row['image']) && file_exists('uploads/' . $row['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                        alt="Product"
                                        style="width:45px; height:45px; object-fit:cover; border-radius:8px; border:1px solid #e0e2e5;">
                                <?php else: ?>
                                    <div style="width:45px; height:45px; background:#f0f2f5; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:20px;">📦</div>
                                <?php endif; ?>
                            </td>
                            <td><code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:12px;"><?php echo htmlspecialchars($row['sku'] ?? 'N/A'); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                            <td>$<?php echo number_format($row['price'] * $row['quantity'], 2); ?></td>
                            <td>
                                <?php if ($row['quantity'] <= 5): ?>
                                    <span class="badge badge-danger">⚠️ Low Stock</span>
                                <?php elseif ($row['quantity'] <= 20): ?>
                                    <span class="badge badge-warning">⚡ Medium</span>
                                <?php else: ?>
                                    <span class="badge badge-success">✅ In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($row['name']); ?>?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align:center; padding:40px; color:#888;">
                                No products found. <a href="add.php" style="color:#1a3c6e;">Add your first product!</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:25px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" class="btn btn-secondary btn-sm">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"
                    class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" class="btn btn-secondary btn-sm">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) {
        alert('Please select at least one product to delete.');
        return false;
    }
    return confirm('Are you sure you want to delete ' + checked.length + ' selected products?');
}
</script>

</body>
</html>