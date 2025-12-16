<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = AuthController::login($email, $password);

    if ($result['ok']) {
        // Redirect based on role
        if ($_SESSION['user']['role'] === 'employer') {
            header("Location: ../employer/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $error = $result['msg'];
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css?v=20">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .login-container {
        max-width: 400px;
        margin: 4rem auto;
        padding: 0 1rem;
    }

    .login-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header h1 {
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .login-header p {
        color: var(--gray);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--dark);
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .input-icon input {
        padding-left: 3rem;
    }

    .remember-forgot {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }

    .forgot-password {
        color: var(--primary);
        text-decoration: none;
    }

    .forgot-password:hover {
        text-decoration: underline;
    }

    .register-link {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--light-gray);
        color: var(--gray);
    }

    .register-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    /* role-selection styles removed from login (not used on this page) */
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-right">
                <a href="../auth/register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="input" placeholder="you@example.com"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="input" placeholder="••••••••"
                            required>
                    </div>
                </div>

                <div class="remember-forgot">
                    <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
    </div>
</body>

</html>