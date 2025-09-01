<?php
session_start();
require 'db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']) && isset($_SESSION['role']);
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = [
        'username' => $_SESSION['user'],
        'role' => $_SESSION['role']
    ];
}

// Fetch menu items from database
$menuItems = [];
$result = $conn->query("SELECT * FROM menu_items ORDER BY FIELD(category, 'starters','main','desserts','beverages'), id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - Westley's Resto Cafe</title>
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

    a {
      color: var(--accent-color);
      text-decoration: none;
      transition: 0.3s;
    }

    a:hover {
      color: color-mix(in srgb, var(--accent-color), transparent 25%);
    }

    h1, h2, h3, h4, h5, h6 {
      color: var(--heading-color);
      font-family: var(--heading-font);
    }

    .container {
      width: 100%;
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .section {
      padding: 60px 0;
    }

    /* Header Styles */
    .header {
      --background-color: rgba(12, 11, 9, 0.61);
      color: var(--default-color);
      transition: all 0.5s;
      z-index: 997;
      position: fixed;
      width: 100%;
      top: 0;
    }

    .header .branding {
      background-color: var(--background-color);
      min-height: 60px;
      padding: 10px 0;
      transition: 0.3s;
      border-bottom: 1px solid var(--background-color);
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header .logo {
      line-height: 1;
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

    /* User Controls in Header */
    .header-controls {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .user-info {
      display: none;
      align-items: center;
      gap: 15px;
    }

    .user-info.logged-in {
      display: flex;
    }

    .user-welcome {
      color: var(--default-color);
      font-family: var(--nav-font);
      font-size: 14px;
    }

    .orders-btn {
      background: transparent;
      color: var(--accent-color);
      border: 1px solid var(--accent-color);
      padding: 8px 15px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.3s;
      font-family: var(--nav-font);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .orders-btn:hover {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    .logout-btn {
      background: transparent;
      color: var(--accent-color);
      border: 1px solid var(--accent-color);
      padding: 8px 15px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.3s;
      font-family: var(--nav-font);
    }

    .logout-btn:hover {
      background: var(--accent-color);
      color: var(--contrast-color);
    }

    .login-btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 10px 20px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s;
      font-family: var(--nav-font);
    }

    .login-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
    }

    /* Section Title */
    .section-title {
      padding-bottom: 60px;
      position: relative;
      text-align: center;
    }

    .section-title h2 {
      font-size: 14px;
      font-weight: 500;
      padding: 0;
      line-height: 1px;
      margin: 0;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      position: relative;
    }

    .section-title h2::after {
      content: "";
      width: 120px;
      height: 1px;
      display: inline-block;
      background: var(--accent-color);
      margin: 4px 10px;
    }

    .section-title p {
      color: var(--accent-color);
      margin: 15px 0 0;
      font-size: 36px;
      font-weight: 600;
      font-family: var(--heading-font);
      position: relative;
      display: inline-block;
      cursor: pointer;
    }

    .section-title p::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      background: var(--accent-color);
      bottom: -10px;
      left: 0;
      transition: width 0.3s ease;
    }

    .section-title p:hover::after {
      width: 100%;
    }

    /* Menu Section */
    .menu {
      background: var(--background-color);
      position: relative;
      padding: 80px 0;
      padding-top: 140px;
    }

    .menu:before {
      content: "";
      background: color-mix(in srgb, var(--background-color), transparent 12%);
      position: absolute;
      bottom: 0;
      top: 0;
      left: 0;
      right: 0;
    }

    .menu .container {
      position: relative;
      z-index: 2;
    }

    .menu-filters {
      padding: 0;
      margin: 0 auto 30px;
      list-style: none;
      text-align: center;
      border-radius: 50px;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .menu-filters li {
      color: var(--default-color);
      cursor: pointer;
      display: inline-block;
      padding: 8px 20px;
      font-size: 16px;
      font-weight: 500;
      line-height: 1;
      transition: all ease-in-out 0.3s;
      font-family: var(--nav-font);
      margin: 5px;
      border-radius: 50px;
      border: 1px solid var(--accent-color);
    }

    .menu-filters li:hover,
    .menu-filters li.filter-active {
      color: var(--contrast-color);
      background: var(--accent-color);
    }

    .menu-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 30px;
    }

    .menu-item {
      background: var(--surface-color);
      border-radius: 10px;
      overflow: hidden;
      position: relative;
      opacity: 0;
      transform: translateY(20px);
      transition: transform 0.5s ease, opacity 0.5s ease, box-shadow 0.3s ease;
      animation: fadeInUp 0.6s ease forwards;
      animation-delay: calc(var(--item-index) * 0.1s);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .menu-item:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.3);
    }

    .menu-item-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .menu-item:hover .menu-item-img {
      transform: scale(1.05);
    }

    .menu-item-content {
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    .menu-item-title {
      font-size: 18px;
      margin: 0 0 10px;
      display: flex;
      justify-content: space-between;
    }

    .menu-item-price {
      color: var(--accent-color);
      font-weight: bold;
    }

    .menu-item-desc {
      font-size: 14px;
      color: color-mix(in srgb, var(--default-color), transparent 30%);
      margin-bottom: 15px;
    }

    .menu-item-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .quantity-control {
      display: flex;
      align-items: center;
    }

    .quantity-btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.2s ease;
    }

    .quantity-btn:hover {
      transform: scale(1.1);
    }

    .quantity-btn:disabled {
      background: color-mix(in srgb, var(--accent-color), transparent 50%);
      cursor: not-allowed;
      transform: none;
    }

    .quantity-input {
      width: 30px;
      text-align: center;
      background: transparent;
      border: none;
      color: var(--default-color);
      margin: 0 5px;
    }

    .add-to-cart {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 6px 12px;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
      flex: 1;
      position: relative;
      overflow: hidden;
    }

    .add-to-cart::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 0;
      border-radius: 100%;
      transform: scale(1, 1) translate(-50%);
      transform-origin: 50% 50%;
    }

    .add-to-cart:focus:not(:active)::after {
      animation: ripple 1s ease-out;
    }

    .order-now {
      background: #4CAF50;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
      flex: 1;
      position: relative;
      overflow: hidden;
    }

    .order-now::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 0;
      border-radius: 100%;
      transform: scale(1, 1) translate(-50%);
      transform-origin: 50% 50%;
    }

    .order-now:focus:not(:active)::after {
      animation: ripple 1s ease-out;
    }

    .add-to-cart:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    .order-now:hover {
      background: #45a049;
      transform: translateY(-2px);
    }

    .add-to-cart:disabled,
    .order-now:disabled {
      background: color-mix(in srgb, var(--default-color), transparent 70%);
      cursor: not-allowed;
      transform: none;
    }

    /* Cart Section */
    .cart-sidebar {
      position: fixed;
      top: 0;
      right: -400px;
      width: 400px;
      height: 100%;
      background: var(--surface-color);
      box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
      transition: right 0.3s ease-out;
      z-index: 1000;
      padding: 20px;
      overflow-y: auto;
      display: none;
    }

    .cart-sidebar.open {
      right: 0;
      display: block;
    }

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
    }

    .close-cart {
      background: none;
      border: none;
      color: var(--default-color);
      font-size: 24px;
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .close-cart:hover {
      transform: rotate(90deg);
    }

    .cart-items-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .cart-item {
      background: var(--background-color);
      border-radius: 8px;
      padding: 10px;
      position: relative;
      animation: slideInRight 0.3s ease forwards;
      opacity: 0;
      transform: translateX(20px);
    }

    .cart-item:nth-child(odd) {
      animation-delay: 0.1s;
    }

    .cart-item:nth-child(even) {
      animation-delay: 0.2s;
    }

    .cart-item-img {
      width: 100%;
      height: 80px;
      object-fit: cover;
      border-radius: 5px;
      margin-bottom: 5px;
      transition: transform 0.3s ease;
    }

    .cart-item:hover .cart-item-img {
      transform: scale(1.05);
    }

    .cart-item-remove {
      position: absolute;
      top: 5px;
      right: 5px;
      background: #ff6b6b;
      color: white;
      border: none;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 10px;
      transition: all 0.2s ease;
    }

    .cart-item-remove:hover {
      transform: scale(1.2);
      background: #ff5252;
    }

    .cart-total {
      font-size: 18px;
      font-weight: bold;
      text-align: right;
      margin: 20px 0;
      padding-top: 15px;
      border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
      animation: fadeIn 0.5s ease;
    }

    .checkout-btn {
      width: 100%;
      padding: 12px;
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .checkout-btn::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 0;
      border-radius: 100%;
      transform: scale(1, 1) translate(-50%);
      transform-origin: 50% 50%;
    }

    .checkout-btn:focus:not(:active)::after {
      animation: ripple 1s ease-out;
    }

    .checkout-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    /* Cart Button in Header */
    .header-cart {
      display: none;
      align-items: center;
      cursor: pointer;
      position: relative;
      transition: transform 0.2s ease;
    }

    .header-cart.show {
      display: flex;
    }

    .header-cart:hover {
      transform: scale(1.05);
    }

    .header-cart-icon {
      font-size: 22px;
      color: var(--accent-color);
      margin-right: 5px;
      transition: transform 0.3s ease;
    }

    .header-cart:hover .header-cart-icon {
      transform: rotate(-10deg);
    }

    .header-cart-text {
      font-family: var(--nav-font);
      color: var(--default-color);
    }

    .header-cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff6b6b;
      color: white;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      animation: pulse 1.5s infinite;
    }

    /* Login Required Message */
    .login-required-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.9);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1001;
      animation: fadeIn 0.3s ease;
    }

    .login-required-overlay.show {
      display: flex;
    }

    .login-required-message {
      background: var(--surface-color);
      padding: 40px;
      border-radius: 15px;
      text-align: center;
      max-width: 400px;
      margin: 20px;
      border: 2px solid var(--accent-color);
      animation: scaleIn 0.3s ease;
    }

    .login-required-message h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      font-size: 24px;
    }

    .login-required-message p {
      color: var(--default-color);
      margin-bottom: 25px;
      font-size: 16px;
    }

    .login-required-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }

    .login-redirect-btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      border: none;
      padding: 12px 25px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s;
      font-family: var(--nav-font);
    }

    .login-redirect-btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    .cancel-btn {
      background: #666;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s;
    }

    .cancel-btn:hover {
      background: #555;
      transform: translateY(-2px);
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
      animation: slideInUp 0.3s ease, fadeOut 0.5s ease 3.5s forwards;
    }

    .notification.error {
      background: #ff6b6b;
    }

    .notification.success {
      background: #4CAF50;
    }

    /* No Items Message */
    .no-items {
      text-align: center;
      padding: 60px 20px;
      color: var(--default-color);
    }

    .no-items h3 {
      color: var(--accent-color);
      margin-bottom: 20px;
      font-size: 24px;
    }

    .no-items p {
      font-size: 16px;
      margin-bottom: 30px;
    }

    .no-items .btn {
      background: var(--accent-color);
      color: var(--contrast-color);
      padding: 12px 25px;
      border-radius: 25px;
      text-decoration: none;
      transition: all 0.3s;
      display: inline-block;
    }

    .no-items .btn:hover {
      background: color-mix(in srgb, var(--accent-color), transparent 20%);
      transform: translateY(-2px);
    }

    /* Animation Keyframes */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translate(-50%, 20px);
      }
      to {
        opacity: 1;
        transform: translate(-50%, 0);
      }
    }

    @keyframes fadeOut {
      from {
        opacity: 1;
      }
      to {
        opacity: 0;
      }
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
      }
    }

    @keyframes ripple {
      0% {
        transform: scale(0, 0);
        opacity: 0.5;
      }
      20% {
        transform: scale(25, 25);
        opacity: 0.3;
      }
      100% {
        opacity: 0;
        transform: scale(40, 40);
      }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .cart-sidebar {
        width: 100%;
        right: -100%;
      }
      
      .cart-sidebar.open {
        right: 0;
      }
      
      .menu-container {
        grid-template-columns: 1fr;
      }

      .cart-items-container {
        grid-template-columns: 1fr;
      }

      .section-title p {
        font-size: 28px;
      }

      .header .logo h1 {
        font-size: 20px;
      }
      
      .header-cart-icon {
        margin-right: 0;
      }

      .header-controls {
        gap: 10px;
        flex-wrap: wrap;
      }

      .user-welcome {
        display: none;
      }

      .orders-btn {
        padding: 6px 10px;
        font-size: 12px;
      }

      .logout-btn {
        padding: 6px 10px;
        font-size: 12px;
      }
    }

    @media (min-width: 992px) {
      .menu-container {
        grid-template-columns: repeat(3, 1fr);
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
        <div class="header-controls">
          <?php if ($isLoggedIn): ?>
            <div class="user-info logged-in" id="user-info">
              <span class="user-welcome" id="user-welcome">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</span>
              <a href="my_orders.php" class="orders-btn">
                <i class="fas fa-list"></i>
                <span>My Orders</span>
              </a>
              <button class="logout-btn" id="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
              </button>
            </div>
            <div class="header-cart show" id="header-cart">
              <i class="fas fa-shopping-cart header-cart-icon"></i>
              <span class="header-cart-count" id="header-cart-count">0</span>
            </div>
          <?php else: ?>
            <button class="login-btn" id="show-login-btn" onclick="window.location.href='login.php'">Login</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Login Required Message Overlay -->
  <div class="login-required-overlay" id="login-required-overlay">
    <div class="login-required-message">
      <h3>Login Required</h3>
      <p>Please login to add items to cart and place orders. If you don't have an account, you can register first.</p>
      <div class="login-required-buttons">
        <button class="login-redirect-btn" onclick="window.location.href='login.php'">Login</button>
        <button class="cancel-btn" id="cancel-login-btn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Menu Section -->
  <section id="menu" class="menu section">
    <div class="container">
      <div class="section-title">
        <h2>Menu</h2>
        <p>Check Our Tasty Menu</p>
      </div>

      <?php if (empty($menuItems)): ?>
        <div class="no-items">
          <h3>No Menu Items Available</h3>
          <p>Our menu is being updated. Please check back soon!</p>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="manage_menu.php" class="btn">Add Menu Items</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <ul class="menu-filters">
          <li class="filter-active" data-category="all">All</li>
          <li data-category="starters">Starters</li>
          <li data-category="main">Main Course</li>
          <li data-category="desserts">Desserts</li>
          <li data-category="beverages">Beverages</li>
        </ul>

        <div class="menu-container" id="menu-container">
          <!-- Menu items will be loaded here by JavaScript -->
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($isLoggedIn): ?>
  <!-- Cart Sidebar -->
  <div class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
      <h3>Your Order</h3>
      <button class="close-cart" id="close-cart">&times;</button>
    </div>
    <div class="cart-items-container" id="cart-items">
      <!-- Cart items will be added here -->
    </div>
    <div class="cart-total">
      Total: ₹<span id="cart-total">0</span>
    </div>
    <button class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
  </div>
  <?php endif; ?>

  <!-- Notification -->
  <div class="notification" id="notification">
    Notification message
  </div>

  <script>
    // Check if user is logged in from PHP
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    const currentUser = <?php echo $isLoggedIn ? json_encode($currentUser) : 'null'; ?>;

    // Get menu items from PHP/database instead of hardcoded array
    const menuItems = <?php echo json_encode($menuItems); ?>;

    // Convert string IDs and prices to numbers for JavaScript compatibility
    menuItems.forEach(item => {
        item.id = parseInt(item.id);
        item.price = parseInt(item.price);
    });

    // DOM Elements
    const menuContainer = document.getElementById('menu-container');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const headerCart = document.getElementById('header-cart');
    const headerCartCount = document.getElementById('header-cart-count');
    const closeCart = document.getElementById('close-cart');
    const checkoutBtn = document.getElementById('checkout-btn');
    const notification = document.getElementById('notification');
    const filterButtons = document.querySelectorAll('.menu-filters li');
    const loginRequiredOverlay = document.getElementById('login-required-overlay');
    const cancelLoginBtn = document.getElementById('cancel-login-btn');
    const logoutBtn = document.getElementById('logout-btn');

    // App state
    let cart = [];

    // Initialize the page
    function init() {
      if (menuItems.length > 0) {
        renderMenu();
        setupEventListeners();
        if (isLoggedIn) {
          loadCartFromStorage();
        }
      }
    }

    // Load cart from localStorage if user is logged in
    function loadCartFromStorage() {
      const savedCart = localStorage.getItem(`cart_${currentUser.username}`);
      if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCart();
      }
    }

    // Save cart to localStorage
    function saveCartToStorage() {
      if (isLoggedIn) {
        localStorage.setItem(`cart_${currentUser.username}`, JSON.stringify(cart));
      }
    }

    // Render menu items
    function renderMenu(filter = 'all') {
      if (!menuContainer) return;
      
      menuContainer.innerHTML = '';
      
      const filteredItems = filter === 'all' 
        ? menuItems 
        : menuItems.filter(item => item.category === filter);
      
      if (filteredItems.length === 0) {
        menuContainer.innerHTML = '<div class="no-items"><h3>No items found in this category</h3><p>Try selecting a different category.</p></div>';
        return;
      }
      
      filteredItems.forEach((item, index) => {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-item';
        menuItem.dataset.category = item.category;
        menuItem.style.setProperty('--item-index', index);
        
        // Handle missing images with a placeholder
        const imageUrl = item.image || 'https://via.placeholder.com/300x180/29261f/cda45e?text=No+Image';
        
        menuItem.innerHTML = `
          <img src="${imageUrl}" class="menu-item-img" alt="${item.name}" onerror="this.src='https://via.placeholder.com/300x180/29261f/cda45e?text=No+Image'">
          <div class="menu-item-content">
            <div class="menu-item-title">
              <span>${item.name}</span>
              <span class="menu-item-price">₹${item.price}</span>
            </div>
            <p class="menu-item-desc">${item.description || 'No description available'}</p>
            <div class="menu-item-actions">
              <div class="quantity-control">
                <button class="quantity-btn minus" data-id="${item.id}">-</button>
                <input type="text" class="quantity-input" value="1" data-id="${item.id}" readonly>
                <button class="quantity-btn plus" data-id="${item.id}">+</button>
              </div>
              <button class="add-to-cart" data-id="${item.id}">Add</button>
              <button class="order-now" data-id="${item.id}">Order Now</button>
            </div>
          </div>
        `;
        menuContainer.appendChild(menuItem);
      });
    }

    // Setup event listeners
    function setupEventListeners() {
      // Filter buttons
      filterButtons.forEach(button => {
        button.addEventListener('click', () => {
          filterButtons.forEach(btn => btn.classList.remove('filter-active'));
          button.classList.add('filter-active');
          renderMenu(button.dataset.category);
        });
      });

      // Login required overlay controls
      if (cancelLoginBtn) {
        cancelLoginBtn.addEventListener('click', () => {
          loginRequiredOverlay.classList.remove('show');
        });
      }

      if (loginRequiredOverlay) {
        loginRequiredOverlay.addEventListener('click', (e) => {
          if (e.target === loginRequiredOverlay) {
            loginRequiredOverlay.classList.remove('show');
          }
        });
      }

      // Logout functionality
      if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
          window.location.href = 'logout.php';
        });
      }

      // Cart functionality (only if logged in)
      if (isLoggedIn) {
        // Cart toggle in header
        if (headerCart) {
          headerCart.addEventListener('click', () => {
            cartSidebar.classList.add('open');
          });
        }

        // Close cart
        if (closeCart) {
          closeCart.addEventListener('click', () => {
            cartSidebar.classList.remove('open');
          });
        }

        // Checkout button
        if (checkoutBtn) {
          checkoutBtn.addEventListener('click', () => {
            if (cart.length === 0) {
              showNotification('Your cart is empty!', 'error');
              return;
            }
            placeOrder();
          });
        }

        // Cart item removal (delegated)
        if (cartItems) {
          cartItems.addEventListener('click', (e) => {
            if (e.target.classList.contains('cart-item-remove')) {
              const itemId = parseInt(e.target.dataset.id);
              removeFromCart(itemId);
            }
          });
        }
      }

      // Menu item actions (delegated)
      if (menuContainer) {
        menuContainer.addEventListener('click', (e) => {
          if (e.target.classList.contains('add-to-cart') || e.target.classList.contains('order-now')) {
            if (!isLoggedIn) {
              showNotification('Please login to add items to cart!', 'error');
              loginRequiredOverlay.classList.add('show');
              return;
            }
          }

          if (e.target.classList.contains('add-to-cart')) {
            const itemId = parseInt(e.target.dataset.id);
            const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
            const quantity = parseInt(quantityInput.value);
            addToCart(itemId, quantity);
          }

          if (e.target.classList.contains('order-now')) {
            const itemId = parseInt(e.target.dataset.id);
            const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
            const quantity = parseInt(quantityInput.value);
            
            // Add item to cart
            addToCart(itemId, quantity);
            
            // Save cart and redirect directly to checkout
            saveCartToStorage();
            window.location.href = 'checkout.php';
          }

          if (e.target.classList.contains('quantity-btn')) {
            const itemId = parseInt(e.target.dataset.id);
            const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
            let quantity = parseInt(quantityInput.value);

            if (e.target.classList.contains('plus')) {
              quantity++;
            } else if (e.target.classList.contains('minus') && quantity > 1) {
              quantity--;
            }

            quantityInput.value = quantity;
          }
        });
      }
    }

    // Add item to cart
    function addToCart(itemId, quantity) {
      if (!isLoggedIn) {
        showNotification('Please login first!', 'error');
        loginRequiredOverlay.classList.add('show');
        return;
      }

      const menuItem = menuItems.find(item => item.id === itemId);
      if (!menuItem) return;

      const existingItem = cart.find(item => item.id === itemId);

      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        cart.push({
          id: menuItem.id,
          name: menuItem.name,
          price: menuItem.price,
          quantity: quantity,
          image: menuItem.image || 'https://via.placeholder.com/300x180/29261f/cda45e?text=No+Image'
        });
      }

      updateCart();
      saveCartToStorage();
      showNotification(`${quantity} ${menuItem.name} added to cart`, 'success');
    }

    // Remove item from cart
    function removeFromCart(itemId) {
      cart = cart.filter(item => item.id !== itemId);
      updateCart();
      saveCartToStorage();
      showNotification('Item removed from cart', 'success');
    }

    // Update cart display
    function updateCart() {
      if (!isLoggedIn || !cartItems || !cartTotal || !headerCartCount) return;

      cartItems.innerHTML = '';

      if (cart.length === 0) {
        cartItems.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--default-color);">Your cart is empty</p>';
        cartTotal.textContent = '0';
        headerCartCount.textContent = '0';
        return;
      }

      let total = 0;

      cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.style.animationDelay = `${index * 0.1}s`;
        cartItem.innerHTML = `
          <img src="${item.image}" class="cart-item-img" alt="${item.name}" onerror="this.src='https://via.placeholder.com/300x180/29261f/cda45e?text=No+Image'">
          <button class="cart-item-remove" data-id="${item.id}">&times;</button>
          <div class="cart-item-details">
            <h4 class="cart-item-title">${item.name}</h4>
            <div class="cart-item-price">₹${item.price} × ${item.quantity}</div>
          </div>
        `;
        cartItems.appendChild(cartItem);
      });

      cartTotal.textContent = total;
      headerCartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    // Place order - redirect to checkout
    function placeOrder() {
      if (!isLoggedIn) {
        showNotification('Please login first!', 'error');
        return;
      }

      if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
      }

      // Save cart to localStorage and redirect to checkout
      saveCartToStorage();
      window.location.href = 'checkout.php';
    }

    // Show notification
    function showNotification(message, type = 'success') {
      if (!notification) return;
      
      notification.textContent = message;
      notification.className = `notification ${type}`;
      notification.style.display = 'block';

      setTimeout(() => {
        notification.style.display = 'none';
      }, 4000);
    }

    // Initialize the app
    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>