<?php
session_start();
require 'db.php';

// Check if user is delivery personnel
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: login.php");
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
            WHEN o.status = 'Processing' THEN 1
            WHEN o.status = 'Out for Delivery' THEN 2
            WHEN o.status = 'Delivered' THEN 3
            WHEN o.status = 'Cancelled' THEN 4
            ELSE 5
        END,
        o.created_at DESC
");
$orders_query->bind_param("i", $delivery_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_assigned,
        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as pending_pickup,
        SUM(CASE WHEN status = 'Out for Delivery' THEN 1 ELSE 0 END) as out_for_delivery,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_today
    FROM orders 
    WHERE assigned_to = ? AND DATE(created_at) = CURDATE()
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
    <title>My Assigned Orders - Westley's Resto Café</title>
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
        }

        .sidebar {
            position: fixed;
            width: 275px;
            height: 100vh;
            background: var(--primary);
            color: #fff;
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            margin: 5px 10px;
            border-radius: 5px;
        }

        .sidebar a:hover, .sidebar a.active {
            background: var(--secondary);
            border-left: 3px solid var(--accent);
            transform: translateX(5px);
        }

        .sidebar a i {
            margin-right: 10px;
            font-size: 18px;
            min-width: 20px;
        }

        .main {
            margin-left: 275px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-icon.total { color: var(--info); }
        .stat-icon.pending { color: var(--warning); }
        .stat-icon.transit { color: var(--accent); }
        .stat-icon.delivered { color: var(--success); }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .orders-container {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            border-left: 4px solid var(--accent);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .order-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .order-meta {
            font-size: 14px;
            color: #6b7280;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .order-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }

        .info-section {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }

        .info-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section p {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-items {
            grid-column: 1 / -1;
            margin-top: 15px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .order-items h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .item-list {
            display: grid;
            gap: 8px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
            flex: 1;
        }

        .item-quantity {
            color: #6b7280;
            margin: 0 15px;
            font-size: 14px;
        }

        .item-price {
            font-weight: 600;
            color: var(--accent);
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

        .order-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .call-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
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

        .priority-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--danger);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 15px;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .order-content {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-actions {
                flex-direction: column;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Westley's Resto Cafe</h2>
    <a href="delivery_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="assigned_orders.php" class="active"><i class="fas fa-clipboard-list"></i> Assigned Orders</a>
    <a href="update_delivery_status.php"><i class="fas fa-truck"></i> Update Status</a>
    <a href="login.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-clipboard-list"></i> My Assigned Orders</h1>
        <div class="user-info">
            <div class="user-details">
                <h3><?php echo htmlspecialchars($delivery_name); ?></h3>
                <p>Delivery Personnel</p>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($delivery_name, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_assigned']; ?></div>
            <div class="stat-label">Today's Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo $stats['pending_pickup']; ?></div>
            <div class="stat-label">Pending Pickup</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon transit">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-value"><?php echo $stats['out_for_delivery']; ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon delivered">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['delivered_today']; ?></div>
            <div class="stat-label">Delivered Today</div>
        </div>
    </div>

    <!-- Orders List -->
    <div class="orders-container">
        <?php if ($orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    <?php if ($order['status'] === 'Processing'): ?>
                        <div class="priority-badge">
                            <i class="fas fa-exclamation"></i> PICKUP
                        </div>
                    <?php endif; ?>

                    <div class="order-header">
                        <div>
                            <div class="order-title">Order #<?php echo $order['id']; ?></div>
                            <div class="order-meta">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                            <?php echo $order['status']; ?>
                        </div>
                    </div>

                    <div class="order-content">
                        <div class="info-section">
                            <h4><i class="fas fa-user"></i> Customer Details</h4>
                            <p><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <?php if (!empty($order['customer_phone'])): ?>
                                <p>
                                    <i class="fas fa-phone"></i> 
                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                    <button class="call-btn" onclick="window.open('tel:<?php echo $order['customer_phone']; ?>')">
                                        <i class="fas fa-phone"></i> Call
                                    </button>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="info-section">
                            <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                            <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><i class="fas fa-credit-card"></i> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            <button class="call-btn" onclick="openMap('<?php echo urlencode($order['delivery_address']); ?>')">
                                <i class="fas fa-map"></i> Navigate
                            </button>
                        </div>

                        <div class="order-items">
                            <h4><i class="fas fa-utensils"></i> Order Items</h4>
                            <div class="item-list">
                                <?php
                                $items_query = $conn->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_id = m.id WHERE oi.order_id = ?");
                                $items_query->bind_param("i", $order['id']);
                                $items_query->execute();
                                $items_result = $items_query->get_result();
                                
                                while ($item = $items_result->fetch_assoc()):
                                ?>
                                    <div class="item-row">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                        <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="total-price">
                                <i class="fas fa-receipt"></i> Total Amount: ₹<?php echo number_format($order['total_price'], 2); ?>
                            </div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <?php if ($order['status'] === 'Processing'): ?>
                            <button class="action-btn btn-warning" onclick="updateStatus(<?php echo $order['id']; ?>, 'Out for Delivery')">
                                <i class="fas fa-truck"></i> Mark as Picked Up
                            </button>
                        <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                            <button class="action-btn btn-success" onclick="updateStatus(<?php echo $order['id']; ?>, 'Delivered')">
                                <i class="fas fa-check-circle"></i> Mark as Delivered
                            </button>
                        <?php endif; ?>
                        
                        <a href="tel:<?php echo $order['customer_phone']; ?>" class="action-btn btn-info">
                            <i class="fas fa-phone"></i> Call Customer
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Orders Assigned</h3>
                <p>You don't have any orders assigned to you at the moment. Please check back later or contact your admin.</p>
            </div>
        <?php endif; ?>
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
    const actionText = status === 'Out for Delivery' ? 'mark this order as picked up' : 'mark this order as delivered';
    
    if (!confirm(`Are you sure you want to ${actionText}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', status);

    fetch('assigned_orders.php', {
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

// Auto-refresh page every 2 minutes to get new orders
setInterval(() => {
    location.reload();
}, 120000);

// Add visual feedback for urgent orders
document.addEventListener('DOMContentLoaded', function() {
    const processingOrders = document.querySelectorAll('.status-processing');
    processingOrders.forEach(badge => {
        const card = badge.closest('.order-card');
        card.style.borderLeft = '4px solid var(--warning)';
    });
});
</script>

</body>
</html>