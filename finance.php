<?php
// finance.php - Finance Department Dashboard
require_once 'config.php';
check_office_login(); // Ensure user is logged into an office

// Get office information
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

// Check if subscription is active
if ($days_left <= 0) {
    echo "<script>alert('Your subscription has expired. Please renew to access the Finance Department.'); window.location.href='office_dashboard.php';</script>";
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Get user's position in the office
$user_email = $user['email'];
$office_email = $_SESSION['office_email'];
$position_sql = "SELECT position FROM office_request WHERE user_email = '$user_email' AND office_email = '$office_email' AND status = 'Yes'";
$position_result = mysqli_query($conn, $position_sql);
$position_data = mysqli_fetch_assoc($position_result);
$user_position = $position_data ? $position_data['position'] : 'member';

// Check permissions for creating entries (only founder/managers can create)
$can_create_entry = in_array($user_position, ['founder', 'manager', 'finance_manager']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Department - <?php echo htmlspecialchars($office['business_name']); ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            padding: 40px 0;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .welcome-section p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 30px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-align: center;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--secondary);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2.5rem;
        }

        .dashboard-card h3 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .dashboard-card p {
            color: var(--gray);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .card-btn {
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
            max-width: 200px;
        }

        .card-btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .card-btn:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
            transform: none;
        }

        /* Recent Activity */
        .recent-activity {
            margin-top: 60px;
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .recent-activity h2 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border);
            transition: background-color 0.3s;
        }

        .activity-item:hover {
            background-color: var(--background);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--secondary);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .activity-time {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .activity-amount {
            font-weight: 600;
            color: var(--accent);
        }

        .permission-note {
            color: var(--warning);
            font-size: 0.9rem;
            margin-top: 10px;
            font-style: italic;
            text-align: center;
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

            .welcome-section h1 {
                font-size: 2rem;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="finance-nav">
        <div class="nav-left">
            <button class="back-btn" onclick="window.location.href='office_dashboard.php'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
            <div class="page-title">Finance Department</div>
        </div>
        <div class="subscription-badge">
            <i class="fas fa-clock"></i> <?php echo $days_left; ?> Days Left
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <h1>Financial Management</h1>
                <p>Welcome to the Finance Department of <?php echo htmlspecialchars($office['business_name']); ?>. 
                   Manage financial entries, generate reports, and track your business finances efficiently.</p>
                <div style="background-color: var(--accent); color: white; padding: 10px 20px; border-radius: 10px; display: inline-block;">
                    <i class="fas fa-user-tag"></i> Your Role: <?php echo ucfirst($user_position); ?>
                </div>
            </section>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <!-- Create Entry Card -->
                <div class="dashboard-card" onclick="<?php echo $can_create_entry ? "window.location.href='create_financial_entry.php'" : "alert('Only founders, managers, and finance managers can create entries.')"; ?>">
                    <div class="card-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Create Entry</h3>
                    <p>Record new financial transactions, expenses, revenues, and other financial activities.</p>
                    <button class="card-btn" <?php echo $can_create_entry ? '' : 'disabled'; ?>>
                        <i class="fas fa-edit"></i> Create New Entry
                    </button>
                    <?php if (!$can_create_entry): ?>
                        <p class="permission-note">Contact your office manager for create permissions</p>
                    <?php endif; ?>
                </div>

                <!-- Financial Reports Card -->
                <div class="dashboard-card" onclick="window.location.href='financial_reports.php'">
                    <div class="card-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Financial Reports</h3>
                    <p>View and generate comprehensive financial reports, analytics, and business insights.</p>
                    <button class="card-btn">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </button>
                </div>

                <!-- Manage Entries Card -->
                <div class="dashboard-card" onclick="window.location.href='manage_entries.php'">
                    <div class="card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Manage Entries</h3>
                    <p>View, edit, delete, and manage all financial entries in your system.</p>
                    <button class="card-btn">
                        <i class="fas fa-cog"></i> Manage
                    </button>
                </div>
            </div>

            <!-- User Info -->
            <div style="text-align: center; margin-top: 40px; color: var(--gray); font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> 
                Logged in as: <?php echo htmlspecialchars($user['fullname']); ?> | 
                Office: <?php echo htmlspecialchars($office['business_name']); ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Card hover effects
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                const btn = card.querySelector('.card-btn');
                if (!btn.disabled) {
                    card.addEventListener('mouseenter', () => {
                        card.style.transform = 'translateY(-5px)';
                    });
                    card.addEventListener('mouseleave', () => {
                        card.style.transform = 'translateY(0)';
                    });
                }
            });

            // Check subscription status every 5 minutes
            setInterval(() => {
                // This could be enhanced with an AJAX call to check subscription
                console.log('Subscription check: <?php echo $days_left; ?> days remaining');
            }, 300000); // 5 minutes
        });
    </script>
</body>
</html>