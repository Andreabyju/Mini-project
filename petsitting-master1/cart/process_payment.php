<?php
session_start();
require_once "../connect.php";

// Include Razorpay SDK
require('vendor/autoload.php');
use Razorpay\Api\Api;

$api = new Api('rzp_test_CzoNYGf1d0sXyX', 'VTpHhBQzNi3UeNF9cjEGBIU3');

header('Content-Type: application/json');

try {
    // Validate the payment data
    if (!isset($_POST['razorpay_payment_id']) || !isset($_POST['razorpay_order_id']) || !isset($_POST['razorpay_signature'])) {
        throw new Exception('Missing payment information');
    }

    // Get the form data
    $formData = json_decode($_POST['formData'], true);
    if (!$formData) {
        throw new Exception('Invalid form data');
    }

    // Start transaction
    $conn->beginTransaction();

    // Insert into orders table
    $stmt = $conn->prepare("INSERT INTO orders (user_id, first_name, last_name, email, phone, address, city, state, zip, total_amount, order_date, status) 
                           VALUES (:user_id, :first_name, :last_name, :email, :phone, :address, :city, :state, :zip, :total_amount, NOW(), 'paid')");

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt->execute([
        'user_id' => $userId,
        'first_name' => $formData['first_name'],
        'last_name' => $formData['last_name'],
        'email' => $formData['email'],
        'phone' => $formData['phone'],
        'address' => $formData['address'],
        'city' => $formData['city'],
        'state' => $formData['state'],
        'zip' => $formData['zip'],
        'total_amount' => isset($_SESSION['total_amount']) ? $_SESSION['total_amount'] : 0
    ]);

    $orderId = $conn->lastInsertId();

    // Insert into payments table
    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_id, order_ref_id, signature, amount, status, payment_date) 
                           VALUES (:order_id, :payment_id, :order_ref_id, :signature, :amount, 'completed', NOW())");

    $stmt->execute([
        'order_id' => $orderId,
        'payment_id' => $_POST['razorpay_payment_id'],
        'order_ref_id' => $_POST['razorpay_order_id'],
        'signature' => $_POST['razorpay_signature'],
        'amount' => isset($_SESSION['total_amount']) ? $_SESSION['total_amount'] : 0
    ]);

    // Insert order items
    if (!empty($_SESSION['cart'])) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $productStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $productStmt->execute([$productId]);
            $price = $productStmt->fetchColumn();
            
            $stmt->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price
            ]);
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear the cart
    $_SESSION['cart'] = [];

    echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Payment Processing Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 