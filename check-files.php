<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Models/Application.php';

requireRole('jobseeker');

$applications = Application::findByUser($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Check Resume Files</title>
</head>

<body>
    <h1>Resume File Checker</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Job Title</th>
            <th>Resume Path</th>
            <th>File Exists</th>
            <th>Full Path</th>
            <th>Suggested Path</th>
        </tr>
        <?php foreach ($applications as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['job_title']) ?></td>
                <td><?= htmlspecialchars($app['resume_path'] ?? 'No resume') ?></td>
                <td>
                    <?php
                    if (!empty($app['resume_path'])) {
                        $fullPath = __DIR__ . '/../../' . $app['resume_path'];
                        echo file_exists($fullPath) ? '✅ Yes' : '❌ No';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td><?= !empty($app['resume_path']) ? htmlspecialchars(__DIR__ . '/../../' . $app['resume_path']) : '' ?>
                </td>
                <td>
                    <?php
                    if (!empty($app['resume_path'])) {
                        $filename = basename($app['resume_path']);
                        echo 'uploads/cv/' . htmlspecialchars($filename);
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Uploads Directory Structure</h2>
    <pre>
<?php
function listDirectory($dir, $prefix = '')
{
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = $dir . '/' . $file;
        echo $prefix . $file . "\n";
        if (is_dir($path)) {
            listDirectory($path, $prefix . '  ');
        }
    }
}

$uploadsDir = __DIR__ . '/../../uploads/';
if (is_dir($uploadsDir)) {
    listDirectory($uploadsDir);
} else {
    echo "Uploads directory doesn't exist!\n";
    echo "Creating uploads directory...\n";
    mkdir($uploadsDir, 0755, true);
    mkdir($uploadsDir . '/cv', 0755, true);
    mkdir($uploadsDir . '/resumes', 0755, true);
    echo "Created: uploads/\n";
    echo "Created: uploads/cv/\n";
    echo "Created: uploads/resumes/\n";
}
?>
    </pre>

    <a href="applications/my-applications.php">Back to My Applications</a>
</body>

</html>