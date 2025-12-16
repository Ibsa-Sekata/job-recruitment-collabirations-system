<?php
// Controllers/JobController.php
require_once __DIR__ . '/../Models/Job.php';
require_once __DIR__ . '/../Models/Employer.php';

class JobController
{
    public static function postJob(array $input)
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employer') {
            return ['ok' => false, 'msg' => 'Unauthorized'];
        }

        // Get the employer ID using the user ID
        $userId = $_SESSION['user']['id'];
        $employer = Employer::getByUserId($userId);

        if (!$employer) {
            return ['ok' => false, 'msg' => 'Employer profile not found. Please complete your company profile first.'];
        }

        // Check if employer is approved
        if ($employer['approval_status'] !== 'approved') {
            $statusMsg = $employer['approval_status'] === 'pending' 
                ? 'Your account is pending admin approval.' 
                : 'Your account has been rejected.';
            return ['ok' => false, 'msg' => 'Cannot post jobs. ' . $statusMsg];
        }

        // Prepare data for insertion - MATCHING YOUR TABLE COLUMNS
        $data = [
            'employer_id' => $employer['id'],
            'title' => trim($input['title'] ?? ''),
            'summary' => trim($input['summary'] ?? ''), // Added
            'description' => trim($input['description'] ?? ''),
            'requirements' => trim($input['requirements'] ?? ''), // Added
            'skills' => trim($input['skills'] ?? ''), // Added
            'job_type' => $input['job_type'] ?? 'full-time',
            'experience_level' => $input['experience_level'] ?? 'mid',
            'education_level' => $input['education_level'] ?? '', // Added
            'location' => trim($input['location'] ?? ''),
            'salary_range' => trim($input['salary_range'] ?? ''),
            'application_deadline' => $input['application_deadline'] ?? null, // Added
            'status' => 'active',
            'visibility' => $input['visibility'] ?? 'public' // Added
            // created_at and updated_at will be set automatically
        ];

        // Use direct database insertion
        try {
            $pdo = getPDO();

            $sql = "INSERT INTO jobs (
            employer_id, title, summary, description, requirements, skills, 
            job_type, experience_level, education_level, location, salary_range, 
            application_deadline, status, visibility
        ) VALUES (
            :employer_id, :title, :summary, :description, :requirements, :skills,
            :job_type, :experience_level, :education_level, :location, :salary_range,
            :application_deadline, :status, :visibility
        )";

            $stmt = $pdo->prepare($sql);

            // Convert empty string to null for optional fields
            if (empty($data['application_deadline'])) {
                $data['application_deadline'] = null;
            }
            if (empty($data['education_level'])) {
                $data['education_level'] = null;
            }

            $result = $stmt->execute($data);

            if ($result) {
                $jobId = $pdo->lastInsertId();
                return ['ok' => true, 'msg' => 'Job posted successfully', 'job_id' => $jobId];
            } else {
                $error = $stmt->errorInfo();
                return ['ok' => false, 'msg' => 'Database error: ' . ($error[2] ?? 'Unknown error')];
            }
        } catch (PDOException $e) {
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    // ... rest of your code
}
