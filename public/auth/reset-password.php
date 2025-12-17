<?php
// Start session for potential user session management
session_start();

// Initialize error and success messages
$error = '';
$success = '';
// Get token from URL parameter
$token = $_GET['token'] ?? '';

// Database connection
try {
    $host = 'localhost';
    $dbname = 'job_recruitment1';
    $username = 'root';
    $password = 'IbsaMysql1';

    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
    $error = "Database connection failed: " . $e->getMessage();
}

/**
 * Simple sanitization function to clean user input
 * @param string|null $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

// Debug: Check what's in the users table (for troubleshooting)
if ($pdo && isset($_GET['debug'])) {
    try {
        echo "<h3>Debug Info:</h3>";
        echo "<h4>Checking users table...</h4>";

        // Check if users table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($tableCheck) {
            echo "✓ Users table exists<br>";

            // Show all columns in users table
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h4>Columns in users table:</h4>";
            echo "<ul>";
            foreach ($columns as $column) {
                echo "<li><strong>" . $column['Field'] . "</strong> - Type: " . $column['Type'] . "</li>";
            }
            echo "</ul>";

            // Show some sample data from users table
            echo "<h4>Sample data (first 5 rows):</h4>";
            $sampleStmt = $pdo->query("SELECT * FROM users LIMIT 5");
            $sampleData = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($sampleData) > 0) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr>";
                foreach (array_keys($sampleData[0]) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                foreach ($sampleData as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No data found in users table.";
            }
        } else {
            echo "✗ Users table does not exist";
        }

        echo "<h4>Checking password_resets table...</h4>";
        $resetTable = $pdo->query("SHOW TABLES LIKE 'password_resets'")->fetch();
        if ($resetTable) {
            echo "✓ Password_resets table exists";
        } else {
            echo "✗ Password_resets table does not exist";
        }

        exit(); // Stop execution after debug output
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}

// Check if token is provided in URL
if (empty($token)) {
    $error = "Invalid or missing reset token.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = sanitizeInput($_POST['password'] ?? '');
    $confirm_password = sanitizeInput($_POST['confirm_password'] ?? '');

    // Validate inputs
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!$pdo) {
        $error = "Database connection issue. Please try again later.";
    } else {
        try {
            // Verify token exists and is not expired in password_resets table
            $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $resetRequest = $stmt->fetch();

            if (!$resetRequest) {
                $error = "Invalid or expired reset token.";
            } elseif (strtotime($resetRequest['expires_at']) < time()) {
                $error = "Reset token has expired. Please request a new password reset.";
            } else {
                $email = $resetRequest['email'];

                // First, check what columns exist in users table
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Try to find the correct columns dynamically
                $emailColumn = null;
                $passwordColumn = null;

                // Look for email column (case-insensitive)
                foreach ($columns as $column) {
                    $lowerColumn = strtolower($column);
                    if (in_array($lowerColumn, ['email', 'user_email', 'e_mail', 'email_address'])) {
                        $emailColumn = $column;
                    }
                    if (in_array($lowerColumn, ['password', 'user_password', 'pass', 'user_pass'])) {
                        $passwordColumn = $column;
                    }
                }

                // If not found, try more generic approach
                if (!$emailColumn) {
                    // Look for any column containing 'email'
                    foreach ($columns as $column) {
                        if (stripos($column, 'email') !== false) {
                            $emailColumn = $column;
                            break;
                        }
                    }
                }

                if (!$passwordColumn) {
                    // Look for any column containing 'pass'
                    foreach ($columns as $column) {
                        if (stripos($column, 'pass') !== false) {
                            $passwordColumn = $column;
                            break;
                        }
                    }
                }

                // If still not found, use common column positions as fallback
                if (!$emailColumn && count($columns) > 0) {
                    // Check common email column positions (Often email is 2nd or 3rd column)
                    $commonEmailPositions = [1, 2, 3];
                    foreach ($commonEmailPositions as $pos) {
                        if (isset($columns[$pos])) {
                            $emailColumn = $columns[$pos];
                            break;
                        }
                    }
                }

                if (!$passwordColumn && count($columns) > 0) {
                    // Password is often the 3rd or 4th column
                    $commonPasswordPositions = [3, 4];
                    foreach ($commonPasswordPositions as $pos) {
                        if (isset($columns[$pos])) {
                            $passwordColumn = $columns[$pos];
                            break;
                        }
                    }
                }

                // Final fallback - assume standard column positions
                if (!$emailColumn && count($columns) > 1) {
                    $emailColumn = $columns[1]; // Assume second column is email
                }

                if (!$passwordColumn && count($columns) > 2) {
                    $passwordColumn = $columns[2]; // Assume third column is password
                }

                // Error if required columns cannot be identified
                if (!$emailColumn || !$passwordColumn) {
                    $error = "Could not identify required columns in users table. Available columns: " . implode(", ", $columns);
                    $error .= "<br><br><a href='?debug=1&token=$token' style='color: blue;'>Click here to see table structure</a>";
                } else {
                    // Hash the new password for secure storage
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Update password in users table using dynamic column names
                    $updateStmt = $pdo->prepare("UPDATE users SET $passwordColumn = ? WHERE $emailColumn = ?");
                    $updateResult = $updateStmt->execute([$hashed_password, $email]);

                    if ($updateResult) {
                        // Delete the used token from password_resets table
                        $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                        $deleteStmt->execute([$token]);

                        // Success message with login link
                        $success = "Password has been reset successfully!<br>";
                        $success .= "Used columns: $emailColumn (email) and $passwordColumn (password)<br>";
                        $success .= "<a href='login.php' style='color: #764ba2; font-weight: bold;'>Click here to login</a>";
                    } else {
                        $error = "Failed to update password. Please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            $error .= "<br><br><a href='?debug=1&token=$token' style='color: blue;'>Click here to debug</a>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        .reset-container {
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

        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input[type="password"]:focus {
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

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .debug-link {
            margin-top: 20px;
            text-align: center;
        }

        .debug-link a {
            color: #666;
            font-size: 12px;
            text-decoration: none;
        }

        .debug-link a:hover {
            color: #764ba2;
        }

        @media (max-width: 480px) {
            .reset-container {
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
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="logo">
            <i class="fas fa-lock"></i>
            <h2>Reset Your Password</h2>
            <p class="subtitle">Enter your new password below.</p>
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

        <?php if (empty($success) && empty($error)): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password"
                            placeholder="Enter new password (min. 6 characters)" required>
                    </div>
                    <div class="password-strength">Password must be at least 6 characters long.</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Confirm new password" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-check-circle"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
            <a href="../index.php"><i class="fas fa-home"></i> Return to Home</a>
        </div>

        <div class="debug-link">
            <a href="?debug=1&token=<?php echo htmlspecialchars($token); ?>">Debug: View table structure</a>
        </div>
    </div>
</body>

</html>