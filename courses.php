<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle Add to Cart - FIXED WITH CORRECT CART_TYPE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "login_required";
        exit();
    }
    
    if (!isset($_POST['course_id'])) {
        echo "no_course_id";
        exit();
    }
    
    $course_id = $_POST['course_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if course already in cart
    $check_query = "SELECT id, quantity FROM user_cart WHERE user_id = '$user_id' AND course_id = '$course_id'";
    $check_result = $db->query($check_query);
    
    if ($check_result->rowCount() > 0) {
        // Update quantity
        $cart_item = $check_result->fetch();
        $new_quantity = $cart_item['quantity'] + 1;
        
        $update_query = "UPDATE user_cart 
                        SET quantity = '$new_quantity',
                            updated_at = NOW() 
                        WHERE user_id = '$user_id' AND course_id = '$course_id'";
        $db->query($update_query);
    } else {
        // Add new item - use 'main' as cart_type (since it's an ENUM with only 'main' or 'wishlist')
        $insert_query = "INSERT INTO user_cart (user_id, course_id, quantity, cart_type) 
                         VALUES ('$user_id', '$course_id', 1, 'main')";
        $db->query($insert_query);
    }
    
    echo "success";
    exit();
}
// Get user role
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = $user_id";
$result = $db->query($query);
$user_role = $result->fetch()['role'];
$is_admin = $user_role === 'admin';
$first_name = $_SESSION['first_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'student';
$last_name = $_SESSION['last_name'] ?? 'User';


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
         
            min-height: 100vh;
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
          
            flex: 1;
            padding: 30px;
            
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

        /* User Menu Styles */
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
        
        .btn-primary {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 26px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--btn-hover);
            transform: scale(1.05);
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
                            <div class="user-avatar"><?php echo strtoupper(substr($first_name , 0, 1)); ?></div>
                            <span><?php echo htmlspecialchars($first_name); htmlspecialchars($last_name); ?></span>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="profile.php" class="dropdown-item">My Profile</a>
                            <a href="my-courses.php" class="dropdown-item"> My Courses</a>
                            <a href="settings.php" class="dropdown-item"> Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="auth.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
<div class="cart" style="text-decoration: none; margin: 5px; position: absolute; top: 40px; right: 140px; color: var(--text-secondary); ">
<a href="cart.php" style="text-decoration: none; color: var(--text-secondary);">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-count badge">
        <?php
        
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $count_query = "SELECT SUM(quantity) as total FROM user_cart WHERE user_id = '$user_id'";
            $count_result = $db->query($count_query);
            $count_data = $count_result->fetch();
            echo $count_data['total'] ? $count_data['total'] : 0;
        } else {
            echo "0";
        }
        ?>
    </span>
</a></div>
    </header>
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Courses available</h1>
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
                                
                                    <?php echo htmlspecialchars($course['title']); ?>
                            
                            </div>
<button class="btn-primary" onclick="addToCartSimple(<?php echo $course['id']; ?>, this)">
    <i class="fas fa-shopping-cart"></i> Enroll Now
</button>                
                        </div>

                     
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
                    <a href="course-details.php?id=<?php echo $course['id']; ?>" style="margin-top: 10px; margin-left: 850px; color: var(--text-secondary); font-size: smaller;">learn more-></a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-book" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No courses found</h3>
                   
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
function addToCartSimple(courseId, button) {
    
    

   
    let originalHTML = button.innerHTML;
  
    
  
    let data = new URLSearchParams();
    data.append('course_id', courseId);
    data.append('add_to_cart', 'true');
    
  
    fetch('courses.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data === 'success') {
          
            let cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                let current = parseInt(cartCount.textContent) || 0;
                cartCount.textContent = current + 1;
                
            }
            

        } else {
           
            alert('Error: ' + data);
            button.innerHTML = originalHTML;
           
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Network error. Please try again.');
        button.innerHTML = originalHTML;
     
    });
}
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
        
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            tabs.forEach(tab => {
                if (tab.textContent.toLowerCase().includes(filter.toLowerCase())) {
                    tab.classList.add('active');
                }
            });
            
           
            const courseCards = document.querySelectorAll('.course-card');
            
            courseCards.forEach(card => {
                const status = card.getAttribute('data-status');
                
                if (filter === 'all' || status === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        
            
            const visibleCards = document.querySelectorAll('.course-card:not(.hidden)');
            const noCoursesMsg = document.querySelector('.no-courses-msg');
            
            if (visibleCards.length === 0) {
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