<?php
// logout.php - Handle user logout

require_once 'config.php';

// Destroy all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: user.php");
exit();
?>