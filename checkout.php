<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$first_name = $_SESSION['first_name'] ?? 'User';
$last_name = $_SESSION['last_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Master Edu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        /* Payment Method Tabs */
        .payment-tabs {
            display: flex;
            margin-bottom: 30px;
            background: var(--bg-card);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: var(--bg-card-hover);
        }
        
        .tab.active {
            background: var(--bg-card-hover);
            border-bottom: 3px solid var(--bg-card1);
        }
        
        .tab i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        
        .tab h3 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .payment-content {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .payment-section {
            display: none;
        }
        
        .payment-section.active {
            display: block;
        }
        
        .section-title {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
            color: var(--text-primary);

        }
input, select, textarea {
background-color: var(--text-primary);
}
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-primary);
        }
        
        .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--text-primary);
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--bg-card);
            box-shadow: 0 0 5px var(--bg-card-hover);
        }
        
        .row {
            display: flex;
            gap: 15px;
        }
        
        .row .form-group {
            flex: 1;
        }
        
        /* RIB Section */
        .rib-info {
            background: var(--bg-card1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .rib-info h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        
        .bank-details {
            font-family: monospace;
            background: var(--bg-card);
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid var(--bg-card);
        }
        
        .bank-details p {
            margin: 8px 0;
        }
        
        .file-upload {
            border: 2px dashed var(--text-primary);
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: var(--bg-card);
            background: var(--bg-card-hover);
        }
        
        .file-upload i {
            font-size: 40px;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .file-input {
            display: none;
        }
        
        .file-name {
            margin-top: 10px;
            color: var(--bg-card);
            font-weight: bold;
        }
        
        /* Credit Card Section */
        .payment-icons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .payment-icons i {
            font-size: 30px;
        }
        
        .payment-icons .fa-cc-visa { color: var(--bg-card1); }
        .payment-icons .fa-cc-mastercard { color: var(--bg-card1); }
        
        /* Order Summary */
        .order-summary {
            background: var(--bg-card1);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-total {
            font-size: 22px;
            font-weight: bold;
            padding: 15px 0;
            color: var(--text-primary);
            border-top: 2px solid #ddd;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--btn-bg);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: var(--btn-hover);
        }
        
        .submit-btn i {
            margin-right: 10px;
        }
        
        .security-note {
            background: var(--bg-card1);
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
            color: #2e7d32;
        }
        
        .security-note i {
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .payment-tabs {
                flex-direction: column;
            }
            
            .row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> Complete Your Purchase</h1>
            <p>Choose your preferred payment method</p>
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
                
                <div class="rib-info">
                    <h4><i class="fas fa-info-circle"></i> Transfer Instructions</h4>
                    <p>Please transfer the total amount to the following bank account:</p>
                    
                    <div class="bank-details">
                        <p><strong>Bank:</strong> BNA (Banque Nationale d'Algérie)</p>
                        <p><strong>Account Holder:</strong> Master Edu Formation</p>
                        <p><strong>RIB:</strong> 001 23456 78910111213 45</p>
                        <p><strong>SWIFT/BIC:</strong> BNADDZALXXX</p>
                        <p><strong>Amount:</strong> 15,000 DZD</p>
                    </div>
                    
                    <p style="margin-top: 15px; color: #e74c3c;">
                        <i class="fas fa-exclamation-circle"></i> 
                        <strong>Important:</strong> Use your name as the payment reference
                    </p>
                </div>
                
                <form id="ribForm">
                    <div class="form-group">
                        <label>Your Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" required 
                               value="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Transfer Date <span class="required">*</span></label>
                        <input type="date" name="transfer_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Transaction Reference/Number <span class="required">*</span></label>
                        <input type="text" name="transaction_ref" required 
                               placeholder="e.g., TRX-123456789">
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Payment Proof <span class="required">*</span></label>
                        <div class="file-upload" onclick="document.getElementById('receiptFile').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload payment receipt or screenshot</p>
                            <p><small>Accepted: JPG, PNG, PDF (Max: 5MB)</small></p>
                            <div id="fileName" class="file-name"></div>
                            <input type="file" id="receiptFile" class="file-input" 
                                   accept=".jpg,.jpeg,.png,.pdf" onchange="displayFileName(this)">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" placeholder="Any additional information about your payment..."></textarea>
                    </div>
                    
                    <div class="order-summary">
                        <h4>Order Summary</h4>
                        <div class="order-item">
                            <span>Web Development Course</span>
                            <span>15,000 DZD</span>
                        </div>
                        <div class="summary-total">
                            <span>Total Amount:</span>
                            <span>15,000 DZD</span>
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
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
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
                        <div class="order-item">
                            <span>Web Development Course</span>
                            <span>15,000 DZD</span>
                        </div>
                        <div class="summary-total">
                            <span>Total Amount:</span>
                            <span>15,000 DZD</span>
                        </div>
                    </div>
                    
                    <div class="security-note">
                        <i class="fas fa-lock"></i>
                        Your payment is secured with 256-bit SSL encryption. We don't store your card details.
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-lock"></i> Pay Now - 15,000 DZD
                    </button>
                </form>
            </div>
        </div>
        
        <a href="cart.php" class="back-link" style="display: block; text-align: center; margin-top: 20px;">
            <i class="fas fa-arrow-left"></i> Back to Cart
        </a>
    </div>

    <script>
        // Switch between payment methods
        function showPaymentMethod(method) {
            // Update tabs
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
        
        // Display selected file name
        function displayFileName(input) {
            if (input.files.length > 0) {
                document.getElementById('fileName').textContent = input.files[0].name;
            }
        }
        
        // Format card number with spaces
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
            
            // Format expiry date
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
            
            // Form submission
            document.getElementById('ribForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Payment proof submitted! We will verify your bank transfer within 24-48 hours.');
                // In real app: submit to server
            });
            
            document.getElementById('cardForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Payment successful! Thank you for your purchase.');
                // In real app: process payment
            });
        });
    </script>
</body>
</html>