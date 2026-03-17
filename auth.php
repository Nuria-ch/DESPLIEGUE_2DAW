<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pages that guests can VIEW
$guest_allowed = [
    'dashboard.php',
    'products.php',
    'reports.php',
    'barcode.php',
    'currency.php',
    'categories.php',
    'suppliers.php',
    'stock.php',
    'activity.php',
];

// Pages that REQUIRE login
$login_required = [
    'add.php',
    'edit.php',
    'delete.php',
    'email_alerts.php',
];

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id'])) {
    if (in_array($current_page, $login_required)) {
        // Store the page they tried to visit
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php?message=login_required');
        exit();
    }
}
?>