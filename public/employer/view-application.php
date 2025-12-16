<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/Employer.php';

requireRole('employer');

// Get employer ID from user ID
$employer = Employer::getByUserId($_SESSION['user']['id']);
if (!$employer) {
    $_SESSION['error'] = 'Employer profile not found.';
    header('Location: dashboard.php');
    exit;
}
$employerId = $employer['id'];

// Normalize application ID and load record
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch application with employer verification - use a method that joins with job
$application = Application::findForEmployer($applicationId, $employerId);

if (!$application) {
    $_SESSION['error'] = 'Application not found or you do not have permission to view it.';
    header('Location: dashboard.php');
    exit;
}

// Allowed statuses - used for validation and normalized display
$allowedStatuses = ['submitted', 'under_review', 'shortlisted', 'accepted', 'rejected'];

// Update status if requested and valid
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if (in_array($status, $allowedStatuses, true)) {
        if (Application::updateStatus($applicationId, $status, $employerId)) {
            $_SESSION['success'] = 'Application status updated successfully!';
            header("Location: view-application.php?id=$applicationId");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update application status.';
        }
    } else {
        $_SESSION['error'] = 'Invalid status value.';
        header("Location: view-application.php?id=$applicationId");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Application Details - Employer Dashboard</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .application-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .application-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .application-details {
                grid-template-columns: 1fr;
            }
        }

        .detail-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .cover-letter {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .status-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-under_review {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-shortlisted {
            background: #dcfce7;
            color: #166534;
        }

        .badge-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .info-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.75rem 1.5rem;
            margin-top: 1rem;
        }

        .info-label {
            font-weight: 500;
            color: #64748b;
        }

        .info-value {
            color: #1e293b;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-right">
                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="../auth/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="application-header">
            <h1><i class="fas fa-file-alt"></i> Application Details</h1>
            <p>Review application for: <strong><?= htmlspecialchars($application['job_title'] ?? 'Job') ?></strong></p>
        </div>

        <div class="application-details">
            <div class="detail-card">
                <h3><i class="fas fa-user"></i> Applicant Information</h3>
                <div class="info-grid">
                    <div class="info-label">Name:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['applicant_name'] ?? $application['user_name'] ?? 'N/A') ?>
                    </div>

                    <div class="info-label">Email:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['applicant_email'] ?? $application['email'] ?? 'N/A') ?></div>

                    <div class="info-label">Phone:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['phone'] ?? $application['applicant_phone'] ?? 'Not provided') ?>
                    </div>

                    <div class="info-label">Applied Date:</div>
                    <div class="info-value">
                        <?= date('F j, Y, g:i a', strtotime($application['applied_at'] ?? $application['created_at'] ?? 'now')) ?>
                    </div>
                    <div class="info-label">Status:</div>
                    <?php
                    // Normalize display status for CSS class and label
                    $displayStatus = $application['status'] ?? 'submitted';
                    if (!in_array($displayStatus, $allowedStatuses, true)) {
                        $displayStatus = 'submitted';
                    }
                    $badgeClass = 'badge-' . str_replace('_', '-', $displayStatus);
                    $badgeLabel = ucfirst(str_replace('_', ' ', $displayStatus));
                    ?>
                    <div class="info-value">
                        <span class="badge <?= htmlspecialchars($badgeClass) ?>">
                            <?= htmlspecialchars($badgeLabel) ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($application['resume_path'])): ?>
                    <?php
                    // Use the download-resume.php script to handle file download
                    ?>
                    <div style="margin-top: 1.5rem;">
                        <a href="download-resume.php?id=<?= $applicationId ?>" class="btn btn-primary"
                            onclick="return confirm('Download resume for <?= htmlspecialchars($application['applicant_name'] ?? 'applicant') ?>?')">
                            <i class="fas fa-file-download"></i> Download Resume
                        </a>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1.5rem;">
                        <span class="btn btn-secondary disabled">
                            <i class="fas fa-file-exclamation"></i> No Resume Attached
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-card">
                <h3><i class="fas fa-briefcase"></i> Job Information</h3>
                <div class="info-grid">
                    <div class="info-label">Job Title:</div>
                    <div class="info-value"><?= htmlspecialchars($application['job_title'] ?? 'N/A') ?></div>

                    <div class="info-label">Location:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['job_location'] ?? $application['location'] ?? 'N/A') ?></div>
                </div>

                <?php if (!empty($application['job_description'])): ?>
                    <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                        <strong>Job Description:</strong><br>
                        <?= nl2br(htmlspecialchars(substr($application['job_description'], 0, 200))) ?>...
                    </p>
                <?php endif; ?>

                <?php if (!empty($application['job_id'])): ?>
                    <a href="../jobs/view.php?id=<?= $application['job_id'] ?>" class="btn btn-secondary btn-sm"
                        style="margin-top: 1rem;">
                        <i class="fas fa-external-link-alt"></i> View Job Posting
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($application['cover_letter'])): ?>
            <div class="cover-letter">
                <h3><i class="fas fa-envelope"></i> Cover Letter</h3>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.75rem; margin-top: 1rem;">
                    <p style="white-space: pre-wrap; line-height: 1.6;">
                        <?= htmlspecialchars($application['cover_letter']) ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="detail-card">
                <h3><i class="fas fa-envelope"></i> Cover Letter</h3>
                <p style="color: #64748b; font-style: italic; padding: 1rem; text-align: center;">
                    No cover letter provided by the applicant.
                </p>
            </div>
        <?php endif; ?>

        <div class="status-actions">
            <h3 style="width: 100%; margin-bottom: 1rem;">Update Application Status:</h3>

            <?php
            $currentStatus = $application['status'] ?? 'submitted';
            ?>

            <?php if ($currentStatus === 'submitted'): ?>
                <a href="?id=<?= $applicationId ?>&status=under_review" class="btn btn-info">
                    <i class="fas fa-check"></i> Mark as Under Review
                </a>
                <a href="?id=<?= $applicationId ?>&status=rejected" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Application
                </a>
            <?php endif; ?>

            <?php if ($currentStatus === 'under_review'): ?>
                <a href="?id=<?= $applicationId ?>&status=shortlisted" class="btn btn-warning">
                    <i class="fas fa-star"></i> Shortlist Candidate
                </a>
                <a href="?id=<?= $applicationId ?>&status=rejected" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Application
                </a>
            <?php endif; ?>

            <?php if ($currentStatus === 'shortlisted'): ?>
                <a href="?id=<?= $applicationId ?>&status=accepted" class="btn btn-success">
                    <i class="fas fa-user-check"></i> Accept Candidate
                </a>
                <a href="?id=<?= $applicationId ?>&status=rejected" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Application
                </a>
            <?php endif; ?>

            <?php if (in_array($currentStatus, ['accepted', 'rejected'])): ?>
                <div style="width: 100%;">
                    <p
                        style="color: #64748b; font-style: italic; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                        This application has been <strong><?= $currentStatus ?></strong>.
                        <?php if (!empty($application['job_id'])): ?>
                            You can still <a href="../jobs/view.php?id=<?= $application['job_id'] ?>">view the job posting</a>
                        <?php endif; ?>
                        or <a href="dashboard.php">return to dashboard</a>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>