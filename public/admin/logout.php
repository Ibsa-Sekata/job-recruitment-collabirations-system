<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

AdminController::logout();
header('Location: login.php');
exit;
