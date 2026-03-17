<?php
require 'auth.php';
include 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = "";
$error   = "";

// Get low stock products
$low_stock = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= 5 ORDER BY quantity ASC");
$low_count = mysqli_num_rows($low_stock);

// Handle Send Alert
if (isset($_POST['send_alert'])) {
    $to_email = trim($_POST['to_email']);

    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Build email content
        $low_stock_items = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= 5 ORDER BY quantity ASC");

        $email_body = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <div style='background: #1a3c6e; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
                <h1 style='color: white; margin: 0;'>📦 InvenPro</h1>
                <p style='color: rgba(255,255,255,0.7); margin: 5px 0 0;'>Low Stock Alert</p>
            </div>

            <div style='background: #fce8e6; border-left: 4px solid #d93025; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <h2 style='color: #d93025; margin: 0;'>⚠️ " . $low_count . " Products Need Restocking!</h2>
                <p style='color: #666; margin: 5px 0 0;'>The following products are running low on stock.</p>
            </div>

            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='background: #f0f2f5;'>
                        <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e0e2e5;'>Product</th>
                        <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e0e2e5;'>SKU</th>
                        <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e0e2e5;'>Category</th>
                        <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e0e2e5;'>Quantity Left</th>
                        <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e0e2e5;'>Price</th>
                    </tr>
                </thead>
                <tbody>";

        while ($item = mysqli_fetch_assoc($low_stock_items)) {
            $color = $item['quantity'] == 0 ? '#d93025' : '#e37400';
            $email_body .= "
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #f0f2f5;'><strong>" . htmlspecialchars($item['name']) . "</strong></td>
                        <td style='padding: 12px; border-bottom: 1px solid #f0f2f5;'>" . htmlspecialchars($item['sku'] ?? 'N/A') . "</td>
                        <td style='padding: 12px; border-bottom: 1px solid #f0f2f5;'>" . htmlspecialchars($item['category']) . "</td>
                        <td style='padding: 12px; border-bottom: 1px solid #f0f2f5;'><span style='color: $color; font-weight: bold;'>" . $item['quantity'] . " units</span></td>
                        <td style='padding: 12px; border-bottom: 1px solid #f0f2f5;'>$" . number_format($item['price'], 2) . "</td>
                    </tr>";
        }

        $email_body .= "
                </tbody>
            </table>

            <div style='margin-top: 20px; padding: 15px; background: #f0f2f5; border-radius: 5px;'>
                <p style='color: #666; margin: 0; font-size: 13px;'>
                    This is an automated alert from InvenPro Inventory System.<br>
                    Please restock these items as soon as possible.
                </p>
            </div>
        </body>
        </html>";

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sn123686@gmail.com';
            $mail->Password   = trim($_POST['app_password']);
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom('sn123686@gmail.com', 'InvenPro Alerts');
            $mail->addAddress($to_email);
            $mail->isHTML(true);
            $mail->Subject = '⚠️ Low Stock Alert — ' . $low_count . ' products need restocking!';
            $mail->Body    = $email_body;

            $mail->send();

            // Log activity
            $action = "Low stock alert email sent to: $to_email";
            mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");

            $success = "✅ Alert email sent successfully to $to_email!";

        } catch (Exception $e) {
            $error = "❌ Email failed: " . $mail->ErrorInfo;
        }
    }
}

// Reset low stock query
$low_stock = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= 5 ORDER BY quantity ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Alerts — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>📧 Email Alerts</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:25px;">

        <!-- Send Alert Form -->
        <div class="card">
            <div class="card-header">
                <h2>📤 Send Low Stock Alert</h2>
            </div>

            <?php if ($low_count == 0): ?>
                <div class="alert alert-success">✅ All products are well stocked! No alerts needed.</div>
            <?php else: ?>
                <div class="alert alert-danger">⚠️ <?php echo $low_count; ?> products are running low!</div>

                <form method="POST" action="email_alerts.php">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Send Alert To</label>
                        <input type="email" name="to_email" placeholder="Enter recipient email" required
                            value="<?php echo isset($_POST['to_email']) ? htmlspecialchars($_POST['to_email']) : ''; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Gmail App Password</label>
                        <input type="password" name="app_password" placeholder="Enter your Gmail App Password" required>
                        <small style="color:#888; font-size:12px;">
                            Get it from: Google Account → Security → App Passwords
                        </small>
                    </div>
                    <button type="submit" name="send_alert" class="btn btn-danger" style="width:100%;">
                        📧 Send Low Stock Alert Email
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Low Stock Products -->
        <div class="card">
            <div class="card-header">
                <h2>⚠️ Low Stock Products</h2>
                <span class="badge badge-danger"><?php echo $low_count; ?> items</span>
            </div>
            <?php if ($low_count > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = mysqli_fetch_assoc($low_stock)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><code style="background:#f0f2f5; padding:2px 6px; border-radius:4px; font-size:12px;"><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></code></td>
                            <td><span class="badge badge-danger">⚠️ <?php echo $item['quantity']; ?> left</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#888; text-align:center; padding:20px;">✅ All products well stocked!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>