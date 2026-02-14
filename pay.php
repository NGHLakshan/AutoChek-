<?php
session_start();
require_once 'db.php';

// Check if buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    die("Access Denied");
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$buyer_id = $_SESSION['user_id'];
$message = "";

// Security: Check if booking is valid and approved
$check = $conn->query("SELECT * FROM booking WHERE booking_id = $booking_id AND buyer_id = $buyer_id");
if ($check->num_rows == 0) {
    die("Booking not found.");
}
$booking = $check->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $booking['package_price']; 
    $method = $_POST['method']; // Card, Cash, etc.

    // Insert into payment table
    $stmt = $conn->prepare("INSERT INTO payment (booking_id, buyer_id, amount, method, payment_date) VALUES (?, ?, ?, ?, CURRENT_DATE)");
    $stmt->bind_param("iids", $booking_id, $buyer_id, $amount, $method);

    if ($stmt->execute()) {
        $message = "<div class='alert-success'><i class='ph ph-check-circle'></i> Payment Successful! Your booking is confirmed. <a href='dashboard.php'>Back to Dashboard</a></div>";
    } else {
        $message = "<div class='alert-error'><i class='ph ph-x-circle'></i> Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Inspection Fee | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #64748b;
            --text-dark: #1e293b;
            --bg-body: #f8fafc;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        .payment-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }

        /* Card Display Section */
        .card-display-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .credit-card {
            width: 100%;
            max-width: 420px;
            height: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .credit-card:hover {
            transform: translateY(-10px) rotateX(5deg);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }

        .credit-card.flipped {
            transform: rotateY(180deg);
        }

        .card-front, .card-back {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 30px;
            backface-visibility: hidden;
            border-radius: 20px;
        }

        .card-back {
            transform: rotateY(180deg);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-chip {
            width: 50px;
            height: 40px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .card-chip::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            right: 5px;
            bottom: 5px;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,0.1) 2px,
                rgba(0,0,0,0.1) 4px
            );
        }

        .card-contactless {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 2rem;
            opacity: 0.6;
        }

        .card-number {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 3px;
            margin: 30px 0 20px;
            font-family: 'Courier New', monospace;
        }

        .card-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .card-holder, .card-expiry {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .card-label {
            font-size: 0.7rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-value {
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .card-brand {
            position: absolute;
            bottom: 30px;
            right: 30px;
            font-size: 3rem;
            opacity: 0.9;
        }

        /* Card Back */
        .magnetic-strip {
            width: 100%;
            height: 50px;
            background: #000;
            margin: 20px 0 30px;
        }

        .cvv-section {
            background: white;
            padding: 10px 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .cvv-label {
            color: #1e293b;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .cvv-value {
            color: #1e293b;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }

        /* Booking Summary Card */
        .booking-summary {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .booking-summary h3 {
            margin: 0 0 20px 0;
            color: var(--text-dark);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid var(--primary);
        }

        .summary-label {
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .summary-value {
            color: var(--text-dark);
            font-weight: 600;
        }

        .summary-total {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
        }

        /* Payment Form Section */
        .payment-form-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .payment-form-section h2 {
            margin: 0 0 30px 0;
            color: var(--text-dark);
            font-size: 1.8rem;
        }

        .payment-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 0;
        }

        .payment-tab {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: var(--secondary);
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .payment-tab:hover {
            color: var(--primary);
        }

        .payment-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-pay {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-pay:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .alert-success {
            background: white;
            color: var(--primary-dark);
            padding: 20px 24px;
            border-radius: 16px;
            margin: 0 auto 30px;
            max-width: 600px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 2px solid var(--primary);
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
        }

        .alert-success i {
            font-size: 1.8rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .alert-success a {
            color: var(--primary);
            text-decoration: underline;
            font-weight: 700;
        }

        .alert-error {
            background: white;
            color: #991b1b;
            padding: 20px 24px;
            border-radius: 16px;
            margin: 0 auto 30px;
            max-width: 600px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 2px solid #ef4444;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2);
        }

        .alert-error i {
            font-size: 1.8rem;
            color: #ef4444;
            flex-shrink: 0;
        }

        .other-methods {
            padding: 40px;
            text-align: center;
            color: var(--secondary);
        }

        .other-methods i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .credit-card {
                max-width: 100%;
                height: 220px;
                padding: 24px;
            }

            .card-number {
                font-size: 1.2rem;
            }

            .payment-form-section {
                padding: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .payment-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .payment-tab {
                white-space: nowrap;
                padding: 12px 16px;
                font-size: 0.85rem;
            }
        }

        /* Card Type Gradients */
        .credit-card.visa {
            background: linear-gradient(135deg, #1434CB 0%, #2E5CFF 100%);
        }

        .credit-card.mastercard {
            background: linear-gradient(135deg, #EB001B 0%, #F79E1B 100%);
        }

        .credit-card.amex {
            background: linear-gradient(135deg, #006FCF 0%, #00A3E0 100%);
        }

        .credit-card.default {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="payment-container">
        <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Complete Your Payment</h2>
        
        <?php echo $message; ?>

        <?php if (empty($message)): ?>
        <div class="payment-grid">
            <!-- Left Column: Card Display & Summary -->
            <div class="card-display-section">
                <!-- 3D Credit Card -->
                <div class="credit-card default" id="credit-card">
                    <div class="card-front">
                        <div class="card-chip"></div>
                        <i class="ph ph-broadcast card-contactless"></i>
                        
                        <div class="card-number" id="card-display-number">•••• •••• •••• ••••</div>
                        
                        <div class="card-details">
                            <div class="card-holder">
                                <div class="card-label">Card Holder</div>
                                <div class="card-value" id="card-display-name">YOUR NAME</div>
                            </div>
                            <div class="card-expiry">
                                <div class="card-label">Expires</div>
                                <div class="card-value" id="card-display-expiry">MM/YY</div>
                            </div>
                        </div>
                        
                        <i class="ph ph-credit-card card-brand" id="card-brand-icon"></i>
                    </div>
                    
                    <div class="card-back">
                        <div class="magnetic-strip"></div>
                        <div class="cvv-section">
                            <span class="cvv-label">CVV</span>
                            <span class="cvv-value" id="card-display-cvv">•••</span>
                        </div>
                        <div style="color: white; font-size: 0.7rem; opacity: 0.6; text-align: center;">
                            This card is property of AutoChek Payment Systems
                        </div>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="booking-summary">
                    <h3><i class="ph ph-receipt"></i> Booking Summary</h3>
                    <div class="summary-item">
                        <span class="summary-label">Package</span>
                        <span class="summary-value"><?php echo htmlspecialchars($booking['package_name'] ?? 'Standard'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Vehicle</span>
                        <span class="summary-value"><?php echo htmlspecialchars($booking['vehicle_type']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Service Type</span>
                        <span class="summary-value"><?php echo htmlspecialchars($booking['service_type'] ?? 'Physical'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Date</span>
                        <span class="summary-value"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Amount</span>
                        <span class="summary-total">LKR <?php echo number_format($booking['package_price'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Payment Form -->
            <div class="payment-form-section">
                <h2>Payment Details</h2>

                <!-- Payment Method Tabs -->
                <div class="payment-tabs">
                    <button class="payment-tab active" data-tab="card">
                        <i class="ph ph-credit-card"></i> Card Payment
                    </button>
                    <button class="payment-tab" data-tab="bank">
                        <i class="ph ph-bank"></i> Bank Transfer
                    </button>
                    <button class="payment-tab" data-tab="cash">
                        <i class="ph ph-money"></i> Cash
                    </button>
                </div>

                <!-- Card Payment Form -->
                <div class="tab-content active" id="card-tab">
                    <form method="POST" id="payment-form">
                        <input type="hidden" name="method" value="Credit Card">
                        
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>

                        <div class="form-group">
                            <label>Cardholder Name</label>
                            <input type="text" id="card-name" placeholder="JOHN DOE" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" id="card-cvv" placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-pay">
                            <i class="ph ph-lock-simple"></i>
                            Pay LKR <?php echo number_format($booking['package_price'], 2); ?>
                        </button>
                    </form>
                </div>

                <!-- Bank Transfer -->
                <div class="tab-content" id="bank-tab">
                    <div class="other-methods">
                        <i class="ph ph-bank"></i>
                        <h3>Bank Transfer</h3>
                        <p>Coming soon! This payment method will be available shortly.</p>
                    </div>
                </div>

                <!-- Cash Payment -->
                <div class="tab-content" id="cash-tab">
                    <form method="POST" style="max-width: 500px; margin: 0 auto;">
                        <input type="hidden" name="method" value="Cash at Location">
                        
                        <div class="cash-payment-info">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <i class="ph ph-money" style="font-size: 4rem; color: var(--primary);"></i>
                                <h3 style="margin: 16px 0 8px; color: var(--text-dark);">Pay Cash at Inspection</h3>
                                <p style="color: var(--text-muted); font-size: 0.95rem;">You'll pay the expert directly at the inspection location</p>
                            </div>

                            <div style="background: var(--bg-light); padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
                                <h4 style="margin: 0 0 16px; color: var(--text-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="ph ph-info"></i> Payment Details
                                </h4>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                    <span style="color: var(--text-muted);">Amount to Pay:</span>
                                    <strong style="color: var(--text-dark); font-size: 1.1rem;">LKR <?php echo number_format($booking['package_price'], 2); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                    <span style="color: var(--text-muted);">Payment Method:</span>
                                    <strong style="color: var(--primary);">Cash</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">When:</span>
                                    <strong style="color: var(--text-dark);">At Inspection</strong>
                                </div>
                            </div>

                            <div style="background: #fef3c7; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fbbf24;">
                                <h4 style="margin: 0 0 12px; color: #92400e; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="ph ph-warning"></i> Important Notes
                                </h4>
                                <ul style="margin: 0; padding-left: 20px; color: #92400e; font-size: 0.9rem; line-height: 1.6;">
                                    <li>Please bring exact change if possible</li>
                                    <li>Payment must be made before inspection begins</li>
                                    <li>Request a receipt from the expert</li>
                                    <li>Your booking is confirmed upon payment</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn-pay">
                                <i class="ph ph-check-circle"></i>
                                Confirm Cash Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Card number formatting and validation
        const cardNumberInput = document.getElementById('card-number');
        const cardNameInput = document.getElementById('card-name');
        const cardExpiryInput = document.getElementById('card-expiry');
        const cardCvvInput = document.getElementById('card-cvv');
        const creditCard = document.getElementById('credit-card');

        // Display elements
        const cardDisplayNumber = document.getElementById('card-display-number');
        const cardDisplayName = document.getElementById('card-display-name');
        const cardDisplayExpiry = document.getElementById('card-display-expiry');
        const cardDisplayCvv = document.getElementById('card-display-cvv');
        const cardBrandIcon = document.getElementById('card-brand-icon');

        // Format card number with spaces
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
            
            // Update display
            cardDisplayNumber.textContent = formattedValue || '•••• •••• •••• ••••';
            
            // Detect card type
            detectCardType(value);
        });

        // Update cardholder name
        cardNameInput.addEventListener('input', function(e) {
            cardDisplayName.textContent = e.target.value.toUpperCase() || 'YOUR NAME';
        });

        // Format expiry date
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
            cardDisplayExpiry.textContent = value || 'MM/YY';
        });

        // Update CVV and flip card
        cardCvvInput.addEventListener('focus', function() {
            creditCard.classList.add('flipped');
        });

        cardCvvInput.addEventListener('blur', function() {
            creditCard.classList.remove('flipped');
        });

        cardCvvInput.addEventListener('input', function(e) {
            cardDisplayCvv.textContent = e.target.value || '•••';
        });

        // Detect card type
        function detectCardType(number) {
            creditCard.classList.remove('visa', 'mastercard', 'amex', 'default');
            
            if (number.startsWith('4')) {
                creditCard.classList.add('visa');
                cardBrandIcon.className = 'ph ph-credit-card card-brand';
            } else if (number.startsWith('5')) {
                creditCard.classList.add('mastercard');
                cardBrandIcon.className = 'ph ph-credit-card card-brand';
            } else if (number.startsWith('3')) {
                creditCard.classList.add('amex');
                cardBrandIcon.className = 'ph ph-credit-card card-brand';
            } else {
                creditCard.classList.add('default');
                cardBrandIcon.className = 'ph ph-credit-card card-brand';
            }
        }

        // Tab switching
        const tabs = document.querySelectorAll('.payment-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
                
                // Update hidden method input
                const methodInput = document.querySelector('input[name="method"]');
                if (targetTab === 'card') methodInput.value = 'Credit Card';
                else if (targetTab === 'bank') methodInput.value = 'Bank Transfer';
                else if (targetTab === 'cash') methodInput.value = 'Cash at Location';
            });
        });

        // Form validation
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const cardNumber = cardNumberInput.value.replace(/\s/g, '');
            const expiry = cardExpiryInput.value;
            const cvv = cardCvvInput.value;
            
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                e.preventDefault();
                alert('Please enter a valid card number');
                return;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                e.preventDefault();
                alert('Please enter a valid expiry date (MM/YY)');
                return;
            }
            
            if (cvv.length < 3 || cvv.length > 4) {
                e.preventDefault();
                alert('Please enter a valid CVV');
                return;
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
