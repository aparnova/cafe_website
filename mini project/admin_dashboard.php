<?php 
include 'db.php'; 
session_start(); 

// Get statistics for the dashboard
$stats = [];

// Total Customers
$customer_query = "SELECT COUNT(*) as total FROM users ";
$customer_result = $conn->query($customer_query);
$stats['total_customers'] = $customer_result->fetch_assoc()['total'];

// Total Orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = $conn->query($orders_query);
$stats['total_orders'] = $orders_result->fetch_assoc()['total'];

// Total Revenue with payment status consideration
$revenue_query = "SELECT SUM(total_price) as total FROM orders WHERE status != 'Cancelled' AND (payment_method = 'cash_on_delivery' OR (payment_method = 'razorpay' AND payment_status = 'paid'))";                                                                                                                                                   $revenue_result = $conn->query($revenue_query);
$revenue_result = $conn->query($revenue_query);
$stats['total_revenue'] = $revenue_result->fetch_assoc()['total'] ?? 0;

// Total Reservations
$reservations_query = "SELECT COUNT(*) as total FROM reservations";
$reservations_result = $conn->query($reservations_query);
$stats['total_reservations'] = $reservations_result->fetch_assoc()['total'];

// Total Messages
$messages_query = "SELECT COUNT(*) as total FROM contact_submissions";
$messages_result = $conn->query($messages_query);
$stats['total_messages'] = $messages_result->fetch_assoc()['total'];

// Today's Orders
$today_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
$today_result = $conn->query($today_query);
$stats['today_orders'] = $today_result->fetch_assoc()['total'];

// Today's Revenue
$today_revenue_query = "SELECT SUM(total_price) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'Cancelled' AND (payment_method = 'cash_on_delivery' OR (payment_method = 'razorpay' AND payment_status = 'paid'))";
$today_revenue_result = $conn->query($today_revenue_query);
$stats['today_revenue'] = $today_revenue_result->fetch_assoc()['total'] ?? 0;

// Total Recipes
$recipes_query = "SELECT COUNT(*) as total FROM recipes";
$recipes_result = $conn->query($recipes_query);
$stats['total_recipes'] = $recipes_result->fetch_assoc()['total'];

// Payment Statistics (keeping this for reference)
$payment_stats_query = "SELECT 
    COUNT(CASE WHEN payment_method = 'razorpay' THEN 1 END) as online_payments,
    COUNT(CASE WHEN payment_method = 'cash_on_delivery' THEN 1 END) as cod_payments,
    COUNT(CASE WHEN payment_method = 'razorpay' AND payment_status = 'paid' THEN 1 END) as successful_payments,
    COUNT(CASE WHEN payment_method = 'razorpay' AND payment_status = 'failed' THEN 1 END) as failed_payments,
    COUNT(CASE WHEN payment_method = 'razorpay' AND payment_status = 'pending' THEN 1 END) as pending_payments
    FROM orders";
$payment_stats_result = $conn->query($payment_stats_query);
$payment_stats = $payment_stats_result->fetch_assoc();

// Recent Orders for activity feed
$recent_orders_query = "SELECT o.id, o.status, o.payment_method, o.payment_status, o.created_at,
    COALESCE(o.customer_name, u.fullname) as customer_name
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10";
$recent_orders_result = $conn->query($recent_orders_query);
$recent_orders = [];
while ($row = $recent_orders_result->fetch_assoc()) {
    $recent_orders[] = $row;
}

// Menu Items Count (keeping for reference)
$menu_query = "SELECT COUNT(*) as total FROM menu_items";
$menu_result = $conn->query($menu_query);
$stats['total_menu_items'] = $menu_result->fetch_assoc()['total'];

