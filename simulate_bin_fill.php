<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get all bins that are not currently being collected
$bins_sql = "SELECT * FROM trash_bins WHERE status != 'collecting'";
$result = $conn->query($bins_sql);

$updated_bins = [];

if ($result->num_rows > 0) {
    while ($bin = $result->fetch_assoc()) {
        $current_level = intval($bin['fill_level']);
        
        // Only increase if not at 100%
        if ($current_level < 100) {
            // Increase by 3-8% for faster testing
            $increase = rand(3, 8);
            $new_level = min($current_level + $increase, 100);
            
            // Update bin
            $update_sql = "UPDATE trash_bins SET fill_level = $new_level WHERE id = " . $bin['id'];
            $conn->query($update_sql);
            
            // If bin reaches 75%, mark as needing collection
            if ($new_level >= 75 && $current_level < 75) {
                $conn->query("UPDATE trash_bins SET status = 'needs_collection' WHERE id = " . $bin['id']);
            }
            
            $updated_bins[] = [
                'bin_id' => $bin['bin_id'],
                'old_level' => $current_level,
                'new_level' => $new_level,
                'status' => $new_level >= 75 ? 'needs_collection' : 'normal'
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'updated_bins' => count($updated_bins),
    'bins' => $updated_bins,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>