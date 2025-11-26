<?php
session_start();


require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}
// Create database object
$database = new Database();
$db = $database->getConnection();

// Get all students
$query = "SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC";
$result = $db->query($query);   
$students = $result->fetchAll(PDO::FETCH_ASSOC);

// Get total students count
$countQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$countResult = $db->query($countQuery);   
$totalStudents = $countResult->fetch(PDO::FETCH_ASSOC);

// Get current user info
$userQuery = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$userResult = $db->query($userQuery);
$currentUser = $userResult->fetchAll(PDO::FETCH_ASSOC);

// Get active courses count
    $activeCoursesquery= "SELECT COUNT(*) as total FROM courses";
    $courseresult = $db->query($activeCoursesquery);
    $activeCourses = $courseresult->fetch(PDO::FETCH_ASSOC);

// Get formateurs count
  $formateurquery = "SELECT COUNT(*) as total FROM users WHERE role = 'formateur'";
     $formateurresult = $db->query($formateurquery);
    $totalFormateurs = $formateurresult->fetch(PDO::FETCH_ASSOC);

// Get pending approvals count
 $pendingquery = "SELECT COUNT(*) as total FROM registrations WHERE status = 'pending'";
     $pendingresult = $db->query($pendingquery);
    $totalpending = $pendingresult->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Master Edu</title>
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
            padding: 24px;
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .header-title h1 {
            font-size: 28px;
            margin-bottom: 4px;
        }
        
        .header-title p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn-primary {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-card);
            padding: 8px 16px;
            border-radius: 25px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-card p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Search Bar */
        .search-bar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .search-bar input {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .search-bar input::placeholder {
            color: var(--text-secondary);
        }
        
        /* Table */
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
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        tr:hover {
            background: var(--bg-card-hover);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .student-details h4 {
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .student-details p {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .status-inactive {
            background: rgba(255, 87, 87, 0.2);
            color: #ff5757;
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
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .btn-edit {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .btn-delete {
            background: rgba(255, 87, 87, 0.2);
            color: #ff5757;
        }
        
        .btn-action:hover {
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
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
            <a href="students.php" class="menu-item active">
                <i class="fas fa-users"></i>
                <span>Students</span>
                <span class="menu-badge"><?php echo $totalStudents['total']; ?></span>
            </a>
            <a href="teachers.php" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teachers</span>
                <span class="menu-badge"><?php echo $totalFormateurs['total']; ?></span>
            </a>
            <a href="courses.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
                <span class="menu-badge"><?php echo $activeCourses['total']; ?></span>
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
            </a>
            <a href="pending-approvals.php" class="menu-item">
                <i class="fas fa-clock"></i>
                <span>Pending Approvals</span>
                <?php if($totalpending > 0): ?>
                <span class="menu-badge"><?php echo $totalpending['total']; ?></span>
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
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Students Management</h1>
                <p>Manage all registered students</p>
            </div>
            <div class="header-actions">
                <a href="add-student.php" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Student
                </a>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($currentUser['first_name'] ?? 'Admin'); ?></span>
                </div>
            </div>
        </div>
        
     
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" placeholder="Search students by name or email..." style="font-family: 'Lusitana', serif;" id="searchInput" onkeyup="searchStudents()">
        </div>
        
        <!-- Students Table -->
        <div class="table-container">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Phone</th>
                    
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                    <p>ID: #<?php echo $student['id']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                    
                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="editStudent(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        
        // Search Students
        function searchStudents() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('studentsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
        
        // Action Functions
        function viewStudent(id) {
            window.location.href = 'view-student.php?id=' + id;
        }
        
        function editStudent(id) {
            window.location.href = 'edit-student.php?id=' + id;
        }
        
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                window.location.href = 'delete-student.php?id=' + id;
            }
        }
    </script>
</body>
</html>