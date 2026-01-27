<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get current user info
$userQuery = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$userResult = $db->query($userQuery);
$currentUser = $userResult->fetch(PDO::FETCH_ASSOC);

// Get counts for sidebar
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$result = $db->query($query);   
$totalStudents = $result->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM courses";
$result = $db->query($query);
$activeCourses = $result->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM users WHERE role = 'formateur'";
$result = $db->query($query);
$totalFormateurs = $result->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM registrations WHERE status = 'pending'";
$result = $db->query($query);
$totalpending = $result->fetch()['total'];

// Get payment statistics
$statsQuery = "SELECT 
    COUNT(*) as total_payments,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
FROM payments";
$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch(PDO::FETCH_ASSOC);

// Handle search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$method_filter = isset($_GET['method_filter']) ? $_GET['method_filter'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT p.*, 
          u.first_name, u.last_name, u.email,
          c.title as course_title,
          r.registration_date,
          rec.first_name as recorded_by_name
          FROM payments p
          LEFT JOIN users u ON p.user_id = u.id
          LEFT JOIN courses c ON p.course_id = c.id
          LEFT JOIN registrations r ON p.registration_id = r.id
          LEFT JOIN users rec ON p.recorded_by = rec.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' 
                OR c.title LIKE '%$search%' OR p.transaction_id LIKE '%$search%')";
}

if ($status_filter !== 'all') {
    $query .= " AND p.status = '$status_filter'";
}

if ($method_filter !== 'all') {
    $query .= " AND p.payment_method = '$method_filter'";
}

if (!empty($date_from)) {
    $query .= " AND DATE(p.payment_date) >= '$date_from'";
}

if (!empty($date_to)) {
    $query .= " AND DATE(p.payment_date) <= '$date_to'";
}

$query .= " ORDER BY p.payment_date DESC";

$result = $db->query($query);
$payments = $result->fetchAll(PDO::FETCH_ASSOC);

// Handle update payment status
if (isset($_GET['update_status']) && isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];
    $new_status = $_GET['update_status'];
    
    $updateQuery = "UPDATE payments SET status = :status WHERE id = :id";
    $stmt = $db->prepare($updateQuery);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $payment_id);
    $stmt->execute();
    
    header("Location: payments.php?message=Payment+status+updated+successfully");
    exit;
}

