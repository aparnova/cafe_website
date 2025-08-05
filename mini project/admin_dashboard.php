<?php include 'db.php'; session_start(); ?>
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
      width: 250px;
      height: 100vh;
      background: var(--primary);
      color: #fff;
      padding-top: 20px;
      transition: all 0.3s ease;
      z-index: 1000;
      box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-collapsed {
      width: 80px;
    }

    .sidebar-collapsed .menu-text {
      display: none;
    }

    .sidebar-collapsed h2 {
      font-size: 0;
    }

    .sidebar-collapsed h2::after {
      content: "WR";
      font-size: 22px;
    }

    .sidebar h2 {
      text-align: center;
      font-size: 22px;
      margin-bottom: 30px;
      padding: 0 15px;
      transition: all 0.3s ease;
      white-space: nowrap;
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
      margin-left: 250px;
      padding: 30px;
      transition: all 0.3s ease;
    }

    .main-expanded {
      margin-left: 80px;
    }

    .toggle-sidebar {
      position: fixed;
      top: 20px;
      left: 210px;
      background: var(--accent);
      color: white;
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1001;
      transition: all 0.3s ease;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .toggle-sidebar:hover {
      transform: scale(1.1);
    }

    .sidebar-collapsed + .main .toggle-sidebar {
      left: 50px;
      transform: rotate(180deg);
    }

    .sidebar-collapsed + .main .toggle-sidebar:hover {
      transform: rotate(180deg) scale(1.1);
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
    }

    .header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 50px;
      height: 3px;
      background: var(--accent);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
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

    .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 20px;
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

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }

    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .stat-title {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 10px;
    }

    .stat-value {
      font-size: 24px;
      font-weight: 600;
      color: var(--primary);
    }

    .stat-change {
      font-size: 12px;
      margin-top: 5px;
    }

    .positive {
      color: var(--success);
    }

    .negative {
      color: var(--danger);
    }

    .recent-activity {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-top: 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .activity-title {
      font-size: 18px;
      margin-bottom: 20px;
      color: var(--primary);
      font-weight: 600;
    }

    .activity-item {
      display: flex;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #f3f4f6;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: var(--accent);
    }

    .activity-content {
      flex: 1;
    }

    .activity-message {
      font-size: 14px;
      margin-bottom: 5px;
    }

    .activity-time {
      font-size: 12px;
      color: #9ca3af;
    }

    @media(max-width: 768px) {
      .sidebar {
        width: 80px;
      }
      
      .sidebar h2 {
        font-size: 0;
      }
      
      .sidebar h2::after {
        content: "WR";
        font-size: 22px;
      }
      
      .menu-text {
        display: none;
      }
      
      .main {
        margin-left: 80px;
      }
      
      .toggle-sidebar {
        left: 50px;
        transform: rotate(180deg);
      }
      
      .card-container {
        grid-template-columns: 1fr;
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
  </style>
</head>
<body>

<div class="sidebar">
  <h2>Westley's Resto Cafe</h2>
  <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span></a>
  <a href="view_customers.php"><i class="fas fa-users"></i> <span class="menu-text">Customers</span></a>
  <a href="manage_menu.php"><i class="fas fa-utensils"></i> <span class="menu-text">Menu Editor</span></a>
  <a href="view_orders.php"><i class="fas fa-receipt"></i> <span class="menu-text">Orders</span></a>
  <a href="view_reservations.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Reservations</span></a>
  <a href="view_messages.php"><i class="fas fa-envelope"></i> <span class="menu-text">Messages</span></a>
  <a href="homepage.php"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span></a>
</div>

<button class="toggle-sidebar">
  <i class="fas fa-chevron-left"></i>
</button>

<div class="main">
  <div class="header">
    <h1>Admin Dashboard</h1>
    <div class="user-info">
      <div class="user-avatar">A</div>
    </div>
  </div>

  <div class="stats-container">
    <div class="stat-card animate-fade delay-1">
      <div class="stat-title">Total Customers</div>
      <div class="stat-value">1,248</div>
      <div class="stat-change positive">+12% from last month</div>
    </div>
    <div class="stat-card animate-fade delay-2">
      <div class="stat-title">Today's Orders</div>
      <div class="stat-value">47</div>
      <div class="stat-change positive">+5% from yesterday</div>
    </div>
    <div class="stat-card animate-fade delay-3">
      <div class="stat-title">Pending Orders</div>
      <div class="stat-value">8</div>
      <div class="stat-change negative">+2 from yesterday</div>
    </div>
    <div class="stat-card animate-fade delay-4">
      <div class="stat-title">Reservations</div>
      <div class="stat-value">15</div>
      <div class="stat-change positive">+3 from yesterday</div>
    </div>
  </div>

  <div class="card-container">
    <div class="card animate-fade delay-1">
      <div class="card-icon"><i class="fas fa-users"></i></div>
      <h3>Customer Details</h3>
      <p>View and manage all customer accounts and information</p>
      <a href="view_customers.php">Manage Customers</a>
    </div>
    <div class="card animate-fade delay-2">
      <div class="card-icon"><i class="fas fa-utensils"></i></div>
      <h3>Menu Management</h3>
      <p>Add, edit or remove items from your restaurant menu</p>
      <a href="manage_menu.php">Edit Menu</a>
    </div>
    <div class="card animate-fade delay-3">
      <div class="card-icon"><i class="fas fa-receipt"></i></div>
      <h3>Online Orders</h3>
      <p>View and assign delivery orders to your staff</p>
      <a href="view_orders.php">View Orders</a>
    </div>
    <div class="card animate-fade delay-4">
      <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
      <h3>Table Reservations</h3>
      <p>Manage upcoming table reservations and bookings</p>
      <a href="view_reservations.php">Check Reservations</a>
    </div>
    <div class="card animate-fade delay-5">
      <div class="card-icon"><i class="fas fa-envelope"></i></div>
      <h3>Customer Messages</h3>
      <p>Respond to customer inquiries and feedback</p>
      <a href="view_messages.php">View Messages</a>
    </div>
  </div>

  <div class="recent-activity animate-fade">
    <h3 class="activity-title">Recent Activity</h3>
    <div class="activity-item">
      <div class="activity-icon"><i class="fas fa-utensils"></i></div>
      <div class="activity-content">
        <div class="activity-message">New menu item "Truffle Pasta" added</div>
        <div class="activity-time">10 minutes ago</div>
      </div>
    </div>
    <div class="activity-item">
      <div class="activity-icon"><i class="fas fa-receipt"></i></div>
      <div class="activity-content">
        <div class="activity-message">Order #2456 marked as completed</div>
        <div class="activity-time">25 minutes ago</div>
      </div>
    </div>
    <div class="activity-item">
      <div class="activity-icon"><i class="fas fa-calendar-check"></i></div>
      <div class="activity-content">
        <div class="activity-message">New reservation for 4 people at 7:30 PM</div>
        <div class="activity-time">1 hour ago</div>
      </div>
    </div>
    <div class="activity-item">
      <div class="activity-icon"><i class="fas fa-user"></i></div>
      <div class="activity-content">
        <div class="activity-message">New customer registered: John Doe</div>
        <div class="activity-time">2 hours ago</div>
      </div>
    </div>
  </div>
</div>

<script>
  // Toggle sidebar
  document.querySelector('.toggle-sidebar').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('sidebar-collapsed');
    document.querySelector('.main').classList.toggle('main-expanded');
  });

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
</script>

</body>
</html>