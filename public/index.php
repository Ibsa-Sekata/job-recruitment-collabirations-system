<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Models/Job.php';

// Fetch latest jobs
$jobs = Job::all();
$isEmployer = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'employer';

// Check ownership for delete buttons
// If the user is an employer, $job['employer_id'] is used for ownership checks.
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Job Recruitment — Find Your Dream Job</title>
    <link rel="stylesheet" href="css/app.css?v=20">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(green, lightgreen);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 2rem 2rem;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .search-box {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            margin: -3rem auto 0;
            position: relative;
            z-index: 10;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .job-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .job-actions .btn {
            flex: 1;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #e0e7ff;
            color: var(--primary);
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="brand">Job Recruitment</a>
            <div class="nav-links">
                <a href="jobs/list.php">Find Jobs</a>
                <a href="#features">Features</a>
                <a href="#contact">Contact</a>
                <a href="admin/login.php" style="color: #dc2626;">Admin</a>
            </div>
            <div class="nav-right">
                <?php if (isLoggedIn()): ?>
                    <span><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                    <div class="btn-group">
                        <?php if ($_SESSION['user']['role'] === 'employer'): ?>
                            <a href="employer/dashboard.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a href="applications/my-applications.php" class="btn btn-primary">
                                <i class="fas fa-user"></i> My Applications
                            </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="btn-group">
                        <a href="auth/login.php" class="btn btn-secondary">Login</a>
                        <a href="auth/register.php" class="btn btn-primary">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Find Your Dream Job</h1>
                <p>Connect with top companies and discover thousands of opportunities. Your next career move starts
                    here.</p>
                <div class="btn-group">
                    <a href="jobs/list.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Browse Jobs
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="auth/register.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-user-plus"></i> Get Started
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Box -->
    <div class="container">
        <div class="search-box">
            <form method="GET" action="jobs/list.php" class="form-row">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem;">
                    <input type="text" name="q" class="input" placeholder="Job title, keywords, or company">
                    <input type="text" name="location" class="input" placeholder="Location">
                    <select name="type" class="input">
                        <option value="">Job Type</option>
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="remote">Remote</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Latest Jobs -->
    <main class="container">
        <div class="text-center mb-5">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Latest Job Opportunities</h2>
            <p style="color: var(--dark-gray); max-width: 600px; margin: 0 auto;">Discover new career opportunities from
                top employers</p>
        </div>

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

        <div class="grid">
            <?php foreach ($jobs as $job): ?>
                <div class="card job-card">
                    <div class="job-meta">
                        <span><i class="fas fa-building"></i>
                            <?= htmlspecialchars($job['company_name'] ?? $job['employer_name']) ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                        <?php if (!empty($job['salary_range'])): ?>
                            <span><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary_range']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h2><?= htmlspecialchars($job['title']) ?></h2>
                    <p><?= nl2br(htmlspecialchars(substr($job['description'], 0, 150))) ?>...</p>

                    <span
                        class="job-type-badge"><?= ucfirst(str_replace('-', ' ', $job['job_type'] ?? 'full-time')) ?></span>

                    <div class="job-actions">
                        <a href="jobs/view.php?id=<?= $job['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>

                        <?php if ($isEmployer && (($job['employer_id'] ?? null) == $_SESSION['user']['id'])): ?>
                            <a href="jobs/delete-job.php?id=<?= $job['id'] ?>" class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                            <a href="jobs/edit.php?id=<?= $job['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEmployer && (($job['employer_id'] ?? null) == $_SESSION['user']['id'])): ?>
                        <div class="mt-3" style="font-size: 0.875rem; color: var(--gray);">
                            <i class="fas fa-clock"></i> Posted: <?= date('M d, Y', strtotime($job['created_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="jobs/list.php" class="btn btn-primary btn-lg">
                View All Jobs <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </main>

    <!-- Features -->
    <section class="container" id="features">
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Find Jobs</h3>
                <p>Browse thousands of job opportunities from top companies across various industries.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>For Employers</h3>
                <p>Post jobs, manage applications, and find the perfect candidates for your company.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Career Growth</h3>
                <p>Track your applications and get insights to improve your job search strategy.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>JobRecruit Pro</h3>
                    <p>Connecting talented professionals with top companies worldwide.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="auth/login.php">Employer Dashboard</a></li>
                        <li><a href="#about">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@jobrecruit.com</li>
                        <li><i class="fas fa-phone"></i> +251921721817</li>
                        <li><i class="fas fa-map-marker-alt"></i> haramaya university</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; <?= date('Y') ?> JobRecruit Pro. All rights reserved.
            </div>
        </div>
    </footer>
</body>

</html>