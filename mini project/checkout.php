<?php
session_start();
require 'db.php';

// Replace these with your actual Razorpay keys from the dashboard
$razorpay_key_id = "rzp_test_RGBscspQt4e8A5"; // Replace with your actual key ID
$razorpay_key_secret = "5bqiEvg3UOUn1dLkDrQ5mhDN"; // Replace with your actual key secret

// Enhanced error logging for debugging
error_log('Checkout page accessed at: ' . date('Y-m-d H:i:s'));
error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    error_log('User not logged in or wrong role. Redirecting to login.');
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
error_log('User ID: ' . $user_id);

// Function to create Razorpay order via direct API call
function createRazorpayOrder($amount, $currency, $receipt, $key_id, $key_secret) {
    $url = 'https://api.razorpay.com/v1/orders';
    
    $data = array(
        'amount' => $amount,
        'currency' => $currency,
        'receipt' => $receipt,
    );
    
    $options = array(
        'http' => array(
            'header' => array(
                'Content-type: application/json',
                'Authorization: Basic ' . base64_encode($key_id . ':' . $key_secret)
            ),
            'method' => 'POST',
            'content' => json_encode($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        throw new Exception('Failed to create Razorpay order');
    }
    
    return json_decode($result, true);
}

// Function to clear cart after successful order
function clearCartAfterOrder($user_id, $order_type, $conn) {
    if ($order_type === 'direct') {
        unset($_SESSION['direct_order']);
    } else {
        // Clear session cart
        unset($_SESSION['cart']);
        
        // Clear database cart as well
        $clear_cart = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
        $clear_cart->bind_param("i", $user_id);
        $clear_cart->execute();
    }
}

// Determine order type and get items
$order_type = $_GET['order_type'] ?? 'cart';
$cart_items = [];
$total_price = 0;

if ($order_type === 'direct') {
    $menu_id = intval($_GET['menu_id'] ?? 0);
    $quantity = intval($_GET['quantity'] ?? 1);
    
    if ($menu_id > 0) {
        $menu_query = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
        $menu_query->bind_param("i", $menu_id);
        $menu_query->execute();
        $menu_item = $menu_query->get_result()->fetch_assoc();
        
        if ($menu_item) {
            $cart_items[] = [
                'id' => $menu_item['id'],
                'name' => $menu_item['name'],
                'price' => $menu_item['price'],
                'quantity' => $quantity,
                'total' => $menu_item['price'] * $quantity
            ];
            $total_price = $menu_item['price'] * $quantity;
            
            $_SESSION['direct_order'] = [
                $menu_id => [
                    'name' => $menu_item['name'],
                    'price' => $menu_item['price'],
                    'quantity' => $quantity
                ]
            ];
        } else {
            $_SESSION['error'] = 'Item not found';
            header("Location: menu.php");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid item selected';
        header("Location: menu.php");
        exit();
    }
} else {
    // Cart order - load from both database and session
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $menu_ids = implode(',', array_keys($_SESSION['cart']));
        $menu_query = $conn->query("SELECT * FROM menu_items WHERE id IN ($menu_ids)");
        
        while ($menu_item = $menu_query->fetch_assoc()) {
            $cart_item = $_SESSION['cart'][$menu_item['id']];
            $cart_items[] = [
                'id' => $menu_item['id'],
                'name' => $menu_item['name'],
                'price' => $menu_item['price'],
                'quantity' => $cart_item['quantity'],
                'total' => $menu_item['price'] * $cart_item['quantity']
            ];
            $total_price += $menu_item['price'] * $cart_item['quantity'];
        }
    } else {
        // Try to load from database if session cart is empty
        $cart_query = $conn->prepare("
            SELECT 
                uc.menu_item_id as id,
                mi.name,
                mi.price,
                uc.quantity
            FROM user_cart uc
            JOIN menu_items mi ON uc.menu_item_id = mi.id
            WHERE uc.user_id = ?
        ");
        $cart_query->bind_param("i", $user_id);
        $cart_query->execute();
        $result = $cart_query->get_result();
        
        $_SESSION['cart'] = [];
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'total' => $row['price'] * $row['quantity']
            ];
            $total_price += $row['price'] * $row['quantity'];
            
            // Sync with session
            $_SESSION['cart'][$row['id']] = [
                'name' => $row['name'],
                'price' => $row['price'],
                'quantity' => $row['quantity']
            ];
        }
        
        if (empty($cart_items)) {
            $_SESSION['error'] = 'Your cart is empty';
            header("Location: menu.php");
            exit();
        }
    }
}

error_log('Cart items count: ' . count($cart_items));
error_log('Total price: ' . $total_price);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    error_log('POST action received: ' . $action);
    error_log('POST data: ' . print_r($_POST, true));
    
    if ($action === 'create_razorpay_order') {
        header('Content-Type: application/json');
        
        try {
            $name = trim($_POST['fullname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $payment_method = $_POST['payment_method'] ?? '';
            $order_type = $_POST['order_type'] ?? 'cart';
            
            error_log("Processing Razorpay order - Name: $name, Phone: $phone, Payment: $payment_method");
            
            if (empty($name) || empty($phone) || empty($address)) {
                throw new Exception('Please fill all required fields');
            }
            
            $items_to_process = [];
            if ($order_type === 'direct' && isset($_SESSION['direct_order'])) {
                $items_to_process = $_SESSION['direct_order'];
            } elseif ($order_type === 'cart' && isset($_SESSION['cart'])) {
                $items_to_process = $_SESSION['cart'];
            } else {
                throw new Exception('No items found to order');
            }
            
            $total_price = 0;
            foreach ($items_to_process as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }
            
            if ($total_price <= 0) {
                throw new Exception('Invalid order total');
            }
            
            // Create Razorpay order using direct API call
            $receipt = 'order_' . time() . '_' . $user_id;
            $razorpay_order = createRazorpayOrder(
                $total_price * 100, // Amount in paise
                'INR',
                $receipt,
                $razorpay_key_id,
                $razorpay_key_secret
            );
            
            if (!isset($razorpay_order['id'])) {
                throw new Exception('Failed to create Razorpay order: ' . ($razorpay_order['error']['description'] ?? 'Unknown error'));
            }
            
            $razorpay_order_id = $razorpay_order['id'];
            
            // Insert order into database
            $insert_order = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, delivery_address, total_price, payment_method, payment_status, status, razorpay_order_id, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'Payment Pending', ?, NOW())");
            $insert_order->bind_param("isssiss", $user_id, $name, $phone, $address, $total_price, $payment_method, $razorpay_order_id);
            
            if (!$insert_order->execute()) {
                throw new Exception('Failed to create order: ' . $insert_order->error);
            }
            
            $order_id = $conn->insert_id;
            error_log('Order created with ID: ' . $order_id);
            
            // Insert order items
            $insert_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            foreach ($items_to_process as $menu_id => $item) {
                $insert_item->bind_param("iiid", $order_id, $menu_id, $item['quantity'], $item['price']);
                if (!$insert_item->execute()) {
                    throw new Exception('Failed to save order items: ' . $insert_item->error);
                }
            }
            
            echo json_encode([
                'success' => true,
                'order_data' => [
                    'razorpay_order_id' => $razorpay_order_id,
                    'amount' => $total_price,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'order_id' => $order_id,
                    'order_type' => $order_type
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Razorpay order creation error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    if ($action === 'place_order') {
        header('Content-Type: application/json');
        
        try {
            $name = trim($_POST['fullname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $payment_method = $_POST['payment_method'] ?? '';
            $order_type = $_POST['order_type'] ?? 'cart';
            
            error_log("Processing COD order - Name: $name, Phone: $phone, Payment: $payment_method");
            
            if (empty($name) || empty($phone) || empty($address)) {
                throw new Exception('Please fill all required fields');
            }
            
            $items_to_process = [];
            if ($order_type === 'direct' && isset($_SESSION['direct_order'])) {
                $items_to_process = $_SESSION['direct_order'];
            } elseif ($order_type === 'cart' && isset($_SESSION['cart'])) {
                $items_to_process = $_SESSION['cart'];
            } else {
                throw new Exception('No items found to order');
            }
            
            $total_price = 0;
            foreach ($items_to_process as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }
            
            // Insert order for COD
            $insert_order = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, delivery_address, total_price, payment_method, payment_status, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'Pending', NOW())");
            $insert_order->bind_param("isssds", $user_id, $name, $phone, $address, $total_price, $payment_method);
            
            if (!$insert_order->execute()) {
                throw new Exception('Failed to create order: ' . $insert_order->error);
            }
            
            $order_id = $conn->insert_id;
            error_log('COD Order created with ID: ' . $order_id);
            
            // Insert order items
            $insert_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            foreach ($items_to_process as $menu_id => $item) {
                $insert_item->bind_param("iiid", $order_id, $menu_id, $item['quantity'], $item['price']);
                if (!$insert_item->execute()) {
                    throw new Exception('Failed to save order items: ' . $insert_item->error);
                }
            }
            
            // Clear cart after successful order
            clearCartAfterOrder($user_id, $order_type, $conn);
            
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'message' => 'Order placed successfully!'
            ]);
            
        } catch (Exception $e) {
            error_log('COD order creation error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Handle payment success callback (for clearing cart after successful Razorpay payment)
    if ($action === 'payment_success') {
        header('Content-Type: application/json');
        
        try {
            $order_id = intval($_POST['order_id'] ?? 0);
            $order_type = $_POST['order_type'] ?? 'cart';
            
            if ($order_id > 0) {
                // Clear cart after successful payment
                clearCartAfterOrder($user_id, $order_type, $conn);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Payment success callback error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
}

// Get user details
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Westley's Resto Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
            max-width: 800px;
            margin: 120px auto 50px auto;
            padding: 20px;
        }
        
        .checkout-form {
            background-color: #29261f;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #4CAF50;
        }
        
        h1, h2 {
            color: #cda45e;
            font-family: "Playfair Display", serif;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .order-summary {
            background-color: #1a1816;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #3a3530;
        }
        
        .summary-title {
            color: #cda45e;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            flex: 1;
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
        }
        
        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #cda45e;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 20px;
            font-weight: bold;
            color: #cda45e;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #cda45e;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            background-color: #1a1816;
            border: 1px solid #3a3530;
            border-radius: 8px;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #cda45e;
        }
        
        .payment-methods {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .payment-option {
            flex: 1;
            position: relative;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option label {
            display: block;
            padding: 15px;
            background-color: #1a1816;
            border: 2px solid #3a3530;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option input[type="radio"]:checked + label {
            border-color: #cda45e;
            background-color: rgba(205, 164, 94, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background-color: #cda45e;
            color: #0c0b09;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn:hover {
            background-color: #f7a25e;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background-color: #6b7280;
            cursor: not-allowed;
            transform: none;
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
            max-width: 350px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .notification.success { background: #10b981; }
        .notification.error { background: #ef4444; }
        .notification.info { background: #3b82f6; }
        
        /* Selenium-friendly identifiers */
        .place-order-btn {
            position: relative;
        }
        
        .form-processing {
            opacity: 0.7;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 100px auto 30px auto;
                padding: 15px;
            }
            
            .payment-methods {
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
            <a href="my_orders.php"><i class="fas fa-clipboard-list"></i> My Orders</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="checkout-form" id="checkout-form-container">
        <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        
        <div class="order-summary">
            <div class="summary-title">
                <i class="fas fa-receipt"></i> Order Summary
            </div>
            
            <?php foreach ($cart_items as $item): ?>
                <div class="order-item">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    <div class="item-price">₹<?php echo $item['total']; ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Total Amount:</span>
                    <span id="total-amount">₹<?php echo $total_price; ?></span>
                </div>
            </div>
        </div>
        
        <form id="checkout-form" data-selenium="checkout-form">
            <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($order_type); ?>" id="order-type-input">
            
            <div class="form-group">
                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required data-selenium="fullname-input">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required data-selenium="phone-input">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" data-selenium="email-input">
            </div>
            
            <div class="form-group">
                <label for="address">Delivery Address *</label>
                <textarea id="address" name="address" rows="3" required placeholder="Enter your complete delivery address" data-selenium="address-input"></textarea>
            </div>
            
            <div class="form-group">
                <label>Payment Method</label>
                <div class="payment-methods" id="payment-methods-container">
                    <div class="payment-option">
                        <input type="radio" id="razorpay" name="payment_method" value="razorpay" checked data-selenium="payment-razorpay">
                        <label for="razorpay" data-selenium="payment-razorpay-label">
                            <i class="fas fa-credit-card"></i><br>
                            Online Payment<br>
                            <small>(Razorpay)</small>
                        </label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="cod" name="payment_method" value="cash_on_delivery" data-selenium="payment-cod">
                        <label for="cod" data-selenium="payment-cod-label">
                            <i class="fas fa-money-bill-wave"></i><br>
                            Cash on Delivery<br>
                            <small>(COD)</small>
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn place-order-btn" id="place-order-btn" onclick="processPayment()" data-selenium="place-order-button">
                <i class="fas fa-shopping-cart"></i> <span id="btn-text">Place Order - ₹<?php echo $total_price; ?></span>
            </button>
        </form>
    </div>
</div>

<div class="notification" id="payment-notification"></div>

<!-- Debug info for development (remove in production) -->
<?php if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
<div style="position: fixed; bottom: 10px; left: 10px; background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 12px; z-index: 9999;">
    <div>User ID: <?php echo $user_id; ?></div>
    <div>Order Type: <?php echo $order_type; ?></div>
    <div>Cart Items: <?php echo count($cart_items); ?></div>
    <div>Total: ₹<?php echo $total_price; ?></div>
    <div>Session Cart: <?php echo isset($_SESSION['cart']) ? 'Yes' : 'No'; ?></div>
</div>
<?php endif; ?>

<script>
// Enhanced logging for debugging
function debugLog(message, data = null) {
    console.log(`[CHECKOUT DEBUG] ${message}`, data || '');
}

// Enhanced notification system
function showNotification(message, type = 'success') {
    debugLog('Showing notification', {message, type});
    const notification = document.getElementById('payment-notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';
    notification.style.opacity = '1';
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }, 4000);
}

// Enhanced form validation
function validateForm() {
    debugLog('Starting form validation');
    
    const form = document.getElementById('checkout-form');
    const formData = new FormData(form);
    
    const name = formData.get('fullname')?.trim() || '';
    const phone = formData.get('phone')?.trim() || '';
    const address = formData.get('address')?.trim() || '';
    const paymentMethod = formData.get('payment_method') || '';
    
    debugLog('Form data extracted', {name, phone, address, paymentMethod});
    
    if (!name) {
        showNotification('Please enter your full name', 'error');
        document.getElementById('fullname').focus();
        return false;
    }
    
    if (!phone) {
        showNotification('Please enter your phone number', 'error');
        document.getElementById('phone').focus();
        return false;
    }
    
    if (!address) {
        showNotification('Please enter your delivery address', 'error');
        document.getElementById('address').focus();
        return false;
    }
    
    if (!paymentMethod) {
        showNotification('Please select a payment method', 'error');
        return false;
    }
    
    debugLog('Form validation passed');
    return true;
}

// Enhanced payment processing with better error handling
function processPayment() {
    debugLog('processPayment() function called');
    
    try {
        // Prevent double submission
        const button = document.getElementById('place-order-btn');
        if (button.disabled) {
            debugLog('Button already disabled, preventing double submission');
            return;
        }
        
        // Validate form first
        if (!validateForm()) {
            debugLog('Form validation failed');
            return;
        }
        
        // Disable button and show processing
        button.disabled = true;
        const originalText = document.getElementById('btn-text').innerHTML;
        document.getElementById('btn-text').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        document.getElementById('checkout-form-container').classList.add('form-processing');
        
        const form = document.getElementById('checkout-form');
        const formData = new FormData(form);
        const paymentMethod = formData.get('payment_method');
        
        debugLog('Payment method selected', paymentMethod);
        
        // Add action based on payment method
        if (paymentMethod === 'razorpay') {
            formData.append('action', 'create_razorpay_order');
            debugLog('Creating Razorpay order');
            processRazorpayPayment(formData, button, originalText);
        } else {
            formData.append('action', 'place_order');
            debugLog('Processing COD order');
            processCODPayment(formData, button, originalText);
        }
        
    } catch (error) {
        debugLog('Error in processPayment', error);
        showNotification('An unexpected error occurred: ' + error.message, 'error');
        resetButton();
    }
}

// Razorpay payment processing
function processRazorpayPayment(formData, button, originalText) {
    debugLog('Processing Razorpay payment');
    
    const currentUrl = new URL(window.location.href);
    let fetchUrl = 'checkout.php';
    
    // Preserve URL parameters for direct orders
    if (currentUrl.searchParams.get('order_type') === 'direct') {
        fetchUrl += '?' + currentUrl.searchParams.toString();
    }
    
    fetch(fetchUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        debugLog('Razorpay API response status', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        debugLog('Razorpay API response data', data);
        if (data.success) {
            initiateRazorpayPayment(data.order_data);
        } else {
            throw new Error(data.message || 'Failed to create Razorpay order');
        }
    })
    .catch(error => {
        debugLog('Razorpay payment error', error);
        showNotification('Error: ' + error.message, 'error');
        resetButton(button, originalText);
    });
}

// COD payment processing
function processCODPayment(formData, button, originalText) {
    debugLog('Processing COD payment');
    
    const currentUrl = new URL(window.location.href);
    let fetchUrl = 'checkout.php';
    
    // Preserve URL parameters for direct orders
    if (currentUrl.searchParams.get('order_type') === 'direct') {
        fetchUrl += '?' + currentUrl.searchParams.toString();
    }
    
    fetch(fetchUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        debugLog('COD API response status', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        debugLog('COD API response data', data);
        if (data.success) {
            showNotification(data.message, 'success');
            debugLog('COD order successful, redirecting to confirmation', data.order_id);
            setTimeout(() => {
                window.location.href = `order_confirmation.php?order_id=${data.order_id}`;
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to place order');
        }
    })
    .catch(error => {
        debugLog('COD payment error', error);
        showNotification('Error: ' + error.message, 'error');
        resetButton(button, originalText);
    });
}

// Reset button state
function resetButton(button = null, originalText = null) {
    const btn = button || document.getElementById('place-order-btn');
    const textElement = document.getElementById('btn-text');
    
    btn.disabled = false;
    if (originalText) {
        textElement.innerHTML = originalText;
    } else {
        textElement.innerHTML = `<i class="fas fa-shopping-cart"></i> Place Order - ${document.getElementById('total-amount').textContent}`;
    }
    document.getElementById('checkout-form-container').classList.remove('form-processing');
    
    debugLog('Button reset to original state');
}

// Enhanced Razorpay payment initiation
function initiateRazorpayPayment(orderData) {
    debugLog('Initiating Razorpay payment', orderData);
    
    const options = {
        "key": "<?php echo $razorpay_key_id; ?>",
        "amount": orderData.amount * 100,
        "currency": "INR",
        "name": "Westley's Resto Cafe",
        "description": "Food Order Payment",
        "order_id": orderData.razorpay_order_id,
        "handler": function (response) {
            debugLog('Razorpay payment successful', response);
            showNotification('Payment successful! Verifying...', 'info');
            
            fetch('payment_verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'razorpay_order_id': response.razorpay_order_id,
                    'razorpay_payment_id': response.razorpay_payment_id,
                    'razorpay_signature': response.razorpay_signature
                })
            })
            .then(response => {
                debugLog('Payment verification response status', response.status);
                return response.json();
            })
            .then(data => {
                debugLog('Payment verification response', data);
                if (data.success) {
                    // Clear cart after successful payment verification
                    clearCartAfterPayment(orderData.order_id, orderData.order_type);
                    showNotification('Payment verified successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = `order_confirmation.php?order_id=${data.order_id}`;
                    }, 1500);
                } else {
                    showNotification('Payment verification failed: ' + data.message, 'error');
                    resetButton();
                }
            })
            .catch(error => {
                debugLog('Payment verification error', error);
                showNotification('Payment verification failed. Please contact support.', 'error');
                resetButton();
            });
        },
        "prefill": {
            "name": orderData.customer_name,
            "email": orderData.customer_email,
            "contact": orderData.customer_phone
        },
        "theme": {
            "color": "#cda45e"
        },
        "modal": {
            "ondismiss": function() {
                debugLog('Razorpay payment dismissed');
                showNotification('Payment cancelled', 'error');
                resetButton();
            }
        }
    };

    const rzp = new Razorpay(options);
    
    rzp.on('payment.failed', function (response) {
        debugLog('Razorpay payment failed', response.error);
        
        fetch('update_payment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'razorpay_order_id': orderData.razorpay_order_id,
                'status': 'failed',
                'error_description': response.error.description
            })
        }).catch(err => debugLog('Failed to update payment status', err));
        
        showNotification('Payment failed: ' + response.error.description, 'error');
        resetButton();
    });

    debugLog('Opening Razorpay checkout');
    rzp.open();
}

// Function to clear cart after successful payment
function clearCartAfterPayment(orderId, orderType) {
    debugLog('Clearing cart after payment', {orderId, orderType});
    
    const formData = new FormData();
    formData.append('action', 'payment_success');
    formData.append('order_id', orderId);
    formData.append('order_type', orderType);
    
    fetch('checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            debugLog('Cart cleared successfully after payment');
        } else {
            debugLog('Failed to clear cart', data.message);
        }
    })
    .catch(error => {
        debugLog('Error clearing cart', error);
    });
}

// Enhanced form field event handlers for better Selenium compatibility
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM loaded, setting up event handlers');
    
    // Add change event listeners to form fields for validation
    const formFields = ['fullname', 'phone', 'email', 'address'];
    formFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                // Clear any error styling when user starts typing
                this.style.borderColor = '';
            });
            
            field.addEventListener('change', function() {
                debugLog(`Field ${fieldId} changed`, this.value);
            });
        }
    });
    
    // Payment method change handlers
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', function() {
            debugLog('Payment method changed', this.value);
            
            // Update button text based on payment method
            const totalAmount = document.getElementById('total-amount').textContent;
            const btnText = document.getElementById('btn-text');
            
            if (this.value === 'razorpay') {
                btnText.innerHTML = `<i class="fas fa-credit-card"></i> Pay Online - ${totalAmount}`;
            } else {
                btnText.innerHTML = `<i class="fas fa-shopping-cart"></i> Place Order - ${totalAmount}`;
            }
        });
    });
    
    // Add keyboard support for the place order button
    document.getElementById('place-order-btn').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if (!this.disabled) {
                processPayment();
            }
        }
    });
    
    debugLog('Checkout page initialized successfully');
    
    // Add a data attribute to indicate page is ready (useful for Selenium)
    document.body.setAttribute('data-checkout-ready', 'true');
});

