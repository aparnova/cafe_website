<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['order_id'], $_POST['delivery_boy_id'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_boy_id = intval($_POST['delivery_boy_id']);

    $stmt = $conn->prepare("UPDATE orders SET assigned_to = ?, status = 'Processing' WHERE id = ?");
    $stmt->bind_param("ii", $delivery_boy_id, $order_id);
    $stmt->execute();

    header("Location: view_orders.php");
    exit();
}
?>
