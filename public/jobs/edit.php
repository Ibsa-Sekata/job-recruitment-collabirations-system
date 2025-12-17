<?php
// public/jobs/edit.php
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

// Check if job belongs to this employer
if (!Job::belongsToEmployer($jobId, $employer['id'])) {
    flash('error', 'You are not authorized to edit this job');
    header('Location: ../employer/dashboard.php');
    exit;
}

// Get job data
$job = Job::find($jobId);
if (!$job) {
    flash('error', 'Job not found');
    header('Location: ../employer/dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle empty date field
    $applicationDeadline = trim($_POST['application_deadline'] ?? '');
    if (empty($applicationDeadline)) {
        $applicationDeadline = null;
    }

    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'summary' => trim($_POST['summary'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'requirements' => trim($_POST['requirements'] ?? ''),
        'skills' => trim($_POST['skills'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'salary_range' => trim($_POST['salary_range'] ?? ''),
        'job_type' => $_POST['job_type'] ?? 'full-time',
        'experience_level' => $_POST['experience_level'] ?? 'mid',
        'education_level' => trim($_POST['education_level'] ?? ''),
        'application_deadline' => $applicationDeadline, // Use converted value
        'visibility' => $_POST['visibility'] ?? 'public',
        'status' => $_POST['status'] ?? 'active'
    ];

    $updated = Job::update($jobId, $data);

    if ($updated) {
        flash('success', 'Job updated successfully');
        header('Location: ../employer/dashboard.php');
        exit;
    } else {
        $error = 'Failed to update job';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Edit Job - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <style>
        .form-row {
            margin-bottom: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="brand">JobRecruit</a>
            <div class="nav-right">
                <a href="../employer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 800px;">
        <h1>Edit Job: <?= htmlspecialchars($job['title']) ?></h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <input class="input" type="text" name="title" placeholder="Job Title *" required
                    value="<?= htmlspecialchars($job['title']) ?>">
            </div>

            <div class="form-row">
                <textarea class="input" name="summary" placeholder="Short Summary (Optional)"
                    rows="3"><?= htmlspecialchars($job['summary'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <textarea class="input" name="description" placeholder="Job Description *" rows="6"
                    required><?= htmlspecialchars($job['description']) ?></textarea>
            </div>

            <div class="form-row">
                <textarea class="input" name="requirements" placeholder="Requirements (Optional)"
                    rows="4"><?= htmlspecialchars($job['requirements'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <textarea class="input" name="skills" placeholder="Skills (comma separated, Optional)"
                    rows="3"><?= htmlspecialchars($job['skills'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <input class="input" type="text" name="location" placeholder="Location *" required
                    value="<?= htmlspecialchars($job['location']) ?>">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <input class="input" type="text" name="salary_range" placeholder="Salary Range"
                    value="<?= htmlspecialchars($job['salary_range'] ?? '') ?>">
                <select class="input" name="job_type">
                    <option value="full-time" <?= ($job['job_type'] ?? 'full-time') == 'full-time' ? 'selected' : '' ?>>
                        Full Time</option>
                    <option value="part-time" <?= ($job['job_type'] ?? 'full-time') == 'part-time' ? 'selected' : '' ?>>
                        Part Time</option>
                    <option value="contract" <?= ($job['job_type'] ?? 'full-time') == 'contract' ? 'selected' : '' ?>>
                        Contract</option>
                    <option value="remote" <?= ($job['job_type'] ?? 'full-time') == 'remote' ? 'selected' : '' ?>>Remote
                    </option>
                    <option value="internship"
                        <?= ($job['job_type'] ?? 'full-time') == 'internship' ? 'selected' : '' ?>>Internship</option>
                </select>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <select class="input" name="experience_level">
                    <option value="entry" <?= ($job['experience_level'] ?? 'mid') == 'entry' ? 'selected' : '' ?>>Entry
                        Level</option>
                    <option value="mid" <?= ($job['experience_level'] ?? 'mid') == 'mid' ? 'selected' : '' ?>>Mid Level
                    </option>
                    <option value="senior" <?= ($job['experience_level'] ?? 'mid') == 'senior' ? 'selected' : '' ?>>
                        Senior Level</option>
                    <option value="executive"
                        <?= ($job['experience_level'] ?? 'mid') == 'executive' ? 'selected' : '' ?>>Executive</option>
                </select>
                <input class="input" type="text" name="education_level" placeholder="Education Level"
                    value="<?= htmlspecialchars($job['education_level'] ?? '') ?>">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <input class="input" type="date" name="application_deadline"
                    value="<?= htmlspecialchars($job['application_deadline'] ?? '') ?>">
                <select class="input" name="visibility">
                    <option value="public" <?= ($job['visibility'] ?? 'public') == 'public' ? 'selected' : '' ?>>Public
                    </option>
                    <option value="private" <?= ($job['visibility'] ?? 'public') == 'private' ? 'selected' : '' ?>>
                        Private</option>
                </select>
            </div>

            <div class="form-row">
                <select class="input" name="status">
                    <option value="active" <?= ($job['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active
                    </option>
                    <option value="inactive" <?= ($job['status'] ?? 'active') == 'inactive' ? 'selected' : '' ?>>
                        Inactive</option>
                    <option value="closed" <?= ($job['status'] ?? 'active') == 'closed' ? 'selected' : '' ?>>Closed
                    </option>
                    <option value="draft" <?= ($job['status'] ?? 'active') == 'draft' ? 'selected' : '' ?>>Draft
                    </option>
                </select>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Job
                </button>
                <a href="../employer/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</body>

</html>