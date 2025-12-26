<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user role
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = $user_id";
$result = $db->query($query);
$user_role = $result->fetch()['role'];
$is_admin = $user_role === 'admin';

// Get statistics for sidebar
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$result = $db->query($query);   
$totalStudents = $result->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM registrations WHERE payment_status = 'pending'";
$result = $db->query($query);
$pendingApprovals = $result->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM courses";
$result = $db->query($query);
$activeCourses = $result->fetch()['total'];

$query = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
$result = $db->query($query);
$totalRevenue = $result->fetch()['total'] ?? 0;

$query = "SELECT COUNT(*) as total FROM users WHERE role = 'formateur'";
$result = $db->query($query);
$totalFormateurs = $result->fetch()['total'];

// Handle delete action
if (isset($_GET['delete_id']) && $is_admin) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM events WHERE id = $delete_id";
    $db->query($query);
    header("Location: events.php?message=Event+deleted+successfully");
    exit;
}

if (isset($_GET['change_status']) && $is_admin) {
    $event_id = $_GET['change_status'];
    $new_status = $_GET['status'];

    $query = "UPDATE events SET status = '$new_status' WHERE id = $event_id";
    $db->query($query);
    header("Location: events.php?message=Status+updated+successfully");
    exit;
}

if (isset($_POST['edit_event']) && $is_admin) {
    $event_id = $_POST['event_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $end_date = $_POST['end_date'];
    $location = $_POST['location'];
    $max_attendees = $_POST['max_attendees'];
    $event_type = $_POST['event_type'];
    
    $query = "UPDATE events SET 
              title = '$title',
              description = '$description',
              event_date = '$event_date',
              end_date = '$end_date',
              max_attendees = $max_attendees,
              location = '$location',
              event_type = '$event_type'
              WHERE id = $event_id";
    
    $db->query($query);
    header("Location: events.php?message=Event+updated+successfully");
    exit;
}

// Handle add event
if (isset($_POST['add_event']) && $is_admin) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $end_date = $_POST['end_date'];
    $location = $_POST['location'];
    $max_attendees = $_POST['max_attendees'];
    $event_type = $_POST['event_type'];
    $status = $_POST['status'] ?? 'upcoming';
    $created_by = $_SESSION['user_id'];
    
    $query = "INSERT INTO events (title, description, event_date, end_date, location, max_attendees, event_type, status, created_by, created_at) 
              VALUES ('$title', '$description', '$event_date', '$end_date', '$location', $max_attendees, '$event_type', '$status', $created_by, NOW())";
    
    $db->query($query);
    header("Location: events.php?message=Event+added+successfully");
    exit;
}

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : 'all';

// Build query for events
$query = "SELECT e.*, 
          u.first_name as creator_firstname,
          u.last_name as creator_lastname,
          CONCAT(u.first_name, ' ', u.last_name) as creator_name
          FROM events e
          LEFT JOIN users u ON e.created_by = u.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (e.title LIKE '%$search%' OR e.description LIKE '%$search%' OR e.location LIKE '%$search%')";
}

if ($status_filter !== 'all') {
    $query .= " AND e.status = '$status_filter'";
}

if ($type_filter !== 'all') {
    $query .= " AND e.event_type = '$type_filter'";
}

$query .= " ORDER BY e.event_date DESC";

$result = $db->query($query);
$events = $result->fetchAll();

