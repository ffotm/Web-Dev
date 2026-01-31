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


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM certs_obtained WHERE id = $delete_id";
    $db->query($query);
    header("Location: certifications.php?message=Certificate+deleted+successfully");
    exit;
}


if (isset($_GET['approve_id'])) {
    $approve_id = $_GET['approve_id'];
    $approved_by = $_SESSION['user_id'];
    $query = "UPDATE certs_obtained SET is_approved = 1, approved_by = $approved_by WHERE id = $approve_id";
    $db->query($query);
    header("Location: certifications.php?message=Certificate+approved+successfully");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_certificate'])) {
    $user_id = $_POST['user_id'];
    $course_id = $_POST['course_id'];
    $completion_date = $_POST['completion_date'];
    $final_grade = $_POST['final_grade'];
    $total_hours_spent = $_POST['total_hours_spent'];
    $skills_learned = $_POST['skills_learned'];
    $notes = $_POST['notes'];
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;
    $approved_by = $is_approved ? $_SESSION['user_id'] : 'NULL';
    
 
 
    
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    
   
    $certificate_id = rand(1000, 9999);
    
   
    
    
    $insertQuery = "INSERT INTO certs_obtained 
                   (user_id, course_id, completion_date, final_grade, total_hours_spent, 
                    certificate_issued, certificate_id, skills_learned, notes, is_approved, approved_by) 
                   VALUES (
                       $user_id, 
                       $course_id, 
                       '$completion_date', 
                       $final_grade, 
                       $total_hours_spent, 
                       1, 
                       $certificate_id, 
                       '$skills_learned', 
                       '$notes', 
                       $is_approved, 
                       $approved_by
                   )";
    
    if ($db->query($insertQuery)) {
        $success = "Certificate issued successfully! Certificate ID: " . $certificate_id;
       
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        $error = "Failed to issue certificate! Error: " . $db->errorInfo()[2];
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_certificate'])) {
    $cert_id = $_POST['cert_id'];
    $final_grade = $_POST['final_grade'];
    $total_hours_spent = $_POST['total_hours_spent'];
    $skills_learned = $_POST['skills_learned'];
    $notes = $_POST['notes'];
    
    
    $updateQuery = "UPDATE certs_obtained SET 
                   final_grade = $final_grade,
                   total_hours_spent = $total_hours_spent,
                   skills_learned = '$skills_learned',
                   notes = '$notes'
                   WHERE id = $cert_id";
    
    if ($db->query($updateQuery)) {
        $success = "Certificate updated successfully!";
    } else {
        $error = "Failed to update certificate! Error: " . $db->errorInfo()[2];
    }
}


$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

$query = "SELECT co.*, 
          u.first_name, u.last_name, u.email,
          c.title as course_title,
          a.first_name as approved_by_name
          FROM certs_obtained co
          LEFT JOIN users u ON co.user_id = u.id
          LEFT JOIN courses c ON co.course_id = c.id
          LEFT JOIN users a ON co.approved_by = a.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' 
                OR c.title LIKE '%$search%' OR co.certificate_id LIKE '%$search%')";
}

if ($status_filter === 'approved') {
    $query .= " AND co.is_approved = 1";
} elseif ($status_filter === 'pending') {
    $query .= " AND co.is_approved = 0";
}

$query .= " ORDER BY co.created_at DESC";

$result = $db->query($query);
$certificates = $result->fetchAll(PDO::FETCH_ASSOC);


$students_query = "SELECT id, first_name, last_name FROM users WHERE role = 'student' ORDER BY first_name";
$students_result = $db->query($students_query);
$students = $students_result->fetchAll(PDO::FETCH_ASSOC);

