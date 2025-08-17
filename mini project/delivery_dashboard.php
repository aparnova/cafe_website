<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Portal - Westley's Resto Café</title>
    <style>
        :root {
            --primary: #507B55;
            --primary-dark: #1E3C2E;
            --accent: #DCD4A6;
            --light: #FFFFFF;
            --gray: #f5f5f5;
            --dark-gray: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--gray);
            color: #333;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--primary-dark);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .brand {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .brand h2 {
            font-weight: 600;
            color: var(--accent);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .user-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .user-info p {
            font-size: 13px;
            opacity: 0.8;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 10px;
            background-color: rgba(255,255,255,0.1);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .page-title h1 {
            color: var(--primary-dark);
            font-size: 28px;
        }
        
        .welcome-message {
            background-color: var(--primary);
            color: white;
            padding: 30px 40px;
            border-radius: 8px;
            margin-bottom: 30px;
            width: 100%;
            max-width: 800px;
        }
        
        .welcome-message h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .welcome-message p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .stat-card p {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-card i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .action-card h3 {
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-cards,
            .action-cards {
                grid-template-columns: 1fr;
            }
            
            .welcome-message {
                padding: 20px;
            }
            
            .welcome-message h2 {
                font-size: 24px;
            }
            
            .welcome-message p {
                font-size: 16px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brand">
                <h2>Westley's Resto Café</h2>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $_SESSION['user']; ?></h3>
                    <p>Delivery Personnel</p>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <a href="login.php?logout=true" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    <h2>Welcome, <?php echo $_SESSION['user']; ?>!</h2>
                    <p>Ready to handle today's deliveries?</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Assigned Orders</h3>
                    <p>12</p>
                </div>
                <div class="stat-card">
                    <h3>Completed Today</h3>
                    <p>8</p>
                </div>
            </div>
            
            <!-- Action Cards -->
            <div class="action-cards">
                <div class="action-card">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>My Assigned Orders</h3>
                    <p>View and manage all orders currently assigned to you for delivery</p>
                    <a href="assigned_orders.php" class="btn">View Orders</a>
                </div>
                
                <div class="action-card">
                    <i class="fas fa-truck"></i>
                    <h3>Update Delivery Status</h3>
                    <p>Mark orders as picked up, in transit, or delivered</p>
                    <a href="update_delivery_status.php" class="btn">Update Status</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>