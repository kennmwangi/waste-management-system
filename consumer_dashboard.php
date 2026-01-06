<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (isAdmin()) {
    redirect('admin_dashboard.php');
}

// Get consumer details
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT u.*, b.fill_level, b.status as bin_status, b.last_emptied 
             FROM users u
             LEFT JOIN trash_bins b ON u.bin_id = b.bin_id
             WHERE u.id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Check if truck is assigned to user's bin
$truck_sql = "SELECT * FROM waste_trucks WHERE assigned_bin_id = '{$user['bin_id']}' AND status != 'idle'";
$truck_result = $conn->query($truck_sql);
$assigned_truck = $truck_result->num_rows > 0 ? $truck_result->fetch_assoc() : null;

// Get collection history
$history_sql = "SELECT ch.*, wt.truck_name, wt.driver_name 
                FROM collection_history ch
                JOIN waste_trucks wt ON ch.truck_id = wt.id
                WHERE ch.bin_id = '{$user['bin_id']}'
                ORDER BY ch.collection_date DESC
                LIMIT 10";
$history_result = $conn->query($history_sql);

// Handle subscription cancellation
if (isset($_POST['cancel_subscription'])) {
    $cancel_sql = "UPDATE users SET subscription_status = 'expired', subscription_end_date = NOW() WHERE id = $user_id";
    if ($conn->query($cancel_sql)) {
        $_SESSION['cancel_success'] = true;
        header("Location: consumer_dashboard.php");
        exit();
    }
}

$cancel_success = isset($_SESSION['cancel_success']);
unset($_SESSION['cancel_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - SmartWaste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .info-card { transition: transform 0.2s; border-radius: 15px; }
        .info-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .bin-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .bin-id {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 3px;
        }
        .fill-level-bar {
            height: 40px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        .fill-level-progress {
            height: 100%;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .status-badge {
            font-size: 1.1rem;
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-speedometer2"></i> My Dashboard
            </span>
            <div class="d-flex align-items-center text-white">
                <a href="consumer_complaints.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-chat-square-text"></i> Complaints
                </a>
                <span class="me-3">
                    <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($cancel_success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Subscription cancelled successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Bin ID Display -->
        <div class="bin-display shadow">
            <p class="mb-2">Your Waste Bin ID</p>
            <div class="bin-id"><?php echo $user['bin_id']; ?></div>
            <p class="mt-3 mb-0 opacity-75">
                <i class="bi bi-info-circle"></i> This is your unique waste collection identifier
            </p>
        </div>

        <div class="row">
            <!-- Fill Level Status -->
            <div class="col-lg-6 mb-4">
                <div class="card info-card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-trash-fill"></i> Bin Fill Level</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $fill_level = intval($user['fill_level']);
                        $color = $fill_level >= 75 ? 'danger' : ($fill_level >= 50 ? 'warning' : 'success');
                        ?>
                        <div class="fill-level-bar mb-3">
                            <div class="fill-level-progress bg-<?php echo $color; ?>" 
                                 style="width: <?php echo $fill_level; ?>%">
                                <?php echo $fill_level; ?>%
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <h2 class="text-<?php echo $color; ?>"><?php echo $fill_level; ?>%</h2>
                                <p class="text-muted mb-0">Current Level</p>
                            </div>
                            <div class="col-6">
                                <h2 class="text-success"><?php echo 100 - $fill_level; ?>%</h2>
                                <p class="text-muted mb-0">Remaining Capacity</p>
                            </div>
                        </div>

                        <?php if ($fill_level >= 75): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Your bin is almost full! 
                            Collection will be scheduled soon.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Collection Status -->
            <div class="col-lg-6 mb-4">
                <div class="card info-card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Collection Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assigned_truck): ?>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-truck-front-fill"></i> Truck Assigned!</h5>
                            <hr>
                            <p class="mb-1"><strong>Truck:</strong> <?php echo htmlspecialchars($assigned_truck['truck_name']); ?></p>
                            <p class="mb-1"><strong>Driver:</strong> <?php echo htmlspecialchars($assigned_truck['driver_name'] ?? 'N/A'); ?></p>
                            <p class="mb-0"><strong>Status:</strong> 
                                <span class="badge bg-primary"><?php echo strtoupper($assigned_truck['status']); ?></span>
                            </p>
                        </div>
                        <p class="text-muted mb-0">
                            <i class="bi bi-clock-history"></i> Assigned: 
                            <?php echo date('d M Y, h:i A', strtotime($assigned_truck['assigned_at'])); ?>
                        </p>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">No Active Collection</h5>
                            <p class="text-muted">Your bin will be collected when it reaches 75% capacity</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['last_emptied']): ?>
                        <hr>
                        <p class="mb-0 small text-muted">
                            <i class="bi bi-calendar-check"></i> Last Collection: 
                            <?php echo date('d M Y, h:i A', strtotime($user['last_emptied'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Info & Cancel Button -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card info-card shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Subscription Status</h5>
                        <span class="status-badge badge bg-<?php echo $user['subscription_status'] === 'active' ? 'success' : 'warning'; ?>">
                            <?php echo strtoupper($user['subscription_status']); ?>
                        </span>
                        <?php if ($user['subscription_end_date']): ?>
                        <p class="mt-3 mb-2 text-muted small">
                            Expires: <?php echo date('d M Y', strtotime($user['subscription_end_date'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($user['subscription_status'] === 'active'): ?>
                        <button class="btn btn-danger btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Subscription
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card info-card shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-phone text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">M-Pesa Phone</h5>
                        <p class="h6 mb-0"><?php echo htmlspecialchars($user['mpesa_phone'] ?? 'Not set'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card info-card shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope text-info" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Email</h5>
                        <p class="mb-0 small"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection History -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Collection History</h5>
            </div>
            <div class="card-body">
                <?php if ($history_result->num_rows === 0): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">No collection history yet</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Truck</th>
                                <th>Driver</th>
                                <th>Fill Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($history = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y, h:i A', strtotime($history['collection_date'])); ?></td>
                                <td><?php echo htmlspecialchars($history['truck_name']); ?></td>
                                <td><?php echo htmlspecialchars($history['driver_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $history['fill_level_before'] >= 75 ? 'danger' : 'warning'; ?>">
                                        <?php echo $history['fill_level_before']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cancel Subscription Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Cancel Subscription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p><strong>Are you sure you want to cancel your subscription?</strong></p>
                        <p class="text-muted">Your waste collection service will stop immediately and you will no longer receive collections.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                        <button type="submit" name="cancel_subscription" class="btn btn-danger">Yes, Cancel Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-simulate bin filling every 5 seconds
        setInterval(function() {
            fetch('simulate_bin_fill.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Bins updated:', data.updated_bins);
                        // Refresh page to show new levels
                        location.reload();
                    }
                });
        }, 5000); // Every 5 seconds
    </script>
</body>
</html>