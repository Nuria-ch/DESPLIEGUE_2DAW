<?php
require 'auth.php';
include 'db.php';

$error = "";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$id = (int) $_GET['id'];

// Fetch product
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get categories for dropdown
$categories_result = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name     = trim($_POST['name']);
    $category = trim($_POST['category']);
    $quantity = trim($_POST['quantity']);
    $price    = trim($_POST['price']);

    if ($category == '__custom__') {
        $category = trim($_POST['custom_category']);
    }

    if (empty($name) || empty($category) || empty($quantity) || empty($price)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = "Quantity must be a valid positive number.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a valid positive number.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, quantity=?, price=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdii", $name, $category, $price, $quantity, $id);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: products.php?success=updated');
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Edit Product</h1>
        <div class="topbar-right">
            <a href="products.php" class="btn btn-secondary">← Back to Products</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>Edit Product Details</h2>
        </div>

        <form method="POST" action="edit.php?id=<?php echo $id; ?>">
            <div class="form-grid">

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="categorySelect" onchange="toggleCustomCategory(this)">
                        <option value="">Select Category</option>
                        <?php
                        // Reset categories result
                        mysqli_data_seek($categories_result, 0);
                        while ($cat = mysqli_fetch_assoc($categories_result)):
                        ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                <?php echo $product['category'] == $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endwhile; ?>
                        <option value="__custom__">+ Add New Category</option>
                    </select>
                </div>

                <div class="form-group" id="customCategoryGroup" style="display:none;">
                    <label>New Category Name</label>
                    <input type="text" name="custom_category" placeholder="Enter new category name">
                </div>

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars($product['quantity']); ?>">
                </div>

                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Update Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCustomCategory(select) {
    const customGroup = document.getElementById('customCategoryGroup');
    customGroup.style.display = select.value === '__custom__' ? 'block' : 'none';
}
</script>

</body>
</html>