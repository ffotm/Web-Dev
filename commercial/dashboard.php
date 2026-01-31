<?php
session_start();

require_once __DIR__ . '/../config/database.php';



$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';


// Get counts for dashboard
$total_promotions = $db->query("SELECT COUNT(*) as total FROM promotions WHERE is_active = 1")->fetch()['total'];

// Check if sessions table exists, if not use 0
try {
    $upcoming_sessions = $db->query("SELECT COUNT(*) as total FROM sessions WHERE session_date >= CURDATE() AND status = 'scheduled'")->fetch()['total'];
} catch (Exception $e) {
    $upcoming_sessions = 0;
}

$upcoming_events = $db->query("SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()")->fetch()['total'];

// Monthly revenue from payments table
$monthly_revenue_result = $db->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'completed'");
$monthly_revenue = $monthly_revenue_result->fetch()['total'] ?? 0;

// Get recent promotions
$recent_promotions_query = "SELECT p.*, c.title as course_title 
                           FROM promotions p 
                           LEFT JOIN courses c ON p.course_id = c.id 
                           WHERE p.is_active = 1 
                           ORDER BY p.created_at DESC 
                           LIMIT 5";
$recent_promotions_result = $db->query($recent_promotions_query);
$recent_promotions = $recent_promotions_result->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming sessions (if table exists)
try {
    $upcoming_sessions_query = "SELECT s.*, c.title as course_title, u.first_name, u.last_name 
                               FROM sessions s 
                               LEFT JOIN courses c ON s.course_id = c.id 
                               LEFT JOIN users u ON s.formateur_id = u.id 
                               WHERE s.session_date >= CURDATE() 
                               ORDER BY s.session_date ASC 
                               LIMIT 5";
    $upcoming_sessions_result = $db->query($upcoming_sessions_query);
    $upcoming_sessions_data = $upcoming_sessions_result->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $upcoming_sessions_data = [];
}

// Get recent sales (payments)
$recent_sales_query = "SELECT p.*, u.first_name, u.last_name, c.title as course_title 
                      FROM payments p 
                      LEFT JOIN users u ON p.user_id = u.id 
                      LEFT JOIN courses c ON p.course_id = c.id 
                      WHERE p.status = 'completed' 
                      ORDER BY p.created_at DESC 
                      LIMIT 5";
$recent_sales_result = $db->query($recent_sales_query);
$recent_sales = $recent_sales_result->fetchAll(PDO::FETCH_ASSOC);

