<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Job.php';

$query = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';

$jobs = Job::search($query, $location, $type);
// Number of results for display
$jobCount = count($jobs);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Browse Jobs - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
        }

        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 1rem;
        }

        .job-count {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .job-item {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }

        .job-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--darker);
            margin: 0;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            color: var(--gray);
            font-size: 0.875rem;
            margin: 1rem 0;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #e0e7ff;
            color: var(--primary);
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .job-salary {
            color: var(--success);
            font-weight: 600;
        }

        .job-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
            background: white;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid var(--light-gray);
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="brand">Job Recruitment</a>
            <div class="nav-links">
                <a href="list.php" class="active">Find Jobs</a>
                <a href="../auth/register.php">For Employers</a>

            </div>
            <div class="nav-right">
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary">Login</a>
                    <a href="../auth/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Find Your Dream Job</h1>
            <p style="font-size: 1.125rem; opacity: 0.9;">Browse thousands of job opportunities from top companies</p>
        </div>
    </div>

    <main class="container">
        <div class="search-filters">
            <form method="GET" action="" class="filter-row">
                <input type="text" name="q" class="input" placeholder="Job title, keywords, or company"
                    value="<?= htmlspecialchars($query) ?>">
                <input type="text" name="location" class="input" placeholder="Location"
                    value="<?= htmlspecialchars($location) ?>">
                <select name="type" class="input">
                    <option value="">All Job Types</option>
                    <option value="full-time" <?= $type === 'full-time' ? 'selected' : '' ?>>Full Time</option>
                    <option value="part-time" <?= $type === 'part-time' ? 'selected' : '' ?>>Part Time</option>
                    <option value="contract" <?= $type === 'contract' ? 'selected' : '' ?>>Contract</option>
                    <option value="remote" <?= $type === 'remote' ? 'selected' : '' ?>>Remote</option>
                    <option value="internship" <?= $type === 'internship' ? 'selected' : '' ?>>Internship</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <div class="job-count">
            Found <?= $jobCount ?> job<?= $jobCount !== 1 ? 's' : '' ?>
            <?php if ($query): ?> for "<?= htmlspecialchars($query) ?>"<?php endif; ?>
                <?php if ($location): ?> in <?= htmlspecialchars($location) ?><?php endif; ?>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No jobs found</h3>
                <p>Try adjusting your search criteria or browse all jobs</p>
                <a href="list.php" class="btn btn-primary mt-3">Browse All Jobs</a>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job-item">
                    <div class="job-header">
                        <div>
                            <h2 class="job-title"><?= htmlspecialchars($job['title']) ?></h2>
                            <p style="color: var(--primary); font-weight: 500; margin: 0.25rem 0;">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($job['company_name'] ?? $job['employer_name']) ?>
                            </p>
                        </div>
                        <div class="job-salary">
                            <?= !empty($job['salary_range']) ? htmlspecialchars($job['salary_range']) : 'Salary negotiable' ?>
                        </div>
                    </div>

                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                        <span><i class="fas fa-briefcase"></i>
                            <span class="job-type"><?= ucfirst(str_replace('-', ' ', $job['job_type'] ?? 'full-time')) ?></span>
                        </span>
                        <span><i class="fas fa-clock"></i> <?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                    </div>

                    <p style="color: var(--dark-gray); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars(substr($job['description'], 0, 250))) ?>...
                    </p>

                    <div class="job-actions">
                        <a href="view.php?id=<?= $job['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="view.php?id=<?= $job['id'] ?>#apply" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Apply Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="pagination">
                <a href="#" class="page-link active">1</a>
                <a href="#" class="page-link">2</a>
                <a href="#" class="page-link">3</a>
                <a href="#" class="page-link">4</a>
                <a href="#" class="page-link">5</a>
                <a href="#" class="page-link">Next</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>JobRecruit</h3>
                    <p>Your career journey starts here. Find the perfect job or the perfect candidate.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="list.php">Browse Jobs</a></li>
                        <li><a href="../auth/register.php">Register</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@jobrecruit.com</li>
                        <li><i class="fas fa-phone"></i> +251921721817</li>
                        <li><i class="fas fa-map-marker-alt"></i>haramaya university</li>
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