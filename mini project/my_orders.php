<?php
// Enhanced my_orders.php with real-time status updates
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// AJAX endpoint for real-time status updates
if (isset($_GET['action']) && $_GET['action'] === 'get_status_updates') {
    header('Content-Type: application/json');
    
    $orders_query = $conn->prepare("SELECT id, status, payment_status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $orders_query->bind_param("i", $user_id);
    $orders_query->execute();
    $orders_result = $orders_query->get_result();
    
    $orders = [];
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
    exit();
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    $order_id = intval($_POST['order_id']);
    
    $verify_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND (status = 'Pending' OR status = 'Processing' OR status = 'Payment Pending') AND (assigned_to IS NULL OR assigned_to = '')");
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $order = $verify_result->fetch_assoc();
        
        if (isset($order['payment_status']) && $order['payment_status'] === 'paid') {
            $response = ['success' => false, 'message' => 'Cannot cancel order with successful payment. Please contact support.'];
        } else {
            $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
            $cancel_stmt->bind_param("ii", $order_id, $user_id);
            
            if ($cancel_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Order cancelled successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to cancel order. Please try again.'];
            }
        }
    } else {
        $response = ['success' => false, 'message' => 'This order cannot be cancelled.'];
    }
    
    echo json_encode($response);
    exit();
}

