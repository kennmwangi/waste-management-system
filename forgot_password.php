<?php
require_once 'config.php';

$user_type = isset($_GET['type']) ? $_GET['type'] : 'consumer';
$success = '';
$error = '';

if (isset($_POST['reset'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT * FROM users WHERE email = '$email' AND user_type = '$user_type'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // In a real application, you would:
        // 1. Generate a unique reset token
        // 2. Save it to database with expiration time
        // 3. Send email with reset link
        
        // For demonstration, we'll just show success
        $success = 'Password reset instructions have been sent to your email!';
        
        // Simulated email content (in production, use PHPMailer or similar)
        $reset_link = "http://localhost/waste_management/reset_password.php?token=DEMO_TOKEN";
        $email_body = "Click this link to reset your password: $reset_link";
        
        // Here you would send actual email
        // mail($email, "Password Reset", $email_body);
        
    } else {
        $error = 'Email not found in our system!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartWaste Ruiru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="reset-container">
                    <div class="reset-header">
                        <i class="bi bi-key-fill fs-1"></i>
                        <h2 class="mt-2">Forgot Password?</h2>
                        <p class="mb-0">We'll send you reset instructions</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger mx-4 mt-3">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success mx-4 mt-3">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            <hr>
                            <small class="text-muted">
                                <strong>Demo Mode:</strong> In production, an email would be sent to your inbox. 
                                For now, contact your administrator to reset your password.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <?php if (!$success): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" required autofocus 
                                           placeholder="Enter your registered email">
                                </div>
                                <small class="text-muted">
                                    Enter the email you used to register
                                </small>
                            </div>
                            <button type="submit" name="reset" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-send"></i> Send Reset Link
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        <div class="text-center">
                            <p class="text-muted">Remember your password?</p>
                            <?php if ($user_type === 'admin'): ?>
                                <a href="admin_login.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Admin Login
                                </a>
                            <?php else: ?>
                                <a href="consumer_login.php" class="btn btn-outline-success">
                                    <i class="bi bi-arrow-left"></i> Back to Consumer Login
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>