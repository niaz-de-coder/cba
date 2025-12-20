<?php
// create_financial_entry.php - Create Financial Entry Form
require_once 'config.php';
check_office_login();

// Get office information
$office_id = $_SESSION['office_id'];
$office_sql = "SELECT * FROM office_list WHERE id = '$office_id'";
$office_result = mysqli_query($conn, $office_sql);
$office = mysqli_fetch_assoc($office_result);

// Calculate subscription days
$purchase_date = new DateTime($office['purchase_date']);
$current_date = new DateTime();
$interval = $current_date->diff($purchase_date);
$days_left = 30 - $interval->days;
if ($days_left < 0) $days_left = 0;

// Check subscription
if ($days_left <= 0) {
    echo "<script>alert('Your subscription has expired. Please renew to create financial entries.'); window.location.href='finance.php';</script>";
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Check permissions
$user_email = $user['email'];
$office_email = $_SESSION['office_email'];
$position_sql = "SELECT position FROM office_request WHERE user_email = '$user_email' AND office_email = '$office_email' AND status = 'Yes'";
$position_result = mysqli_query($conn, $position_sql);
$position_data = mysqli_fetch_assoc($position_result);
$user_position = $position_data ? $position_data['position'] : 'member';

$can_create_entry = in_array($user_position, ['founder', 'manager', 'finance_manager']);
if (!$can_create_entry) {
    echo "<script>alert('You do not have permission to create financial entries.'); window.location.href='finance.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $entry_type = sanitize_input($_POST['entry_type'], $conn);
    $entry_date = sanitize_input($_POST['entry_date'], $conn);
    $debit_account = sanitize_input($_POST['debit_account'], $conn);
    $credit_account = sanitize_input($_POST['credit_account'], $conn);
    $transaction_amount = sanitize_input($_POST['transaction_amount'], $conn);
    $currency = sanitize_input($_POST['currency'], $conn);
    $reference = isset($_POST['reference']) ? sanitize_input($_POST['reference'], $conn) : '';
    $comments = isset($_POST['comments']) ? sanitize_input($_POST['comments'], $conn) : '';
    
    // Convert date from dd/mm/yyyy to yyyy-mm-dd
    $date_parts = explode('/', $entry_date);
    if (count($date_parts) == 3) {
        $mysql_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
    } else {
        $mysql_date = date('Y-m-d');
    }
    
    // Handle document upload
    $document_path = '';
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $allowed_types = ['application/pdf'];
        $max_size = 10 * 1024 * 1024; // 10 MB
        
        if (in_array($_FILES['document']['type'], $allowed_types) && 
            $_FILES['document']['size'] <= $max_size) {
            
            $upload_dir = 'uploads/finance_documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('finance_doc_') . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_path)) {
                $document_path = $upload_path;
            }
        }
    }
    
    // Insert into database
    // Use created_by (user id) instead of user_email to match existing schema
    $created_by = isset($user_id) ? (int) $user_id : NULL;
    $insert_sql = "INSERT INTO user_finance_entry (
        office_email,
        created_by,
        entry_type,
        document_path,
        entry_date,
        debit_account,
        credit_account,
        transaction_amount,
        currency,
        reference,
        comments,
        status
    ) VALUES (
        '$office_email',
        '$created_by',
        '$entry_type',
        '$document_path',
        '$mysql_date',
        '$debit_account',
        '$credit_account',
        '$transaction_amount',
        '$currency',
        '$reference',
        '$comments',
        'pending'
    )";
    
    if (mysqli_query($conn, $insert_sql)) {
        $entry_id = mysqli_insert_id($conn);
        
        // Log the activity (finance_logs schema uses entry_id, office_email, action, timestamp)
        $log_sql = "INSERT INTO finance_logs (entry_id, office_email, action, timestamp) 
               VALUES ('$entry_id', '$office_email', 'created', NOW())";
        mysqli_query($conn, $log_sql);
        
        echo "<script>
            alert('Financial entry created successfully! Entry ID: #$entry_id');
            window.location.href='manage_entries.php';
        </script>";
        exit();
    } else {
        $error_message = "Error creating entry: " . mysqli_error($conn);
    }
}

// Account titles for dropdowns
$assets_accounts = [
    'Cash', 'Bank Account', 'Accounts Receivable', 'Inventory', 
    'Prepaid Expenses', 'Property, Plant & Equipment', 'Investments',
    'Vehicles', 'Office Equipment', 'Land', 'Buildings', 'Intangible Assets',
    'Goodwill', 'Marketable Securities', 'Accrued Income', 'Service Revenue'
];

$liabilities_accounts = [
    'Accounts Payable', 'Short-term Loans', 'Accrued Expenses',
    'Unearned Revenue', 'Current Portion of Long-term Debt',
    'Bank Overdraft', 'Taxes Payable', 'Wages Payable',
    'Interest Payable', 'Long-term Loans', 'Mortgage Payable',
    'Bonds Payable', 'Deferred Tax Liabilities', 'Lease Obligations'
];

