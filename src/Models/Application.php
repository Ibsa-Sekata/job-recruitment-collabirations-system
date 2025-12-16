<?php
require_once __DIR__ . '/../helpers.php';

class Application
{
    protected static function pdo()
    {
        return getPDO();
    }

    // Create application
    public static function create(array $data)
    {
        $pdo = self::pdo();

        // Check what date column exists in the table
        $dateColumn = self::getDateColumnName();

        $stmt = $pdo->prepare(
            "INSERT INTO applications (job_id, user_id, resume_path, cover_letter, status, $dateColumn)
             VALUES (:job_id, :user_id, :resume_path, :cover_letter, :status, :date_value)"
        );

        if (!isset($data['status'])) {
            $data['status'] = 'submitted';
        }

        $params = [
            'job_id' => $data['job_id'],
            'user_id' => $data['user_id'],
            'resume_path' => $data['resume_path'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'status' => $data['status'],
            'date_value' => date('Y-m-d H:i:s')
        ];

        return $stmt->execute($params);
    }

    // Helper method to get the correct date column name
    private static function getDateColumnName()
    {
        $pdo = self::pdo();

        try {
            // Check if applied_at column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM applications LIKE 'applied_at'");
            if ($stmt->fetch()) {
                return 'applied_at';
            }

            // Check if created_at column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM applications LIKE 'created_at'");
            if ($stmt->fetch()) {
                return 'created_at';
            }

            // Default to applied_at
            return 'applied_at';
        } catch (Exception $e) {
            return 'applied_at';
        }
    }

    // Get applications by user - Universal version that works with both column names
    public static function findByUser($user_id)
    {
        $pdo = self::pdo();

        try {
            // First try with explicit column selection
            $stmt = $pdo->prepare(
                "SELECT 
                    a.id,
                    a.job_id,
                    a.user_id,
                    a.resume_path,
                    a.cover_letter,
                    a.status,
                    COALESCE(a.applied_at, a.created_at) as applied_date,
                    j.title as job_title,
                    j.location,
                    u.name as employer_name
                 FROM applications a
                 JOIN jobs j ON a.job_id = j.id
                 JOIN users u ON j.employer_id = u.id
                 WHERE a.user_id = :user_id
                 ORDER BY COALESCE(a.applied_at, a.created_at) DESC"
            );
            $stmt->execute(['user_id' => $user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ensure applied_at exists in results
            foreach ($results as &$result) {
                if (!isset($result['applied_at']) && isset($result['applied_date'])) {
                    $result['applied_at'] = $result['applied_date'];
                }
                // Also set created_at for compatibility
                if (!isset($result['created_at']) && isset($result['applied_date'])) {
                    $result['created_at'] = $result['applied_date'];
                }
            }

            return $results;
        } catch (PDOException $e) {
            // Fallback: try simpler query
            try {
                $stmt = $pdo->prepare(
                    "SELECT a.*, j.title as job_title, j.location, u.name as employer_name
                     FROM applications a
                     JOIN jobs j ON a.job_id = j.id
                     JOIN users u ON j.employer_id = u.id
                     WHERE a.user_id = :user_id
                     ORDER BY a.id DESC"
                );
                $stmt->execute(['user_id' => $user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                error_log("Application findByUser error: " . $e2->getMessage());
                return [];
            }
        }
    }

    // Get applications for employer's jobs
    public static function findByEmployer($employer_id)
    {
        $pdo = self::pdo();

        try {
            $stmt = $pdo->prepare(
                "SELECT 
                    a.id,
                    a.job_id,
                    a.user_id,
                    a.resume_path,
                    a.cover_letter,
                    a.status,
                    COALESCE(a.applied_at, a.created_at) as applied_date,
                    j.title as job_title,
                    u.name as applicant_name,
                    u.email,
                    u.phone
                 FROM applications a
                 JOIN jobs j ON a.job_id = j.id
                 JOIN users u ON a.user_id = u.id
                 WHERE j.employer_id = :employer_id
                 ORDER BY COALESCE(a.applied_at, a.created_at) DESC"
            );
            $stmt->execute(['employer_id' => $employer_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ensure applied_at exists in results
            foreach ($results as &$result) {
                if (!isset($result['applied_at']) && isset($result['applied_date'])) {
                    $result['applied_at'] = $result['applied_date'];
                }
            }

            return $results;
        } catch (PDOException $e) {
            // Fallback
            try {
                $stmt = $pdo->prepare(
                    "SELECT a.*, j.title as job_title, u.name as applicant_name, u.email, u.phone
                     FROM applications a
                     JOIN jobs j ON a.job_id = j.id
                     JOIN users u ON a.user_id = u.id
                     WHERE j.employer_id = :employer_id
                     ORDER BY a.id DESC"
                );
                $stmt->execute(['employer_id' => $employer_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                error_log("Application findByEmployer error: " . $e2->getMessage());
                return [];
            }
        }
    }
    public static function findForEmployer($applicationId, $employerId)
    {
        $pdo = self::pdo();

        $sql = "SELECT a.*, 
                   j.title as job_title, 
                   j.description as job_description,
                   j.location as job_location,
                   u.name as applicant_name, 
                   u.email, 
                   u.phone
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = :id AND j.employer_id = :employer_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $applicationId, 'employer_id' => $employerId]);
        return $stmt->fetch();
    }
    // Get application by ID
    public static function find($id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update application status
    public static function updateStatus($id, $status, $employer_id = null)
    {
        $pdo = self::pdo();

        if ($employer_id) {
            // Verify employer owns the job
            $sql = "UPDATE applications a
                    JOIN jobs j ON a.job_id = j.id
                    SET a.status = :status
                    WHERE a.id = :id AND j.employer_id = :employer_id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute(['status' => $status, 'id' => $id, 'employer_id' => $employer_id]);
        }

        $stmt = $pdo->prepare("UPDATE applications SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    // Check if user has already applied
    public static function hasApplied($job_id, $user_id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM applications WHERE job_id = :job_id AND user_id = :user_id"
        );
        $stmt->execute(['job_id' => $job_id, 'user_id' => $user_id]);
        return $stmt->fetch() !== false;
    }

    // Delete application
    public static function delete($id, $userId = null)
    {
        $pdo = self::pdo();

        if ($userId) {
            // Verify ownership
            $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND user_id = ?");
            return $stmt->execute([$id, $userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
            return $stmt->execute([$id]);
        }
    }

    // Get application statistics for employer
    public static function getStats($employer_id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN a.status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN a.status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected
             FROM applications a
             JOIN jobs j ON a.job_id = j.id
             WHERE j.employer_id = :employer_id"
        );
        $stmt->execute(['employer_id' => $employer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Alternative method name for compatibility
    public static function findByUserCompatible($user_id)
    {
        return self::findByUser($user_id);
    }
}
