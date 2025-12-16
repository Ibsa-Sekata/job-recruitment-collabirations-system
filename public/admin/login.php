<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = AdminController::login($email, $password);

    if ($result['ok']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['msg'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin Login - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="admin-login">
        <div class="login-card">
            <div class="admin-header">
                <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h1>Admin Login</h1>
                <p>Access the administrative dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="input" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="input" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login as Admin
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
                <span class="text-muted mx-2">|</span>
                <a href="setup.php" class="text-muted">
                    <i class="fas fa-cog"></i> Admin Setup
                </a>
            </div>
        </div>
    </div>
</body>

</html>