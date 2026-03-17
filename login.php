<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

include 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email']= $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Log activity
            $action = "User logged in: " . $user['name'];
            mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");

            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'dashboard.php';
unset($_SESSION['redirect_after_login']);
header('Location: ' . $redirect);
exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — InvenPro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a3c6e 0%, #0f2548 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo .logo-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }

        .auth-logo h1 {
            font-size: 28px;
            color: #1a3c6e;
            font-weight: 700;
        }

        .auth-logo p {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e2e5;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            font-family: inherit;
        }

        input:focus {
            border-color: #1a3c6e;
            box-shadow: 0 0 0 3px rgba(26,60,110,0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #1a3c6e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 5px;
        }

        .btn-submit:hover {
            background: #0f2548;
        }

        .alert-error {
            background: #fce8e6;
            color: #d93025;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #d93025;
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #888;
        }

        .auth-footer a {
            color: #1a3c6e;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #ccc;
            font-size: 13px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #e0e2e5;
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-logo">
        <span class="logo-icon">📦</span>
        <h1>InvenPro</h1>
        <p>
    <?php
    if (isset($_GET['message']) && $_GET['message'] == 'login_required') {
        echo '🔒 Please login to perform this action';
    } else {
        echo 'Sign in to your account';
    }
    ?>
</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn-submit">🔐 Sign In</button>
    </form>

    <div class="divider">or</div>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Create one</a>
    </div>
</div>

</body>
</html>