// Fetch all orders for this customer
$orders_query = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_query->bind_param("i", $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Westley's Resto Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #0c0b09;
            color: rgba(255,255,255,0.8);
            font-family: "Roboto", sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #0c0b09;
            border-bottom: 1px solid #29261f;
            z-index: 1000;
            padding: 15px 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo h1 {
            color: #cda45e;
            margin: 0;
            font-family: "Playfair Display", serif;
            font-size: 24px;
        }
        
        .nav-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: #cda45e;
            color: #0c0b09;
        }
        
        .container {
            width: 90%;
            max-width: 900px;
            margin: 120px auto 50px auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            color: #cda45e;
            margin-bottom: 40px;
            font-family: "Playfair Display", serif;
            font-size: 32px;
        }
        
        .order-card {
            background-color: #29261f;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 2px solid #4CAF50;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-card h2 {
            margin: 0;
            color: #cda45e;
            font-size: 20px;
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .status-update-animation {
            animation: statusUpdate 0.6s ease;
        }
        
        @keyframes statusUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); background-color: #f59e0b; }
            100% { transform: scale(1); }
        }
        
        .Pending { background: #fef3c7; color: #92400e; }
        .Processing { background: #bfdbfe; color: #1e3a8a; }
        .Confirmed { background: #d1fae5; color: #065f46; }
        .Out\ for\ Delivery { background: #fef08a; color: #854d0e; }
        .Delivered { background: #d1fae5; color: #065f46; }
        .Cancelled { background: #fee2e2; color: #991b1b; }
        .Payment\ Failed { background: #fee2e2; color: #991b1b; }
        .Payment\ Pending { background: #fef3c7; color: #92400e; }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .order-info p {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .order-info i {
            color: #cda45e;
            width: 16px;
        }

        .payment-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .payment-status.paid {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .payment-status.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .payment-status.failed {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .order-items {
            margin: 20px 0;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 15px;
        }
        
        .items-header {
            color: #cda45e;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 600;
            color: #ffffff;
            flex: 1;
        }
        
        .item-quantity, .item-price {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            min-width: 80px;
            text-align: right;
        }
        
        .total {
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #cda45e;
            font-weight: bold;
            color: #cda45e;
            font-size: 18px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .view-btn {
            background-color: #cda45e;
            color: #0c0b09;
        }
        
        .view-btn:hover {
            background-color: #f7a25e;
            transform: translateY(-1px);
        }
        
        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }
        
        .cancel-btn:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }
        
        .retry-payment-btn {
            background-color: #f59e0b;
            color: white;
        }
        
        .retry-payment-btn:hover {
            background-color: #d97706;
            transform: translateY(-1px);
        }
        
        .refresh-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 14px;
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        
        .refresh-indicator.show {
            display: flex;
        }
        
        .notification {
            position: fixed;
            top: 90px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            display: none;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }
        
        .notification.success { background: #10b981; }
        .notification.error { background: #ef4444; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.6);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #cda45e;
        }
        
        .empty-state h3 {
            color: #cda45e;
            margin-bottom: 15px;
        }
        
        .empty-state a {
            color: #cda45e;
            text-decoration: none;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 100px auto 30px auto;
                padding: 10px;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo">
            <h1>Westley's Resto Cafe</h1>
        </div>
        <div class="nav-links">
            <a href="menu.php"><i class="fas fa-utensils"></i> Menu</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1><i class="fas fa-clipboard-list"></i> My Orders</h1>

    <?php if ($orders_result->num_rows > 0): ?>
        <?php while ($order = $orders_result->fetch_assoc()): ?>
            <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                <div class="order-header">
                    <h2>Order Id #<?php echo $order['id']; ?></h2>
                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>" data-status="<?php echo $order['status']; ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                
                <div class="order-info">
                    <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($order['created_at'])); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><i class="fas fa-credit-card"></i> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                </div>

                <?php if (isset($order['payment_status']) && $order['payment_method'] === 'razorpay'): ?>
                    <div class="payment-status <?php echo $order['payment_status']; ?>">
                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <i class="fas fa-check-circle"></i> Payment Successful
                        <?php elseif ($order['payment_status'] === 'pending'): ?>
                            <i class="fas fa-clock"></i> Payment Pending
                        <?php elseif ($order['payment_status'] === 'failed'): ?>
                            <i class="fas fa-times-circle"></i> Payment Failed
                        <?php endif; ?>
                    </div>
                <?php elseif ($order['payment_method'] === 'cash_on_delivery'): ?>
                    <div class="payment-status" style="background: rgba(156, 163, 175, 0.2); color: #9ca3af; border-color: rgba(156, 163, 175, 0.3);">
                        <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                    </div>
                <?php endif; ?>

                <?php
                $items_query = $conn->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_id = m.id WHERE oi.order_id = ?");
                $items_query->bind_param("i", $order['id']);
                $items_query->execute();
                $items_result = $items_query->get_result();
                ?>
                <div class="order-items">
                    <div class="items-header">
                        <i class="fas fa-receipt"></i> Order Items
                    </div>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="item-row">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-quantity">₹<?php echo $item['price']; ?> × <?php echo $item['quantity']; ?></div>
                            <div class="item-price">₹<?php echo $item['price'] * $item['quantity']; ?></div>
                        </div>
                    <?php endwhile; ?>
                    <div class="total">Total: ₹<?php echo $order['total_price']; ?></div>
                </div>

                <div class="action-buttons">
                    <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn view-btn">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    
                    <?php 
                    $can_cancel = ($order['status'] === 'Pending' || $order['status'] === 'Processing' || $order['status'] === 'Payment Pending') && 
                                  (!isset($order['payment_status']) || $order['payment_status'] !== 'paid');
                    
                    $can_retry_payment = isset($order['payment_status']) && 
                                        $order['payment_status'] === 'failed' && 
                                        $order['payment_method'] === 'razorpay' &&
                                        $order['status'] !== 'Cancelled';
                    ?>
                    
                    <?php if ($can_cancel): ?>
                        <button class="btn cancel-btn" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($can_retry_payment): ?>
                        <a href="retry_payment.php?order_id=<?php echo $order['id']; ?>" class="btn retry-payment-btn">
                            <i class="fas fa-redo"></i> Retry Payment
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Orders Found</h3>
            <p>You haven't placed any orders yet.</p>
            <p><a href="menu.php">Browse our menu and place your first order!</a></p>
        </div>
    <?php endif; ?>
</div>

<!-- Refresh Indicator -->
<div class="refresh-indicator" id="refresh-indicator">
    <i class="fas fa-sync-alt"></i>
    <span>Checking for updates...</span>
</div>

<!-- Notification -->
<div class="notification" id="notification"></div>

<script>
let lastOrderStatuses = {};

function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 4000);
}

function checkForStatusUpdates() {
    const indicator = document.getElementById('refresh-indicator');
    indicator.classList.add('show');
    
    fetch('my_orders.php?action=get_status_updates')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.orders.forEach(order => {
                    const orderCard = document.querySelector(`[data-order-id="${order.id}"]`);
                    const statusElement = orderCard?.querySelector('[data-status]');
                    
                    if (orderCard && statusElement) {
                        const currentStatus = statusElement.dataset.status;
                        const newStatus = order.status;
                        
                        // Check if status changed
                        if (lastOrderStatuses[order.id] && lastOrderStatuses[order.id] !== newStatus) {
                            // Status updated - show animation and notification
                            statusElement.classList.add('status-update-animation');
                            statusElement.textContent = newStatus;
                            statusElement.dataset.status = newStatus;
                            statusElement.className = `status status-${newStatus.toLowerCase().replace(/ /g, '-')} status-update-animation`;
                            
                            showNotification(`Order #${order.id} status updated to: ${newStatus}`, 'success');
                            
                            // Remove animation class after animation completes
                            setTimeout(() => {
                                statusElement.classList.remove('status-update-animation');
                            }, 600);
                        }
                        
                        lastOrderStatuses[order.id] = newStatus;
                    }
                });
            }
            
            indicator.classList.remove('show');
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
            indicator.classList.remove('show');
        });
}

function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'cancel_order');
    formData.append('order_id', orderId);
    
    fetch('my_orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize status tracking on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize last order statuses
    document.querySelectorAll('.order-card').forEach(orderCard => {
        const statusElement = orderCard.querySelector('[data-status]');
        if (statusElement) {
            const status = statusElement.dataset.status;
            const orderId = orderCard.dataset.orderId;
            lastOrderStatuses[orderId] = status;
        }
    });
    
    // Check for updates every 30 seconds
    setInterval(checkForStatusUpdates, 30000);
    
    // Check for updates when page becomes visible (user returns to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(checkForStatusUpdates, 1000);
        }
    });
});
</script>

</body>
</html>