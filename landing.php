<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: consumer_dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartWaste Ruiru - Intelligent Waste Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #0c8a2cff;
            --secondary-color: #0c8a2cff;
            --bg-color: rgba(255, 255, 255, 1);
            --text-color: #333333;
            --card-bg: #f8f9fa;
            --nav-bg: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] {
            --primary-color: #00ff55ff;
            --secondary-color: #00ff55ff;
            --bg-color: #1a1a2e;
            --text-color: #eaeaea;
            --card-bg: #16213e;
            --nav-bg: rgba(26, 26, 46, 0.95);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: var(--nav-bg);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 2rem;
        }

        .hero-image {
            position: relative;
            animation: float 3s ease-in-out infinite;
        }

        .hero-image img {
            max-width: 100%;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));
            border-radius: 20px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Portal Cards */
        .portal-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .portal-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
            border: 2px solid var(--primary-color);
        }

        .portal-icon {
            font-size: 4rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        /* Feature Cards */
        .feature-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
        }

        /* Buttons */
        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Section Styling */
        .section {
            padding: 5rem 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer */
        .footer {
            background: var(--card-bg);
            padding: 3rem 0;
            margin-top: 5rem;
        }

        .footer a {
            color: var(--text-color);
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary-color);
        }

        .text-muted {
            color: var(--text-color) !important;
            opacity: 0.7;
        }

        .card {
            background: var(--card-bg);
            color: var(--text-color);
        }

        .form-control, .form-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: rgba(128, 128, 128, 0.3);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
        }

        .form-label {
            color: var(--text-color);
        }

        /* Animations */
        .fade-in-up {
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-recycle"></i> SmartWaste Ruiru
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#login">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content animate__animated animate__fadeInLeft">
                    <h1 class="hero-title">Smart Waste Management for Ruiru</h1>
                    <p class="hero-subtitle">
                        Track waste collection in real-time. Monitor bins. Optimize routes. 
                        Building a cleaner, smarter Ruiru together.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#login" class="btn btn-gradient">
                            <i class="bi bi-box-arrow-in-right"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-play-circle"></i> Learn More
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="bi bi-geo-alt-fill"></i> Serving Ruiru, Kiambu County, Kenya
                        </small>
                    </div>
                </div>
                <div class="col-lg-6 hero-image animate__animated animate__fadeInRight">
                    <img src="https://i.ytimg.com/vi/ozAjol4P1Us/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLB00qHGhTQg6vumrmKUsjQ75Yl94A" 
                         alt="Waste Collection Truck" 
                         class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="section-title animate__animated animate__fadeInUp">Why Choose SmartWaste?</h2>
            <div class="row">
                <div class="col-md-4 fade-in-up">
                    <div class="feature-card">
                        <i class="bi bi-geo-alt-fill feature-icon"></i>
                        <h4>Real-Time Tracking</h4>
                        <p>Monitor waste collection trucks and bin status in real-time with GPS precision.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="feature-card">
                        <i class="bi bi-trash-fill feature-icon"></i>
                        <h4>Smart Bin Monitoring</h4>
                        <p>Automated sensors track fill levels, ensuring timely collection and reducing overflow.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="feature-card">
                        <i class="bi bi-graph-up-arrow feature-icon"></i>
                        <h4>Data Analytics</h4>
                        <p>Comprehensive insights and reports to optimize waste management operations.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.6s;">
                    <div class="feature-card">
                        <i class="bi bi-phone-fill feature-icon"></i>
                        <h4>Mobile Friendly</h4>
                        <p>Access the system anywhere, anytime from your smartphone or tablet.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.8s;">
                    <div class="feature-card">
                        <i class="bi bi-shield-check feature-icon"></i>
                        <h4>Secure & Reliable</h4>
                        <p>Bank-level encryption ensures your data is always protected and secure.</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 1s;">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon"></i>
                        <h4>Community Focused</h4>
                        <p>Empowering Ruiru residents to participate in creating a cleaner environment.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Portals Section -->
    <section id="login" class="section" style="background: var(--card-bg);">
        <div class="container">
            <h2 class="section-title">Choose Your Portal</h2>
            <div class="row justify-content-center">
                <div class="col-md-5 mb-4">
                    <div class="portal-card" onclick="window.location.href='admin_login.php'">
                        <i class="bi bi-shield-lock portal-icon"></i>
                        <h3>Administrator Portal</h3>
                        <p class="text-muted">Manage trucks, assign routes, monitor operations</p>
                        <button class="btn btn-gradient mt-3">
                            <i class="bi bi-arrow-right-circle"></i> Admin Login
                        </button>
                    </div>
                </div>
                <div class="col-md-5 mb-4">
                    <div class="portal-card" onclick="window.location.href='consumer_login.php'">
                        <i class="bi bi-person-circle portal-icon"></i>
                        <h3>Consumer Portal</h3>
                        <p class="text-muted">Track collection, view schedules, report issues</p>
                        <button class="btn btn-gradient mt-3">
                            <i class="bi bi-arrow-right-circle"></i> Consumer Login
                        </button>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-muted">
                    Don't have an account? <a href="signup.php" class="text-decoration-none fw-bold">Sign Up Now</a>
                </p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4">
                    <h2 class="section-title text-start">About SmartWaste Ruiru</h2>
                    <p class="lead">
                        SmartWaste Ruiru is an innovative waste management solution designed 
                        specifically for Ruiru town and its environs in Kiambu County, Kenya.
                    </p>
                    <p>
                        Our mission is to revolutionize waste collection through technology, 
                        making Ruiru cleaner, greener, and more sustainable. We leverage 
                        IoT sensors, GPS tracking, and data analytics to optimize waste 
                        collection routes and ensure timely service delivery.
                    </p>
                    <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span>Real-time monitoring of 50+ bins across Ruiru</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span>Fleet of modern waste collection trucks</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                            <span>Serving 10,000+ residents daily</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=600&h=400&fit=crop" 
                         alt="Clean City" 
                         class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section" style="background: var(--card-bg);">
        <div class="container">
            <h2 class="section-title">Get In Touch</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <form id="contactForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" placeholder="+254...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-gradient w-100">
                                    <i class="bi bi-send"></i> Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="row mt-5">
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-geo-alt-fill fs-2 text-primary"></i>
                            <h6 class="mt-2">Location</h6>
                            <p class="text-muted small">Ruiru Town, Kiambu County</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-telephone-fill fs-2 text-primary"></i>
                            <h6 class="mt-2">Phone</h6>
                            <p class="text-muted small">+254793531128</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="bi bi-envelope-fill fs-2 text-primary"></i>
                            <h6 class="mt-2">Email</h6>
                            <p class="text-muted small">info@smartwaste-ruiru.co.ke</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-recycle"></i> SmartWaste Ruiru
                    </h5>
                    <p class="text-muted">
                        Transforming waste management in Ruiru through innovative technology 
                        and community engagement.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-decoration-none"><i class="bi bi-facebook fs-4"></i></a>
                        <a href="#" class="text-decoration-none"><i class="bi bi-twitter fs-4"></i></a>
                        <a href="#" class="text-decoration-none"><i class="bi bi-instagram fs-4"></i></a>
                        <a href="#" class="text-decoration-none"><i class="bi bi-linkedin fs-4"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="text-decoration-none text-muted">Home</a></li>
                        <li><a href="#features" class="text-decoration-none text-muted">Features</a></li>
                        <li><a href="#about" class="text-decoration-none text-muted">About</a></li>
                        <li><a href="#contact" class="text-decoration-none text-muted">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Services</h6>
                    <ul class="list-unstyled">
                        <li class="text-muted">Waste Collection</li>
                        <li class="text-muted">Route Optimization</li>
                        <li class="text-muted">Bin Monitoring</li>
                        <li class="text-muted">Analytics & Reports</li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Working Hours</h6>
                    <p class="text-muted small">Monday - Friday: 6:00 AM - 6:00 PM</p>
                    <p class="text-muted small">Saturday: 6:00 AM - 2:00 PM</p>
                    <p class="text-muted small">Sunday: Closed</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted small mb-0">
                        © 2025 SmartWaste Ruiru. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted small mb-0">
                        Made with <i class="bi bi-heart-fill text-danger"></i> for Ruiru
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            const icon = document.getElementById('themeIcon');
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (newTheme === 'dark') {
                icon.className = 'bi bi-sun-fill';
            } else {
                icon.className = 'bi bi-moon-stars-fill';
            }
        }

        // Load saved theme
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'dark') {
                document.getElementById('themeIcon').className = 'bi bi-sun-fill';
            }
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Contact form
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });

        // Fade in animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .portal-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>