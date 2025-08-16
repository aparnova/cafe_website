<?php
session_start();
require 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_id = $userData['id'];

try {
    // Check if order exists and belongs to user
    $stmt = $conn->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT item_name, item_price, quantity 
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_items = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($order_items)) {
        echo json_encode(['success' => false, 'message' => 'No items found in order']);
        exit();
    }

    // Convert order items to cart format
    $cart_items = [];
    $item_id = 1; // Start with ID 1, will be matched by name in frontend

    foreach ($order_items as $item) {
        $cart_items[] = [
            'id' => $item_id++, // This will need to be matched with actual menu items in frontend
            'name' => $item['item_name'],
            'price' => floatval($item['item_price']),
            'quantity' => intval($item['quantity']),
            'image' => 'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg' // Default image
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $cart_items,
        'message' => 'Items loaded successfully'
    ]);

} catch (Exception $e) {
    error_log("Get order items error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load order items. Please try again.'
    ]);
}

$conn->close();
?>