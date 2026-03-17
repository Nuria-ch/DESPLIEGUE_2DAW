<?php
require 'auth.php';
include 'db.php';

$error   = "";
$success = "";

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        // Check if exists
        $check = mysqli_prepare($conn, "SELECT id FROM products WHERE category = ? LIMIT 1");
        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Category already exists!";
        } else {
            // Insert a placeholder product with this category? No —
            // We store categories separately
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $name);
            if (mysqli_stmt_execute($stmt)) {
                $action = "Added new category: $name";
                mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
                $success = "Category added successfully!";
            } else {
                $error = "Something went wrong.";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Check if category has products
    $cat_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM categories WHERE id = $id"))['name'];
    $has_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category = '$cat_name'"))['count'];

    if ($has_products > 0) {
        $error = "Cannot delete — this category has $has_products products!";
    } else {
        mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
        $action = "Deleted category: $cat_name";
        mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
        header('Location: categories.php?success=deleted');
        exit();
    }
}

// Handle Edit
if (isset($_POST['edit_category'])) {
    $id      = (int) $_POST['edit_id'];
    $name    = trim($_POST['edit_name']);

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $id);
        if (mysqli_stmt_execute($stmt)) {
            $action = "Updated category to: $name";
            mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
            $success = "Category updated successfully!";
        }
    }
}

// Get all categories with product count
$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(p.id) as total_products
    FROM categories c
    LEFT JOIN products p ON p.category = c.name
    GROUP BY c.id
    ORDER BY c.name ASC
");

$total_categories = mysqli_num_rows($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>🏷️ Categories</h1>
        <div class="topbar-right">
            <span style="color:#888; font-size:14px;"><?php echo $total_categories; ?> categories total</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Category deleted successfully!</div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px;">

        <!-- Add Category -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add Category</h2>
            </div>
            <form method="POST" action="categories.php">
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Category Name</label>
                    <input type="text" name="name" placeholder="e.g. Electronics" required>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary" style="width:100%;">
                    ➕ Add Category
                </button>
            </form>
        </div>

        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h2>📋 All Categories</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category Name</th>
                        <th>Total Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($categories) > 0): ?>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <span class="badge badge-warning">
                                    🏷️ <?php echo htmlspecialchars($cat['name']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-success">
                                    <?php echo $cat['total_products']; ?> products
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm"
                                    onclick="openEdit(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')">
                                    ✏️ Edit
                                </button>
                                <a href="categories.php?delete=<?php echo $cat['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this category?')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:40px; color:#888;">
                                No categories yet. Add your first one!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:400px; max-width:90%;">
        <h2 style="color:#1a3c6e; margin-bottom:20px;">✏️ Edit Category</h2>
        <form method="POST" action="categories.php">
            <input type="hidden" name="edit_id" id="editId">
            <div class="form-group" style="margin-bottom:15px;">
                <label>Category Name</label>
                <input type="text" name="edit_name" id="editName" required>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="edit_category" class="btn btn-primary">💾 Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name) {
    document.getElementById('editId').value   = id;
    document.getElementById('editName').value = name;
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
}

function closeEdit() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>