<?php
/**
 * Payment related helper functions
 */

/**
 * Formats amount to standard currency format
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Converts amount to Razorpay format (paise)
 * @param float $amount
 * @return int
 */
function convertToPaise($amount) {
    return (int)($amount * 100);
}

/**
 * Converts amount from Razorpay format (paise) to rupees
 * @param int $amount
 * @return float
 */
function convertToRupees($amount) {
    return $amount / 100;
}

/**
 * Inserts payment data into the database
 * @param PDO $conn
 * @param int $order_id
 * @param float $amount
 * @param string $razorpay_payment_id
 * @param string $status
 * @return bool
 */
function insertPaymentData($conn, $order_id, $amount, $razorpay_payment_id, $status = 'completed') {
    try {
        $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_amount, payment_status, razorpay_payment_id) 
                               VALUES (:order_id, :amount, :status, :razorpay_payment_id)");
        
        $stmt->execute([
            ':order_id' => $order_id,
            ':amount' => $amount,
            ':status' => $status,
            ':razorpay_payment_id' => $razorpay_payment_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Payment insertion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates order status after payment
 * @param PDO $conn
 * @param int $order_id
 * @param string $status
 * @return bool
 */
function updateOrderStatus($conn, $order_id, $status) {
    try {
        $stmt = $conn->prepare("UPDATE orders SET payment_status = :status, 
                               updated_at = CURRENT_TIMESTAMP 
                               WHERE order_id = :order_id");
        
        $stmt->execute([
            ':status' => $status,
            ':order_id' => $order_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Order status update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validates payment amount
 * @param float $expected_amount
 * @param float $paid_amount
 * @return bool
 */
function validatePaymentAmount($expected_amount, $paid_amount) {
    // Convert both to paise for comparison
    $expected = convertToPaise($expected_amount);
    $paid = convertToPaise($paid_amount);
    return $expected === $paid;
}

/**
 * Generates a unique transaction reference
 * @return string
 */
function generateTransactionReference() {
    return 'TXN' . time() . rand(1000, 9999);
}

/**
 * Records payment failure
 * @param PDO $conn
 * @param int $order_id
 * @param string $error_message
 * @return bool
 */
function recordPaymentFailure($conn, $order_id, $error_message) {
    try {
        $stmt = $conn->prepare("INSERT INTO payment_failures (order_id, error_message, created_at) 
                               VALUES (:order_id, :error_message, CURRENT_TIMESTAMP)");
        
        $stmt->execute([
            ':order_id' => $order_id,
            ':error_message' => $error_message
        ]);
        
        // Update order status to failed
        updateOrderStatus($conn, $order_id, 'failed');
        
        return true;
    } catch (PDOException $e) {
        error_log("Payment failure recording error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets payment status for an order
 * @param PDO $conn
 * @param int $order_id
 * @return string|null
 */
function getPaymentStatus($conn, $order_id) {
    try {
        $stmt = $conn->prepare("SELECT payment_status FROM payments 
                               WHERE order_id = :order_id 
                               ORDER BY created_at DESC LIMIT 1");
        
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Payment status fetch error: " . $e->getMessage());
        return null;
    }
}

/**
 * Sends payment confirmation email
 * @param string $email
 * @param array $orderDetails
 * @return bool
 */
function sendPaymentConfirmation($email, $orderDetails) {
    try {
        $subject = "Payment Confirmation - Order #{$orderDetails['order_id']}";
        
        $message = "Dear {$orderDetails['customer_name']},\n\n";
        $message .= "Thank you for your payment. Your order has been confirmed.\n\n";
        $message .= "Order Details:\n";
        $message .= "Order ID: {$orderDetails['order_id']}\n";
        $message .= "Amount Paid: " . formatCurrency($orderDetails['amount']) . "\n";
        $message .= "Transaction ID: {$orderDetails['razorpay_payment_id']}\n\n";
        $message .= "Thank you for shopping with us!\n\n";
        $message .= "Best regards,\nThe Canine & Feline Co.";
        
        $headers = "From: noreply@caninefeline.com\r\n";
        $headers .= "Reply-To: support@caninefeline.com\r\n";
        
        return mail($email, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validates payment signature from Razorpay
 * @param string $razorpay_order_id
 * @param string $razorpay_payment_id
 * @param string $razorpay_signature
 * @param string $key_secret
 * @return bool
 */
function validatePaymentSignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature, $key_secret) {
    try {
        // The correct signature generation string format
        $text = $razorpay_payment_id . "|" . $razorpay_order_id;
        $generated_signature = hash_hmac('sha256', $text, $key_secret);
        
        error_log("Generated Signature: " . $generated_signature);
        error_log("Received Signature: " . $razorpay_signature);
        error_log("Text used for signature: " . $text);
        
        return hash_equals($razorpay_signature, $generated_signature);
    } catch (Exception $e) {
        error_log("Signature validation error: " . $e->getMessage());
        return false;
    }
} 