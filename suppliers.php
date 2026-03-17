<?php
require 'auth.php';
include 'db.php';

$error   = "";
$success = "";

// Handle Add Supplier
if (isset($_POST['add_supplier'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = "Supplier name is required.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO suppliers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $address);

        if (mysqli_stmt_execute($stmt)) {
            $action = "Added new supplier: $name";
            mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
            $success = "Supplier added successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Handle Delete Supplier
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM suppliers WHERE id = $id");
    header('Location: suppliers.php?success=deleted');
    exit();
}

// Get all suppliers
$suppliers = mysqli_query($conn, "
    SELECT s.*, COUNT(p.id) as total_products 
    FROM suppliers s 
    LEFT JOIN products p ON s.id = p.supplier_id 
    GROUP BY s.id 
    ORDER BY s.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Supplier Management</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Supplier deleted successfully!</div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px;">

        <!-- Add Supplier Form -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add Supplier</h2>
            </div>
            <form method="POST" action="suppliers.php">
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Supplier Name *</label>
                    <input type="text" name="name" placeholder="Enter supplier name" required>
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter email">
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="Enter phone number">
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label>Address</label>
                    <textarea name="address" placeholder="Enter address" rows="3" style="resize:vertical;"></textarea>
                </div>
                <button type="submit" name="add_supplier" class="btn btn-primary" style="width:100%;">
                    ➕ Add Supplier
                </button>
            </form>
        </div>

        <!-- Suppliers List -->
        <div class="card">
            <div class="card-header">
                <h2>📋 All Suppliers</h2>
                <span style="color:#888; font-size:14px;">
                    <?php echo mysqli_num_rows($suppliers); ?> suppliers
                </span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($suppliers) > 0): ?>
                        <?php while ($sup = mysqli_fetch_assoc($suppliers)): ?>
                        <tr>
                            <td><?php echo $sup['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($sup['name']); ?></strong>
                                <?php if (!empty($sup['address'])): ?>
                                    <div style="font-size:12px; color:#888;"><?php echo htmlspecialchars($sup['address']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($sup['email'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($sup['phone'] ?? '—'); ?></td>
                            <td>
                                <span class="badge badge-warning"><?php echo $sup['total_products']; ?> products</span>
                            </td>
                            <td>
                                <a href="suppliers.php?delete=<?php echo $sup['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this supplier?')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px; color:#888;">
                                No suppliers yet. Add your first supplier!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>