// Global error handler for uncaught errors
window.addEventListener('error', function(e) {
    debugLog('Uncaught error', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error
    });
});

// Selenium helper functions (can be called directly from Selenium)
window.seleniumHelpers = {
    // Check if page is ready
    isPageReady: function() {
        return document.body.hasAttribute('data-checkout-ready');
    },
    
    // Fill form programmatically
    fillForm: function(data) {
        debugLog('Filling form programmatically', data);
        
        if (data.fullname) document.getElementById('fullname').value = data.fullname;
        if (data.phone) document.getElementById('phone').value = data.phone;
        if (data.email) document.getElementById('email').value = data.email;
        if (data.address) document.getElementById('address').value = data.address;
        if (data.payment_method) {
            document.getElementById(data.payment_method).checked = true;
            document.getElementById(data.payment_method).dispatchEvent(new Event('change'));
        }
        
        debugLog('Form filled programmatically');
    },
    
    // Validate form programmatically
    validateForm: function() {
        return validateForm();
    },
    
    // Submit order programmatically
    submitOrder: function() {
        debugLog('Submitting order programmatically');
        processPayment();
    },
    
    // Get current form data
    getFormData: function() {
        const form = document.getElementById('checkout-form');
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        debugLog('Current form data', data);
        return data;
    },
    
    // Check if processing
    isProcessing: function() {
        return document.getElementById('place-order-btn').disabled;
    }
};

debugLog('Checkout JavaScript loaded successfully');
</script>

</body>
</html>