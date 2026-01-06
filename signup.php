<?php
require_once 'config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('consumer_dashboard.php');
    }
}

$error = '';
$success = '';

// Function to generate unique bin ID
function generateBinID($conn) {
    do {
        // Generate format: WB-XXXXX (WB = Waste Bin, followed by 5 random digits)
        $bin_id = 'WB-' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        
        // Check if this ID already exists
        $check = $conn->query("SELECT id FROM users WHERE bin_id = '$bin_id'");
    } while ($check->num_rows > 0);
    
    return $bin_id;
}

if (isset($_POST['signup'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $user_type = 'consumer'; // Only consumers can self-register
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Email already registered!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate unique bin ID
            $bin_id = generateBinID($conn);
            
            $sql = "INSERT INTO users (email, password, role, full_name, bin_id, subscription_status) 
                    VALUES ('$email', '$hashed_password', '$user_type', '$full_name', '$bin_id', 'pending')";
            
            if ($conn->query($sql)) {
                // Get the new user ID
                $new_user_id = $conn->insert_id;
                
                // Create trash bin for this user
                $bin_sql = "INSERT INTO trash_bins (bin_id, user_id, fill_level, status) 
                           VALUES ('$bin_id', $new_user_id, 0, 'normal')";
                $conn->query($bin_sql);
                
                // Store user ID for payment
                $_SESSION['pending_user_id'] = $new_user_id;
                $_SESSION['pending_user_email'] = $email;
                $_SESSION['pending_user_name'] = $full_name;
                $_SESSION['pending_bin_id'] = $bin_id;
                
                // Redirect to payment
                header('Location: mpesa_payment.php');
                exit();
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SmartWaste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 50px 0;
        }
        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .signup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .bin-info {
            background: #d1ecf1;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="signup-container">
                    <div class="signup-header">
                        <i class="bi bi-person-plus-fill fs-1"></i>
                        <h2 class="mt-2">Create Account</h2>
                        <p class="mb-0">Join SmartWaste Community</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger mx-4 mt-3">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <!-- Bin ID Info -->
                        <div class="bin-info">
                            <h6 class="mb-2"><i class="bi bi-trash"></i> Your Personal Waste Bin</h6>
                            <p class="mb-0 small">
                                Upon registration, you'll receive a <strong>unique Bin ID</strong> that identifies 
                                your waste collection point. This ID will be used to track your bin's fill level 
                                and schedule collections.
                            </p>
                        </div>

                        <!-- Payment Notice -->
                        <div class="payment-notice">
                            <h6 class="mb-2"><i class="bi bi-credit-card"></i> Registration Fee Required</h6>
                            <p class="mb-0 small">
                                After creating your account, you'll pay <strong>KSh 100</strong> 
                                monthly subscription via M-Pesa to activate your waste collection service.
                            </p>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" minlength="6" required>
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" required id="terms">
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a> 
                                    and understand the KSh 100 monthly subscription fee
                                </label>
                            </div>
                            <button type="submit" name="signup" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-person-plus"></i> Create Account & Proceed to Payment
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="text-muted">Already have an account?</p>
                            <a href="consumer_login.php" class="btn btn-outline-success">
                                <i class="bi bi-box-arrow-in-right"></i> Login Instead
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="landing.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>