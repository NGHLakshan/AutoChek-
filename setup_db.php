<?php
require_once 'db.php';

// Disable foreign key checks to allow dropping tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop old tables if they exist to ensure clean slate
$tables = ['users', 'expert_profiles', 'bookings', 'admin', 'expert', 'buyer', 'review', 'inspection_report', 'payment'];
foreach ($tables as $table) {
    echo "Dropping table $table...<br>";
    $conn->query("DROP TABLE IF EXISTS $table");
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Admin Table
$sql_admin = "CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql_admin) === TRUE) {
    echo "Table 'admin' created successfully.<br>";
} else {
    echo "Error creating table 'admin': " . $conn->error . "<br>";
}

// 2. Buyer Table
$sql_buyer = "CREATE TABLE IF NOT EXISTS buyer (
    buyer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    location VARCHAR(255),
    profile_photo VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    email_notifications BOOLEAN DEFAULT 1,
    sms_notifications BOOLEAN DEFAULT 1,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql_buyer) === TRUE) {
    echo "Table 'buyer' created successfully.<br>";
} else {
    echo "Error creating table 'buyer': " . $conn->error . "<br>";
}

// 3. Expert Table
$sql_expert = "CREATE TABLE IF NOT EXISTS expert (
    expert_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    district VARCHAR(100),
    qualification VARCHAR(255),
    experience INT,
    verified BOOLEAN DEFAULT 0,
    profile_photo VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    email_notifications BOOLEAN DEFAULT 1,
    sms_notifications BOOLEAN DEFAULT 1,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql_expert) === TRUE) {
    echo "Table 'expert' created successfully.<br>";
} else {
    echo "Error creating table 'expert': " . $conn->error . "<br>";
}

// 4. Booking Table
$sql_booking = "CREATE TABLE IF NOT EXISTS booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    expert_id INT NOT NULL,
    vehicle_type VARCHAR(100),
    location VARCHAR(255),
    booking_date DATE,
    status VARCHAR(50) DEFAULT 'pending',
    cancellation_reason TEXT DEFAULT NULL,
    FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id) ON DELETE CASCADE,
    FOREIGN KEY (expert_id) REFERENCES expert(expert_id) ON DELETE CASCADE
)";

if ($conn->query($sql_booking) === TRUE) {
    echo "Table 'booking' created successfully.<br>";
} else {
    echo "Error creating table 'booking': " . $conn->error . "<br>";
}

// 5. Review Table
$sql_review = "CREATE TABLE IF NOT EXISTS review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    buyer_id INT NOT NULL,
    expert_id INT NOT NULL,
    rating INT,
    comment TEXT,
    review_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (booking_id) REFERENCES booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id) ON DELETE CASCADE,
    FOREIGN KEY (expert_id) REFERENCES expert(expert_id) ON DELETE CASCADE
)";

if ($conn->query($sql_review) === TRUE) {
    echo "Table 'review' created successfully.<br>";
} else {
    echo "Error creating table 'review': " . $conn->error . "<br>";
}

// 6. Inspection Report Table
$sql_report = "CREATE TABLE IF NOT EXISTS inspection_report (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    engine_condition VARCHAR(255),
    body_condition VARCHAR(255),
    electrical_condition VARCHAR(255),
    test_drive_status VARCHAR(255),
    overall_rating FLOAT,
    comments TEXT,
    report_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (booking_id) REFERENCES booking(booking_id) ON DELETE CASCADE
)";

if ($conn->query($sql_report) === TRUE) {
    echo "Table 'inspection_report' created successfully.<br>";
} else {
    echo "Error creating table 'inspection_report': " . $conn->error . "<br>";
}

// 7. Payment Table
$sql_payment = "CREATE TABLE IF NOT EXISTS payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    buyer_id INT NOT NULL,
    amount DOUBLE,
    method VARCHAR(50),
    payment_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (booking_id) REFERENCES booking(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id) ON DELETE CASCADE
)";

if ($conn->query($sql_payment) === TRUE) {
    echo "Table 'payment' created successfully.<br>";
} else {
    echo "Error creating table 'payment': " . $conn->error . "<br>";
}

// Create Default Admin Account
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$sql_insert_admin = "INSERT IGNORE INTO admin (username, password) VALUES ('admin', '$admin_password')";

if ($conn->query($sql_insert_admin) === TRUE) {
    echo "Default Admin account created (admin / admin123).<br>";
}

$conn->close();
?>
