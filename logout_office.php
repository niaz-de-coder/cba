<?php
// logout_office.php
require_once 'config.php';

if (!isset($_SESSION['office_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

// Get user and office information
$user_id = $_SESSION['user_id'];
$office_id = $_SESSION['office_id'];

$user_sql = "SELECT email FROM user_list WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

$office_sql = "SELECT business_email FROM office_list WHERE id = '$office_id'";
$office_result = mysqli_query($conn, $office_sql);
$office = mysqli_fetch_assoc($office_result);

// Get current time in mm/dd/yy format (USA)
$logout_time = date('m/d/y');

// Check if office_logout table exists, create if not
$check_table = "SHOW TABLES LIKE 'office_logout'";
$table_result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_result) == 0) {
    // Create office_logout table if it doesn't exist
    $create_table = "CREATE TABLE office_logout (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        office_email VARCHAR(255) NOT NULL,
        logout_time VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $create_table);
}

// Insert logout record
$user_email = sanitize_input($user['email'], $conn);
$office_email = sanitize_input($office['business_email'], $conn);

$sql = "INSERT INTO office_logout (user_email, office_email, logout_time) 
        VALUES ('$user_email', '$office_email', '$logout_time')";
mysqli_query($conn, $sql);

// Unset office session variables
unset($_SESSION['office_id']);
unset($_SESSION['office_email']);

// Redirect to user dashboard
header("Location: user_dashboard.php");
exit();
?>