<?php
// manage_entries.php - Manage Financial Entries
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
    echo "<script>alert('Your subscription has expired. Please renew to manage entries.'); window.location.href='finance.php';</script>";
    exit();
}

// Get office email
$office_email = $_SESSION['office_email'];

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = sanitize_input($_GET['delete_id'], $conn);
    
    // Verify the entry belongs to this office
    $verify_sql = "SELECT * FROM user_finance_entry WHERE id = '$delete_id' AND office_email = '$office_email'";
    $verify_result = mysqli_query($conn, $verify_sql);
    
    if (mysqli_num_rows($verify_result) > 0) {
        // Log before deleting
        $log_sql = "INSERT INTO finance_logs (entry_id, office_email, action, timestamp) 
                   VALUES ('$delete_id', '$office_email', 'deleted', NOW())";
        mysqli_query($conn, $log_sql);
        
        // Delete the entry
        $delete_sql = "DELETE FROM user_finance_entry WHERE id = '$delete_id'";
        if (mysqli_query($conn, $delete_sql)) {
            $success_message = "Entry deleted successfully!";
        } else {
            $error_message = "Error deleting entry: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Entry not found or you don't have permission to delete it.";
    }
}

// Get all entries for this office
$entries_sql = "SELECT * FROM user_finance_entry WHERE office_email = '$office_email' ORDER BY entry_date DESC, id DESC";
$entries_result = mysqli_query($conn, $entries_sql);
$total_entries = mysqli_num_rows($entries_result);

