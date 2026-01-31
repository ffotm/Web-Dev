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

// Handle payment status filter
$status_filter = $_GET['status'] ?? 'all';
$status_conditions = [
    'all' => "1=1",
    'pending' => "p.status = 'pending'",
    'approved' => "p.status = 'approved'",
    'rejected' => "p.status = 'rejected'"
];
$status_condition = $status_conditions[$status_filter] ?? "1=1";

// Get user's payments with course details
$paymentsQuery = "SELECT 
    p.*,
    c.title as course_title,
    c.description as course_description,
    c.image_url as course_image,
    c.duration_hours as course_duration,
    DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i') as formatted_date,
    DATE_FORMAT(p.payment_date, '%Y-%m-%d') as formatted_payment_date
FROM payments p
JOIN courses c ON p.course_id = c.id
WHERE p.user_id = $user_id AND $status_condition
ORDER BY p.created_at DESC";

$paymentsResult = $db->query($paymentsQuery);
$payments = $paymentsResult->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(amount) as total_amount
FROM payments 
WHERE user_id = $user_id";

$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch(PDO::FETCH_ASSOC);

// Get counts for navbar
$subscriptionsQuery = "SELECT COUNT(*) as total FROM user_courses WHERE user_id = $user_id";
$subscriptionsResult = $db->query($subscriptionsQuery);
$totalSubscriptions = $subscriptionsResult->fetch()['total'];

