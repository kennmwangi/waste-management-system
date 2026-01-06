<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle admin response
if (isset($_POST['respond_complaint'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $response = $conn->real_escape_string($_POST['admin_response']);
    $new_status = $_POST['status'];
    $admin_id = $_SESSION['user_id'];
    
    $update_sql = "UPDATE complaints SET 
        admin_response = '$response',
        status = '$new_status',
        responded_by = $admin_id,
        response_date = NOW()
        WHERE id = $complaint_id";
    
    if ($conn->query($update_sql)) {
        $success = 'Response sent successfully! Issue will auto-resolve in 5 seconds.';
    } else {
        $error = 'Failed to send response.';
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE complaints SET status = '$new_status' WHERE id = $complaint_id";
    
    if ($conn->query($update_sql)) {
        $success = 'Status updated successfully!';
    }
}

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : 'all';

// Build query
$where_clauses = [];
if ($status_filter !== 'all') {
    $where_clauses[] = "c.status = '$status_filter'";
}
if ($priority_filter !== 'all') {
    $where_clauses[] = "c.priority = '$priority_filter'";
}
$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get complaints
$complaints_sql = "SELECT c.*, u.full_name, u.email, u.bin_id,
                   r.full_name as responded_by_name
                   FROM complaints c
                   JOIN users u ON c.user_id = u.id
                   LEFT JOIN users r ON c.responded_by = r.id
                   $where_sql
                   ORDER BY 
                   CASE c.status 
                       WHEN 'pending' THEN 1
                       WHEN 'in_progress' THEN 2
                       WHEN 'resolved' THEN 3
                       WHEN 'closed' THEN 4
                   END,
                   CASE c.priority
                       WHEN 'high' THEN 1
                       WHEN 'medium' THEN 2
                       WHEN 'low' THEN 3
                   END,
                   c.created_at DESC";
$complaints = $conn->query($complaints_sql);

// Get statistics
$total_complaints = $conn->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'")->fetch_assoc()['count'];
$in_progress = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'in_progress'")->fetch_assoc()['count'];
$resolved = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card { border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .status-pending { background: #ffc107; color: #000; }
        .status-in_progress { background: #17a2b8; color: #fff; }
        .status-resolved { background: #28a745; color: #fff; }
        .status-closed { background: #6c757d; color: #fff; }
        .priority-high { border-left: 4px solid #dc3545 !important; }
        .priority-medium { border-left: 4px solid #ffc107 !important; }
        .priority-low { border-left: 4px solid #28a745 !important; }
        .complaint-card { transition: all 0.2s; cursor: pointer; }
        .complaint-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-chat-square-dots"></i> Complaints Management
            </span>
            <div class="d-flex align-items-center text-white">
                <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <a href="admin_subscribers.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-people"></i> Subscribers
                </a>
                <span class="me-3"><i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Total Complaints</h6>
                        <h2 class="text-primary"><?php echo $total_complaints; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Pending</h6>
                        <h2 class="text-warning"><?php echo $pending; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">In Progress</h6>
                        <h2 class="text-info"><?php echo $in_progress; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Resolved</h6>
                        <h2 class="text-success"><?php echo $resolved; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filter by Priority</label>
                        <select name="priority" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priorities</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="admin_complaints.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Complaints List -->
        <div class="row">
            <?php if ($complaints->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> No complaints found matching your filters.
                    </div>
                </div>
            <?php else: ?>
                <?php while ($complaint = $complaints->fetch_assoc()): ?>
                <div class="col-md-6 mb-3">
                    <div class="card complaint-card priority-<?php echo $complaint['priority']; ?>" data-bs-toggle="modal" data-bs-target="#complaintModal<?php echo $complaint['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0"><?php echo htmlspecialchars($complaint['subject']); ?></h6>
                                <span class="badge status-<?php echo $complaint['status']; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $complaint['status'])); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($complaint['full_name']); ?> 
                                (Bin: <?php echo htmlspecialchars($complaint['bin_id']); ?>)
                            </p>
                            
                            <p class="text-muted small mb-2">
                                <i class="bi bi-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($complaint['created_at'])); ?>
                                <span class="ms-2">
                                    <i class="bi bi-flag"></i> 
                                    <span class="text-<?php echo $complaint['priority'] === 'high' ? 'danger' : ($complaint['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                        <?php echo strtoupper($complaint['priority']); ?>
                                    </span>
                                </span>
                            </p>
                            
                            <p class="mb-0 text-truncate"><?php echo htmlspecialchars($complaint['description']); ?></p>
                            
                            <?php if ($complaint['admin_response']): ?>
                                <small class="text-success"><i class="bi bi-check-circle"></i> Responded</small>
                            <?php else: ?>
                                <small class="text-warning"><i class="bi bi-hourglass-split"></i> Needs Response</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Complaint Detail Modal -->
                <div class="modal fade" id="complaintModal<?php echo $complaint['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><?php echo htmlspecialchars($complaint['subject']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Customer Info -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">Customer Information</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($complaint['full_name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($complaint['email']); ?></p>
                                        <p class="mb-0"><strong>Bin ID:</strong> <?php echo htmlspecialchars($complaint['bin_id']); ?></p>
                                    </div>
                                </div>

                                <!-- Complaint Details -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">Complaint Details</h6>
                                        <p class="mb-1">
                                            <strong>Priority:</strong> 
                                            <span class="badge bg-<?php echo $complaint['priority'] === 'high' ? 'danger' : ($complaint['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                                <?php echo strtoupper($complaint['priority']); ?>
                                            </span>
                                        </p>
                                        <p class="mb-1"><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($complaint['created_at'])); ?></p>
                                        <p class="mb-1"><strong>Status:</strong> 
                                            <span class="badge status-<?php echo $complaint['status']; ?>">
                                                <?php echo strtoupper(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </p>
                                        <hr>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                                    </div>
                                </div>

                                <!-- Existing Response -->
                                <?php if ($complaint['admin_response']): ?>
                                <div class="alert alert-success">
                                    <strong><i class="bi bi-reply"></i> Previous Response:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($complaint['admin_response'])); ?>
                                    <hr class="my-2">
                                    <small class="text-muted">
                                        Responded by <?php echo htmlspecialchars($complaint['responded_by_name']); ?> 
                                        on <?php echo date('d M Y, h:i A', strtotime($complaint['response_date'])); ?>
                                    </small>
                                </div>
                                <?php endif; ?>

                                <!-- Response Form -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <?php echo $complaint['admin_response'] ? 'Update Response' : 'Send Response'; ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" class="complaint-form" data-complaint-id="<?php echo $complaint['id']; ?>">
                                            <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Your Response *</label>
                                                <textarea class="form-control" name="admin_response" rows="4" 
                                                          required placeholder="Type your response to the customer..."><?php echo htmlspecialchars($complaint['admin_response'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Update Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="pending" <?php echo $complaint['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    <option value="closed" <?php echo $complaint['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </div>

                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> <strong>Auto-Resolve:</strong> This complaint will automatically be marked as "Resolved" 5 seconds after you send your response.
                                            </div>
                                            
                                            <button type="submit" name="respond_complaint" class="btn btn-primary">
                                                <i class="bi bi-send"></i> Send Response (Auto-resolves in 5s)
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Auto-resolve complaints after response
        document.querySelectorAll('.complaint-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const complaintId = this.dataset.complaintId;
                
                // After form submission, wait 5 seconds then auto-resolve
                setTimeout(function() {
                    fetch('auto_resolve_complaint.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'complaint_id=' + complaintId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Complaint auto-resolved');
                            location.reload();
                        }
                    });
                }, 5000);
            });
        });
    </script>
</body>
</html>