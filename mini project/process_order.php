<?php
include 'db.php';
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'customer_name', 'customer_phone', 'delivery_address', 'payment_method', 'cart_items', 'total_amount'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit();
    }
}

// Validate cart items
if (!is_array($input['cart_items']) || empty($input['cart_items'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert order into orders table
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, delivery_address, phone, notes, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $notes = isset($input['order_notes']) ? $input['order_notes'] : null;
    
    $stmt->bind_param("idsss", 
        $input['user_id'],
        $input['total_amount'],
        $input['delivery_address'],
        $input['customer_phone'],
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = $conn->insert_id;
    
    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_name, item_price, quantity, subtotal, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    foreach ($input['cart_items'] as $item) {
        if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
            throw new Exception('Invalid cart item data');
        }
        
        $subtotal = $item['price'] * $item['quantity'];
        
        $stmt->bind_param("isdid",
            $order_id,
            $item['name'],
            $item['price'],
            $item['quantity'],
            $subtotal
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add order item: ' . $item['name']);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}

$conn->close();