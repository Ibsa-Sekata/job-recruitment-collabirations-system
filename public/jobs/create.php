<?php
// public/jobs/create.php


require_once __DIR__ . '/../../src/helpers.php';
requireLogin();
requireRole('employer'); // only employer can create

$pdo = getPDO();
// get employer id from employers table
$stmt = $pdo->prepare("SELECT id FROM employers WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user']['id']]);
$emp = $stmt->fetch();
if (!$emp) {
    echo "Employer profile not found. Please complete company profile first.";
    exit;
}
$employer_id = $emp['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic server-side validation
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        flash('error', 'Job title is required.');
        header('Location: /jobs/create.php');
        exit;
    }
    // In create.php, change the INSERT statement to:
    $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, location, salary_range, job_type, experience_level, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $employer_id,
        $title,
        $_POST['description'] ?? null,
        $_POST['location'] ?? null,
        $_POST['salary_range'] ?? null,
        $_POST['job_type'] ?? 'full-time', // Add this field to form
        $_POST['experience_level'] ?? 'mid', // Add this field to form
        'active',
        date('Y-m-d H:i:s')
    ]);
    flash('success', 'Job created');
    header('Location: /jobs/list.php');
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Create job</title>
    <link rel="stylesheet" href="../css/app.css?v=20">
</head>

<body>
    <nav class="navbar">
        <div class="container"><a href="/" class="brand">JobRecruit</a></div>
    </nav>
    <main class="container" style="max-width:900px;">
        <div class="card">
            <h2>Create Job</h2>
            ?>
            <form method="post">
                <input class="input" name="title" placeholder="Job title" required>
                <textarea class="input" name="description" placeholder="Full description" rows="6"></textarea>
                <input class="input" name="location" placeholder="Location">
                <input class="input" name="salary_range" placeholder="Salary range">

                <!-- ADD THESE FIELDS -->
                <select class="input" name="job_type">
                    <option value="full-time">Full Time</option>
                    <option value="part-time">Part Time</option>
                    <option value="contract">Contract</option>
                </select>

                <select class="input" name="experience_level">
                    <option value="entry">Entry Level</option>
                    <option value="mid">Mid Level</option>
                    <option value="senior">Senior Level</option>
                </select>

                <button class="btn btn-primary" type="submit">Publish Job</button>
            </form>
        </div>
    </main>
</body>

</html>