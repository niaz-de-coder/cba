<?php
// register.php - Handle user registration

require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullname = sanitize_input($_POST['fullname'], $conn);
    $email = sanitize_input($_POST['email'], $conn);
    $country_code = sanitize_input($_POST['country_code'], $conn);
    $mobile = sanitize_input($_POST['mobile'], $conn);
    $address = sanitize_input($_POST['address'], $conn);
    $dob = sanitize_input($_POST['dob'], $conn);
    $password = $_POST['password']; // Don't sanitize password
    $policy = isset($_POST['policy']) ? 1 : 0;
    
    // Validate password length
    if (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long'); window.history.back();</script>";
        exit();
    }
    
    // Check if policy is accepted
    if (!$policy) {
        echo "<script>alert('You must agree to the Terms of Service and Privacy Policy'); window.history.back();</script>";
        exit();
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM user_list WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email already registered. Please use a different email or login.'); window.history.back();</script>";
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate user_id (you can use UUID or auto-increment)
    $user_id = uniqid('user_', true);
    
    // Insert into database
    $sql = "INSERT INTO user_list (user_id, fullname, email, country_code, mobile, address, dob, password, policy_agreed, created_at) 
            VALUES ('$user_id', '$fullname', '$email', '$country_code', '$mobile', '$address', '$dob', '$hashed_password', $policy, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        // Get the inserted user ID
        $user_id = mysqli_insert_id($conn);
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['fullname'] = $fullname;
        
        // Redirect to user dashboard
        echo "<script>alert('Registration successful!'); window.location.href='user_dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.history.back();</script>";
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: user.php");
    exit();
}
?>