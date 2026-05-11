<?php
session_start();
require_once 'config.php';

// Already logged in → go straight to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ADMIN_DASHBOARD.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string)($_POST['email']    ?? ''));
    $password =       (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT admin_id, first_name, last_name, email, password
             FROM admins WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res   = $stmt->get_result();
        $admin = $res->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']    = (int)$admin['admin_id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name']  = $admin['first_name'] . ' ' . $admin['last_name'];
            header('Location: ADMIN_DASHBOARD.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Blessed Board</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { width: 100%; max-width: 420px; }
        .logo-section { text-align: center; margin-bottom: 40px; }
        .logo { font-size: 48px; margin-bottom: 20px; }
        h1 { font-size: 28px; color: #1a2b4a; font-weight: 700; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #6b7c93; }
        .form-container {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,.08);
        }
        .error-box {
            background: rgba(231,76,60,.1);
            color: #e74c3c;
            border: 1px solid rgba(231,76,60,.25);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 22px; }
        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #1a2b4a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e6f0;
            border-radius: 10px;
            font-size: 14px;
            background: #f8f9fb;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus {
            outline: none;
            border-color: #4a90e2;
            background: white;
            box-shadow: 0 0 0 3px rgba(74,144,226,.12);
        }
        input::placeholder { color: #a8b3c1; }
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            font-size: 13px;
        }
        .remember-me { display: flex; align-items: center; gap: 8px; }
        .remember-me input { accent-color: #4a90e2; }
        .remember-me span { color: #6b7c93; }
        .forgot { color: #4a90e2; text-decoration: none; font-weight: 600; }
        .btn {
            display: block;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: .5px;
            transition: background .2s, transform .15s;
        }
        .btn-login { background: #1a2b4a; color: white; margin-bottom: 12px; }
        .btn-login:hover { background: #0d1620; transform: translateY(-1px); }
        .btn-create { background: #e0e6f0; color: #6b7c93; }
        .btn-create:hover { background: #d0d8e4; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #4a90e2; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo-section">
        <div class="logo">🛡️</div>
        <h1>Admin Login</h1>
        <p class="subtitle">Access the Blessed Board administration panel</p>
    </div>

    <div class="form-container">
        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="ADMIN_LOG_IN.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="Enter your email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <div class="options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-login">Sign In</button>
            <a href="ADMIN_SIGN_UP.php" class="btn btn-create">Create Account</a>
        </form>

        <div class="back-link">
            <a href="INDEX.html">← Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
