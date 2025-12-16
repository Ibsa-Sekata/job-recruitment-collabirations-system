<?php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Employer.php';
require_once __DIR__ . '/../helpers.php';

class AuthController
{
    // User registration
    public static function register(array $input)
    {
        // Sanitize and validate email
        $email = strtolower(trim($input['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Invalid email'];
        }

        // Check if email already exists
        if (User::findByEmail($email)) {
            return ['ok' => false, 'msg' => 'Email already registered'];
        }

        // Validate password
        $password = $input['password'] ?? '';
        if (strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'Password must be at least 6 characters'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Create user
        $userId = User::create([
            'role' => $input['role'] ?? 'jobseeker',
            'name' => trim($input['name'] ?? ''),
            'email' => $email,
            'phone' => trim($input['phone'] ?? ''),
            'password_hash' => $hash,
            'is_verified' => 0
        ]);
        if ($userId) {
            // If the user is an employer, create a record in the employers table
            if (($input['role'] ?? 'jobseeker') === 'employer') {
                Employer::create([
                    'user_id' => $userId,
                    'company_name' => $input['company_name'] ?? $input['name'],
                    'website' => $input['website'] ?? '',
                    'industry' => $input['industry'] ?? '',
                    'company_size' => $input['company_size'] ?? '',
                    'address' => $input['address'] ?? '',
                    'logo' => $input['logo'] ?? '', // Handle file upload separately if needed
                    'verified' => 0, // or false
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            return ['ok' => true, 'msg' => 'Registration successful', 'user_id' => $userId];
        }

        return ['ok' => false, 'msg' => 'Could not create user'];
    }

    // User login
    public static function login(string $email, string $password)
    {
        $email = strtolower(trim($email));
        $user = User::verifyPassword($email, $password);

        if (!$user) {
            return ['ok' => false, 'msg' => 'Invalid credentials'];
        }

        // Remove sensitive info before storing in session
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        return ['ok' => true, 'msg' => 'Logged in'];
    }

    // User logout
    public static function logout()
    {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        session_regenerate_id(true);
    }
}
