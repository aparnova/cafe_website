<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Westley's Resto Café</title>
    <style>
        body {
            font-family: Arial;
            background-color: #E3CBA5;
            margin: 0;
            padding: 0;
        }
        .dashboard {
            padding: 40px;
        }
        .dashboard h1 {
            color: #0C1E17;
        }
        .card {
            background-color: #F7A25E;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            color: white;
        }
        a {
            text-decoration: none;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Welcome Admin, <?php echo $_SESSION['user']; ?>!</h1>

        <div class="card"><a href="view_customers.php">View Registered Customers</a></div>
        <div class="card"><a href="view_delivery_boys.php">Manage Delivery Boys</a></div>
        <div class="card"><a href="assign_orders.php">Assign Orders to Delivery Boys</a></div>
        <div class="card"><a href="view_orders.php">View All Orders</a></div>
        <div class="card"><a href="login.php">Logout</a></div>
    </div>
</body>
</html>