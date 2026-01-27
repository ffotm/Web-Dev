<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'student';
$last_name = $_SESSION['last_name'] ?? 'User';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    
    if (!isset($_POST['course_id'])) {
        echo "no_course_id";
        exit();
    }
    
    $course_id = $_POST['course_id'];
    
    // Get course price
    $priceQuery = "SELECT price FROM courses WHERE id = $course_id";
    $priceResult = $db->query($priceQuery);
    $coursePrice = $priceResult->fetch()['price'];
    
    // Check if course already in cart
    $check_query = "SELECT id, quantity FROM user_cart WHERE user_id = '$user_id' AND course_id = '$course_id' AND cart_type = 'main'";
    $check_result = $db->query($check_query);
    
    if ($check_result->rowCount() > 0) {
        // Update quantity
        $cart_item = $check_result->fetch();
        $new_quantity = $cart_item['quantity'] + 1;
        $new_total = $coursePrice * $new_quantity;
        
        $update_query = "UPDATE user_cart 
                        SET quantity = '$new_quantity',
                            total_price = '$new_total',
                            updated_at = NOW() 
                        WHERE user_id = '$user_id' AND course_id = '$course_id' AND cart_type = 'main'";
        $db->query($update_query);
        echo "success";
    } else {
        // Add new item
        $total_price = $coursePrice;
        $insert_query = "INSERT INTO user_cart (user_id, course_id, quantity, total_price, cart_type) 
                         VALUES ('$user_id', '$course_id', 1, '$total_price', 'main')";
        $db->query($insert_query);
        echo "success";
    }
    
    exit();
}

// Get user's subscriptions count
$subscriptionsQuery = "SELECT COUNT(*) as total FROM user_courses WHERE user_id = $user_id";
$subscriptionsResult = $db->query($subscriptionsQuery);
$totalSubscriptions = $subscriptionsResult->fetch()['total'];

// Get certificates count
$certsQuery = "SELECT COUNT(*) as total FROM certs_obtained WHERE user_id = $user_id";
$certsResult = $db->query($certsQuery);
$totalCertificates = $certsResult->fetch()['total'];

// Get cart items count
$cartQuery = "SELECT COUNT(*) as total FROM user_cart WHERE user_id = $user_id AND cart_type = 'main'";
$cartResult = $db->query($cartQuery);
$cartCount = $cartResult->fetch()['total'];

// Get wishlist count
$wishlistQuery = "SELECT COUNT(*) as total FROM user_cart WHERE user_id = $user_id AND cart_type = 'wishlist'";
$wishlistResult = $db->query($wishlistQuery);
$wishlistCount = $wishlistResult->fetch()['total'];

// Get user role
$query = "SELECT role FROM users WHERE id = $user_id";
$result = $db->query($query);
$user_role = $result->fetch()['role'];
$is_admin = $user_role === 'admin';

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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
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

        /* Navigation Links */
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
        
        .nav-link i {
            font-size: 16px;
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
            min-width: 18px;
            text-align: center;
        }

        /* User Menu */
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

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
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
            font-family: "Lusitana", serif;
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
            font-family: "Lusitana", serif;
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

        .btn-enroll {
            background: var(--btn-bg);
            color: var(--btn-text);
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: "Lusitana", serif;
        }

        .btn-enroll:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }

        .btn-enroll:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
            nav {
                gap: 8px;
                flex-wrap: wrap;
            }
            
            .nav-link span {
                display: none;
            }
            
            .course-details {
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
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Available Courses</h1>
                <?php if ($is_admin): ?>
                <a href="add-course.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Course
                </a>
                <?php endif; ?>
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
                <div class="tab active" onclick="filterCourses('upcoming')">Upcoming</div>
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
                        ?>
                        
                        <div class="course-card" data-status="<?php echo $status; ?>">
                            <!-- Status Badge -->
                            <div class="course-status-badge status-<?php echo $status; ?>">
                                <?php echo ucfirst($status); ?>
                            </div>

                            <!-- Course Header -->
                            <div class="course-header">
                                <div class="course-title">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </div>
                                <button class="btn-enroll" onclick="addToCart(<?php echo $course['id']; ?>, this)">
                                    <i class="fas fa-shopping-cart"></i> Enroll Now
                                </button>                
                            </div>

                            <!-- Course Description -->
                            <div class="course-description">
                                <?php echo htmlspecialchars($course['description']); ?>
                            </div>

                            <!-- Course Details -->
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
                            
                            <a href="course-details.php?id=<?php echo $course['id']; ?>" 
                               style="display: block; margin-top: 10px; text-align: right; color: var(--text-secondary); font-size: 13px; text-decoration: none;">
                                Learn more →
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
                        <i class="fas fa-book" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>No courses found</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
  
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        // Add to Cart Function
        function addToCart(courseId, button) {
            let originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
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
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'success') {
                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    
                    // Update cart count
                    let cartBadge = document.querySelector('.cart-count');
                    if (cartBadge) {
                        let current = parseInt(cartBadge.textContent) || 0;
                        cartBadge.textContent = current + 1;
                    }
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 2000);
                } else {
                    alert('Error: ' + data);
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }

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

        // Filter Courses
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
                
                if (status === filter) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // User Menu Dropdown
        document.getElementById('userMenuBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('dropdownMenu');
            dropdown.classList.toggle('active');
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