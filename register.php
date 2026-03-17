<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

include 'db.php';

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);

            if (mysqli_stmt_execute($stmt)) {
                // Log activity
                $action = "New user registered: $name";
                mysqli_query($conn, "INSERT INTO activity_log (action) VALUES ('$action')");
                $success = "Account created successfully! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — InvenPro</title>
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
            margin-bottom: 18px;
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
            background: #27ae60;
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
            background: #1e8e3e;
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

        .alert-success {
            background: #e6f4ea;
            color: #1e8e3e;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #1e8e3e;
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

        .password-hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-logo">
        <span class="logo-icon">📦</span>
        <h1>InvenPro</h1>
        <p>Create your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">✅ <?php echo $success; ?> <a href="login.php" style="color:#1a3c6e;">Login here</a></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Enter your full name"
                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
            <p class="password-hint">Must be at least 6 characters</p>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat your password" required>
        </div>
        <button type="submit" class="btn-submit">🚀 Create Account</button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>

</body>
</html>