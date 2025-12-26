<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "webdev"; 

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        if (!empty($email) && !empty($password)) {
           
            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    
                    if ($user['role'] == 'admin') {
                        header("Location: admin/dashboard.php");
                    } else if ($user['role'] == 'formateur') {
                        header("Location: formateur/dashboard.php");
                    } else if ($user['role'] == 'assistant') {
                        header("Location: assistant/dashboard.php");
                    } else if ($user['role'] == 'commercial') {
                        header("Location: commercial/dashboard.php");
                    } else if ($user['role'] == 'director') {
                        header("Location: director/dashboard.php");
                    } else if ($user['role'] == 'marketing') {
                        header("Location: marketing/dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    $error = "Invalid email or password!";
                }
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Please fill in all fields!";
        }
    }

    elseif (isset($_POST['signup_email']) && isset($_POST['signup_password'])) {
        $email = $_POST['signup_email'];
        $password = $_POST['signup_password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'] ?? '';
    
        if (!empty($email) && !empty($password) && !empty($first_name) && !empty($last_name)) {
            
            $check_query = "SELECT id FROM users WHERE email = '$email'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = "Email already exists";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (email, password_hash, first_name, last_name, phone, created_at) 
                               VALUES ('$email', '$password_hash', '$first_name', '$last_name', '$phone', NOW())";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = "Account created successfully! Please login.";
                    // Clear form
                    $_POST['signup_email'] = '';
                    $_POST['signup_password'] = '';
                    $_POST['first_name'] = '';
                    $_POST['last_name'] = '';
                    $_POST['phone'] = '';
                } else {
                    $error = "Failed to create account: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Please fill in all required fields!";
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Lusitana:wght@400;700&display=swap" rel="stylesheet">    
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Login - Master Edu</title>
</head>
<style>
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    height: 100%;
    overflow-x: hidden;
}

.light-mode {
    --bg-primary: rgb(255, 255, 255);
    --bg-secondary: #a093d1;
    --bg-tertiary: #ffffff;
    --bg-card1: #9580bb;
    --bg-card: #a093d1;
    --bg-card-hover: #1706FA0;
    --text-primary: #240447;
    --text-secondary: #1e063d;
    --btn-bg: #9DFF57;
    --btn-text: #1f093d;
    --btn-hover: #2d0561;
    --separator-color: rgb(255, 255, 255);
    --error-bg: #ff4444;
    --error-text: #ffffff;
}

 :root {
    /* Dark mode colors */
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
    --separator-color: rgba(224, 217, 255, 0.5);
    --error-bg: #ff4444;
    --error-text: #ffffff;
}

body {
    font-family: "Lusitana", serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.3s, color 0.3s;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.container {
    width: 100%;
    max-width: 450px;
    padding: 20px;
}

.auth-card {
    background: var(--bg-card);
    border-radius: 20px;
    padding: 50px 40px;
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--separator-color);
    transition: all 0.3s;
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0px;
    margin-bottom: 40px;
}

.logo-icon {
    height: 70px;
    width: 70px;
}

.logo-text {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
}

h2 {
    text-align: center;
    margin-bottom: 35px;
    font-size: 24px;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group {
    margin-bottom: 25px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

input {
    width: 100%;
    padding: 14px 18px;
    border-radius: 8px;
    border: 1px solid var(--separator-color);
    font-size: 15px;
    transition: all 0.3s;
    outline: none;
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

input:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    border-color: var(--bg-secondary);
}

input::placeholder {
    color: var(--text-secondary);
    opacity: 0.7;
}

.btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
    font-family: "Lusitana", serif;
}

.btn-primary {
    background: var(--btn-bg);
    color: var(--btn-text);
    font-family: "Lusitana", serif;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(157, 255, 87, 0.4);
    background: var(--btn-hover);
    color: var(--text-primary);
}

.btn-secondary {
    background: transparent;
    border: 2px solid var(--separator-color);
    margin-top: 15px;
    color: var(--text-primary);
    font-family: "Lusitana", serif;
}

.btn-secondary:hover {
    background: var(--bg-card-hover);
    transform: translateY(-2px);
    border-color: var(--bg-secondary);
}

.link-text {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: var(--text-secondary);
}

.link-text a {
    color: var(--btn-bg);
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
}

.link-text a:hover {
    text-decoration: underline;
    color: var(--btn-hover);
}

.hidden {
    display: none;
}


/* Error message styling */

.error-message {
    background: var(--error-bg);
    color: var(--error-text);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
}

.goal-section {
    margin-bottom: 30px;
}

.goal-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 25px;
}

.goal-option {
    padding: 14px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
    border: 1px solid var(--separator-color);
    color: var(--text-primary);
}

.goal-option:hover {
    transform: translateX(5px);
    border-color: var(--bg-secondary);
    background: var(--bg-card-hover);
}

.goal-option.selected {
    border-color: var(--btn-bg);
    background: var(--bg-card-hover);
}
</style>

<body class="dark">

    <div class="container">
        <!-- Login Form -->
        <div class="logo">
            <img src="logo.png" class="logo-icon" alt="Master Edu Logo">
            <span class="logo-text">Master Edu</span>
        </div>
        
        <div id="loginCard" class="auth-card">
            <h2>Sign In</h2>
            
            <?php if (!empty($error) && !isset($_POST['signup_email'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="auth.php">
                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
            
            <div class="link-text">
                New to Master Edu? <a href="#" id="showSignupLink">Sign up</a>
            </div>

        </div>

        <!-- Signup Form -->
        <div id="signupCard" class="auth-card hidden">
            <h2>Create Account</h2>
            
            <?php if (!empty($error) && isset($_POST['signup_email'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form id="signupForm" method="POST" action="auth.php">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="signupEmail">Email</label>
                    <input type="email" id="signupEmail" name="signup_email" placeholder="Enter your email" required 
                           value="<?php echo htmlspecialchars($_POST['signup_email'] ?? ''); ?>">
                </div>
 <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter your phone Number (optional)" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="signupPassword">Password</label>
                    <input type="password" id="signupPassword" name="signup_password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                <input type="hidden" id="selectedGoal" name="goal" value="">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            <div class="link-text">
                Already have an account? <a href="#" id="showLoginLink">Sign in</a>
            </div>
        </div>

        <div id="goalCard" class="auth-card hidden">
            <div class="goal-section">
                <h2>What's Your Top Goal?</h2>
                <div class="goal-options">
                    <div class="goal-option">
                        <span>Excelling in school</span>
                    </div>
                    <div class="goal-option">
                        <span>Learning new skills</span>
                    </div>
                    <div class="goal-option">
                        <span>Participating in Events</span>
                    </div>
                    <div class="goal-option">
                        <span>Teaching</span>
                    </div>
                </div>
                <button class="btn btn-primary" id="completeGoalBtn">Complete Sign Up</button>
            </div>
        </div>
    </div>
     <button class="theme-toggle" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
    <script>
$(document).ready(function() {
    let selectedGoal = null;
    let formValidated = false; // Add flag to track validation


    // Show signup form
    $('#showSignupLink').on('click', function(e) {
        e.preventDefault();
        $('#loginCard').addClass('hidden');
        $('#signupCard').removeClass('hidden');
        $('#goalCard').addClass('hidden');
    });

    // Show login form
    $('#showLoginLink').on('click', function(e) {
        e.preventDefault();
        $('#signupCard').addClass('hidden');
        $('#goalCard').addClass('hidden');
        $('#loginCard').removeClass('hidden');
    });

    // Show goal selection
    function showGoalSelection() {
        $('#signupCard').addClass('hidden');
        $('#goalCard').removeClass('hidden');
    }

    // Goal selection
    $('.goal-option').on('click', function() {
        $('.goal-option').removeClass('selected');
        $(this).addClass('selected');
        selectedGoal = $(this).text().trim();
    });

    // Complete goal and submit form
    $('#completeGoalBtn').on('click', function() {
        if (selectedGoal) {
            $('#selectedGoal').val(selectedGoal);
            formValidated = true; // Set flag to bypass validation
            $('#signupForm').submit();
        } else {
            alert('Please select a goal to continue.');
        }
    });

    // Signup form validation
    $('#signupForm').on('submit', function(e) {
        // If form is already validated (coming from goal selection), allow submission
        if (formValidated) {
            formValidated = false; // Reset flag
            return true;
        }

        const password = $('#signupPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        const firstName = $('#first_name').val();
        const lastName = $('#last_name').val();
        const phone = $('#phone').val();



        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            e.preventDefault();
            return false;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters long!');
            e.preventDefault();
            return false;
        }

        if (!firstName || !lastName) {
            alert('Please fill in your first and last name!');
            e.preventDefault();
            return false;
        }

        // If validation passes, show goal selection instead of submitting immediately
        e.preventDefault();
        showGoalSelection();
        return false;
    });

    // Additional click handlers for showLogin from goal card
    $(document).on('click', '#goalCard .link-text a', function(e) {
        e.preventDefault();
        $('#signupCard').addClass('hidden');
        $('#goalCard').addClass('hidden');
        $('#loginCard').removeClass('hidden');
    });
});

   // Theme Toggle
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle.querySelector('i');
            const body = document.body;

            // Load saved theme
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

    </script>
</body>
</html>