<?php
require 'auth.php';
include 'db.php';

$api_key  = "6448334bfb17055d87c74b53";
$base_url = "http://v6.exchangerate-api.com/v6/$api_key/latest/USD";

// Use cURL instead of file_get_contents
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$data    = json_decode($response, true);
$rates   = $data['rates'] ?? [];
$updated = $data['time_last_update_utc'] ?? 'N/A';

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");

// Selected currency
$selected_currency = isset($_GET['currency']) ? $_GET['currency'] : 'PKR';
$rate              = $rates[$selected_currency] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Converter — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>💱 Currency Converter</h1>
        <div class="topbar-right">
            <span style="color:#888; font-size:13px;">Last updated: <?php echo $updated; ?></span>
        </div>
    </div>

    <!-- Currency Selector -->
    <div class="card" style="margin-bottom:25px;">
        <div class="card-header">
            <h2>Select Currency</h2>
        </div>
        <form method="GET" action="currency.php" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
            <select name="currency" class="filter-select" style="min-width:200px;">
                <?php
                $popular = ['PKR', 'USD', 'EUR', 'GBP', 'AED', 'SAR', 'CAD', 'AUD', 'INR', 'CNY'];
                foreach ($popular as $cur):
                ?>
                    <option value="<?php echo $cur; ?>" <?php echo $selected_currency == $cur ? 'selected' : ''; ?>>
                        <?php echo $cur; ?>
                        <?php if (isset($rates[$cur])): ?>
                            (1 USD = <?php echo number_format($rates[$cur], 2); ?> <?php echo $cur; ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">💱 Convert</button>
        </form>
    </div>

    <!-- Exchange Rates Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom:25px;">
        <?php foreach ($popular as $cur): ?>
        <div class="stat-card" style="<?php echo $selected_currency == $cur ? 'border:2px solid #1a3c6e;' : ''; ?>">
            <div class="stat-icon blue" style="font-size:16px; width:40px; height:40px;">💰</div>
            <div class="stat-info">
                <h3 style="font-size:16px;"><?php echo number_format($rates[$cur] ?? 0, 2); ?></h3>
                <p><?php echo $cur; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Products in Selected Currency -->
    <div class="card">
        <div class="card-header">
            <h2>📦 Product Prices in <?php echo htmlspecialchars($selected_currency); ?></h2>
            <span style="color:#888; font-size:13px;">
                1 USD = <?php echo number_format($rate, 2); ?> <?php echo htmlspecialchars($selected_currency); ?>
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price (USD)</th>
                    <th>Price (<?php echo htmlspecialchars($selected_currency); ?>)</th>
                    <th>Total Value (<?php echo htmlspecialchars($selected_currency); ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                mysqli_data_seek($products, 0);
                while ($p = mysqli_fetch_assoc($products)):
                    $converted_price = $p['price'] * $rate;
                    $converted_total = $p['price'] * $p['quantity'] * $rate;
                ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td>
                        <code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:12px;">
                            <?php echo htmlspecialchars($p['sku'] ?? 'N/A'); ?>
                        </code>
                    </td>
                    <td>
                        <span class="badge badge-warning"><?php echo htmlspecialchars($p['category']); ?></span>
                    </td>
                    <td>$<?php echo number_format($p['price'], 2); ?></td>
                    <td>
                        <strong style="color:#1a3c6e;">
                            <?php echo number_format($converted_price, 2); ?> <?php echo htmlspecialchars($selected_currency); ?>
                        </strong>
                    </td>
                    <td>
                        <span class="badge badge-success">
                            <?php echo number_format($converted_total, 2); ?> <?php echo htmlspecialchars($selected_currency); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>