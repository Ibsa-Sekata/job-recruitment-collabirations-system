<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Job.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/Employer.php';
require_once __DIR__ . '/../../src/Controllers/JobController.php';

requireRole('employer');

$error = '';
$success = '';
$showJobForm = true;
$jobs = [];
$jobCount = 0;
$applications = [];
$stats = ['total' => 0, 'shortlisted' => 0, 'accepted' => 0];

// Get employer ID from user ID
$employer = Employer::getByUserId($_SESSION['user']['id']);

// Check approval status
$isApproved = $employer && $employer['approval_status'] === 'approved';
$isPending = $employer && $employer['approval_status'] === 'pending';
$isRejected = $employer && $employer['approval_status'] === 'rejected';

if (!$employer) {
    // If no employer profile, show message but don't redirect
    $showJobForm = false;
    $error = 'Please complete your employer profile to post jobs.';

    // Try to create a basic employer profile automatically
    $userName = $_SESSION['user']['name'];
    $basicEmployer = [
        'user_id' => $_SESSION['user']['id'],
        'company_name' => $userName . "'s Company",
        'website' => '',
        'industry' => '',
        'company_size' => '',
        'address' => '',
        'logo' => '',
        'verified' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $created = Employer::create($basicEmployer);
    if ($created) {
        $employer = Employer::getByUserId($_SESSION['user']['id']);
        $showJobForm = true;
        $success = 'Basic employer profile created. You can update it later.';
    }
}

if ($employer) {
    $employerId = $employer['id'];

    // Handle job posting (only if approved)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        if (!$isApproved) {
            $error = 'Your account must be approved by an administrator before you can post jobs.';
        } else {
            $result = JobController::postJob($_POST);

            if ($result['ok']) {
                $_SESSION['success'] = $result['msg'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = $result['msg'];
            }
        }
    }

    // Fetch all jobs posted by this employer using employer ID
    $jobs = Job::allByEmployer($employerId);
    $jobCount = count($jobs);

    // Fetch applications using employer ID
    $applications = Application::findByEmployer($employerId);
    $stats = Application::getStats($employerId);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Employer Dashboard - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .job-item {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #4f46e5;
        }

        .job-item h3 {
            margin: 0 0 0.5rem 0;
            color: #1e293b;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .application-count {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tab-navigation {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
            border-radius: 0.5rem;
        }

        .tab-btn:hover {
            background: #f8fafc;
        }

        .tab-btn.active {
            color: #4f46e5;
            background: #f1f5f9;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 100%;
            height: 2px;
            background: #4f46e5;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .application-item {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            transition: transform 0.3s;
        }

        .application-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
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

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .profile-warning {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .profile-warning h3 {
            margin-top: 0;
            color: #92400e;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="brand">Job Recruitment</a>
            <div class="nav-right">
                <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                <div class="btn-group">
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="../auth/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Employer Dashboard</h1>
            <p>Manage your job postings and applications</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Approval Status Messages -->
        <?php if ($isPending): ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-clock"></i> Account Pending Approval</h4>
                <p>Your employer account is currently pending administrator approval. You will be able to post jobs once your account is approved.</p>
                <p>This process typically takes 1-2 business days. You will receive an email notification once approved.</p>
            </div>
        <?php elseif ($isRejected): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-times-circle"></i> Account Rejected</h4>
                <p>Your employer account application has been rejected.</p>
                <?php if (!empty($employer['rejection_reason'])): ?>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($employer['rejection_reason']) ?></p>
                <?php endif; ?>
                <p>Please contact support for more information or to reapply.</p>
            </div>
        <?php elseif ($isApproved): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Account Approved</h4>
                <p>Your employer account has been approved! You can now post jobs and manage applications.</p>
            </div>
        <?php endif; ?>

        <?php if (!$employer): ?>
            <div class="profile-warning">
                <h3><i class="fas fa-exclamation-triangle"></i> Complete Your Profile</h3>
                <p>You need an employer profile to post jobs. A basic profile has been created for you automatically.</p>
                <p>You can update your company details later.</p>
            </div>
        <?php elseif ($employer && $employer['company_name'] === $_SESSION['user']['name'] . "'s Company"): ?>
            <div class="alert alert-info">
                <p><strong>Tip:</strong> Update your company name from "<?= htmlspecialchars($employer['company_name']) ?>"
                    to your actual company name for better visibility.</p>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $jobCount ?></div>
                <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['shortlisted'] ?? 0 ?></div>
                <div class="stat-label">Shortlisted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['accepted'] ?? 0 ?></div>
                <div class="stat-label">Accepted</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" data-tab="post-job">Post New Job</button>
            <button class="tab-btn" data-tab="my-jobs">My Jobs (<?= $jobCount ?>)</button>
            <button class="tab-btn" data-tab="applications">Applications (<?= $stats['total'] ?? 0 ?>)</button>
        </div>

        <!-- Post Job Tab -->
        <div id="post-job" class="tab-content active">
            <div class="card">
                <h2>Post a New Job</h2>

                <?php if (!$showJobForm): ?>
                    <div class="alert alert-warning">
                        <p>Creating your employer profile... Please refresh the page in a moment.</p>
                    </div>
                <?php elseif (!$isApproved): ?>
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle"></i> Job Posting Disabled</h4>
                        <p>Job posting is disabled until your employer account is approved by an administrator.</p>
                        <?php if ($isPending): ?>
                            <p>Your account is currently pending approval.</p>
                        <?php elseif ($isRejected): ?>
                            <p>Your account application was rejected. Please contact support.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- In the Post Job form section -->
                    <form method="POST">
                        <div class="form-row">
                            <input class="input" type="text" name="title" placeholder="Job Title *" required>
                        </div>

                        <div class="form-row">
                            <textarea class="input" name="summary" placeholder="Short Summary (Optional)"
                                rows="3"></textarea>
                        </div>

                        <div class="form-row">
                            <textarea class="input" name="description" placeholder="Job Description *" rows="6"
                                required></textarea>
                        </div>

                        <div class="form-row">
                            <textarea class="input" name="requirements" placeholder="Requirements (Optional)"
                                rows="4"></textarea>
                        </div>

                        <div class="form-row">
                            <textarea class="input" name="skills" placeholder="Skills (comma separated, Optional)"
                                rows="3"></textarea>
                        </div>

                        <div class="form-row">
                            <input class="input" type="text" name="location"
                                placeholder="Location (e.g., New York, NY or Remote) *" required>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <input class="input" type="text" name="salary_range"
                                placeholder="Salary Range (e.g., $60,000 - $80,000)">
                            <select class="input" name="job_type">
                                <option value="full-time">Full Time</option>
                                <option value="part-time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="remote">Remote</option>
                                <option value="internship">Internship</option>
                            </select>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <select class="input" name="experience_level">
                                <option value="entry">Entry Level</option>
                                <option value="mid" selected>Mid Level</option>
                                <option value="senior">Senior Level</option>
                                <option value="executive">Executive</option>
                            </select>
                            <input class="input" type="text" name="education_level"
                                placeholder="Education Level (Optional)">
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <input class="input" type="date" name="application_deadline" placeholder="Application Deadline">
                            <select class="input" name="visibility">
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane"></i> Post Job
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Jobs Tab -->
        <div id="my-jobs" class="tab-content">
            <h2>My Job Postings</h2>
            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No jobs posted yet</h3>
                    <p>Post your first job to start receiving applications</p>
                    <button class="tab-btn btn btn-primary" data-tab="post-job" style="margin-top: 1rem;">
                        Post a Job
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-item">
                        <h3><?= htmlspecialchars($job['title']) ?></h3>
                        <div class="job-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                            <span><i class="fas fa-money-bill-wave"></i>
                                <?= htmlspecialchars($job['salary_range'] ?? 'Not specified') ?></span>
                            <span><i class="fas fa-clock"></i> <?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                            <span class="application-count">
                                <i class="fas fa-users"></i> <?= $job['application_count'] ?? 0 ?> applications
                            </span>
                        </div>
                        <p><?= nl2br(htmlspecialchars(substr($job['description'], 0, 200))) ?>...</p>
                        <div class="action-buttons">
                            <a href="../jobs/view.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="../jobs/edit.php?id=<?= $job['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="../jobs/delete-job.php?id=<?= $job['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                            <?php if (($job['application_count'] ?? 0) > 0): ?>
                                <a href="view-application.php?job_id=<?= $job['id'] ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-file-alt"></i> View Applications
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Applications Tab -->
        <div id="applications" class="tab-content">
            <h2>Applications Received</h2>
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No applications yet</h3>
                    <p>Applications will appear here when candidates apply to your jobs</p>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <div class="application-item">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap;">
                            <h4 style="margin: 0;"><?= htmlspecialchars($app['applicant_name']) ?></h4>
                            <span class="badge badge-<?= str_replace('_', '-', $app['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                            </span>
                        </div>
                        <p style="margin: 0.5rem 0; color: #64748b;">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($app['email']) ?> |
                            <i class="fas fa-phone"></i> <?= htmlspecialchars($app['phone'] ?? 'Not provided') ?>
                        </p>
                        <p style="margin: 0.5rem 0;">
                            Applied for: <strong><?= htmlspecialchars($app['job_title']) ?></strong>
                        </p>
                        <p style="margin: 0.5rem 0; color: #64748b; font-size: 0.875rem;">
                            <i class="fas fa-clock"></i>
                            Applied on <?= date('M d, Y', strtotime($app['applied_at'] ?? $app['created_at'] ?? 'now')) ?>
                        </p>
                        <div class="action-buttons">
                            <a href="view-application.php?id=<?= $app['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($app['status'] === 'submitted'): ?>
                                <a href="update-application.php?id=<?= $app['id'] ?>&status=under_review"
                                    class="btn btn-info btn-sm">
                                    <i class="fas fa-check"></i> Mark as Reviewing
                                </a>
                            <?php elseif ($app['status'] === 'under_review'): ?>
                                <a href="update-application.php?id=<?= $app['id'] ?>&status=shortlisted"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-star"></i> Shortlist
                                </a>
                            <?php elseif ($app['status'] === 'shortlisted'): ?>
                                <a href="update-application.php?id=<?= $app['id'] ?>&status=accepted"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-user-check"></i> Accept
                                </a>
                                <a href="update-application.php?id=<?= $app['id'] ?>&status=rejected" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        // Allow switching tabs via buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                switchTab(tabId);
            });
        });

        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId).classList.add('active');

            // Update active tab button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.tab === tabId) {
                    btn.classList.add('active');
                }
            });
        }
    </script>
</body>

</html>