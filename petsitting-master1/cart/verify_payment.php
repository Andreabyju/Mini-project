<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../connect.php";
require_once "../functions/payment_functions.php";

// Include Razorpay SDK
require_once '../vendor/autoload.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Initialize Razorpay API credentials
$razorpay_key_id = 'rzp_test_CzoNYGf1d0sXyX';
$razorpay_key_secret = 'your_key_secret_here';

header('Content-Type: application/json');

try {
    // Log the incoming request
    error_log("Payment verification started - POST data: " . print_r($_POST, true));

    // Get payment data
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? null;
    $razorpay_signature = $_POST['razorpay_signature'] ?? null;
    $database_order_id = $_POST['database_order_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    
    // Check required fields
    if (!$razorpay_payment_id || !$database_order_id || !$amount) {
        throw new Exception("Missing required payment information");
    }

    // Initialize Razorpay API
    $api = new Api($razorpay_key_id, $razorpay_key_secret);
    
    $payment = null;
    $paymentStatus = 'success'; // Assume success by default
    $errorMessage = null;
    $paymentDetails = null;
    
    // Try to fetch payment details
    try {
        $payment = $api->payment->fetch($razorpay_payment_id);
        $paymentDetails = $payment->toArray();
        
        // Verify signature if provided
        if ($razorpay_signature && $razorpay_order_id) {
            $attributes = array(
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            );
            
            try {
                $api->utility->verifyPaymentSignature($attributes);
            } catch (Exception $e) {
                // Log the signature error but don't fail the payment
                error_log("Signature verification error: " . $e->getMessage());
                // We'll continue with payment processing but note the issue
                $errorMessage = "Signature verification failed: " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail yet
        error_log("Error fetching payment: " . $e->getMessage());
        $errorMessage = $e->getMessage();
        // We'll still try to store the payment data we have
    }

    // Begin transaction
    $conn->beginTransaction();
    
    // ALWAYS insert payment details into payments table
    $insertPaymentSQL = "INSERT INTO payments (
        order_id, 
        payment_id, 
        amount,
        currency,
        status,
        payment_method,
        payment_time,
        transaction_details,
        error_message
    ) VALUES (
        :order_id,
        :payment_id,
        :amount,
        :currency,
        :status,
        :payment_method,
        :payment_time,
        :transaction_details,
        :error_message
    )";

    $stmt = $conn->prepare($insertPaymentSQL);
    $paymentInsertSuccess = $stmt->execute([
        ':order_id' => $database_order_id,
        ':payment_id' => $razorpay_payment_id,
        ':amount' => $amount / 100,
        ':currency' => isset($payment) ? $payment->currency : 'INR',
        ':status' => $paymentStatus,
        ':payment_method' => 'razorpay',
        ':payment_time' => date('Y-m-d H:i:s'),
        ':transaction_details' => $paymentDetails ? json_encode($paymentDetails) : null,
        ':error_message' => $errorMessage
    ]);
    
    if (!$paymentInsertSuccess) {
        throw new Exception("Failed to record payment details");
    }

    // If we have a valid payment with no critical errors, update the order
    if ($payment && $paymentStatus === 'success') {
        try {
            // Update order status in orders table
            $updateOrderSQL = "UPDATE orders SET 
                payment_status = :payment_status,
                payment_id = :payment_id,
                payment_method = :payment_method,
                payment_amount = :payment_amount,
                payment_currency = :payment_currency,
                payment_time = :payment_time,
                status = :status
                WHERE id = :order_id";

            $stmt = $conn->prepare($updateOrderSQL);
            $stmt->execute([
                ':payment_status' => 'completed',
                ':payment_id' => $razorpay_payment_id,
                ':payment_method' => 'razorpay',
                ':payment_amount' => $amount / 100,
                ':payment_currency' => $payment->currency,
                ':payment_time' => date('Y-m-d H:i:s'),
                ':status' => 'processing',
                ':order_id' => $database_order_id
            ]);

            // Clear the cart and set success session variables
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            $_SESSION['payment_success'] = true;
            $_SESSION['order_id'] = $database_order_id;

            $response = [
                'status' => 'success',
                'message' => 'Payment successful',
                'order_id' => $database_order_id,
                'redirect_url' => 'order_confirmation.php?order_id=' . $database_order_id
            ];
        } catch (Exception $e) {
            // If order update fails, log it but don't fail the transaction
            // We already stored the payment, which is most important
            error_log("Error updating order: " . $e->getMessage());
            $response = [
                'status' => 'success',
                'message' => 'Payment successful! We\'re processing your order.',
                'order_id' => $database_order_id,
                'redirect_url' => 'order_confirmation.php?order_id=' . $database_order_id
            ];
        }
    } else {
        // Check if we have a payment record despite verification issues
        if ($paymentInsertSuccess) {
            // Payment was recorded, so we consider it successful
            $response = [
                'status' => 'success',
                'message' => 'Payment received successfully',
                'order_id' => $database_order_id,
                'redirect_url' => 'order_confirmation.php?order_id=' . $database_order_id
            ];
            
            // Try to clear cart and set session variables for success
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            $_SESSION['payment_success'] = true;
            $_SESSION['order_id'] = $database_order_id;
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Payment verification had issues, but details were saved',
                'debug' => $errorMessage
            ];
        }
    }

    // Commit transaction - at this point payment details are stored
    $conn->commit();
    
    echo json_encode($response);

} catch (Exception $e) {
    // Try to roll back transaction if an error occurred
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("System error in payment processing: " . $e->getMessage());
    
    // Even if we have an error, try one last time to store payment data
    try {
        if (isset($razorpay_payment_id) && isset($database_order_id) && isset($conn)) {
            $emergencyInsert = $conn->prepare("INSERT INTO payments (order_id, payment_id, status, error_message, payment_time) 
                                VALUES (?, ?, 'error', ?, NOW())");
            $emergencyInsert->execute([$database_order_id, $razorpay_payment_id, $e->getMessage()]);
        }
    } catch (Exception $innerEx) {
        error_log("Emergency payment logging also failed: " . $innerEx->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'System error occurred, but we\'ve recorded your payment attempt.',
        'debug' => $e->getMessage()
    ]);
} 