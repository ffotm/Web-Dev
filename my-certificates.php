<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';

// Handle filter
$filter = $_GET['filter'] ?? 'all';
$status_conditions = [
    'all' => "1=1",
    'issued' => "certificate_issued = 1",
    'pending' => "certificate_issued = 0"
];
$status_condition = $status_conditions[$filter] ?? "1=1";

// Get user's certificates with course details
$certsQuery = "SELECT 
    c.*,
    cr.title as course_title,
    cr.description as course_description,
    cr.image_url as course_image,
    cr.category as course_category,
    DATE_FORMAT(c.completion_date, '%Y-%m-%d') as formatted_completion_date,
    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') as formatted_created_date
FROM certs_obtained c
JOIN courses cr ON c.course_id = cr.id
WHERE c.user_id = $user_id AND $status_condition
ORDER BY c.completion_date DESC";

$certsResult = $db->query($certsQuery);
$certificates = $certsResult->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN certificate_issued = 1 THEN 1 ELSE 0 END) as issued,
    SUM(CASE WHEN certificate_issued = 0 THEN 1 ELSE 0 END) as pending,
    AVG(final_grade) as avg_grade,
    SUM(total_hours_spent) as total_hours
FROM certs_obtained 
WHERE user_id = $user_id";

$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch(PDO::FETCH_ASSOC);

// Get counts for navbar
$subscriptionsQuery = "SELECT COUNT(*) as total FROM user_courses WHERE user_id = $user_id";
$subscriptionsResult = $db->query($subscriptionsQuery);
$totalSubscriptions = $subscriptionsResult->fetch()['total'];

$paymentsQuery = "SELECT COUNT(*) as total FROM payments WHERE user_id = $user_id";
$paymentsResult = $db->query($paymentsQuery);
$totalPayments = $paymentsResult->fetch()['total'];

$cartQuery = "SELECT COUNT(*) as total FROM user_cart WHERE user_id = $user_id AND cart_type = 'main'";
$cartResult = $db->query($cartQuery);
$cartCount = $cartResult->fetch()['total'];

