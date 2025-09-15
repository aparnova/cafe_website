<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    header("Location: my_orders.php");
    exit();
}

// Fetch order details
$order_query = $conn->prepare("SELECT o.*, u.fullname as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$order_query->bind_param("ii", $order_id, $user_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows === 0) {
    header("Location: my_orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = $conn->prepare("SELECT oi.*, m.name, m.image FROM order_items oi JOIN menu_items m ON oi.menu_id = m.id WHERE oi.order_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();

$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Westley's Resto Cafe</title>
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
            margin: 0 5px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: #cda45e;
            color: #0c0b09;
        }
        
        .container {
            max-width: 900px;
            margin: 120px auto 50px auto;
            padding: 20px;
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }
        
        .success-icon i {
            font-size: 36px;
            color: white;
        }
        
        .success-title {
            color: #10b981;
            font-size: 32px;
            font-family: "Playfair Display", serif;
            margin-bottom: 10px;
        }
        
        .success-subtitle {
            font-size: 18px;
            color: rgba(255,255,255,0.8);
        }
        
        .customer-name {
            color: #cda45e;
            font-weight: bold;
        }
        
        .order-details {
            background-color: #29261f;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #10b981;
            margin-bottom: 30px;
        }
        
        .order-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .order-number {
            font-size: 28px;
            color: #cda45e;
            font-family: "Playfair Display", serif;
            margin-bottom: 15px;
        }
        
        .order-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        
        /* Status styles */
        .Pending { background: #fef3c7; color: #92400e; }
        .Processing { background: #bfdbfe; color: #1e3a8a; }
        .Confirmed { background: #d1fae5; color: #065f46; }
        .Out { background: #fef08a; color: #854d0e; }
        .Delivered { background: #d1fae5; color: #065f46; }
        .Cancelled { background: #fee2e2; color: #991b1b; }
        .Payment\ Failed { background: #fee2e2; color: #991b1b; }
        .Payment\ Pending { background: #fef3c7; color: #92400e; }
        
        .payment-info {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .payment-success {
            color: #10b981;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .payment-pending {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
        }
        
        .payment-pending .payment-success {
            color: #f59e0b;
        }
        
        .payment-failed {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .payment-failed .payment-success {
            color: #ef4444;
        }
        
        .transaction-id {
            font-family: 'Courier New', monospace;
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 10px;
        }
        
        .info-section h3 {
            color: #cda45e;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .info-item i {
            color: #cda45e;
            width: 16px;
        }
        
        .order-items {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .items-header {
            color: #cda45e;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-details {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .item-name {
            font-weight: 600;
            color: #ffffff;
        }
        
        .item-quantity {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }
        
        .item-price {
            font-weight: 600;
            color: #cda45e;
            text-align: right;
            min-width: 100px;
        }
        
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #cda45e;
        }
        
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #cda45e;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #cda45e;
            color: #0c0b09;
        }
        
        .btn-primary:hover {
            background-color: #f7a25e;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: #cda45e;
            border: 2px solid #cda45e;
        }
        
        .btn-secondary:hover {
            background-color: #cda45e;
            color: #0c0b09;
            transform: translateY(-2px);
        }
        
        .btn-retry {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-retry:hover {
            background-color: #d97706;
            transform: translateY(-2px);
        }
        
        .estimated-time {
            background: rgba(205, 164, 94, 0.1);
            border: 1px solid rgba(205, 164, 94, 0.3);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
        }
        
        .estimated-time i {
            color: #cda45e;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 100px auto 30px auto;
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .item-details {
                gap: 10px;
            }
            
            .item-image {
                width: 40px;
                height: 40px;
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
            <a href="my_orders.php"><i class="fas fa-clipboard-list"></i> My Orders</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <!-- Success Header -->
    <div class="success-header">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1 class="success-title">Order Confirmed!</h1>
        <p class="success-subtitle">
            Thank you, <span class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></span>! 
            Your order has been placed successfully.
        </p>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <div class="order-header">
            <div class="order-number">Order #<?php echo $order['id']; ?></div>
            <div class="order-status <?php echo str_replace(' ', '\ ', $order['status']); ?>"><?php echo $order['status']; ?></div>
        </div>

        <?php if ($order['payment_method'] === 'razorpay'): ?>
            <div class="payment-info <?php 
                if (isset($order['payment_status']) && $order['payment_status'] === 'paid') echo '';
                elseif (isset($order['payment_status']) && $order['payment_status'] === 'failed') echo 'payment-failed';
                else echo 'payment-pending';
            ?>">
                <div class="payment-success">
                    <i class="fas fa-credit-card"></i>
                    <?php if (isset($order['payment_status']) && $order['payment_status'] === 'paid'): ?>
                        Paid Online (Razorpay) - Payment Successful
                    <?php elseif (isset($order['payment_status']) && $order['payment_status'] === 'failed'): ?>
                        Paid Online (Razorpay) - Payment Failed
                    <?php else: ?>
                        Paid Online (Razorpay) - Payment Pending
                    <?php endif; ?>
                </div>
                
                <?php if (isset($order['razorpay_payment_id']) && !empty($order['razorpay_payment_id'])): ?>
                    <div class="transaction-id">
                        <strong>Transaction ID:</strong> <?php echo htmlspecialchars($order['razorpay_payment_id']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="payment-info">
                <div class="payment-success">
                    <i class="fas fa-money-bill-wave"></i>
                    Cash on Delivery
                </div>
                <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.8;">
                    Please keep exact change ready for delivery
                </p>
            </div>
        <?php endif; ?>

        <!-- Customer and Order Info -->
        <div class="info-grid">
            <div class="info-section">
                <h3><i class="fas fa-user"></i> Customer Details</h3>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                <?php if (isset($order['customer_phone'])): ?>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Order Info</h3>
                <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-credit-card"></i>
                    <span><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-items">
            <div class="items-header">
                <i class="fas fa-receipt"></i>
                Order Items
            </div>
            
            <?php foreach ($order_items as $item): ?>
                <div class="item-row">
                    <div class="item-details">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="item-image">
                        <?php else: ?>
                            <div class="item-image" style="background-color: #cda45e; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-utensils" style="color: #0c0b09;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-quantity">₹<?php echo $item['price']; ?> × <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <div class="item-price">₹<?php echo $item['price'] * $item['quantity']; ?></div>
                </div>
            <?php endforeach; ?>

            <div class="total-section">
                <div class="total-amount">Total: ₹<?php echo $order['total_price']; ?></div>
            </div>
        </div>

        <!-- Estimated Delivery Time -->
        <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled' && $order['status'] !== 'Payment Failed'): ?>
            <div class="estimated-time">
                <i class="fas fa-truck"></i>
                <strong>Estimated Delivery Time: 30-45 minutes</strong>
                <br>
                <small>We'll notify you once your order is out for delivery</small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="menu.php" class="btn btn-primary">
            <i class="fas fa-utensils"></i>
            Order More
        </a>
        <a href="my_orders.php" class="btn btn-secondary">
            <i class="fas fa-clipboard-list"></i>
            View All Orders
        </a>
        
        <!-- Retry Payment Button for Failed Payments -->
        <?php if (isset($order['payment_status']) && $order['payment_status'] === 'failed' && $order['payment_method'] === 'razorpay'): ?>
            <a href="retry_payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-retry">
                <i class="fas fa-redo"></i>
                Retry Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh page every 30 seconds to check for status updates
<?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing' || $order['status'] === 'Payment Pending'): ?>
    setTimeout(() => {
        location.reload();
    }, 30000);
<?php endif; ?>

// Show notification if payment failed
<?php if (isset($order['payment_status']) && $order['payment_method'] === 'razorpay' && $order['payment_status'] === 'failed'): ?>
    setTimeout(() => {
        if (confirm('Payment failed for this order. Would you like to retry payment?')) {
            window.location.href = 'retry_payment.php?order_id=<?php echo $order['id']; ?>';
        }
    }, 2000);
<?php endif; ?>
</script>

</body>
</html>