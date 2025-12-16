<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Check if any admin exists (setup is only for initial admin creation)
$pdo = getPDO();
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
$adminCount = $stmt->fetch()['count'];
$setupComplete = $adminCount > 0;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupComplete) {
    $result = AdminController::createAdmin($_POST);

    if ($result['ok']) {
        $success = $result['msg'];
        $setupComplete = true;
    } else {
        $error = $result['msg'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin Setup - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .setup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .setup-card {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .setup-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <i class="fas fa-cog" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h1>Super Admin Setup</h1>
                <?php if ($setupComplete): ?>
                    <p>Super Admin account is already configured</p>
                <?php else: ?>
                    <p>Create the Super Admin account (one-time setup)</p>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($setupComplete): ?>
                <div class="text-center">
                    <p class="mb-4">Super Admin account is already configured. Only the Super Admin can create additional
                        admin accounts.</p>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Admin Login
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" class="input" required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

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
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-shield"></i> Create Super Admin Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
        </div>
    </div>
</body>

</html>