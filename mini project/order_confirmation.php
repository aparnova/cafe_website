<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    header("Location: menu.php");
    exit();
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE fullname = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_id = $userData['id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.fullname 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: menu.php");
    exit();
}

// Fetch order items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmation - Westley's Resto Cafe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Font & Color Variables */
    :root {
      --default-font: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      --heading-font: "Playfair Display", sans-serif;
      --nav-font: "Poppins", sans-serif;
      --background-color: #0c0b09;
      --default-color: rgba(255, 255, 255, 0.7);
      --heading-color: #ffffff;
      --accent-color: #cda45e;
      --surface-color: #29261f;
      --contrast-color: #0c0b09;
      --success-color: #4CAF50;
    }

    /* General Styles */
    body {
      color: var(--default-color);
      background-color: var(--background-color);
      font-family: var(--default-font);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    .container {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding: 0 15px;
    }

    /* Header Styles */
    .header {
      background-color: var(--background-color);
      color: var(--default-color);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 997;
      border-bottom: 1px solid var(--surface-color);
    }

    .header .branding {
      min-height: 60px;
      padding: 10px 0;
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      display: flex;
      align-items: center;
    }

    .header .logo img {
      height: 50px;
      margin-right: 15px;
    }

    .header .logo h1 {
      font-size: 24px;
      margin: 0;
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .back-to-menu {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 10px 20px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s;
      font-family: var(--nav-font);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .back-to-menu:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    /* Main Content */
    .main-content {
      padding-top: 100px;
      padding-bottom: 60px;
      min-height: 100vh;
    }

    /* Success Animation */
    .success-animation {
      text-align: center;
      margin-bottom: 40px;
    }

    .success-icon {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: var(--success-color);
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: successPulse 2s ease-in-out;
    }

    .success-icon i {
      font-size: 50px;
      color: white;
    }

    @keyframes successPulse {
      0% {
        transform: scale(0);
        opacity: 0;
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    .success-title {
      color: var(--success-color);
      font-family: var(--heading-font);
      font-size: 32px;
      margin: 0 0 10px;
    }

    .success-subtitle {
      color: var(--default-color);
      font-size: 18px;
      margin: 0;
    }

    /* Order Details Card */
    .order-card {
      background: var(--surface-color);
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      border: 2px solid var(--success-color);
    }

    .order-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .order-id {
      color: var(--accent-color);
      font-size: 24px;
      font-weight: bold;
      margin: 0 0 10px;
    }

    .order-status {
      display: inline-block;
      background: var(--success-color);
      color: white;
      padding: 8px 20px;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
    }

    /* Order Info */
    .order-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 30px;
    }

    .info-section h3 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 20px;
      margin-bottom: 15px;
      border-bottom: 2px solid var(--accent-color);
      padding-bottom: 5px;
    }

    .info-item {
      margin-bottom: 10px;
    }

    .info-label {
      color: var(--heading-color);
      font-weight: 600;
      display: inline-block;
      min-width: 80px;
    }

    .info-value {
      color: var(--default-color);
    }

    /* Order Items */
    .order-items {
      margin-bottom: 20px;
    }

    .order-items h3 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 20px;
      margin-bottom: 20px;
      text-align: center;
    }

    .item-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .item-row:last-child {
      border-bottom: none;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      color: var(--heading-color);
      font-weight: 600;
      font-size: 16px;
    }

    .item-quantity {
      color: var(--default-color);
      font-size: 14px;
      margin-top: 5px;
    }

    .item-price {
      color: var(--accent-color);
      font-weight: bold;
      font-size: 16px;
    }

    .order-total {
      text-align: right;
      padding-top: 20px;
      border-top: 2px solid var(--accent-color);
    }

    .total-amount {
      color: var(--accent-color);
      font-size: 24px;
      font-weight: bold;
    }

    /* Timeline */
    .order-timeline {
      background: var(--surface-color);
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
    }

    .timeline-title {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 24px;
      text-align: center;
      margin-bottom: 30px;
    }

    .timeline-steps {
      display: flex;
      justify-content: space-between;
      position: relative;
    }

    .timeline-steps::before {
      content: '';
      position: absolute;
      top: 20px;
      left: 50px;
      right: 50px;
      height: 2px;
      background: color-mix(in srgb, var(--default-color), transparent 70%);
      z-index: 1;
    }

    .timeline-step {
      text-align: center;
      flex: 1;
      position: relative;
      z-index: 2;
    }

    .timeline-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: white;
    }

    .timeline-step.active .timeline-icon {
      background: var(--success-color);
      animation: pulse 2s infinite;
    }

    .timeline-step.pending .timeline-icon {
      background: color-mix(in srgb, var(--default-color), transparent 70%);
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
      70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
      100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
    }

    .timeline-label {
      color: var(--default-color);
      font-size: 12px;
      font-weight: 600;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      margin-top: 30px;
    }

    .btn {
      padding: 12px 30px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s;
      font-family: var(--nav-font);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    .btn-primary:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: transparent;
      color: var(--accent-color);
      border: 2px solid var(--accent-color);
    }

    .btn-secondary:hover {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .order-info {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .timeline-steps {
        flex-direction: column;
        gap: 20px;
      }

      .timeline-steps::before {
        display: none;
      }

      .action-buttons {
        flex-direction: column;
        align-items: center;
      }

      .success-title {
        font-size: 24px;
      }

      .header .logo h1 {
        font-size: 18px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="branding">
      <div class="container">
        <div class="logo">
          <img src="img.png" alt="Westley's Resto Cafe">
          <h1>Westley's Resto Cafe</h1>
        </div>
        <a href="menu.php" class="back-to-menu">
          <i class="fas fa-utensils"></i>
          Order More
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">
      <!-- Success Animation -->
      <div class="success-animation">
        <div class="success-icon">
          <i class="fas fa-check"></i>
        </div>
        <h1 class="success-title">Order Confirmed!</h1>
        <p class="success-subtitle">Thank you for choosing Westley's Resto Cafe</p>
      </div>

      <!-- Order Details -->
      <div class="order-card">
        <div class="order-header">
          <h2 class="order-id">Order #<?php echo $order['id']; ?></h2>
          <span class="order-status"><?php echo ucfirst($order['status']); ?></span>
        </div>

        <div class="order-info">
          <div class="info-section">
            <h3><i class="fas fa-user"></i> Customer Details</h3>
            <div class="info-item">
              <span class="info-label">Name:</span>
              <span class="info-value"><?php echo htmlspecialchars($order['fullname']); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Phone:</span>
              <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Address:</span>
              <span class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
            </div>
          </div>

          <div class="info-section">
            <h3><i class="fas fa-clock"></i> Order Info</h3>
            <div class="info-item">
              <span class="info-label">Date:</span>
              <span class="info-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Time:</span>
              <span class="info-value"><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Total:</span>
              <span class="info-value">₹<?php echo $order['total_amount']; ?></span>
            </div>
          </div>
        </div>

        <div class="order-items">
          <h3><i class="fas fa-receipt"></i> Your Order</h3>
          <?php foreach ($order_items as $item): ?>
          <div class="item-row">
            <div class="item-details">
              <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
              <div class="item-quantity">₹<?php echo $item['item_price']; ?> × <?php echo $item['quantity']; ?></div>
            </div>
            <div class="item-price">₹<?php echo $item['subtotal']; ?></div>
          </div>
          <?php endforeach; ?>

          <div class="order-total">
            <strong>Total Amount: <span class="total-amount">₹<?php echo $order['total_amount']; ?></span></strong>
          </div>
        </div>

        <?php if ($order['notes']): ?>
        <div class="info-section">
          <h3><i class="fas fa-sticky-note"></i> Special Instructions</h3>
          <p class="info-value"><?php echo htmlspecialchars($order['notes']); ?></p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Order Timeline -->
      <div class="order-timeline">
        <h3 class="timeline-title">Order Progress</h3>
        <div class="timeline-steps">
          <div class="timeline-step active">
            <div class="timeline-icon">
              <i class="fas fa-check"></i>
            </div>
            <div class="timeline-label">Order Placed</div>
          </div>
          <div class="timeline-step pending">
            <div class="timeline-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="timeline-label">Confirmed</div>
          </div>
          <div class="timeline-step pending">
            <div class="timeline-icon">
              <i class="fas fa-utensils"></i>
            </div>
            <div class="timeline-label">Preparing</div>
          </div>
          <div class="timeline-step pending">
            <div class="timeline-icon">
              <i class="fas fa-truck"></i>
            </div>
            <div class="timeline-label">On the Way</div>
          </div>
          <div class="timeline-step pending">
            <div class="timeline-icon">
              <i class="fas fa-home"></i>
            </div>
            <div class="timeline-label">Delivered</div>
          </div>
        </div>
      </div>

      
      <!-- Action Buttons -->
      <div class="action-buttons">
        <a href="menu.php" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Order More
        </a>
        <a href="my_orders.php" class="btn btn-secondary">
          <i class="fas fa-list"></i>
          View All Orders
        </a>
      </div>
    </div>
  </div>

  <script>
    // Auto-refresh page every 30 seconds to check for order status updates
    setTimeout(() => {
      location.reload();
    }, 30000);
  </script>
</body>
</html>