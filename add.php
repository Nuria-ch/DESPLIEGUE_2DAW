<?php
require 'auth.php';
include 'db.php';

$error = "";

$categories_result = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
$suppliers_result  = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name ASC");

function generateSKU($name, $category) {
    $name_part     = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    $category_part = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $category), 0, 3));
    $random        = rand(100, 999);
    return $name_part . '-' . $category_part . '-' . $random;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name        = trim($_POST['name']);
    $category    = trim($_POST['category']);
    $quantity    = trim($_POST['quantity']);
    $price       = trim($_POST['price']);
    $sku         = trim($_POST['sku']);
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

    if ($category == '__custom__') {
        $category = trim($_POST['custom_category']);
    }

    if (empty($sku)) {
        $sku = generateSKU($name, $category);
    }

    // Handle image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed     = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext         = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $max_size    = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, GIF, WEBP images are allowed.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "Image size must be less than 2MB.";
        } else {
            $image    = uniqid() . '_' . time() . '.' . $ext;
            $upload   = move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image);
            if (!$upload) {
                $error = "Failed to upload image.";
                $image = null;
            }
        }
    }

    if (empty($error)) {
        if (empty($name) || empty($category) || empty($quantity) || empty($price)) {
            $error = "All fields are required.";
        } elseif (!is_numeric($quantity) || $quantity < 0) {
            $error = "Quantity must be a valid positive number.";
        } elseif (!is_numeric($price) || $price < 0) {
            $error = "Price must be a valid positive number.";
        } else {
            $check = mysqli_prepare($conn, "SELECT id FROM products WHERE sku = ?");
            mysqli_stmt_bind_param($check, "s", $sku);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "SKU already exists. Please use a different SKU.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO products (name, sku, category, supplier_id, quantity, price, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssidds", $name, $sku, $category, $supplier_id, $quantity, $price, $image);

                if (mysqli_stmt_execute($stmt)) {
                    $action = "Added new product: $name (SKU: $sku)";
                    mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
                    header('Location: products.php?success=added');
                    exit();
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Add New Product</h1>
        <div class="topbar-right">
            <a href="products.php" class="btn btn-secondary">← Back to Products</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>Product Details</h2>
            <span style="color:#888; font-size:13px;">SKU will be auto-generated if left empty</span>
        </div>

        <form method="POST" action="add.php" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="Enter product name"
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>SKU <span style="color:#888; font-weight:400;">(auto-generated if empty)</span></label>
                    <input type="text" name="sku" placeholder="e.g. MAC-ELE-001"
                        value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category" id="categorySelect" onchange="toggleCustomCategory(this)">
                        <option value="">Select Category</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
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
                    <label>Supplier <span style="color:#888; font-weight:400;">(optional)</span></label>
                    <select name="supplier_id">
                        <option value="">No Supplier</option>
                        <?php while ($sup = mysqli_fetch_assoc($suppliers_result)): ?>
                            <option value="<?php echo $sup['id']; ?>">
                                <?php echo htmlspecialchars($sup['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" placeholder="Enter quantity" min="0"
                        value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" placeholder="Enter price" min="0" step="0.01"
                        value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Product Image <span style="color:#888; font-weight:400;">(optional, max 2MB)</span></label>
                    <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" style="margin-top:10px; display:none;">
                        <img id="preview" src="" alt="Preview" style="max-width:150px; max-height:150px; border-radius:8px; border:1px solid #e0e2e5;">
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">➕ Add Product</button>
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

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img     = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>