<?php
include 'db.php';
session_start();

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Check if admin is logged in based on your authentication system
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_POST['report_month'])) {
    die('Invalid request');
}

$selected_month = $_POST['report_month'];
$month_name = date('F Y', strtotime($selected_month . '-01'));

// Validate the month format
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    die('Invalid month format');
}

// Get revenue data for the selected month
$revenue_query = "SELECT 
    SUM(CASE WHEN status != 'Cancelled' AND (payment_method = 'cash_on_delivery' OR (payment_method = 'razorpay' AND payment_status = 'paid')) THEN total_price ELSE 0 END) as total_revenue,
    COUNT(CASE WHEN status != 'Cancelled' THEN 1 END) as total_orders,
    COUNT(CASE WHEN payment_method = 'razorpay' AND payment_status = 'paid' THEN 1 END) as online_orders,
    COUNT(CASE WHEN payment_method = 'cash_on_delivery' AND status != 'Cancelled' THEN 1 END) as cod_orders,
    SUM(CASE WHEN payment_method = 'razorpay' AND payment_status = 'paid' THEN total_price ELSE 0 END) as online_revenue,
    SUM(CASE WHEN payment_method = 'cash_on_delivery' AND status != 'Cancelled' THEN total_price ELSE 0 END) as cod_revenue
    FROM orders 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";

$stmt = $conn->prepare($revenue_query);
$stmt->bind_param('s', $selected_month);
$stmt->execute();
$revenue_data = $stmt->get_result()->fetch_assoc();

// Get daily breakdown for the month
$daily_query = "SELECT 
    DATE(created_at) as order_date,
    SUM(CASE WHEN status != 'Cancelled' AND (payment_method = 'cash_on_delivery' OR (payment_method = 'razorpay' AND payment_status = 'paid')) THEN total_price ELSE 0 END) as daily_revenue,
    COUNT(CASE WHEN status != 'Cancelled' THEN 1 END) as daily_orders
    FROM orders 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY DATE(created_at)
    ORDER BY order_date";

$stmt2 = $conn->prepare($daily_query);
$stmt2->bind_param('s', $selected_month);
$stmt2->execute();
$daily_data = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Get top selling items for the month
$top_items_query = "SELECT 
    mi.name as item_name,
    mi.category,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price) as item_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE_FORMAT(o.created_at, '%Y-%m') = ? 
    AND o.status != 'Cancelled' 
    AND (o.payment_method = 'cash_on_delivery' OR (o.payment_method = 'razorpay' AND o.payment_status = 'paid'))
    GROUP BY oi.menu_id, mi.name, mi.category
    ORDER BY item_revenue DESC
    LIMIT 10";

