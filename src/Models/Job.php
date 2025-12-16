<?php
require_once __DIR__ . '/../helpers.php';

class Job
{
    protected static function pdo()
    {
        return getPDO();
    }

    // Insert a new job - FIXED to return job ID
    public static function create(array $data)
    {
        $pdo = self::pdo();

        // Set default values matching your table structure
        $defaults = [
            'summary' => '',
            'requirements' => '',
            'skills' => '',
            'job_type' => 'full-time',
            'experience_level' => 'mid',
            'education_level' => null,
            'salary_range' => '',
            'application_deadline' => null,
            'status' => 'active',
            'visibility' => 'public',
            'views' => 0
        ];

        $mergedData = array_merge($defaults, $data);

        // Remove any fields that don't exist in the table
        $allowedFields = [
            'employer_id',
            'title',
            'summary',
            'description',
            'requirements',
            'skills',
            'job_type',
            'experience_level',
            'education_level',
            'location',
            'salary_range',
            'application_deadline',
            'status',
            'visibility',
            'views'
        ];

        $filteredData = array_intersect_key($mergedData, array_flip($allowedFields));

        $columns = implode(', ', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));

        $sql = "INSERT INTO jobs ($columns) VALUES ($placeholders)";

        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($filteredData);

            if ($result) {
                return $pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Job creation error: " . $e->getMessage());
            return false;
        }
    }

    // Fetch all jobs (for index.php)
    public static function all()
    {
        $pdo = self::pdo();
        $stmt = $pdo->query(
            "SELECT j.*, e.company_name
             FROM jobs j
             LEFT JOIN employers e ON j.employer_id = e.id
             WHERE j.status = 'active'
             ORDER BY j.created_at DESC
             LIMIT 8"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all jobs with pagination
    public static function getAll($limit = null, $offset = 0)
    {
        $pdo = self::pdo();
        $sql = "SELECT j.*, e.company_name
                FROM jobs j
                LEFT JOIN employers e ON j.employer_id = e.id
                WHERE j.status = 'active'
                ORDER BY j.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch jobs for a single employer
    public static function allByEmployer($employer_id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "SELECT j.*, COUNT(a.id) as application_count 
             FROM jobs j 
             LEFT JOIN applications a ON j.id = a.job_id 
             WHERE j.employer_id = :id 
             GROUP BY j.id 
             ORDER BY j.created_at DESC"
        );
        $stmt->execute(['id' => $employer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch jobs by user ID (helper method)
    public static function allByUserId($userId)
    {
        // First get employer ID from user ID
        require_once __DIR__ . '/Employer.php';
        $employer = Employer::getByUserId($userId);

        if (!$employer) {
            return [];
        }

        return self::allByEmployer($employer['id']);
    }

    // Find job by ID - FIXED: Removed e.company_description
    public static function find($id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "SELECT j.*, e.company_name
             FROM jobs j
             LEFT JOIN employers e ON j.employer_id = e.id
             WHERE j.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update job
    public static function update($id, array $data)
    {
        $pdo = self::pdo();
        $set = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            // Handle empty date fields - convert to NULL
            if ($key === 'application_deadline' && empty($value)) {
                $value = null;
            }

            $set[] = "$key = :$key";
            $params[$key] = $value;
        }

        $sql = "UPDATE jobs SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    // Delete job (with employer verification)
    public static function delete($id, $employer_id = null)
    {
        $pdo = self::pdo();

        if ($employer_id) {
            // Verify employer owns the job
            $check = $pdo->prepare("SELECT id FROM jobs WHERE id = :id AND employer_id = :employer_id");
            $check->execute(['id' => $id, 'employer_id' => $employer_id]);

            if (!$check->fetch()) {
                return false; // Not authorized
            }
        }

        // Delete related applications first
        $stmt = $pdo->prepare("DELETE FROM applications WHERE job_id = :id");
        $stmt->execute(['id' => $id]);

        // Delete the job
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Search jobs
    public static function search($query, $location = null, $type = null)
    {
        $pdo = self::pdo();

        $sql = "SELECT j.*, e.company_name
                FROM jobs j
                LEFT JOIN employers e ON j.employer_id = e.id
                WHERE j.status = 'active'";

        $params = [];
        $conditions = [];

        // Add search conditions
        if (!empty($query)) {
            $conditions[] = "(j.title LIKE :query OR j.description LIKE :query_desc OR j.skills LIKE :query_skills)";
            $searchTerm = "%$query%";
            $params[':query'] = $searchTerm;
            $params[':query_desc'] = $searchTerm;
            $params[':query_skills'] = $searchTerm;
        }

        if (!empty($location)) {
            $conditions[] = "j.location LIKE :location";
            $params[':location'] = "%$location%";
        }

        if (!empty($type) && $type !== 'all') {
            $conditions[] = "j.job_type = :type";
            $params[':type'] = $type;
        }

        // Add WHERE conditions if any
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY j.created_at DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }

    // Get job applications
    public static function getApplications($job_id, $employer_id = null)
    {
        $pdo = self::pdo();

        $sql = "SELECT a.*, u.name, u.email, u.phone
                FROM applications a
                JOIN users u ON a.user_id = u.id
                WHERE a.job_id = :job_id";

        if ($employer_id) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM jobs j 
                WHERE j.id = a.job_id AND j.employer_id = :employer_id
            )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['job_id' => $job_id, 'employer_id' => $employer_id]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['job_id' => $job_id]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Toggle job status
    public static function toggleStatus($id, $employer_id = null)
    {
        $pdo = self::pdo();

        if ($employer_id) {
            // Get current status
            $stmt = $pdo->prepare("SELECT status FROM jobs WHERE id = :id AND employer_id = :employer_id");
            $stmt->execute(['id' => $id, 'employer_id' => $employer_id]);
            $job = $stmt->fetch();

            if (!$job) {
                return false;
            }

            $newStatus = $job['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE jobs SET status = :status WHERE id = :id AND employer_id = :employer_id");
            return $stmt->execute(['status' => $newStatus, 'id' => $id, 'employer_id' => $employer_id]);
        }

        return false;
    }

    // Check if job belongs to employer
    public static function belongsToEmployer($jobId, $employerId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
        $stmt->execute([$jobId, $employerId]);
        return $stmt->fetch() !== false;
    }

    // Get job count for employer
    public static function countByEmployer($employerId)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE employer_id = ?");
        $stmt->execute([$employerId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    // Get popular jobs (most viewed)
    public static function getPopular($limit = 5)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare(
            "SELECT j.*, e.company_name 
             FROM jobs j 
             LEFT JOIN employers e ON j.employer_id = e.id 
             WHERE j.status = 'active' 
             ORDER BY j.views DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get jobs by type
    public static function getByType($type, $limit = null)
    {
        $pdo = self::pdo();
        $sql = "SELECT j.*, e.company_name 
                FROM jobs j 
                LEFT JOIN employers e ON j.employer_id = e.id 
                WHERE j.status = 'active' AND j.job_type = ? 
                ORDER BY j.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$type, $limit]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$type]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Increment views count
    public static function incrementViews($id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
