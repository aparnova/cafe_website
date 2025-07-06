<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['user_type'] !== 'delivery') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delivery Dashboard</title>
</head>
<body>
    <h1>Welcome, Delivery Boy!</h1>
    <p>Email: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
    <a href="logout.php">Logout</a>
</body>
</html>