$wishlistQuery = "SELECT COUNT(*) as total FROM user_cart WHERE user_id = $user_id AND cart_type = 'wishlist'";
$wishlistResult = $db->query($wishlistQuery);
$wishlistCount = $wishlistResult->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - Master Edu</title>
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
            --issued: #2ed573;
            --pending: #ffa502;
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
        
        nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            position: relative;
        }
        
        .nav-link:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--btn-bg);
            color: var(--btn-text);
        }

        .nav-badge {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            position: absolute;
            top: 2px;
            right: 2px;
        }

        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-family: 'Lusitana', serif;
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
        }
        
        .dropdown-menu.active {
            display: block;
        }
        
        .dropdown-item {
            padding: 12px 15px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background: var(--bg-card-hover);
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--text-secondary);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--btn-bg);
        }
        
        .stat-card i {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
        }
        
        .stat-card.total i { color: var(--text-primary); }
        .stat-card.issued i { color: var(--issued); }
        .stat-card.pending i { color: var(--pending); }
        .stat-card.hours i { color: var(--btn-bg); }
        
        .stat-card h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .stat-card .grade {
            font-size: 18px;
            color: var(--btn-bg);
            margin-top: 5px;
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background: var(--bg-card);
            border: 2px solid transparent;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tab:hover {
            background: var(--bg-card-hover);
        }
        
        .filter-tab.active {
            background: var(--btn-bg);
            color: var(--btn-text);
            border-color: var(--btn-bg);
        }
        
        .filter-tab.badge {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        /* Certificates Grid */
        .certificates-grid {
            display: grid;
            gap: 25px;
        }
        
        .certificate-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            border: 2px solid transparent;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .certificate-card.issued {
            border-color: var(--issued);
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(46, 213, 115, 0.1) 100%);
        }
        
        .certificate-card.pending {
            border-color: var(--pending);
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 165, 2, 0.1) 100%);
        }
        
        .certificate-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .certificate-badge.issued {
            background: rgba(46, 213, 115, 0.2);
            color: var(--issued);
            border: 1px solid var(--issued);
        }
        
        .certificate-badge.pending {
            background: rgba(255, 165, 2, 0.2);
            color: var(--pending);
            border: 1px solid var(--pending);
        }
        
        .certificate-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .certificate-header h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
            padding-right: 100px;
        }
        
        .certificate-header p {
            color: var(--text-secondary);
            font-size: 15px;
        }
        
        .certificate-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .detail-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .detail-value.grade {
            color: var(--btn-bg);
            font-size: 22px;
            position: relative;
            display: inline-block;
        }
        
        .detail-value.grade::after {
            content: '%';
            font-size: 14px;
            color: var(--text-secondary);
            margin-left: 2px;
        }
        
        .detail-value.hours {
            color: var(--info);
        }
        
        .detail-value.date {
            color: var(--text-primary);
            font-family: monospace;
        }
        
        .certificate-id {
            background: var(--bg-tertiary);
            padding: 10px 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            margin: 15px 0;
            display: inline-block;
        }
        
        .skills-section {
            margin: 25px 0;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 8px;
            border-left: 4px solid var(--btn-bg);
        }
        
        .skills-section h4 {
            margin-bottom: 15px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .skill-tag {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid rgba(157, 255, 87, 0.3);
        }
        
        .notes-section {
            margin: 20px 0;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-secondary);
            border-left: 4px solid var(--text-secondary);
        }
        
        .certificate-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .action-btn {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            font-family: 'Lusitana', serif;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .action-btn.download {
            background: var(--btn-bg);
            color: var(--btn-text);
        }
        
        .action-btn.preview {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.share {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* No Certificates Message */
        .no-certificates {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }
        
        .no-certificates i {
            font-size: 64px;
            color: var(--text-secondary);
            margin-bottom: 25px;
            display: block;
        }
        
        .no-certificates h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .no-certificates p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 16px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .browse-btn {
            padding: 15px 35px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: 8px;
            font-family: 'Lusitana', serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .browse-btn:hover {
            background: var(--btn-hover);
            transform: translateY(-3px);
        }
        
        /* Certificate Ribbon */
        .certificate-ribbon {
            position: absolute;
            top: -10px;
            left: -10px;
            padding: 8px 20px;
            background: linear-gradient(45deg, var(--issued), var(--btn-bg));
            color: var(--btn-text);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: rotate(-45deg);
            transform-origin: top right;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
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
        
        @media (max-width: 992px) {
            .certificate-details {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .certificate-header h3 {
                padding-right: 0;
                font-size: 20px;
            }
            
            .certificate-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            nav {
                gap: 8px;
            }
            
            .nav-link span {
                display: none;
            }
            
            .skills-list {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .certificate-details {
                grid-template-columns: 1fr;
            }
            
            .filter-tabs {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </a>
                <nav>
                    <a href="my-subscriptions.php" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                        <?php if($totalPayments > 0): ?>
                        <span class="nav-badge"><?php echo $totalPayments; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="my-certificates.php" class="nav-link active">
                        <i class="fas fa-certificate"></i>
                        <span>Certificates</span>
                        <?php if($stats['total'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['total']; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                        <?php if($wishlistCount > 0): ?>
                        <span class="nav-badge"><?php echo $wishlistCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Cart</span>
                        <?php if($cartCount > 0): ?>
                        <span class="nav-badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="user-menu">
                        <button class="user-button" id="userMenuBtn">
                            <div class="user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                            <span><?php echo htmlspecialchars($first_name); ?></span>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                            <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                            <a href="auth.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-award"></i> My Certificates</h1>
                <p>Your achievements and learning milestones</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <i class="fas fa-trophy"></i>
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total Certificates</p>
                </div>
                
                <div class="stat-card issued">
                    <i class="fas fa-certificate"></i>
                    <h3><?php echo $stats['issued'] ?? 0; ?></h3>
                    <p>Issued</p>
                </div>
                
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
                
                <div class="stat-card hours">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['total_hours'] ?? 0; ?></h3>
                    <p>Hours Invested</p>
                    <?php if($stats['avg_grade']): ?>
                    <div class="grade">Avg: <?php echo number_format($stats['avg_grade'], 1); ?>%</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Certificates
                    <span class="badge"><?php echo $stats['total'] ?? 0; ?></span>
                </a>
                
                <a href="?filter=issued" class="filter-tab <?php echo $filter == 'issued' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i> Issued
                    <span class="badge"><?php echo $stats['issued'] ?? 0; ?></span>
                </a>
                
                <a href="?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                    <span class="badge"><?php echo $stats['pending'] ?? 0; ?></span>
                </a>
            </div>
            
            <!-- Certificates Grid -->
            <div class="certificates-grid">
                <?php if (empty($certificates)): ?>
                <div class="no-certificates">
                    <i class="fas fa-award"></i>
                    <h3>No Certificates Yet</h3>
                    <p>Complete your enrolled courses to earn certificates. Your achievements will appear here once you finish a course.</p>
                    <a href="my-subscriptions.php" class="browse-btn">
                        <i class="fas fa-play-circle"></i> Continue Learning
                    </a>
                </div>
                <?php else: ?>
                <?php foreach($certificates as $cert): ?>
                <div class="certificate-card <?php echo $cert['certificate_issued'] ? 'issued' : 'pending'; ?>">
                    <?php if($cert['certificate_issued']): ?>
                    <div class="certificate-ribbon">ACHIEVED</div>
                    <?php endif; ?>
                    
                    <div class="certificate-header">
                        <h3><?php echo htmlspecialchars($cert['course_title']); ?></h3>
                        <p>Completed on <?php echo $cert['formatted_completion_date']; ?></p>
                    </div>
                    
                    <div class="certificate-badge <?php echo $cert['certificate_issued'] ? 'issued' : 'pending'; ?>">
                        <?php echo $cert['certificate_issued'] ? 'Certificate Issued' : 'Pending Approval'; ?>
                    </div>
                    
                    <?php if($cert['certificate_id']): ?>
                    <div class="certificate-id">
                        Certificate ID: <?php echo htmlspecialchars($cert['certificate_id']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="certificate-details">
                        <div class="detail-item">
                            <div class="detail-label">Final Grade</div>
                            <div class="detail-value grade"><?php echo number_format($cert['final_grade'], 1); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Hours Spent</div>
                            <div class="detail-value hours"><?php echo number_format($cert['total_hours_spent'], 1); ?> hrs</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Completion Date</div>
                            <div class="detail-value date"><?php echo $cert['formatted_completion_date']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <?php if($cert['is_approved']): ?>
                                <span style="color: var(--issued);">✓ Approved</span>
                                <?php elseif($cert['certificate_issued']): ?>
                                <span style="color: var(--issued);">✓ Issued</span>
                                <?php else: ?>
                                <span style="color: var(--pending);">⏳ Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($cert['skills_learned']): ?>
                    <div class="skills-section">
                        <h4><i class="fas fa-lightbulb"></i> Skills Acquired</h4>
                        <div class="skills-list">
                            <?php 
                            $skills = explode(',', $cert['skills_learned']);
                            foreach($skills as $skill):
                                $skill = trim($skill);
                                if(!empty($skill)):
                            ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($cert['notes']): ?>
                    <div class="notes-section">
                        <strong><i class="fas fa-sticky-note"></i> Notes:</strong> <?php echo htmlspecialchars($cert['notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="certificate-actions">
                        <?php if($cert['certificate_issued']): ?>
                        <button class="action-btn download" onclick="downloadCertificate('<?php echo $cert['certificate_id']; ?>')">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                        <button class="action-btn preview" onclick="previewCertificate('<?php echo $cert['certificate_id']; ?>')">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button class="action-btn share" onclick="shareCertificate('<?php echo $cert['certificate_id']; ?>')">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                        <?php else: ?>
                        <button class="action-btn preview" style="background: var(--pending); color: white;" onclick="trackApproval('<?php echo $cert['id']; ?>')">
                            <i class="fas fa-clock"></i> Track Approval
                        </button>
                        <button class="action-btn share" onclick="contactSupport('<?php echo $cert['id']; ?>')">
                            <i class="fas fa-question-circle"></i> Need Help?
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

        // User menu
        document.getElementById('userMenuBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('dropdownMenu').classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownMenu');
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Action functions
        function downloadCertificate(certificateId) {
            alert('Downloading certificate: ' + certificateId);
            // In production: window.location.href = 'download-certificate.php?id=' + certificateId;
        }
        
        function previewCertificate(certificateId) {
            alert('Previewing certificate: ' + certificateId);
            // In production: Open modal with certificate preview
        }
        
        function shareCertificate(certificateId) {
            alert('Sharing certificate: ' + certificateId);
            // In production: Open share dialog with social media options
        }
        
        function trackApproval(certId) {
            alert('Tracking approval status for certificate ID: ' + certId);
            // In production: Show detailed status modal
        }
        
        function contactSupport(certId) {
            alert('Opening support contact form for certificate ID: ' + certId);
            // In production: Open contact form modal
        }
        
        // Update filter tabs to show active state
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>