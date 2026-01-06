<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['complaint_id'])) {
    $complaint_id = intval($_POST['complaint_id']);
    
    // Auto-resolve the complaint
    $resolve_sql = "UPDATE complaints SET status = 'resolved' WHERE id = $complaint_id";
    
    if ($conn->query($resolve_sql)) {
        echo json_encode(['success' => true, 'message' => 'Complaint auto-resolved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to resolve']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No complaint ID provided']);
}
?>