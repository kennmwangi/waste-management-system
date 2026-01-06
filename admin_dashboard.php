<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$total_bins = $conn->query("SELECT COUNT(*) as count FROM trash_bins")->fetch_assoc()['count'];
$total_trucks = $conn->query("SELECT COUNT(*) as count FROM waste_trucks")->fetch_assoc()['count'];
$bins_needing_service = $conn->query("SELECT COUNT(*) as count FROM trash_bins WHERE fill_level >= 75")->fetch_assoc()['count'];
$pending_complaints = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'")->fetch_assoc()['count'];
$active_subscribers = $conn->query("SELECT COUNT(*) as count FROM users WHERE subscription_status = 'active' AND role = 'consumer'")->fetch_assoc()['count'];

// Handle truck assignment
$success = '';
$error = '';

if (isset($_POST['assign_truck'])) {
    $truck_id = intval($_POST['truck_id']);
    $bin_id = $conn->real_escape_string($_POST['bin_id']);
    $admin_id = $_SESSION['user_id'];
    
    // Get bin info
    $bin_check = $conn->query("SELECT * FROM trash_bins WHERE bin_id = '$bin_id'");
    if ($bin_check->num_rows === 0) {
        $error = 'Bin not found!';
    } else {
        $bin = $bin_check->fetch_assoc();
        
        // Assign truck
        $assign_sql = "UPDATE waste_trucks SET 
            status = 'assigned',
            assigned_bin_id = '$bin_id',
            assigned_at = NOW(),
            assigned_by = $admin_id
            WHERE id = $truck_id";
        
        if ($conn->query($assign_sql)) {
            // Update bin status
            $conn->query("UPDATE trash_bins SET status = 'collecting' WHERE bin_id = '$bin_id'");
            
            // Record in history
            $history_sql = "INSERT INTO collection_history (bin_id, truck_id, fill_level_before) 
                           VALUES ('$bin_id', $truck_id, {$bin['fill_level']})";
            $conn->query($history_sql);
            
            // IMMEDIATELY empty the bin to 0%
            $empty_sql = "UPDATE trash_bins SET fill_level = 0, status = 'normal', last_emptied = NOW() WHERE bin_id = '$bin_id'";
            $conn->query($empty_sql);
            
            // Set truck back to idle
            $idle_sql = "UPDATE waste_trucks SET status = 'idle', assigned_bin_id = NULL WHERE id = $truck_id";
            $conn->query($idle_sql);
            
            $success = 'Truck assigned and bin emptied to 0% successfully!';
        } else {
            $error = 'Failed to assign truck: ' . $conn->error;
        }
    }
}

// Get bins needing collection (75% or more)
$bins_sql = "SELECT b.*, u.full_name, u.email 
             FROM trash_bins b
             JOIN users u ON b.user_id = u.id
             WHERE b.fill_level >= 75
             ORDER BY b.fill_level DESC";
$bins_needing_collection = $conn->query($bins_sql);

// Get all bins for monitoring
$all_bins_sql = "SELECT b.*, u.full_name, u.email, u.mpesa_phone
                 FROM trash_bins b
                 JOIN users u ON b.user_id = u.id
                 ORDER BY b.fill_level DESC";
$all_bins = $conn->query($all_bins_sql);

// Get all trucks
$trucks_sql = "SELECT * FROM waste_trucks ORDER BY status ASC, truck_name ASC";
$trucks = $conn->query($trucks_sql);

// Get recent collections
$recent_sql = "SELECT ch.*, b.bin_id, u.full_name, wt.truck_name 
               FROM collection_history ch
               JOIN trash_bins b ON ch.bin_id = b.bin_id
               JOIN users u ON b.user_id = u.id
               JOIN waste_trucks wt ON ch.truck_id = wt.id
               ORDER BY ch.collection_date DESC
               LIMIT 10";
