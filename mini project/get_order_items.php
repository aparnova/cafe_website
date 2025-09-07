<?php
session_start();
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

// ✅ Enable detailed error reporting during debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Allow only delivery boys to fetch order items
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// ✅ Get delivery boy ID and order ID
$delivery_id = isset($_SESSION['delivery_id']) ? (int)$_SESSION['delivery_id'] : 0;
$order_id    = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0 || $delivery_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// ✅ Check if the order is assigned to this delivery boy
$verify = $conn->prepare("SELECT id FROM orders WHERE id = ? AND assigned_to = ?");
$verify->bind_param("ii", $order_id, $delivery_id);
$verify->execute();
$result = $verify->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
    exit();
}

// ✅ Fetch ordered items with names & prices from menu_items
$sql = "
    SELECT 
        oi.quantity,
        oi.price,
        mi.name
    FROM order_items oi
    INNER JOIN menu_items mi ON mi.id = oi.menu_id
    WHERE oi.order_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit();
}

$res = $stmt->get_result();
$items = [];
$totalAmount = 0;

while ($row = $res->fetch_assoc()) {
    $itemTotal = (float)$row['price'] * (int)$row['quantity'];
    $totalAmount += $itemTotal;

    $items[] = [
        'name'     => $row['name'],
        'quantity' => (int)$row['quantity'],
        'price'    => (float)$row['price'],
        'subtotal' => $itemTotal
    ];
}

// ✅ Return a valid JSON response
echo json_encode([
    'success' => true,
    'items' => $items,
    'total' => $totalAmount
]);
