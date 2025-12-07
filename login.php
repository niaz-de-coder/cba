<?php
// login.php - Handle user login

require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email'], $conn);
    $password = $_POST['password']; // Don't sanitize password
    
    // Check if email exists
    $sql = "SELECT * FROM user_list WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['fullname'] = $user['fullname'];
            
            // Record login time in log_time table
            $login_sql = "INSERT INTO log_time (user_id, email, login_time) 
                         VALUES ('{$user['id']}', '$email', NOW())";
            mysqli_query($conn, $login_sql);
            
            // Redirect to user dashboard
            header("Location: user_dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('No account found with this email.'); window.history.back();</script>";
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header("Location: user.php");
    exit();
}
?>