<?php
// Register page with role-based registration (Job Seeker or Employer)

// Include required helper functions and controller classes
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

// Initialize error and success message variables
$error = '';
$success = '';

// Check if form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Call AuthController register method with form data
    $result = AuthController::register($_POST);

    if ($result['ok']) {
        // Store success message in session for display
        $_SESSION['success'] = $result['msg'];

        // Auto-login user after successful registration
        $user = AuthController::login($_POST['email'], $_POST['password']);
        if ($user['ok']) {
            // Redirect based on user role
            if ($_POST['role'] === 'employer') {
                // Employers need admin approval before posting jobs
                $_SESSION['success'] = 'Registration successful! Your account is pending admin approval. You will be able to post jobs once approved.';
                header('Location: ../employer/dashboard.php');
            } else {
                // Job seekers are redirected to homepage
                header('Location: ../index.php');
            }
            exit; // Stop script execution after redirect
        } else {
            // If auto-login fails, redirect to login page
            header('Location: login.php');
            exit;
        }
    } else {
        // Store registration error message
        $error = $result['msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register - Job Recruitment</title>
    <!-- External CSS libraries and custom styles -->
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Main container styling */
        .register-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        /* Card styling for registration form */
        .register-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Header section styling */
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--gray);
        }

        /* Role selection buttons styling */
        .role-selection {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 1.5rem 1rem;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .role-option.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1));
        }

        .role-option i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
            display: block;
        }

        .role-option h3 {
            margin: 0 0 0.5rem 0;
            color: var(--dark);
        }

        .role-option p {
            margin: 0;
            color: var(--gray);
            font-size: 0.875rem;
        }

        /* Company fields (shown only for employers) */
        .company-fields {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .company-fields.show {
            display: block;
        }

        /* Form layout */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

        /* Input field with icon */
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

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: var(--light-gray);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            border-radius: 2px;
        }

        .weak {
            background: var(--danger);
            width: 33%;
        }

        .medium {
            background: var(--warning);
            width: 66%;
        }

        .strong {
            background: var(--success);
            width: 100%;
        }

        /* Login link at bottom */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
            color: var(--gray);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Animation for showing company fields */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <!-- Navigation bar -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-right">
                <a href="login.php" class="btn btn-secondary">Login</a>
            </div>
        </div>
    </nav>

    <!-- Main registration container -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join thousands of professionals and companies</p>
            </div>

            <!-- Error message display -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Success message display -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Registration form -->
            <form method="post" action="" id="registerForm">
                <!-- Role selection (Job Seeker or Employer) -->
                <div class="role-selection">
                    <div class="role-option selected" data-role="jobseeker">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Job Seeker</h3>
                        <p>Looking for job opportunities</p>
                        <input type="radio" name="role" value="jobseeker" checked hidden>
                    </div>
                    <div class="role-option" data-role="employer">
                        <i class="fas fa-building"></i>
                        <h3>Employer</h3>
                        <p>Hiring talent for your company</p>
                        <input type="radio" name="role" value="employer" hidden>
                    </div>
                </div>

                <!-- Name and phone fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" class="input" placeholder="John Doe" required
                                value="<?= htmlspecialchars(old('name') ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone" class="input" placeholder="+1 (555) 123-4567"
                                value="<?= htmlspecialchars(old('phone') ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Email field -->
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="input" placeholder="you@example.com" required
                            value="<?= htmlspecialchars(old('email') ?? '') ?>">
                    </div>
                </div>

                <!-- Password field with strength indicator -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="input" placeholder="••••••••"
                            required onkeyup="checkPasswordStrength(this.value)">
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">Minimum 6 characters</small>
                </div>

                <!-- Company fields (only shown for employers) -->
                <div id="companyFields" class="company-fields">
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name</label>
                        <div class="input-icon">
                            <i class="fas fa-building"></i>
                            <input type="text" id="company_name" name="company_name" class="input"
                                placeholder="Your Company Inc." value="<?= htmlspecialchars(old('company_name') ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <!-- Login link for existing users -->
            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <!-- JavaScript for interactive features -->
    <script>
        // Role selection functionality
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                    opt.querySelector('input[type="radio"]').checked = false;
                });

                // Add selected class to clicked option
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;

                // Show/hide company fields based on selected role
                const role = this.dataset.role;
                const companyFields = document.getElementById('companyFields');
                const companyNameInput = document.getElementById('company_name');

                if (role === 'employer') {
                    companyFields.classList.add('show');
                    companyNameInput.required = true; // Make company name required for employers
                } else {
                    companyFields.classList.remove('show');
                    companyNameInput.required = false;
                }
            });
        });

        /**
         * Check password strength and update visual indicator
         * @param {string} password - The password to check
         */
        function checkPasswordStrength(password) {
            const bar = document.getElementById('passwordStrengthBar');
            let strength = 0;

            // Criteria for password strength
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++; // Has uppercase
            if (/[0-9]/.test(password)) strength++; // Has number
            if (/[^A-Za-z0-9]/.test(password)) strength++; // Has special character

            // Update strength bar based on score
            bar.className = 'password-strength-bar';
            if (strength < 2) {
                bar.classList.add('weak');
            } else if (strength < 4) {
                bar.classList.add('medium');
            } else {
                bar.classList.add('strong');
            }
        }

        // Form validation before submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
    </script>
</body>

</html>