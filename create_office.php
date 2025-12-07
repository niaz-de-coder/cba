<?php
require_once 'config.php';
check_login();

// Get user information from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $business_name = sanitize_input($_POST['business_name'], $conn);
    $business_email = sanitize_input($_POST['business_email'], $conn);
    $office_address = sanitize_input($_POST['office_address'], $conn);
    $country_code = sanitize_input($_POST['country_code'], $conn);
    $contact_number = sanitize_input($_POST['contact_number'], $conn);
    $founder_email = $user['email']; // User email who created it
    
    // Handle logo upload
    $business_logo = '';
    if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] == 0) {
        $allowed_types = ['image/png'];
        $max_size = 100 * 1024 * 1024; // 100 MB in bytes
        
        if (in_array($_FILES['business_logo']['type'], $allowed_types) && 
            $_FILES['business_logo']['size'] <= $max_size) {
            
            $upload_dir = 'uploads/office_logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['business_logo']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('office_logo_') . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['business_logo']['tmp_name'], $upload_path)) {
                $business_logo = $upload_path;
            }
        }
    }
    
    // Current date for creation and purchase
    $current_date = date('Y-m-d H:i:s');
    
    // Insert into office_list table
    $office_sql = "INSERT INTO office_list (business_name, business_logo, business_email, office_address, country_code, contact_number, creation_date, purchase_date, founder_email) 
                   VALUES ('$business_name', '$business_logo', '$business_email', '$office_address', '$country_code', '$contact_number', '$current_date', '$current_date', '$founder_email')";
    
    if (mysqli_query($conn, $office_sql)) {
        $office_id = mysqli_insert_id($conn);
        
        // Insert into office_request table
        $request_sql = "INSERT INTO office_request (user_email, status, position, full_name, mobile_number, office_email) 
                       VALUES ('$founder_email', 'Yes', 'founder', '" . $user['fullname'] . "', '" . $user['mobile'] . "', '$business_email')";
        
        if (mysqli_query($conn, $request_sql)) {
            // Set office session and redirect to office dashboard
            $_SESSION['office_id'] = $office_id;
            $_SESSION['office_email'] = $business_email;
            echo "<script>alert('Office created successfully!'); window.location.href='office_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error creating office request: " . mysqli_error($conn) . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Error creating office: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    header("Location: user_dashboard.php");
    exit();
}
?>