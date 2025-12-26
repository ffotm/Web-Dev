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
    $query = "DELETE FROM courses WHERE id = $delete_id";
    $db->query($query);
    header("Location: courses.php?message=Course+deleted+successfully");
    exit;
}


if (isset($_GET['change_status']) && $is_admin) {
    $course_id = $_GET['change_status'];
    $new_status = $_GET['status'];
    
    $query = "UPDATE courses SET status = '$new_status' WHERE id = $course_id";
    $db->query($query);
    header("Location: courses.php?message=Status+updated+successfully");
    exit;
}
if (isset($_POST['edit_course']) && $is_admin) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $duration_hours = $_POST['duration_hours'];
    $formateur_id = $_POST['formateur_id'];
    
    $query = "UPDATE courses SET 
              title = '$title',
              description = '$description',
              price = '$price',
              start_date = '$start_date',
              end_date = '$end_date',
              duration_hours = '$duration_hours',
              formateur_id = '$formateur_id'
              WHERE id = $course_id";
    
    $db->query($query);
    header("Location: courses.php?message=Course+updated+successfully");
    exit;
}

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

// Build query
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM user_courses WHERE course_id = c.id) as registered_students,
          t.first_name as teacher_firstname,
          t.last_name as teacher_lastname,
          CONCAT(t.first_name, ' ', t.last_name) as teacher_name
          FROM courses c
          LEFT JOIN users t ON c.formateur_id = t.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (c.title LIKE '%$search%' OR c.description LIKE '%$search%' OR t.first_name LIKE '%$search%' OR t.last_name LIKE '%$search%')";
}

if ($status_filter !== 'all') {
    $query .= " AND c.status = '$status_filter'";
}

$query .= " ORDER BY c.created_at DESC";

$result = $db->query($query);
$courses = $result->fetchAll();

