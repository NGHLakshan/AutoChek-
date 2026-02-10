<?php
require_once 'db.php';

echo "Repairing Expert Coordinates...\n";

$res = $conn->query("SELECT expert_id, district FROM expert WHERE (latitude IS NULL OR latitude = 0) OR (longitude IS NULL OR longitude = 0)");

if (!$res) {
    die("Query failed: " . $conn->error);
}

while ($row = $res->fetch_assoc()) {
    $id = $row['expert_id'];
    $location = $row['district'];
    echo "Geocoding Expert $id ($location)... ";
    
    // Use Nominatim to geocode
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location . ", Badulla, Sri Lanka") . "&limit=1";
    
    // Set user agent for OSM compliance
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: AutoChekRepairScript/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $data_text = file_get_contents($url, false, $context);
    $data = json_decode($data_text, true);

    if (isset($data[0])) {
        $lat = $data[0]['lat'];
        $lng = $data[0]['lon'];
        $conn->query("UPDATE expert SET latitude = $lat, longitude = $lng WHERE expert_id = $id");
        echo "Found: $lat, $lng\n";
    } else {
        // Fallback for broader search
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location . ", Sri Lanka") . "&limit=1";
        $data_text = file_get_contents($url, false, $context);
        $data = json_decode($data_text, true);
        if (isset($data[0])) {
            $lat = $data[0]['lat'];
            $lng = $data[0]['lon'];
            $conn->query("UPDATE expert SET latitude = $lat, longitude = $lng WHERE expert_id = $id");
            echo "Found (fallback): $lat, $lng\n";
        } else {
            echo "FAILED\n";
        }
    }
    
    // Pause to respect API rate limits
    sleep(1);
}

echo "Done.\n";
