<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}
// Create database object
$database = new Database();
$db = $database->getConnection();

try {

    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $result = $db->query($query);   
    $totalStudents = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Courses
    $query = "SELECT COUNT(*) as total FROM courses";
$result = $db->query($query);
    $activeCourses = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Revenue

    $query = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
     $result = $db->query($query);
      $totalRevenue = $result -> fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    


    // Upcoming Events

    $query = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()";
     $result = $db->query($query);
     $upcomingEvents = $result->fetch(PDO::FETCH_ASSOC)['total'];

    
    // Total Formateurs
  $query = "SELECT COUNT(*) as total FROM users WHERE role = 'formateur'";
     $result = $db->query($query);
    $totalFormateurs = $result->fetch(PDO::FETCH_ASSOC)['total'];


    // Total Sessions
 
   
$query = "SELECT COUNT(*) as total FROM sessions";
     $result = $db->query($query);
     $totalSessions = $result->fetch(PDO::FETCH_ASSOC)['total'];

    // Certifications Issued
$query = "SELECT COUNT(*) as total FROM certifications";
     $result = $db->query($query);
   
    $totalCertifications = $result->fetch(PDO::FETCH_ASSOC)['total'];
    


    // Total Payments
$query = "SELECT COUNT(*) as total FROM payments";
     $result = $db->query($query);
   
    $totalPayments = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Approvals
$query = "SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'pending'";
     $result = $db->query($query);
    
    $pendingApprovals = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Sessions Today
$query = "SELECT COUNT(*) as total FROM sessions WHERE DATE(session_date) = CURDATE()";
     $result = $db->query($query);

    $activeSessionsToday = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Certifications
$query = "SELECT COUNT(*) as total FROM certifications";
     $result = $db->query($query);
   
    $pendingCertifications = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent Activity
    $query ="
        SELECT * FROM (
            SELECT 'registration' as type, created_at, user_id, course_id FROM registrations 
            UNION ALL
            SELECT 'payment' as type, created_at, user_id, course_id FROM payments
            UNION ALL
            SELECT 'certification' as type, created_at, user_id, course_id FROM certifications
        ) as activities 
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    $result = $db->query($query);
    $recentActivities = $result->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user info
    $query = $db->prepare("SELECT * FROM users WHERE id = ?");
    $query->execute([$_SESSION['user_id']]);
    $currentUser = $query->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard - Master Edu</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
 <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">       
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
                --sidebar-width: 260px;
            }
            
            .light-mode {
                --bg-primary: #f8f9fa;
                --bg-secondary: #BFB6D9;
                --bg-tertiary: #b4a8d8ff;
                --bg-card1: #aea0d8ff;
                --bg-card: #BFB6D9;
                --bg-card-hover: #e9ecef;
                --text-primary: #212529;
                --text-secondary: #495057;
                --btn-bg: #9DFF57;
                --btn-text: #14002E;
                --btn-hover: #8BED4A;
            }
            
            body {
                background: var(--bg-primary);
                color: var(--text-primary);
                font-family: "Lusitana", serif;
                display: flex;
                min-height: 100vh;
            }
            /* Sidebar */
            
            .sidebar {
                width: var(--sidebar-width);
                background: var(--bg-secondary);
                position: fixed;
                height: 100vh;
                left: 0;
                top: 0;
                overflow-y: auto;
                border-right: 1px solid rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar-header {
                padding: 24px 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .logo {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 20px;
                font-weight: 700;
                color: var(--text-primary);
            }
            
            .logo svg {
                width: 28px;
                height: 28px;
            }
            
            .sidebar-menu {
                padding: 20px 0;
            }
            
            .menu-item {
                display: flex;
                align-items: center;
                padding: 12px 20px;
                color: var(--text-secondary);
                text-decoration: none;
                transition: all 0.2s;
                cursor: pointer;
            }
            
            .menu-item:hover {
                background: var(--bg-card-hover);
                color: var(--text-primary);
            }
            
            .menu-item.active {
                background: var(--bg-card);
                color: var(--btn-bg);
                border-left: 3px solid var(--btn-bg);
            }
            
            .menu-item i {
                width: 20px;
                margin-right: 12px;
                font-size: 16px;
            }
            
            .menu-item span {
                flex: 1;
            }
            
            .menu-badge {
                background: var(--btn-bg);
                color: var(--btn-text);
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            /* Main Content */
            
            .main-content {
                margin-left: var(--sidebar-width);
                flex: 1;
                padding: 24px;
                width: calc(100% - var(--sidebar-width));
            }
            /* Header */
            
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 32px;
                padding-bottom: 20px;
                border-bottom: 1px solid var(--bg-card);
            }
            
            .header-title h1 {
                font-size: 28px;
                margin-bottom: 4px;
            }
            
            .header-title p {
                color: var(--text-secondary);
                font-size: 14px;
            }
            
            .header-actions {
                display: flex;
                gap: 12px;
                align-items: center;
            }
            
            .user-profile {
                display: flex;
                align-items: center;
                gap: 12px;
                background: var(--bg-card);
                padding: 8px 16px;
                border-radius: 25px;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .user-profile:hover {
                background: var(--bg-card-hover);
            }
            
            .user-avatar {
                width: 36px;
                height: 36px;
                background: var(--btn-bg);
                color: var(--btn-text);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 14px;
            }
            /* Stats Grid */
            
            .stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr;
                gap: 20px;
                margin-bottom: 32px;
            }
            
            .stat-card {
                background: var(--bg-card);
                border-radius: 12px;
                padding: 24px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                transition: all 0.3s;
            }
            
            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            }
            
            .stat-content {
                display: flex;
                align-items: center;
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                margin-right: 16px;
                background: rgba(157, 255, 87, 0.2);
                color: var(--btn-bg);
            }
            
            .stat-info h3 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 4px;
            }
            
            .stat-info p {
                font-size: 14px;
                color: var(--text-secondary);
            }
            /* Content Section */
            
            .content-section {
                display: grid;
                grid-template-rows: 1fr 1fr ;
                gap: 24px;
            }
            
            .card {
                background: var(--bg-primary);    
                border-radius: 12px;
                padding: 24px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .card-title {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 20px;
            }
            
            .activity-list {
                list-style: none;
            }
            
            .activity-item {
                display: flex;
                align-items: flex-start;
                padding: 16px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .activity-item:last-child {
                border-bottom: none;
            }
            
            .activity-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 16px;
                flex-shrink: 0;
                background: rgba(157, 255, 87, 0.2);
                color: var(--btn-bg);
            }
            
            .activity-content h4 {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 4px;
            }
            
            .activity-content p {
                font-size: 13px;
                color: var(--text-secondary);
                margin-bottom: 4px;
            }
            
            .activity-time {
                font-size: 12px;
                color: var(--text-secondary);
            }
            
            .quick-stats {
                display: grid;
               grid-template-columns: 1fr 1fr 1fr  ;
                gap: 10px;
height: 250px;
            }
            
            .quick-stat-item {
                padding: 16px;
                background: var(--bg-secondary);
                border-radius: 8px;
            }
            
            .quick-stat-item p {
                font-size: 13px;
                color: var(--text-secondary);
                margin-bottom: 8px;
            }
            
            .quick-stat-item h3 {
                font-size: 24px;
                font-weight: 700;
            }
            /* Theme Toggle */
            
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
            /* Responsive */
            
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                }
                .sidebar.active {
                    transform: translateX(0);
                }
                .main-content {
                    margin-left: 0;
                    width: 100%;
                }
                .content-section {
                    grid-template-columns: 1fr;
                }
            }
