<?php
session_start();
require_once __DIR__ . '/../config/database.php';



$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';

// Get counts for dashboard from payments table
$pending_payments = $db->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'")->fetch()['total'];
$total_clients = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'client'")->fetch()['total'];
$today_payments = $db->query("SELECT COUNT(*) as total FROM payments WHERE DATE(payment_date) = CURDATE()")->fetch()['total'];

// Get ratings/feedback count
$new_ratings = $db->query("SELECT COUNT(*) as total FROM ratings WHERE DATE(created_at) = CURDATE()")->fetch()['total'];

// Get total revenue (sum of all approved payments)
$total_revenue = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")->fetch()['total'] ?? 0;

// Get recent pending payments
$recent_payments_query = "SELECT p.*, u.first_name, u.last_name, c.title as course_title 
                          FROM payments p 
                          LEFT JOIN users u ON p.user_id = u.id 
                          LEFT JOIN courses c ON p.course_id = c.id 
                          WHERE p.status = 'pending' 
                          ORDER BY p.created_at DESC 
                          LIMIT 5";
$recent_payments_result = $db->query($recent_payments_query);
$recent_payments = $recent_payments_result->fetchAll(PDO::FETCH_ASSOC);

// Get recent ratings
$recent_ratings_query = "SELECT r.*, u.first_name, u.last_name, c.title as course_title 
                         FROM ratings r 
                         LEFT JOIN users u ON r.user_id = u.id 
                         LEFT JOIN courses c ON r.course_id = c.id 
                         ORDER BY r.created_at DESC 
                         LIMIT 5";
