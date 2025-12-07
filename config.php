<?php
// config.php - Database Connection Configuration

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cba');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// Function to sanitize input data
function sanitize_input($data, $conn) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for protected pages)
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: user.php");
        exit();
    }
}

// Check if user is in office (for office dashboard)
function check_office_login() {
    if (!isset($_SESSION['office_id'])) {
        header("Location: user_dashboard.php");
        exit();
    }
}
?>