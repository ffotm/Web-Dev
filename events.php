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
$query = "SELECT role FROM users WHERE id = $user_id";
$result = $db->query($query);
$user = $result->fetch();
$is_admin = $user['role'] === 'admin';

// If user is admin, redirect to admin events page
if ($is_admin) {
    header("Location: events.php");
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
          WHERE e.status != 'cancelled'"; // Don't show cancelled events to public

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
    $attendees_query = "SELECT COUNT(*) as attendees FROM user_events WHERE event_id = $event_id ";
    $attendees_result = $db->query($attendees_query);
    $event['registered_attendees'] = $attendees_result->fetch()['attendees'] ?? 0;
    $event['available_spots'] = $event['max_attendees'] - $event['registered_attendees'];
}
unset($event); // Break the reference

// Get event types for filter
$event_types_query = "SELECT DISTINCT event_type FROM events WHERE event_type IS NOT NULL AND status != 'cancelled'";
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

        /* Google Form Link */
        .google-form-section {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--bg-tertiary);
            border-radius: 8px;
            border-left: 4px solid var(--btn-bg);
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-primary);
            font-size: 16px;
        }

        .form-title i {
            color: var(--btn-bg);
        }

        .form-description {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .form-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
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

        .spots-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: rgba(157, 255, 87, 0.1);
            border-radius: 6px;
            font-size: 13px;
        }

        .spots-available {
            color: var(--btn-bg);
            font-weight: 600;
        }

        .spots-full {
            color: var(--danger);
            font-weight: 600;
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

        .progress-fill.full {
            background-color: var(--danger);
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

        /* Event dates footer */
        .event-date-footer {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* No events message */
        .no-events {
            text-align: center;
            padding: 50px;
            color: var(--text-secondary);
        }

        .no-events i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        <span class="nav-badge cart-count"><?php echo $cartCount; ?></span>
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
                            <div class="dropdown-divider"></div>
                            <a href="auth.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
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
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Events</option>
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
            <?php if (!empty($search) || $status_filter != 'all' || $type_filter != 'all'): ?>
                <a href="events.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            <?php endif; ?>
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
                    $is_upcoming = $status == 'upcoming';
                    $is_active = $status == 'active';
                    $is_completed = $status == 'completed';
                    
                    $available_spots = $event['available_spots'];
                    $has_spots = $available_spots > 0;
                    $is_full = $available_spots <= 0;
                    $attendee_percentage = $event['max_attendees'] > 0 ? 
                        min(100, ($event['registered_attendees'] / $event['max_attendees']) * 100) : 0;
                    
                   
                    $show_google_form = $has_spots && ($is_upcoming || $is_active);
                    ?>
                    
                    <div class="event-card" data-status="<?php echo $status; ?>">
                        <!-- Status Badge -->
                        <div class="event-status-badge status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                            <?php if ($is_full && ($is_upcoming || $is_active)): ?>
                                <span style="margin-left: 5px;">• FULL</span>
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
                                <span><?php echo $event['registered_attendees']; ?>/<?php echo $event['max_attendees']; ?> (<?php echo round($attendee_percentage); ?>%)</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $is_full ? 'full' : ''; ?>" 
                                     style="width: <?php echo $attendee_percentage; ?>%"></div>
                            </div>
                        </div>

                        <!-- Google Form Section (only for upcoming/active events with spots) -->
                        <?php if ($show_google_form): ?>
                            <div class="google-form-section">
                                <div class="form-title">
                                    <i class="fab fa-google"></i>
                                    <h3>Register for this Event</h3>
                                </div>
                                <div class="form-description">
                                    Click the link below to register for this event through our Google Form.
                                    Registration will be confirmed within 24 hours.
                                </div>
                                <a href="https://docs.google.com/forms/d/e/YOUR_GOOGLE_FORM_ID_HERE/viewform?entry.1234567890=<?php echo urlencode($event['title']); ?>" 
                                   target="_blank" 
                                   class="form-link">
                                    <i class="fas fa-external-link-alt"></i> Open Registration Form
                                </a>
                                <div class="spots-info">
                                    <i class="fas fa-info-circle"></i>
                                    <?php echo $available_spots; ?> spot<?php echo $available_spots > 1 ? 's' : ''; ?> remaining
                                </div>
                            </div>
                        <?php elseif ($is_completed): ?>
                            <div class="google-form-section" style="border-left-color: #78909c;">
                                <div class="form-title">
                                    <i class="fas fa-check-circle"></i>
                                    <h3>Event Completed</h3>
                                </div>
                                <div class="form-description">
                                    This event has already taken place. Check out our upcoming events for future opportunities.
                                </div>
                            </div>
                        <?php elseif ($is_full): ?>
                            <div class="google-form-section" style="border-left-color: var(--danger);">
                                <div class="form-title">
                                    <i class="fas fa-times-circle"></i>
                                    <h3>Event Full</h3>
                                </div>
                                <div class="form-description">
                                    This event has reached maximum capacity. You can join the waiting list by contacting support.
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
                    <i class="fas fa-calendar-alt"></i>
                    <h3>No events found</h3>
                    <p style="margin-top: 10px; color: var(--text-secondary);">
                        <?php if (!empty($search)): ?>
                            No events match your search criteria. Try different keywords or clear the search.
                        <?php else: ?>
                            There are currently no events scheduled.
                            Check back soon for new events!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || $status_filter != 'all'): ?>
                        <a href="events.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-calendar-alt"></i> View All Events
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
        
        // Filter events by status
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
            const noEventsMsg = document.querySelector('.no-events');
            
            if (visibleCards.length === 0 && !noEventsMsg) {
                const eventsGrid = document.querySelector('.events-grid');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'no-events';
                emptyDiv.style.cssText = 'text-align: center; padding: 50px; color: var(--text-secondary); grid-column: 1 / -1;';
                emptyDiv.innerHTML = `
                    <i class="fas fa-calendar-alt" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No ${filter} events found</h3>
                `;
                eventsGrid.appendChild(emptyDiv);
            } else if (visibleCards.length > 0 && document.querySelector('.no-events')) {
                document.querySelector('.no-events').remove();
            }
        }

        // Auto-submit search on enter
        document.querySelector('.search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Add click handlers to tabs (if you add tabs back)
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const filter = this.textContent.toLowerCase().replace(' events', '');
                filterEvents(filter === 'all events' ? 'all' : filter);
            });
        });
    </script>
</body>
</html>