<?php
require_once __DIR__ . '/../helpers.php';

class Employer
{
    protected static function pdo()
    {
        return getPDO();
    }

    public static function create($data)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO employers (user_id, company_name, website, industry, company_size, address, logo, verified, approval_status, created_at)
             VALUES (:user_id, :company_name, :website, :industry, :company_size, :address, :logo, :verified, :approval_status, :created_at)"
        );

        // Set default values if missing
        $defaults = [
            'website' => '',
            'industry' => '',
            'company_size' => '',
            'address' => '',
            'logo' => '',
            'verified' => 0,
            'approval_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $mergedData = array_merge($defaults, $data);

        return $stmt->execute($mergedData);
    }

    // Get employer by user ID - FIXED
    public static function getByUserId($userId)
    {
        $pdo = self::pdo(); // Use pdo() method instead of Database::getConnection()
        $stmt = $pdo->prepare("SELECT * FROM employers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get employer by employer ID
    public static function getById($employerId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM employers WHERE id = ?");
        $stmt->execute([$employerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function update($user_id, $fields)
    {
        $pdo = self::pdo();
        $set = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $set[] = "$k = ?";
            $params[] = $v;
        }
        $params[] = $user_id;
        $sql = "UPDATE employers SET " . implode(',', $set) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function getJobsCount($user_id)
    {
        $pdo = self::pdo();

        // First get the employer ID from user_id
        $employer = self::getByUserId($user_id);
        if (!$employer) {
            return 0;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE employer_id = ?");
        $stmt->execute([$employer['id']]); // Use employer ID, not user ID
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    // Add this method to get company name by user ID
    public static function getCompanyName($userId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT company_name FROM employers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['company_name'] ?? '';
    }

    // Get all employers
    public static function getAll()
    {
        $pdo = self::pdo();
        $stmt = $pdo->query("SELECT * FROM employers ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if employer is approved
    public static function isApproved($userId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT approval_status FROM employers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['approval_status'] === 'approved';
    }
}
