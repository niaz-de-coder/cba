<?php
require_once 'config.php';
check_login();

// Get user information from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $office_email = sanitize_input($_POST['office_email'], $conn);
    $user_email = $user['email'];
    
    // Check if office exists
    $office_check = "SELECT * FROM office_list WHERE business_email = '$office_email'";
    $office_result = mysqli_query($conn, $office_check);
    
    if (mysqli_num_rows($office_result) == 0) {
        echo "<script>alert('Office not found with this email.'); window.history.back();</script>";
        exit();
    }
    
    // Check if user already has a request for this office
    $request_check = "SELECT * FROM office_request WHERE user_email = '$user_email' AND office_email = '$office_email'";
    $request_result = mysqli_query($conn, $request_check);
    
    if (mysqli_num_rows($request_result) > 0) {
        $request = mysqli_fetch_assoc($request_result);
        
        // Check status
        switch ($request['status']) {
            case 'Yes':
                // Get office ID and set session
                $office = mysqli_fetch_assoc($office_result);
                $_SESSION['office_id'] = $office['id'];
                $_SESSION['office_email'] = $office_email;
                
                // Log the successful join
                require_once 'log_join.php';
                logOfficeJoin($conn, $user['email'], $office_email);
                
                header("Location: office_dashboard.php");
                exit();
                
            case 'No':
                echo "<script>alert('Authority rejected you.'); window.history.back();</script>";
                exit();
                
            case 'ban':
                echo "<script>alert('You have been banned.'); window.history.back();</script>";
                exit();
                
            case 'waiting':
                echo "<script>alert('Request is reviewing to join, ask your authority to accept.'); window.history.back();</script>";
                exit();
                
            default:
                echo "<script>alert('Unknown status.'); window.history.back();</script>";
                exit();
        }
    } else {
        // Insert new request with waiting status
        $insert_request = "INSERT INTO office_request (user_email, status, position, full_name, mobile_number, office_email) 
                          VALUES ('$user_email', 'waiting', 'member', '" . $user['fullname'] . "', '" . $user['mobile'] . "', '$office_email')";
        
        if (mysqli_query($conn, $insert_request)) {
            echo "<script>alert('Request sent to join office. Waiting for approval.'); window.history.back();</script>";
        } else {
            echo "<script>alert('Error sending request: " . mysqli_error($conn) . "'); window.history.back();</script>";
        }
    }
} else {
    header("Location: user_dashboard.php");
    exit();
}
?>