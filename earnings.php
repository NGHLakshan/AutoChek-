<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'expert') {
    header("Location: login.php");
    exit;
}

$expert_id = $_SESSION['user_id'];

// Fetch expert info
$expert = $conn->query("SELECT name FROM expert WHERE expert_id = $expert_id")->fetch_assoc();

// Calculate earnings statistics
$stats = [];

// Total Earnings (all completed and paid bookings)
$total_earnings_query = "SELECT COALESCE(SUM(p.amount), 0) as total 
                         FROM payment p 
                         JOIN booking b ON p.booking_id = b.booking_id 
                         WHERE b.expert_id = $expert_id";
$stats['total_earnings'] = $conn->query($total_earnings_query)->fetch_assoc()['total'];

// This Month's Earnings
$this_month_query = "SELECT COALESCE(SUM(p.amount), 0) as total 
                     FROM payment p 
                     JOIN booking b ON p.booking_id = b.booking_id 
                     WHERE b.expert_id = $expert_id 
                     AND MONTH(p.payment_date) = MONTH(CURRENT_DATE()) 
                     AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())";
$stats['this_month'] = $conn->query($this_month_query)->fetch_assoc()['total'];

// Pending Earnings (completed but not yet paid)
$pending_query = "SELECT COUNT(*) as count 
                  FROM booking b 
                  LEFT JOIN payment p ON b.booking_id = p.booking_id 
                  WHERE b.expert_id = $expert_id 
                  AND b.status = 'completed' 
                  AND p.payment_id IS NULL";
$stats['pending_count'] = $conn->query($pending_query)->fetch_assoc()['count'];

// Total Completed Jobs
$completed_query = "SELECT COUNT(*) as count 
                    FROM booking 
                    WHERE expert_id = $expert_id 
                    AND status = 'completed'";
$stats['completed_jobs'] = $conn->query($completed_query)->fetch_assoc()['count'];

// Fetch payment history
$payment_history_query = "SELECT p.*, b.vehicle_type, b.booking_date, u.name as buyer_name 
                          FROM payment p 
                          JOIN booking b ON p.booking_id = b.booking_id 
                          JOIN buyer u ON b.buyer_id = u.buyer_id 
                          WHERE b.expert_id = $expert_id 
                          ORDER BY p.payment_date DESC";
$payment_history = $conn->query($payment_history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .earnings-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .earnings-header h2 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .earnings-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card-large {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }
        
        .stat-card-large .label {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .stat-card-large .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-card-large .value.currency::before {
            content: 'Rs. ';
            font-size: 1.2rem;
            color: #64748b;
        }
        
        .stat-card-large.highlight {
            background: linear-gradient(135deg, #dcfce7 0%, #f0fdf4 100%);
            border-color: #86efac;
        }
        
        .stat-card-large.highlight .value {
            color: #15803d;
        }
        
        .payment-table {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #f1f5f9;
        }
        
        .payment-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payment-table th {
            text-align: left;
            padding: 12px;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .payment-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .payment-table tr:last-child td {
            border-bottom: none;
        }
        
        .amount-cell {
            font-weight: 600;
            color: #15803d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="earnings-header">
            <h2>💰 My Earnings</h2>
            <p>Track your income and payment history</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card-large highlight">
                <div class="label">Total Earnings</div>
                <div class="value currency"><?php echo number_format($stats['total_earnings'], 2); ?></div>
            </div>
            
            <div class="stat-card-large">
                <div class="label">This Month</div>
                <div class="value currency"><?php echo number_format($stats['this_month'], 2); ?></div>
            </div>
            
            <div class="stat-card-large">
                <div class="label">Completed Jobs</div>
                <div class="value"><?php echo $stats['completed_jobs']; ?></div>
            </div>
            
            <div class="stat-card-large">
                <div class="label">Pending Payments</div>
                <div class="value"><?php echo $stats['pending_count']; ?></div>
            </div>
        </div>

        <!-- Payment History -->
        <h3 class="section-title">Payment History</h3>
        <div class="payment-table">
            <?php if ($payment_history && $payment_history->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Buyer</th>
                            <th>Vehicle</th>
                            <th>Inspection Date</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $payment_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['buyer_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['vehicle_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($payment['booking_date'])); ?></td>
                                <td><?php echo ucfirst($payment['method']); ?></td>
                                <td class="amount-cell">Rs. <?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h3>No Payment History Yet</h3>
                    <p>Your payment history will appear here once buyers complete their payments.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
