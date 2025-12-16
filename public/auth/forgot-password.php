<?php
session_start();

$error = '';
$success = '';
$email = '';

// Database connection
try {
    $host = 'localhost';
    $dbname = 'job_recruitment1';
    $username = 'root';
    $password = 'IbsaMysql1';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
    $error = "Database connection failed. Error: " . $e->getMessage();
}

// Simple sanitization function
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);

    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif ($pdo) {
        try {
            // Check if users table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();

            if (!$tableCheck) {
                $error = "The users table does not exist in the database.";
            } else {
                // First, let's check what columns exist in the users table
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Debug: Show available columns
                // echo "Available columns: " . implode(", ", $columns);

                // Check which email column exists
                $emailColumn = null;
                $idColumn = null;
                $usernameColumn = null;

                // Common email column names
                $possibleEmailColumns = ['email', 'Email', 'EMAIL', 'user_email', 'userEmail'];
                $possibleIdColumns = ['id', 'user_id', 'userId', 'ID'];
                $possibleUsernameColumns = ['username', 'user_name', 'name', 'full_name', 'fullname'];

                foreach ($columns as $column) {
                    if (in_array(strtolower($column), array_map('strtolower', $possibleEmailColumns))) {
                        $emailColumn = $column;
                    }
                    if (in_array(strtolower($column), array_map('strtolower', $possibleIdColumns))) {
                        $idColumn = $column;
                    }
                    if (in_array(strtolower($column), array_map('strtolower', $possibleUsernameColumns))) {
                        $usernameColumn = $column;
                    }
                }

                if (!$emailColumn) {
                    $error = "No email column found in users table. Available columns: " . implode(", ", $columns);
                } else {
                    // Build the SELECT query dynamically based on available columns
                    $selectFields = [];
                    if ($idColumn) $selectFields[] = $idColumn;
                    if ($usernameColumn) $selectFields[] = $usernameColumn;
                    $selectFields[] = $emailColumn; // Always include email

                    $query = "SELECT " . implode(", ", $selectFields) . " FROM users WHERE $emailColumn = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        // Check if password_resets table exists
                        $tableExists = $pdo->query("SHOW TABLES LIKE 'password_resets'")->fetch();

                        if (!$tableExists) {
                            // Create password_resets table
                            try {
                                $pdo->exec("CREATE TABLE password_resets (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    email VARCHAR(255) NOT NULL,
                                    token VARCHAR(100) NOT NULL,
                                    expires_at DATETIME NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                )");
                            } catch (PDOException $e) {
                                $error = "Could not create password_resets table: " . $e->getMessage();
                            }
                        }

                        // Generate reset token
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                        // Store token in database
                        try {
                            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                            $stmt->execute([$email, $token, $expires]);

                            // Create reset link
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
                            $server_name = $_SERVER['HTTP_HOST'];
                            $script_name = dirname($_SERVER['SCRIPT_NAME']);

                            $resetLink = $protocol . $server_name . rtrim($script_name, '/') . "/reset-password.php?token=" . $token;

                            // Get username for personalized message
                            $username = isset($user[$usernameColumn]) ? $user[$usernameColumn] : 'User';

                            $success = "Password reset link has been generated for <strong>$username</strong>!<br><br>";
                            $success .= "<strong>Test Link:</strong> <a href='reset-password.php?token=$token' style='color: #764ba2; font-weight: bold; text-decoration: underline;'>Click here to reset password</a><br><br>";
                            $success .= "<small>Note: In a production environment, this link would be sent to your email ($email).</small>";
                        } catch (PDOException $e) {
                            $error = "Could not save reset token: " . $e->getMessage();
                        }
                    } else {
                        // For security, don't reveal if email exists
                        $success = "If your email is registered in our system, you will receive a password reset link shortly.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error occurred: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .password-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 50px;
            color: #764ba2;
            margin-bottom: 15px;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 14px;
        }

        .alert-error {
            background-color: #ffeaea;
            color: #d93025;
            border-left: 4px solid #d93025;
        }

        .alert-success {
            background-color: #e8f7ee;
            color: #1e7b34;
            border-left: 4px solid #1e7b34;
        }

        .alert a {
            color: #764ba2;
            text-decoration: none;
            font-weight: bold;
        }

        .alert a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        input[type="email"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input[type="email"]:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.2);
        }

        .btn {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: translateY(0);
        }

        .links {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .links a {
            color: #764ba2;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 0;
            transition: color 0.3s;
        }

        .links a:hover {
            color: #5a3a8a;
        }

        .links a i {
            margin-right: 8px;
        }

        @media (max-width: 480px) {
            .password-container {
                padding: 30px 20px;
            }

            .links {
                flex-direction: column;
                text-align: center;
            }

            .links a {
                margin-bottom: 10px;
            }
        }

        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="password-container">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your email address and we'll send you instructions to reset your password.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success) || $error): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your email address" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
            <a href="../index.php"><i class="fas fa-home"></i> Return to Home</a>
        </div>

        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div class="debug-info">
                <strong>Database Debug Info:</strong><br>
                <?php
                if ($pdo) {
                    echo "✓ Database Connected<br>";

                    // Check users table
                    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                    if ($tableCheck) {
                        echo "✓ Users table exists<br>";

                        // Show columns
                        $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

                        echo "Columns in users table:<br>";
                        echo "<ul style='margin-left: 20px; margin-top: 5px;'>";
                        foreach ($columns as $column) {
                            echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . "</li>";
                        }
                        echo "</ul>";

                        // Check if password_resets table exists
                        $resetTable = $pdo->query("SHOW TABLES LIKE 'password_resets'")->fetch();
                        if ($resetTable) {
                            echo "✓ Password_resets table exists";
                        } else {
                            echo "✗ Password_resets table does not exist";
                        }
                    } else {
                        echo "✗ Users table does not exist";
                    }
                } else {
                    echo "✗ Database Not Connected";
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>