// Get total students registered this month
$students_this_month = $db->query("SELECT COUNT(*) as total FROM registrations WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Dashboard - Master Edu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">
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
            --bg-card-hover: #443a66;
            --text-primary: #E0D9FF;
            --text-secondary: #BFB6D9;
            --btn-bg: #9DFF57;
            --btn-text: #14002E;
            --btn-hover: #8BED4A;
            --danger: #ff4757;
            --warning: #ffa502;
            --info: #2ed573;
        }
        
        .light-mode {
            --bg-primary: #f8f9fa;
            --bg-secondary: #BFB6D9;
            --bg-tertiary: #b4a8d8ff;
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
            font-family: 'Lusitana', serif;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }
        
        /* Header */
        header {
            background: var(--bg-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            gap: 12px;
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .logo svg {
            width: 32px;
            height: 32px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info-container {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-card);
            padding: 8px 16px;
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 15px;
        }
        
        .user-role {
            font-size: 11px;
            color: var(--btn-bg);
            background: rgba(157, 255, 87, 0.2);
            padding: 2px 10px;
            border-radius: 12px;
            text-align: center;
            margin-top: 3px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Dashboard */
        .dashboard {
            padding: 40px 0 80px;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            padding: 30px;
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .welcome-section h1 {
            font-size: 40px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--btn-bg), var(--info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-section p {
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(157, 255, 87, 0.05);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            border-color: var(--btn-bg);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .stat-card i {
            font-size: 48px;
            margin-bottom: 18px;
            display: block;
        }
        
        .stat-card.promotions i { color: var(--info); }
        .stat-card.sessions i { color: var(--btn-bg); }
        .stat-card.events i { color: var(--warning); }
        .stat-card.revenue i { color: var(--danger); }
        
        .stat-card h3 {
            font-size: 42px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .stat-card p {
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 500;
        }
        
        .revenue-amount {
            font-size: 32px;
            color: var(--btn-bg);
            font-weight: bold;
            margin: 12px 0;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-bottom: 50px;
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title i {
            color: var(--btn-bg);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }
        
        .action-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(157, 255, 87, 0.1) 0%, transparent 70%);
            transform: scale(0);
            transition: transform 0.5s;
        }
        
        .action-card:hover::before {
            transform: scale(1);
        }
        
        .action-card:hover {
            border-color: var(--btn-bg);
            transform: translateY(-5px);
            background: var(--bg-card-hover);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .action-card i {
            font-size: 42px;
            margin-bottom: 18px;
            color: var(--btn-bg);
            display: block;
            position: relative;
            z-index: 1;
        }
        
        .action-card h4 {
            font-size: 20px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .action-card p {
            color: var(--text-secondary);
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        /* Two Column Layout */
        .two-column-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .recent-section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .recent-section h3 {
            font-size: 22px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recent-section h3 i {
            color: var(--btn-bg);
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .recent-item {
            padding: 18px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border-left: 4px solid var(--info);
            transition: all 0.3s;
        }
        
        .recent-item:hover {
            background: var(--bg-tertiary);
            transform: translateX(5px);
        }
        
        .recent-item.session {
            border-left-color: var(--btn-bg);
        }
        
        .recent-item.sale {
            border-left-color: var(--danger);
        }
        
        .recent-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .recent-item-title {
            font-weight: 600;
            font-size: 15px;
        }
        
        .recent-item-details {
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .recent-item-amount {
            color: var(--btn-bg);
            font-weight: 700;
            font-size: 16px;
        }
        
        .discount-badge {
            background: rgba(46, 213, 115, 0.2);
            color: var(--info);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .session-time {
            color: var(--btn-bg);
            font-size: 13px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--bg-card);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 22px;
            z-index: 100;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .theme-toggle:hover {
            transform: scale(1.15);
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }
        
        /* Logout Button */
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Lusitana', serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
        }
        
        .logout-btn:hover {
            background: #ff2e43;
            transform: translateY(-2px);
        }
        
        @media (max-width: 992px) {
            .two-column-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section h1 {
                font-size: 28px;
            }
            
            .user-info-container {
                padding: 6px 12px;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="commercial-dashboard.php" class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </a>
                
                <div class="user-menu">
                    <div class="user-info-container">
                        <div class="user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                            <div class="user-role">Commercial</div>
                        </div>
                    </div>
                    <button class="logout-btn" onclick="window.location.href='../auth.php'">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Dashboard -->
    <main class="dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($first_name); ?>!</h1>
                <p>Commercial Interface - Manage Promotions, Sessions & Revenue</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card promotions">
                    <i class="fas fa-tags"></i>
                    <h3><?php echo $total_promotions; ?></h3>
                    <p>Active Promotions</p>
                </div>
                
                <div class="stat-card sessions">
                    <i class="fas fa-calendar-alt"></i>
                    <h3><?php echo $upcoming_sessions; ?></h3>
                    <p>Upcoming Sessions</p>
                </div>
                
                <div class="stat-card events">
                    <i class="fas fa-calendar-star"></i>
                    <h3><?php echo $upcoming_events; ?></h3>
                    <p>Upcoming Events</p>
                </div>
                
                <div class="stat-card revenue">
                    <i class="fas fa-chart-line"></i>
                    <div class="revenue-amount"><?php echo number_format($monthly_revenue, 2); ?> DA</div>
                    <p>Monthly Revenue</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
                <div class="actions-grid">
                    <a href="../admin/promotions.php" class="action-card">
                        <i class="fas fa-percentage"></i>
                        <h4>Promotions</h4>
                        <p>Manage promotions and discounts</p>
                    </a>
                    
                    <a href="../admin/sessions.php" class="action-card">
                        <i class="fas fa-calendar-plus"></i>
                        <h4>Sessions</h4>
                        <p>Schedule private sessions</p>
                    </a>
                    
                    <a href="../admin/events.php" class="action-card">
                        <i class="fas fa-calendar-alt"></i>
                        <h4>Events</h4>
                        <p>Manage events and webinars</p>
                    </a>
                    
                    
                </div>
            </div>
            
            <!-- Recent Items in Two Columns -->
            <div class="two-column-grid">
                <!-- Recent Promotions -->
                <div class="recent-section">
                    <h3><i class="fas fa-tag"></i> Recent Promotions</h3>
                    <div class="recent-list">
                        <?php if (count($recent_promotions) > 0): ?>
                            <?php foreach($recent_promotions as $promotion): ?>
                            <div class="recent-item">
                                <div class="recent-item-header">
                                    <div class="recent-item-title">
                                        <?php echo htmlspecialchars($promotion['title']); ?>
                                    </div>
                                    <?php if($promotion['discount_percentage'] > 0): ?>
                                    <div class="discount-badge">
                                        <?php echo $promotion['discount_percentage']; ?>% OFF
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="recent-item-details">
                                    <?php echo htmlspecialchars($promotion['course_title'] ?? 'General Promotion'); ?>
                                </div>
                                <?php if(!empty($promotion['code'])): ?>
                                <div class="recent-item-details">
                                    Code: <strong><?php echo htmlspecialchars($promotion['code']); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <p>No active promotions</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Sessions -->
                <div class="recent-section">
                    <h3><i class="fas fa-calendar-check"></i> Upcoming Sessions</h3>
                    <div class="recent-list">
                        <?php if (count($upcoming_sessions_data) > 0): ?>
                            <?php foreach($upcoming_sessions_data as $session): ?>
                            <div class="recent-item session">
                                <div class="recent-item-header">
                                    <div class="recent-item-title">
                                        <?php echo htmlspecialchars($session['course_title']); ?>
                                    </div>
                                    <div class="session-time">
                                        <?php echo date('M d, H:i', strtotime($session['session_date'])); ?>
                                    </div>
                                </div>
                                <div class="recent-item-details">
                                    Trainer: <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                </div>
                                <?php if(isset($session['duration_minutes'])): ?>
                                <div class="recent-item-details">
                                    Duration: <?php echo $session['duration_minutes']; ?> minutes
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No upcoming sessions</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Sales -->
            <div class="recent-section">
                <h3><i class="fas fa-dollar-sign"></i> Recent Sales</h3>
                <div class="recent-list">
                    <?php if (count($recent_sales) > 0): ?>
                        <?php foreach($recent_sales as $sale): ?>
                        <div class="recent-item sale">
                            <div class="recent-item-header">
                                <div class="recent-item-title">
                                    <?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?>
                                </div>
                                <div class="recent-item-amount">
                                    <?php echo number_format($sale['amount'], 2); ?> DA
                                </div>
                            </div>
                            <div class="recent-item-details">
                                <?php echo htmlspecialchars($sale['course_title'] ?? 'N/A'); ?>
                            </div>
                            <div class="recent-item-details">
                                Transaction ID: <?php echo htmlspecialchars($sale['transaction_id']); ?>
                            </div>
                            <div class="recent-item-details">
                                <?php echo date('M d, Y - H:i', strtotime($sale['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No recent sales</p>
                        </div>
                    <?php endif; ?>
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