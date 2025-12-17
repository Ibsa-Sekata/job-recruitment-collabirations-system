<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/Employer.php';

requireRole('employer');

// Check if ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Application ID is required.');
}

$applicationId = (int)$_GET['id'];

// Get employer ID from user ID
$employer = Employer::getByUserId($_SESSION['user']['id']);
if (!$employer) {
    http_response_code(403);
    die('Employer profile not found.');
}

$employerId = $employer['id'];

// Fetch application with employer verification
$application = Application::findForEmployer($applicationId, $employerId);

if (!$application) {
    http_response_code(404);
    die('Application not found or you do not have permission to view it.');
}

// Check if resume exists
if (empty($application['resume_path'])) {
    http_response_code(404);
    die('No resume attached to this application.');
}

$resumePath = $application['resume_path'];

// Make sure the resume path is safe and points to uploads directory
// Check if path is relative or absolute
if (strpos($resumePath, '/') !== 0) {
    // Relative path - assume it's from project root
    $filePath = realpath(__DIR__ . '/../../' . $resumePath);
} else {
    // Absolute path
    $filePath = realpath($resumePath);
}

// Security check: make sure file is within project directory
$projectRoot = realpath(__DIR__ . '/../../');
if (!$filePath || strpos($filePath, $projectRoot) !== 0) {
    http_response_code(403);
    die('Invalid file path.');
}

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('Resume file not found on server.');
}

// Get file info
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Set appropriate content type
$contentTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'rtf' => 'application/rtf',
    'odt' => 'application/vnd.oasis.opendocument.text'
];

$contentType = $contentTypes[$fileExtension] ?? 'application/octet-stream';

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $fileSize);

// Clear output buffer
ob_clean();
flush();

// Read and output the file
readfile($filePath);
exit;