$stmt3 = $conn->prepare($top_items_query);
$stmt3->bind_param('s', $selected_month);
$stmt3->execute();
$top_items = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for PDF download
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Revenue Report - <?php echo $month_name; ?></title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 20px;
            color: #f59e0b;
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 14px;
            color: #6b7280;
        }
        
        .summary-section {
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 18px;
            color: #111827;
            margin-bottom: 15px;
            border-left: 4px solid #f59e0b;
            padding-left: 15px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            text-align: center;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            color: #111827;
            margin-bottom: 15px;
            border-left: 4px solid #f59e0b;
            padding-left: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #111827;
            border-bottom: 2px solid #f59e0b;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        tr:hover {
            background-color: #f3f4f6;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .currency {
            color: #059669;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 20px;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        
        .action-btn {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        
        .back-btn {
            background: #6b7280;
        }
        
        .back-btn:hover {
            background: #4b5563;
        }
        
        @media print {
            .action-buttons {
                display: none;
            }
            
            body {
                -webkit-print-color-adjust: exact;
            }
            
            .header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            
            table {
                font-size: 13px;
            }
            
            .summary-grid {
                gap: 15px;
                margin-bottom: 15px;
            }
        }
        
        .highlight {
            background-color: #fef3c7;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <a href="admin_dashboard.php" class="action-btn back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        <button class="action-btn" onclick="window.print()">
            <i class="fas fa-print"></i>
            Print / Save PDF
        </button>
    </div>
    
    <div class="header">
        <div class="logo">Westley's Resto Cafe</div>
        <div class="report-title">Monthly Revenue Report</div>
        <div class="report-period"><?php echo $month_name; ?></div>
        <div class="report-period">Generated on: <?php echo date('F d, Y h:i A'); ?></div>
    </div>
    
    <div class="summary-section">
        <h2 class="summary-title">Revenue Summary</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-value currency">₹<?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></div>
                <div class="summary-label">Total Revenue</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($revenue_data['total_orders'] ?? 0); ?></div>
                <div class="summary-label">Total Orders</div>
            </div>
            <div class="summary-card">
                <div class="summary-value currency">₹<?php echo number_format($revenue_data['online_revenue'] ?? 0, 2); ?></div>
                <div class="summary-label">Online Revenue</div>
            </div>
            <div class="summary-card">
                <div class="summary-value currency">₹<?php echo number_format($revenue_data['cod_revenue'] ?? 0, 2); ?></div>
                <div class="summary-label">COD Revenue</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($revenue_data['online_orders'] ?? 0); ?></div>
                <div class="summary-label">Online Orders</div>
            </div>
            <div class="summary-card">
                <div class="summary-value"><?php echo number_format($revenue_data['cod_orders'] ?? 0); ?></div>
                <div class="summary-label">COD Orders</div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">Daily Revenue Breakdown</h2>
        <?php if (!empty($daily_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_data as $day): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($day['order_date'])); ?></td>
                    <td><?php echo date('l', strtotime($day['order_date'])); ?></td>
                    <td class="text-center"><?php echo number_format($day['daily_orders']); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($day['daily_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #fef3c7;">
                    <td colspan="2">TOTAL</td>
                    <td class="text-center"><?php echo number_format($revenue_data['total_orders']); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($revenue_data['total_revenue'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">No orders found for this month.</div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">Top Selling Items</h2>
        <?php if (!empty($top_items)): ?>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th class="text-center">Quantity Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_items as $index => $item): ?>
                <tr>
                    <td class="text-center">
                        <?php 
                        echo $index + 1;
                        if ($index == 0) echo " 🥇";
                        elseif ($index == 1) echo " 🥈";
                        elseif ($index == 2) echo " 🥉";
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td class="text-center"><?php echo number_format($item['total_quantity']); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($item['item_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">No items sold in this month.</div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">Payment Method Analysis</h2>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Online Payment (Razorpay)</td>
                    <td class="text-center"><?php echo number_format($revenue_data['online_orders'] ?? 0); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($revenue_data['online_revenue'] ?? 0, 2); ?></td>
                    <td class="text-right">
                        <?php 
                        $online_percentage = $revenue_data['total_revenue'] > 0 ? ($revenue_data['online_revenue'] / $revenue_data['total_revenue']) * 100 : 0;
                        echo number_format($online_percentage, 1) . '%';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Cash on Delivery</td>
                    <td class="text-center"><?php echo number_format($revenue_data['cod_orders'] ?? 0); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($revenue_data['cod_revenue'] ?? 0, 2); ?></td>
                    <td class="text-right">
                        <?php 
                        $cod_percentage = $revenue_data['total_revenue'] > 0 ? ($revenue_data['cod_revenue'] / $revenue_data['total_revenue']) * 100 : 0;
                        echo number_format($cod_percentage, 1) . '%';
                        ?>
                    </td>
                </tr>
                <tr style="font-weight: bold; background-color: #fef3c7;">
                    <td>TOTAL</td>
                    <td class="text-center"><?php echo number_format($revenue_data['total_orders'] ?? 0); ?></td>
                    <td class="text-right currency">₹<?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p><strong>Westley's Resto Café</strong> - Revenue Report for <?php echo $month_name; ?></p>
        <p>Generated on <?php echo date('F d, Y \a\t h:i A'); ?> | Excludes cancelled orders</p>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
        
        // Add print functionality
        function printReport() {
            window.print();
        }
        
        // Add keyboard shortcut for printing (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>