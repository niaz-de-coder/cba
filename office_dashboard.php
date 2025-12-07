<?php
// office_dashboard.php
require_once 'config.php';
check_office_login(); // Ensure user is logged into an office

// Get office information from database
$office_id = $_SESSION['office_id'];
$office_sql = "SELECT * FROM office_list WHERE id = '$office_id'";
$office_result = mysqli_query($conn, $office_sql);
$office = mysqli_fetch_assoc($office_result);

// Calculate subscription days
$purchase_date = new DateTime($office['purchase_date']);
$current_date = new DateTime();
$interval = $current_date->diff($purchase_date);
$days_left = 30 - $interval->days; // Assuming 30-day subscription
if ($days_left < 0) $days_left = 0;

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Get business logo path
$business_logo = !empty($office['business_logo']) ? $office['business_logo'] : 'assets/default-logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($office['business_name']); ?> - Office Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ffffff;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #17a2b8;
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
            padding-top: 80px; /* Space for fixed nav */
        }

        /* Fixed Navigation */
        .office-nav {
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

        .website-logo {
            height: 50px;
            width: auto;
        }

        .office-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background-color: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--primary);
            font-size: 1.2rem;
            border: 2px solid transparent;
        }

        .nav-icon:hover {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
            transform: translateY(-2px);
        }

        .office-logo-container {
            position: relative;
        }

        .office-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            cursor: pointer;
            border: 3px solid var(--border);
            transition: all 0.3s;
        }

        .office-logo:hover {
            transform: scale(1.05);
            border-color: var(--secondary);
        }

        .office-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            background-color: var(--light);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            width: 200px;
            padding: 15px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1001;
        }

        .office-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .office-dropdown button {
            width: 100%;
            padding: 12px 20px;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
            font-size: 0.95rem;
            transition: background-color 0.3s;
        }

        .office-dropdown button:hover {
            background-color: var(--background);
        }

        .office-dropdown i {
            color: var(--secondary);
            width: 20px;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 40px 0;
        }

        /* Subscription Section */
        .subscription-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.2);
            text-align: center;
        }

        .subscription-info h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .days-left {
            font-size: 4rem;
            font-weight: 700;
            color: var(--warning);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin: 10px 0;
        }

        .subscription-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .pay-now-btn {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .pay-now-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(46, 204, 113, 0.4);
        }

        /* Departments Section */
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .department-card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
            text-align: center;
        }

        .department-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--secondary);
        }

        .department-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        .department-card h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .department-card p {
            color: var(--gray);
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .entry-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .entry-btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
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

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--light);
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--accent);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .office-nav {
                padding: 0 15px;
                height: 70px;
            }

            .nav-right {
                gap: 10px;
            }

            .nav-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .office-logo {
                width: 40px;
                height: 40px;
            }

            .subscription-section {
                padding: 30px 20px;
            }

            .days-left {
                font-size: 3rem;
            }

            .departments-grid {
                grid-template-columns: 1fr;
            }
        }

        .user-info {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Fixed Navigation -->
    <nav class="office-nav">
        <div class="nav-left">
            <img src="assets/logo.png" alt="CBA Logo" class="website-logo">
            <div class="office-name"><?php echo htmlspecialchars($office['business_name']); ?></div>
        </div>

        <div class="nav-right">
            <div class="nav-icon" title="Access">
                <i class="fas fa-id-card"></i>
            </div>
            
            <div class="nav-icon" title="Notifications">
                <i class="fas fa-bell"></i>
            </div>

            <div class="office-logo-container">
                <img src="<?php echo htmlspecialchars($business_logo); ?>" 
                     alt="Business Logo" 
                     class="office-logo"
                     id="office-logo-btn">
                
                <div class="office-dropdown" id="office-dropdown">
                    <button id="office-settings-btn">
                        <i class="fas fa-cog"></i>
                        <span>Office Settings</span>
                    </button>
                    <button id="office-logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Office Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Subscription Section -->
            <section class="subscription-section">
                <div class="subscription-info">
                    <h2>Your Subscription Status</h2>
                    <div class="days-left"><?php echo $days_left; ?></div>
                    <p class="subscription-message">
                        <?php if($days_left > 0): ?>
                            Your subscription has <strong><?php echo $days_left; ?> days</strong> remaining
                        <?php else: ?>
                            Your subscription has expired. Please renew to continue access
                        <?php endif; ?>
                    </p>
                    <button class="pay-now-btn">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </button>
                </div>
            </section>

            <!-- Departments Section -->
            <div class="departments-grid">
                <!-- Finance Department -->
                <div class="department-card">
                    <div class="department-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Finance Department</h3>
                    <p>Manage your financial operations, track expenses, generate reports, and analyze business performance metrics.</p>
                    <button class="entry-btn">
                        <i class="fas fa-arrow-right"></i> Enter Finance Department
                    </button>
                </div>

                <!-- Management Department -->
                <div class="department-card">
                    <div class="department-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Management Department</h3>
                    <p>Oversee team members, assign tasks, monitor productivity, and manage organizational structure and workflows.</p>
                    <button class="entry-btn">
                        <i class="fas fa-arrow-right"></i> Enter Management Department
                    </button>
                </div>

                <!-- Marketing Department -->
                <div class="department-card">
                    <div class="department-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Marketing Department</h3>
                    <p>Create campaigns, analyze market trends, manage social media, and track customer engagement metrics.</p>
                    <button class="entry-btn">
                        <i class="fas fa-arrow-right"></i> Enter Marketing Department
                    </button>
                </div>
            </div>

            <!-- User Info -->
            <div class="user-info">
                Logged in as: <?php echo htmlspecialchars($user['fullname']); ?> | 
                Office: <?php echo htmlspecialchars($office['business_email']); ?>
            </div>
        </div>
    </div>

    <!-- Office Settings Modal -->
    <div class="modal" id="office-settings-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Office Settings</h3>
                <button class="close-modal" id="close-settings">&times;</button>
            </div>
            <form method="POST" action="update_office_settings.php" enctype="multipart/form-data">
                <input type="hidden" name="office_id" value="<?php echo $office_id; ?>">
                
                <div class="form-group">
                    <label for="business-name">Business Name</label>
                    <input type="text" id="business-name" name="business_name" 
                           value="<?php echo htmlspecialchars($office['business_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="business-logo">Business Logo</label>
                    <input type="file" id="business-logo" name="business_logo" accept=".png" class="file-input">
                    <small>Current logo: <?php echo basename($office['business_logo']); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="business-email">Business Email</label>
                    <input type="email" id="business-email" name="business_email" 
                           value="<?php echo htmlspecialchars($office['business_email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="office-address">Office Address</label>
                    <input type="text" id="office-address" name="office_address" 
                           value="<?php echo htmlspecialchars($office['office_address']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact-number">Contact Number</label>
                    <div style="display: flex; gap: 10px;">
                        <select id="country-code" name="country_code" style="width: 120px;">
                            <option value="<?php echo htmlspecialchars($office['country_code']); ?>">
                                +<?php echo htmlspecialchars($office['country_code']); ?>
                            </option>
                            <!-- Add country options as in create_office.php -->
                        </select>
                        <input type="tel" id="contact-number" name="contact_number" 
                               value="<?php echo htmlspecialchars($office['contact_number']); ?>" required
                               style="flex: 1;">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" style="width: 100%; padding: 15px; margin-top: 20px;">
                    Update Office Settings
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Office logo dropdown
            const officeLogoBtn = document.getElementById('office-logo-btn');
            const officeDropdown = document.getElementById('office-dropdown');
            
            officeLogoBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                officeDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                officeDropdown.classList.remove('active');
            });
            
            // Office settings modal
            const officeSettingsBtn = document.getElementById('office-settings-btn');
            const closeSettingsBtn = document.getElementById('close-settings');
            const officeSettingsModal = document.getElementById('office-settings-modal');
            
            officeSettingsBtn.addEventListener('click', function() {
                officeDropdown.classList.remove('active');
                officeSettingsModal.classList.add('active');
            });
            
            closeSettingsBtn.addEventListener('click', function() {
                officeSettingsModal.classList.remove('active');
            });
            
            // Office logout
            const officeLogoutBtn = document.getElementById('office-logout-btn');
            
            officeLogoutBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to log out of this office?')) {
                    window.location.href = 'logout_office.php';
                }
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === officeSettingsModal) {
                    officeSettingsModal.classList.remove('active');
                }
            });
            
            // Department buttons (placeholder functionality)
            document.querySelectorAll('.entry-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const department = this.closest('.department-card').querySelector('h3').textContent;
                    alert(`Entering ${department} - Feature coming soon!`);
                });
            });
            
            // Access and Notification buttons (placeholder)
            document.querySelectorAll('.nav-icon').forEach(icon => {
                if (!icon.querySelector('.fa-id-card') && !icon.querySelector('.fa-bell')) {
                    return;
                }
                
                icon.addEventListener('click', function() {
                    const title = this.getAttribute('title');
                    alert(`${title} feature is under development!`);
                });
            });
            
            // Pay Now button
            document.querySelector('.pay-now-btn').addEventListener('click', function() {
                alert('Payment gateway integration coming soon!');
            });
        });
    </script>
</body>
</html>