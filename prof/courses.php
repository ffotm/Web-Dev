<?php
session_start();

require_once __DIR__ . '/../config/database.php';


$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'student';
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $duration_hours = $_POST['duration_hours'];
    $price = $_POST['price'];
    $level = $_POST['level'];
    $max_participants = $_POST['max_participants'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $query = "INSERT INTO courses (title, description, category, duration_hours, price, level, 
              formateur_id, max_participants, start_date, end_date, created_by, status) 
              VALUES ('$title', '$description', '$category', '$duration_hours', '$price', '$level', 
              '$user_id', '$max_participants', '$start_date', '$end_date', '$user_id', 'pending')";
    
    $db->query($query);
    $success_message = "Course submitted successfully, Waiting for approval.";
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $duration_hours = $_POST['duration_hours'];
    $price = $_POST['price'];
    $level = $_POST['level'];
    $max_participants = $_POST['max_participants'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    

    $check_query = "SELECT id, status FROM courses WHERE id = '$course_id' AND formateur_id = '$user_id'";
    $check_result = $db->query($check_query);
    $course_check = $check_result->fetch();
    
    if ($course_check && $course_check['status'] == 'pending') {
        $update_query = "UPDATE courses SET 
                        title = '$title',
                        description = '$description',
                        category = '$category',
                        duration_hours = '$duration_hours',
                        price = '$price',
                        level = '$level',
                        max_participants = '$max_participants',
                        start_date = '$start_date',
                        end_date = '$end_date',
                        updated_at = NOW()
                        WHERE id = '$course_id' AND formateur_id = '$user_id'";
        
        $db->query($update_query);
        $success_message = "Course updated successfully!";
    }
}
 
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
$course_to_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;


if ($active_tab == 'all') {
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM user_courses WHERE course_id = c.id) as registered_students,
              CONCAT(t.first_name, ' ', t.last_name) as teacher_name
              FROM courses c
              LEFT JOIN users t ON c.formateur_id = t.id
              WHERE c.formateur_id = $user_id
              ORDER BY c.created_at DESC";
} else {
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM user_courses WHERE course_id = c.id) as registered_students,
              CONCAT(t.first_name, ' ', t.last_name) as teacher_name
              FROM courses c
              LEFT JOIN users t ON c.formateur_id = t.id
              WHERE c.formateur_id = $user_id AND c.status = '$active_tab'
              ORDER BY c.created_at DESC";
}

$result = $db->query($query);
$courses = $result->fetchAll();


$editing_course = null;
if ($course_to_edit > 0) {
    $edit_query = "SELECT * FROM courses WHERE id = $course_to_edit AND formateur_id = $user_id";
    $edit_result = $db->query($edit_query);
    $editing_course = $edit_result->fetch();
}