// Delivery Boys Count (keeping for reference)
$delivery_query = "SELECT COUNT(*) as total FROM delivery_boys";
$delivery_result = $conn->query($delivery_query);
$stats['total_delivery_boys'] = $delivery_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Westley's Resto Café</title>
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
      transition: all 0.3s ease;
      z-index: 1000;
      box-shadow: 4px 0 10px rgba(0,0,0,0.1);
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: var(--accent) var(--secondary);
    }

    /* Webkit scrollbar styling for sidebar */
    .sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: var(--secondary);
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: var(--accent);
      border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
      background: #d97706;
    }

    .sidebar-collapsed {
      width: 80px;
    }

    .sidebar-collapsed .menu-text {
      display: none;
    }

    .sidebar-collapsed h2 {
      display: none;
    }

    .sidebar-header {
      position: sticky;
      top: 0;
      background: var(--primary);
      z-index: 10;
      padding: 20px 0 10px 0;
      border-bottom: 1px solid var(--secondary);
    }

    .sidebar h2 {
      text-align: center;
      font-size: 22px;
      margin-bottom: 20px;
      padding: 0 15px;
      transition: all 0.3s ease;
      white-space: nowrap;
    }

    .sidebar-content {
      padding-bottom: 20px;
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

    .sidebar a:hover {
      background: var(--secondary);
      border-left: 3px solid var(--accent);
      transform: translateX(5px);
    }

    .sidebar a i {
      margin-right: 10px;
      font-size: 18px;
      min-width: 20px;
    }

    .menu-text {
      transition: all 0.3s ease;
    }

    .main {
      margin-left: 275px;
      padding: 30px;
      transition: all 0.3s ease;
    }

    .main-expanded {
      margin-left: 80px;
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
      position: relative;
      cursor: pointer;
    }

    .header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 0;
      height: 3px;
      background: var(--accent);
      transition: width 0.3s ease;
    }

    .header h1:hover::after {
      width: 100%;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .pdf-download-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: var(--accent);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
    }

    .pdf-download-btn:hover {
      background: #d97706;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(245, 158, 11, 0.4);
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
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .user-avatar:hover {
      transform: scale(1.1);
      box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
    }

    /* PDF Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
    }

    .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      position: relative;
      animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .close {
      position: absolute;
      right: 20px;
      top: 20px;
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .close:hover {
      color: var(--danger);
    }

    .modal h2 {
      color: var(--primary);
      margin-bottom: 20px;
      font-size: 24px;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary);
      font-weight: 500;
    }

    .form-group select {
      width: 100%;
      padding: 12px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      font-size: 16px;
      background: white;
      transition: border-color 0.3s ease;
    }

    .form-group select:focus {
      outline: none;
      border-color: var(--accent);
    }

    .download-btn {
      width: 100%;
      padding: 15px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .download-btn:hover {
      background: #d97706;
      transform: translateY(-2px);
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      border-left: 4px solid var(--accent);
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.05));
      border-radius: 0 0 0 50px;
    }

    .stat-icon {
      font-size: 24px;
      color: var(--accent);
      margin-bottom: 15px;
    }

    .stat-title {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 5px;
    }

    .stat-change {
      font-size: 12px;
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .positive {
      color: var(--success);
    }

    .negative {
      color: var(--danger);
    }

    .neutral {
      color: #6b7280;
    }

    .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      margin-top: 30px;
    }

    .card {
      background: #fff;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      border-top: 3px solid var(--accent);
      cursor: pointer;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(245,158,11,0.1) 0%, rgba(245,158,11,0) 100%);
      opacity: 0;
      transition: all 0.3s ease;
    }

    .card:hover::before {
      opacity: 1;
    }

    .card-icon {
      font-size: 40px;
      margin-bottom: 15px;
      color: var(--accent);
      transition: all 0.3s ease;
    }

    .card:hover .card-icon {
      transform: scale(1.1);
      color: var(--primary);
    }

    .card h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: var(--primary);
      font-weight: 600;
    }

    .card p {
      color: #6b7280;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .card a {
      display: inline-block;
      padding: 8px 20px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 30px;
      font-size: 14px;
      transition: all 0.3s ease;
      border: 1px solid var(--primary);
    }

    .card a:hover {
      background: transparent;
      color: var(--primary);
    }

    .recent-activity {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-top: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .activity-title {
      font-size: 20px;
      margin-bottom: 20px;
      color: var(--primary);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .activity-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f3f4f6;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: var(--accent);
      font-size: 18px;
    }

    .activity-content {
      flex: 1;
      position: relative;
    }

    .activity-message {
      font-size: 14px;
      margin-bottom: 5px;
      color: var(--primary);
    }

    .activity-time {
      font-size: 12px;
      color: #9ca3af;
    }

    .activity-status {
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 12px;
      font-weight: 600;
      margin-left: auto;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-confirmed { background: #d1fae5; color: #065f46; }
    .status-processing { background: #bfdbfe; color: #1e3a8a; }
    .status-delivered { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-payment-pending { background: #fef3c7; color: #92400e; }
    .status-payment-failed { background: #fee2e2; color: #991b1b; }

    .payment-info {
      font-size: 10px;
      margin-top: 2px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .payment-success { color: var(--success); }
    .payment-failed { color: var(--danger); }
    .payment-pending { color: var(--warning); }

    .no-activity {
      text-align: center;
      color: #9ca3af;
      padding: 40px 0;
      position: relative;
    }

    .no-activity i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #d1d5db;
    }

    @media(max-width: 768px) {
      .sidebar {
        width: 80px;
      }
      
      .sidebar h2 {
        display: none;
      }
      
      .menu-text {
        display: none;
      }
      
      .main {
        margin-left: 80px;
      }
      
      .card-container {
        grid-template-columns: 1fr;
      }

      .stats-container {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }

      .header-right {
        flex-direction: column;
        gap: 10px;
      }

      .pdf-download-btn {
        padding: 8px 16px;
        font-size: 12px;
      }
    }

    /* Animation classes */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade {
      animation: fadeIn 0.5s ease forwards;
    }

    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }
    .delay-5 { animation-delay: 0.5s; }
    .delay-6 { animation-delay: 0.6s; }
    .delay-7 { animation-delay: 0.7s; }
    .delay-8 { animation-delay: 0.8s; }
    .delay-9 { animation-delay: 0.9s; }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">
    <h2>Westley's Resto Cafe</h2>
  </div>
  <div class="sidebar-content">
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span></a>
    <a href="view_customers.php"><i class="fas fa-users"></i> <span class="menu-text">Customers</span></a>
    <a href="manage_menu.php"><i class="fas fa-utensils"></i> <span class="menu-text">Menu Editor</span></a>
    <a href="view_orders.php"><i class="fas fa-receipt"></i> <span class="menu-text">Orders</span></a>
    <a href="view_delivery.php"><i class="fas fa-truck"></i> <span class="menu-text">Delivery</span></a>
    <a href="view_reservations.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Reservations</span></a>
    <a href="view_messages.php"><i class="fas fa-envelope"></i> <span class="menu-text">Messages</span></a>
    <a href="view_gallery.php"><i class="fas fa-images"></i> <span class="menu-text">Gallery</span></a>
    <a href="view_aboutus.php"><i class="fas fa-info-circle"></i> <span class="menu-text">About Us</span></a>
    <a href="view_contact_us.php"><i class="fas fa-phone"></i> <span class="menu-text">Contact Us</span></a>
    <a href="view_recipes.php"><i class="fas fa-book"></i> <span class="menu-text">Recipes</span></a>
    <a href="homepage.php"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span></a>
  </div>
