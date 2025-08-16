<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$currentUser = [
    'username' => $_SESSION['user'],
    'role' => $_SESSION['role']
];

// Get user ID from database
$stmt = $conn->prepare("SELECT id FROM users WHERE fullname = ?");
$stmt->bind_param("s", $currentUser['username']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_id = $userData['id'];
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
      max-width: 1140px;
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

    .checkout-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-top: 40px;
    }

    .section {
      background: var(--surface-color);
      border-radius: 15px;
      padding: 30px;
      border: 1px solid color-mix(in srgb, var(--accent-color), transparent 70%);
    }

    .section h2 {
      color: var(--accent-color);
      font-family: var(--heading-font);
      font-size: 24px;
      margin-bottom: 25px;
      text-align: center;
    }

    /* Order Summary */
    .order-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      color: var(--heading-color);
      font-weight: 600;
      margin-bottom: 5px;
    }

    .item-quantity {
      color: var(--default-color);
      font-size: 14px;
    }

    .item-price {
      color: var(--accent-color);
      font-weight: bold;
      font-size: 18px;
    }

    .order-total {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid var(--accent-color);
      text-align: right;
    }

    .total-amount {
      font-size: 24px;
      font-weight: bold;
      color: var(--accent-color);
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--heading-color);
      font-weight: 600;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid color-mix(in srgb, var(--default-color), transparent 60%);
      border-radius: 8px;
      background: var(--background-color);
      color: var(--default-color);
      font-family: var(--default-font);
      font-size: 16px;
      transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--accent-color);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    /* Payment Methods */
    .payment-methods {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 15px;
      margin-top: 15px;
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
      padding: 15px;
      border: 2px solid color-mix(in srgb, var(--default-color), transparent 70%);
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: var(--background-color);
    }

    .payment-method input[type="radio"]:checked + label {
      border-color: var(--accent-color);
      background: color-mix(in srgb, var(--accent-color), transparent 90%);
      color: var(--accent-color);
    }

    .payment-method i {
      display: block;
      font-size: 24px;
      margin-bottom: 8px;
    }

    /* Place Order Button */
    .place-order-btn {
      width: 100%;
      padding: 15px;
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      border-radius: 10px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-family: var(--nav-font);
      margin-top: 20px;
    }

    .place-order-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    .place-order-btn:disabled {
      background: color-mix(in srgb, var(--default-color), transparent 70%);
      cursor: not-allowed;
      transform: none;
    }

    /* Notification */
    .notification {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      z-index: 1001;
      display: none;
      font-size: 16px;
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
    }

    .loading-overlay.show {
      display: flex;
    }

    .loading-content {
      background: var(--surface-color);
      padding: 40px;
      border-radius: 15px;
      text-align: center;
      border: 2px solid var(--accent-color);
    }

    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 3px solid color-mix(in srgb, var(--accent-color), transparent 70%);
      border-top: 3px solid var(--accent-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 20px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .checkout-container {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .payment-methods {
        grid-template-columns: 1fr;
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
          <i class="fas fa-arrow-left"></i>
          Back to Menu
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">
      <div class="section-title" style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: var(--accent-color); font-family: var(--heading-font); font-size: 36px; margin: 0;">Checkout</h1>
        <p style="color: var(--default-color); margin: 10px 0 0;">Complete your order</p>
      </div>

      <div class="checkout-container">
        <!-- Order Summary -->
        <div class="section">
          <h2><i class="fas fa-receipt"></i> Order Summary</h2>
          <div id="order-summary">
            <!-- Order items will be populated by JavaScript -->
          </div>
          <div class="order-total">
            <div style="font-size: 18px; margin-bottom: 10px;">
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
                <input type="text" id="customer-name" name="customer_name" required>
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
      <h3 style="color: var(--accent-color); margin: 0;">Processing Your Order...</h3>
      <p style="color: var(--default-color); margin: 10px 0 0;">Please wait while we confirm your order</p>
    </div>
  </div>

  <!-- Notification -->
  <div class="notification" id="notification">
    Notification message
  </div>

  <script>
    // User data from PHP
    const currentUser = <?php echo json_encode($currentUser); ?>;
    const userId = <?php echo $user_id; ?>;

    // DOM Elements
    const orderSummary = document.getElementById('order-summary');
    const totalAmount = document.getElementById('total-amount');
    const orderBtnTotal = document.getElementById('order-btn-total');
    const checkoutForm = document.getElementById('checkout-form');
    const notification = document.getElementById('notification');
    const loadingOverlay = document.getElementById('loading-overlay');
    const placeOrderBtn = document.getElementById('place-order-btn');

    // Load cart from localStorage
    let cart = [];
    let total = 0;

    function loadCart() {
      const savedCart = localStorage.getItem(`cart_${currentUser.username}`);
      if (savedCart) {
        cart = JSON.parse(savedCart);
        renderOrderSummary();
      } else {
        // Redirect back to menu if no cart found
        window.location.href = 'menu.php';
      }
    }

    function renderOrderSummary() {
      if (cart.length === 0) {
        window.location.href = 'menu.php';
        return;
      }

      orderSummary.innerHTML = '';
      total = 0;

      cart.forEach(item => {
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
    }

    function showNotification(message, type = 'success') {
      notification.textContent = message;
      notification.className = `notification ${type}`;
      notification.style.display = 'block';

      setTimeout(() => {
        notification.style.display = 'none';
      }, 5000);
    }

    // Form submission
    checkoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
      }

      // Get form data
      const formData = new FormData(checkoutForm);
      const orderData = {
        user_id: userId,
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        delivery_address: formData.get('delivery_address'),
        order_notes: formData.get('order_notes'),
        payment_method: formData.get('payment_method'),
        cart_items: cart,
        total_amount: total
      };

      // Validate required fields
      if (!orderData.customer_name || !orderData.customer_phone || !orderData.delivery_address) {
        showNotification('Please fill in all required fields!', 'error');
        return;
      }

      // Show loading
      loadingOverlay.classList.add('show');
      placeOrderBtn.disabled = true;

      try {
        const response = await fetch('process_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
          // Clear cart
          localStorage.removeItem(`cart_${currentUser.username}`);
          
          // Show success message
          showNotification('Order placed successfully! Order ID: ' + result.order_id, 'success');
          
          // Redirect to order confirmation page after delay
          setTimeout(() => {
            window.location.href = `order_confirmation.php?order_id=${result.order_id}`;
          }, 2000);
        } else {
          showNotification(result.message || 'Failed to place order. Please try again.', 'error');
        }
      } catch (error) {
        console.error('Order submission error:', error);
        showNotification('An error occurred. Please try again.', 'error');
      } finally {
        loadingOverlay.classList.remove('show');
        placeOrderBtn.disabled = false;
      }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      loadCart();
    });
  </script>
</body>
</html>