.b{
background-color: #BFB6D9;

}

        </style>
    </head>

    <body>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
                    <span>Master Edu</span>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="students.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
                    <span class="menu-badge"><?php echo $totalStudents; ?></span>
                </a>
                <a href="teachers.php" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                    <span class="menu-badge"><?php echo $totalFormateurs; ?></span>
                </a>
                <a href="courses.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Courses</span>
                    <span class="menu-badge"><?php echo $activeCourses; ?></span>
                </a>
                <a href="certifications.php" class="menu-item">
                    <i class="fas fa-certificate"></i>
                    <span>Certifications</span>
                </a>
                <a href="payments.php" class="menu-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payments</span>
                </a>
                <a href="ratings.php" class="menu-item">
                    <i class="fas fa-star"></i>
                    <span>Ratings</span>
                </a>
                <a href="registrations.php" class="menu-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Registrations</span>
                    <?php if($pendingApprovals > 0): ?>
                    <span class="menu-badge"><?php echo $pendingApprovals; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pending-approvals.php" class="menu-item">
                    <i class="fas fa-clock"></i>
                    <span>Pending Approvals</span>
                    <?php if($pendingApprovals > 0): ?>
                    <span class="menu-badge"><?php echo $pendingApprovals; ?></span>
                    <?php endif; ?>
                </a>
                <a href="user-management.php" class="menu-item">
                    <i class="fas fa-user-cog"></i>
                    <span>User Management</span>
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1>Dashboard Admin</h1>
                    <p>Welcome back,
                        <?php echo htmlspecialchars($currentUser['first_name'] ?? 'Admin'); ?>!</p>
                </div>
                <div class="header-actions">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($currentUser['first_name'] ?? 'Admin'); ?></span>
                    </div>
                </div>
            </div>
 <!-- Content Section -->
            <div class="content-section" style="margin-bottom: 20px;">
                <!-- Recent Activity -->
                <div class="card b"style="color: #14002E;">
                <h2 class="card-title">Recent Events</h2>    
                    <ul class="activity-list">
                        <?php 
                    $activityIcons = [
                        'registration' => 'fa-user-plus',
                        'payment' => 'fa-money-bill-wave',
                        'certification' => 'fa-certificate'
                    ];
                    
                    $activityTitles = [
                        'registration' => 'New Registration',
                        'payment' => 'Payment Received',
                        'certification' => 'Certificate Issued'
                    ];
                    
                    foreach($recentActivities as $activity): 
                        $icon = $activityIcons[$activity['type']] ?? 'fa-info-circle';
                        $title = $activityTitles[$activity['type']] ?? 'Activity';
                        $timeAgo = time() - strtotime($activity['created_at']);
                        $timeString = $timeAgo < 3600 ? floor($timeAgo/60) . ' minutes ago' : 
                                     ($timeAgo < 86400 ? floor($timeAgo/3600) . ' hours ago' : 
                                      floor($timeAgo/86400) . ' days ago');
                    ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <h4>
                                    <?php echo $title; ?>
                                </h4>
                                <p>Activity recorded for course ID:
                                    <?php echo $activity['course_id']; ?>
                                </p>
                                <div class="activity-time">
                                    <?php echo $timeString; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Recent Stats -->
                <div class="card">
                    <h2 class="card-title">Recent Stats</h2>
                    <div class="quick-stats">
                        <div class="quick-stat-item">
                            <p>Today</p>
