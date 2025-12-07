<?php
// log_join.php
require_once 'config.php';

/**
 * Log when a user successfully joins an office
 * Called from login_office.php when status is 'Yes'
 */
function logOfficeJoin($conn, $user_email, $office_email) {
    // Get current date in dd/mm/yy format (USA style)
    $join_time = date('m/d/y'); // mm/dd/yy format (USA)
    
    // Sanitize inputs
    $user_email = sanitize_input($user_email, $conn);
    $office_email = sanitize_input($office_email, $conn);
    
    // Check if office_log table exists, create if not
    $check_table = "SHOW TABLES LIKE 'office_log'";
    $table_result = mysqli_query($conn, $check_table);
    
    if (mysqli_num_rows($table_result) == 0) {
        // Create office_log table if it doesn't exist
        $create_table = "CREATE TABLE office_log (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(255) NOT NULL,
            office_email VARCHAR(255) NOT NULL,
            join_time VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($conn, $create_table)) {
            // Log error but don't disrupt user experience
            error_log("Failed to create office_log table: " . mysqli_error($conn));
            return false;
        }
    }
    
    // Insert the join record
    $sql = "INSERT INTO office_log (user_email, office_email, join_time) 
            VALUES ('$user_email', '$office_email', '$join_time')";
    
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        error_log("Failed to log office join: " . mysqli_error($conn));
        return false;
    }
}

// This function can be called directly if needed as a standalone script
if (isset($_GET['log']) && $_GET['log'] == 'true') {
    if (isset($_GET['user_email']) && isset($_GET['office_email'])) {
        $user_email = $_GET['user_email'];
        $office_email = $_GET['office_email'];
        
        logOfficeJoin($conn, $user_email, $office_email);
        echo "Logged successfully";
    } else {
        echo "Missing parameters";
    }
}
?>