// Get all teachers for dropdown
$teachers_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'formateur'";
$teachers_result = $db->query($teachers_query);
$teachers = $teachers_result->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">     
    <title>Course Management - Master Edu</title>
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
        }

        .search-box {
            flex: 1;
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

        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .course-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(157, 255, 87, 0.1);
            position: relative;
        }

        .course-card.hidden {
            display: none;
        }

        /* Status badge */
        .course-status-badge {
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

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .course-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .course-actions {
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

        .course-description {
            color: var(--text-secondary);
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .course-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
                <a href="dashboard.php" class="menu-item active">
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
 <a href="events.php" class="menu-item">
                    <i class="fas fa-star"></i>
                    <span>Events</span>
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
            <h1>Course Management</h1>
            <div class="action-buttons">
                <?php if ($is_admin): ?>
                    <a href="add-course.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Course
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="" class="search-bar">
            <div class="search-box">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search courses..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <i class="fas fa-search search-icon"></i>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="courses.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>

    
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="filterCourses('all')">All Courses</div>
            <div class="tab" onclick="filterCourses('upcoming')">Upcoming</div>
            <div class="tab" onclick="filterCourses('active')">Active</div>
            <div class="tab" onclick="filterCourses('completed')">Completed</div>
        </div>

        <!-- Courses Grid -->
        <div class="courses-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <?php
                    $start_date = date('d/m/Y', strtotime($course['start_date']));
                    $end_date = date('d/m/Y', strtotime($course['end_date']));
                    $duration = $course['duration_hours'];
                    $status = strtolower($course['status']);
                    $editing = isset($_GET['edit']) && $_GET['edit'] == $course['id'];
                    ?>
                    
                    <div class="course-card" data-status="<?php echo $status; ?>">
                        <!-- Status Badge -->
                        <div class="course-status-badge status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </div>

                        <!-- Course Header with Actions -->
                        <div class="course-header">
                            <div class="course-title">
                                <?php if ($editing): ?>
                                    <input type="text" name="title" form="edit_form_<?php echo $course['id']; ?>" 
                                           value="<?php echo htmlspecialchars($course['title']); ?>" 
                                           class="form-input" style="width: 100%;" required>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_admin): ?>
                                <div class="course-actions">
                                    <?php if ($editing): ?>
                                        <!-- Save and Cancel buttons -->
                                        <button type="submit" form="edit_form_<?php echo $course['id']; ?>" 
                                                class="action-btn btn-edit" title="Save">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="courses.php" class="action-btn btn-status" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php else: ?>
                                        <!-- Edit button -->
                                        <a href="?edit=<?php echo $course['id']; ?>" 
                                           class="action-btn btn-edit" 
                                           title="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Status Dropdown -->
                                        <div class="status-dropdown">
                                            <button class="action-btn btn-status" title="Change Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <div class="status-dropdown-content">
                                                <a href="?change_status=<?php echo $course['id']; ?>&status=upcoming">
                                                    Set as Upcoming
                                                </a>
                                                <a href="?change_status=<?php echo $course['id']; ?>&status=active">
                                                    Set as Active
                                                </a>
                                                <a href="?change_status=<?php echo $course['id']; ?>&status=completed">
                                                    Set as Completed
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Button -->
                                        <a href="?delete_id=<?php echo $course['id']; ?>" 
                                           class="action-btn btn-delete" 
                                           title="Delete Course"
                                           onclick="return confirm('Delete this course?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($editing): ?>
                            <!-- Edit Form -->
                            <form method="POST" action="" class="edit-form" id="edit_form_<?php echo $course['id']; ?>">
                                <input type="hidden" name="edit_course" value="1">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-textarea" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Price (da)</label>
                                    <input type="number" name="price" class="form-input" 
                                           value="<?php echo $course['price']; ?>" step="0.01" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-input" 
                                           value="<?php echo date('Y-m-d', strtotime($course['start_date'])); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-input" 
                                           value="<?php echo date('Y-m-d', strtotime($course['end_date'])); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Duration (hours)</label>
                                    <input type="number" name="duration_hours" class="form-input" 
                                           value="<?php echo $course['duration_hours']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Teacher</label>
                                    <select name="formateur_id" class="form-select" required>
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>" 
                                                <?php echo $teacher['id'] == $course['formateur_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($teacher['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="course-description">
                                <?php echo htmlspecialchars($course['description']); ?>
                            </div>

                            <div class="course-details">
                                <div class="detail-item">
                                    <span class="detail-label">Price</span>
                                    <span class="detail-value">da <?php echo number_format($course['price'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Teacher</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Students</span>
                                    <span class="detail-value"><?php echo $course['registered_students']; ?> enrolled</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Duration</span>
                                    <span class="detail-value"><?php echo $duration; ?> hours</span>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-top: 15px;">
                                <span>Start: <?php echo $start_date; ?></span>
                                <span>End: <?php echo $end_date; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-book" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No courses found</h3>
                    <?php if ($is_admin): ?>
                        <a href="add-course.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Add Your First Course
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
        function filterCourses(filter) {
            // Update active tab
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            tabs.forEach(tab => {
                if (tab.textContent.toLowerCase().includes(filter.toLowerCase())) {
                    tab.classList.add('active');
                }
            });
            
            // Filter courses
            const courseCards = document.querySelectorAll('.course-card');
            
            courseCards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all' || status === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Check if no courses are visible
            const visibleCards = document.querySelectorAll('.course-card:not(.hidden)');
            const noCoursesMsg = document.querySelector('.no-courses-msg');
            
            if (visibleCards.length === 0 && !noCoursesMsg) {
                const coursesGrid = document.querySelector('.courses-grid');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'no-courses-msg';
                emptyDiv.style.cssText = 'text-align: center; padding: 50px; color: var(--text-secondary); grid-column: 1 / -1;';
                emptyDiv.innerHTML = `
                    <i class="fas fa-book" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No ${filter} courses found</h3>
                `;
                coursesGrid.appendChild(emptyDiv);
            } else if (visibleCards.length > 0 && document.querySelector('.no-courses-msg')) {
                document.querySelector('.no-courses-msg').remove();
            }
        }

        // Auto-submit search on enter
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>