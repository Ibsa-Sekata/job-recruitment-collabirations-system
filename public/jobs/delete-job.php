<?php
// public/jobs/delete-job.php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/Employer.php';

// Check if user is logged in and is employer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employer') {
    flash('error', 'Unauthorized');
    header('Location: ../auth/login.php');
    exit;
}

$jobId = $_GET['id'] ?? 0;

if (!$jobId) {
    flash('error', 'Job ID is required');
    header('Location: ../employer/dashboard.php');
    exit;
}

// Get employer ID
$employer = Employer::getByUserId($_SESSION['user']['id']);
if (!$employer) {
    flash('error', 'Employer profile not found');
    header('Location: ../employer/dashboard.php');
    exit;
}

// Delete the job
$deleted = Job::delete($jobId, $employer['id']);

if ($deleted) {
    flash('success', 'Job deleted successfully');
} else {
    flash('error', 'Failed to delete job or unauthorized');
}

header('Location: ../employer/dashboard.php');
exit;
