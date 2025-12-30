<?php
require_once 'db.php';

echo "Seeding reviews...<br>";

// 1. Get some expert IDs and buyer IDs
$experts_res = $conn->query("SELECT expert_id FROM expert LIMIT 5");
$buyers_res = $conn->query("SELECT buyer_id FROM buyer LIMIT 5");

$experts = [];
while($row = $experts_res->fetch_assoc()) $experts[] = $row['expert_id'];

$buyers = [];
while($row = $buyers_res->fetch_assoc()) $buyers[] = $row['buyer_id'];

if (empty($experts) || empty($buyers)) {
    die("Error: Please make sure you have experts and buyers in the database first.");
}

// 2. Sample Reviews
$sample_reviews = [
    [
        'rating' => 5,
        'comment' => "Rohan's inspection was thorough and professional. His report helped me avoid a potentially costly mistake. Highly recommended!"
    ],
    [
        'rating' => 4,
        'comment' => "Chamara was knowledgeable and provided a detailed report. I appreciated his insights and prompt service."
    ],
    [
        'rating' => 5,
        'comment' => "Excellent service! The expert arrived on time and checked everything. The report was very easy to understand."
    ],
    [
        'rating' => 5,
        'comment' => "Very detailed inspection. Found things I would have Never noticed myself. Saved me a lot of money and headache."
    ]
];

// 3. Insert Reviews linked to bookings (even if fake bookings)
foreach ($sample_reviews as $index => $rev) {
    $e_id = $experts[$index % count($experts)];
    $b_id = $buyers[$index % count($buyers)];
    
    // Create a dummy completed booking for this review
    $conn->query("INSERT INTO booking (buyer_id, expert_id, vehicle_type, location, booking_date, status) 
                  VALUES ($b_id, $e_id, 'Car', 'Badulla', CURRENT_DATE, 'completed')");
    $booking_id = $conn->insert_id;
    
    // Insert Review
    $rating = $rev['rating'];
    $comment = $conn->real_escape_string($rev['comment']);
    $conn->query("INSERT INTO review (booking_id, buyer_id, expert_id, rating, comment, review_date) 
                  VALUES ($booking_id, $b_id, $e_id, $rating, '$comment', CURRENT_DATE)");
}

echo "Seeding complete. Added " . count($sample_reviews) . " reviews.<br>";
echo "<a href='index.php'>Go to Homepage</a>";
?>
