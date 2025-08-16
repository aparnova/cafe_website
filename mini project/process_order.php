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

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'customer_name', 'customer_phone', 'delivery_address', 'cart_items', 'total_amount'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Validate cart items
if (!is_array($data['cart_items']) || count($data['cart_items']) === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit();
}

// Sanitize input data
$user_id = intval($data['user_id']);
$customer_name = trim($data['customer_name']);
$customer_phone = trim($data['customer_phone']);
$delivery_address = trim($data['delivery_address']);
$order_notes = isset($data['order_notes']) ? trim($data['order_notes']) : null;
$payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'cash_on_delivery';
$total_amount = floatval($data['total_amount']);
$cart_items = $data['cart_items'];

// Validate phone number (basic validation)
if (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $customer_phone))) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit phone number']);
    exit();
}

// Calculate total from cart items to verify
$calculated_total = 0;
foreach ($cart_items as $item) {
    if (!isset($item['price']) || !isset($item['quantity']) || !isset($item['name'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item data']);
        exit();
    }
    $calculated_total += floatval($item['price']) * intval($item['quantity']);
}

// Allow small floating point discrepancies
if (abs($calculated_total - $total_amount) > 0.01) {
    echo json_encode(['success' => false, 'message' => 'Total amount mismatch']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert into orders table
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, delivery_address, phone, notes, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->bind_param("idsss", $user_id, $total_amount, $delivery_address, $customer_phone, $order_notes);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_name, item_price, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($cart_items as $item) {
        $item_name = trim($item['name']);
        $item_price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        $subtotal = $item_price * $quantity;
        
        $stmt->bind_param("isdid", $order_id, $item_name, $item_price, $quantity, $subtotal);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add order item: " . $stmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the order (optional)
    error_log("New order placed - Order ID: $order_id, User: {$_SESSION['user']}, Total: $total_amount");
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Log the error
    error_log("Order processing error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to process order. Please try again.'
    ]);
}

$conn->close();
?>