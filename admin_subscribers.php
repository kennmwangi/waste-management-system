<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Get all subscribers - FIXED: changed user_type to role, removed non-existent columns
$subscribers_sql = "SELECT u.*, 
    DATEDIFF(u.subscription_end_date, CURDATE()) as days_remaining,
    p.mpesa_receipt, p.payment_date, p.amount
    FROM users u
    LEFT JOIN payments p ON u.id = p.user_id AND p.status = 'completed'
    WHERE u.role = 'consumer'
    ORDER BY u.subscription_status ASC, u.created_at DESC";
$subscribers = $conn->query($subscribers_sql);

// Get statistics - FIXED: changed user_type to role
$active_subs = $conn->query("SELECT COUNT(*) as count FROM users WHERE subscription_status = 'active' AND role = 'consumer'")->fetch_assoc()['count'];
$pending_subs = $conn->query("SELECT COUNT(*) as count FROM users WHERE subscription_status = 'pending' AND role = 'consumer'")->fetch_assoc()['count'];
$expired_subs = $conn->query("SELECT COUNT(*) as count FROM users WHERE subscription_status = 'expired' AND role = 'consumer'")->fetch_assoc()['count'];
$monthly_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriber Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card { border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .status-active { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-expired { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-people"></i> Subscriber Management
            </span>
            <div class="d-flex align-items-center text-white">
                <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <span class="me-3"><i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active Subscribers</h6>
                        <h2 class="text-success"><?php echo $active_subs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Payment</h6>
                        <h2 class="text-warning"><?php echo $pending_subs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Expired</h6>
                        <h2 class="text-danger"><?php echo $expired_subs; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Monthly Revenue</h6>
                        <h2 class="text-primary">KSh <?php echo number_format($monthly_revenue, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Subscribers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Days Left</th>
                                <th>Last Payment</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($subscribers->num_rows === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No subscribers found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php while ($sub = $subscribers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $sub['id']; ?></td>
                                <td><?php echo htmlspecialchars($sub['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                <td>
                                    <span class="status-<?php echo $sub['subscription_status']; ?>">
                                        <?php echo strtoupper($sub['subscription_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($sub['subscription_status'] === 'active') {
                                        $days = $sub['days_remaining'];
                                        if ($days > 0) {
                                            echo '<span class="badge bg-success">' . $days . ' days</span>';
                                        } elseif ($days == 0) {
                                            echo '<span class="badge bg-warning">Expires today</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Expired</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($sub['payment_date']) {
                                        echo '<small>' . date('d M Y', strtotime($sub['payment_date'])) . '</small>';
                                    } else {
                                        echo '<span class="text-muted">No payment</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($sub['amount']) {
                                        echo '<strong>KSh ' . number_format($sub['amount'], 2) . '</strong>';
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailsModal<?php echo $sub['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal<?php echo $sub['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">
                                                <i class="bi bi-person-circle"></i> Subscriber Details
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Full Name:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php echo htmlspecialchars($sub['full_name']); ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Email:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php echo htmlspecialchars($sub['email']); ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Status:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="badge bg-<?php echo $sub['subscription_status'] === 'active' ? 'success' : ($sub['subscription_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo strtoupper($sub['subscription_status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Subscription Start:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php echo $sub['subscription_start_date'] ? date('d M Y', strtotime($sub['subscription_start_date'])) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Subscription End:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php echo $sub['subscription_end_date'] ? date('d M Y', strtotime($sub['subscription_end_date'])) : 'N/A'; ?>
                                                </div>
                                            </div>
                                            <?php if ($sub['mpesa_receipt']): ?>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>M-Pesa Receipt:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <code><?php echo htmlspecialchars($sub['mpesa_receipt']); ?></code>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Registered On:</strong>
                                                </div>
                                                <div class="col-6">
                                                    <?php echo date('d M Y, h:i A', strtotime($sub['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>