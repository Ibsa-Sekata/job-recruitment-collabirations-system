<?php
// public/applications/withdraw.php
require_once __DIR__ . '/../../src/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    flash('error', 'Please login first');
    header('Location: ../auth/login.php');
    exit;
}

// Only jobseekers can withdraw applications
if ($_SESSION['user']['role'] !== 'jobseeker') {
    flash('error', 'Only jobseekers can withdraw applications');
    header('Location: ../index.php');
    exit;
}

// Get application ID
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($applicationId <= 0) {
    flash('error', 'Invalid application ID');
    header('Location: my-applications.php');
    exit;
}

try {
    $pdo = getPDO();
    $userId = $_SESSION['user']['id'];

    // Debug: Check what we're getting
    error_log("Withdraw attempt - User ID: $userId, Application ID: $applicationId");

    // First, check if application exists and belongs to this user
    $checkSql = "SELECT id, resume_path, status FROM applications WHERE id = ? AND user_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$applicationId, $userId]);
    $application = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        // Application doesn't exist or doesn't belong to user
        error_log("Application not found or unauthorized - ID: $applicationId, User ID: $userId");
        flash('error', 'You are not authorized to withdraw this application or it does not exist.');
        header('Location: my-applications.php');
        exit;
    }

    // Debug: Found application
    error_log("Application found: " . print_r($application, true));

    // Check if application can be withdrawn (only 'submitted' status)
    if ($application['status'] !== 'submitted') {
        flash('error', 'Only submitted applications can be withdrawn.');
        header('Location: my-applications.php');
        exit;
    }

    // Delete the application from database
    $deleteSql = "DELETE FROM applications WHERE id = ? AND user_id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([$applicationId, $userId]);

    if ($result && $deleteStmt->rowCount() > 0) {
        // Successfully deleted

        // Delete the uploaded resume file if exists
        if (!empty($application['resume_path'])) {
            $resumePath = realpath(__DIR__ . '/../../' . ltrim($application['resume_path'], '/'));
            if ($resumePath && file_exists($resumePath)) {
                unlink($resumePath);
                error_log("Deleted resume file: " . $resumePath);
            }
        }

        flash('success', 'Application withdrawn successfully.');
    } else {
        // Failed to delete
        flash('error', 'Failed to withdraw application. Please try again.');
    }
} catch (Exception $e) {
    error_log("Withdraw error: " . $e->getMessage());
    flash('error', 'An error occurred while withdrawing application: ' . $e->getMessage());
}

// Redirect back to applications page
header('Location: my-applications.php');
exit;
