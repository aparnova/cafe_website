<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: homepage.php");
    exit();
}

$delivery_id = $_SESSION['delivery_id'];
$delivery_name = $_SESSION['user'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        // Verify this order is assigned to current delivery person
        $verify_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND assigned_to = ?");
        $verify_stmt->bind_param("ii", $order_id, $delivery_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND assigned_to = ?");
            $stmt->bind_param("sii", $status, $order_id, $delivery_id);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Order status updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update order status.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Unauthorized access to this order.'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch orders assigned to this delivery person
$orders_query = $conn->prepare("
    SELECT o.*, 
           u.fullname as customer_name, 
           u.email as customer_email,
           u.phone as customer_phone
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.assigned_to = ? 
    ORDER BY 
        CASE 
            WHEN o.status = 'Confirmed' THEN 1
            WHEN o.status = 'Processing' THEN 2
            WHEN o.status = 'Out for Delivery' THEN 3
            WHEN o.status = 'Delivered' THEN 4
            WHEN o.status = 'Cancelled' THEN 5
            ELSE 6
        END,
        o.created_at DESC
");
$orders_query->bind_param("i", $delivery_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

// Get enhanced statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status IN ('Confirmed', 'Processing', 'Out for Delivery') THEN 1 ELSE 0 END) as active_orders,
        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as pending_pickup,
        SUM(CASE WHEN status = 'Out for Delivery' THEN 1 ELSE 0 END) as out_for_delivery,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as total_delivered,
        SUM(CASE WHEN status = 'Delivered' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today,
        SUM(CASE WHEN status = 'Delivered' THEN total_price ELSE 0 END) as total_earnings
    FROM orders 
    WHERE assigned_to = ?
");
$stats_query->bind_param("i", $delivery_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Portal - Westley's Resto Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #111827;
            --secondary: #1f2937;
            --accent: #f59e0b;
            --light: #f3f4f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
            overflow-x: hidden;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .user-details h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .user-details p {
            font-size: 13px;
            color: #6b7280;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card.total { border-left-color: var(--info); }
        .stat-card.active { border-left-color: var(--warning); }
        .stat-card.pending { border-left-color: #f97316; }
        .stat-card.transit { border-left-color: var(--accent); }
        .stat-card.delivered { border-left-color: var(--success); }
        .stat-card.earnings { border-left-color: #8b5cf6; }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-icon.total { color: var(--info); }
        .stat-icon.active { color: var(--warning); }
        .stat-icon.pending { color: #f97316; }
        .stat-icon.transit { color: var(--accent); }
        .stat-icon.delivered { color: var(--success); }
        .stat-icon.earnings { color: #8b5cf6; }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            background: var(--primary);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h2 {
            font-size: 20px;
            margin: 0;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .orders-table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-processing {
            background: #bfdbfe;
            color: #1e3a8a;
        }

        .status-out-for-delivery {
            background: #fef08a;
            color: #854d0e;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
        }

        .payment-razorpay-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .payment-razorpay-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-razorpay-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-cod {
            background: #e5e7eb;
            color: #374151;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .order-items-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary);
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .order-info-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent);
        }

        .order-info-section h4 {
            margin: 0 0 15px 0;
            color: var(--primary);
            font-size: 16px;
        }

        .order-info-section p {
            margin: 8px 0;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-info-section i {
            width: 16px;
            color: var(--accent);
        }

        .category-header {
            background: var(--primary);
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 15px 0 10px 0;
            border-radius: 6px;
        }

        .item-list {
            display: grid;
            gap: 10px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .item-description {
            font-size: 12px;
            color: #6b7280;
            font-style: italic;
        }

        .item-quantity {
            color: #6b7280;
            margin: 0 20px;
            font-size: 14px;
            font-weight: 500;
            min-width: 60px;
            text-align: center;
        }

        .item-price {
            font-weight: 700;
            color: var(--accent);
            font-size: 16px;
            min-width: 80px;
            text-align: right;
        }

        .total-price {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--accent);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            display: none;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }

        .notification.success {
            background: var(--success);
        }

        .notification.error {
            background: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d1d5db;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .orders-table {
                font-size: 12px;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .orders-table {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>
            <i class="fas fa-truck"></i>
            Delivery Portal - Westley's Resto Café
        </h1>
        <div class="user-info">
            <div class="user-details">
                <h3><?php echo htmlspecialchars($delivery_name); ?></h3>
                <p>Delivery Personnel</p>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($delivery_name, 0, 1)); ?>
            </div>
            <a href="login.php?logout=true" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card active">
            <div class="stat-icon active">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-value"><?php echo $stats['active_orders']; ?></div>
            <div class="stat-label">Active Orders</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo $stats['pending_pickup']; ?></div>
            <div class="stat-label">Pending Pickup</div>
        </div>
        <div class="stat-card transit">
            <div class="stat-icon transit">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-value"><?php echo $stats['out_for_delivery']; ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-card delivered">
            <div class="stat-icon delivered">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_delivered']; ?></div>
            <div class="stat-label">Total Delivered</div>
        </div>
        <div class="stat-card earnings">
            <div class="stat-icon earnings">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-value">₹<?php echo number_format($stats['total_earnings'], 0); ?></div>
            <div class="stat-label">Total Earnings</div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <div class="table-header">
            <i class="fas fa-list"></i>
            <h2>Assigned Orders</h2>
        </div>
        
        <?php if ($orders_result->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Amount & Payment</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <a href="tel:<?php echo $order['customer_phone']; ?>" class="action-btn btn-info">
                                        <i class="fas fa-phone"></i> <?php echo $order['customer_phone']; ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['delivery_address']); ?>
                                <br>
                                <button class="action-btn btn-info" onclick="openMap('<?php echo urlencode($order['delivery_address']); ?>')" style="margin-top: 5px;">
                                    <i class="fas fa-map"></i> Navigate
                                </button>
                            </td>
                            <td>
                                <strong style="font-size: 16px;">₹<?php echo number_format($order['total_price'], 2); ?></strong>
                                <br>
                                <?php 
                                $payment_method = $order['payment_method'];
                                $payment_status = isset($order['payment_status']) ? $order['payment_status'] : '';
                                
                                if ($payment_method === 'razorpay'): 
                                    switch ($payment_status) {
                                        case 'paid':
                                            echo '<span class="payment-badge payment-razorpay-paid"><i class="fas fa-check-circle"></i> Paid Online</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="payment-badge payment-razorpay-pending"><i class="fas fa-clock"></i> Payment Pending</span>';
                                            break;
                                        case 'failed':
                                            echo '<span class="payment-badge payment-razorpay-failed"><i class="fas fa-times-circle"></i> Payment Failed</span>';
                                            break;
                                        default:
                                            echo '<span class="payment-badge payment-razorpay-pending"><i class="fas fa-question-circle"></i> Unknown Status</span>';
                                    }
                                elseif ($payment_method === 'cash_on_delivery'): 
                                    echo '<span class="payment-badge payment-cod"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</span>';
                                endif; 
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                <br>
                                <small style="color: #6b7280;"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                            </td>
                            <td>
                                <button class="order-items-btn" onclick="showOrderItems(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Items
                                </button>
                            </td>
                            <td>
                                <?php if ($order['status'] === 'Confirmed'): ?>
                                    <button class="action-btn btn-warning" onclick="updateStatus(<?php echo $order['id']; ?>, 'Processing')">
                                        <i class="fas fa-play"></i> Start Preparing
                                    </button>
                                <?php elseif ($order['status'] === 'Processing'): ?>
                                    <button class="action-btn btn-warning" onclick="updateStatus(<?php echo $order['id']; ?>, 'Out for Delivery')">
                                        <i class="fas fa-truck"></i> Picked Up
                                    </button>
                                <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                                    <button class="action-btn btn-success" onclick="updateStatus(<?php echo $order['id']; ?>, 'Delivered')">
                                        <i class="fas fa-check-circle"></i> Delivered
                                    </button>
                                <?php elseif ($order['status'] === 'Delivered'): ?>
                                    <span style="color: #10b981; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                <?php elseif ($order['status'] === 'Cancelled'): ?>
                                    <span style="color: #ef4444; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-times-circle"></i> Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Orders Assigned</h3>
                <p>You don't have any orders assigned to you at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Items Modal -->
<div id="orderItemsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Items</h3>
            <span class="close">&times;</span>
        </div>
        <div id="orderItemsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification"></div>

<script>
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 4000);
}

function updateStatus(orderId, status) {
    let actionText = '';
    switch (status) {
        case 'Processing':
            actionText = 'start preparing this order';
            break;
        case 'Out for Delivery':
            actionText = 'mark this order as picked up';
            break;
        case 'Delivered':
            actionText = 'mark this order as delivered';
            break;
        default:
            actionText = `update this order status to ${status}`;
    }
    
    if (!confirm(`Are you sure you want to ${actionText}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', status);

    fetch('delivery_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function openMap(address) {
    const mapUrl = `https://www.google.com/maps/search/?api=1&query=${address}`;
    window.open(mapUrl, '_blank');
}

function showOrderItems(orderId) {
    fetch(`get_order_items.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let itemsHtml = '<div class="item-list">';
                let total = 0;
                
                data.items.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    
                    itemsHtml += `
                        <div class="item-row">
                            <div class="item-name">${item.name}</div>
                            <div class="item-quantity">Qty: ${item.quantity}</div>
                            <div class="item-price">₹${itemTotal.toFixed(2)}</div>
                        </div>
                    `;
                });
                
                itemsHtml += '</div>';
                itemsHtml += `<div class="total-price">Total Amount: ₹${total.toFixed(2)}</div>`;
                
                document.getElementById('orderItemsContent').innerHTML = itemsHtml;
                document.getElementById('orderItemsModal').style.display = 'block';
            } else {
                showNotification('Failed to load order items', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while loading order items', 'error');
        });
}

// Modal functionality
const modal = document.getElementById('orderItemsModal');
const closeBtn = document.querySelector('.close');

closeBtn.onclick = function() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Auto-refresh page every 2 minutes to get new orders
setInterval(() => {
    location.reload();
}, 120000);
</script>

</body>
</html>