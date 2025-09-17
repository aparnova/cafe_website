<?php
// retry_payment.php - For retrying failed Razorpay payments
session_start();
require 'db.php';

use Razorpay\Api\Api;

// Razorpay configuration
$razorpay_key_id = "rzp_test_RGBscspQt4e8A5z"; // Replace with your actual key
$razorpay_key_secret = "5bqiEvg3UOUn1dLkDrQ5mhDN"; // Replace with your actual secret

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

// Verify this order belongs to the current user and payment failed
$order_query = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'failed'");
$order_query->bind_param("ii", $order_id, $user_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['error'] = "Order not found or payment retry not allowed.";
    header("Location: my_orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Handle payment retry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Create new Razorpay order
        $api = new Api($razorpay_key_id, $razorpay_key_secret);
        
        $razorpay_order = $api->order->create([
            'receipt' => 'retry_order_' . $order['id'] . '_' . time(),
            'amount' => $order['total_price'] * 100, // Amount in paise
            'currency' => 'INR'
        ]);
        
        $new_razorpay_order_id = $razorpay_order['id'];
        
        // Update order with new Razorpay order ID
        $update_stmt = $conn->prepare("UPDATE orders SET razorpay_order_id = ?, payment_status = 'pending', status = 'Payment Pending' WHERE id = ?");
        $update_stmt->bind_param("si", $new_razorpay_order_id, $order['id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update order for retry');
        }
        
        echo json_encode([
            'success' => true,
            'order_data' => [
                'razorpay_order_id' => $new_razorpay_order_id,
                'amount' => $order['total_price'],
                'customer_name' => $order['customer_name'],
                'customer_phone' => $order['customer_phone'],
                'order_id' => $order['id']
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retry Payment - Westley's Resto Cafe</title>
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
            max-width: 600px;
            margin: 120px auto 50px auto;
            padding: 20px;
        }
        
        .retry-card {
            background-color: #29261f;
            padding: 40px;
            border-radius: 15px;
            border: 2px solid #f59e0b;
            text-align: center;
        }
        
        .retry-icon {
            width: 80px;
            height: 80px;
            background-color: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }
        
        .retry-icon i {
            font-size: 36px;
            color: white;
        }
        
        h1 {
            color: #f59e0b;
            font-family: "Playfair Display", serif;
            margin-bottom: 20px;
        }
        
        .order-info {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            color: #cda45e;
            font-weight: 600;
        }
        
        .value {
            color: rgba(255,255,255,0.9);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background-color: #f59e0b;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn:hover {
            background-color: #d97706;
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
        }
        
        .notification.success { background: #10b981; }
        .notification.error { background: #ef4444; }
        .notification.info { background: #3b82f6; }
        
        @media (max-width: 768px) {
            .container {
                margin: 100px auto 30px auto;
                padding: 15px;
            }
            
            .retry-card {
                padding: 30px 20px;
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
    <div class="retry-card">
        <div class="retry-icon">
            <i class="fas fa-redo"></i>
        </div>
        
        <h1>Retry Payment</h1>
        <p>Your payment for this order failed. You can retry the payment below.</p>
        
        <div class="order-info">
            <div class="info-row">
                <span class="label">Order ID:</span>
                <span class="value">#<?php echo $order['id']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Total Amount:</span>
                <span class="value">₹<?php echo $order['total_price']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Customer:</span>
                <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value" style="color: #ef4444;">Payment Failed</span>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <button class="btn" onclick="retryPayment()">
                <i class="fas fa-credit-card"></i>
                Retry Payment - ₹<?php echo $order['total_price']; ?>
            </button>
            <br>
            <a href="my_orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Orders
            </a>
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

function retryPayment() {
    fetch('retry_payment.php?order_id=<?php echo $order['id']; ?>', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            initiateRazorpayPayment(data.order_data);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function initiateRazorpayPayment(orderData) {
    const options = {
        "key": "<?php echo $razorpay_key_id; ?>",
        "amount": orderData.amount * 100,
        "currency": "INR",
        "name": "Westley's Resto Cafe",
        "description": "Retry Food Order Payment",
        "order_id": orderData.razorpay_order_id,
        "handler": function (response) {
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Payment verified successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = `order_confirmation.php?order_id=${data.order_id}`;
                    }, 1500);
                } else {
                    showNotification('Payment verification failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Verification Error:', error);
                showNotification('Payment verification failed. Please contact support.', 'error');
            });
        },
        "prefill": {
            "name": orderData.customer_name,
            "contact": orderData.customer_phone
        },
        "theme": {
            "color": "#f59e0b"
        },
        "modal": {
            "ondismiss": function() {
                showNotification('Payment cancelled', 'error');
            }
        }
    };

    const rzp = new Razorpay(options);
    
    rzp.on('payment.failed', function (response) {
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
        });
        
        showNotification('Payment failed: ' + response.error.description, 'error');
    });

    rzp.open();
}
</script>

</body>
</html>