// Handle delete payment
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM payments WHERE id = $delete_id";
    $db->query($query);
    header("Location: payments.php?message=Payment+record+deleted+successfully");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Master Edu</title>
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
            --danger: #ff4757;
            --warning: #ffa502;
            --info: #2ed573;
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
            font-family: 'Lusitana', serif;
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
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 32px;
        }
        
       
        .stats-row {
            display: grid;
        grid-template-columns: repeat(auto-fit, 1fr);
grid-template-rows: auto   ;

            gap: 20px;
         
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);

        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-icon.success {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .stat-icon.warning {
            background: rgba(255, 165, 2, 0.2);
            color: var(--warning);
        }
        
        .stat-icon.danger {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger);
        }
        
        .stat-icon.info {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .stat-count {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 8px;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(157, 255, 87, 0.2);
            border: 1px solid var(--btn-bg);
            color: var(--btn-bg);
        }
        
        /* Filters Section */
        .filters-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-input, .filter-select {
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Lusitana', serif;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Lusitana', serif;
        }
        
        .btn-primary {
            background-color: var(--btn-bg);
            color: var(--btn-text);
        }
        
        .btn-primary:hover {
            background-color: var(--btn-hover);
        }
        
        .btn-secondary {
            background-color: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--bg-tertiary);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--bg-secondary);
            padding-bottom: 10px;
        }
        
        .tab {
            font-size: 16px;
            color: var(--text-secondary);
            text-decoration: none;
            padding: 10px 5px;
            transition: color 0.3s;
            border-bottom: 2px solid transparent;
            margin-bottom: -12px;
        }
        
        .tab.active {
            color: var(--btn-bg);
            border-bottom-color: var(--btn-bg);
        }
        
        /* Payments Table */
        .table-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: var(--bg-secondary);
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }
        
        tr:hover {
            background: var(--bg-card-hover);
        }
        
        .student-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .student-name {
            font-weight: 600;
        }
        
        .student-email {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-completed {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .status-pending {
            background: rgba(255, 165, 2, 0.2);
            color: var(--warning);
        }
        
        .status-failed {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger);
        }
        
        .status-refunded {
            background: rgba(100, 100, 100, 0.2);
            color: #999;
        }
        
        .method-badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--btn-bg);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-view {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .btn-delete {
            background: rgba(255, 87, 87, 0.2);
            color: #ff5757;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        /* Status dropdown */
        .status-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .status-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--bg-card);
            min-width: 140px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .status-dropdown-content a {
            color: var(--text-primary);
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 12px;
        }
        
        .status-dropdown:hover .status-dropdown-content {
            display: block;
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
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: scroll;
            }
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
            <a href="dashboard.php" class="menu-item">
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
            <a href="payments.php" class="menu-item active">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>
            <a href="ratings.php" class="menu-item">
                <i class="fas fa-star"></i>
                <span>Ratings</span>
            </a>
            <a href="events.php" class="menu-item">
                <i class="fas fa-calendar"></i>
                <span>Events</span>
            </a>
           
            <a href="users.php" class="menu-item">
                <i class="fas fa-user-cog"></i>
                <span>User Management</span>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Payment Management</h1>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">da <?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-count"><?php echo $stats['completed_count']; ?> completed payments</div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">da <?php echo number_format($stats['pending_amount'], 2); ?></div>
                        <div class="stat-label">Pending Amount</div>
                        <div class="stat-count"><?php echo $stats['pending_count']; ?> pending payments</div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value">da <?php echo number_format($stats['failed_amount'], 2); ?></div>
                        <div class="stat-label">Failed Amount</div>
                        <div class="stat-count"><?php echo $stats['failed_count']; ?> failed payments</div>
                    </div>
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total_payments']; ?></div>
                        <div class="stat-label">Total Transactions</div>
                        <div class="stat-count">All payment records</div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Student, course, transaction ID..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status_filter" class="filter-select">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Payment Method</label>
                        <select name="method_filter" class="filter-select">
                            <option value="all" <?php echo $method_filter == 'all' ? 'selected' : ''; ?>>All Methods</option>
                            <option value="credit_card" <?php echo $method_filter == 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                            <option value="debit_card" <?php echo $method_filter == 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                            <option value="bank_transfer" <?php echo $method_filter == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="cash" <?php echo $method_filter == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="paypal" <?php echo $method_filter == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" name="date_from" class="filter-input" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" name="date_to" class="filter-input" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <?php if (!empty($search) || $status_filter != 'all' || $method_filter != 'all' || !empty($date_from) || !empty($date_to)): ?>
                        <a href="payments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="?status_filter=all" class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Payments</a>
            <a href="?status_filter=completed" class="tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?status_filter=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status_filter=failed" class="tab <?php echo $status_filter == 'failed' ? 'active' : ''; ?>">Failed</a>
        </div>
        
        <!-- Payments Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach($payments as $payment): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></strong>
                            </td>
                            <td>
                                <div class="student-info">
                                    <span class="student-name">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </span>
                                    <span class="student-email"><?php echo htmlspecialchars($payment['email']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($payment['course_title'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="amount">da <?php echo number_format($payment['amount'], 2); ?></span>
                            </td>
                            <td>
                                <span class="method-badge">
                                    <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="status-dropdown">
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                    <div class="status-dropdown-content">
                                        <a href="?update_status=completed&payment_id=<?php echo $payment['id']; ?>">
                                            Mark as Completed
                                        </a>
                                        <a href="?update_status=pending&payment_id=<?php echo $payment['id']; ?>">
                                            Mark as Pending
                                        </a>
                                        <a href="?update_status=failed&payment_id=<?php echo $payment['id']; ?>">
                                            Mark as Failed
                                        </a>
                                        <a href="?update_status=refunded&payment_id=<?php echo $payment['id']; ?>">
                                            Mark as Refunded
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view-payment.php?id=<?php echo $payment['id']; ?>" 
                                       class="btn-action btn-view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $payment['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       title="Delete"
                                       onclick="return confirm('Delete this payment record?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                                <i class="fas fa-money-bill-wave" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                                <h3>No payment records found</h3>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
</body>
</html>