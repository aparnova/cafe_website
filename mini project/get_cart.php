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

try {
    // Get cart items with menu item details
    $stmt = $conn->prepare("
        SELECT 
            uc.menu_item_id as id,
            mi.name,
            mi.price,
            mi.image,
            uc.quantity
        FROM user_cart uc
        JOIN menu_items mi ON uc.menu_item_id = mi.id
        WHERE uc.user_id = ?
        ORDER BY uc.created_at DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        // Convert to the format expected by JavaScript
        $cart[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'image' => $row['image'] ?: 'https://via.placeholder.com/300x180/29261f/cda45e?text=No+Image'
        ];
    }
    
    echo json_encode(['success' => true, 'cart' => $cart]);
    
} catch (Exception $e) {
    error_log("Error loading cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load cart']);
}

$conn->close();
?>