<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delivery Dashboard - Westley's Resto Café</title>
    <style>
        body {
            font-family: Arial;
            background-color: #DCD4A6;
            margin: 0;
            padding: 0;
        }
        .dashboard {
            padding: 40px;
        }
        .dashboard h1 {
            color: #1E3C2E;
        }
        .card {
            background-color: #507B55;
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
        <h1>Welcome Delivery Boy, <?php echo $_SESSION['user']; ?>!</h1>

        <div class="card"><a href="assigned_orders.php">View My Assigned Orders</a></div>
        <div class="card"><a href="update_delivery_status.php">Update Delivery Status</a></div>
        <div class="card"><a href="login.php">Logout</a></div>
    </div>
</body>
</html>