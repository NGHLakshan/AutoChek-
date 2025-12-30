<?php
function test_nominatim($query) {
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
            echo " - " . $item['display_name'] . "\n";
        }
    }
    echo "--------------------------------------------------\n";
}

test_nominatim("Ban");
test_nominatim("Ban, Badulla District");
?>
