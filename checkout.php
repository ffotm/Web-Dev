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

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && $_POST['payment_method'] === 'rib') {
    try {
        // Get form data
        $full_name = $_POST['full_name'];
        $transfer_date = $_POST['transfer_date'];
        $transaction_ref = $_POST['transaction_ref'];
        $notes = $_POST['notes'] ?? '';
        
        // Get user's cart items
        $cartQuery = "SELECT uc.*, c.title, c.price, c.description
                      FROM user_cart uc
                      JOIN courses c ON uc.course_id = c.id
                      WHERE uc.user_id = $user_id AND uc.cart_type = 'main'";
        $cartResult = $db->query($cartQuery);
        $cartItems = $cartResult->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total
        $cartTotal = 0;
        foreach($cartItems as $item) {
            $cartTotal += $item['total_price'];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Handle file upload
        $file_note = '';
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/payments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['receipt']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
                $file_note = "File uploaded: " . $fileName;
                if (!empty($notes)) {
                    $notes .= "\n" . $file_note;
                } else {
                    $notes = $file_note;
                }
            }
        }
        
        // Insert payment for each course in cart
        $payment_success = true;
        $payment_ids = [];
        
        foreach($cartItems as $item) {
            $course_id = $item['course_id'];
            $amount = $item['total_price'];
            
            
            
            // Insert into payments table
            $paymentQuery = "INSERT INTO payments (
     
                user_id, 
                course_id, 
                amount, 
                payment_date, 
                payment_method, 
                transaction_id, 
                status, 
                notes, 
                recorded_by, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($paymentQuery);
            $result = $stmt->execute([
                $user_id,
                $course_id,
                $amount,
                $transfer_date,
                'bank_transfer',
                $transaction_ref,
                'pending',
                $notes,
                $user_id
            ]);
            
            if ($result) {
                $payment_ids[] = $db->lastInsertId();
            } else {
                $payment_success = false;
                break;
            }
        }
        
        if ($payment_success) {
            $db->commit();
            $success_message = "Payment proof submitted successfully! We will verify your transfer within 24-48 hours.";
        } else {
            $db->rollBack();
            $error_message = "There was an error processing your payment. Please try again.";
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get user's cart items (after processing payment if needed)
$cartQuery = "SELECT uc.*, c.title, c.price, c.description
              FROM user_cart uc
              JOIN courses c ON uc.course_id = c.id
              WHERE uc.user_id = $user_id AND uc.cart_type = 'main'";
$cartResult = $db->query($cartQuery);
$cartItems = $cartResult->fetchAll(PDO::FETCH_ASSOC);

$cartTotal = 0;
foreach($cartItems as $item) {
    $cartTotal += $item['total_price'];
}

// Get counts for navbar
$subscriptionsQuery = "SELECT COUNT(*) as total FROM user_courses WHERE user_id = $user_id";
$subscriptionsResult = $db->query($subscriptionsQuery);
$totalSubscriptions = $subscriptionsResult->fetch()['total'];

$certsQuery = "SELECT COUNT(*) as total FROM certs_obtained WHERE user_id = $user_id";
$certsResult = $db->query($certsQuery);
$totalCertificates = $certsResult->fetch()['total'];

$cartCount = count($cartItems);

$wishlistQuery = "SELECT COUNT(*) as total FROM user_cart WHERE user_id = $user_id AND cart_type = 'wishlist'";
$wishlistResult = $db->query($wishlistQuery);
$wishlistCount = $wishlistResult->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Master Edu</title>
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
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--text-secondary);
        }
        
        /* Payment Tabs */
        .payment-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tab {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            text-align: center;
        }
        
        .tab:hover {
            border-color: var(--btn-bg);
            transform: translateY(-2px);
        }
        
        .tab.active {
            border-color: var(--btn-bg);
            background: var(--bg-card-hover);
        }
        
        .tab i {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
            color: var(--btn-bg);
        }
        
        .tab h3 {
            margin-bottom: 8px;
        }
        
        .tab p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Payment Content */
        .payment-content {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .payment-section {
            display: none;
        }
        
        .payment-section.active {
            display: block;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            background: var(--bg-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Lusitana', serif;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--btn-bg);
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Bank Info */
        .bank-info {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--btn-bg);
        }
        
        .bank-info h4 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bank-details {
            background: var(--bg-tertiary);
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .bank-details p {
            margin: 8px 0;
            font-family: monospace;
        }
        
        /* File Upload */
        .file-upload {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: var(--btn-bg);
            background: var(--bg-secondary);
        }
        
        .file-upload i {
            font-size: 48px;
            color: var(--btn-bg);
            margin-bottom: 15px;
        }
        
        .file-input {
            display: none;
        }
        
        .file-name {
            margin-top: 15px;
            color: var(--btn-bg);
            font-weight: 600;
        }
        
        /* Order Summary */
        .order-summary {
            background: var(--bg-secondary);
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .order-summary h4 {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 700;
            padding: 20px 0 0 0;
            margin-top: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
            color: var(--btn-bg);
        }
        
        /* Buttons */
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
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
        
        .security-note {
            background: rgba(46, 213, 115, 0.1);
            border: 1px solid var(--info);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: var(--info);
        }
        
        .security-note i {
            margin-right: 10px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-top: 30px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        /* Payment Icons */
        .payment-icons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .payment-icons i {
            font-size: 36px;
            color: var(--btn-bg);
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
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(46, 213, 115, 0.1);
            border: 1px solid var(--info);
            color: var(--info);
        }
        
        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        @media (max-width: 768px) {
            .payment-tabs {
                grid-template-columns: 1fr;
            }
            
            .row {
                grid-template-columns: 1fr;
            }

            nav {
                gap: 8px;
            }
            
            .nav-link span {
                display: none;
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
                            <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                            <a href="auth.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Display success/error messages -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-shopping-cart"></i> Complete Your Purchase</h1>
                <p>Choose your preferred payment method below</p>
            </div>
            
            <!-- Payment Method Tabs -->
            <div class="payment-tabs">
                <div class="tab active" onclick="showPaymentMethod('rib')">
                    <i class="fas fa-university"></i>
                    <h3>Bank Transfer (RIB)</h3>
                    <p>Transfer money to our bank account</p>
                </div>
                <div class="tab" onclick="showPaymentMethod('card')">
                    <i class="fas fa-credit-card"></i>
                    <h3>Credit Card</h3>
                    <p>Pay with Visa/Mastercard</p>
                </div>
            </div>
            
            <!-- Payment Content -->
            <div class="payment-content">
                <!-- RIB Transfer Section -->
                <div id="rib-section" class="payment-section active">
                    <h2 class="section-title">Bank Transfer Payment</h2>
                    
                    <div class="bank-info">
                        <h4><i class="fas fa-info-circle"></i> Transfer Instructions</h4>
                        <p>Please transfer the total amount to the following bank account:</p>
                        
                        <div class="bank-details">
                            <p><strong>Bank:</strong> BNA (Banque Nationale d'Algérie)</p>
                            <p><strong>Account Holder:</strong> Master Edu Formation</p>
                            <p><strong>RIB:</strong> 001 23456 78910111213 45</p>
                            <p><strong>SWIFT/BIC:</strong> BNADDZALXXX</p>
                            <p><strong>Amount:</strong> da <?php echo number_format($cartTotal, 2); ?></p>
                        </div>
                        
                        <p style="margin-top: 15px; color: var(--danger);">
                            <i class="fas fa-exclamation-circle"></i> 
                            <strong>Important:</strong> Use your name as the payment reference
                        </p>
                    </div>
                    
                    <form id="ribForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="payment_method" value="rib">
                        
                        <div class="form-group">
                            <label>Your Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" required 
                                   value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="form-group">
                                <label>Transfer Date <span class="required">*</span></label>
                                <input type="date" name="transfer_date" required 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Transaction Reference <span class="required">*</span></label>
                                <input type="text" name="transaction_ref" required 
                                       placeholder="e.g., TRX-123456789">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Payment Proof <span class="required">*</span></label>
                            <div class="file-upload" onclick="document.getElementById('receiptFile').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload payment receipt or screenshot</p>
                                <p><small>Accepted: JPG, PNG, PDF (Max: 5MB)</small></p>
                                <div id="fileName" class="file-name"></div>
                                <input type="file" id="receiptFile" class="file-input" name="receipt"
                                       accept=".jpg,.jpeg,.png,.pdf" onchange="displayFileName(this)">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Additional Notes (Optional)</label>
                            <textarea name="notes" placeholder="Any additional information about your payment..."></textarea>
                        </div>
                        
                        <div class="order-summary">
                            <h4>Order Summary</h4>
                            <?php foreach($cartItems as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['title']); ?></span>
                                <span>da <?php echo number_format($item['total_price'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="summary-total">
                                <span>Total Amount:</span>
                                <span>da <?php echo number_format($cartTotal, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="security-note">
                            <i class="fas fa-shield-alt"></i>
                            Your payment details are secure. We'll verify your transfer within 24-48 hours.
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Payment Proof
                        </button>
                    </form>
                </div>
                
                <!-- Credit Card Section -->
                <div id="card-section" class="payment-section">
                    <h2 class="section-title">Credit Card Payment</h2>
                    
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                    </div>
                    
                    <form id="cardForm">
                        <div class="form-group">
                            <label>Cardholder Name <span class="required">*</span></label>
                            <input type="text" name="cardholder_name" required 
                                   value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Card Number <span class="required">*</span></label>
                            <input type="text" name="card_number" required 
                                   placeholder="1234 5678 9012 3456"
                                   maxlength="19">
                        </div>
                        
                        <div class="row">
                            <div class="form-group">
                                <label>Expiry Date <span class="required">*</span></label>
                                <input type="text" name="expiry" required 
                                       placeholder="MM/YY"
                                       maxlength="5">
                            </div>
                            
                            <div class="form-group">
                                <label>CVV <span class="required">*</span></label>
                                <input type="text" name="cvv" required 
                                       placeholder="123"
                                       maxlength="3">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Billing Address <span class="required">*</span></label>
                            <input type="text" name="billing_address" required 
                                   placeholder="Street, City, Postal Code">
                        </div>
                        
                        <div class="order-summary">
                            <h4>Order Summary</h4>
                            <?php foreach($cartItems as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['title']); ?></span>
                                <span>da <?php echo number_format($item['total_price'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="summary-total">
                                <span>Total Amount:</span>
                                <span>da <?php echo number_format($cartTotal, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="security-note">
                            <i class="fas fa-lock"></i>
                            Your payment is secured with 256-bit SSL encryption.
                        </div>
                        
                        <button type="button" class="submit-btn" onclick="processCardPayment()">
                            <i class="fas fa-lock"></i> Pay Now - da <?php echo number_format($cartTotal, 2); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <a href="cart.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
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

        // User menu
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

        // Switch payment methods
        function showPaymentMethod(method) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            if (method === 'rib') {
                document.querySelector('.tab:nth-child(1)').classList.add('active');
                document.getElementById('rib-section').classList.add('active');
                document.getElementById('card-section').classList.remove('active');
            } else {
                document.querySelector('.tab:nth-child(2)').classList.add('active');
                document.getElementById('card-section').classList.add('active');
                document.getElementById('rib-section').classList.remove('active');
            }
        }
        
        // Display file name
        function displayFileName(input) {
            if (input.files.length > 0) {
                document.getElementById('fileName').textContent = input.files[0].name;
            }
        }
        
        // Process card payment (demo)
        function processCardPayment() {
            alert('This is a demo. Credit card payments are not implemented yet.');
        }
        
        // Format card number
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumberInput = document.querySelector('input[name="card_number"]');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formatted = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formatted += ' ';
                        }
                        formatted += value[i];
                    }
                    e.target.value = formatted.substring(0, 19);
                });
            }
            
            // Format expiry
            const expiryInput = document.querySelector('input[name="expiry"]');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value.substring(0, 5);
                });
            }
        });
    </script>
</body>
</html>