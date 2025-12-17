<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/Employer.php';

requireRole('employer');

// Check if ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = 'Invalid request. Missing parameters.';
    header('Location: dashboard.php');
    exit;
}

$applicationId = $_GET['id'];
$newStatus = $_GET['status'];

// Valid statuses
$validStatuses = ['submitted', 'under_review', 'shortlisted', 'accepted', 'rejected'];
if (!in_array($newStatus, $validStatuses)) {
    $_SESSION['error'] = 'Invalid status value.';
    header('Location: dashboard.php');
    exit;
}

// Get employer ID from user ID
$employer = Employer::getByUserId($_SESSION['user']['id']);
if (!$employer) {
    $_SESSION['error'] = 'Employer profile not found.';
    header('Location: dashboard.php');
    exit;
}

$employerId = $employer['id'];

// Update the application status with employer verification
$success = Application::updateStatus($applicationId, $newStatus, $employerId);

if ($success) {
    $_SESSION['success'] = 'Application status updated successfully!';
} else {
    $_SESSION['error'] = 'Failed to update application status. You may not have permission to update this application.';
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit;
