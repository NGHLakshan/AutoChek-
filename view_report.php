<?php
session_start();
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id > 0) {
    // Fetch report logic
    // Join with booking to get vehicle details
    $sql = "SELECT ir.*, b.vehicle_type, b.location, b.booking_date, e.name as expert_name 
            FROM inspection_report ir 
            JOIN booking b ON ir.booking_id = b.booking_id 
            JOIN expert e ON b.expert_id = e.expert_id
            WHERE ir.report_id = $report_id";
            
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $report = $result->fetch_assoc();
    } else {
        die("Report not found.");
    }
} else {
    die("Invalid Report ID");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Report #<?php echo $report_id; ?> | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #1e293b; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-top: 0; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; }
        .section { margin-bottom: 25px; }
        .label { font-weight: 700; color: #64748b; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { background: #fff; border: 1px solid #e2e8f0; padding: 10px 15px; border-radius: 6px; margin-top: 5px; }
        .rating-box { text-align: center; background: #1e293b; color: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .rating-score { font-size: 3rem; font-weight: 700; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Vehicle Inspection Report</h1>
        
        <div class="meta">
            <div>
                <div class="label">Vehicle</div>
                <div><?php echo htmlspecialchars($report['vehicle_type']); ?></div>
            </div>
            <div>
                <div class="label">Inspection Date</div>
                <div><?php echo $report['report_date']; ?></div>
            </div>
            <div>
                <div class="label">Location</div>
                <div><?php echo htmlspecialchars($report['location']); ?></div>
            </div>
            <div>
                <div class="label">Inspector</div>
                <div><?php echo htmlspecialchars($report['expert_name']); ?></div>
            </div>
        </div>

        <div class="section">
            <div class="label">Engine Condition</div>
            <div class="value"><?php echo htmlspecialchars($report['engine_condition']); ?></div>
        </div>

        <div class="section">
            <div class="label">Body Condition</div>
            <div class="value"><?php echo htmlspecialchars($report['body_condition']); ?></div>
        </div>

        <div class="section">
            <div class="label">Electrical Condition</div>
            <div class="value"><?php echo htmlspecialchars($report['electrical_condition']); ?></div>
        </div>

        <div class="section">
            <div class="label">Test Drive Status</div>
            <div class="value"><?php echo htmlspecialchars($report['test_drive_status']); ?></div>
        </div>

        <div class="section">
            <div class="label">Inspector Comments</div>
            <div class="value" style="min-height: 80px;"><?php echo nl2br(htmlspecialchars($report['comments'])); ?></div>
        </div>

        <div class="rating-box">
            <div class="label" style="color: #94a3b8;">Overall Rating</div>
            <div class="rating-score"><?php echo $report['overall_rating']; ?>/10</div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px;" data-html2canvas-ignore="true">
            <button onclick="downloadPDF()" style="padding: 10px 20px; cursor: pointer; background: #0f172a; color: white; border: none; border-radius: 5px; font-weight: 600;">Download PDF</button>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.container');
            const opt = {
                margin:       [10, 10, 10, 10], // top, left, bottom, right
                filename:     'Inspection_Report_<?php echo $report_id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Temporarily hide the button container for the screenshot if data-html2canvas-ignore doesn't work as expected in all versions, 
            // but the attribute should handle it.
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