$equity_accounts = [
    'Owner\'s Capital', 'Common Stock', 'Preferred Stock',
    'Retained Earnings', 'Additional Paid-in Capital',
    'Treasury Stock', 'Dividends', 'Share Premium'
];

// All accounts combined for dropdown
$all_accounts = array_merge($assets_accounts, $liabilities_accounts, $equity_accounts);
sort($all_accounts);

// Currency list
$currencies = [
    'USD' => 'US Dollar ($)',
    'BDT' => 'Bangladeshi Taka (৳)',
    'EUR' => 'Euro (€)',
    'GBP' => 'British Pound (£)',
    'JPY' => 'Japanese Yen (¥)',
    'AUD' => 'Australian Dollar (A$)',
    'CAD' => 'Canadian Dollar (C$)',
    'CHF' => 'Swiss Franc (CHF)',
    'CNY' => 'Chinese Yuan (¥)',
    'INR' => 'Indian Rupee (₹)',
    'SGD' => 'Singapore Dollar (S$)',
    'AED' => 'UAE Dirham (د.إ)',
    'SAR' => 'Saudi Riyal (ر.س)',
    'PKR' => 'Pakistani Rupee (₨)',
    'MYR' => 'Malaysian Ringgit (RM)',
    'THB' => 'Thai Baht (฿)',
    'KRW' => 'South Korean Won (₩)'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Financial Entry - <?php echo htmlspecialchars($office['business_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #2ecc71;
            --warning: #e74c3c;
            --light: #ffffff;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Navigation */
        .finance-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: var(--light);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            height: 80px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background-color: var(--background);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .subscription-badge {
            background-color: var(--accent);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Main Content */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 40px 0;
        }

        /* Form Container */
        .form-container {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .form-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .required::after {
            content: " *";
            color: var(--warning);
        }

        select, input[type="text"], input[type="number"], input[type="date"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light);
        }

        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--secondary);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--gray);
        }

        .file-upload-label i {
            font-size: 2.5rem;
            color: var(--secondary);
        }

        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }

        .submit-btn, .cancel-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn {
            background-color: var(--accent);
            color: white;
        }

        .submit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .cancel-btn {
            background-color: var(--light);
            color: var(--dark);
            border: 2px solid var(--border);
        }

        .cancel-btn:hover {
            background-color: var(--background);
            border-color: var(--gray);
        }

        /* Account Categories */
        .account-categories {
            background-color: var(--background);
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .category-section {
            margin-bottom: 15px;
        }

        .category-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--border);
        }

        .category-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .category-tag {
            background-color: var(--light);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            border: 1px solid var(--border);
            color: var(--dark);
        }

        /* Error Message */
        .error-message {
            background-color: #fdecea;
            color: var(--warning);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--warning);
            display: <?php echo isset($error_message) ? 'block' : 'none'; ?>;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .finance-nav {
                padding: 0 15px;
                height: 70px;
            }

            .page-title {
                font-size: 1.2rem;
            }

            .form-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="finance-nav">
        <div class="nav-left">
            <a href="finance.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Finance
            </a>
            <div class="page-title">Create Financial Entry</div>
        </div>
        <div class="subscription-badge">
            <i class="fas fa-clock"></i> <?php echo $days_left; ?> Days Left
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Form Container -->
            <div class="form-container">
                <div class="form-header">
                    <h1><i class="fas fa-file-invoice-dollar"></i> New Financial Entry</h1>
                    <p>Create a new journal or adjustment entry for <?php echo htmlspecialchars($office['business_name']); ?></p>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Entry Type -->
                    <div class="form-group">
                        <label class="required">Entry Type</label>
                        <select name="entry_type" required>
                            <option value="">Select Entry Type</option>
                            <option value="journal">Journal Entry</option>
                            <option value="adjustment">Adjustment Entry</option>
                        </select>
                    </div>

                    <!-- Document Upload -->
                    <div class="form-group">
                        <label>Supporting Document (PDF Only)</label>
                        <div class="file-upload">
                            <input type="file" name="document" accept=".pdf,application/pdf">
                            <div class="file-upload-label">
                                <i class="fas fa-file-pdf"></i>
                                <span>Click to upload PDF document</span>
                                <span>Max file size: 10MB</span>
                            </div>
                        </div>
                        <div class="file-info">
                            <i class="fas fa-info-circle"></i> Optional: Upload invoice, receipt, or supporting document
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label class="required">Entry Date (DD/MM/YYYY)</label>
                        <input type="text" name="entry_date" id="entry_date" 
                               placeholder="DD/MM/YYYY" required
                               pattern="\d{2}/\d{2}/\d{4}"
                               title="Please enter date in DD/MM/YYYY format">
                    </div>

                    <!-- Account Categories Info -->
                    <div class="account-categories">
                        <div class="category-section">
                            <div class="category-title">Assets</div>
                            <div class="category-tags">
                                <?php foreach($assets_accounts as $account): ?>
                                    <span class="category-tag"><?php echo $account; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="category-section">
                            <div class="category-title">Liabilities</div>
                            <div class="category-tags">
                                <?php foreach($liabilities_accounts as $account): ?>
                                    <span class="category-tag"><?php echo $account; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="category-section">
                            <div class="category-title">Equity</div>
                            <div class="category-tags">
                                <?php foreach($equity_accounts as $account): ?>
                                    <span class="category-tag"><?php echo $account; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Debit and Credit Accounts -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Debit Account</label>
                            <select name="debit_account" required>
                                <option value="">Select Debit Account</option>
                                <?php foreach($all_accounts as $account): ?>
                                    <option value="<?php echo htmlspecialchars($account); ?>">
                                        <?php echo $account; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="required">Credit Account</label>
                            <select name="credit_account" required>
                                <option value="">Select Credit Account</option>
                                <?php foreach($all_accounts as $account): ?>
                                    <option value="<?php echo htmlspecialchars($account); ?>">
                                        <?php echo $account; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Amount and Currency -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Transaction Amount</label>
                            <input type="number" name="transaction_amount" 
                                   step="0.01" min="0" required 
                                   placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label class="required">Currency</label>
                            <select name="currency" required>
                                <option value="">Select Currency</option>
                                <?php foreach($currencies as $code => $name): ?>
                                    <option value="<?php echo $code; ?>">
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Reference -->
                    <div class="form-group">
                        <label>Reference Number (Optional)</label>
                        <input type="text" name="reference" 
                               placeholder="Invoice #, Receipt #, etc.">
                    </div>

                    <!-- Comments -->
                    <div class="form-group">
                        <label>Comments (Optional)</label>
                        <textarea name="comments" 
                                  placeholder="Add any additional notes or description about this entry..."></textarea>
                    </div>

                    <!-- User Info -->
                    <div class="form-group" style="background-color: var(--background); padding: 15px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <small style="color: var(--gray);">Creating as:</small><br>
                                <strong><?php echo htmlspecialchars($user['fullname']); ?></strong> (<?php echo $user_position; ?>)
                            </div>
                            <div style="text-align: right;">
                                <small style="color: var(--gray);">Office:</small><br>
                                <strong><?php echo htmlspecialchars($office['business_name']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-check-circle"></i> Create Entry
                        </button>
                        <a href="finance.php" class="cancel-btn">
                            <i class="fas fa-times-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Information Section -->
            <div style="background-color: #e8f4fd; border-radius: 10px; padding: 20px; margin-top: 20px;">
                <h3 style="color: var(--primary); margin-bottom: 10px;">
                    <i class="fas fa-lightbulb"></i> Quick Tips:
                </h3>
                <ul style="color: var(--dark); padding-left: 20px;">
                    <li>Ensure debit and credit accounts are different</li>
                    <li>Date format must be DD/MM/YYYY</li>
                    <li>Only PDF documents are accepted for upload</li>
                    <li>All entries require approval from office authority</li>
                    <li>Keep transaction amounts accurate and verifiable</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Date picker
            flatpickr("#entry_date", {
                dateFormat: "d/m/Y",
                maxDate: "today",
                allowInput: true
            });

            // File upload preview
            const fileInput = document.querySelector('input[type="file"]');
            const fileUploadLabel = document.querySelector('.file-upload-label span');
            
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const fileName = this.files[0].name;
                        fileUploadLabel.textContent = fileName;
                        
                        // Check file size
                        const fileSize = this.files[0].size;
                        const maxSize = 10 * 1024 * 1024; // 10MB
                        
                        if (fileSize > maxSize) {
                            alert('File size exceeds 10MB limit. Please choose a smaller file.');
                            this.value = '';
                            fileUploadLabel.textContent = 'Click to upload PDF document';
                        }
                        
                        // Check file type
                        const fileType = this.files[0].type;
                        if (fileType !== 'application/pdf') {
                            alert('Only PDF files are allowed.');
                            this.value = '';
                            fileUploadLabel.textContent = 'Click to upload PDF document';
                        }
                    }
                });
            }

            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const debitAccount = document.querySelector('select[name="debit_account"]').value;
                const creditAccount = document.querySelector('select[name="credit_account"]').value;
                
                if (debitAccount === creditAccount && debitAccount !== '') {
                    e.preventDefault();
                    alert('Debit and Credit accounts cannot be the same. Please select different accounts.');
                    return false;
                }
                
                // Date validation
                const dateInput = document.getElementById('entry_date').value;
                const dateRegex = /^\d{2}\/\d{2}\/\d{4}$/;
                if (!dateRegex.test(dateInput)) {
                    e.preventDefault();
                    alert('Please enter date in DD/MM/YYYY format.');
                    return false;
                }
                
                // Check if date is valid
                const dateParts = dateInput.split('/');
                const day = parseInt(dateParts[0], 10);
                const month = parseInt(dateParts[1], 10);
                const year = parseInt(dateParts[2], 10);
                
                const date = new Date(year, month - 1, day);
                if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                    e.preventDefault();
                    alert('Please enter a valid date.');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>