// Get event types for filter
$event_types_query = "SELECT DISTINCT event_type FROM events WHERE event_type IS NOT NULL";
$event_types_result = $db->query($event_types_query);
$event_types = $event_types_result->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">     
    <title>Event Management - Master Edu</title>
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

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
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

        /* Search Bar */
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            background-color: var(--bg-card);
            border: 1px solid var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-select {
            padding: 12px 20px;
            background-color: var(--bg-card);
            border: 1px solid var(--bg-tertiary);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            min-width: 150px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--bg-secondary);
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .tab {
            font-size: 16px;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 10px 5px;
            transition: color 0.3s;
            border-bottom: 2px solid transparent;
            margin-bottom: -12px;
        }

        .tab.active {
            color: var(--btn-bg);
            border-bottom-color: var(--btn-bg);
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .event-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(157, 255, 87, 0.1);
            position: relative;
        }

        .event-card.hidden {
            display: none;
        }

        /* Status badge */
        .event-status-badge {
            position: relative;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
            margin-bottom: 20px;
        }

        .status-upcoming {
            background-color: var(--warning);
            color: #212529;
        }

        .status-active {
            background-color: var(--btn-bg);
            color: var(--btn-text);
        }

        .status-completed {
            background-color: #78909c;
            color: white;
        }

        .status-cancelled {
            background-color: var(--danger);
            color: white;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .event-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .event-type-badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .event-actions {
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

        .btn-status {
            background-color: var(--info);
            color: white;
        }

        .event-description {
            color: var(--text-secondary);
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* Edit form */
        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-input, .form-textarea, .form-select {
            padding: 10px;
            background-color: var(--bg-tertiary);
            border: 1px solid rgba(157, 255, 87, 0.2);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 14px;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
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
            min-width: 150px;
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
            font-size: 13px;
        }

        .status-dropdown:hover .status-dropdown-content {
            display: block;
        }

        /* Message alert */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: rgba(46, 213, 115, 0.2);
            border: 1px solid var(--info);
            color: var(--info);
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

        /* Progress bar for attendees */
        .attendees-progress {
            margin-top: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .progress-bar {
            height: 6px;
            background-color: var(--bg-tertiary);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--btn-bg);
            border-radius: 3px;
            transition: width 0.3s ease;
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
            <a href="events.php" class="menu-item active">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
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
    
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Event Management</h1>
            <div class="action-buttons">
                <?php if ($is_admin): ?>
                    <button onclick="showAddEventModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Event
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <form method="GET" action="" class="search-bar">
            <div class="search-box">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search events by title, description, or location..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <select name="status_filter" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            
            <select name="type_filter" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                <?php foreach ($event_types as $type): ?>
                    <?php if (!empty($type['event_type'])): ?>
                        <option value="<?php echo $type['event_type']; ?>" <?php echo $type_filter == $type['event_type'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type['event_type']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search) || $status_filter != 'all' || $type_filter != 'all'): ?>
                <a href="events.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            <?php endif; ?>
        </form>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="filterEvents('all')">All Events</div>
            <div class="tab" onclick="filterEvents('upcoming')">Upcoming</div>
            <div class="tab" onclick="filterEvents('active')">Active</div>
            <div class="tab" onclick="filterEvents('completed')">Completed</div>
        </div>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <?php
                    $event_date = date('d/m/Y', strtotime($event['event_date']));
                    $end_date = date('d/m/Y', strtotime($event['end_date']));
                    $status = strtolower($event['status']);
                    $editing = isset($_GET['edit']) && $_GET['edit'] == $event['id'];
                    
                    ?>
                    
                    <div class="event-card" data-status="<?php echo $status; ?>">
                        <!-- Status Badge -->
                        <div class="event-status-badge status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </div>

                        <!-- Event Header with Actions -->
                        <div class="event-header">
                            <div class="event-title">
                                <?php if ($editing): ?>
                                    <input type="text" name="title" form="edit_form_<?php echo $event['id']; ?>" 
                                           value="<?php echo htmlspecialchars($event['title']); ?>" 
                                           class="form-input" style="width: 100%;" required>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                    <?php if (!empty($event['event_type'])): ?>
                                        <span class="event-type-badge"><?php echo ucfirst($event['event_type']); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_admin): ?>
                                <div class="event-actions">
                                    <?php if ($editing): ?>
                                        <!-- Save and Cancel buttons -->
                                        <button type="submit" form="edit_form_<?php echo $event['id']; ?>" 
                                                class="action-btn btn-edit" title="Save">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="events.php" class="action-btn btn-status" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php else: ?>
                                        <!-- Edit button -->
                                        <a href="?edit=<?php echo $event['id']; ?>" 
                                           class="action-btn btn-edit" 
                                           title="Edit Event">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Status Dropdown -->
                                        <div class="status-dropdown">
                                            <button class="action-btn btn-status" title="Change Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <div class="status-dropdown-content">
                                                <a href="?change_status=<?php echo $event['id']; ?>&status=upcoming">
                                                    Set as Upcoming
                                                </a>
                                                <a href="?change_status=<?php echo $event['id']; ?>&status=active">
                                                    Set as Active
                                                </a>
                                                <a href="?change_status=<?php echo $event['id']; ?>&status=completed">
                                                    Set as Completed
                                                </a>
                                                <a href="?change_status=<?php echo $event['id']; ?>&status=cancelled">
                                                    Set as Cancelled
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Button -->
                                        <a href="?delete_id=<?php echo $event['id']; ?>" 
                                           class="action-btn btn-delete" 
                                           title="Delete Event"
                                           onclick="return confirm('Delete this event?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($editing): ?>
                            <!-- Edit Form -->
                            <form method="POST" action="" class="edit-form" id="edit_form_<?php echo $event['id']; ?>">
                                <input type="hidden" name="edit_event" value="1">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-textarea" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="datetime-local" name="event_date" class="form-input" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="datetime-local" name="end_date" class="form-input" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($event['end_date'])); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" class="form-input" 
                                               value="<?php echo htmlspecialchars($event['location']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Max Attendees</label>
                                        <input type="number" name="max_attendees" class="form-input" 
                                               value="<?php echo $event['max_attendees']; ?>" min="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Event Type</label>
                                        <input type="text" name="event_type" class="form-input" 
                                               value="<?php echo htmlspecialchars($event['event_type']); ?>" placeholder="e.g., Workshop, Seminar, Conference">
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>

                            <div class="event-details">
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Event Type</span>
                                    <span class="detail-value"><?php echo !empty($event['event_type']) ? ucfirst($event['event_type']) : 'Not specified'; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Created By</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['creator_name'] ?? 'Unknown'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Attendees Progress Bar -->
                            <div class="attendees-progress">
                                <div class="progress-label">
                                    <span>Attendees: </span>
                                    <span>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" ></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-top: 15px;">
                                <span>Start: <?php echo date('d/m/Y H:i', strtotime($event['event_date'])); ?></span>
                                <span>End: <?php echo date('d/m/Y H:i', strtotime($event['end_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-calendar-alt" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No events found</h3>
                    <?php if ($is_admin): ?>
                        <button onclick="showAddEventModal()" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Add Your First Event
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
  
    <!-- Add Event Modal -->
    <div id="addEventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-card); padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: var(--text-primary);">Add New Event</h2>
                <button onclick="hideAddEventModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 20px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="addEventForm">
                <input type="hidden" name="add_event" value="1">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-textarea" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="event_date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label>End Date & Time</label>
                        <input type="datetime-local" name="end_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Max Attendees</label>
                        <input type="number" name="max_attendees" class="form-input" min="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Event Type</label>
                        <input type="text" name="event_type" class="form-input" placeholder="e.g., Workshop, Seminar, Conference">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Save Event
                    </button>
                    <button type="button" onclick="hideAddEventModal()" class="btn btn-secondary" style="flex: 1;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
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
        
        function filterEvents(filter) {
            // Update active tab
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            tabs.forEach(tab => {
                if (tab.textContent.toLowerCase().includes(filter.toLowerCase())) {
                    tab.classList.add('active');
                }
            });
            
            // Filter events
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all' || status === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Check if no events are visible
            const visibleCards = document.querySelectorAll('.event-card:not(.hidden)');
            const noEventsMsg = document.querySelector('.no-events-msg');
            
            if (visibleCards.length === 0 && !noEventsMsg) {
                const eventsGrid = document.querySelector('.events-grid');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'no-events-msg';
                emptyDiv.style.cssText = 'text-align: center; padding: 50px; color: var(--text-secondary); grid-column: 1 / -1;';
                emptyDiv.innerHTML = `
                    <i class="fas fa-calendar-alt" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No ${filter} events found</h3>
                `;
                eventsGrid.appendChild(emptyDiv);
            } else if (visibleCards.length > 0 && document.querySelector('.no-events-msg')) {
                document.querySelector('.no-events-msg').remove();
            }
        }

        // Auto-submit search on enter
        document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Add Event Modal Functions
        function showAddEventModal() {
            document.getElementById('addEventModal').style.display = 'flex';
            // Set default datetime to now
            const now = new Date();
            const nowStr = now.toISOString().slice(0, 16);
            document.querySelector('input[name="event_date"]').value = nowStr;
            // Set end date to 2 hours from now
            const endDate = new Date(now.getTime() + 2 * 60 * 60 * 1000);
            const endStr = endDate.toISOString().slice(0, 16);
            document.querySelector('input[name="end_date"]').value = endStr;
        }

        function hideAddEventModal() {
            document.getElementById('addEventModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('addEventModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideAddEventModal();
            }
        });

        // Prevent form submission from closing modal
        document.getElementById('addEventForm')?.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Set default dates for new events
        window.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.querySelector('input[name="event_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            
            if (startDateInput && !startDateInput.value) {
                const now = new Date();
                const nowStr = now.toISOString().slice(0, 16);
                startDateInput.value = nowStr;
                
                const endDate = new Date(now.getTime() + 2 * 60 * 60 * 1000);
                const endStr = endDate.toISOString().slice(0, 16);
                endDateInput.value = endStr;
            }
        });
    </script>
</body>
</html>