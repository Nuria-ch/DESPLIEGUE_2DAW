<?php
require 'auth.php';
include 'db.php';

// Clear all logs
if (isset($_POST['clear_logs'])) {
    mysqli_query($conn, "DELETE FROM activity_log");
    header('Location: activity.php?success=cleared');
    exit();
}

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

$total_rows  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_log"))['count'];
$total_pages = ceil($total_rows / $per_page);

$logs = mysqli_query($conn, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log — InvenPro</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <h1>Activity Log</h1>
        <div class="topbar-right">
            <form method="POST" action="activity.php" style="display:inline;">
                <button type="submit" name="clear_logs" class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to clear all logs?')">
                    🗑️ Clear All Logs
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Logs cleared successfully!</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📜 System Activity</h2>
            <span style="color:#888; font-size:14px;"><?php echo $total_rows; ?> total activities</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                    <th>Time Ago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td>
                            <?php
                            $action = htmlspecialchars($log['action']);
                            if (strpos($log['action'], 'Added') !== false) {
                                echo '<span class="badge badge-success">➕</span> ' . $action;
                            } elseif (strpos($log['action'], 'deleted') !== false || strpos($log['action'], 'Deleted') !== false) {
                                echo '<span class="badge badge-danger">🗑️</span> ' . $action;
                            } elseif (strpos($log['action'], 'Stock IN') !== false) {
                                echo '<span class="badge badge-success">📦</span> ' . $action;
                            } elseif (strpos($log['action'], 'Stock OUT') !== false) {
                                echo '<span class="badge badge-danger">📤</span> ' . $action;
                            } else {
                                echo '<span class="badge badge-warning">✏️</span> ' . $action;
                            }
                            ?>
                        </td>
                        <td><?php echo $log['created_at']; ?></td>
                        <td style="color:#888; font-size:13px;">
                            <?php
                            $diff = abs(time() - strtotime($log['created_at']));
                            if ($diff < 60) echo $diff . ' seconds ago';
                            elseif ($diff < 3600) echo floor($diff/60) . ' minutes ago';
                            elseif ($diff < 86400) echo floor($diff/3600) . ' hours ago';
                            else echo floor($diff/86400) . ' days ago';
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:40px; color:#888;">
                            No activity recorded yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:25px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>" class="btn btn-secondary btn-sm">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                    class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>" class="btn btn-secondary btn-sm">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>