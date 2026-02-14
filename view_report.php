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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        .report-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .report-header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            text-align: center;
        }

        .report-header h1 {
            margin: 0 0 8px;
            color: var(--text-dark);
            font-size: 2rem;
            font-weight: 800;
        }

        .report-id {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .meta-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }

        .meta-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-label i {
            font-size: 1rem;
            color: var(--primary);
        }

        .meta-value {
            font-size: 1.05rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .section-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .section-content {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            color: var(--text-dark);
            line-height: 1.6;
            border: 1px solid #e2e8f0;
        }

        .rating-card {
            background: linear-gradient(135deg, var(--primary) 0%, #059669 100%);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            margin-bottom: 24px;
        }

        .rating-label {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .rating-score {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .rating-text {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }

        .btn-download {
            background: var(--primary);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-download:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-back {
            background: white;
            color: var(--text-dark);
            padding: 14px 28px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .meta-grid {
                grid-template-columns: 1fr;
            }

            .report-header h1 {
                font-size: 1.5rem;
            }

            .rating-score {
                font-size: 3rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-download, .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <h1>Vehicle Inspection Report</h1>
            <div class="report-id">Report #<?php echo $report_id; ?></div>
        </div>

        <!-- Meta Information Grid -->
        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">
                    <i class="ph ph-car"></i> Vehicle
                </div>
                <div class="meta-value"><?php echo htmlspecialchars($report['vehicle_type']); ?></div>
            </div>

            <div class="meta-card">
                <div class="meta-label">
                    <i class="ph ph-calendar"></i> Inspection Date
                </div>
                <div class="meta-value"><?php echo date('F j, Y', strtotime($report['report_date'])); ?></div>
            </div>

            <div class="meta-card">
                <div class="meta-label">
                    <i class="ph ph-map-pin"></i> Location
                </div>
                <div class="meta-value"><?php echo htmlspecialchars($report['location']); ?></div>
            </div>

            <div class="meta-card">
                <div class="meta-label">
                    <i class="ph ph-user-circle"></i> Inspector
                </div>
                <div class="meta-value"><?php echo htmlspecialchars($report['expert_name']); ?></div>
            </div>
        </div>

        <!-- Overall Rating -->
        <div class="rating-card">
            <div class="rating-label">Overall Rating</div>
            <div class="rating-score"><?php echo $report['overall_rating']; ?><span style="font-size: 2.5rem; opacity: 0.8;">/10</span></div>
            <div class="rating-text">
                <?php 
                $rating = $report['overall_rating'];
                if ($rating >= 8) echo "Excellent Condition";
                elseif ($rating >= 6) echo "Good Condition";
                elseif ($rating >= 4) echo "Fair Condition";
                else echo "Needs Attention";
                ?>
            </div>
        </div>

        <!-- Engine Condition -->
        <div class="section-card">
            <div class="section-title">
                <i class="ph ph-engine"></i> Engine Condition
            </div>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($report['engine_condition'])); ?>
            </div>
        </div>

        <!-- Body Condition -->
        <div class="section-card">
            <div class="section-title">
                <i class="ph ph-car-simple"></i> Body Condition
            </div>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($report['body_condition'])); ?>
            </div>
        </div>

        <!-- Electrical Condition -->
        <div class="section-card">
            <div class="section-title">
                <i class="ph ph-lightning"></i> Electrical Condition
            </div>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($report['electrical_condition'])); ?>
            </div>
        </div>

        <!-- Test Drive Status -->
        <div class="section-card">
            <div class="section-title">
                <i class="ph ph-steering-wheel"></i> Test Drive Status
            </div>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($report['test_drive_status'])); ?>
            </div>
        </div>

        <!-- Inspector Comments -->
        <div class="section-card">
            <div class="section-title">
                <i class="ph ph-chat-text"></i> Inspector Comments
            </div>
            <div class="section-content" style="min-height: 80px;">
                <?php echo nl2br(htmlspecialchars($report['comments'])); ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons no-print" data-html2canvas-ignore="true">
            <a href="dashboard.php" class="btn-back">
                <i class="ph ph-arrow-left"></i> Back to Dashboard
            </a>
            <button onclick="downloadPDF()" class="btn-download">
                <i class="ph ph-download-simple"></i> Download PDF
            </button>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.report-container');
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Inspection_Report_<?php echo $report_id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
