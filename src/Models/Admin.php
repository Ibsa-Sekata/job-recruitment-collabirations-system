<?php
require_once __DIR__ . '/../helpers.php';

class Admin
{
    protected static function pdo()
    {
        return getPDO();
    }

    public static function create($data)
    {
        $pdo = self::pdo();
        $sql = "INSERT INTO admins (name, email, password_hash, role, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password_hash'],
            $data['role'] ?? 'admin',
            $data['created_by'] ?? null,
            date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    // Check if user is super admin
    public static function isSuperAdmin($adminId)
    {
        $pdo = self::pdo();
        
        // Check if role column exists first
        try {
            $stmt = $pdo->prepare("SELECT role, is_active FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $result = $stmt->fetch();
            
            if ($result) {
                $isActive = !isset($result['is_active']) || $result['is_active'];
                $role = $result['role'] ?? 'admin';
                return $isActive && $role === 'super_admin';
            }
        } catch (PDOException $e) {
            // If columns don't exist, assume it's the old structure
            // In old structure, first admin is super admin
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$adminId]);
            $firstAdmin = $pdo->query("SELECT id FROM admins ORDER BY id ASC LIMIT 1")->fetch();
            return $firstAdmin && $firstAdmin['id'] == $adminId;
        }
        
        return false;
    }

    // Get all admins (only for super admin)
    public static function getAllAdmins()
    {
        $pdo = self::pdo();
        
        try {
            // Try new structure first
            $stmt = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM admins ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback to old structure
            $stmt = $pdo->query("SELECT id, name, email, created_at, 'admin' as role, 1 as is_active FROM admins ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Deactivate admin (only super admin can do this)
    public static function deactivateAdmin($adminId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("UPDATE admins SET is_active = 0 WHERE id = ? AND role != 'super_admin'");
        return $stmt->execute([$adminId]);
    }

    // Activate admin
    public static function activateAdmin($adminId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("UPDATE admins SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$adminId]);
    }

    public static function findByEmail($email)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function verifyPassword($email, $password)
    {
        $admin = self::findByEmail($email);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Check if is_active column exists (backward compatibility)
            $isActive = !isset($admin['is_active']) || $admin['is_active'];
            if ($isActive) {
                return $admin;
            }
        }
        return false;
    }

    // Get pending employer registrations
    public static function getPendingEmployers()
    {
        $pdo = self::pdo();
        $sql = "SELECT e.*, u.name as user_name, u.email, u.phone, u.created_at as user_created_at 
                FROM employers e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.approval_status = 'pending' 
                ORDER BY e.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all employers with their approval status
    public static function getAllEmployers()
    {
        $pdo = self::pdo();
        $sql = "SELECT e.*, u.name as user_name, u.email, u.phone, u.created_at as user_created_at 
                FROM employers e 
                JOIN users u ON e.user_id = u.id 
                ORDER BY e.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Approve employer registration
    public static function approveEmployer($employerId)
    {
        $pdo = self::pdo();
        $sql = "UPDATE employers SET approval_status = 'approved', approved_at = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([date('Y-m-d H:i:s'), $employerId]);
    }

    // Reject employer registration
    public static function rejectEmployer($employerId, $reason = null)
    {
        $pdo = self::pdo();
        $sql = "UPDATE employers SET approval_status = 'rejected', rejection_reason = ?, approved_at = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$reason, date('Y-m-d H:i:s'), $employerId]);
    }

    // Get dashboard statistics
    public static function getDashboardStats()
    {
        $pdo = self::pdo();
        
        $stats = [];
        
        // Total employers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employers");
        $stats['total_employers'] = $stmt->fetch()['count'];
        
        // Pending employers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employers WHERE approval_status = 'pending'");
        $stats['pending_employers'] = $stmt->fetch()['count'];
        
        // Approved employers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employers WHERE approval_status = 'approved'");
        $stats['approved_employers'] = $stmt->fetch()['count'];
        
        // Total jobs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs");
        $stats['total_jobs'] = $stmt->fetch()['count'];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        return $stats;
    }
}