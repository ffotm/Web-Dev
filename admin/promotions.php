<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();


$userQuery = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$userResult = $db->query($userQuery);
$currentUser = $userResult->fetch(PDO::FETCH_ASSOC);


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


$statsQuery = "SELECT 
    COUNT(*) as total_promotions,
    COUNT(CASE WHEN is_active = 1 AND CURDATE() BETWEEN start_date AND end_date THEN 1 END) as active_promotions,
    COUNT(CASE WHEN promotion_type = 'discount' THEN 1 END) as discount_count,
    COUNT(CASE WHEN promotion_type = 'offer' THEN 1 END) as offer_count,
    COUNT(CASE WHEN promotion_type = 'advertisement' THEN 1 END) as ad_count,
    SUM(usage_count) as total_usage
FROM promotions";
$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch(PDO::FETCH_ASSOC);


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM promotions WHERE id = $delete_id";
    $db->query($query);
    header("Location: promotions.php?message=Promotion+deleted+successfully");
    exit;
}


if (isset($_GET['toggle_id'])) {
    $toggle_id = $_GET['toggle_id'];
    $query = "UPDATE promotions SET is_active = NOT is_active WHERE id = $toggle_id";
    $db->query($query);
    header("Location: promotions.php?message=Promotion+status+updated");
    exit;
}


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promotion'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $promotion_type = $_POST['promotion_type'];
    $discount_percentage = $_POST['discount_percentage'] ?? 0;
    $discount_amount = $_POST['discount_amount'] ?? 0;
    $code = !empty($_POST['code']) ? $_POST['code'] : 'NULL';
    $target_audience = $_POST['target_audience'];
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : 'NULL';
    $image_url = $_POST['image_url'] ?? '';
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : 'NULL';
    $terms_conditions = $_POST['terms_conditions'] ?? '';
    $created_by = $_SESSION['user_id'];
    
    // Handle NULL values for string fields
    if ($code == 'NULL') {
        $code_value = 'NULL';
    } else {
        $code_value = "'$code'";
    }
    
    if ($image_url == '') {
        $image_url_value = 'NULL';
    } else {
        $image_url_value = "'$image_url'";
    }
    
    if ($terms_conditions == '') {
        $terms_value = 'NULL';
    } else {
        $terms_value = "'$terms_conditions'";
    }
    
    // EXTREMELY SIMPLE INSERT - NO ESCAPING
    $insertQuery = "INSERT INTO promotions 
                   (title, description, promotion_type, discount_percentage, discount_amount, 
                    code, target_audience, course_id, image_url, start_date, end_date, 
                    usage_limit, terms_conditions, created_by) 
                   VALUES ('$title', '$description', '$promotion_type', $discount_percentage, $discount_amount, 
                           $code_value, '$target_audience', $course_id, $image_url_value, '$start_date', '$end_date', 
                           $usage_limit, $terms_value, $created_by)";
    
    if ($db->query($insertQuery)) {
        $success = "Promotion created successfully!";
    } else {
        $error = "Failed to create promotion! Error: " . $db->errorInfo()[2];
    }
}

// Handle edit promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_promotion'])) {
    $promo_id = $_POST['promo_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount_percentage = $_POST['discount_percentage'] ?? 0;
    $discount_amount = $_POST['discount_amount'] ?? 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : 'NULL';
    $terms_conditions = $_POST['terms_conditions'] ?? '';
    
    // Handle NULL values for string fields
    if ($terms_conditions == '') {
        $terms_value = 'NULL';
    } else {
        $terms_value = "'$terms_conditions'";
    }
    
    // EXTREMELY SIMPLE UPDATE - NO ESCAPING
    $updateQuery = "UPDATE promotions SET 
                   title = '$title',
                   description = '$description',
                   discount_percentage = $discount_percentage,
                   discount_amount = $discount_amount,
                   start_date = '$start_date',
                   end_date = '$end_date',
                   usage_limit = $usage_limit,
                   terms_conditions = $terms_value
                   WHERE id = $promo_id";
    
    if ($db->query($updateQuery)) {
        $success = "Promotion updated successfully!";
    } else {
        $error = "Failed to update promotion! Error: " . $db->errorInfo()[2];
    }
}

// Get all promotions with filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : 'all';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

$query = "SELECT p.*, 
          c.title as course_title,
          u.first_name as creator_name
          FROM promotions p
          LEFT JOIN courses c ON p.course_id = c.id
          LEFT JOIN users u ON p.created_by = u.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%' OR p.code LIKE '%$search%')";
}

if ($type_filter !== 'all') {
    $query .= " AND p.promotion_type = '$type_filter'";
}

if ($status_filter === 'active') {
    $query .= " AND p.is_active = 1 AND CURDATE() BETWEEN p.start_date AND p.end_date";
} elseif ($status_filter === 'inactive') {
    $query .= " AND p.is_active = 0";
} elseif ($status_filter === 'expired') {
    $query .= " AND p.end_date < CURDATE()";
} elseif ($status_filter === 'upcoming') {
    $query .= " AND p.start_date > CURDATE()";
}

$query .= " ORDER BY p.display_priority DESC, p.created_at DESC";

