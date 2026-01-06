<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get all trucks
$sql = "SELECT * FROM waste_trucks";
$result = $conn->query($sql);

$trucks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $trucks[] = $row;
    }
}

echo json_encode($trucks);
?>