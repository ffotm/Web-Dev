<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';

// Get cart items for current user
$cart_query = "SELECT 
                uc.*, 
                c.title, 
                c.description, 
                c.price,
    
                CONCAT(u.first_name, ' ', u.last_name) as teacher_name
              FROM user_cart uc
              LEFT JOIN courses c ON uc.course_id = c.id
              LEFT JOIN users u ON c.formateur_id = u.id
              WHERE uc.user_id = '$user_id'
              ORDER BY uc.added_at DESC";

$cart_result = $db->query($cart_query);
$cart_items = $cart_result->fetchAll();

// Calculate total
$total_query = "SELECT SUM(c.price * uc.quantity) as total 
                FROM user_cart uc
                LEFT JOIN courses c ON uc.course_id = c.id
                WHERE uc.user_id = '$user_id'";

$total_result = $db->query($total_query);
$total_data = $total_result->fetch();
$cart_total = $total_data['total'] ?? 0;

// Handle remove item
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $item_id = $_GET['remove'];
    $db->query("DELETE FROM user_cart WHERE id = '$item_id' AND user_id = '$user_id'");
    header("Location: cart.php");
    exit;
}

// Handle update quantity
if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = max(1, intval($_POST['quantity'])); // At least 1
    
    $db->query("UPDATE user_cart SET quantity = '$quantity' WHERE id = '$item_id' AND user_id = '$user_id'");
    header("Location: cart.php");
    exit;
}

// Handle clear cart
if (isset($_POST['clear_cart'])) {
    $db->query("DELETE FROM user_cart WHERE user_id = '$user_id'");
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>My Cart - Master Edu</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        
        /* Main Content */
        .main-content {
            background: var(--bg-secondary);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--bg-card-hover);
        }
        
        .page-header h1 {
            font-size: 28px;
            color: var(--text-primary);
        }
        
        /* Cart Items */
        .cart-empty {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        
        .cart-empty i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .cart-empty h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .cart-empty p {
            margin-bottom: 20px;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-success {
            background: var(--btn-bg);
            color: white;
        }
        
        /* Cart Items Table */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
background: var(--bg-secondary);
        }
        
        .cart-table th {
            background: var(--bg-secondary);
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: var(--text-primary);
            border-bottom: 2px solid var(--bg-card-hover);
        }
        
        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid var(--bg-card-hover);
            vertical-align: top;
        }
        
        .course-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .course-image {
            width: 80px;
            height: 60px;
            background: var(--bg-tertiary);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        
        .course-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .course-teacher {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid var(--bg-primary);
            border-radius: 4px;
            text-align: center;
            background-color: var(--bg-card-hover);
        }
        
        .update-btn {
            padding: 5px 10px;
            background: var(--btn-bg);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .price {
            font-weight: bold;
            color: var(--text-primary);
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 18px;
        }
        
        /* Cart Summary */
        .cart-summary {
            
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--bg-card-hover);
        }
        
        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            border-bottom: none;
            padding-top: 20px;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--bg-card-hover);
        }
        
      
        
        @media (max-width: 768px) {
            .cart-table {
                display: block;
                overflow-x: auto;
            }
            
            .course-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .cart-actions .btn {
                width: 100%;
                text-align: center;
            }
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
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1>My Shopping Cart</h1>
                <?php if (count($cart_items) > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to clear your cart?');">
                        <button type="submit" name="clear_cart" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (count($cart_items) == 0): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <a href="courses.php" class="btn btn-primary">
                        <i class="fas fa-book"></i> Browse Courses
                    </a>
                </div>
            <?php else: ?>
                <!-- Cart Items Table -->
                <table class="cart-table" >
                    <thead >
                        <tr>
                            <th>Course</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): 
                            $item_total = ($item['price'] ?? 0) * $item['quantity'];
                        ?>
                            <tr>
                                <td>
                                    <div class="course-info">
                                        <div class="course-image">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Course Image" style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                <i class="fas fa-book"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="course-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="course-teacher">
                                                <?php echo htmlspecialchars($item['teacher_name'] ?? 'Not assigned'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="price">
                                    da <?php echo number_format($item['price'] ?? 0, 2); ?>
                                </td>
                                <td>
                                    <form method="POST" class="quantity-control">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" 
                                               name="quantity" 
                                               class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1" 
                                               max="10">
                                            
                                        </button>
                                    </form>
                                </td>
                                <td class="price">
                                    da <?php echo number_format($item_total, 2); ?>
                                </td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                       class="remove-btn"
                                       onclick="return confirm('Remove this item from cart?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Cart Summary -->
                <div class="cart-summary">
                   
                  
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>da <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                </div>

                <!-- Cart Actions -->
                <div class="cart-actions">
                    <a href="courses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="checkout.php" class="btn btn-success">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    

    <script>
      
        
        // Update quantity on input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>