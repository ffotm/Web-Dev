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
$last_name = $_SESSION['last_name'] ?? 'User';

// Get user's counts for navbar
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





$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : NULL;
    $rating_cmnt = $_POST['rating_cmnt'];
    $stars = $_POST['stars'];
    
    $insertQuery = "INSERT INTO ratings (user_id, course_id, rating_cmnt, stars, is_approved) 
                    VALUES (:user_id, :course_id, :rating_cmnt, :stars, 0)";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->bindParam(':rating_cmnt', $rating_cmnt);
    $stmt->bindParam(':stars', $stars);
    
    if ($stmt->execute()) {
        $success = "Thank you for your feedback! Your review is pending approval.";
    } else {
        $error = "Failed to submit feedback. Please try again.";
    }
}


$coursesQuery = "SELECT DISTINCT c.id, c.title 
                 FROM user_courses uc
                 JOIN courses c ON uc.course_id = c.id
                 WHERE uc.user_id = $user_id
                 ORDER BY c.title";
$coursesResult = $db->query($coursesQuery);
$enrolledCourses = $coursesResult->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Contact - Master Edu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">
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
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }

        /* Tabs */
        .feedback-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tab {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: var(--bg-card-hover);
            transform: translateY(-2px);
        }
        
        .tab.active {
            border-color: var(--btn-bg);
            background: var(--bg-card-hover);
        }
        
        .tab i {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
            color: var(--btn-bg);
        }
        
        .tab h3 {
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 20px;
        }

        .tab p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* Feedback Content */
        .feedback-content {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feedback-section {
            display: none;
        }
        
        .feedback-section.active {
            display: block;
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

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-primary);
            font-family: 'Lusitana', serif;
            transition: all 0.3s;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--btn-bg);
        }

        /* Star Rating */
        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            color: var(--bg-secondary);
            transition: color 0.2s;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffa502;
        }
        
        .star-rating {
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 25px;
            transition: all 0.3s;
            font-family: 'Lusitana', serif;
        }
        
        .submit-btn:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }
        
        .submit-btn i {
            margin-right: 10px;
        }

        /* Contact Info */
        .contact-info {
            background: var(--bg-secondary);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-info h3 {
            margin-bottom: 20px;
            font-size: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .contact-item i {
            font-size: 24px;
            color: var(--btn-bg);
            width: 30px;
            text-align: center;
        }

        .contact-item div {
            flex: 1;
        }

        .contact-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .contact-item span {
            color: var(--text-secondary);
            font-size: 14px;
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

            .feedback-tabs {
                grid-template-columns: 1fr;
            }

            .feedback-content {
                padding: 25px;
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
            <div class="page-header">
                <h1><i class="fas fa-comment-dots"></i> Feedback & Contact</h1>
                <p>Share your experience or get in touch with us</p>
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

            <!-- Feedback Tabs -->
            <div class="feedback-tabs">
                <div class="tab active" onclick="showSection('course')">
                    <i class="fas fa-star"></i>
                    <h3>Course Feedback</h3>
                    <p>Rate and review your courses</p>
                </div>
                <div class="tab" onclick="showSection('contact')">
                    <i class="fas fa-envelope"></i>
                    <h3>Contact Us</h3>
                    <p>Get in touch with our team</p>
                </div>
            </div>

            <!-- Feedback Content -->
            <div class="feedback-content">
                <!-- Course Feedback Section -->
                <div id="course-section" class="feedback-section active">
                    <h2 style="margin-bottom: 25px; font-size: 24px;">Leave a Course Review</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="submit_feedback" value="1">
                        
                        <div class="form-group">
                            <label>Select Course (Optional)</label>
                            <select name="course_id">
                                <option value="">General Feedback</option>
                                <?php foreach($enrolledCourses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Rating <span class="required">*</span></label>
                            <div class="star-rating">
                                <input type="radio" name="stars" value="5" id="star5" required>
                                <label for="star5"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="stars" value="4" id="star4">
                                <label for="star4"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="stars" value="3" id="star3">
                                <label for="star3"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="stars" value="2" id="star2">
                                <label for="star2"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" name="stars" value="1" id="star1">
                                <label for="star1"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Review <span class="required">*</span></label>
                            <textarea name="rating_cmnt" required placeholder="Share your experience with us..."></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </form>
                </div>

                <!-- Contact Section -->
                <div id="contact-section" class="feedback-section">
                    <h2 style="margin-bottom: 25px; font-size: 24px;">Get in Touch</h2>
                    
                    <div class="contact-info">
                        <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong>
                                <span>info@masteredu.com</span>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone</strong>
                                <span>+213 123 456 789</span>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Address</strong>
                                <span>123 Education Street, Algiers, Algeria</span>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Business Hours</strong>
                                <span>Sunday - Thursday: 9:00 AM - 6:00 PM</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Your Name <span class="required">*</span></label>
                            <input type="text" name="contact_name" required 
                                   value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="contact_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="billing">Billing Question</option>
                                <option value="course">Course Information</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Message <span class="required">*</span></label>
                            <textarea name="message" required placeholder="How can we help you?"></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
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

        // User Menu
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

        // Section Switch
        function showSection(section) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            if (section === 'course') {
                document.querySelector('.tab:nth-child(1)').classList.add('active');
                document.getElementById('course-section').classList.add('active');
                document.getElementById('contact-section').classList.remove('active');
            } else {
                document.querySelector('.tab:nth-child(2)').classList.add('active');
                document.getElementById('contact-section').classList.add('active');
                document.getElementById('course-section').classList.remove('active');
            }
        }
    </script>
</body>
</html>