$courses_query = "SELECT id, title FROM courses ORDER BY title";
$courses_result = $db->query($courses_query);
$courses = $courses_result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - Master Edu</title>
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
        
        /* Alert Messages */
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
        
        /* Add Certificate Form */
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
        
        .form-toggle i {
            transition: transform 0.3s ease;
        }
        
        .form-toggle i.rotated {
            transform: rotate(180deg);
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
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-checkbox input {
            width: 18px;
            height: 18px;
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
            font-family: 'Lusitana', serif;
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
        
        /* Certificates Grid */
        .certificates-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        
        .certificate-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(157, 255, 87, 0.1);
            position: relative;
        }
        
        .certificate-card.hidden {
            display: none;
        }
        
        /* Status badge */
        .status-badge {
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
        
        .status-approved {
            background-color: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .status-pending {
            background-color: rgba(255, 165, 2, 0.2);
            color: var(--warning);
        }
        
        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .certificate-info {
            flex: 1;
        }
        
        .certificate-id {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .student-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .course-title {
            font-size: 16px;
            color: var(--text-secondary);
        }
        
        .certificate-actions {
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
        
        .certificate-details {
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
        
        .skills-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .skills-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .skills-content {
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.5;
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
            
            .form-grid, .form-row, .certificate-details {
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
            <a href="certifications.php" class="menu-item active">
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
            <h1>Certificate Management</h1>
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
        
        <!-- Add Certificate Form -->
        <div class="add-form-section" id="addFormSection">
            <div class="form-toggle" id="formToggle" onclick="toggleCertificateForm()">
                <h2><i class="fas fa-plus-circle"></i> Issue New Certificate</h2>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="form-content" id="formContent">
                <form method="POST" action="">
                    <input type="hidden" name="add_certificate" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="user_id">Student *</label>
                            <select id="user_id" name="user_id" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php foreach($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_id">Course *</label>
                            <select id="course_id" name="course_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="completion_date">Completion Date *</label>
                            <input type="date" id="completion_date" name="completion_date" class="form-input" required
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="final_grade">Final Grade *</label>
                            <input type="number" id="final_grade" name="final_grade" class="form-input" 
                                   step="0.01" min="0" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_hours_spent">Total Hours *</label>
                            <input type="number" id="total_hours_spent" name="total_hours_spent" class="form-input" 
                                   step="0.5" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="form-checkbox">
                                <input type="checkbox" id="is_approved" name="is_approved" value="1">
                                <label for="is_approved">Approve immediately</label>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="skills_learned">Skills Learned</label>
                            <textarea id="skills_learned" name="skills_learned" class="form-textarea" 
                                      placeholder="List the skills acquired (separated by commas)..."></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" class="form-textarea" 
                                      placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-certificate"></i>
                            Issue Certificate
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Search Bar -->
        <form method="GET" action="" class="search-bar">
            <div class="search-box">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search by student name, course, or certificate ID..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <i class="fas fa-search search-icon"></i>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="certifications.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="?status_filter=all" class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Certificates</a>
            <a href="?status_filter=approved" class="tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status_filter=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending Approval</a>
        </div>
        
        <!-- Certificates Grid -->
        <div class="certificates-grid">
            <?php if (count($certificates) > 0): ?>
                <?php foreach ($certificates as $cert): ?>
                    <?php
                    $editing = isset($_GET['edit']) && $_GET['edit'] == $cert['id'];
                    $is_approved = $cert['is_approved'] == 1;
                    ?>
                    
                    <div class="certificate-card">
                        <!-- Status Badge -->
                        <div class="status-badge status-<?php echo $is_approved ? 'approved' : 'pending'; ?>">
                            <?php echo $is_approved ? 'Approved' : 'Pending Approval'; ?>
                        </div>
                        
                        <!-- Certificate Header -->
                        <div class="certificate-header">
                            <div class="certificate-info">
                                <div class="certificate-id">
                                    Certificate ID: <?php echo htmlspecialchars($cert['certificate_id']); ?>
                                </div>
                                <div class="student-name">
                                    <?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?>
                                </div>
                                <div class="course-title">
                                    <?php echo htmlspecialchars($cert['course_title']); ?>
                                </div>
                            </div>
                            
                            <div class="certificate-actions">
                                <?php if ($editing): ?>
                                    <button type="submit" form="edit_form_<?php echo $cert['id']; ?>" 
                                            class="action-btn btn-edit" title="Save">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <a href="certifications.php" class="action-btn btn-delete" title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?edit=<?php echo $cert['id']; ?>" 
                                       class="action-btn btn-edit" 
                                       title="Edit Certificate">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if (!$is_approved): ?>
                                    <a href="?approve_id=<?php echo $cert['id']; ?>" 
                                       class="action-btn btn-approve" 
                                       title="Approve Certificate"
                                       onclick="return confirm('Approve this certificate?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?delete_id=<?php echo $cert['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       title="Delete Certificate"
                                       onclick="return confirm('Delete this certificate?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($editing): ?>
                            <!-- Edit Form -->
                            <form method="POST" action="" class="edit-form" id="edit_form_<?php echo $cert['id']; ?>">
                                <input type="hidden" name="edit_certificate" value="1">
                                <input type="hidden" name="cert_id" value="<?php echo $cert['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Final Grade</label>
                                        <input type="number" name="final_grade" class="form-input" 
                                               value="<?php echo $cert['final_grade']; ?>" 
                                               step="0.01" min="0" max="100" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Total Hours</label>
                                        <input type="number" name="total_hours_spent" class="form-input" 
                                               value="<?php echo $cert['total_hours_spent']; ?>" 
                                               step="0.5" min="0" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Skills Learned</label>
                                    <textarea name="skills_learned" class="form-textarea"><?php echo htmlspecialchars($cert['skills_learned']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-textarea"><?php echo htmlspecialchars($cert['notes']); ?></textarea>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- View Mode -->
                            <div class="certificate-details">
                                <div class="detail-item">
                                    <span class="detail-label">Completion Date</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($cert['completion_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Final Grade</span>
                                    <span class="detail-value"><?php echo number_format($cert['final_grade'], 2); ?>%</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Total Hours</span>
                                    <span class="detail-value"><?php echo $cert['total_hours_spent']; ?> hours</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($cert['skills_learned'])): ?>
                            <div class="skills-section">
                                <div class="skills-label">Skills Learned:</div>
                                <div class="skills-content"><?php echo nl2br(htmlspecialchars($cert['skills_learned'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($cert['notes'])): ?>
                            <div class="skills-section">
                                <div class="skills-label">Notes:</div>
                                <div class="skills-content"><?php echo nl2br(htmlspecialchars($cert['notes'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-top: 15px;">
                                <span>Student Email: <?php echo htmlspecialchars($cert['email']); ?></span>
                                <?php if ($is_approved && $cert['approved_by_name']): ?>
                                <span>Approved by: <?php echo htmlspecialchars($cert['approved_by_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-certificate" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No certificates found</h3>
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

        // Toggle add certificate form
        function toggleCertificateForm() {
            const formContent = document.getElementById('formContent');
            const chevronIcon = document.querySelector('#formToggle .fa-chevron-down');
            const formSection = document.getElementById('addFormSection');
            
            formContent.classList.toggle('hidden');
            formSection.classList.toggle('collapsed');
            chevronIcon.classList.toggle('rotated');
        }

        // Prefill today's date in completion date field
        document.addEventListener('DOMContentLoaded', function() {
            const completionDateInput = document.getElementById('completion_date');
            if (completionDateInput && !completionDateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                completionDateInput.value = today;
            }
            
            // Add validation for grade input
            const finalGradeInput = document.getElementById('final_grade');
            if (finalGradeInput) {
                finalGradeInput.addEventListener('input', function() {
                    let value = parseFloat(this.value);
                    if (value < 0) this.value = 0;
                    if (value > 100) this.value = 100;
                });
            }
            
            // Add validation for hours input
            const hoursInput = document.getElementById('total_hours_spent');
            if (hoursInput) {
                hoursInput.addEventListener('input', function() {
                    let value = parseFloat(this.value);
                    if (value < 0) this.value = 0;
                });
            }
        });

        // Open add certificate form if there's an error (so user can see the form)
        <?php if (!empty($error) && isset($_POST['add_certificate'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('formContent').classList.contains('hidden')) {
                toggleCertificateForm();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>