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


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
  
    $checkQuery = "SELECT id FROM users WHERE email = '$email'";
    $checkResult = $db->query($checkQuery);
    
    if ($checkResult->rowCount() > 0) {
        $error = "Email already exists!";
    } else {
       
        $insertQuery = "INSERT INTO users (first_name, last_name, email, phone, password_hash, role) 
                       VALUES ('$first_name', '$last_name', '$email', '$phone', '$password', '$role')";
        
        if ($db->query($insertQuery)) {
            $success = "User added successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error = "Failed to add user!";
        }
    }
}


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    

    if ($delete_id != $_SESSION['user_id']) {
        $deleteQuery = "DELETE FROM users WHERE id = $delete_id";
        if ($db->query($deleteQuery)) {
            $success = "User deleted successfully!";
        } else {
            $error = "Failed to delete user!";
        }
    } else {
        $error = "You cannot delete your own account!";
    }
}


if (isset($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    
    if ($user_id != $_SESSION['user_id']) {
        
        $statusQuery = "SELECT is_active FROM users WHERE id = $user_id";
        $statusResult = $db->query($statusQuery);
        $statusData = $statusResult->fetch(PDO::FETCH_ASSOC);
        
        $new_status = $statusData['is_active'] == 1 ? 0 : 1;
        
        $updateQuery = "UPDATE users SET is_active = $new_status WHERE id = $user_id";
        if ($db->query($updateQuery)) {
            $success = "User status updated successfully!";
        } else {
            $error = "Failed to update user status!";
        }
    } else {
        $error = "You cannot change your own status!";
    }
}


$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = $db->query($query);   
$users = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Master Edu</title>
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
        
        /* Form Card */
        .form-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--text-primary);
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
        
        .form-input, .form-select {
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Lusitana', serif;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--btn-bg);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-cancel:hover {
            background: var(--bg-card-hover);
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
        
        /* Users List */
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            border-color: var(--btn-bg);
        }
        
        .user-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .user-card-avatar {
            width: 50px;
            height: 50px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .user-card-info h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .user-card-info p {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .user-card-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .user-card-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .user-card-detail i {
            width: 16px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: rgba(157, 255, 87, 0.2);
            color: var(--btn-bg);
        }
        
        .role-formateur {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .role-student {
            background: rgba(255, 165, 0, 0.2);
            color: #ffa500;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .users-grid {
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
            
            <a href="users.php" class="menu-item active">
                <i class="fas fa-user-cog"></i>
                <span>User Management</span>
            </a>
            
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Add New User</h1>
                <p>Create a new user account</p>
            </div>
            <div class="header-actions">
                <a href="users.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i>
                    Back to Users
                </a>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($currentUser['first_name'] ?? 'Admin'); ?></span>
                </div>
            </div>
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
        
        <!-- Add User Form -->
        <div class="form-card">
            <h2><i class="fas fa-user-plus"></i> User Information</h2>
            <form method="POST" action="">
                <input type="hidden" name="add_user" value="1">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="formateur" <?php echo (isset($_POST['role']) && $_POST['role'] == 'formateur') ? 'selected' : ''; ?>>Teacher</option>
                            <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="users.php" class="btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Add User
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Recent Users -->
        <div class="form-card">
            <h2><i class="fas fa-users"></i> Recent Users</h2>
            <div class="users-grid">
                <?php 
                $recentUsers = array_slice($users, 0, 6);
                foreach($recentUsers as $user): 
                ?>
                <div class="user-card">
                    <div class="user-card-header">
                        <div class="user-card-avatar">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                        </div>
                        <div class="user-card-info">
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p>ID: #<?php echo $user['id']; ?></p>
                        </div>
                    </div>
                    
                    <div class="user-card-details">
                        <div class="user-card-detail">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if (!empty($user['phone'])): ?>
                        <div class="user-card-detail">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="user-card-detail">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <span class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
</body>
</html>