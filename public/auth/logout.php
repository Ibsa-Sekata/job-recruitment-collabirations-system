<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

AuthController::logout();
header('Location: ../index.php');
exit;
