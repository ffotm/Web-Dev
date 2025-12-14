<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

// Create database object
$database = new Database();
$db = $database->getConnection();

// Get user information
$first_name = $_SESSION['first_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'student';
$last_name = $_SESSION['last_name'] ?? 'User';


// Fetch courses
try {
    $coursesQuery = "SELECT * FROM courses LIMIT 4";
    $coursesStmt = $db->prepare($coursesQuery);
    $coursesStmt->execute();
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $courses = [];
}

// Fetch events
try {
    $eventsQuery = "SELECT * FROM events ORDER BY event_date DESC LIMIT 3";
    $eventsStmt = $db->prepare($eventsQuery);
    $eventsStmt->execute();
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">    
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Dashboard - Master Edu</title>
    <style>
        /* RESET */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .light-mode {
            --bg-primary: rgb(255, 255, 255);
            --bg-secondary: #a093d1;
            --bg-tertiary: #ffffff;
            --bg-card1: #9580bb;
            --bg-card: #a093d1;
            --bg-card-hover: #1706FA0;
            --text-primary: #240447;
            --text-secondary: #1e063d;
            --btn-bg: #9DFF57;
            --btn-text: #1f093d;
            --btn-hover: #2d0561;
            --separator-color: rgb(255, 255, 255);
        }
        
         :root {
            /* Dark mode colors */
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
        /* GENERAL STYLE */
        
        body {
            font-family: "Lusitana", serif;
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
        /* Theme Toggle Button */
        
        .theme-toggle {
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: transparent;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                border: none;
                font-size: 20px;
                z-index: 100;
                transition: all 0.3s;
            }
            
            .theme-toggle:hover {
                transform: scale(1.1);
            }
        /* Section separator with line */
        
        .section-separator {
            width: 95%;
            height: 1px;
            background: var(--separator-color);
            margin-left: 30px;
            margin-top: 40px;
        }
        /* HEADER */
        
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
            padding: 18px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            color: var(--text-primary);
        }
        
        .logo svg {
            width: 28px;
            height: 28px;
        }
        
        nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        /* User Menu Styles */
        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-family: "Lusitana", serif;
            font-size: 14px;
        }
        
        .user-button:hover {
            background: var(--bg-card-hover);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px;
            min-width: 200px;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        
        .dropdown-menu.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 12px 15px;
            color: var(--text-primary);
            text-decoration: none;
            display: block;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background: var(--bg-card-hover);
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 10px 0;
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
        }
        
        .btn-primary:hover {
            background: var(--btn-hover);
            transform: scale(1.05);
        }
        /* HERO SECTION */
        
        .hero {
            padding: 50px 0 20px;
            text-align: left;
        }
        
        .hh {
            border-radius: 29px;
            width: 100%;
            max-width: 1300px;
            height: 330px;
            background: var(--bg-secondary);
            position: relative;
            overflow: hidden;
        }
        
        .hh::before {
            content: "";
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient( to bottom, rgba(255, 255, 255, 0.089) 0, rgba(0, 0, 0, 0.288) 1px), repeating-linear-gradient( to right, rgba(255, 255, 255, 0.082) 0, rgba(0, 0, 0, 0.438) 1px);
            mix-blend-mode: overlay;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 45px;
            font-weight: 450;
            margin: 30px;
            padding-top: 35px;
        }
        
        .hero p {
            color: var(--text-secondary);
            font-size: 18px;
            margin: 0 30px 25px 30px;
            max-width: 700px;
        }
        
        .btn-explore {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 36px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border: #8BED4A 2px solid;
            margin-left: 30px;
        }
        
        .btn-explore:hover {
            background: var(--btn-hover);
            box-shadow: 0 10px 25px rgba(157, 255, 87, 0.3);
            transform: scale(1.05);
        }

        /* Dashboard Stats */
        .stats-section {
            padding: 40px 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 28px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            background: var(--bg-card-hover);
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: var(--btn-bg);
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 15px;
        }

        /* Quick Actions */
        .quick-actions {
            padding: 30px 0;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: var(--bg-card1);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            cursor: pointer;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            background: var(--bg-card-hover);
        }

        .action-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .action-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .action-card p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* ABOUT SECTION */
        
        .about {
            padding: 50px 0;
        }
        
        .about-content {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.5s ease;
        }
        
        .light-mode .about-content {
            background: rgba(191, 182, 217, 0.2);
            border: 1px solid rgba(224, 217, 255, 0.2);
        }
        
        .about-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .about-text h2 {
            font-size: 28px;
            margin-bottom: 16px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .about-text h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--text-primary);
        }
        
        .about-text p {
            color: var(--text-secondary);
            margin-bottom: 14px;
            max-width: 700px;
        }
        
        .btn-about {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 28px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-about:hover {
            background: var(--btn-hover);
            transform: scale(1.05);
        }
        /* TEAM */
        
        .team-section h3 {
            font-size: 22px;
            margin-bottom: 24px;
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
            height: 2px;
            background: var(--text-primary);
        }
        
        .team-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .team-member {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px 32px;
            transition: all 0.3s;
            cursor: pointer;
            gap: 20px;
            margin: 10px;
            display: grid;
            grid-template-columns: 1fr auto;
        }
        
        .team-member:hover {
            background: var(--bg-card-hover);
            transform: translateX(5px);
        }
        
        .team-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.527);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        
        .light-mode .team-avatar {
            background: rgba(224, 217, 255, 0.3);
        }
        /* COURSES */
        
        .courses {
            padding: 60px 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .section-header h2 {
            font-size: 28px;
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
            height: 2px;
            background: var(--text-primary);
        }
        
        .courses-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            max-width: 100%;
        }
        
        .course-card {
            background: var(--bg-card1);
            border-radius: 18px;
            padding: 32px;
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
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .course-card p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-size: 14px;
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
        }
        
        .btn-learn:hover {
            background: var(--btn-hover);
        }
        
        .read-more {
            text-align: right;
            margin-top: 20px;
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
        /* EVENTS */
        
        .events {
            padding: 60px 0 100px;
            color: #ffffff;
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
            color: #ffffff;
        }
        
        .event-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-5px);
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
            margin-bottom: 8px;
            color: #ffffff;
        }
        
        .event-content p {
            color: #ffffff;
            font-size: 15px;
            margin-bottom: 12px;
        }
        
        .event-tag {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            color: #eee;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .light-mode .event-tag {
            background: rgba(224, 217, 255, 0.2);
            color: var(--text-primary);
        }
        /* FOOTER */
        
        footer {
            background: var(--bg-card1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 0 30px;
            color: #ffffff;
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
            color: #ffffff;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--text-primary);
        }
        
        .social-links {
            display: flex;
            gap: 16px;
        }
        
        .social-links a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
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
            color: #ffffff;
            font-size: 14px;
        }
        
        .light-mode .footer-bottom {
            border-top: 1px solid rgba(224, 217, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }
            .hh {
                height: auto;
                min-height: 280px;
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
            nav {
                gap: 15px;
            }
            .courses-container {
                grid-template-columns: 1fr;
            }
            .section-separator {
                margin: 40px 0;
            }
            .theme-toggle {
                top: 10px;
                right: 10px;
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
 

    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </div>
                <nav>
                    <div class="user-menu">
                        <button class="user-button" id="userMenuBtn">
                            <div class="user-avatar"><?php echo strtoupper(substr($first_name , 0, 1)); ?></div>
                            <span><?php echo htmlspecialchars($first_name); htmlspecialchars($last_name); ?></span>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php" class="dropdown-item">My Profile</a>
                            <a href="my-courses.php" class="dropdown-item"> My Courses</a>
                            <a href="settings.php" class="dropdown-item"> Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="auth.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hh">
            <div class="hero-content">
                <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?> <?php echo htmlspecialchars($last_name); ?>!</h1>
                <p>Continue your learning journey with Master Edu. Track your progress and discover new opportunities.</p>
                <button class="btn-explore" onclick="location.href='courses.php'">Browse Courses</button>
            </div>
        </div>
    </section>

    <!-- Dashboard Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card" onclick="location.href='my-courses.php'">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Courses Enrolled</div>
                </div>
                <div class="stat-card" onclick="location.href='my-courses.php?filter=completed'">
                    <div class="stat-number">8</div>
                    <div class="stat-label"> Completed</div>
                </div>
                <div class="stat-card" onclick="location.href='achievements.php'">
                    <div class="stat-number">45</div>
                    <div class="stat-label"> Learning Hours</div>
                </div>
                <div class="stat-card" onclick="location.href='events.php'">
                    <div class="stat-number">3</div>
                    <div class="stat-label"> Upcoming Events</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="quick-actions">
        <div class="container">
            <div class="actions-grid">
                <div class="action-card" onclick="location.href='courses.php'">
                    <h3>Explore Courses</h3>
                    <p>Discover new learning paths</p>
                </div>
                <div class="action-card" onclick="location.href='my-courses.php'">
                    <h3>Continue Learning</h3>
                    <p>Resume your courses</p>
                </div>
                <div class="action-card" onclick="location.href='events.php'">
                    <h3>Join Events</h3>
                    <p>Participate in workshops</p>
                </div>
                <div class="action-card" onclick="location.href='community.php'">
                    <h3>Community</h3>
                    <p>Connect with learners</p>
                </div>
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
                    </svg> Recommended For You
                </h2>
                <button class="btn-primary" onclick="location.href='courses.php'">View All</button>
            </div>

            <div class="courses-container">
                <?php if (empty($courses)): ?>
                    <div class="course-card">
                        <h3>Object-Oriented Programming</h3>
                        <p>Master the principles of OOP including encapsulation, inheritance, and polymorphism.</p>
                        <button class="btn-learn">Learn More</button>
                    </div>
                    <div class="course-card">
                        <h3>Data Structures & Algorithms</h3>
                        <p>Explore fundamental data structures and algorithms. Improve your problem-solving skills.</p>
                        <button class="btn-learn">Learn More</button>
                    </div>
                    <div class="course-card">
                        <h3>Web Development</h3>
                        <p>Build modern, responsive websites using HTML, CSS, and JavaScript frameworks.</p>
                        <button class="btn-learn">Learn More</button>
                    </div>
                    <div class="course-card">
                        <h3>Machine Learning</h3>
                        <p>Dive into AI and machine learning. Understand algorithms and real-world applications.</p>
                        <button class="btn-learn">Learn More</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <button class="btn-learn" onclick="location.href='course-detail.php?id=<?php echo $course['id']; ?>'">Learn More</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="read-more">
                <a href="courses.php">See all courses →</a>
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
                    </svg> Upcoming Events
                </h2>
                <button class="btn-primary" onclick="location.href='events.php'">View All</button>
            </div>
            <div class="events-grid">
                <?php if (empty($events)): ?>
                    <div class="event-card">
                        <div class="event-image"></div>
                        <div class="event-content">
                            <h3>Annual Hackathon</h3>
                            <p>Join our 48-hour coding marathon. Build innovative projects and win prizes.</p>
                            <span class="event-tag">March 15-16</span>
                        </div>
                    </div>
                    <div class="event-card">
                        <div class="event-image"></div>
                        <div class="event-content">
                            <h3>Tech Career Fair</h3>
                            <p>Connect with top tech companies. Network with recruiters and explore opportunities.</p>
                            <span class="event-tag">April 5</span>
                        </div>
                    </div>
                    <div class="event-card">
                        <div class="event-image"></div>
                        <div class="event-content">
                            <h3>AI Workshop Series</h3>
                            <p>Hands-on workshops on machine learning and AI applications for all levels.</p>
                            <span class="event-tag">May 10-12</span>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-image"></div>
                            <div class="event-content">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                <span class="event-tag"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="read-more">
                <a href="events.php">See all events →</a>
            </div>
        </div>
    </section>
 <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Master Edu</h3>
                    <p>Innovative online interactive tools and expert guidance to help you master any subject.</p>
                    <div class="social-links">
                        <a href="#" title="Facebook">f</a>
                        <a href="#" title="Twitter">𝕏</a>
                        <a href="#" title="LinkedIn">in</a>
                        <a href="#" title="Instagram">📷</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="courses.php">Courses</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="support.php">Support</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul class="footer-links">
                        <li>Email: info@masteredu.com</li>
                        <li>Phone: +123 456 7890</li>
                        <li>Address: 123 Education St.</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Master Edu. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
  const themeToggle = document.getElementById('theme-toggle');
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

        // User menu dropdown
        document.getElementById('userMenuBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('dropdownMenu');
            dropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownMenu');
            const userMenu = document.querySelector('.user-menu');
            
            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('active');
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
        document.querySelectorAll('.course-card, .event-card, .stat-card, .action-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>
</html>