$certsQuery = "SELECT COUNT(*) as total FROM certs_obtained WHERE user_id = $user_id";
$certsResult = $db->query($certsQuery);
$totalCertificates = $certsResult->fetch()['total'];

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
    <title>My Payments - Master Edu</title>
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
            --danger: #8BED4A;
            --warning: #8BED4A;
            --info: #8BED4A;
            --pending: #8BED4A;
            --approved: #8BED4A;
            --rejected: #8BED4A;
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
        .stat-card.pending i { color: var(--pending); }
        .stat-card.approved i { color: var(--approved); }
        .stat-card.rejected i { color: var(--rejected); }
        
        .stat-card h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: var(--text-secondary);
            font-size: 14px;
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
        
        /* Payments Grid */
        .payments-grid {
            display: grid;
            gap: 20px;
        }
        
        .payment-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid var(--text-primary);
            transition: all 0.3s;
        }
        
        .payment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .payment-card.pending {
            border-left-color: var(--pending);
        }
        
        .payment-card.approved {
            border-left-color: var(--approved);
        }
        
        .payment-card.rejected {
            border-left-color: var(--rejected);
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .course-info h3 {
            font-size: 20px;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .course-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .payment-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payment-status.pending {
            background: rgba(255, 165, 2, 0.2);
            color: var(--pending);
        }
        
        .payment-status.approved {
            background: rgba(46, 213, 115, 0.2);
            color: var(--approved);
        }
        
        .payment-status.rejected {
            background: rgba(255, 71, 87, 0.2);
            color: var(--rejected);
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .detail-value.amount {
            color: var(--btn-bg);
            font-size: 18px;
        }
        
        .payment-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-family: 'Lusitana', serif;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn.view {
            background: var(--btn-bg);
            color: var(--btn-text);
        }
        
        .action-btn.upload {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
            border: 1px solid var(--btn-bg);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        /* No Payments Message */
        .no-payments {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }
        
        .no-payments i {
            font-size: 48px;
            color: var(--text-secondary);
            margin-bottom: 20px;
            display: block;
        }
        
        .no-payments h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .no-payments p {
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        
        .browse-btn {
            padding: 12px 30px;
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
            gap: 10px;
        }
        
        .browse-btn:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
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
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .payment-details {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .payment-header {
                flex-direction: column;
                gap: 15px;
            }
            
            nav {
                gap: 8px;
            }
            
            .nav-link span {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .payment-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </a>
                <nav>
                    <a href="my-subscriptions.php" class="nav-link active">
                        <i class="fas fa-book-reader"></i>
                        <span>Payments</span>
                        <?php if($stats['total'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['total']; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="my-certificates.php" class="nav-link">
                        <i class="fas fa-certificate"></i>
                        <span>Certificates</span>
                        <?php if($totalCertificates > 0): ?>
                        <span class="nav-badge"><?php echo $totalCertificates; ?></span>
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
                <h1><i class="fas fa-credit-card"></i> My Payment History</h1>
                <p>Track and manage all your course payments</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <i class="fas fa-receipt"></i>
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total Payments</p>
                </div>
                
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
                
                <div class="stat-card approved">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $stats['approved'] ?? 0; ?></h3>
                    <p>Approved</p>
                </div>
                
                <div class="stat-card rejected">
                    <i class="fas fa-times-circle"></i>
                    <h3><?php echo $stats['rejected'] ?? 0; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All
                    <span class="badge"><?php echo $stats['total'] ?? 0; ?></span>
                </a>
                
                <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                    <span class="badge"><?php echo $stats['pending'] ?? 0; ?></span>
                </a>
                
                <a href="?status=approved" class="filter-tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Approved
                    <span class="badge"><?php echo $stats['approved'] ?? 0; ?></span>
                </a>
                
                <a href="?status=rejected" class="filter-tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Rejected
                    <span class="badge"><?php echo $stats['rejected'] ?? 0; ?></span>
                </a>
            </div>
            
            <!-- Payments Grid -->
            <div class="payments-grid">
                <?php if (empty($payments)): ?>
                <div class="no-payments">
                    <i class="fas fa-credit-card"></i>
                    <h3>No payments found</h3>
                    <p>You haven't made any payments yet. Browse our courses and start learning!</p>
                    <a href="courses.php" class="browse-btn">
                        <i class="fas fa-search"></i> Browse Courses
                    </a>
                </div>
                <?php else: ?>
                <?php foreach($payments as $payment): ?>
                <div class="payment-card <?php echo $payment['status']; ?>">
                    <div class="payment-header">
                        <div class="course-info">
                            <h3><?php echo htmlspecialchars($payment['course_title']); ?></h3>
                            <p>Registration ID: <?php echo htmlspecialchars($payment['registration_id']); ?></p>
                        </div>
                        <div class="payment-status <?php echo $payment['status']; ?>">
                            <?php echo ucfirst($payment['status']); ?>
                        </div>
                    </div>
                    
                    <div class="payment-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value amount">da <?php echo number_format($payment['amount'], 2); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Payment Date</div>
                            <div class="detail-value"><?php echo $payment['formatted_payment_date']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Method</div>
                            <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Submitted</div>
                            <div class="detail-value"><?php echo $payment['formatted_date']; ?></div>
                        </div>
                    </div>
                    
                    <?php if ($payment['transaction_id']): ?>
                    <div class="detail-item" style="text-align: left; margin-bottom: 15px;">
                        <div class="detail-label">Transaction ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payment['notes']): ?>
                    <div class="detail-item" style="text-align: left; margin-bottom: 15px;">
                        <div class="detail-label">Notes</div>
                        <div class="detail-value" style="font-size: 14px; font-weight: normal;"><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="payment-actions">
                        <?php if ($payment['status'] == 'pending'): ?>
                        <button class="action-btn upload" onclick="uploadAdditionalProof(<?php echo $payment['id']; ?>)">
                            <i class="fas fa-upload"></i> Upload Proof
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($payment['status'] == 'approved'): ?>
                        <button class="action-btn view" onclick="accessCourse(<?php echo $payment['course_id']; ?>)">
                            <i class="fas fa-play-circle"></i> Access Course
                        </button>
                        <?php endif; ?>
                        
                        <button class="action-btn view" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
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
        function uploadAdditionalProof(paymentId) {
            alert('This feature would open a file upload dialog for payment ID: ' + paymentId);
            // In production: Open modal with file upload form
        }
        
        function accessCourse(courseId) {
            alert('Redirecting to course ID: ' + courseId);
            // In production: window.location.href = 'course-player.php?id=' + courseId;
        }
        
        function viewPaymentDetails(paymentId) {
            alert('Showing details for payment ID: ' + paymentId);
            // In production: Open modal with full payment details
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