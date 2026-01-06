<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle new complaint submission
if (isset($_POST['submit_complaint'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $description = $conn->real_escape_string($_POST['description']);
    $priority = $_POST['priority'];
    
    if (strlen($subject) < 5) {
        $error = 'Subject must be at least 5 characters long';
    } elseif (strlen($description) < 20) {
        $error = 'Description must be at least 20 characters long';
    } else {
        $insert_sql = "INSERT INTO complaints (user_id, subject, description, priority) 
                      VALUES ($user_id, '$subject', '$description', '$priority')";
        
        if ($conn->query($insert_sql)) {
            $success = 'Complaint submitted successfully! We will respond within 24 hours.';
        } else {
            $error = 'Failed to submit complaint. Please try again.';
        }
    }
}

// Handle complaint deletion
if (isset($_POST['delete_complaint'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $delete_sql = "DELETE FROM complaints WHERE id = $complaint_id AND user_id = $user_id";
    
    if ($conn->query($delete_sql)) {
        $success = 'Complaint deleted successfully.';
    }
}

// Handle rating submission
if (isset($_POST['submit_rating'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $rating = intval($_POST['rating']);
    $feedback = $conn->real_escape_string($_POST['feedback']);
    
    $rating_sql = "UPDATE complaints SET rating = $rating, rating_feedback = '$feedback' WHERE id = $complaint_id AND user_id = $user_id";
    
    if ($conn->query($rating_sql)) {
        $success = 'Thank you for your rating!';
    }
}

// Get user's complaints
$complaints_sql = "SELECT c.*, u.full_name as responded_by_name 
                  FROM complaints c
                  LEFT JOIN users u ON c.responded_by = u.id
                  WHERE c.user_id = $user_id 
                  ORDER BY c.created_at DESC";
$complaints = $conn->query($complaints_sql);

// Get user subscription info
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user = $conn->query($user_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - SmartWaste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .complaint-card { transition: all 0.3s; }
        .complaint-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .status-pending { background: #ffc107; color: #000; }
        .status-in_progress { background: #17a2b8; color: #fff; }
        .status-resolved { background: #28a745; color: #fff; }
        .status-closed { background: #6c757d; color: #fff; }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
        .star-rating { font-size: 2rem; cursor: pointer; }
        .star-rating i { color: #ddd; transition: color 0.2s; }
        .star-rating i.active { color: #ffc107; }
        .star-rating i:hover { color: #ffc107; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-chat-square-text"></i> My Complaints & Support
            </span>
            <div class="d-flex align-items-center text-white">
                <a href="consumer_dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <span class="me-3"><i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Submit New Complaint -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Submit New Complaint</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" name="subject" 
                                       placeholder="Brief description of your issue" required minlength="5" maxlength="255">
                                <small class="text-muted">Minimum 5 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Low - General inquiry</option>
                                    <option value="medium" selected>Medium - Service issue</option>
                                    <option value="high">High - Urgent problem</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" rows="5" 
                                          placeholder="Please provide detailed information about your complaint..." 
                                          required minlength="20"></textarea>
                                <small class="text-muted">Minimum 20 characters. Be specific to help us assist you better.</small>
                            </div>
                            
                            <button type="submit" name="submit_complaint" class="btn btn-primary w-100">
                                <i class="bi bi-send"></i> Submit Complaint
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <a href="consumer_dashboard.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-house"></i> View Collection Schedule
                        </a>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-telephone"></i> Emergency Contact</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Call Center:</strong> 0793531128</p>
                        <p class="mb-2"><strong>Email:</strong> support@smartwaste.co.ke</p>
                        <p class="mb-0"><strong>Hours:</strong> Mon-Fri, 8AM - 5PM</p>
                    </div>
                </div>
            </div>

            <!-- My Complaints History -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
<h5 class="mb-0"><i class="bi bi-list-ul"></i> My Complaints History</h5>
                    <span class="badge bg-secondary"><?php echo $complaints->num_rows; ?> Total</span>
                </div>
                <div class="card-body" style="max-height: 800px; overflow-y: auto;">
                    <?php if ($complaints->num_rows === 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">No complaints yet. Submit one if you need assistance.</p>
                        </div>
                    <?php else: ?>
                        <?php while ($complaint = $complaints->fetch_assoc()): ?>
                        <div class="card complaint-card mb-3 priority-<?php echo $complaint['priority']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($complaint['subject']); ?></h6>
                                    <span class="badge status-<?php echo $complaint['status']; ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($complaint['created_at'])); ?>
                                    <span class="ms-3">
                                        <i class="bi bi-flag"></i> 
                                        <span class="text-<?php echo $complaint['priority'] === 'high' ? 'danger' : ($complaint['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                            <?php echo strtoupper($complaint['priority']); ?> Priority
                                        </span>
                                    </span>
                                </p>
                                
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                                
                                <?php if ($complaint['admin_response']): ?>
                                    <div class="alert alert-success mb-2">
                                        <strong><i class="bi bi-reply"></i> Admin Response:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($complaint['admin_response'])); ?>
                                        <hr class="my-2">
                                        <small class="text-muted">
                                            Responded by <?php echo htmlspecialchars($complaint['responded_by_name']); ?> 
                                            on <?php echo date('d M Y, h:i A', strtotime($complaint['response_date'])); ?>
                                        </small>
                                    </div>

                                    <!-- Rating Section -->
                                    <?php if ($complaint['status'] === 'resolved' && !$complaint['rating']): ?>
                                    <div class="card bg-light mt-2">
                                        <div class="card-body p-3">
                                            <h6 class="mb-2">Rate this service:</h6>
                                            <form method="POST">
                                                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                <div class="star-rating mb-2" data-rating="0">
                                                    <i class="bi bi-star-fill" data-value="1"></i>
                                                    <i class="bi bi-star-fill" data-value="2"></i>
                                                    <i class="bi bi-star-fill" data-value="3"></i>
                                                    <i class="bi bi-star-fill" data-value="4"></i>
                                                    <i class="bi bi-star-fill" data-value="5"></i>
                                                </div>
                                                <input type="hidden" name="rating" class="rating-input" value="0">
                                                <textarea class="form-control form-control-sm mb-2" name="feedback" 
                                                          placeholder="Optional feedback..." rows="2"></textarea>
                                                <button type="submit" name="submit_rating" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-send"></i> Submit Rating
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php elseif ($complaint['rating']): ?>
                                    <div class="alert alert-info mb-0">
                                        <strong>Your Rating:</strong> 
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill <?php echo $i <= $complaint['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <?php if ($complaint['rating_feedback']): ?>
                                        <br><small><?php echo htmlspecialchars($complaint['rating_feedback']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info mb-2">
                                        <i class="bi bi-hourglass-split"></i> Waiting for admin response...
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if ($complaint['status'] !== 'resolved' && $complaint['status'] !== 'closed'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this complaint?');">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <button type="submit" name="delete_complaint" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Star rating functionality
    document.querySelectorAll('.star-rating').forEach(ratingDiv => {
        const stars = ratingDiv.querySelectorAll('i');
        const input = ratingDiv.parentElement.querySelector('.rating-input');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                input.value = value;
                
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.dataset.value);
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        ratingDiv.addEventListener('mouseleave', function() {
            const currentRating = parseInt(input.value);
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
</script>                        