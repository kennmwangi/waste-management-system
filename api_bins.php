<?php
require_once 'config.php';

header('Content-Type: application/json');

// Simulate fill level changes (sensor simulation)
$update_sql = "UPDATE trash_bins SET fill_level = LEAST(fill_level + FLOOR(RAND() * 5), 100)";
$conn->query($update_sql);

// Get all bins
$sql = "SELECT * FROM trash_bins ORDER BY fill_level DESC";
$result = $conn->query($sql);

$bins = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bins[] = $row;
    }
}

echo json_encode($bins);
?>