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
$payment_history_query = "SELECT p.*, b.vehicle_type, b.booking_date, b.booking_id, u.name as buyer_name, u.profile_photo 
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
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .header-card h2 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }
        
        .header-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.05rem;
            font-weight: 400;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            margin-top: -30px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        
        /* Specific Card Styles */
        .stat-total .stat-icon { background: #ecfdf5; color: #059669; }
        .stat-month .stat-icon { background: #eff6ff; color: #2563eb; }
        .stat-jobs .stat-icon { background: #f5f3ff; color: #7c3aed; }
        .stat-pending .stat-icon { background: #fff7ed; color: #ea580c; }

        .table-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 16px 24px;
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; transition: background 0.1s; }
        
        .amount-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: #475569;
            font-weight: 500;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-small {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #64748b;
            overflow: hidden;
            font-weight: 700;
            border: 1px solid white;
            box-shadow: 0 0 0 1px #cbd5e1;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #94a3b8;
        }
        
        @media (max-width: 768px) {
            .header-card { padding: 30px 20px; border-radius: 0 0 20px 20px; margin: -20px -20px 30px -20px; }
            .stats-grid { padding: 0; margin-top: 0; }
            th, td { padding: 12px 15px; }
            .hide-mobile { display: none; }
            .stat-value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        
        <div class="header-card">
            <h2><i class="ph-fill ph-wallet"></i> My Earnings</h2>
            <p>Track your income, view payment history, and manage your finances.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="ph-fill ph-money"></i></div>
                <div class="stat-label">Total Earnings</div>
                <div class="stat-value">Rs. <?php echo number_format($stats['total_earnings'], 2); ?></div>
            </div>
            
            <div class="stat-card stat-month">
                <div class="stat-icon"><i class="ph-fill ph-calendar-check"></i></div>
                <div class="stat-label">This Month</div>
                <div class="stat-value">Rs. <?php echo number_format($stats['this_month'], 2); ?></div>
            </div>
            
            <div class="stat-card stat-jobs">
                <div class="stat-icon"><i class="ph-fill ph-check-circle"></i></div>
                <div class="stat-label">Completed Jobs</div>
                <div class="stat-value"><?php echo $stats['completed_jobs']; ?></div>
            </div>
            
            <div class="stat-card stat-pending">
                <div class="stat-icon"><i class="ph-fill ph-clock-countdown"></i></div>
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title"><i class="ph ph-receipt"></i> Payment History</div>
                <!-- Optional: Filter or Export buttons could go here -->
            </div>
            
            <?php if ($payment_history && $payment_history->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Buyer</th>
                                <th>Vehicle Info</th>
                                <th class="hide-mobile">Inspection Date</th>
                                <th>Method</th>
                                <th style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payment_history->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b;"><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="avatar-small">
                                                <?php if (!empty($payment['profile_photo'])): ?>
                                                    <img src="uploads/profiles/<?php echo $payment['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($payment['buyer_name'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($payment['buyer_name']); ?></div>
                                                <div style="font-size: 0.75rem; color: #64748b;">ID: <?php echo $payment['booking_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
                                            <i class="ph-fill ph-car" style="color: #64748b; font-size: 1.1rem;"></i>
                                            <?php echo htmlspecialchars($payment['vehicle_type']); ?>
                                        </div>
                                    </td>
                                    <td class="hide-mobile">
                                        <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; color: #475569;">
                                            <?php echo date('M j, Y', strtotime($payment['booking_date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="payment-method">
                                            <?php if ($payment['method'] == 'card'): ?>
                                                <i class="ph-fill ph-credit-card" style="color: #2563eb; font-size: 1.1rem;"></i> Card
                                            <?php else: ?>
                                                <i class="ph-fill ph-money" style="color: #16a34a; font-size: 1.1rem;"></i> Cash
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="amount-badge">
                                            LKR <?php echo number_format($payment['amount'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">📉</div>
                    <h3 style="color: #1e293b; margin: 0 0 10px 0;">No Payment History Yet</h3>
                    <p>Your finished inspection payments will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
