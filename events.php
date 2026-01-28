<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user info
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';

$query = "SELECT role FROM users WHERE id = $user_id";
$result = $db->query($query);
$user = $result->fetch();
$is_admin = $user['role'] === 'admin';


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


$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : 'all';


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

$query .= " ORDER BY e.event_date ASC";

$result = $db->query($query);
$events = $result->fetchAll();

// Count attendees for each event
foreach ($events as &$event) {
    $event_id = $event['id'];
    $attendees_query = "SELECT COUNT(*) as attendees FROM user_events WHERE event_id = $event_id";
    $attendees_result = $db->query($attendees_query);
    $event['registered_attendees'] = $attendees_result->fetch()['attendees'] ?? 0;
    $event['available_spots'] = $event['max_attendees'] - $event['registered_attendees'];
}
unset($event);

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
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">     
    <title>Events - Master Edu</title>
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
            font-family: 'Lusitana', serif;
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
            font-family: 'Lusitana', serif;
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
            padding: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(157, 255, 87, 0.1);
        }

        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .event-status-badge {
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

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .event-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .event-type-badge {
            padding: 4px 12px;
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .event-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .detail-value {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Progress bar */
        .attendees-progress {
            margin-top: 20px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .progress-bar {
            height: 8px;
            background-color: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--btn-bg);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-fill.full {
            background-color: var(--danger);
        }

        .spots-available {
            color: var(--btn-bg);
            font-weight: 600;
        }

        .spots-full {
            color: var(--danger);
            font-weight: 600;
        }

        /* Google Form Section */
        .google-form-section {
            margin-top: 25px;
            padding: 20px;
            background-color: var(--bg-tertiary);
            border-radius: 12px;
            border-left: 4px solid var(--btn-bg);
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .form-title i {
            color: var(--btn-bg);
        }

        .form-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .form-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--btn-bg);
            color: var(--btn-text);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-link:hover {
            background-color: var(--btn-hover);
            transform: translateY(-2px);
        }

        /* Event dates footer */
        .event-date-footer {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* No events */
        .no-events {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-events i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
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
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            nav {
                gap: 8px;
            }
            
            .nav-link span {
                display: none;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-box {
                min-width: 100%;
            }

            .event-details {
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
                    <a href="my-subscriptions.php" class="nav-link">
                        <i class="fas fa-book-reader"></i>
                        <span>Subscriptions</span>
                        <?php if($totalSubscriptions > 0): ?>
                        <span class="nav-badge"><?php echo $totalSubscriptions; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="my-certificates.php" class="nav-link">
                        <i class="fas fa-certificate"></i>
                        <span>Certificates</span>
                        <?php if($totalCertificates > 0): ?>
                        <span class="nav-badge"><?php echo $totalCertificates; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="wishlist.php" class="nav-link">
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
                            <a href="my-courses.php" class="dropdown-item"><i class="fas fa-book"></i> My Courses</a>
                            <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                            <a href="auth.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>All Events</h1>
            </div>

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
            
            </form>

            <!-- Events Grid -->
            <div class="events-grid">
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $start_date = date('F j, Y', strtotime($event['event_date']));
                        $start_time = date('g:i A', strtotime($event['event_date']));
                        $end_date = date('F j, Y', strtotime($event['end_date']));
                        $end_time = date('g:i A', strtotime($event['end_date']));
                        $status = strtolower($event['status']);
                        
                        $available_spots = $event['available_spots'];
                        $has_spots = $available_spots > 0;
                        $is_full = $available_spots <= 0;
                        $attendee_percentage = $event['max_attendees'] > 0 ? 
                            min(100, ($event['registered_attendees'] / $event['max_attendees']) * 100) : 0;
                        
                        $is_upcoming = $status == 'upcoming';
                        $is_active = $status == 'active';
                        $is_completed = $status == 'completed';
                        $show_google_form = $has_spots && ($is_upcoming || $is_active);
                        ?>
                        
                        <div class="event-card">
                            <!-- Status Badge -->
                            <div class="event-status-badge status-<?php echo $status; ?>">
                                <?php echo ucfirst($status); ?>
                                <?php if ($is_full && ($is_upcoming || $is_active)): ?>
                                    • FULL
                                <?php endif; ?>
                            </div>

                            <!-- Event Header -->
                            <div class="event-header">
                                <h2 class="event-title">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                    <?php if (!empty($event['event_type'])): ?>
                                        <span class="event-type-badge"><?php echo ucfirst($event['event_type']); ?></span>
                                    <?php endif; ?>
                                </h2>
                            </div>

                            <!-- Event Description -->
                            <div class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>

                            <!-- Event Details -->
                            <div class="event-details">
                                <div class="detail-item">
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Organizer</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['creator_name'] ?? 'Master Edu'); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Capacity</span>
                                    <span class="detail-value"><?php echo $event['max_attendees']; ?> attendees</span>
                                </div>
                            </div>

                            <!-- Attendees Progress -->
                            <div class="attendees-progress">
                                <div class="progress-label">
                                    <span>
                                        <?php if ($is_completed): ?>
                                            Event completed
                                        <?php elseif ($is_full): ?>
                                            <span class="spots-full">No spots available</span>
                                        <?php else: ?>
                                            <span class="spots-available"><?php echo $available_spots; ?> spots available</span>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo $event['registered_attendees']; ?>/<?php echo $event['max_attendees']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?php echo $is_full ? 'full' : ''; ?>" 
                                         style="width: <?php echo $attendee_percentage; ?>%"></div>
                                </div>
                            </div>

                            <!-- Google Form Section -->
                            <?php if ($show_google_form): ?>
                                <div class="google-form-section">
                                    <div class="form-title">
                                        <i class="fab fa-google"></i>
                                        <h3>Register for this Event</h3>
                                    </div>
                                    <div class="form-description">
                                        Click the link below to register for this event. Registration will be confirmed within 24 hours.
                                    </div>
                                    <a href="https://forms.google.com" 
                                       target="_blank" 
                                       class="form-link">
                                        <i class="fas fa-external-link-alt"></i> Open Registration Form
                                    </a>
                                </div>
                            <?php elseif ($is_completed): ?>
                                <div class="google-form-section" style="border-left-color: #78909c;">
                                    <div class="form-title">
                                        <i class="fas fa-check-circle"></i>
                                        <h3>Event Completed</h3>
                                    </div>
                                    <div class="form-description">
                                        This event has ended. Check out our upcoming events!
                                    </div>
                                </div>
                            <?php elseif ($is_full): ?>
                                <div class="google-form-section" style="border-left-color: var(--danger);">
                                    <div class="form-title">
                                        <i class="fas fa-times-circle"></i>
                                        <h3>Event Full</h3>
                                    </div>
                                    <div class="form-description">
                                        This event has reached maximum capacity.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Event Dates Footer -->
                            <div class="event-date-footer">
                                <span>Start: <?php echo $start_date; ?> at <?php echo $start_time; ?></span>
                                <span>End: <?php echo $end_date; ?> at <?php echo $end_time; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No events found</h3>
                        <p style="margin-top: 15px;">
                            <?php if (!empty($search)): ?>
                                No events match your search criteria.
                            <?php else: ?>
                                There are currently no events scheduled.
                            <?php endif; ?>
                        </p>
                    </div>
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
    </script>
</body>
</html>