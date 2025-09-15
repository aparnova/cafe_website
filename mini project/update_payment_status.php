<?php
// update_payment_status.php - Updated to handle payment failures
session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $error_description = $_POST['error_description'] ?? '';
        
        if (empty($razorpay_order_id) || empty($status)) {
            throw new Exception('Missing required parameters');
        }
        
        if ($status === 'failed') {
            // Update order status to failed
            $update_stmt = $conn->prepare("
                UPDATE orders 
                SET payment_status = 'failed',
                    status = 'Payment Failed'
                WHERE razorpay_order_id = ?
            ");
            
            $update_stmt->bind_param("s", $razorpay_order_id);
            
            if ($update_stmt->execute()) {
                // Log the error for debugging
                error_log("Payment failed for order ID: $razorpay_order_id. Error: $error_description");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment status updated to failed'
                ]);
            } else {
                throw new Exception('Failed to update payment status');
            }
        } else {
            throw new Exception('Invalid status provided');
        }
        
    } catch (Exception $e) {
        error_log("Update payment status error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Error updating payment status: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>