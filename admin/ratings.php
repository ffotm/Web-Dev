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

// Get rating statistics
$statsQuery = "SELECT 
    COUNT(*) as total_ratings,
    AVG(stars) as avg_rating,
    COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_count,
    COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_count,
    COUNT(CASE WHEN stars = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN stars = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN stars = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN stars = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN stars = 1 THEN 1 END) as one_star
FROM ratings";
$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch(PDO::FETCH_ASSOC);

// Handle approve rating
if (isset($_GET['approve_id'])) {
    $approve_id = $_GET['approve_id'];
    $approved_by = $_SESSION['user_id'];
    $query = "UPDATE rating SET is_approved = 1, approved_by = $approved_by WHERE id = $approve_id";
    $db->query($query);
    header("Location: ratings.php?message=Rating+approved+successfully");
    exit;
}

// Handle delete rating
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM ratings WHERE id = $delete_id";
    $db->query($query);
    header("Location: ratings.php?message=Rating+deleted+successfully");
    exit;
}

// Handle edit rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_rating'])) {
    $rating_id = $_POST['rating_id'];
    $rating_cmnt = $_POST['rating_cmnt'];
    $stars = $_POST['stars'];
    
    $updateQuery = "UPDATE ratings SET 
                   rating_cmnt = :rating_cmnt,
                   stars = :stars
                   WHERE id = :rating_id";
    
    $stmt = $db->prepare($updateQuery);
    $stmt->bindParam(':rating_cmnt', $rating_cmnt);
    $stmt->bindParam(':stars', $stars);
    $stmt->bindParam(':rating_id', $rating_id);
    
    if ($stmt->execute()) {
        header("Location: ratings.php?message=Rating+updated+successfully");
        exit;
    }
}

// Get all ratings with filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$stars_filter = isset($_GET['stars_filter']) ? $_GET['stars_filter'] : 'all';

$query = "SELECT r.*, 
          u.first_name, u.last_name, u.email,
          c.title as course_title,
          a.first_name as approved_by_name
          FROM ratings r
          LEFT JOIN users u ON r.user_id = u.id
          LEFT JOIN courses c ON r.course_id = c.id
          LEFT JOIN users a ON r.approved_by = a.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' 
                OR c.title LIKE '%$search%' OR r.rating_cmnt LIKE '%$search%')";
}

if ($status_filter === 'approved') {
    $query .= " AND r.is_approved = 1";
} elseif ($status_filter === 'pending') {
    $query .= " AND r.is_approved = 0";
}

if ($stars_filter !== 'all') {
    $query .= " AND r.stars = $stars_filter";
}

$query .= " ORDER BY r.created_at DESC";

