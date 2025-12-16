<?php
require_once __DIR__ . '/../Models/Admin.php';
require_once __DIR__ . '/../Models/Employer.php';
require_once __DIR__ . '/../helpers.php';

class AdminController
{
    // Admin login
    public static function login(string $email, string $password)
    {
        $email = strtolower(trim($email));
        $admin = Admin::verifyPassword($email, $password);

        if (!$admin) {
            return ['ok' => false, 'msg' => 'Invalid admin credentials'];
        }

        // Remove sensitive info before storing in session
        unset($admin['password_hash']);
        $_SESSION['admin'] = $admin;

        return ['ok' => true, 'msg' => 'Admin logged in successfully'];
    }

    // Admin logout
    public static function logout()
    {
        if (isset($_SESSION['admin'])) {
            unset($_SESSION['admin']);
        }
        session_regenerate_id(true);
    }

    // Create admin account (only super admin can create other admins)
    public static function createAdmin(array $input)
    {
        // Check if this is initial setup (no admins exist) or if current user is super admin
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $adminCount = $stmt->fetch()['count'];
        
        $isInitialSetup = $adminCount == 0;
        $isSuperAdmin = isset($_SESSION['admin']) && $_SESSION['admin']['role'] === 'super_admin';
        
        if (!$isInitialSetup && !$isSuperAdmin) {
            return ['ok' => false, 'msg' => 'Only Super Admin can create new admin accounts'];
        }

        // Sanitize and validate email
        $email = strtolower(trim($input['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Invalid email'];
        }

        // Check if admin email already exists
        if (Admin::findByEmail($email)) {
            return ['ok' => false, 'msg' => 'Admin email already exists'];
        }

        // Validate password
        $password = $input['password'] ?? '';
        if (strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'Password must be at least 6 characters'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Determine role and creator
        $role = $isInitialSetup ? 'super_admin' : 'admin';
        $createdBy = $isInitialSetup ? null : $_SESSION['admin']['id'];

        // Create admin
        $adminId = Admin::create([
            'name' => trim($input['name'] ?? ''),
            'email' => $email,
            'password_hash' => $hash,
            'role' => $role,
            'created_by' => $createdBy
        ]);

        if ($adminId) {
            $message = $isInitialSetup ? 'Super Admin account created successfully' : 'Admin account created successfully';
            return ['ok' => true, 'msg' => $message, 'admin_id' => $adminId];
        }

        return ['ok' => false, 'msg' => 'Could not create admin account'];
    }

    // Deactivate admin (only super admin)
    public static function deactivateAdmin($adminId)
    {
        if (!isset($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'super_admin') {
            return ['ok' => false, 'msg' => 'Only Super Admin can deactivate admin accounts'];
        }

        $result = Admin::deactivateAdmin($adminId);
        if ($result) {
            return ['ok' => true, 'msg' => 'Admin account deactivated successfully'];
        }

        return ['ok' => false, 'msg' => 'Failed to deactivate admin account'];
    }

    // Approve employer
    public static function approveEmployer($employerId)
    {
        if (!isset($_SESSION['admin'])) {
            return ['ok' => false, 'msg' => 'Admin not logged in'];
        }

        $result = Admin::approveEmployer($employerId);
        if ($result) {
            return ['ok' => true, 'msg' => 'Employer approved successfully'];
        }

        return ['ok' => false, 'msg' => 'Failed to approve employer'];
    }

    // Reject employer
    public static function rejectEmployer($employerId, $reason = null)
    {
        if (!isset($_SESSION['admin'])) {
            return ['ok' => false, 'msg' => 'Admin not logged in'];
        }

        $result = Admin::rejectEmployer($employerId, $reason);
        if ($result) {
            return ['ok' => true, 'msg' => 'Employer rejected successfully'];
        }

        return ['ok' => false, 'msg' => 'Failed to reject employer'];
    }
}