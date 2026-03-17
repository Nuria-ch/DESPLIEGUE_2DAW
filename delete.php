<?php
require 'auth.php';
include 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header('Location: products.php?success=deleted');
exit();
?>