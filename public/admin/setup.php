<?php
// Include helper functions (database connection, sessions, utilities, etc.)
require_once __DIR__ . '/../../src/helpers.php';

// Include AdminController for admin-related actions
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Check if any admin already exists
// This setup page is intended ONLY for first-time admin creation
$pdo = getPDO();
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
$adminCount = $stmt->fetch()['count'];

// If at least one admin exists, setup is considered complete
$setupComplete = $adminCount > 0;

// Variables to store error and success messages
$error = '';
$success = '';

// Handle form submission (only if setup is not complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupComplete) {

    // Trim input values to avoid issues with extra spaces
    $postData = array_map('trim', $_POST);

    // Attempt to create the super admin account
    $result = AdminController::createAdmin($postData);

    if ($result['ok']) {
        // Success message after admin creation
        $success = $result['msg'];
        $setupComplete = true;
    } else {
        // Error message if admin creation fails
        $error = $result['msg'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin Setup - Job Recruitment</title>

    <!-- Main application stylesheet -->
    <link rel="stylesheet" href="../css/app.css">

    <!-- Font Awesome icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Full-page centered layout */
        .setup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Setup card styling */
        .setup-card {
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        /* Header section */
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
                <i class="fas fa-cog"
                   style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>

                <h1>Super Admin Setup</h1>

                <!-- Display setup status -->
                <?php if ($setupComplete): ?>
                    <p>Super Admin account is already configured</p>
                <?php else: ?>
                    <p>Create the Super Admin account (one-time setup)</p>
                <?php endif; ?>
            </div>

            <!-- Display error message if any -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Display success message if admin is created -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- If setup is complete, show login link -->
            <?php if ($setupComplete): ?>
                <div class="text-center">
                    <p class="mb-4">
                        Super Admin account is already configured.
                        Only the Super Admin can create additional admin accounts.
                    </p>

                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Admin Login
                    </a>
                </div>

            <!-- Otherwise, show setup form -->
            <?php else: ?>
                <form method="POST" class="form">

                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name"
                               class="input" required
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email"
                               class="input" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password"
                               class="input" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-shield"></i>
                        Create Super Admin Account
                    </button>
                </form>
            <?php endif; ?>

            <!-- Back to main site link -->
            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
        </div>
    </div>
</body>

</html>