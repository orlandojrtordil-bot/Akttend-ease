<?php
/**
 * Attend Ease - User Logout
 *
 * @package AttendEase
 */

require_once __DIR__ . '/../config.php';

clearUserSession();
session_destroy();

header('Location: ' . BASE_URL . 'Registration/login.php');
exit;
?>

