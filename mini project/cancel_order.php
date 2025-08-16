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

if (!$data || !isset($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$order_id = intval($data['order_id']);

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
        SELECT id, status, total_amount 
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

    // Check if order can be cancelled
    $cancellable_statuses = ['pending', 'confirmed'];
    if (!in_array($order['status'], $cancellable_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
        exit();
    }

    // Update order status to cancelled
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if ($stmt->execute()) {
        // Log the cancellation
        error_log("Order cancelled - Order ID: $order_id, User: {$_SESSION['user']}, Amount: {$order['total_amount']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled successfully'
        ]);
    } else {
        throw new Exception("Failed to cancel order: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Order cancellation error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to cancel order. Please try again.'
    ]);
}

$conn->close();
?>