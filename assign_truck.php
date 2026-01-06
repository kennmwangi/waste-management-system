<?php
require_once 'config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $truck_id = isset($_POST['truck_id']) ? intval($_POST['truck_id']) : 0;
    $bin_id = isset($_POST['bin_id']) ? intval($_POST['bin_id']) : 0;
    
    if ($truck_id <= 0 || $bin_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid truck or bin ID']);
        exit();
    }
    
    // Get bin location
    $bin_sql = "SELECT * FROM trash_bins WHERE id = $bin_id";
    $bin_result = $conn->query($bin_sql);
    
    if ($bin_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Bin not found']);
        exit();
    }
    
    $bin = $bin_result->fetch_assoc();
    
    // Assign truck to bin
    $assign_sql = "UPDATE waste_trucks SET 
        destination_lat = {$bin['location_lat']},
        destination_lng = {$bin['location_lng']},
        status = 'en_route',
        assigned_by = {$_SESSION['user_id']},
        assigned_at = NOW()
        WHERE id = $truck_id";
    
    if ($conn->query($assign_sql)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Truck assigned successfully',
            'truck_id' => $truck_id,
            'bin_name' => $bin['bin_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>