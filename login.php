<?php
require_once 'config.php';

// Initialize database if needed
Database::initDB();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = getPost('username', '');
    $password = getPost('password', '');

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if (login($username, $password)) {
            $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectUrl);
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }

        .logo h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }

        .default-credentials {
            margin-top: 20px;
            padding: 15px;
            background: #f0f4ff;
            border-radius: 10px;
            border: 1px solid #d0e0ff;
        }

        .default-credentials h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .default-credentials p {
            font-size: 13px;
            color: #555;
            margin: 4px 0;
        }

        .default-credentials code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <svg viewBox="0 0 50 50">
                <defs>
                    <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea"/>
                        <stop offset="100%" style="stop-color:#764ba2"/>
                    </linearGradient>
                </defs>
                <circle cx="25" cy="25" r="22" fill="url(#logoGradient)" />
                <circle cx="12" cy="38" r="4" fill="white" />
                <path d="M 12 30 Q 12 16, 26 16" stroke="white" stroke-width="4" fill="none" stroke-linecap="round"/>
                <path d="M 12 24 Q 12 14, 22 14" stroke="white" stroke-width="4" fill="none" stroke-linecap="round"/>
            </svg>
            <h1><?= e(APP_NAME) ?></h1>
            <p>Professional Edition</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus value="<?= e(getPost('username', '')) ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="default-credentials">
            <h4>Default Admin Credentials:</h4>
            <p>Username: <code>admin</code></p>
            <p>Password: <code>admin123</code></p>
            <p style="margin-top: 8px; font-size: 12px; color: #999;">Please change the password after first login</p>
        </div>
    </div>
</body>
</html>
