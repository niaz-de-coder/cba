<?php
// update_office_settings.php
require_once 'config.php';
check_office_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $office_id = sanitize_input($_POST['office_id'], $conn);
    $business_name = sanitize_input($_POST['business_name'], $conn);
    $business_email = sanitize_input($_POST['business_email'], $conn);
    $office_address = sanitize_input($_POST['office_address'], $conn);
    $country_code = sanitize_input($_POST['country_code'], $conn);
    $contact_number = sanitize_input($_POST['contact_number'], $conn);
    
    // Handle logo upload if provided
    $logo_update = '';
    if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] == 0) {
        $allowed_types = ['image/png'];
        $max_size = 100 * 1024 * 1024; // 100 MB
        
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
                $logo_update = ", business_logo = '$upload_path'";
            }
        }
    }
    
    // Update office information
    $sql = "UPDATE office_list SET 
            business_name = '$business_name',
            business_email = '$business_email',
            office_address = '$office_address',
            country_code = '$country_code',
            contact_number = '$contact_number'
            $logo_update
            WHERE id = '$office_id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Office settings updated successfully!'); window.location.href='office_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating office: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    header("Location: office_dashboard.php");
    exit();
}
?>