<?php
require_once __DIR__ . '/../../src/helpers.php';

requireRole('jobseeker');

// Direct database query to get applications
try {
    $pdo = getPDO();
    $userId = $_SESSION['user']['id'];

    // Query using the correct column names from your table
    $sql = "
        SELECT 
            a.*,
            j.title as job_title,
            j.location,
            e.company_name as employer_name,
            e.company_name as company_name,
            a.resume_path as resume  -- Use resume_path from your table
        FROM applications a
        LEFT JOIN jobs j ON a.job_id = j.id
        LEFT JOIN employers e ON j.employer_id = e.id
        WHERE a.user_id = ?
        ORDER BY a.applied_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Check what we got
    error_log("Applications found for user $userId: " . count($applications));
} catch (Exception $e) {
    error_log("Error loading applications: " . $e->getMessage());
    $applications = [];
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>My Applications - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css?v=20">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .page-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 2rem 2rem;
    }

    .application-item {
        background: white;
        padding: 1.5rem;
        border-radius: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-left: 4px solid;
        transition: transform 0.3s;
    }

    .application-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .application-item.submitted {
        border-left-color: #3b82f6;
    }

    .application-item.under_review {
        border-left-color: #f59e0b;
    }

    .application-item.shortlisted {
        border-left-color: #10b981;
    }

    .application-item.accepted {
        border-left-color: #10b981;
    }

    .application-item.rejected {
        border-left-color: #ef4444;
    }

    .application-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .application-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .application-meta {
        display: flex;
        gap: 1.5rem;
        color: #64748b;
        font-size: 0.875rem;
        margin: 1rem 0;
        flex-wrap: wrap;
    }

    .application-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .status-submitted {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-under_review {
        background: #fef3c7;
        color: #92400e;
    }

    .status-shortlisted {
        background: #dcfce7;
        color: #166534;
    }

    .status-accepted {
        background: #dcfce7;
        color: #166534;
    }

    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #cbd5e1;
    }

    .application-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-right">
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                <div class="btn-group">
                    <a href="../jobs/list.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Find Jobs
                    </a>
                    <a href="../auth/logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1>My Applications</h1>
            <p>Track all your job applications in one place</p>
        </div>
    </div>

    <main class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
        <div class="empty-state">
            <i class="fas fa-file-alt"></i>
            <h3>No applications yet</h3>
            <p>You haven't applied to any jobs yet. Start your job search today!</p>
            <a href="../jobs/list.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-search"></i> Browse Jobs
            </a>
        </div>
        <?php else: ?>
        <?php $appCount = count($applications); ?>
        <p style="color: #64748b; margin-bottom: 1.5rem;">
            You have <?= $appCount ?> application<?= $appCount !== 1 ? 's' : '' ?>
        </p>

        <?php foreach ($applications as $app): ?>
        <?php
                // Normalize status - your table uses enum with these values
                $status = $app['status'] ?? 'submitted';
                ?>
        <div class="application-item <?= htmlspecialchars($status) ?>">
            <div class="application-header">
                <div>
                    <h3 class="application-title">
                        <?= htmlspecialchars($app['job_title'] ?? 'Job #' . ($app['job_id'] ?? 'Unknown')) ?>
                    </h3>
                    <p style="color: #4f46e5; font-weight: 500; margin: 0.25rem 0;">
                        <i class="fas fa-building"></i>
                        <?= htmlspecialchars($app['employer_name'] ?? $app['company_name'] ?? 'Unknown Company') ?>
                    </p>
                </div>
                <span class="status-badge status-<?= htmlspecialchars($status) ?>">
                    <?= ucfirst(str_replace('_', ' ', $status)) ?>
                </span>
            </div>

            <div class="application-meta">
                <?php if (!empty($app['location'])): ?>
                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['location']) ?></span>
                <?php endif; ?>

                <?php if (!empty($app['applied_at'])): ?>
                <span><i class="fas fa-clock"></i> Applied on
                    <?= date('M d, Y', strtotime($app['applied_at'])) ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($app['cover_letter'])): ?>
            <div style="margin: 1rem 0; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                <p style="margin: 0 0 0.5rem 0; font-weight: 500; color: #1e293b;">Cover letter:</p>
                <p style="margin: 0; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                    <?= nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))) ?>
                    <?php if (strlen($app['cover_letter']) > 200): ?>...<?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <div class="application-actions">
                <?php if (!empty($app['job_id'])): ?>
                <a href="../jobs/view.php?id=<?= $app['job_id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> View Job
                </a>
                <?php endif; ?>

                <?php if ($status === 'submitted' && !empty($app['id'])): ?>
                <a href="withdraw.php?id=<?= $app['id'] ?>" class="btn btn-danger btn-sm"
                    onclick="return confirm('Are you sure you want to withdraw this application? This action cannot be undone.')">
                    <i class="fas fa-times"></i> Withdraw
                </a>
                <?php endif; ?>

                <?php if (!empty($app['resume_path'])): ?>
                <?php
                            $resumePath = $app['resume_path'];
                            // Use the viewer script
                            $viewUrl = '../view-resume.php?path=' . urlencode($resumePath);
                            ?>
                <a href="<?= $viewUrl ?>" class="btn btn-secondary btn-sm" target="_blank">
                    <i class="fas fa-file-download"></i> View Resume
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>


    </main>
</body>

</html>