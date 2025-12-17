<?php
// public/view-resume.php
require_once __DIR__ . '/../src/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access denied - Not logged in';
    exit;
}

// Get resume path from URL
$resumePath = $_GET['path'] ?? '';

if (empty($resumePath)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Resume path is required';
    exit;
}

// Remove any leading slashes and clean the path
$resumePath = ltrim($resumePath, '/');

// Try to find the file in the uploads directory
$baseDir = realpath(__DIR__ . '/../uploads/');

if (!$baseDir) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Uploads directory not configured';
    exit;
}

// Try different possible file locations
$possiblePaths = [
    $baseDir . '/resumes/' . $resumePath,
    $baseDir . '/resumes/' . basename($resumePath),
    $baseDir . '/' . $resumePath,
    $baseDir . '/' . basename($resumePath),
];

$foundPath = null;
foreach ($possiblePaths as $testPath) {
    if (file_exists($testPath) && is_file($testPath)) {
        $foundPath = $testPath;
        break;
    }
}

if (!$foundPath) {
    header('HTTP/1.0 404 Not Found');
    echo 'Resume file not found: ' . htmlspecialchars($resumePath);
    exit;
}

// Verify user has permission to view this file
$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];
$pdo = getPDO();

// Get just the filename for database lookup
$fileName = basename($foundPath);

// Check database for permission
$hasPermission = false;

if ($role === 'jobseeker') {
    // Jobseekers can view their own resumes
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND (resume_path LIKE ? OR resume_path LIKE ? OR resume_path LIKE ?)");

    // Try different pattern matches
    $patterns = [
        '%' . $fileName,
        '%/' . $fileName,
        $fileName
    ];

    foreach ($patterns as $pattern) {
        // Pass all 4 parameters for the 4 placeholders
        $stmt->execute([$userId, $pattern, $pattern, $pattern]);
        if ($stmt->fetch()) {
            $hasPermission = true;
            break;
        }
    }
} elseif ($role === 'employer') {
    // Employers can view resumes for their jobs
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN employers e ON j.employer_id = e.id
        WHERE e.user_id = ? 
        AND (a.resume_path LIKE ? OR a.resume_path LIKE ? OR a.resume_path LIKE ?)
    ");

    $patterns = [
        '%' . $fileName,
        '%/' . $fileName,
        $fileName
    ];

    foreach ($patterns as $pattern) {
        // Pass all 4 parameters for the 4 placeholders
        $stmt->execute([$userId, $pattern, $pattern, $pattern]);
        if ($stmt->fetch()) {
            $hasPermission = true;
            break;
        }
    }
}

if (!$hasPermission) {
    header('HTTP/1.0 403 Forbidden');
    echo 'You do not have permission to view this resume';
    exit;
}

// Get file info and serve the file
$fileInfo = pathinfo($foundPath);
$fileName = $fileInfo['basename'];
$fileSize = filesize($foundPath);

// Set content type
$extension = strtolower($fileInfo['extension'] ?? '');
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'rtf' => 'application/rtf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Output headers and file
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline; filename="' . $fileName . '"');

readfile($foundPath);
exit;