$result = $db->query($query);
$ratings = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings Management - Master Edu</title>
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
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--btn-bg);
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }
        
        .star-distribution {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12px;
        }
        
        .star-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .star-row .stars {
            color: #ffa502;
            width: 80px;
        }
        
        .star-bar {
            flex: 1;
            height: 6px;
            background: var(--bg-secondary);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .star-bar-fill {
            height: 100%;
            background: #ffa502;
            transition: width 0.3s;
        }
        
        .star-count {
            color: var(--text-secondary);
            min-width: 30px;
            text-align: right;
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
        
        /* Filters */
        .filters-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
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
        
        /* Ratings Grid */
        .ratings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .rating-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .rating-card:hover {
            border-color: var(--btn-bg);
        }
        
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .rating-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .rating-avatar {
            width: 50px;
            height: 50px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .rating-user-details h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .rating-course {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .rating-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 12px;
        }
        
        .btn-edit {
            background-color: var(--btn-bg);
            color: var(--btn-text);
        }
        
        .btn-delete {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-approve {
            background-color: var(--info);
            color: white;
        }
        
        .rating-stars {
            display: flex;
            gap: 4px;
            margin-bottom: 12px;
            font-size: 18px;
        }
        
        .star {
            color: #ffa502;
        }
        
        .star.empty {
            color: var(--bg-secondary);
        }
        
        .rating-comment {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-primary);
            margin-bottom: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
        }
        
        .rating-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .status-pending {
            background: rgba(255, 165, 2, 0.2);
            color: var(--warning);
        }
        
        /* Edit Form */
        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .form-input, .form-select, .form-textarea {
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Lusitana', serif;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .star-selector {
            display: flex;
            gap: 8px;
            font-size: 24px;
        }
        
        .star-selector input[type="radio"] {
            display: none;
        }
        
        .star-selector label {
            cursor: pointer;
            color: var(--bg-secondary);
            transition: color 0.2s;
        }
        
        .star-selector input[type="radio"]:checked ~ label,
        .star-selector label:hover,
        .star-selector label:hover ~ label {
            color: #ffa502;
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
            <a href="payments.php" class="menu-item">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>
            <a href="ratings.php" class="menu-item active">
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
            </a>
            
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Ratings & Reviews</h1>
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
                <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?> <i class="fas fa-star" style="font-size: 24px; color: #ffa502;"></i></div>
                <div class="stat-label">Average Rating</div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    Based on <?php echo $stats['total_ratings']; ?> reviews
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['approved_count']; ?></div>
                <div class="stat-label">Approved Reviews</div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    <?php echo $stats['pending_count']; ?> pending approval
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Rating Distribution</div>
                <div class="star-distribution">
                    <div class="star-row">
                        <span class="stars">5 <i class="fas fa-star"></i></span>
                        <div class="star-bar">
                            <div class="star-bar-fill" style="width: <?php echo $stats['total_ratings'] > 0 ? ($stats['five_star'] / $stats['total_ratings'] * 100) : 0; ?>%"></div>
                        </div>
                        <span class="star-count"><?php echo $stats['five_star']; ?></span>
                    </div>
                    <div class="star-row">
                        <span class="stars">4 <i class="fas fa-star"></i></span>
                        <div class="star-bar">
                            <div class="star-bar-fill" style="width: <?php echo $stats['total_ratings'] > 0 ? ($stats['four_star'] / $stats['total_ratings'] * 100) : 0; ?>%"></div>
                        </div>
                        <span class="star-count"><?php echo $stats['four_star']; ?></span>
                    </div>
                    <div class="star-row">
                        <span class="stars">3 <i class="fas fa-star"></i></span>
                        <div class="star-bar">
                            <div class="star-bar-fill" style="width: <?php echo $stats['total_ratings'] > 0 ? ($stats['three_star'] / $stats['total_ratings'] * 100) : 0; ?>%"></div>
                        </div>
                        <span class="star-count"><?php echo $stats['three_star']; ?></span>
                    </div>
                    <div class="star-row">
                        <span class="stars">2 <i class="fas fa-star"></i></span>
                        <div class="star-bar">
                            <div class="star-bar-fill" style="width: <?php echo $stats['total_ratings'] > 0 ? ($stats['two_star'] / $stats['total_ratings'] * 100) : 0; ?>%"></div>
                        </div>
                        <span class="star-count"><?php echo $stats['two_star']; ?></span>
                    </div>
                    <div class="star-row">
                        <span class="stars">1 <i class="fas fa-star"></i></span>
                        <div class="star-bar">
                            <div class="star-bar-fill" style="width: <?php echo $stats['total_ratings'] > 0 ? ($stats['one_star'] / $stats['total_ratings'] * 100) : 0; ?>%"></div>
                        </div>
                        <span class="star-count"><?php echo $stats['one_star']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Search by student, course, or comment..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status_filter" class="filter-select">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Stars</label>
                        <select name="stars_filter" class="filter-select">
                            <option value="all" <?php echo $stars_filter == 'all' ? 'selected' : ''; ?>>All Ratings</option>
                            <option value="5" <?php echo $stars_filter == '5' ? 'selected' : ''; ?>>5 Stars</option>
                            <option value="4" <?php echo $stars_filter == '4' ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="3" <?php echo $stars_filter == '3' ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="2" <?php echo $stars_filter == '2' ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="1" <?php echo $stars_filter == '1' ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if (!empty($search) || $status_filter != 'all' || $stars_filter != 'all'): ?>
                        <a href="ratings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="?status_filter=all" class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Reviews</a>
            <a href="?status_filter=approved" class="tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status_filter=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending Approval</a>
        </div>
        
        <!-- Ratings Grid -->
        <div class="ratings-grid">
            <?php if (count($ratings) > 0): ?>
                <?php foreach ($ratings as $rating): ?>
                    <?php
                    $editing = isset($_GET['edit']) && $_GET['edit'] == $rating['id'];
                    $is_approved = $rating['is_approved'] == 1;
                    ?>
                    
                    <div class="rating-card">
                        <div class="rating-header">
                            <div class="rating-user-info">
                                <div class="rating-avatar">
                                    <?php echo strtoupper(substr($rating['first_name'], 0, 1)); ?>
                                </div>
                                <div class="rating-user-details">
                                    <h3><?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?></h3>
                                    <div class="rating-course"><?php echo htmlspecialchars($rating['course_title']); ?></div>
                                </div>
                            </div>
                            
                            <div class="rating-actions">
                                <?php if ($editing): ?>
                                    <button type="submit" form="edit_form_<?php echo $rating['id']; ?>" 
                                            class="action-btn btn-edit" title="Save">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <a href="ratings.php" class="action-btn btn-delete" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?edit=<?php echo $rating['id']; ?>" 
                                       class="action-btn btn-edit" 
                                       title="Edit Rating">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if (!$is_approved): ?>
                                    <a href="?approve_id=<?php echo $rating['id']; ?>" 
                                       class="action-btn btn-approve" 
                                       title="Approve Rating"
                                       onclick="return confirm('Approve this rating?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?delete_id=<?php echo $rating['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       title="Delete Rating"
                                       onclick="return confirm('Delete this rating?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($editing): ?>
                            <!-- Edit Form -->
                            <form method="POST" action="" class="edit-form" id="edit_form_<?php echo $rating['id']; ?>">
                                <input type="hidden" name="edit_rating" value="1">
                                <input type="hidden" name="rating_id" value="<?php echo $rating['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Rating</label>
                                    <div class="star-selector">
                                        <input type="radio" name="stars" value="5" id="star5_<?php echo $rating['id']; ?>" <?php echo $rating['stars'] == 5 ? 'checked' : ''; ?>>
                                        <label for="star5_<?php echo $rating['id']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" name="stars" value="4" id="star4_<?php echo $rating['id']; ?>" <?php echo $rating['stars'] == 4 ? 'checked' : ''; ?>>
                                        <label for="star4_<?php echo $rating['id']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" name="stars" value="3" id="star3_<?php echo $rating['id']; ?>" <?php echo $rating['stars'] == 3 ? 'checked' : ''; ?>>
                                        <label for="star3_<?php echo $rating['id']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" name="stars" value="2" id="star2_<?php echo $rating['id']; ?>" <?php echo $rating['stars'] == 2 ? 'checked' : ''; ?>>
                                        <label for="star2_<?php echo $rating['id']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" name="stars" value="1" id="star1_<?php echo $rating['id']; ?>" <?php echo $rating['stars'] == 1 ? 'checked' : ''; ?>>
                                        <label for="star1_<?php echo $rating['id']; ?>"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Comment</label>
                                    <textarea name="rating_cmnt" class="form-textarea" required><?php echo htmlspecialchars($rating['rating_cmnt']); ?></textarea>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="rating-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $rating['stars'] ? 'star' : 'star empty'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="rating-comment">
                                <?php echo nl2br(htmlspecialchars($rating['rating_cmnt'])); ?>
                            </div>
                            
                            <div class="rating-footer">
                                <span><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></span>
                                <span class="status-badge status-<?php echo $is_approved ? 'approved' : 'pending'; ?>">
                                    <?php echo $is_approved ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-star" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No ratings found</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
</body>
</html>