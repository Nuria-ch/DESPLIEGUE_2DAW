<?php
session_start();

// Log activity before destroying session
if (isset($_SESSION['user_name'])) {
    include 'db.php';
    $name   = $_SESSION['user_name'];
    $action = "User logged out: $name";
    mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
}

session_destroy();
header('Location: login.php');
exit();
?>