$recent_collections = $conn->query($recent_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartWaste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card { border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .bin-card { cursor: pointer; transition: all 0.2s; border-radius: 10px; }
        .bin-card:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .fill-level-high { border-left: 4px solid #dc3545; }
        .fill-level-medium { border-left: 4px solid #ffc107; }
        .fill-level-low { border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-trash3"></i> SmartWaste Admin Dashboard
            </span>
            <div class="d-flex align-items-center text-white">
                <a href="admin_complaints.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-chat-square-dots"></i> Complaints
                    <?php if ($pending_complaints > 0): ?>
                    <span class="badge bg-danger"><?php echo $pending_complaints; ?></span>
                    <?php endif; ?>
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

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Subscribers</h6>
                                <h2 class="mb-0"><?php echo $active_subscribers; ?></h2>
                            </div>
                            <div class="text-success"><i class="bi bi-people fs-1"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Bins</h6>
                                <h2 class="mb-0"><?php echo $total_bins; ?></h2>
                            </div>
                            <div class="text-primary"><i class="bi bi-trash fs-1"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Bins Needing Service</h6>
                                <h2 class="mb-0"><?php echo $bins_needing_service; ?></h2>
                            </div>
                            <div class="text-warning"><i class="bi bi-exclamation-triangle fs-1"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Available Trucks</h6>
                                <h2 class="mb-0"><?php echo $total_trucks; ?></h2>
                            </div>
                            <div class="text-info"><i class="bi bi-truck fs-1"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Bins Needing Collection -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i> 
                            Bins Needing Collection (≥75%)
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if ($bins_needing_collection->num_rows === 0): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
                            <p class="mt-3">All bins are below 75% capacity</p>
                        </div>
                        <?php else: ?>
                        <?php while ($bin = $bins_needing_collection->fetch_assoc()): ?>
                        <div class="card bin-card fill-level-high mb-3" 
                             data-bs-toggle="modal" 
                             data-bs-target="#assignModal"
                             onclick="selectBin('<?php echo $bin['bin_id']; ?>', '<?php echo htmlspecialchars($bin['full_name']); ?>', <?php echo $bin['fill_level']; ?>)">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-trash-fill"></i> 
                                            <?php echo htmlspecialchars($bin['bin_id']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($bin['full_name']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-danger">URGENT</span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo $bin['fill_level']; ?>%">
                                        <?php echo $bin['fill_level']; ?>%</div></div></div><?php endwhile;?><?php endif;?></div></div>
<!-- All Bins Monitor -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Bins Monitor</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <?php while ($bin = $all_bins->fetch_assoc()): ?>
                    <?php
                    $level = intval($bin['fill_level']);
                    $level_class = $level >= 75 ? 'high' : ($level >= 50 ? 'medium' : 'low');
                    $color = $level >= 75 ? 'danger' : ($level >= 50 ? 'warning' : 'success');
                    ?>
                    <div class="card bin-card fill-level-<?php echo $level_class; ?> mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($bin['bin_id']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($bin['full_name']); ?></small>
                                </div>
                                <span class="badge bg-<?php echo $color; ?>"><?php echo $level; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $level; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Trucks and Recent Collections -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Waste Trucks</h5>
                </div>
                <div class="card-body">
                    <?php while ($truck = $trucks->fetch_assoc()): ?>
                    <?php
                    $status_color = $truck['status'] === 'idle' ? 'secondary' : 
                                  ($truck['status'] === 'assigned' ? 'primary' : 'warning');
                    ?>
                    <div class="card mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($truck['truck_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($truck['driver_name'] ?? 'No driver'); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $status_color; ?>">
                                    <?php echo strtoupper($truck['status']); ?>
                                </span>
                            </div>
                            <?php if ($truck['assigned_bin_id']): ?>
                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-arrow-right"></i> Bin: <?php echo $truck['assigned_bin_id']; ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Collections</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bin ID</th>
                                    <th>Customer</th>
                                    <th>Truck</th>
                                    <th>Fill Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_collections->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No collections yet</td>
                                </tr>
                                <?php else: ?>
                                <?php while ($col = $recent_collections->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M, h:i A', strtotime($col['collection_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($col['bin_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($col['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($col['truck_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $col['fill_level_before'] >= 75 ? 'danger' : 'warning'; ?>">
                                            <?php echo $col['fill_level_before']; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-truck"></i> Assign Truck to Bin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="bin_id" id="selectedBinId">
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Selected Bin</h6>
                        <p class="mb-1"><strong>Bin ID:</strong> <span id="displayBinId"></span></p>
                        <p class="mb-1"><strong>Customer:</strong> <span id="displayCustomer"></span></p>
                        <p class="mb-0"><strong>Fill Level:</strong> <span id="displayLevel"></span></p>
                    </div>

                    <hr>

                    <h6>Select Available Truck:</h6>
                    <div id="availableTrucks">
                        <?php
                        // Reset trucks query
                        $trucks_sql = "SELECT * FROM waste_trucks WHERE status = 'idle' ORDER BY truck_name";
                        $available_trucks = $conn->query($trucks_sql);
                        
                        if ($available_trucks->num_rows === 0): ?>
                        <p class="text-danger">No available trucks. All trucks are currently assigned.</p>
                        <?php else: ?>
                        <select class="form-select" name="truck_id" required>
                            <option value="">Choose a truck...</option>
                            <?php while ($truck = $available_trucks->fetch_assoc()): ?>
                            <option value="<?php echo $truck['id']; ?>">
                                <?php echo htmlspecialchars($truck['truck_name']); ?>
                                <?php if ($truck['driver_name']): ?>
                                - <?php echo htmlspecialchars($truck['driver_name']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-lightning-fill"></i> 
                        <strong>Note:</strong> When you assign a truck, the bin will be immediately emptied to 0%.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_truck" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Assign & Empty Bin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function selectBin(binId, customer, level) {
        document.getElementById('selectedBinId').value = binId;
        document.getElementById('displayBinId').textContent = binId;
        document.getElementById('displayCustomer').textContent = customer;
        document.getElementById('displayLevel').textContent = level + '%';
    }

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
</body></html>                                        