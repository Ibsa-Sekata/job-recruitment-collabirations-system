<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/User.php';

$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$job = Job::find($jobId);

if (!$job) {
    header('Location: list.php');
    exit;
}

$error = '';
$success = '';
$alreadyApplied = false;

if (isLoggedIn()) {
    $alreadyApplied = Application::hasApplied($jobId, $_SESSION['user']['id']);
}

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to apply for this job.';
    } elseif ($_SESSION['user']['role'] === 'employer') {
        $error = 'Employers cannot apply for jobs.';
    } elseif ($alreadyApplied) {
        $error = 'You have already applied for this job.';
    } else {
        // Handle resume upload
        $resumePath = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resumePath = uploadFile($_FILES['resume'], 'resumes');
            if (!$resumePath) {
                $error = 'Invalid resume file. Please upload PDF, DOC, or DOCX files (max 5MB).';
            }
        } else {
            $error = 'Resume is required.';
        }

        if (!$error) {
            $coverLetter = trim($_POST['cover_letter'] ?? '');

            $applicationData = [
                'job_id' => $jobId,
                'user_id' => $_SESSION['user']['id'],
                'resume_path' => $resumePath,
                'cover_letter' => $coverLetter,
                'status' => 'submitted'
            ];

            if (Application::create($applicationData)) {
                $success = 'Application submitted successfully! The employer will review your application.';
                $alreadyApplied = true;

                // Update user's resume path if this is their first application
                $currentUser = User::findById($_SESSION['user']['id']);
                if ($currentUser && empty($currentUser['resume_path'])) {
                    User::update($_SESSION['user']['id'], ['resume_path' => $resumePath]);
                }
            } else {
                $error = 'Failed to submit application. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($job['title']) ?> - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .job-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 2rem 2rem;
    }

    .job-meta {
        display: flex;
        gap: 2rem;
        margin: 1.5rem 0;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.875rem;
    }

    .meta-item i {
        font-size: 1.25rem;
        opacity: 0.8;
    }

    .company-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .job-details {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .section-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: var(--darker);
        border-bottom: 2px solid var(--light-gray);
        padding-bottom: 0.5rem;
    }

    .job-description {
        line-height: 1.8;
        color: var(--dark);
    }

    .apply-section {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .apply-already {
        background: #d1fae5;
        color: #065f46;
        padding: 1.5rem;
        border-radius: 0.75rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 600;
        margin-left: 1rem;
    }

    .badge-full-time {
        background: #dcfce7;
        color: #166534;
    }

    .badge-part-time {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-remote {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-contract {
        background: #f3e8ff;
        color: #7c3aed;
    }

    .badge-internship {
        background: #fce7f3;
        color: #be185d;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-right">
                <?php if (isLoggedIn()): ?>
                <span>Hi, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                <?php if ($_SESSION['user']['role'] === 'employer'): ?>
                <a href="../employer/dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                <a href="../applications/my-applications.php" class="btn btn-primary">My Applications</a>
                <?php endif; ?>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                <a href="../auth/login.php" class="btn btn-secondary">Login</a>
                <a href="../auth/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="job-header">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?= htmlspecialchars($job['title']) ?></h1>
            <p style="font-size: 1.25rem; opacity: 0.9;">
                <i class="fas fa-building"></i> <?= htmlspecialchars($job['company_name'] ?? $job['employer_name']) ?>
                <?php if ($job['location']): ?>
                <span style="margin-left: 2rem;"><i class="fas fa-map-marker-alt"></i>
                    <?= htmlspecialchars($job['location']) ?></span>
                <?php endif; ?>
            </p>

            <div class="job-meta">
                <?php if (!empty($job['salary_range'])): ?>
                <div class="meta-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span><strong>Salary:</strong> <?= htmlspecialchars($job['salary_range']) ?></span>
                </div>
                <?php endif; ?>

                <div class="meta-item">
                    <i class="fas fa-briefcase"></i>
                    <span><strong>Type:</strong>
                        <span class="badge badge-<?= $job['job_type'] ?? 'full-time' ?>">
                            <?= ucfirst(str_replace('-', ' ', $job['job_type'] ?? 'full-time')) ?>
                        </span>
                    </span>
                </div>

                <div class="meta-item">
                    <i class="fas fa-layer-group"></i>
                    <span><strong>Experience:</strong> <?= ucfirst($job['experience_level'] ?? 'mid') ?> Level</span>
                </div>

                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><strong>Posted:</strong> <?= date('F j, Y', strtotime($job['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <main class="container">
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <div class="job-details">
                    <h2 class="section-title">Job Description</h2>
                    <div class="job-description">
                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                    </div>

                    <?php if (!empty($job['requirements'])): ?>
                    <h3 class="section-title" style="margin-top: 2rem;">Requirements</h3>
                    <div class="job-description">
                        <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($job['skills'])): ?>
                    <h3 class="section-title" style="margin-top: 2rem;">Required Skills</h3>
                    <div class="job-description">
                        <?= nl2br(htmlspecialchars($job['skills'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <?php if (!empty($job['company_description'])): ?>
                <div class="company-card">
                    <h3 class="section-title">About the Company</h3>
                    <p style="line-height: 1.6; color: var(--dark-gray);">
                        <?= nl2br(htmlspecialchars(substr($job['company_description'], 0, 300))) ?>...
                    </p>
                </div>
                <?php endif; ?>

                <div class="apply-section" id="apply">
                    <?php if ($alreadyApplied): ?>
                    <div class="apply-already">
                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h3>Application Submitted</h3>
                        <p>Your application has been submitted successfully. The employer will review it soon.</p>
                        <a href="../applications/my-applications.php" class="btn btn-primary mt-2">
                            <i class="fas fa-list"></i> View My Applications
                        </a>
                    </div>
                    <?php elseif (isLoggedIn() && $_SESSION['user']['role'] === 'jobseeker'): ?>
                    <h3 class="section-title">Apply for this Job</h3>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="apply" value="1">

                        <div class="form-group">
                            <label class="form-label" for="resume">Upload Resume *</label>
                            <input type="file" id="resume" name="resume" class="input" accept=".pdf,.doc,.docx"
                                required>
                            <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                                PDF, DOC, or DOCX files only (max 5MB)
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cover_letter">Cover Letter</label>
                            <textarea id="cover_letter" name="cover_letter" class="input" rows="6"
                                placeholder="Tell the employer why you're a good fit for this position..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </form>
                    <?php elseif (isLoggedIn() && $_SESSION['user']['role'] === 'employer'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Employers cannot apply for jobs. Switch to a job seeker account to apply.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-user-circle"></i>
                        <h3 style="margin: 0 0 0.5rem 0;">Login to Apply</h3>
                        <p style="margin: 0;">Please login or create an account to apply for this job.</p>
                        <div class="action-buttons">
                            <a href="../auth/login.php" class="btn btn-primary">Login</a>
                            <a href="../auth/register.php" class="btn btn-secondary">Register</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isLoggedIn() && $_SESSION['user']['role'] === 'employer' && Job::belongsToEmployer($jobId, $_SESSION['user']['id'])): ?>
                    <div class="action-buttons">
                        <a href="edit.php?id=<?= $jobId ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Job
                        </a>
                        <a href="delete-job.php?id=<?= $jobId ?>" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                            <i class="fas fa-trash"></i> Delete Job
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>JobRecruit</h3>
                    <p>Connecting talent with opportunity since <?= date('Y') ?>.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="list.php">Browse Jobs</a></li>
                        <li><a href="../auth/login.php">Login</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Need Help?</h3>
                    <ul class="footer-links">
                        <li><a href="#faq">FAQ</a></li>
                        <li><a href="#support">Support Center</a></li>
                        <li><a href="#terms">Terms of Service</a></li>
                        <li><a href="#privacy">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; <?= date('Y') ?> JobRecruit. All rights reserved.
            </div>
        </div>
    </footer>
</body>

</html>