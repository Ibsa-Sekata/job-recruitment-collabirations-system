<?php
// Include helper functions (session handling, utilities, etc.)
require_once __DIR__ . '/../../src/helpers.php';

// Include AdminController to access logout functionality
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Call logout method to destroy admin session and clear authentication data
AdminController::logout();

// Redirect user back to the login page after logout
header('Location: login.php');

// Stop further script execution
exit;