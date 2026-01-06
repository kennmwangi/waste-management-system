<?php
session_start();

// Check if user just registered
if (!isset($_SESSION['pending_user_id'])) {
    header('Location: signup.php');
    exit();
}

require_once 'config.php';

$error = '';
$success = '';

// Get pending user details
$user_id = $_SESSION['pending_user_id'];
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Handle M-Pesa payment submission
if (isset($_POST['initiate_payment'])) {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone_number']);
    
    // Validate Kenyan phone number
    if (!preg_match('/^(254|0)[17]\d{8}$/', $phone)) {
        $error = 'Please enter a valid Kenyan phone number (e.g., 0712345678 or 254712345678)';
    } else {
        // Normalize to 254 format
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        
        // Update user with phone number
        $update_phone = "UPDATE users SET mpesa_phone = '$phone' WHERE id = $user_id";
        $conn->query($update_phone);
        
        // Simulate M-Pesa prompt (in production, integrate with Safaricom API)
        $_SESSION['payment_phone'] = $phone;
        $success = 'payment_initiated';
    }
}

// Handle payment confirmation (simulated)
if (isset($_POST['confirm_payment'])) {
    $mpesa_code = strtoupper($_POST['mpesa_code']);
    
    if (strlen($mpesa_code) < 5) {
        $error = 'Please enter a valid M-Pesa transaction code';
    } else {
        // Simulate payment verification (in production, verify with Safaricom)
        $phone = $_SESSION['payment_phone'];
        $amount = 100.00;
        
        // Record payment
        $payment_sql = "INSERT INTO payments (user_id, mpesa_receipt, phone_number, amount, status) 
                       VALUES ($user_id, '$mpesa_code', '$phone', $amount, 'completed')";
        $conn->query($payment_sql);
        
        // Activate subscription (30 days from now)
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));
        
        $activate_sql = "UPDATE users SET 
            subscription_status = 'active',
            subscription_start_date = '$start_date',
            subscription_end_date = '$end_date'
            WHERE id = $user_id";
        
        if ($conn->query($activate_sql)) {
            // Clear pending session
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['payment_phone']);
            
            // Redirect to login
            $_SESSION['payment_success'] = true;
            header('Location: consumer_login.php?payment=success');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payment - SmartWaste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #00A651 0%, #006838 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
        }
        .payment-header {
            background: linear-gradient(135deg, #00A651 0%, #006838 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .mpesa-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .amount-display {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-display h2 {
            color: #00A651;
            font-weight: bold;
            margin: 0;
        }
        .phone-input {
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 2px;
        }
        .payment-steps {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .payment-steps li {
            margin-bottom: 10px;
        }
        .mpesa-animation {
            text-align: center;
            padding: 30px;
        }
        .mpesa-animation i {
            font-size: 4rem;
            color: #00A651;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .bin-id-display {
            background: #d1ecf1;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .bin-id-text {
            font-size: 2rem;
            font-weight: bold;
            color: #0c5460;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="payment-container mx-auto">
                    <div class="payment-header">
                        <div class="mpesa-logo">📱</div>
                        <h2>M-Pesa Payment</h2>
                        <p class="mb-0">SmartWaste Monthly Subscription</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger mx-4 mt-3">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <!-- Display Bin ID -->
                        <?php if (isset($_SESSION['pending_bin_id'])): ?>
                        <div class="bin-id-display">
                            <p class="mb-2"><i class="bi bi-trash"></i> Your Waste Bin ID</p>
                            <div class="bin-id-text"><?php echo $_SESSION['pending_bin_id']; ?></div>
                            <small class="text-muted">Save this ID - you'll need it to track your collections</small>
                        </div>
                        <?php endif; ?>

                        <?php if ($success !== 'payment_initiated'): ?>
                        <!-- Step 1: Enter Phone Number -->
                        <div class="amount-display">
                            <p class="text-muted mb-2">Monthly Subscription Fee</p>
                            <h2>KSh 100.00</h2>
                            <small class="text-muted">Valid for 30 days</small>
                        </div>
                        
                        <div class="mb-4">
                            <p><strong>Hello, <?php echo htmlspecialchars($user['full_name']); ?>!</strong></p>
                            <p>To activate your waste collection service, please pay KSh 100 via M-Pesa.</p>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">M-Pesa Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-phone"></i> +254
                                    </span>
                                    <input type="tel" class="form-control phone-input" name="phone_number" 
                                           placeholder="712345678" required pattern="[0-9]{9,10}"
                                           value="<?php echo $user['mpesa_phone'] ? substr($user['mpesa_phone'], 3) : ''; ?>">
                                </div>
                                <small class="text-muted">Enter your Safaricom number without country code</small>
                            </div>
                            
                            <div class="payment-steps">
                                <p class="fw-bold mb-2"><i class="bi bi-info-circle"></i> What happens next:</p>
                                <ol class="mb-0 small">
                                    <li>Click "Send Payment Request"</li>
                                    <li>Check your phone for M-Pesa prompt</li>
                                    <li>Enter your M-Pesa PIN</li>
                                    <li>Confirm the payment</li>
                                    <li>Enter the M-Pesa code you receive</li>
                                </ol>
                            </div>
                            
                            <button type="submit" name="initiate_payment" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-send"></i> Send Payment Request
                            </button>
                        </form>
                        
                        <?php else: ?>
                        <!-- Step 2: Confirm Payment -->
                        <div class="mpesa-animation">
                            <i class="bi bi-phone-vibrate"></i>
                            <h5 class="mt-3">Check Your Phone!</h5>
                            <p class="text-muted">An M-Pesa prompt has been sent to<br><strong><?php echo $_SESSION['payment_phone']; ?></strong></p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Important:</strong><br>
                            Enter your M-Pesa PIN on your phone to complete the payment. You will receive an SMS with a transaction code.
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">M-Pesa Transaction Code</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input type="text" class="form-control text-uppercase" name="mpesa_code" 
                                           placeholder="e.g., QGH7K9MXYZ" required minlength="5" maxlength="15">
                                </div>
                                <small class="text-muted">Enter the M-Pesa confirmation code from SMS</small>
                            </div>
                            
                            <button type="submit" name="confirm_payment" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-check-circle"></i> Confirm Payment
                            </button>
                            
                            <button type="button" onclick="window.location.reload()" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Change Phone Number
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-2">
                                <i class="bi bi-shield-check"></i> Secure payment powered by M-Pesa
                            </p>
                            <p class="text-muted small">
                                Need help? Contact: <strong>0700 000 000</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>