<?php
require 'auth.php';
include 'db.php';

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcodes — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
    <style>
        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .barcode-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .barcode-card:hover {
            transform: translateY(-3px);
        }

        .barcode-card img {
            max-width: 100%;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .barcode-card h3 {
            font-size: 15px;
            color: #1a3c6e;
            margin-bottom: 5px;
        }

        .barcode-card p {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }

        @media print {
            .sidebar, .topbar-right, .card-header .btn { display: none !important; }
            .main-content { margin-left: 0; }
            .barcode-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 768px) {
            .barcode-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>🔖 Barcode Generator</h1>
        <div class="topbar-right">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print All Barcodes</button>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card" style="margin-bottom:25px; background:linear-gradient(135deg, #1a3c6e, #0f2548); color:white;">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="font-size:40px;">🔖</div>
            <div>
                <h2 style="color:white; margin-bottom:5px;">Auto-Generated Barcodes</h2>
                <p style="color:rgba(255,255,255,0.7); font-size:14px;">
                    Each barcode is automatically generated from the product SKU using Barcode API.
                    Print them and stick on your products!
                </p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Product Barcodes</h2>
            <span style="color:#888; font-size:14px;">
                <?php echo mysqli_num_rows($products); ?> barcodes
            </span>
        </div>

        <div class="barcode-grid">
            <?php while ($p = mysqli_fetch_assoc($products)): ?>
            <div class="barcode-card">
                <!-- Barcode image generated from SKU using barcodeapi.org -->
                <img
                    src="https://barcodeapi.org/api/128/<?php echo urlencode($p['sku'] ?? $p['id']); ?>"
                    alt="Barcode for <?php echo htmlspecialchars($p['name']); ?>"
                    onerror="this.src=''; this.alt='Barcode unavailable';">

                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <p>SKU: <?php echo htmlspecialchars($p['sku'] ?? 'N/A'); ?></p>

                <div style="display:flex; gap:8px; justify-content:center;">
                    <span class="badge badge-warning"><?php echo htmlspecialchars($p['category']); ?></span>
                    <?php if ($p['quantity'] <= 5): ?>
                        <span class="badge badge-danger">⚠️ Low Stock</span>
                    <?php else: ?>
                        <span class="badge badge-success">✅ In Stock</span>
                    <?php endif; ?>
                </div>

                <div style="margin-top:10px;">
                    <a href="https://barcodeapi.org/api/128/<?php echo urlencode($p['sku'] ?? $p['id']); ?>"
                        download="barcode-<?php echo htmlspecialchars($p['sku']); ?>.png"
                        class="btn btn-primary btn-sm">
                        ⬇️ Download
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

</body>
</html>