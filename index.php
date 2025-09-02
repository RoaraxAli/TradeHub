<?php
require_once 'config/config.php';
$page_title = 'Home';
include 'includes/header.php';
$conn = getDBConnection();

$query = "SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1";
$result = $conn->query($query);

$site_name = '';
if ($result && $row = $result->fetch_assoc()) {
    $site_name = htmlspecialchars($row['setting_value']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo $site_name?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet" rel="preload" as="style" onload="this.rel='stylesheet'">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #1a1a1a;
            overflow-x: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        body.loaded {
            opacity: 1;
        }
        .neumorphic {
            background: #f5f5f5;
            border-radius: 16px;
            box-shadow: inset 4px 4px 8px rgba(200, 200, 200, 0.3), inset -4px -4px 8px rgba(255, 255, 255, 0.2);
            transition: all 0.19s ease;
            will-change: transform, box-shadow;
        }
        .neumorphic:hover {
            box-shadow: 4px 4px 12px rgba(200, 200, 200, 0.4), -4px -4px 12px rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.4275s ease-out, transform 0.4275s ease-out;
            will-change: opacity, transform;
        }
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .animate-on-scroll.staggered {
            transition-delay: calc(0.0684s * var(--index));
        }
        header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 1rem 0;
            background: linear-gradient(180deg, rgba(245, 245, 245, 0.95), rgba(245, 245, 245, 0.7));
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 12px rgba(200, 200, 200, 0.3);
        }
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            will-change: transform;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 10px rgba(5, 150, 105, 0.5); }
            50% { transform: scale(1.1); box-shadow: 0 0 15px rgba(5, 150, 105, 0.7); }
            100% { transform: scale(1); box-shadow: 0 0 10px rgba(5, 150, 105, 0.5); }
        }
        .logo:hover .logo-icon {
            background: #10b981;
        }
        .logo-text {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: -0.03em;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
        }
        .nav-links a {
            color: #4b5563;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            position: relative;
            transition: color 0.19s ease, transform 0.19s ease;
        }
        .nav-links a:hover {
            color: #059669;
            transform: translateY(-2px);
        }
        .btn {
            padding: 0.75rem 1.75rem;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.19s ease;
            will-change: transform, box-shadow;
        }
        .btn-primary {
            background: #059669;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #10b981;
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(5, 150, 105, 0.5);
        }
        .btn-secondary {
            background: transparent;
            border: 2px solid #059669;
            color: #059669;
        }
        .btn-secondary:hover {
            background: #10b981;
            color: #ffffff;
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.5);
        }
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            background: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAnkIKGC_BmqdPzVkJ91BTDUwZNzz9YhP9GAfHZsBg15DdtKzhk9zZ1Yn9rbrN30u1FtSE6EMztp2rgPJyjUvDsA7BhEUDE9oHfBCRxXyIoSd2oaH2UOrX5qErgqnPATuzNixLFb09mbHP0y03Bx8Qp0lQlwFejNOrKSrouifD283q_iGtSFHBj-lsoC6oqHmKyXGIQS3W_5fRlzALQn-BCGNa6f3-7QdeRDns9kzkBoucortrbcy81BmEFdjLlePf43bjy_i_cRr9y') no-repeat center center/cover;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3); /
            z-index: -1;
        }
        .hero-content {
            max-width: 800px;
            padding: 2rem;
        }
        .footer-bottom {
        text-align: center;
        padding: 1rem 0;
        }

        .footer-bottom p {
        font-size: 0.85rem;
        color: #1a1a1a;
        margin: 0;
        }
        .hero-content h1 {
    font-size: 5rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    color: #ffffff; /* Solid white color */
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.7); /* Adjusted shadow for better contrast */
}
.hero-content p {
    font-size: 1.5rem;
    color: #ffffff; /* Changed to white */
    max-width: 600px;
    margin: 0 auto 2rem;
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.5); /* Added subtle shadow for readability */
}
        .hero-ctas {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .hero-ctas a {
            transition: transform 0.19s ease, box-shadow 0.19s ease;
        }
        .hero-ctas a:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(5, 150, 105, 0.4);
        }
        .features {
            padding: 6rem 0;
            background: linear-gradient(180deg, #f5f5f5, #e5e5e5);
        }
        .features h2 {
            font-size: 3rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            color: #1a1a1a;
        }
        .features p {
            font-size: 1.25rem;
            color: #4b5563;
            text-align: center;
            max-width: 600px;
            margin: 0 auto 3rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            padding: 2rem;
            text-align: center;
            background: #f5f5f5;
            border-radius: 16px;
            box-shadow: 4px 4px 12px rgba(200, 200, 200, 0.3), -4px -4px 12px rgba(255, 255, 255, 0.2);
            transition: all 0.19s ease;
            will-change: transform, box-shadow;
        }
        .feature-card:hover {
            box-shadow: 6px 6px 16px rgba(200, 200, 200, 0.4), -6px -6px 16px rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            background: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: transform 0.342s ease, background 0.19s ease;
            will-change: transform, background;
        }
        .feature-card:hover .feature-icon {
            transform: rotate(180deg);
            background: #10b981;
        }
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1a1a1a;
        }
        .feature-card p {
            color: #4b5563;
            font-size: 1rem;
        }
        .cta-section {
            padding: 6rem 0;
            background: linear-gradient(90deg, #e5e5e5, #059669);
            text-align: center;
        }
        .cta-section h2 {
            font-size: 3rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
        }
        .cta-section p {
            font-size: 1.25rem;
            color: #4b5563;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .cta-section .btn {
            transition: transform 0.19s ease, box-shadow 0.19s ease;
        }
        .cta-section .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(5, 150, 105, 0.3);
        }
        footer {
            padding: 4rem 0;
            background: #f5f5f5;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
        }
        .footer-grid h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }
        .footer-grid ul {
            list-style: none;
            padding: 0;
        }
        .footer-grid ul li {
            margin-bottom: 0.75rem;
        }
        .footer-grid a {
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.19s ease, transform 0.19s ease;
        }
        .footer-grid a:hover {
            color: #059669;
            transform: scale(1.03);
        }
        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            text-align: center;
            color: #4b5563;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 3rem;
            }
            .nav-links {
                gap: 1rem;
            }
            .features h2, .cta-section h2 {
                font-size: 2.25rem;
            }
            .hero-ctas {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
        .hamburger {
                display: none;
                font-size: 1.5rem;
                background: none;
                border: none;
                cursor: pointer;
                color: #1a1a1a;
        }

            /* Mobile styles */
            @media (max-width: 768px) {
                .hamburger {
                    display: block;
                }
                .nav-links {
                    position: absolute;
                    top: 70px; /* adjust based on header height */
                    right: 0;
                    background: #f5f5f5;
                    flex-direction: column;
                    gap: 1rem;
                    padding: 1.5rem;
                    width: 220px;
                    box-shadow: -4px 4px 12px rgba(0,0,0,0.1);
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                }
                .nav-links.open {
                    transform: translateX(0);
            }
        }
    </style>
</head>
<body style="background: #f5f5f5;">
    <div>
        <!-- Navbar -->
        <header class="animate-on-scroll">
            <div class="nav-container container">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-sync-alt text-white text-lg"></i>
                    </div>
                    <span class="logo-text"><?php echo $site_name?></span>
                </div>

                <!-- Hamburger -->
                <button class="hamburger" id="hamburger">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="nav-links" id="nav-links">
                    <?php if (isLoggedIn()): ?>
                        <a href="./Pages/dashboard.php">Dashboard</a>
                        <a href="auth/logout.php" class="btn btn-primary">Logout</a>
                    <?php else: ?>
                        <a href="auth/auth.php?mode=login">Log In</a>
                        <a href="auth/auth.php?mode=signup" class="btn btn-primary">Join Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container hero-content animate-on-scroll">
                <h1 class="animate-on-scroll">Unlock Value Through Trade</h1>
                <p class="animate-on-scroll">Join a global community to barter products, services, and skills without cash.</p>
                <div class="hero-ctas">
                    <a href="auth/auth.php?mode=signup" class="btn btn-primary animate-on-scroll">Start Trading Now</a>
                    <a href="#" class="btn btn-secondary animate-on-scroll">Learn More</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features animate-on-scroll">
            <div class="container">
                <h2 class="animate-on-scroll">Why <?php echo $site_name?> Stands Out</h2>
                <p class="animate-on-scroll">Join thousands redefining value exchange with a seamless, cashless platform.</p>
                <div class="features-grid">
                    <div class="feature-card neumorphic animate-on-scroll staggered" style="--index: 1;">
                        <div class="feature-icon">
                            <i class="fas fa-sync-alt text-white text-xl"></i>
                        </div>
                        <h3>Seamless Barter</h3>
                        <p>Trade goods, services, or skills effortlessly.</p>
                    </div>
                    <div class="feature-card neumorphic animate-on-scroll staggered" style="--index: 2;">
                        <div class="feature-icon">
                            <i class="fas fa-bolt text-white text-xl"></i>
                        </div>
                        <h3>Cashless Freedom</h3>
                        <p>Exchange value without needing money.</p>
                    </div>
                    <div class="feature-card neumorphic animate-on-scroll staggered" style="--index: 3;">
                        <div class="feature-icon">
                            <i class="fas fa-star text-white text-xl"></i>
                        </div>
                        <h3>Smart Credits</h3>
                        <p>Earn credits to unlock flexible trading.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section animate-on-scroll">
            <div class="container">
                <h2 class="animate-on-scroll">Join the Barter Revolution</h2>
                <p class="animate-on-scroll">Connect with a global community and unlock the power of value-based exchange.</p>
                <a href="auth/auth.php?mode=signup" class="btn btn-primary animate-on-scroll">
                    Get Started Now
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="animate-on-scroll">
            <div class="container footer-grid">
                <div class="animate-on-scroll staggered" style="--index: 1;">
                    <div class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-sync-alt text-white text-lg"></i>
                        </div>
                        <span class="logo-text"><?php echo $site_name?></span>
                    </div>
                    <p class="text-gray-600 mt-2">Empowering communities through value exchange.</p>
                </div>
                <div class="animate-on-scroll staggered" style="--index: 2;">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="animate-on-scroll staggered" style="--index: 3;">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Safety</a></li>
                    </ul>
                </div>
                <div class="animate-on-scroll staggered" style="--index: 4;">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy</a></li>
                        <li><a href="#">Terms</a></li>
                        <li><a href="#">Cookies</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom animate-on-scroll">
                <p>&copy; <?php echo date("Y"); ?> <?php echo $site_name?>. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script>
        // Hamburger toggle
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('open');
            hamburger.querySelector('i').classList.toggle('fa-bars');
            hamburger.querySelector('i').classList.toggle('fa-times');
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('loaded');
        });

        let lastScrollY = window.scrollY;
        const heroContent = document.querySelector('.hero-content');
        function handleParallax() {
            const scrollPosition = window.scrollY;
            if (Math.abs(scrollPosition - lastScrollY) > 5) {
                heroContent.style.transform = `translateY(${scrollPosition * 0.2}px)`;
                lastScrollY = scrollPosition;
            }
            requestAnimationFrame(handleParallax);
        }
        requestAnimationFrame(handleParallax);

        const animateElements = document.querySelectorAll('.animate-on-scroll');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                } else {
                    entry.target.classList.remove('visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        });

        animateElements.forEach((element) => {
            observer.observe(element);
        });
    </script>

<?php include 'includes/footer.php'; ?>
</body>
</html>