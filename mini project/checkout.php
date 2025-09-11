<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 
$username = $_SESSION['user'];
$orderType = isset($_GET['order_type']) ? $_GET['order_type'] : 'cart';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $cart = json_decode($_POST['cart'], true);

    if (empty($cart)) {
        echo json_encode(['status' => 'error', 'message' => 'Your order is empty!']);
        exit();
    }

    // Calculate total price
    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    // Insert into orders table
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, delivery_address, payment_method, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("idss", $user_id, $total_price, $delivery_address, $payment_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order items
    $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmt_items->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt_items->execute();
    }

    echo json_encode(['status' => 'success', 'order_id' => $order_id]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout - Westley's Resto Cafe</title>
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
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
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .header .branding {
      min-height: 70px;
      padding: 15px 0;
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header .logo img {
      height: 50px;
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
      padding: 12px 24px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 500;
      transition: all 0.3s ease;
      font-family: var(--nav-font);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    .back-to-menu:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    /* Main Content */
    .main-content {
      padding-top: 120px;
      padding-bottom: 80px;
      min-height: 100vh;
    }

    /* Section Title */
    .section-title {
      text-align: center;
      margin-bottom: 50px;
      padding: 0 20px;
    }

    .section-title h1 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 42px;
      margin: 0 0 15px 0;
      font-weight: 600;
    }

    .section-title p {
      color: var(--default-color);
      margin: 0;
      font-size: 18px;
    }

    /* Checkout Container */
    .checkout-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 50px;
      margin-top: 50px;
      align-items: start;
    }

    .section {
      background: var(--surface-color);
      border-radius: 20px;
      padding: 40px;
      border: 1px solid color-mix(in srgb, var(--accent-color), transparent 70%);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s ease;
    }

    .section:hover {
      transform: translateY(-5px);
    }

    .section h2 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 28px;
      margin: 0 0 30px 0;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .section h2 i {
      font-size: 24px;
    }

    /* Order Type Indicator */
    .order-type-indicator {
      background: color-mix(in srgb, var(--accent-color), transparent 90%);
      border: 2px solid var(--accent-color);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 30px;
      text-align: center;
      font-weight: 600;
      font-size: 16px;
    }

    .order-type-indicator.single-order {
      background: color-mix(in srgb, var(--accent-color), transparent 90%);
      border-color: var(--accent-color);
      color: var(--accent-color);
    }

    .order-type-indicator.cart-order {
      background: color-mix(in srgb, var(--accent-color), transparent 90%);
      border-color: var(--accent-color);
      color: var(--accent-color);
    }

    /* Order Summary */
    .order-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .item-details {
      flex: 1;
      padding-right: 20px;
    }

    .item-name {
      color: var(--heading-color);
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 18px;
    }

    .item-quantity {
      color: var(--default-color);
      font-size: 15px;
    }

    .item-price {
      color: var(--accent-color);
      font-weight: bold;
      font-size: 20px;
      min-width: 80px;
      text-align: right;
    }

    .order-total {
      margin-top: 30px;
      padding-top: 25px;
      border-top: 2px solid var(--accent-color);
      text-align: right;
    }

    .total-amount {
      font-size: 28px;
      font-weight: bold;
      color: var(--accent-color);
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      margin-bottom: 10px;
      color: var(--heading-color);
      font-weight: 600;
      font-size: 16px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 15px 18px;
      border: 2px solid color-mix(in srgb, var(--default-color), transparent 60%);
      border-radius: 12px;
      background: var(--background-color);
      color: var(--default-color);
      font-family: var(--default-font);
      font-size: 16px;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--accent-color);
      background: color-mix(in srgb, var(--accent-color), transparent 95%);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 25px;
    }

    /* Payment Methods */
    .payment-methods {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .payment-method {
      position: relative;
    }

    .payment-method input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      margin: 0;
      cursor: pointer;
    }

    .payment-method label {
      display: block;
      padding: 20px 15px;
      border: 2px solid color-mix(in srgb, var(--default-color), transparent 70%);
      border-radius: 15px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background: var(--background-color);
      font-weight: 500;
    }

    .payment-method input[type="radio"]:checked + label {
      border-color: var(--accent-color);
      background: color-mix(in srgb, var(--accent-color), transparent 90%);
      color: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(205, 164, 94, 0.3);
    }

    .payment-method i {
      display: block;
      font-size: 26px;
      margin-bottom: 10px;
    }

    /* Place Order Button */
    .place-order-btn {
      width: 100%;
      padding: 18px 20px;
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      border-radius: 15px;
      font-size: 20px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: var(--nav-font);
      margin-top: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .place-order-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(205, 164, 94, 0.4);
    }

    .place-order-btn:disabled {
      background: color-mix(in srgb, var(--default-color), transparent 70%);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* Empty Order Message */
    .empty-order {
      text-align: center;
      padding: 60px 30px;
      color: var(--default-color);
    }

    .empty-order h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      font-size: 24px;
    }

    .empty-order p {
      margin-bottom: 30px;
      font-size: 16px;
      line-height: 1.6;
    }

    .empty-order a {
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 15px 30px;
      border-radius: 25px;
      text-decoration: none;
      display: inline-block;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .empty-order a:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    /* Notification */
    .notification {
      position: fixed;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 18px 30px;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
      z-index: 1001;
      display: none;
      font-size: 16px;
      font-weight: 500;
      min-width: 300px;
      text-align: center;
    }

    .notification.error {
      background: #ff6b6b;
    }

    .notification.success {
      background: #4CAF50;
    }

    /* Loading Overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1002;
      backdrop-filter: blur(5px);
    }

    .loading-overlay.show {
      display: flex;
    }

    .loading-content {
      background: var(--surface-color);
      padding: 50px 40px;
      border-radius: 20px;
      text-align: center;
      border: 2px solid var(--accent-color);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 4px solid color-mix(in srgb, var(--accent-color), transparent 70%);
      border-top: 4px solid var(--accent-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 25px;
    }

    .loading-content h3 {
      color: var(--accent-color);
      margin: 0 0 15px 0;
      font-size: 24px;
    }

    .loading-content p {
      color: var(--default-color);
      margin: 0;
      font-size: 16px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 992px) {
      .checkout-container {
        gap: 40px;
      }

      .section {
        padding: 35px;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 0 15px;
      }

      .main-content {
        padding-top: 100px;
        padding-bottom: 60px;
      }

      .section-title h1 {
        font-size: 32px;
      }

      .checkout-container {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      .section {
        padding: 25px;
      }

      .section h2 {
        font-size: 24px;
      }

      .form-row {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .payment-methods {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .header .logo h1 {
        font-size: 20px;
      }

      .back-to-menu {
        padding: 10px 18px;
        font-size: 14px;
      }
    }

    @media (max-width: 480px) {
      .section {
        padding: 20px;
      }

      .section h2 {
        font-size: 22px;
        flex-direction: column;
        gap: 8px;
      }

      .item-details {
        padding-right: 15px;
      }

      .item-name {
        font-size: 16px;
      }

      .item-price {
        font-size: 18px;
      }

      .total-amount {
        font-size: 24px;
      }

      .place-order-btn {
        padding: 16px;
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
          <i class="fas fa-arrow-left"></i>
          Back to Menu
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">
      <div class="section-title">
        <h1>Checkout</h1>
        <p>Complete your order</p>
      </div>

      <div class="checkout-container">
        <!-- Order Summary -->
        <div class="section">
          <div id="order-type-indicator" class="order-type-indicator"></div>
          <h2><i class="fas fa-receipt"></i> Order Summary</h2>
          <div id="order-summary"></div>
          <div class="order-total">
            <div style="font-size: 20px; margin-bottom: 15px;">
              Total: <span class="total-amount">₹<span id="total-amount">0</span></span>
            </div>
          </div>
        </div>

        <!-- Customer Details & Payment -->
        <div class="section">
          <h2><i class="fas fa-user"></i> Your Details</h2>
          <form id="checkout-form">
            <div class="form-row">
              <div class="form-group">
                <label for="customer-name">Full Name *</label>
                <input type="text" id="customer-name" name="customer_name" value="<?php echo $_SESSION['user']; ?>" required>
              </div>
              <div class="form-group">
                <label for="customer-phone">Phone Number *</label>
                <input type="tel" id="customer-phone" name="customer_phone" required>
              </div>
            </div>

            <div class="form-group">
              <label for="delivery-address">Delivery Address *</label>
              <textarea id="delivery-address" name="delivery_address" placeholder="Enter your complete address" required></textarea>
            </div>

            <div class="form-group">
              <label for="order-notes">Special Instructions (Optional)</label>
              <textarea id="order-notes" name="order_notes" placeholder="Any special requests or notes for the chef"></textarea>
            </div>

            <div class="form-group">
              <label>Payment Method *</label>
              <div class="payment-methods">
                <div class="payment-method">
                  <input type="radio" id="cash-on-delivery" name="payment_method" value="cash_on_delivery" checked>
                  <label for="cash-on-delivery">
                    <i class="fas fa-money-bill-wave"></i>
                    Cash on Delivery
                  </label>
                </div>
                <div class="payment-method">
                  <input type="radio" id="online-payment" name="payment_method" value="online_payment">
                  <label for="online-payment">
                    <i class="fas fa-credit-card"></i>
                    Online Payment
                  </label>
                </div>
                <div class="payment-method">
                  <input type="radio" id="upi-payment" name="payment_method" value="upi_payment">
                  <label for="upi-payment">
                    <i class="fab fa-google-pay"></i>
                    UPI Payment
                  </label>
                </div>
              </div>
            </div>

            <button type="submit" class="place-order-btn" id="place-order-btn">
              <i class="fas fa-check-circle"></i>
              Place Order - ₹<span id="order-btn-total">0</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loading-overlay">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <h3>Processing Your Order...</h3>
      <p>Please wait while we confirm your order</p>
    </div>
  </div>

  <!-- Notification -->
  <div class="notification" id="notification">Notification message</div>

  <script>
    // Pass PHP data to JS
    const currentUser = {
      username: "<?php echo $username; ?>"
    };
    const orderType = "<?php echo $orderType; ?>"; // 'single' or 'cart'

    // DOM Elements
    const orderSummary = document.getElementById('order-summary');
    const totalAmount = document.getElementById('total-amount');
    const orderBtnTotal = document.getElementById('order-btn-total');
    const checkoutForm = document.getElementById('checkout-form');
    const notification = document.getElementById('notification');
    const loadingOverlay = document.getElementById('loading-overlay');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const orderTypeIndicator = document.getElementById('order-type-indicator');

    let orderItems = [];
    let total = 0;

    function loadOrderItems() {
      if (orderType === 'single') {
        // Load single order item
        const singleOrder = localStorage.getItem(`single_order_${currentUser.username}`);
        if (singleOrder) {
          const singleItem = JSON.parse(singleOrder);
          orderItems = [singleItem];
          // Clear single order after loading
          localStorage.removeItem(`single_order_${currentUser.username}`);
          
          // Update indicator
          orderTypeIndicator.textContent = 'Single Item Order';
          orderTypeIndicator.className = 'order-type-indicator single-order';
        } else {
          orderItems = [];
        }
      } else {
        // Load cart items (default)
        const cartData = localStorage.getItem(`cart_${currentUser.username}`);
        if (cartData) {
          orderItems = JSON.parse(cartData);
          
          // Update indicator
          orderTypeIndicator.textContent = `Cart Order (${orderItems.length} items)`;
          orderTypeIndicator.className = 'order-type-indicator cart-order';
        } else {
          orderItems = [];
        }
      }
      
      renderOrderSummary();
    }

    function renderOrderSummary() {
      orderSummary.innerHTML = '';
      total = 0;

      if (orderItems.length === 0) {
        orderSummary.innerHTML = `
          <div class="empty-order">
            <h3>No items to order</h3>
            <p>Your order is empty. Please go back to the menu to add items.</p>
            <a href="menu.php">Back to Menu</a>
          </div>
        `;
        totalAmount.textContent = 0;
        orderBtnTotal.textContent = 0;
        placeOrderBtn.disabled = true;
        return;
      }

      orderItems.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        const orderItem = document.createElement('div');
        orderItem.className = 'order-item';
        orderItem.innerHTML = `
          <div class="item-details">
            <div class="item-name">${item.name}</div>
            <div class="item-quantity">Quantity: ${item.quantity} × ₹${item.price}</div>
          </div>
          <div class="item-price">₹${itemTotal}</div>
        `;
        orderSummary.appendChild(orderItem);
      });

      totalAmount.textContent = total;
      orderBtnTotal.textContent = total;
      placeOrderBtn.disabled = false;
    }

    function showNotification(message, type = 'success') {
      notification.textContent = message;
      notification.className = `notification ${type}`;
      notification.style.display = 'block';
      setTimeout(() => { notification.style.display = 'none'; }, 4000);
    }

    checkoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (orderItems.length === 0) {
        showNotification('Your order is empty!', 'error');
        return;
      }

      // Show loading overlay
      loadingOverlay.classList.add('show');
      placeOrderBtn.disabled = true;

      const formData = new FormData(checkoutForm);
      const orderData = {
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        delivery_address: formData.get('delivery_address'),
        order_notes: formData.get('order_notes'),
        payment_method: formData.get('payment_method'),
        cart: orderItems
      };

      try {
        const response = await fetch('checkout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ ...orderData, cart: JSON.stringify(orderItems) })
        });

        const result = await response.json();
        if (result.status === 'success') {
          // Clear cart ONLY if this was a cart order, NOT for single orders
          if (orderType === 'cart') {
            localStorage.removeItem(`cart_${currentUser.username}`);
          }
          // Note: Single order localStorage is already cleared when loading
          
          showNotification('Order placed successfully!', 'success');
          setTimeout(() => {
            window.location.href = `order_confirmation.php?order_id=${result.order_id}`;
          }, 2000);
        } else {
          showNotification(result.message || 'Order failed!', 'error');
        }
      } catch (error) {
        console.error(error);
        showNotification('Error placing order!', 'error');
      } finally {
        loadingOverlay.classList.remove('show');
        placeOrderBtn.disabled = false;
      }
    });

    document.addEventListener('DOMContentLoaded', loadOrderItems);
  </script>
</body>
</html>