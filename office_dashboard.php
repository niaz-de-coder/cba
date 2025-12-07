<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consistent Business Assistant - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --success: #2ecc71;
            --warning: #f39c12;
        }

        body {
            color: #333;
            line-height: 1.6;
            background-color: #f5f7fa;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 50px;
            width: 50px;
            margin-right: 10px;
            background-color: var(--primary);
            border-radius: 8px;
            padding: 5px;
        }

        .logo h1 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .motto {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 3px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-icon, .office-logo {
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
        }

        .notification-icon:hover {
            background-color: #e0e7ed;
            transform: scale(1.05);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .office-logo img {
            height: 45px;
            width: 45px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .office-logo:hover img {
            border-color: var(--secondary);
        }

        .dropdown-menu {
            position: absolute;
            top: 55px;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 200px;
            padding: 10px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1000;
        }

        .dropdown-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu ul {
            list-style: none;
        }

        .dropdown-menu li {
            padding: 12px 20px;
            transition: background-color 0.3s;
        }

        .dropdown-menu li:hover {
            background-color: #f5f7fa;
        }

        .dropdown-menu a {
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .dropdown-menu i {
            margin-right: 10px;
            color: var(--secondary);
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            padding: 30px 0;
        }

        /* Subscription Banner */
        .subscription-banner {
            background: linear-gradient(135deg, var(--warning) 0%, #e67e22 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .banner-content h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .banner-content p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .pay-button {
            background-color: white;
            color: var(--warning);
            border: none;
            padding: 14px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .pay-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        /* Departments Section */
        .departments-section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 30px;
            text-align: center;
        }

        .departments-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .department-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .department-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .department-header {
            padding: 25px 25px 20px;
            border-bottom: 1px solid #eee;
        }

        .department-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 2rem;
            color: white;
        }

        .management .department-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .marketing .department-icon {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }

        .finance .department-icon {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .department-header h3 {
            font-size: 1.6rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .department-header p {
            color: var(--gray);
        }

        .department-content {
            padding: 25px;
            flex-grow: 1;
        }

        .features-list {
            list-style: none;
            margin-bottom: 25px;
        }

        .features-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
        }

        .features-list i {
            color: var(--success);
            margin-right: 10px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .enter-button {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
        }

        .enter-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Notification Panel */
        .notification-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100vh;
            background-color: white;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            padding: 25px;
            overflow-y: auto;
        }

        .notification-panel.active {
            right: 0;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .panel-header h3 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .close-panel {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-panel:hover {
            color: var(--accent);
        }

        .notification-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid var(--secondary);
        }

        .notification-item.unread {
            background-color: #e8f4fc;
            border-left-color: var(--accent);
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .notification-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 999;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .subscription-banner {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .departments-container {
                grid-template-columns: 1fr;
            }

            .notification-panel {
                width: 100%;
                right: -100%;
            }

            .header-container {
                flex-wrap: wrap;
            }

            .logo {
                margin-bottom: 10px;
            }

            .nav-right {
                margin-left: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <img src="assets/logo.png" alt="Consistent Business Assistant Logo">
                <div>
                    <h1>Consistent Business Assistant</h1>
                    <div class="motto">Keep Moving Forward</div>
                </div>
            </div>
            <div class="nav-right">
                <div class="notification-icon" id="notification-icon">
                    <i class="fas fa-bell"></i>
                    <div class="notification-badge">3</div>
                </div>
                <div class="office-logo" id="office-logo">
                    <img src="assets/office_logo.png" alt="Office Logo">
                    <div class="dropdown-menu" id="office-dropdown">
                        <ul>
                            <li>
                                <a href="#">
                                    <i class="fas fa-cog"></i>
                                    <span>Office Settings</span>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout from Office</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Subscription Banner -->
        <div class="subscription-banner">
            <div class="banner-content">
                <h2>Your Subscription is 30 Days Left</h2>
                <p>Renew your subscription to continue accessing all features</p>
            </div>
            <button class="pay-button">Pay Now</button>
        </div>

        <!-- Departments Section -->
        <section class="departments-section">
            <h2 class="section-title">Business Departments</h2>
            <div class="departments-container">
                <!-- Management Department -->
                <div class="department-card management">
                    <div class="department-header">
                        <div class="department-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Management Department</h3>
                        <p>Streamline your business operations and team management</p>
                    </div>
                    <div class="department-content">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Team performance tracking</li>
                            <li><i class="fas fa-check"></i> Project management tools</li>
                            <li><i class="fas fa-check"></i> Resource allocation</li>
                            <li><i class="fas fa-check"></i> Strategic planning</li>
                        </ul>
                        <a href="#" class="enter-button">Enter Management Department</a>
                    </div>
                </div>

                <!-- Marketing Department -->
                <div class="department-card marketing">
                    <div class="department-header">
                        <div class="department-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3>Marketing Department</h3>
                        <p>Develop and execute effective marketing strategies</p>
                    </div>
                    <div class="department-content">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Campaign management</li>
                            <li><i class="fas fa-check"></i> Customer analytics</li>
                            <li><i class="fas fa-check"></i> Social media integration</li>
                            <li><i class="fas fa-check"></i> Marketing performance reports</li>
                        </ul>
                        <a href="#" class="enter-button">Enter Marketing Department</a>
                    </div>
                </div>

                <!-- Finance Department -->
                <div class="department-card finance">
                    <div class="department-header">
                        <div class="department-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Finance Department</h3>
                        <p>Manage your finances and track business performance</p>
                    </div>
                    <div class="department-content">
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Financial reporting</li>
                            <li><i class="fas fa-check"></i> Expense tracking</li>
                            <li><i class="fas fa-check"></i> Budget planning</li>
                            <li><i class="fas fa-check"></i> Revenue analysis</li>
                        </ul>
                        <a href="#" class="enter-button">Enter Finance Department</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Notification Panel -->
    <div class="notification-panel" id="notification-panel">
        <div class="panel-header">
            <h3>Notifications</h3>
            <button class="close-panel" id="close-notification">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notification-list">
            <div class="notification-item unread">
                <div class="notification-title">New Financial Report Available</div>
                <p>Your Q3 financial report is ready for review.</p>
                <div class="notification-time">2 hours ago</div>
            </div>
            <div class="notification-item unread">
                <div class="notification-title">Marketing Campaign Performance</div>
                <p>Summer campaign exceeded targets by 15%.</p>
                <div class="notification-time">1 day ago</div>
            </div>
            <div class="notification-item">
                <div class="notification-title">Team Meeting Reminder</div>
                <p>Management team meeting scheduled for tomorrow at 10 AM.</p>
                <div class="notification-time">2 days ago</div>
            </div>
            <div class="notification-item">
                <div class="notification-title">System Update Completed</div>
                <p>The latest system update has been successfully installed.</p>
                <div class="notification-time">3 days ago</div>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <script>
        // Office Logo Dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const officeLogo = document.getElementById('office-logo');
            const officeDropdown = document.getElementById('office-dropdown');
            
            officeLogo.addEventListener('click', function(e) {
                e.stopPropagation();
                officeDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                officeDropdown.classList.remove('active');
            });
            
            // Notification Panel
            const notificationIcon = document.getElementById('notification-icon');
            const notificationPanel = document.getElementById('notification-panel');
            const closeNotification = document.getElementById('close-notification');
            const overlay = document.getElementById('overlay');
            
            notificationIcon.addEventListener('click', function() {
                notificationPanel.classList.add('active');
                overlay.classList.add('active');
            });
            
            closeNotification.addEventListener('click', function() {
                notificationPanel.classList.remove('active');
                overlay.classList.remove('active');
            });
            
            overlay.addEventListener('click', function() {
                notificationPanel.classList.remove('active');
                overlay.classList.remove('active');
            });
            
            // Pay Button Action
            const payButton = document.querySelector('.pay-button');
            payButton.addEventListener('click', function() {
                alert('Payment processing would be implemented here');
            });
            
            // Department Enter Buttons
            const enterButtons = document.querySelectorAll('.enter-button');
            enterButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const department = this.closest('.department-card').querySelector('h3').textContent;
                    alert(`Entering ${department} - This would navigate to the department dashboard in a real application`);
                });
            });
        });
    </script>
</body>
</html>