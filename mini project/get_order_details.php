<?php
include 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID not provided']);
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details
$order_query = "
    SELECT o.*, u.fullname as customer_name, db.fullname as delivery_boy_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN delivery_boys db ON o.delivery_boy_id = db.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];

while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
?>