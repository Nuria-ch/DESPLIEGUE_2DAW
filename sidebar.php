<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Hamburger Button -->
<button class="hamburger" id="hamburger" onclick="toggleSidebar()">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" style="text-decoration:none;">
            <div class="sidebar-logo">
                <span class="logo-icon">📦</span>
                <span class="logo-text">InvenPro</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="products.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span>
            <span class="nav-text">Products</span>
        </a>
        <a href="add.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : ''; ?>">
            <span class="nav-icon">➕</span>
            <span class="nav-text">Add Product</span>
        </a>
        <a href="stock.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🔄</span>
            <span class="nav-text">Stock In/Out</span>
        </a>
        <a href="suppliers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏭</span>
            <span class="nav-text">Suppliers</span>
        </a>
        <a href="categories.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏷️</span>
            <span class="nav-text">Categories</span>
        </a>
        <a href="currency.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'currency.php' ? 'active' : ''; ?>">
            <span class="nav-icon">💱</span>
            <span class="nav-text">Currency</span>
        </a>
        <a href="barcode.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'barcode.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🔖</span>
            <span class="nav-text">Barcodes</span>
        </a>
        <a href="email_alerts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'email_alerts.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📧</span>
            <span class="nav-text">Email Alerts</span>
        </a>
        <a href="activity.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'activity.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📜</span>
            <span class="nav-text">Activity Log</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-text">Reports</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div style="padding:15px; border-top:1px solid rgba(255,255,255,0.1);">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="color:white; font-size:13px; font-weight:600; margin-bottom:3px;">
                    👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
                <div style="color:rgba(255,255,255,0.5); font-size:11px; margin-bottom:10px;">
                    <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                </div>
                <a href="logout.php" style="display:block; background:rgba(255,255,255,0.1); color:white; text-decoration:none; padding:8px 12px; border-radius:6px; font-size:13px; text-align:center;">
                    🚪 Logout
                </a>
            <?php else: ?>
                <div style="color:rgba(255,255,255,0.7); font-size:12px; margin-bottom:10px; text-align:center;">
                    👀 Viewing as Guest
                </div>
                <a href="login.php" style="display:block; background:#27ae60; color:white; text-decoration:none; padding:8px 12px; border-radius:6px; font-size:13px; text-align:center; margin-bottom:8px;">
                    🔐 Login
                </a>
                <a href="register.php" style="display:block; background:rgba(255,255,255,0.1); color:white; text-decoration:none; padding:8px 12px; border-radius:6px; font-size:13px; text-align:center;">
                    ✨ Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}
</script>