$recent_ratings_result = $db->query($recent_ratings_query);
$recent_ratings = $recent_ratings_result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Commercial Assistant - Master Edu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">
    <style>
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
            --danger: #8BED4A;
            --warning: #8BED4A;
            --info: #8BED4A;
            --sidebar-width: 260px;
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Lusitana', serif;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background: var(--bg-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .logo svg {
            width: 28px;
            height: 28px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
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
        }
        
        .user-role {
            font-size: 12px;
            color: var(--btn-bg);
            background: rgba(157, 255, 87, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            text-align: center;
            margin-top: 2px;
        }
        
        /* Dashboard */
        .dashboard {
            padding: 40px 0;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .welcome-section h1 {
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(45deg, var(--btn-bg), var(--info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--btn-bg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
        
        .stat-card.payments i { color: var(--warning); }
        .stat-card.clients i { color: var(--btn-bg); }
        .stat-card.ratings i { color: var(--info); }
        .stat-card.revenue i { color: var(--danger); }
        
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .revenue-amount {
            font-size: 24px;
            color: var(--btn-bg);
            font-weight: bold;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            border-color: var(--btn-bg);
            transform: translateY(-3px);
            background: var(--bg-card-hover);
        }
        
        .action-card i {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--btn-bg);
            display: block;
        }
        
        .action-card h4 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .action-card p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 8px;
            border-left: 4px solid var(--btn-bg);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: rgba(157, 255, 87, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--btn-bg);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content h5 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .activity-content p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Two Column Layout for Recent Items */
        .two-column-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .recent-section {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 15px;
        }
        
        .recent-item {
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: 8px;
            border-left: 3px solid var(--info);
        }
        
        .recent-item.rating {
            border-left-color: var(--warning);
        }
        
        .recent-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .recent-item-title {
            font-weight: 600;
            font-size: 14px;
        }
        
        .recent-item-details {
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .recent-item-amount {
            color: var(--btn-bg);
            font-weight: 600;
            font-size: 14px;
        }
        
        .rating-stars {
            color: var(--warning);
            font-size: 12px;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--bg-card);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 20px;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        /* Logout Button */
        .logout-btn {
            position: fixed;
            bottom: 24px;
            left: 24px;
            padding: 10px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Lusitana', serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #ff2e43;
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
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .welcome-section h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="assistante-dashboard.php" class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </a>
                
                <div class="user-menu">
                    <div class="user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                        <div class="user-role">Commercial Assistant</div>
                    </div>
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
                <p>Commercial Assistant Interface - Client and Payment Management</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card payments">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $pending_payments; ?></h3>
                    <p>Pending Payments</p>
                </div>
                
                <div class="stat-card clients">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $total_clients; ?></h3>
                    <p>Total Clients</p>
                </div>
                
                <div class="stat-card ratings">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $new_ratings; ?></h3>
                    <p>New Ratings Today</p>
                </div>
                
                <div class="stat-card revenue">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="revenue-amount"><?php echo number_format($total_revenue, 2); ?> DA</div>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 class="section-title">Quick Actions</h2>
                <div class="actions-grid">
                    <a href="../admin/ratings.php" class="action-card">
                        <i class="fas fa-star"></i>
                        <h4>Ratings & Feedback</h4>
                        <p>Manage client ratings and comments</p>
                    </a>
                    
                    <a href="../admin/payments.php?status=pending" class="action-card">
                        <i class="fas fa-credit-card"></i>
                        <h4>Payment Management</h4>
                        <p>Validate pending payments</p>
                    </a>
                    
                    <a href="users.php" class="action-card">
                        <i class="fas fa-user-friends"></i>
                        <h4>Client List</h4>
                        <p>View all clients</p>
                    </a>
                    
                    
                </div>
            </div>
            
            <!-- Recent Items in Two Columns -->
            <div class="two-column-grid">
                <!-- Recent Pending Payments -->
                <div class="recent-section">
                    <h3 class="section-title">Recent Pending Payments</h3>
                    <div class="recent-list">
                        <?php if (count($recent_payments) > 0): ?>
                            <?php foreach($recent_payments as $payment): ?>
                            <div class="recent-item">
                                <div class="recent-item-header">
                                    <div class="recent-item-title">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </div>
                                    <div class="recent-item-amount">
                                        <?php echo number_format($payment['amount'], 2); ?> DA
                                    </div>
                                </div>
                                <div class="recent-item-details">
                                    <?php echo htmlspecialchars($payment['course_title'] ?? 'N/A'); ?>
                                </div>
                                <div class="recent-item-details">
                                    ID: <?php echo htmlspecialchars($payment['transaction_id']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="recent-item">
                                <div class="recent-item-details">No pending payments</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Ratings -->
                <div class="recent-section">
                    <h3 class="section-title">Recent Ratings</h3>
                    <div class="recent-list">
                        <?php if (count($recent_ratings) > 0): ?>
                            <?php foreach($recent_ratings as $rating): ?>
                            <div class="recent-item rating">
                                <div class="recent-item-header">
                                    <div class="recent-item-title">
                                        <?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?>
                                    </div>
                                    <div class="rating-stars">
                                        <?php 
                                        $stars = $rating['rating'] ?? 0;
                                        for($i = 1; $i <= 5; $i++): 
                                            if($i <= $stars): 
                                                echo '<i class="fas fa-star"></i>';
                                            else: 
                                                echo '<i class="far fa-star"></i>';
                                            endif;
                                        endfor; 
                                        ?>
                                    </div>
                                </div>
                                <div class="recent-item-details">
                                    <?php echo htmlspecialchars($rating['course_title'] ?? 'N/A'); ?>
                                </div>
                                <?php if(!empty($rating['comment'])): ?>
                                <div class="recent-item-details">
                                    "<?php echo htmlspecialchars(substr($rating['comment'], 0, 80)); ?>..."
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="recent-item">
                                <div class="recent-item-details">No recent ratings</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2 class="section-title">Recent Activity</h2>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="activity-content">
                            <h5>New client registered</h5>
                            <p>John Doe created an account</p>
                        </div>
                        <div class="activity-time">10 min ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Payment received</h5>
                            <p>Sarah B. made a payment for "Digital Marketing Course"</p>
                        </div>
                        <div class="activity-time">30 min ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="activity-content">
                            <h5>New rating received</h5>
                            <p>Karim D. rated "Web Development Course" with 5 stars</p>
                        </div>
                        <div class="activity-time">1 hour ago</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <!-- Logout Button -->
    <button class="logout-btn" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
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