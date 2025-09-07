<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $response = ['success' => false, 'message' => ''];
    
    $order_id = intval($_POST['order_id']);
    
    // Verify this order belongs to the current user and can be cancelled
    $verify_stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND (status = 'Pending' OR status = 'Processing') AND assigned_to IS NULL");
    $verify_stmt->bind_param("ii", $order_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Update order status to cancelled
        $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
        $cancel_stmt->bind_param("ii", $order_id, $user_id);
        
        if ($cancel_stmt->execute()) {
            $response = ['success' => true, 'message' => 'Order cancelled successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to cancel order. Please try again.'];
        }
    } else {
        $response = ['success' => false, 'message' => 'This order cannot be cancelled. It may have already been assigned for delivery or is in an advanced stage.'];
    }
    
    header('Content-Type: application/json');
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
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo h1 {
            color: #cda45e;
            margin: 0;
            font-family: "Playfair Display", serif;
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
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
        }
        
        .Pending { background: #fef3c7; color: #92400e; }
        .Processing { background: #bfdbfe; color: #1e3a8a; }
        .Out { background: #fef08a; color: #854d0e; }
        .Delivered { background: #d1fae5; color: #065f46; }
        .Cancelled { background: #fee2e2; color: #991b1b; }

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
        
        .cancel-btn:disabled {
            background-color: #6b7280;
            cursor: not-allowed;
            transform: none;
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
        
        .empty-state a:hover {
            text-decoration: underline;
        }
        
        /* Notification */
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
        
        .notification.success {
            background: #10b981;
        }
        
        .notification.error {
            background: #ef4444;
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
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: #29261f;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            color: rgba(255,255,255,0.9);
        }
        
        .modal h3 {
            color: #cda45e;
            margin-bottom: 15px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .modal-btn.confirm {
            background-color: #ef4444;
            color: white;
        }
        
        .modal-btn.cancel {
            background-color: #6b7280;
            color: white;
        }
        
        .modal-btn:hover {
            transform: translateY(-1px);
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
            
            .nav-links {
                gap: 10px;
            }
            
            .header-content {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
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
            <div class="order-card">
                <div class="order-header">
                    <h2>Order #<?php echo $order['id']; ?></h2>
                    <span class="status <?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                </div>
                
                <div class="order-info">
                    <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($order['created_at'])); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><i class="fas fa-credit-card"></i> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                </div>

                <?php
                // Fetch items for this order
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
                            <div class="item-name"><?php echo $item['name']; ?></div>
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
                    // Show cancel button only for orders that can be cancelled
                    $can_cancel = ($order['status'] === 'Pending' || $order['status'] === 'Processing') && 
                                  $order['assigned_to'] === null;
                    ?>
                    
                    <?php if ($can_cancel): ?>
                        <button class="btn cancel-btn" onclick="showCancelModal(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    <?php elseif ($order['status'] === 'Cancelled'): ?>
                        <span style="color: #ef4444; font-weight: bold;">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </span>
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

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Cancel Order</h3>
        <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
        <div class="modal-buttons">
            <button class="modal-btn confirm" onclick="cancelOrder()">Yes, Cancel</button>
            <button class="modal-btn cancel" onclick="closeCancelModal()">No, Keep Order</button>
        </div>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification"></div>

<script>
let orderToCancel = null;

function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 4000);
}

function showCancelModal(orderId) {
    orderToCancel = orderId;
    document.getElementById('cancelModal').style.display = 'block';
}

function closeCancelModal() {
    orderToCancel = null;
    document.getElementById('cancelModal').style.display = 'none';
}

function cancelOrder() {
    if (!orderToCancel) return;
    
    const formData = new FormData();
    formData.append('action', 'cancel_order');
    formData.append('order_id', orderToCancel);
    
    fetch('my_orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        closeCancelModal();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        closeCancelModal();
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('cancelModal');
    if (event.target === modal) {
        closeCancelModal();
    }
}
</script>

</body>
</html>