$student_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$student_result = $db->query($student_query);
$totalStudents = $student_result->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">     
    <title>Teacher Dashboard - Master Edu</title>
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
            min-height: 100vh;
        }

        header {
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
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
        }
        
        .logo svg {
            width: 28px;
            height: 28px;
        }
        
        nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-family: "Lusitana", serif;
            font-size: 14px;
        }
        
        .user-button:hover {
            background: var(--bg-card-hover);
            transform: translateY(-2px);
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
            font-size: 16px;
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
            z-index: 1000;
        }
        
        .dropdown-menu.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 12px 15px;
            color: var(--text-primary);
            text-decoration: none;
            display: block;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background: var(--bg-card-hover);
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 10px 0;
        }
        
        .main-content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
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
            gap: 20px;
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

        /* Success message */
        .success-message {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--btn-text);
            cursor: pointer;
            font-size: 20px;
        }

        /* Course Creation Form */
        .creation-form {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(157, 255, 87, 0.1);
        }

        .creation-form h2 {
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-input, .form-textarea, .form-select {
            padding: 10px;
            background-color: var(--bg-tertiary);
            border: 1px solid rgba(157, 255, 87, 0.2);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: "Lusitana", serif;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 5px;
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

        .status-pending {
            background-color: var(--warning);
            color: #212529;
        }

        .status-approved {
            background-color: var(--btn-bg);
            color: var(--btn-text);
        }

        .status-rejected {
            background-color: var(--danger);
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
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            font-size: 12px;
            text-decoration: none;
        }

        .btn-edit {
            background-color: var(--btn-bg);
            color: var(--btn-text);
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        .btn-view {
            background-color: var(--info);
            color: white;
        }

        .btn-disabled {
            background-color: #78909c;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
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

        .teacher-stats {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--btn-bg);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
        }

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
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Master Edu</span>
                </div>
                <nav>
                    <div class="user-menu">
                        <button class="user-button" id="userMenuBtn">
                            <div class="user-avatar"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                            <span><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php" class="dropdown-item">My Profile</a>
                            <a href="my-courses.php" class="dropdown-item">My Courses</a>
                            <a href="settings.php" class="dropdown-item">Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="auth.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Teacher Dashboard</h1>
        </div>



        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="success-message" id="successMessage">
                <?php echo htmlspecialchars($success_message); ?>
                <button class="close-btn" onclick="document.getElementById('successMessage').remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Course Creation Form -->
        <div class="creation-form" id="creationForm">
            <h2><?php echo $editing_course ? 'Edit Course' : 'Create New Course'; ?></h2>
            <form method="POST" action="" enctype="multipart/form-data" id="courseForm">
                
                
                <div class="form-group">
                    <label class="form-label">Course Title *</label>
                    <input type="hidden" name="course_id" value="<?php echo $editing_course ? $editing_course['id'] : ''; ?>">
                    <input type="text" name="title" class="form-input" required 
                           value="<?php echo $editing_course ? htmlspecialchars($editing_course['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"> Description *</label>
                    <textarea name="description" class="form-textarea" required><?php echo $editing_course ? htmlspecialchars($editing_course['description']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-input" 
                               value="<?php echo $editing_course ? htmlspecialchars($editing_course['category']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Duration (hours)</label>
                        <input type="number" name="duration_hours" class="form-input" 
                               value="<?php echo $editing_course ? $editing_course['duration_hours'] : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (DA)</label>
                        <input type="number" step="0.01" name="price" class="form-input" 
                               value="<?php echo $editing_course ? $editing_course['price'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select">
                            <option value="beginner" <?php echo ($editing_course && $editing_course['level'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo ($editing_course && $editing_course['level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo ($editing_course && $editing_course['level'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Max Participants</label>
                        <input type="number" name="max_participants" class="form-input" 
                               value="<?php echo $editing_course ? $editing_course['max_participants'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course Image</label>
                        <input type="file" name="image" class="form-input" accept="image/*">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-input" required 
                               value="<?php echo $editing_course ? $editing_course['start_date'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Date *</label>
                        <input type="date" name="end_date" class="form-input" required 
                               value="<?php echo $editing_course ? $editing_course['end_date'] : ''; ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <?php if ($editing_course): ?>
                        <button type="submit" name="update_course" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Course
                        </button>
                        <a href="?tab=<?php echo $active_tab; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="submit_course" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit for Approval
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=pending" class="tab <?php echo $active_tab == 'pending' ? 'active' : ''; ?>">
                Pending Courses
            </a>
            <a href="?tab=approved" class="tab <?php echo $active_tab == 'approved' ? 'active' : ''; ?>">
                Approved Courses
            </a>
            <a href="?tab=all" class="tab <?php echo $active_tab == 'all' ? 'active' : ''; ?>">
                All Courses
            </a>
        </div>

        <!-- Courses Grid -->
        <div class="courses-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <?php
                    $start_date = date('d/m/Y', strtotime($course['start_date']));
                    $end_date = date('d/m/Y', strtotime($course['end_date']));
                    $status = strtolower($course['status']);
                    $can_edit = ($status == 'pending');
                    ?>
                    
                    <div class="course-card">
                        <!-- Status Badge -->
                        <div class="course-status-badge status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </div>

                        <!-- Course Header with Actions -->
                        <div class="course-header">
                            <div class="course-title">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </div>
                            <div class="course-actions">
                                <?php if ($can_edit): ?>
                                    <a href="?tab=<?php echo $active_tab; ?>&edit=<?php echo $course['id']; ?>" class="action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php else: ?>
                                    <button class="action-btn btn-disabled" disabled>
                                        <i class="fas fa-lock"></i> Read Only
                                    </button>
                                <?php endif; ?>
                                
                                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="action-btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>

                        <!-- Course Description -->
                        <div class="course-description">
                            <?php echo htmlspecialchars($course['description']); ?>
                        </div>

                        <!-- Course Details -->
                        <div class="course-details">
                            <div class="detail-item">
                                <span class="detail-label">Price</span>
                                <span class="detail-value">DA <?php echo number_format($course['price'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Level</span>
                                <span class="detail-value"><?php echo ucfirst($course['level']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Students</span>
                                <span class="detail-value"><?php echo $course['registered_students']; ?> enrolled</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Duration</span>
                                <span class="detail-value"><?php echo $course['duration_hours']; ?> hours</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-top: 15px;">
                            <span>Start: <?php echo $start_date; ?></span>
                            <span>End: <?php echo $end_date; ?></span>
                        </div>
                        
                        <?php if ($course['status'] == 'approved'): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                                <span style="color: var(--btn-bg); font-size: 12px;">
                                    <i class="fas fa-check-circle"></i> This course is approved and visible to students
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-book" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No <?php echo $active_tab; ?> courses found</h3>
                    <p>Submit a new course using the form above</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
  
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        // User menu toggle
        document.getElementById('userMenuBtn').addEventListener('click', function() {
            document.getElementById('dropdownMenu').classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById('dropdownMenu');
            var button = document.getElementById('userMenuBtn');
            if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Theme toggle
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

      

        // Form validation for dates
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('courseForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const startDate = new Date(form.start_date.value);
                    const endDate = new Date(form.end_date.value);
                    
                    if (endDate <= startDate) {
                        e.preventDefault();
                        alert('End date must be after start date');
                        form.end_date.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>