</div>

<div class="main">
  <div class="header">
    <h1>Admin Dashboard</h1>
    <div class="header-right">
      <button class="pdf-download-btn" onclick="openPdfModal()">
        <i class="fas fa-file-pdf"></i>
        Download Revenue Report
      </button>
      <div class="user-avatar">A</div>
    </div>
  </div>

  <!-- PDF Download Modal -->
  <div id="pdfModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closePdfModal()">&times;</span>
      <h2><i class="fas fa-file-pdf"></i> Download Revenue Report</h2>
      <form id="pdfForm" action="download_revenue_pdf.php" method="post" target="_blank">
        <div class="form-group">
          <label for="reportMonth">Select Month:</label>
          <select id="reportMonth" name="report_month" required>
            <option value="">Choose a month...</option>
            <?php
            // Generate options for the last 12 months
            for ($i = 0; $i < 12; $i++) {
              $month = date('Y-m', strtotime("-$i months"));
              $monthName = date('F Y', strtotime("-$i months"));
              echo "<option value='$month'>$monthName</option>";
            }
            ?>
          </select>
        </div>
        <button type="submit" class="download-btn">
          <i class="fas fa-download"></i>
          Download PDF Report
        </button>
      </form>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-container">
    <div class="stat-card animate-fade">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-title">Total Customers</div>
      <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-info-circle"></i>
        Registered users
      </div>
    </div>

    <div class="stat-card animate-fade delay-1">
      <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
      <div class="stat-title">Total Orders</div>
      <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-calendar"></i>
        All time orders
      </div>
    </div>

    <div class="stat-card animate-fade delay-2">
      <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
      <div class="stat-title">Total Revenue</div>
      <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
      <div class="stat-change positive">
        <i class="fas fa-check-circle"></i>
        Confirmed payments only
      </div>
    </div>

    <div class="stat-card animate-fade delay-3">
      <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
      <div class="stat-title">Total Reservations</div>
      <div class="stat-value"><?php echo number_format($stats['total_reservations']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-table"></i>
        Table bookings
      </div>
    </div>

    <div class="stat-card animate-fade delay-4">
      <div class="stat-icon"><i class="fas fa-envelope"></i></div>
      <div class="stat-title">Total Messages</div>
      <div class="stat-value"><?php echo number_format($stats['total_messages']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-comments"></i>
        Customer inquiries
      </div>
    </div>

    <div class="stat-card animate-fade delay-5">
      <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
      <div class="stat-title">Today's Orders</div>
      <div class="stat-value"><?php echo number_format($stats['today_orders']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-today"></i>
        <?php echo date('M d, Y'); ?>
      </div>
    </div>

    <div class="stat-card animate-fade delay-6">
      <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
      <div class="stat-title">Today's Revenue</div>
      <div class="stat-value">₹<?php echo number_format($stats['today_revenue'], 2); ?></div>
      <div class="stat-change positive">
        <i class="fas fa-trending-up"></i>
        Today's earnings
      </div>
    </div>

    <div class="stat-card animate-fade delay-7">
      <div class="stat-icon"><i class="fas fa-book"></i></div>
      <div class="stat-title">Total Recipes</div>
      <div class="stat-value"><?php echo number_format($stats['total_recipes']); ?></div>
      <div class="stat-change neutral">
        <i class="fas fa-utensils"></i>
        Recipe collection
      </div>
    </div>
  </div>

  <!-- Management Cards -->
  <div class="card-container">
    <div class="card animate-fade delay-1" onclick="window.location.href='view_customers.php'">
      <div class="card-icon"><i class="fas fa-users"></i></div>
      <h3>Customer Details</h3>
      <p>View and manage all customer accounts and information</p>
      <a href="view_customers.php">Manage Customers</a>
    </div>
    <div class="card animate-fade delay-2" onclick="window.location.href='manage_menu.php'">
      <div class="card-icon"><i class="fas fa-utensils"></i></div>
      <h3>Menu Management</h3>
      <p>Add, edit or remove items from your restaurant menu</p>
      <a href="manage_menu.php">Edit Menu</a>
    </div>
    <div class="card animate-fade delay-3" onclick="window.location.href='view_orders.php'">
      <div class="card-icon"><i class="fas fa-receipt"></i></div>
      <h3>Online Orders</h3>
      <p>View and assign delivery orders to your staff</p>
      <a href="view_orders.php">View Orders</a>
    </div>
    <div class="card animate-fade delay-4" onclick="window.location.href='view_delivery.php'">
      <div class="card-icon"><i class="fas fa-truck"></i></div>
      <h3>Delivery Management</h3>
      <p>Track and manage delivery orders and delivery personnel</p>
      <a href="view_delivery.php">Manage Delivery</a>
    </div>
    <div class="card animate-fade delay-5" onclick="window.location.href='view_reservations.php'">
      <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
      <h3>Table Reservations</h3>
      <p>Manage upcoming table reservations and bookings</p>
      <a href="view_reservations.php">Check Reservations</a>
    </div>
    <div class="card animate-fade delay-6" onclick="window.location.href='view_messages.php'">
      <div class="card-icon"><i class="fas fa-envelope"></i></div>
      <h3>Customer Messages</h3>
      <p>Respond to customer inquiries and feedback</p>
      <a href="view_messages.php">View Messages</a>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="recent-activity animate-fade delay-8">
    <div class="activity-title">
      <i class="fas fa-clock"></i>
      Recent Order Activity
    </div>
    
    <?php if (!empty($recent_orders)): ?>
      <?php foreach ($recent_orders as $order): ?>
        <div class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="activity-content">
            <div class="activity-message">
              Order #<?php echo $order['id']; ?> by <?php echo htmlspecialchars($order['customer_name']); ?>
            </div>
            <div class="activity-time">
              <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
            </div>
            <?php if ($order['payment_method'] === 'razorpay'): ?>
              <div class="payment-info payment-<?php echo $order['payment_status']; ?>">
                <i class="fas fa-credit-card"></i>
                Online - <?php echo ucfirst($order['payment_status']); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="activity-status status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
            <?php echo $order['status']; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-activity">
        <i class="fas fa-inbox"></i>
        <h3>No Recent Activity</h3>
        <p>Recent orders will appear here</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // PDF Modal functions
  function openPdfModal() {
    document.getElementById('pdfModal').style.display = 'block';
  }

  function closePdfModal() {
    document.getElementById('pdfModal').style.display = 'none';
  }

  // Close modal when clicking outside of it
  window.onclick = function(event) {
    const modal = document.getElementById('pdfModal');
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  }

  // Add hover effect to cards
  const cards = document.querySelectorAll('.card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-5px)';
      card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.boxShadow = '';
    });
  });

  // Animate elements when they come into view
  const animateOnScroll = () => {
    const elements = document.querySelectorAll('.animate-fade');
    
    elements.forEach(element => {
      const elementPosition = element.getBoundingClientRect().top;
      const windowHeight = window.innerHeight;
      
      if (elementPosition < windowHeight - 100) {
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
      }
    });
  };

  // Run once on page load
  animateOnScroll();
  
  // Run on scroll
  window.addEventListener('scroll', animateOnScroll);

  // Auto-refresh stats every 5 minutes
  setInterval(() => {
    location.reload();
  }, 300000);
</script>

</body>
</html>