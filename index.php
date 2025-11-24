<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyLearn - Learning Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #0d6efd;
            --accent-color: #20c997;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .btn-get-started {
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            background: white;
            color: var(--primary-color);
            border: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .btn-get-started:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.3);
            background: var(--accent-color);
            color: white;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 4rem 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 1.2rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--secondary-color) 100%);
            padding: 4rem 0;
            color: white;
            text-align: center;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }
        
        .shape1 {
            top: 10%;
            left: 10%;
            font-size: 4rem;
            animation-delay: 0s;
        }
        
        .shape2 {
            top: 60%;
            right: 15%;
            font-size: 5rem;
            animation-delay: 5s;
        }
        
        .shape3 {
            bottom: 20%;
            left: 20%;
            font-size: 3rem;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-shapes">
            <i class="bi bi-book shape shape1"></i>
            <i class="bi bi-mortarboard shape shape2"></i>
            <i class="bi bi-people shape shape3"></i>
        </div>
        
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Welcome to MyLearn LMS</h1>
                    <p class="hero-subtitle">Empower education with our comprehensive Learning Management System</p>
                    <p class="mb-4">Join thousands of students, teachers, and parents in creating an exceptional learning experience.</p>
                    <a href="login.php" class="btn btn-get-started">
                        <i class="bi bi-rocket-takeoff me-2"></i>Get Started
                    </a>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center">
                    <i class="bi bi-laptop" style="font-size: 20rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container my-5 py-5">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold" style="color: var(--primary-color);">Why Choose MyLearn?</h2>
            <p class="lead text-muted">Everything you need for a complete learning experience</p>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-person-video3 feature-icon"></i>
                    <h3 class="feature-title">Live Classes</h3>
                    <p class="feature-description">Interactive live sessions with experienced teachers. Engage in real-time learning and discussions.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-file-earmark-play feature-icon"></i>
                    <h3 class="feature-title">Rich Content</h3>
                    <p class="feature-description">Access videos, audio lessons, documents, and interactive materials anytime, anywhere.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-graph-up-arrow feature-icon"></i>
                    <h3 class="feature-title">Track Progress</h3>
                    <p class="feature-description">Monitor learning progress with comprehensive analytics and detailed reporting.</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-shield-check feature-icon"></i>
                    <h3 class="feature-title">Secure & Safe</h3>
                    <p class="feature-description">Your data is protected with enterprise-grade security and privacy measures.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-phone feature-icon"></i>
                    <h3 class="feature-title">Mobile Friendly</h3>
                    <p class="feature-description">Learn on the go with our responsive design that works on all devices.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <i class="bi bi-credit-card feature-icon"></i>
                    <h3 class="feature-title">Flexible Payment</h3>
                    <p class="feature-description">Choose subscription plans that fit your needs with secure payment options.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Expert Teachers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Courses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-5 fw-bold mb-3">Ready to Start Learning?</h2>
            <p class="lead mb-4">Join MyLearn today and unlock your potential</p>
            <a href="login.php" class="btn btn-light btn-lg me-3" style="border-radius: 50px; padding: 12px 40px;">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
            <a href="register.php" class="btn btn-outline-light btn-lg" style="border-radius: 50px; padding: 12px 40px;">
                <i class="bi bi-person-plus me-2"></i>Register as Parent
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2024 MyLearn LMS. All rights reserved.</p>
            <p class="mb-0 mt-2">
                <small>Empowering education through technology</small>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
