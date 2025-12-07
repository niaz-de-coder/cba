<?php
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['signup'])) {
        // Registration form submitted
        require_once 'register.php';
    } elseif (isset($_POST['signin'])) {
        // Login form submitted
        require_once 'login.php';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consistent Business Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* All CSS styles remain the same as before */
        /* ... (keep all existing CSS) ... */
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        /* Left Panel - Branding */
        .branding-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .branding-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
            opacity: 0.2;
        }

        .branding-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 60px;
            width: 60px;
            margin-right: 15px;
            background-color: white;
            border-radius: 10px;
            padding: 5px;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .motto {
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 300;
            opacity: 0.9;
        }

        .features {
            list-style: none;
            margin-top: 30px;
        }

        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .features li i {
            margin-right: 10px;
            color: var(--success);
        }

        /* Right Panel - Forms */
        .forms-panel {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            width: 100%;
        }

        .form-toggle {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .toggle-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .toggle-btn.active {
            color: var(--primary);
        }

        .toggle-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--secondary);
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
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
            padding: 12px 15px;
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

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-top: 20px;
        }

        .checkbox-group input {
            width: auto;
            margin-right: 10px;
            margin-top: 5px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }

        .checkbox-group a {
            color: var(--secondary);
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .submit-btn {
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
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: var(--secondary);
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .branding-panel {
                padding: 30px 25px;
            }
            
            .forms-panel {
                padding: 30px 25px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Panel - Branding -->
        <div class="branding-panel">
            <div class="branding-content">
                <div class="logo">
                    <img src="assets/logo.png" alt="Consistent Business Assistant Logo">
                    <h1>Consistent Business Assistant</h1>
                </div>
                <p class="motto">Keep Moving Forward</p>
                <p>Your trusted partner for virtual office solutions. Streamline your business operations with our comprehensive tools for management, marketing, and finance.</p>
                
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Virtual Office Management</li>
                    <li><i class="fas fa-check-circle"></i> Financial Document Storage</li>
                    <li><i class="fas fa-check-circle"></i> Marketing & Financial Reports</li>
                    <li><i class="fas fa-check-circle"></i> No Physical Office Required</li>
                </ul>
            </div>
        </div>

        <!-- Right Panel - Forms -->
        <div class="forms-panel">
            <div class="form-container">
                <div class="form-toggle">
                    <button class="toggle-btn active" data-form="signup">Sign Up</button>
                    <button class="toggle-btn" data-form="signin">Sign In</button>
                </div>

                <!-- Sign Up Form -->
                <form id="signup-form" class="form active" method="POST" action="">
                    <h2 class="form-title">Create Your Account</h2>
                    
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country-code">Country Code</label>
                            <select id="country-code" name="country_code" required>
                                <option value="">Select Country</option>
                                <option value="93">Afghanistan</option>
                                <option value="971">UAE</option>
                                <option value="973">Bahrain</option>
                                <option value="880">Bangladesh</option>
                                <option value="975">Bhutan</option>
                                <option value="1">Canada</option>
                                <option value="86">China</option>
                                <option value="33">France</option>
                                <option value="49">Germany</option>
                                <option value="852">Hong Kong</option>
                                <option value="91">India</option>
                                <option value="98">Iran</option>
                                <option value="964">Iraq</option>
                                <option value="81">Japan</option>
                                <option value="965">Kuwait</option>
                                <option value="961">Lebanon</option>
                                <option value="60">Malaysia</option>
                                <option value="95">Myanmar</option>
                                <option value="977">Nepal</option>
                                <option value="234">Nigeria</option>
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
                                <option value="380">Ukraine</option>
                                <option value="1">USA</option>
                                <option value="84">Vietnam</option>
                                <option value="263">Zimbabwe</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mobile">Contact Number</label>
                            <input type="tel" id="mobile" name="mobile" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="policy" name="policy" required>
                        <label for="policy">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    
                    <button type="submit" name="signup" class="submit-btn">Create Account</button>
                </form>

                <!-- Sign In Form -->
                <form id="signin-form" class="form" method="POST" action="">
                    <h2 class="form-title">Welcome Back</h2>
                    
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    
                    <button type="submit" name="signin" class="submit-btn">Sign In</button>
                    
                    <div class="forgot-password">
                        <a href="#">Forgot your password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Form Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-btn');
            const forms = document.querySelectorAll('.form');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetForm = this.getAttribute('data-form');
                    
                    // Update active state of buttons
                    toggleButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show target form, hide others
                    forms.forEach(form => {
                        if (form.id === `${targetForm}-form`) {
                            form.classList.add('active');
                        } else {
                            form.classList.remove('active');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>