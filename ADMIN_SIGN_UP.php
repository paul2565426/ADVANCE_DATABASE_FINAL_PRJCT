<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ADMIN_DASHBOARD.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first   = trim((string)($_POST['firstName'] ?? ''));
    $last    = trim((string)($_POST['lastName']  ?? ''));
    $email   = trim((string)($_POST['email']     ?? ''));
    $pass    =       (string)($_POST['password']  ?? '');
    $confirm =       (string)($_POST['confirm']   ?? '');

    if ($first === '' || $last === '' || $email === '' || $pass === '' || $confirm === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT admin_id FROM admins WHERE email = ? LIMIT 1");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO admins (first_name, last_name, email, password) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $first, $last, $email, $hash);

            if ($stmt->execute()) {
                header('Location: ADMIN_LOG_IN.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Blessed Board</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 560px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 36px; }
        .logo { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 28px; color: #1a2b4a; font-weight: 700; margin-bottom: 6px; }
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-row.single { grid-template-columns: 1fr; }
        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #1a2b4a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        input[type="text"], input[type="email"], input[type="password"] {
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
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 24px 0;
            font-size: 13px;
            color: #6b7c93;
        }
        .terms input { accent-color: #4a90e2; margin-top: 2px; }
        .terms a { color: #4a90e2; font-weight: 600; text-decoration: none; }
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
            text-transform: uppercase;
            letter-spacing: .5px;
            transition: background .2s, transform .15s;
            margin-bottom: 16px;
        }
        .btn-primary { background: #1a2b4a; color: white; }
        .btn-primary:hover { background: #0d1620; transform: translateY(-1px); }
        .signin-link { text-align: center; font-size: 13px; color: #6b7c93; }
        .signin-link a { color: #4a90e2; font-weight: 600; text-decoration: none; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #4a90e2; font-size: 13px; font-weight: 600; text-decoration: none; }
        @media (max-width: 500px) {
            .form-container { padding: 28px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🛡️</div>
        <h1>Create Admin Account</h1>
        <p class="subtitle">Join the Blessed Board to manage prayer requests</p>
    </div>

    <div class="form-container">
        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="ADMIN_SIGN_UP.php">
            <div class="form-row">
                <div>
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName"
                           placeholder="First name"
                           value="<?= htmlspecialchars($_POST['firstName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
                <div>
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName"
                           placeholder="Last name"
                           value="<?= htmlspecialchars($_POST['lastName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
            </div>

            <div class="form-row single">
                <div>
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           placeholder="Enter your email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="At least 6 characters" required>
                </div>
                <div>
                    <label for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms" style="text-transform:none; letter-spacing:0; font-weight:400;">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>

            <p class="signin-link">
                Already have an account? <a href="ADMIN_LOG_IN.php">Sign in here</a>
            </p>
        </form>

        <div class="back-link">
            <a href="INDEX.html">← Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
