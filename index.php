<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('consumer_dashboard.php');
    }
}

$error = '';
$success = '';

// Handle Login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['login_email']);
    $password = $_POST['login_password'];
    
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            
            if ($user['user_type'] === 'admin') {
                redirect('admin_dashboard.php');
            } else {
                redirect('consumer_dashboard.php');
            }
        } else {
            $error = 'Invalid password!';
        }
    } else {
        $error = 'Email not found!';
    }
}

// Handle Signup - UPDATED VERSION
if (isset($_POST['signup'])) {
    $email = $conn->real_escape_string($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $user_type = $_POST['user_type'];
    
    // Get location if consumer
    $location_lat = NULL;
    $location_lng = NULL;
    $location_name = NULL;
    
    if ($user_type === 'consumer' && isset($_POST['location'])) {
        $location_parts = explode(',', $_POST['location']);
        if (count($location_parts) >= 3) {
            $location_lat = floatval($location_parts[0]);
            $location_lng = floatval($location_parts[1]);
            $location_name = $conn->real_escape_string($location_parts[2]);
        }
    }
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Email already registered!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Build SQL query with location data
            if ($location_lat !== NULL && $location_lng !== NULL && $location_name !== NULL) {
                $sql = "INSERT INTO users (email, password, user_type, full_name, location_lat, location_lng, location_name) 
                        VALUES ('$email', '$hashed_password', '$user_type', '$full_name', $location_lat, $location_lng, '$location_name')";
            } else {
                $sql = "INSERT INTO users (email, password, user_type, full_name) 
                        VALUES ('$email', '$hashed_password', '$user_type', '$full_name')";
            }
            
            if ($conn->query($sql)) {
                $success = 'Registration successful! Please login.';
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
    <title>Waste Management System - Ruiru, Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .nav-pills .nav-link {
            color: #667eea;
        }
        .nav-pills .nav-link.active {
            background-color: #667eea;
        }
        #locationField {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-container">
                    <div class="auth-header">
                        <i class="bi bi-trash3 fs-1"></i>
                        <h2 class="mt-2">Waste Management System</h2>
                        <p class="mb-0">Ruiru, Kenya - Smart Waste Collection</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger mx-4 mt-3" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success mx-4 mt-3" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <ul class="nav nav-pills nav-fill mb-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="signup-tab" data-bs-toggle="pill" data-bs-target="#signup" type="button">
                                    <i class="bi bi-person-plus"></i> Sign Up
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="authTabsContent">
                            <!-- Login Form -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="login_email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" name="login_password" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">
                                        <i class="bi bi-box-arrow-in-right"></i> Login
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Signup Form - UPDATED VERSION -->
                            <div class="tab-pane fade" id="signup" role="tabpanel">
                                <form method="POST" action="">
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
                                            <input type="email" class="form-control" name="signup_email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" name="signup_password" minlength="6" required>
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
                                    <div class="mb-3">
                                        <label class="form-label">Account Type</label>
                                        <select class="form-select" name="user_type" id="userTypeSelect" required>
                                            <option value="consumer">Consumer</option>
                                            <option value="admin">Administrator</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Location Field - Shows only for consumers -->
                                    <div class="mb-3" id="locationField">
                                        <label class="form-label"><i class="bi bi-geo-alt"></i> Your Location (Ruiru Area)</label>
                                        <select class="form-select" name="location" id="locationSelect">
                                            <option value="-1.1500,36.9667,Ruiru Town Center">Ruiru Town Center</option>
                                            <option value="-1.1450,36.9700,Toll Station Area">Toll Station Area</option>
                                            <option value="-1.1550,36.9620,Membley Estate">Membley Estate</option>
                                            <option value="-1.1520,36.9750,Ruiru Market">Ruiru Market</option>
                                            <option value="-1.1480,36.9680,Kimbo Area">Kimbo Area</option>
                                            <option value="-1.1420,36.9730,Githurai Area">Githurai Area</option>
                                            <option value="-1.1580,36.9650,Kahawa West">Kahawa West</option>
                                            <option value="-1.1530,36.9590,Mwiki Area">Mwiki Area</option>
                                            <option value="-1.1470,36.9710,Bypass Area">Bypass Area</option>
                                            <option value="-1.1510,36.9640,Railways Area">Railways Area</option>
                                        </select>
                                        <small class="text-muted">Select your area to see relevant bins and trucks near you</small>
                                    </div>
                                    
                                    <button type="submit" name="signup" class="btn btn-success w-100">
                                        <i class="bi bi-person-plus"></i> Create Account
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info Card -->
                <div class="card mt-3" style="background: rgba(255,255,255,0.9);">
                    <div class="card-body text-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Serving Ruiru, Kiambu County, Kenya
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide location field based on account type
        const userTypeSelect = document.getElementById('userTypeSelect');
        const locationField = document.getElementById('locationField');
        
        userTypeSelect.addEventListener('change', function() {
            if (this.value === 'consumer') {
                locationField.style.display = 'block';
                document.getElementById('locationSelect').required = true;
            } else {
                locationField.style.display = 'none';
                document.getElementById('locationSelect').required = false;
            }
        });
        
        // Initialize - show location field by default since consumer is default
        if (userTypeSelect.value === 'consumer') {
            locationField.style.display = 'block';
        }
    </script>
</body>
</html>