// Calculate totals
$total_amount = 0;
$entries = [];
while ($row = mysqli_fetch_assoc($entries_result)) {
    $entries[] = $row;
    $total_amount += $row['transaction_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Financial Entries - <?php echo htmlspecialchars($office['business_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 40px 0;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, #3498db 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .stat-card.accent {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Entries Grid */
        .entries-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .entries-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 10px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            width: 300px;
        }

        .search-box button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        .entries-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        /* Entry Card */
        .entry-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border-left: 4px solid var(--secondary);
        }

        .entry-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .entry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .entry-id {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
            background: var(--background);
            padding: 4px 12px;
            border-radius: 15px;
        }

        .entry-type {
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .entry-type.journal {
            background-color: #3498db;
        }

        .entry-type.adjustment {
            background-color: #9b59b6;
        }

        .entry-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border);
        }

        .detail-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--primary);
        }

        .detail-value.amount {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .entry-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .details-btn {
            background-color: var(--secondary);
            color: white;
        }

        .details-btn:hover {
            background-color: #2980b9;
        }

        .delete-btn {
            background-color: var(--warning);
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--primary) 0%, #3498db 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .modal-section h4 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
            word-break: break-word;
        }

        .info-value.amount {
            color: var(--accent);
            font-size: 1.3rem;
        }

        .no-entries {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .no-entries i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .no-entries h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .no-entries p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Message Styles */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--accent);
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--warning);
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

            .entries-grid {
                grid-template-columns: 1fr;
            }

            .search-box input {
                width: 200px;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
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
            <div class="page-title">Manage Financial Entries</div>
        </div>
        <div class="subscription-badge">
            <i class="fas fa-clock"></i> <?php echo $days_left; ?> Days Left
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <?php if (isset($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <div class="stat-value"><?php echo $total_entries; ?></div>
                    <div class="stat-label">Total Entries</div>
                </div>
                
                <div class="stat-card accent">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-value"><?php echo number_format($total_amount, 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
                
                <div class="stat-card warning">
                    <i class="fas fa-business-time"></i>
                    <div class="stat-value"><?php echo $days_left; ?></div>
                    <div class="stat-label">Subscription Days Left</div>
                </div>
            </div>

            <!-- Entries Section -->
            <div class="entries-header">
                <h2><i class="fas fa-list-ul"></i> Financial Entries</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search entries...">
                    <button onclick="searchEntries()"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>

            <!-- Entries Grid -->
            <div class="entries-grid" id="entriesContainer">
                <?php if (empty($entries)): ?>
                    <div class="no-entries">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Financial Entries Found</h3>
                        <p>You haven't created any financial entries yet.</p>
                        <a href="create_financial_entry.php" style="background-color: var(--secondary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 15px;">
                            <i class="fas fa-plus-circle"></i> Create Your First Entry
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($entries as $entry): 
                        $entry_date = date('d/m/Y', strtotime($entry['entry_date']));
                        $entry_id = str_pad($entry['id'], 6, '0', STR_PAD_LEFT);
                    ?>
                        <div class="entry-card" data-id="<?php echo $entry['id']; ?>">
                            <div class="entry-header">
                                <span class="entry-id">#<?php echo $entry_id; ?></span>
                                <span class="entry-type <?php echo $entry['entry_type']; ?>">
                                    <?php echo ucfirst($entry['entry_type']); ?>
                                </span>
                            </div>
                            
                            <div class="entry-details">
                                <div class="detail-row">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value"><?php echo $entry_date; ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Debit:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($entry['debit_account']); ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Credit:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($entry['credit_account']); ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Amount:</span>
                                    <span class="detail-value amount">
                                        <?php echo number_format($entry['transaction_amount'], 2); ?> 
                                        <?php echo $entry['currency']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="entry-actions">
                                <button class="action-btn details-btn" onclick="showEntryDetails(<?php echo htmlspecialchars(json_encode($entry)); ?>)">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $entry['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Entry Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Show entry details in modal
        function showEntryDetails(entry) {
            const modal = document.getElementById('detailsModal');
            const modalBody = document.getElementById('modalBody');
            
            // Format date
            const entryDate = new Date(entry.entry_date);
            const formattedDate = entryDate.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            // Create modal content
            modalBody.innerHTML = `
                <div class="modal-section">
                    <h4>Basic Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Entry ID</div>
                            <div class="info-value">#${String(entry.id).padStart(6, '0')}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Type</div>
                            <div class="info-value" style="text-transform: capitalize;">${entry.entry_type}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value">${formattedDate}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value" style="color: ${entry.status === 'approved' ? '#2ecc71' : entry.status === 'rejected' ? '#e74c3c' : '#f39c12'}">
                                ${entry.status.charAt(0).toUpperCase() + entry.status.slice(1)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h4>Transaction Details</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Debit Account</div>
                            <div class="info-value">${entry.debit_account}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Credit Account</div>
                            <div class="info-value">${entry.credit_account}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Amount</div>
                            <div class="info-value amount">${parseFloat(entry.transaction_amount).toFixed(2)} ${entry.currency}</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h4>Additional Information</h4>
                    <div class="info-grid">
                        ${entry.reference ? `
                        <div class="info-item">
                            <div class="info-label">Reference</div>
                            <div class="info-value">${entry.reference}</div>
                        </div>
                        ` : ''}
                        <div class="info-item">
                            <div class="info-label">Created By</div>
                            <div class="info-value">${entry.user_email}</div>
                        </div>
                    </div>
                </div>
                
                ${entry.comments ? `
                <div class="modal-section">
                    <h4>Comments</h4>
                    <div class="info-item">
                        <div class="info-value" style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-style: italic;">
                            ${entry.comments}
                        </div>
                    </div>
                </div>
                ` : ''}
                
                ${entry.document_path ? `
                <div class="modal-section">
                    <h4>Document</h4>
                    <div class="info-item">
                        <a href="${entry.document_path}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: #3498db; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none;">
                            <i class="fas fa-file-pdf"></i> View Document
                        </a>
                    </div>
                </div>
                ` : ''}
            `;
            
            modal.style.display = 'flex';
        }

        // Close modal
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Confirm delete
        function confirmDelete(entryId) {
            if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                window.location.href = `manage_entries.php?delete_id=${entryId}`;
            }
        }

        // Search entries
        function searchEntries() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const entries = document.querySelectorAll('.entry-card');
            let found = false;
            
            entries.forEach(entry => {
                const text = entry.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    entry.style.display = 'block';
                    found = true;
                } else {
                    entry.style.display = 'none';
                }
            });
            
            // If no entries found, show message
            const container = document.getElementById('entriesContainer');
            let noResults = container.querySelector('.no-results');
            
            if (!found && !noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-entries';
                noResults.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>No Matching Entries</h3>
                    <p>No entries found matching your search criteria.</p>
                `;
                container.appendChild(noResults);
            } else if (found && noResults) {
                noResults.remove();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Initialize search on Enter key
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchEntries();
            }
        });
    </script>
</body>
</html>