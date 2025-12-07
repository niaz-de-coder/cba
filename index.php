<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consistent Business Assistant - Virtual Office</title>
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
        }

        body {
            color: #333;
            line-height: 1.6;
            background-color: #f9f9f9;
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
        }

        .logo h1 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 25px;
        }

        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: var(--secondary);
        }

        .mobile-menu {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .hero h2 {
            font-size: 2.8rem;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--accent);
            color: white;
            padding: 15px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .cta-button:hover {
            background-color: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /* Section Styles */
        section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 70px;
            height: 3px;
            background-color: var(--secondary);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .section-title p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Services Section */
        .services-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .service-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .service-icon {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }

        .service-box h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        /* Benefits Section */
        .benefits-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .benefit-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            display: flex;
            align-items: flex-start;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .benefit-icon {
            font-size: 2rem;
            color: var(--success);
            margin-right: 20px;
            flex-shrink: 0;
        }

        .benefit-content h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--primary);
        }

        /* Offers Section */
        .offers-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .offer-box {
            background-color: white;
            border-radius: 10px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            flex: 1;
            min-width: 300px;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .offer-box.featured {
            border: 2px solid var(--secondary);
            transform: scale(1.05);
        }

        .offer-badge {
            position: absolute;
            top: 20px;
            right: -30px;
            background-color: var(--accent);
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-weight: bold;
            font-size: 0.9rem;
        }

        .offer-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 20px 0;
        }

        .offer-price span {
            font-size: 1rem;
            color: var(--gray);
        }

        .offer-features {
            list-style: none;
            margin: 25px 0;
        }

        .offer-features li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .offer-features li i {
            color: var(--success);
            margin-right: 10px;
        }

        /* Create Office Section */
        .create-office {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)), url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 0;
        }

        .create-office h2 {
            font-size: 2.8rem;
            margin-bottom: 20px;
        }

        .create-office p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        .create-office-button {
            display: inline-block;
            background-color: var(--accent);
            color: white;
            padding: 18px 45px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .create-office-button:hover {
            background-color: #c0392b;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        /* Footer */
        footer {
            background-color: var(--primary);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
            bottom: 0;
            left: 0;
        }

        .footer-column p {
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .footer-links a:hover {
            opacity: 1;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .social-links a:hover {
            background-color: var(--secondary);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            nav ul {
                margin-top: 15px;
            }

            nav ul li {
                margin: 0 10px;
            }

            .hero h2 {
                font-size: 2.2rem;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }

            .create-office h2 {
                font-size: 2.2rem;
            }

            .mobile-menu {
                display: block;
                position: absolute;
                top: 20px;
                right: 20px;
            }

            nav {
                display: none;
                width: 100%;
                margin-top: 15px;
            }

            nav.active {
                display: block;
            }

            nav ul {
                flex-direction: column;
            }

            nav ul li {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <img src="assets/logo.png" alt="Consistent Business Assistant Logo">
                <h1>Consistent Business Assistant</h1>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#benefits">Benefits</a></li>
                    <li><a href="#offers">Offers</a></li>
                    <li><a href="#create-office">Create Office</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <h2>Consistent Business Assistant</h2>
            <p>Keep Going Forward with our comprehensive virtual office solutions</p>
            <a href="#create-office" class="cta-button">Get Started Today</a>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services">
        <div class="container">
            <div class="section-title">
                <h2>Our Services</h2>
                <p>Virtual office for management, marketing and finance department</p>
            </div>
            <div class="services-container">
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Management Solutions</h3>
                    <p>Streamline your business operations with our comprehensive management tools and reports.</p>
                </div>
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Marketing Department</h3>
                    <p>Develop effective marketing strategies and reports to grow your business presence.</p>
                </div>
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Finance Department</h3>
                    <p>Manage your finances, prepare accounting cycles, and perform financial analysis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" style="background-color: #f5f7fa;">
        <div class="container">
            <div class="section-title">
                <h2>Key Benefits</h2>
                <p>Discover how our virtual office can transform your business operations</p>
            </div>
            <div class="benefits-container">
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>No Need of Physical Office</h3>
                        <p>Operate your business from anywhere without the overhead of a physical office space.</p>
                    </div>
                </div>
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Store Financial Documents</h3>
                        <p>Securely store and manage all your financial documents in one centralized location.</p>
                    </div>
                </div>
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Prepare Accounting Cycle</h3>
                        <p>Easily input journal entries and prepare complete accounting cycles with our tools.</p>
                    </div>
                </div>
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Financial Analysis</h3>
                        <p>Gain valuable insights with comprehensive financial analysis and reporting.</p>
                    </div>
                </div>
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Marketing Reports & Strategies</h3>
                        <p>Develop effective marketing strategies with detailed reports and analytics.</p>
                    </div>
                </div>
                <div class="benefit-box">
                    <div class="benefit-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Management Reports & Strategies</h3>
                        <p>Make informed decisions with comprehensive management reports and development strategies.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Offers Section -->
    <section id="offers">
        <div class="container">
            <div class="section-title">
                <h2>Special Offers</h2>
                <p>Choose the plan that works best for your business needs</p>
            </div>
            <div class="offers-container">
                <div class="offer-box featured">
                    <div class="offer-badge">Popular</div>
                    <h3>Free Trial</h3>
                    <div class="offer-price">$19 <span>/month ($0 for first month)</span></div>
                    <ul class="offer-features">
                        <li><i class="fas fa-check"></i> Full access to all features</li>
                        <li><i class="fas fa-check"></i> No credit card required for free month</li>
                        <li><i class="fas fa-check"></i> Cancel anytime</li>
                    </ul>
                    <a href="#create-office" class="cta-button">Start Free Trial</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Create Office Section -->
    <section class="create-office" id="create-office">
        <div class="container">
            <h2>Create Your Virtual Office Today</h2>
            <p>Join thousands of businesses that have transformed their operations with our virtual office solution. Start with a free one-month trial, no credit card required.</p>
            <a href="user.php" class="create-office-button">Create Your Office Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-column">
                    <h3>Consistent Business Assistant</h3>
                    <p>Your trusted partner for virtual office solutions. Keep Going Forward with our comprehensive business tools.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#benefits">Benefits</a></li>
                        <li><a href="#offers">Offers</a></li>
                        <li><a href="#create-office">Create Office</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope"></i> info@consistentbusiness.com</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Business Ave, Suite 100</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2023 Consistent Business Assistant. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu').addEventListener('click', function() {
            document.querySelector('nav').classList.toggle('active');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    document.querySelector('nav').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>