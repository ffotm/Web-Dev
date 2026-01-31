
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Master Edu - Where Knowledge Meets Mastery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #14002E;
            --bg-secondary: #220547;
            --bg-tertiary: #2b0f50;
            --bg-card: #2A2050;
            --bg-card1: #473e70;
            --bg-card-hover: #443a66;
            --text-primary: #E0D9FF;
            --text-secondary: #BFB6D9;
            --btn-bg: #9DFF57;
            --btn-text: #14002E;
            --btn-hover: #8BED4A;
            --separator-color: rgba(224, 217, 255, 0.5);
        }
        
        .light-mode {
            --bg-primary: #f8f9fa;
            --bg-secondary: #BFB6D9;
            --bg-tertiary: #b4a8d8ff;
            --bg-card1: #9580bb;
            --bg-card: #a093d1;
            --bg-card-hover: #e9ecef;
            --text-primary: #240447;
            --text-secondary: #1e063d;
            --btn-bg: #9DFF57;
            --btn-text: #1f093d;
            --btn-hover: #2d0561;
            --separator-color: rgba(224, 217, 255, 0.5);
        }
        
        body {
            font-family: 'Lusitana', serif;
            background: linear-gradient(to bottom, var(--bg-primary) 0%, var(--bg-secondary) 40%, var(--bg-tertiary) 100%);
            color: var(--text-primary);
            line-height: 1.7;
            transition: all 0.5s ease;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 50px;
            height: 50px;
            border: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
           background: transparent;
            font-size: 20px;
            z-index: 1001;
            transition: all 0.3s;
           
        }
        
      
        /* Section Separator */
        .section-separator {
            width: 95%;
            height: 1px;
            background: var(--separator-color);
            margin: 40px auto;
        }
        
        /* Header */
        header {
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.5s ease;
        }
        
        .light-mode header {
            background-color: var(--bg-tertiary);
            border-bottom: 1px solid rgba(224, 217, 255, 0.34);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .logo svg {
            width: 28px;
            height: 28px;
        }
        
        nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .btn-login {
            background: none;
            border: 1px solid var(--text-secondary);
            color: var(--text-primary);
            cursor: pointer;
            font-size: 14px;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s;
            font-family: 'Lusitana', serif;
            font-weight: 600;
        }
        
        .btn-login:hover {
            background: var(--bg-card);
            border-color: var(--text-primary);
        }
        
        .btn-primary {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 26px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-family: 'Lusitana', serif;
        }
        
        .btn-primary:hover {
            background: var(--btn-hover);
            transform: scale(1.05);
        }
        
        /* Hero Section */
        .hero {
            padding: 50px 0 20px;
        }
        
        .hero-content {
            background: var(--bg-secondary);
            border-radius: 29px;
            padding: 50px;
            position: relative;
            overflow: hidden;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .hero-content::before {
            content: "";
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0.089) 0,
                rgba(0, 0, 0, 0.288) 1px
            ),
            repeating-linear-gradient(
                to right,
                rgba(255, 255, 255, 0.082) 0,
                rgba(0, 0, 0, 0.438) 1px
            );
            mix-blend-mode: overlay;
            pointer-events: none;
        }
        
        .hero-content > * {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 50px;
            font-weight: 450;
            margin-bottom: 20px;
            max-width: 800px;
        }
        
        .hero p {
            color: var(--text-secondary);
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 700px;
        }
        
        .btn-explore {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 14px 36px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--btn-hover);
            font-family: 'Lusitana', serif;
            font-size: 16px;
            width: fit-content;
        }
        
        .btn-explore:hover {
            background: var(--btn-hover);
            box-shadow: 0 10px 25px rgba(157, 255, 87, 0.3);
            transform: scale(1.05);
        }
        
        /* About Section */
        .about {
            padding: 70px 0;
        }
        
        .about-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .about-text h2 {
            font-size: 34px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .about-text h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--text-primary);
        }
        
        .about-text p {
            color: var(--text-secondary);
            margin-bottom: 14px;
            max-width: 700px;
            font-size: 16px;
        }
        
        .btn-about {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Lusitana', serif;
            font-size: 16px;
        }
        
        .btn-about:hover {
            background: var(--btn-hover);
            transform: scale(1.05);
        }
        
        /* Courses */
        .courses {
            padding: 80px 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .section-header h2 {
            font-size: 34px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--text-primary);
        }
        
        .courses-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .course-card {
            background: var(--bg-card1);
            border-radius: 18px;
            padding: 36px;
            transition: all 0.4s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .light-mode .course-card {
            border: 1px solid rgba(224, 217, 255, 0.2);
        }
        
        .course-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .course-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .course-card p {
            color: var(--text-secondary);
            margin-bottom: 22px;
            line-height: 1.6;
        }
        
        .btn-learn {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 24px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Lusitana', serif;
        }
        
        .btn-learn:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }
        
        .read-more {
            text-align: right;
            margin-top: 30px;
        }
        
        .read-more a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s;
        }
        
        .read-more a:hover {
            color: var(--text-primary);
        }
        
        /* Team Section */
        .team-section {
            padding: 50px 0;
        }
        
        .team-section h3 {
            font-size: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .team-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--text-primary);
        }
        
        .team-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .team-member {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 30px;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .team-member:hover {
            background: var(--bg-card-hover);
            transform: translateX(5px);
        }
        
        .team-avatar {
            width: 60px;
            height: 60px;
            background: rgba(157, 255, 87, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            color: var(--btn-text);
        }
        
        /* Events */
        .events {
            padding: 80px 0 120px;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .event-card {
            background: var(--bg-card1);
            border-radius: 16px;
            overflow: hidden;
            transition: 0.3s;
        }
        
        .event-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .event-image {
            height: 180px;
            background: linear-gradient(to bottom, #edd3ff, #ffffff);
        }
        
        .light-mode .event-image {
            background: linear-gradient(to bottom, #2F3E56, #14002E);
        }
        
        .event-content {
            padding: 24px;
        }
        
        .event-content h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .event-content p {
            color: var(--text-secondary);
            font-size: 15px;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .event-tag {
            display: inline-block;
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Footer */
        footer {
            background: var(--bg-card1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 70px 0 30px;
            transition: all 0.5s ease;
        }
        
        .light-mode footer {
            background: rgba(20, 0, 46, 0.9);
            border-top: 1px solid rgba(224, 217, 255, 0.2);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--text-primary);
        }
        
        .social-links {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .social-links a:hover {
            background: var(--btn-bg);
            color: var(--btn-text);
            transform: scale(1.1);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero-content {
                padding: 30px;
            }
            
            .about-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: start;
                gap: 16px;
            }
            
            .courses-container {
                grid-template-columns: 1fr;
            }
            
            nav {
                gap: 10px;
            }
            
            .btn-login,
            .btn-primary {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </div>
                <nav>
                    <button class="btn-login" onclick="window.location.href='auth.php'">LOGIN</button>
                    <button class="btn-primary" onclick="window.location.href='auth.php'">GET STARTED</button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Master Edu — Where Knowledge Meets Mastery</h1>
                <p>Personalized paths, interactive tools, and expert guidance to help you master any subject.</p>
                <button class="btn-explore" onclick="window.location.href='auth.php'">Explore Courses</button>
            </div>
        </div>
    </section>

    <!-- Section Separator -->
    <div class="section-separator"></div>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about-header">
                <div class="about-text">
                    <h2>Master Education</h2>
                    <p>Est une plateforme d'apprentissage en ligne moderne et interactive dédiée à la formation et au développement des compétences.</p>
                    <p>Elle offre une large sélection de cours et de formations dans divers domaines, adaptés aux besoins des étudiants, des professionnels et des passionnés de savoir.</p>
                </div>
                <button class="btn-about" onclick="window.location.href='about.php'">About Us</button>
            </div>
        </div>
    </section>

    <!-- Section Separator -->
    <div class="section-separator"></div>

    <!-- Courses Section -->
    <section class="courses">
        <div class="container">
            <div class="section-header">
                <h2>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg> Courses
                </h2>
            </div>

            <div class="courses-container">
                <div class="course-card">
                    <h3>Object-Oriented Programming</h3>
                    <p>Master the principles of OOP including encapsulation, inheritance, and polymorphism. Learn to design robust software architectures.</p>
                    <button class="btn-learn" onclick="window.location.href='auth.php'">Learn More</button>
                </div>

                <div class="course-card">
                    <h3>Data Structures & Algorithms</h3>
                    <p>Explore fundamental data structures and algorithms. Improve your problem-solving skills and prepare for technical interviews.</p>
                    <button class="btn-learn" onclick="window.location.href='auth.php'">Learn More</button>
                </div>

                <div class="course-card">
                    <h3>Web Development</h3>
                    <p>Build modern, responsive websites using HTML, CSS, and JavaScript. Learn front-end frameworks and back-end development.</p>
                    <button class="btn-learn" onclick="window.location.href='auth.php'">Learn More</button>
                </div>

                <div class="course-card">
                    <h3>Machine Learning</h3>
                    <p>Dive into the world of AI and machine learning. Understand algorithms, neural networks, and real-world applications.</p>
                    <button class="btn-learn" onclick="window.location.href='auth.php'">Learn More</button>
                </div>
            </div>
            
            <div class="read-more">
                <a href="auth.php">View all courses →</a>
            </div>
        </div>
    </section>

    <!-- Section Separator -->
    <div class="section-separator"></div>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg> Our Team
            </h3>
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4>Expert Instructors</h4>
                        <p>Industry professionals with years of experience</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h4>Dedicated Support</h4>
                        <p>24/7 support team ready to help you succeed</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <h4>Career Advisors</h4>
                        <p>Guidance to help you achieve your career goals</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Separator -->
    <div class="section-separator"></div>

    <!-- Events Section -->
    <section class="events">
        <div class="container">
            <div class="section-header">
                <h2>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg> Events
                </h2>
            </div>
            <div class="events-grid">
                <div class="event-card">
                    <div class="event-image"></div>
                    <div class="event-content">
                        <h3>Annual Hackathon</h3>
                        <p>Join our 48-hour coding marathon. Build innovative projects, collaborate with peers, and win exciting prizes.</p>
                        <span class="event-tag">March 15-16</span>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-image"></div>
                    <div class="event-content">
                        <h3>Tech Career Fair</h3>
                        <p>Connect with top tech companies. Network with recruiters and explore internship and job opportunities.</p>
                        <span class="event-tag">April 5</span>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-image"></div>
                    <div class="event-content">
                        <h3>AI Workshop Series</h3>
                        <p>Hands-on workshops on machine learning and AI applications. Suitable for beginners and advanced learners.</p>
                        <span class="event-tag">May 10-12</span>
                    </div>
                </div>
            </div>
            <div class="read-more">
                <a href="auth.php">View all events →</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Master Edu</h3>
                    <p>Innovative online interactive tools and expert guidance to help you master any subject.</p>
                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="auth.php">Courses</a></li>
                        <li><a href="auth.php">Events</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul class="footer-links">
                        <li>Email: info@masteredu.com</li>
                        <li>Phone: +213 123 456 789</li>
                        <li>Address: Algiers, Algeria</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Master Edu. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        const body = document.body;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            body.classList.add('light-mode');
            themeIcon.className = 'fas fa-moon';
        }

        themeToggle.addEventListener('click', function() {
            body.classList.toggle('light-mode');
            
            if (body.classList.contains('light-mode')) {
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.course-card, .event-card, .team-member').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>
</html>