<?php
require_once 'config.php';
check_login(); // Ensure user is logged in

// Get user information from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM user_list WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consistent Business Assistant - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* All existing CSS remains the same, just add modal styles */
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
        }

        body {
            color: #333;
            line-height: 1.6;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
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

        .user-menu {
            position: relative;
        }

        .user-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            padding: 60px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            max-width: 800px;
        }

        .welcome-section h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .welcome-section p {
            font-size: 1.2rem;
            color: var(--gray);
        }

        .options-container {
            display: flex;
            gap: 30px;
            width: 100%;
            max-width: 1000px;
        }

        .option-card {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .option-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .option-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            color: white;
            font-size: 2rem;
        }

        .option-card h3 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .option-card p {
            color: var(--gray);
            margin-bottom: 25px;
            flex-grow: 1;
        }

        .toggle-btn {
            padding: 12px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .toggle-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Forms */
        .form-container {
            width: 100%;
            max-width: 800px;
            margin-top: 40px;
            display: none;
        }

        .form-container.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form {
            background-color: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .form-title {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input, select {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        input:focus, select:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .file-input {
            padding: 10px;
        }

        .file-info {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-btn i {
            margin-right: 8px;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .options-container {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .welcome-section h2 {
                font-size: 2rem;
            }
            
            .welcome-section p {
                font-size: 1rem;
            }
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
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

        /* Add this to the existing dropdown-menu class */
        .dropdown-menu a {
            cursor: pointer;
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
            <div class="user-menu">
                <div class="user-icon" id="user-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="dropdown-menu" id="dropdown-menu">
                    <ul>
                        <li>
                            <a id="open-settings">
                                <i class="fas fa-cog"></i>
                                <span>Account Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Log Out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <div class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($user['fullname']); ?>!</h2>
                <p>Create a new virtual office or access your existing one to manage your business operations</p>
            </div>

            <div class="options-container">
                <div class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>Create an Office</h3>
                    <p>Set up your virtual office with our comprehensive business tools for management, marketing, and finance.</p>
                    <button class="toggle-btn" data-target="create-office-form">Create Office</button>
                </div>

                <div class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3>Log into an Office</h3>
                    <p>Access your existing virtual office to continue managing your business operations and data.</p>
                    <button class="toggle-btn" data-target="login-office-form">Log Into Office</button>
                </div>
            </div>

            <!-- Create Office Form -->
            <div id="create-office-form" class="form-container">
                <div class="form">
                    <h2 class="form-title">Create Your Virtual Office</h2>
                    
                    <form method="POST" action="create_office.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="business-name">Business Name</label>
                            <input type="text" id="business-name" name="business_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business-logo">Business Logo</label>
                            <input type="file" id="business-logo" name="business_logo" accept=".png" class="file-input" required>
                            <div class="file-info">Must be a PNG file, under 100 MB</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="business-email">Business Email</label>
                            <input type="email" id="business-email" name="business_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="office-address">Main Office Address</label>
                            <input type="text" id="office-address" name="office_address" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="country-code">Country Code</label>
                                <select id="country-code" name="country_code" required>
                                    <option value="">Select Country</option>
                                    <option value="93">Afghanistan</option>
                                    <option value="971">UAE</option>
                                    <option value="880">Bangladesh</option>
                                    <option value="975">Bhutan</option>
                                    <option value="1">Canada</option>
                                    <option value="86">China</option>
                                    <option value="33">France</option>
                                    <option value="49">Germany</option>
                                    <option value="91">India</option>
                                    <option value="98">Iran</option>
                                    <option value="964">Iraq</option>
                                    <option value="81">Japan</option>
                                    <option value="965">Kuwait</option>
                                    <option value="961">Lebanon</option>
                                    <option value="60">Malaysia</option>
                                    <option value="95">Myanmar</option>
                                    <option value="977">Nepal</option>
                                    <option value="92">Pakistan</option>
                                    <option value="974">Qatar</option>
                                    <option value="7">Russia</option>
                                    <option value="966">Saudi Arabia</option>
                                    <option value="65">Singapore</option>
                                    <option value="82">South Korea</option>
                                    <option value="94">Sri Lanka</option>
                                    <option value="46">Sweden</option>
                                    <option value="41">Switzerland</option>
                                    <option value="963">Syria</option>
                                    <option value="66">Thailand</option>
                                    <option value="971">UAE</option>
                                    <option value="44">UK</option>
                                    <option value="1">USA</option>
                                    <option value="84">Vietnam</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact-number">Contact Number</label>
                                <input type="tel" id="contact-number" name="contact_number" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">Create Virtual Office</button>
                    </form>
                    
                    <a href="#" class="back-btn" data-target="main-options">
                        <i class="fas fa-arrow-left"></i>
                        Back to Options
                    </a>
                </div>
            </div>

            <!-- Log Into Office Form -->
            <div id="login-office-form" class="form-container">
                <div class="form">
                    <h2 class="form-title">Log Into Your Virtual Office</h2>
                    
                    <form method="POST" action="login_office.php">
                        <div class="form-group">
                            <label for="office-email">Office Email</label>
                            <input type="email" id="office-email" name="office_email" required>
                        </div>
                        
                        <button type="submit" class="submit-btn">Access Office</button>
                    </form>
                    
                    <a href="#" class="back-btn" data-target="main-options">
                        <i class="fas fa-arrow-left"></i>
                        Back to Options
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Settings Modal -->
    <div id="account-settings-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Account Settings</h3>
                <button class="close-modal" id="close-settings">&times;</button>
            </div>
            <form method="POST" action="account_settings.php">
                <div class="form-group">
                    <label for="edit-fullname">Full Name</label>
                    <input type="text" id="edit-fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-gender">Gender</label>
                    <select id="edit-gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        <option value="Prefer not to say" <?php echo ($user['gender'] == 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-mobile">Mobile Number</label>
                    <input type="tel" id="edit-mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit-password" name="password">
                </div>
                
                <button type="submit" class="submit-btn">Update Account</button>
            </form>
        </div>
    </div>

    <script>
        // User dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.getElementById('user-icon');
            const dropdownMenu = document.getElementById('dropdown-menu');
            
            userIcon.addEventListener('click', function() {
                dropdownMenu.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userIcon.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    dropdownMenu.classList.remove('active');
                }
            });
            
            // Account Settings Modal
            const openSettingsBtn = document.getElementById('open-settings');
            const closeSettingsBtn = document.getElementById('close-settings');
            const settingsModal = document.getElementById('account-settings-modal');
            
            openSettingsBtn.addEventListener('click', function() {
                settingsModal.style.display = 'block';
                dropdownMenu.classList.remove('active');
            });
            
            closeSettingsBtn.addEventListener('click', function() {
                settingsModal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target == settingsModal) {
                    settingsModal.style.display = 'none';
                }
            });
            
            // Form toggle functionality (existing code)
            const toggleButtons = document.querySelectorAll('.toggle-btn');
            const formContainers = document.querySelectorAll('.form-container');
            const optionsContainer = document.querySelector('.options-container');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetForm = this.getAttribute('data-target');
                    
                    // Hide options and show target form
                    optionsContainer.style.display = 'none';
                    
                    formContainers.forEach(container => {
                        if (container.id === targetForm) {
                            container.classList.add('active');
                        } else {
                            container.classList.remove('active');
                        }
                    });
                });
            });
            
            // Back button functionality
            const backButtons = document.querySelectorAll('.back-btn');
            
            backButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = this.getAttribute('data-target');
                    
                    // Hide all forms and show options
                    formContainers.forEach(container => {
                        container.classList.remove('active');
                    });
                    
                    optionsContainer.style.display = 'flex';
                });
            });
        });
    </script>
</body>
</html>