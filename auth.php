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
    <link href="login.css" rel="stylesheet"> 
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Login - Master Edu</title>
</head>
<body class="dark">
    <button class="theme-toggle" id="themeToggle">
        <span id="theme-icon">light mode</span>
    </button>

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

    <script>
$(document).ready(function() {
    let selectedGoal = null;
    let formValidated = false; // Add flag to track validation

    // Theme toggle
    $('#themeToggle').on('click', function() {
        const body = $('body');
        const themeIcon = $('#theme-icon');
        const button = $(this);

        if (body.hasClass('dark')) {
            body.removeClass('dark').addClass('light light-mode');
            button.find('span').text(' Dark Mode');
        } else {
            body.removeClass('light light-mode').addClass('dark');
            button.find('span').text(' Light Mode');
        }
    });

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
    </script>
</body>
</html>