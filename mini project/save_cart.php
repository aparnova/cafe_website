<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the cart data from POST request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['cart']) || !is_array($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
    exit;
}

$cart = $input['cart'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, clear existing cart items for this user
    $stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Insert new cart items
    if (!empty($cart)) {
        $stmt = $conn->prepare("INSERT INTO user_cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
        
        foreach ($cart as $item) {
            if (isset($item['id']) && isset($item['quantity']) && $item['quantity'] > 0) {
                $stmt->bind_param("iii", $user_id, $item['id'], $item['quantity']);
                $stmt->execute();
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Cart saved successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Error saving cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save cart']);
}

$conn->close();
?>