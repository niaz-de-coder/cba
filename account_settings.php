<?php
// account_settings.php - Handle account settings update

require_once 'config.php';
check_login(); // Ensure user is logged in

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $fullname = sanitize_input($_POST['fullname'], $conn);
    $gender = sanitize_input($_POST['gender'], $conn);
    $mobile = sanitize_input($_POST['mobile'], $conn);
    
    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        if (strlen($password) < 6) {
            echo "<script>alert('Password must be at least 6 characters long'); window.history.back();</script>";
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $password_update = ", password = '$hashed_password'";
    } else {
        $password_update = "";
    }
    
    // Update user information
    $sql = "UPDATE user_list 
            SET fullname = '$fullname', 
                gender = '$gender', 
                mobile = '$mobile' 
                $password_update
            WHERE id = '$user_id'";
    
    if (mysqli_query($conn, $sql)) {
        // Update session variables
        $_SESSION['fullname'] = $fullname;
        
        echo "<script>alert('Account updated successfully!'); window.location.href='user_dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating account: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }
} else {
    // If not POST request, redirect to user dashboard
    header("Location: user_dashboard.php");
    exit();
}
?>