$result = $db->query($query);
$promotions = $result->fetchAll(PDO::FETCH_ASSOC);

// Get courses for dropdown
$courses_query = "SELECT id, title FROM courses ORDER BY title";
$courses_result = $db->query($courses_query);
$courses = $courses_result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions & Offers - Master Edu</title>
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
        
        .alert-error {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        /* Add Form Section */
        .add-form-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .add-form-section.collapsed {
            padding: 16px 24px;
        }
        
        .form-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .form-content {
            margin-top: 20px;
        }
        
        .form-content.hidden {
            display: none;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .form-input, .form-select, .form-textarea {
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Lusitana', serif;
        }
        
        .form-textarea {
            min-height: 80px;
            resize: vertical;
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
        
        /* Promotions Grid */
        .promotions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        
        .promotion-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .promotion-card.hidden {
            display: none;
        }
        
        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
            margin-bottom: 16px;
        }
        
        .type-discount {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .type-offer {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .type-advertisement {
            background: rgba(255, 165, 2, 0.2);
            color: var(--warning);
        }
        
        .type-announcement {
            background: rgba(138, 43, 226, 0.2);
            color: #ba68c8;
        }
        
        .promotion-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .promotion-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }
        
        .promotion-actions {
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
        
        .btn-toggle {
            background-color: var(--info);
            color: white;
        }
        
        .promotion-description {
            color: var(--text-secondary);
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .promotion-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 11px;
            color: var(--text-secondary);
        }
        
        .detail-value {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .promotion-code {
            background: var(--bg-secondary);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 2px;
            color: var(--btn-bg);
            margin-bottom: 15px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-dot.active {
            background: var(--info);
        }
        
        .status-dot.inactive {
            background: var(--danger);
        }
        
        /* Edit form */
        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            
            .form-grid, .form-row, .promotion-details, .filters-grid {
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
            <a href="ratings.php" class="menu-item">
                <i class="fas fa-star"></i>
                <span>Ratings</span>
            </a>
            <a href="promotions.php" class="menu-item active">
                <i class="fas fa-tags"></i>
                <span>Promotions</span>
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
            <h1>Promotions & Offers</h1>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active_promotions']; ?></div>
                <div class="stat-label">Active Promotions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['discount_count']; ?></div>
                <div class="stat-label">Discount Codes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['offer_count']; ?></div>
                <div class="stat-label">Special Offers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_usage']; ?></div>
                <div class="stat-label">Total Usage</div>
            </div>
        </div>
        
        <!-- Add Promotion Form -->
        <div class="add-form-section" id="addFormSection">
            <div class="form-toggle" id="formToggle">
                <h2><i class="fas fa-plus-circle"></i> Create New Promotion</h2>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="form-content hidden" id="formContent">
                <form method="POST" action="">
                    <input type="hidden" name="add_promotion" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="promotion_type">Type *</label>
                            <select id="promotion_type" name="promotion_type" class="form-select" required>
                                <option value="discount">Discount</option>
                                <option value="offer">Special Offer</option>
                                <option value="advertisement">Advertisement</option>
                                <option value="announcement">Announcement</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-textarea"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount_percentage">Discount % (optional)</label>
                            <input type="number" id="discount_percentage" name="discount_percentage" 
                                   class="form-input" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="discount_amount">Discount Amount (optional)</label>
                            <input type="number" id="discount_amount" name="discount_amount" 
                                   class="form-input" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="code">Promo Code (optional)</label>
                            <input type="text" id="code" name="code" class="form-input" 
                                   placeholder="e.g., SUMMER2025">
                        </div>
                        
                        <div class="form-group">
                            <label for="target_audience">Target Audience</label>
                            <select id="target_audience" name="target_audience" class="form-select">
                                <option value="all">All Users</option>
                                <option value="students">Students Only</option>
                                <option value="new_users">New Users</option>
                                <option value="specific_course">Specific Course</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_id">Course (if specific)</label>
                            <select id="course_id" name="course_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="usage_limit">Usage Limit (optional)</label>
                            <input type="number" id="usage_limit" name="usage_limit" class="form-input" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date *</label>
                            <input type="date" id="end_date" name="end_date" class="form-input" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="image_url">Image URL (optional)</label>
                            <input type="url" id="image_url" name="image_url" class="form-input" 
                                   placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="terms_conditions">Terms & Conditions</label>
                            <textarea id="terms_conditions" name="terms_conditions" class="form-textarea"></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Create Promotion
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Search by title, description, or code..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Type</label>
                        <select name="type_filter" class="filter-select">
                            <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="discount" <?php echo $type_filter == 'discount' ? 'selected' : ''; ?>>Discounts</option>
                            <option value="offer" <?php echo $type_filter == 'offer' ? 'selected' : ''; ?>>Offers</option>
                            <option value="advertisement" <?php echo $type_filter == 'advertisement' ? 'selected' : ''; ?>>Advertisements</option>
                            <option value="announcement" <?php echo $type_filter == 'announcement' ? 'selected' : ''; ?>>Announcements</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select name="status_filter" class="filter-select">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if (!empty($search) || $type_filter != 'all' || $status_filter != 'all'): ?>
                        <a href="promotions.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="?status_filter=all" class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Promotions</a>
            <a href="?status_filter=active" class="tab <?php echo $status_filter == 'active' ? 'active' : ''; ?>">Active</a>
            <a href="?status_filter=upcoming" class="tab <?php echo $status_filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
            <a href="?status_filter=expired" class="tab <?php echo $status_filter == 'expired' ? 'active' : ''; ?>">Expired</a>
        </div>
        
        <!-- Promotions Grid -->
        <div class="promotions-grid">
            <?php if (count($promotions) > 0): ?>
                <?php foreach ($promotions as $promo): ?>
                    <?php
                    $editing = isset($_GET['edit']) && $_GET['edit'] == $promo['id'];
                    $is_active = $promo['is_active'] == 1;
                    $current_date = date('Y-m-d');
                    $status = 'inactive';
                    if ($is_active) {
                        if ($current_date < $promo['start_date']) {
                            $status = 'upcoming';
                        } elseif ($current_date > $promo['end_date']) {
                            $status = 'expired';
                        } else {
                            $status = 'active';
                        }
                    }
                    ?>
                    
                    <div class="promotion-card">
                        <!-- Type Badge -->
                        <div class="type-badge type-<?php echo $promo['promotion_type']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $promo['promotion_type'])); ?>
                        </div>
                        
                        <!-- Promotion Header -->
                        <div class="promotion-header">
                            <div class="promotion-title">
                                <?php if ($editing): ?>
                                    <input type="text" name="title" form="edit_form_<?php echo $promo['id']; ?>" 
                                           value="<?php echo htmlspecialchars($promo['title']); ?>" 
                                           class="form-input" style="width: 100%;" required>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($promo['title']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="promotion-actions">
                                <?php if ($editing): ?>
                                    <button type="submit" form="edit_form_<?php echo $promo['id']; ?>" 
                                            class="action-btn btn-edit" title="Save">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <a href="promotions.php" class="action-btn btn-delete" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?edit=<?php echo $promo['id']; ?>" 
                                       class="action-btn btn-edit" 
                                       title="Edit Promotion">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="?toggle_id=<?php echo $promo['id']; ?>" 
                                       class="action-btn btn-toggle" 
                                       title="<?php echo $is_active ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-<?php echo $is_active ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                    </a>
                                    
                                    <a href="?delete_id=<?php echo $promo['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       title="Delete Promotion"
                                       onclick="return confirm('Delete this promotion?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($editing): ?>
                            <!-- Edit Form -->
                            <form method="POST" action="" class="edit-form" id="edit_form_<?php echo $promo['id']; ?>">
                                <input type="hidden" name="edit_promotion" value="1">
                                <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-textarea"><?php echo htmlspecialchars($promo['description']); ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Discount %</label>
                                        <input type="number" name="discount_percentage" class="form-input" 
                                               value="<?php echo $promo['discount_percentage']; ?>" 
                                               step="0.01" min="0" max="100">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Discount Amount</label>
                                        <input type="number" name="discount_amount" class="form-input" 
                                               value="<?php echo $promo['discount_amount']; ?>" 
                                               step="0.01" min="0">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-input" 
                                               value="<?php echo $promo['start_date']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-input" 
                                               value="<?php echo $promo['end_date']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Usage Limit</label>
                                        <input type="number" name="usage_limit" class="form-input" 
                                               value="<?php echo $promo['usage_limit']; ?>" min="1">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Terms & Conditions</label>
                                    <textarea name="terms_conditions" class="form-textarea"><?php echo htmlspecialchars($promo['terms_conditions']); ?></textarea>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="promotion-description">
                                <?php echo nl2br(htmlspecialchars($promo['description'])); ?>
                            </div>
                            
                            <?php if (!empty($promo['code'])): ?>
                            <div class="promotion-code">
                                <?php echo htmlspecialchars($promo['code']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="promotion-details">
                                <?php if ($promo['discount_percentage'] > 0 || $promo['discount_amount'] > 0): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Discount</span>
                                    <span class="detail-value">
                                        <?php 
                                        if ($promo['discount_percentage'] > 0) {
                                            echo $promo['discount_percentage'] . '%';
                                        }
                                        if ($promo['discount_amount'] > 0) {
                                            echo ' da ' . number_format($promo['discount_amount'], 2);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Valid Period</span>
                                    <span class="detail-value">
                                        <?php echo date('M d', strtotime($promo['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($promo['end_date'])); ?>
                                    </span>
                                </div>
                                
                                <?php if ($promo['usage_limit']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Usage</span>
                                    <span class="detail-value">
                                        <?php echo $promo['usage_count']; ?> / <?php echo $promo['usage_limit']; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Target</span>
                                    <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $promo['target_audience'])); ?></span>
                                </div>
                                
                                <?php if ($promo['course_title']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Course</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($promo['course_title']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="status-indicator">
                                <span class="status-dot <?php echo $status == 'active' ? 'active' : 'inactive'; ?>"></span>
                                <span><?php echo ucfirst($status); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-tags" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No promotions found</h3>
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