<h3>
                                <?php echo $activeSessionsToday; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Active Sessions</p>
                             <h3>
                                <?php echo $pendingCertifications; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Pending Certifications</p>
                            <h3>    
                                <?php echo $upcomingEvents; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Upcoming Events</p>
                        </div>
                        <div class="quick-stat-item">
                            <p>Last Week</p>
<h3>
                                <?php echo $activeSessionsToday; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Active Sessions</p>
                            <h3>
                                <?php echo $pendingCertifications; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Pending Certifications</p>

                           <h3>
                                <?php echo $upcomingEvents; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Upcoming Events</p>
                        </div>
                        <div class="quick-stat-item">
                            <p>This Week</p>
<h3>
                                <?php echo $activeSessionsToday; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Active Sessions</p>
                           <h3>
                                <?php echo $pendingCertifications; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Pending Certifications</p>
 <h3>
                                <?php echo $upcomingEvents; ?>
                            </h3>
                            <p style="font-size: 12px; margin-top: 4px;">Upcoming Events</p>
                        </div>
                    </div>
                </div>
            </div>
        

            <!-- Stats Grid -->
     <h2 class="card-title">general Stats</h2>     
  <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>
                                <?php echo number_format($totalStudents); ?>
                            </h3>
                            <h4>Total Students</h4>
<p>x% from last month</p>
                            
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3>
                                <?php echo $activeCourses; ?>
                            </h3>
                            <h4>Active Courses</h4>
<p>added x courses from last month</p>
                           
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>
                                <?php echo number_format($totalRevenue); ?> DA</h3>
                    <h4>Total Revenue</h4>
<p>x% from last month</p>
                            
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3>
                                <?php echo $upcomingEvents; ?>
                            </h3>
                            <p>Upcoming Events</p>
                        </div>
                    </div>
                </div>
            </div>

        </main> 
        <!-- Theme Toggle -->
        <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>

        <script>
            // Theme Toggle
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
        </script>
    </body>

    </html>