<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get all trucks that are en_route
$sql = "SELECT * FROM waste_trucks WHERE status = 'en_route'";
$result = $conn->query($sql);

$updated_trucks = [];

if ($result->num_rows > 0) {
    while ($truck = $result->fetch_assoc()) {
        $current_lat = floatval($truck['current_lat']);
        $current_lng = floatval($truck['current_lng']);
        $dest_lat = floatval($truck['destination_lat']);
        $dest_lng = floatval($truck['destination_lng']);
        
        // Calculate distance
        $lat_diff = $dest_lat - $current_lat;
        $lng_diff = $dest_lng - $current_lng;
        $distance = sqrt(pow($lat_diff, 2) + pow($lng_diff, 2));
        
        // Move truck (smaller steps for more realistic movement in Ruiru)
        if ($distance > 0.0005) {  // About 50 meters
            $step = 0.015; // Movement speed
            $new_lat = $current_lat + ($lat_diff * $step);
            $new_lng = $current_lng + ($lng_diff * $step);
            
            // Update truck position
            $update_sql = "UPDATE waste_trucks SET current_lat = $new_lat, current_lng = $new_lng WHERE id = " . $truck['id'];
            $conn->query($update_sql);
            
            $updated_trucks[] = [
                'id' => $truck['id'],
                'name' => $truck['truck_name'],
                'status' => 'moving',
                'distance_remaining' => round($distance * 111, 2) // Convert to km
            ];
        } else {
            // Truck reached destination - EMPTY THE BIN
            
            // Find and reset the bin to 0%
            $reset_bin_sql = "UPDATE trash_bins 
                SET fill_level = 0 
                WHERE location_lat BETWEEN ($dest_lat - 0.0001) AND ($dest_lat + 0.0001)
                AND location_lng BETWEEN ($dest_lng - 0.0001) AND ($dest_lng + 0.0001)";
            $conn->query($reset_bin_sql);
            
            // Set truck to idle (admin will assign next bin manually)
            $idle_sql = "UPDATE waste_trucks 
                SET status = 'idle', 
                    destination_lat = NULL, 
                    destination_lng = NULL 
                WHERE id = " . $truck['id'];
            $conn->query($idle_sql);
            
            $updated_trucks[] = [
                'id' => $truck['id'],
                'name' => $truck['truck_name'],
                'status' => 'arrived_and_emptied',
                'message' => 'Bin has been emptied to 0%'
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'updated_trucks' => $updated_trucks,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>