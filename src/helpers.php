<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getPDO()
{
    $cfg = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['db']};charset={$cfg['charset']}";
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    return new PDO($dsn, $cfg['user'], $cfg['pass'], $opt);
}

function old($key, $default = '')
{
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function flash($key = null, $value = null)
{
    if ($key === null) return $_SESSION['flash'] ?? null;
    if ($value === null) {
        $v = $_SESSION['flash'][$key] ?? null;
        if (isset($_SESSION['flash'][$key])) unset($_SESSION['flash'][$key]);
        return $v;
    }
    $_SESSION['flash'][$key] = $value;
}

function auth()
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn()
{
    return isset($_SESSION['user']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireRole($role)
{
    requireLogin();
    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        echo "403 Forbidden — insufficient permissions.";
        exit;
    }
}

function csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

// Admin authentication functions
function isAdminLoggedIn()
{
    return isset($_SESSION['admin']);
}

function requireAdminLogin()
{
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function adminAuth()
{
    return $_SESSION['admin'] ?? null;
}

// Check if employer is approved to post jobs
function isEmployerApproved()
{
    if (!isLoggedIn() || $_SESSION['user']['role'] !== 'employer') {
        return false;
    }
    
    require_once __DIR__ . '/Models/Employer.php';
    return Employer::isApproved($_SESSION['user']['id']);
}
function uploadFile($file, $type = 'cv')
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        return false;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return false;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create specific type directory
    $typeDir = $uploadDir . $type . '/';
    if (!is_dir($typeDir)) {
        mkdir($typeDir, 0755, true);
    }

    $destination = $typeDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return relative path from public directory
        return 'uploads/' . $type . '/' . $filename;
    }

    return false;
}

/**
 * Ensure the uploads directories exist.
 */
function ensure_upload_dirs()
{
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $dirs = ['cv', 'resumes'];
    foreach ($dirs as $d) {
        $typeDir = $uploadDir . $d . '/';
        if (!is_dir($typeDir)) {
            mkdir($typeDir, 0755, true);
        }
    }
}

/**
 * Safely unlink a file path relative to the project (e.g. 'uploads/cv/..').
 * Returns true if file was removed, false otherwise.
 */
function safe_unlink($relativePath)
{
    if (empty($relativePath)) return false;
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    $candidate = realpath(__DIR__ . '/../' . $relativePath);
    if ($candidate && $uploadsDir && strpos($candidate, $uploadsDir) === 0 && is_file($candidate)) {
        return unlink($candidate);
    }
    return false;
}
