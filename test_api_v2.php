<?php
function test_nominatim($query) {
    // Nominatim requires a User-Agent.
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($query) . "&countrycodes=lk&limit=5&addressdetails=1";
    $opts = [
        "http" => [
            "header" => "User-Agent: AutoChekTestScript/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    $data = json_decode($json, true);
    
    echo "Query: '$query'\n";
    echo "Result Count: " . count($data) . "\n";
    if (count($data) > 0) {
        foreach($data as $item) {
             $state = isset($item['address']['state']) ? $item['address']['state'] : 'N/A';
             $district = isset($item['address']['district']) ? $item['address']['district'] : 'N/A';
            echo " - " . $item['display_name'] . " [State: $state, District: $district]\n";
        }
    }
    echo "--------------------------------------------------\n";
}

// Try full name vs partial
test_nominatim("Bandarawela");
test_nominatim("Bandarawela, Badulla");
?>
