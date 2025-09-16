<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
        $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
        $razorpay_signature = $_POST['razorpay_signature'] ?? '';
        
        if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
            throw new Exception('Missing payment details');
        }
        
        // Replace these with your actual Razorpay keys
        $razorpay_key_secret = "5bqiEvg3UOUn1dLkDrQ5mhDN"; // Same secret as in checkout.php
        
        // Verify signature using direct method
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);
        
        if ($generated_signature !== $razorpay_signature) {
            throw new Exception('Invalid signature - payment verification failed');
        }
        
        // Signature is valid, update the order
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'paid',
                razorpay_payment_id = ?,
                status = 'Confirmed'
            WHERE razorpay_order_id = ?
        ");
        
        $update_stmt->bind_param("ss", $razorpay_payment_id, $razorpay_order_id);
        
        if ($update_stmt->execute()) {
            // Get the updated order details for cart clearing
            $order_stmt = $conn->prepare("SELECT id, user_id FROM orders WHERE razorpay_order_id = ?");
            $order_stmt->bind_param("s", $razorpay_order_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();
            
            if ($order_result->num_rows > 0) {
                $order = $order_result->fetch_assoc();
                $user_id = $order['user_id'];
                
                // Clear cart from database for the user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                    // Clear database cart
                    $clear_cart_stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
                    $clear_cart_stmt->bind_param("i", $user_id);
                    $clear_cart_stmt->execute();
                }
                
                // Clear session data after successful payment
                unset($_SESSION['cart']);
                unset($_SESSION['direct_order']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'order_id' => $order['id']
                ]);
            } else {
                throw new Exception('Order not found');
            }
        } else {
            throw new Exception('Failed to update payment status');
        }
        
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Payment verification failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>