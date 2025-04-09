<?php
require_once '../connect.php';
require_once '../vendor/autoload.php'; // Make sure you have Razorpay SDK installed
use Razorpay\Api\Api;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if amount is provided
if (!isset($_POST['amount']) || empty($_POST['amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Amount is required']);
    exit;
}

// Get customer data
$formData = isset($_POST['formData']) ? json_decode($_POST['formData'], true) : [];

try {
    // Log the incoming request
    error_log("Payment Request Started - Amount: " . (isset($_POST['amount']) ? $_POST['amount'] : 'not set'));
    
    // Verify database connection
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection failed');
    }

    // Initialize Razorpay API with correct keys
    $api = new Api('rzp_test_CzoNYGf1d0sXyX', 'VTpHhBQzNi3UeNF9cjEGBIU3'); // Replace with your actual secret key

    // Log the API initialization
    error_log("Razorpay API initialized with key ID: rzp_test_CzoNYGf1d0sXyX");

    // Get and validate amount
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate user is logged in
    if (!$user_id) {
        throw new Exception('User not logged in');
    }

    // Validate amount
    if ($amount <= 0) {
        throw new Exception('Invalid amount: ' . $amount);
    }

    error_log("Creating order for user: $user_id with amount: $amount");

    // First create the order in our database
    $conn->beginTransaction();

    try {
        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$user_id, $amount]);
        $order_id = $conn->lastInsertId();

        error_log("Database order created with ID: $order_id");

        // Create Razorpay order
        $orderData = [
            'amount' => (int)($amount * 100), // Convert to paise and ensure it's an integer
            'currency' => 'INR',
            'payment_capture' => 1,
            'notes' => [
                'database_order_id' => $order_id
            ]
        ];

        error_log("Creating Razorpay order with data: " . json_encode($orderData));

        $razorpayOrder = $api->order->create($orderData);
        
        error_log("Razorpay order created successfully: " . json_encode($razorpayOrder));
        
        $conn->commit();

        // Return success response
        echo json_encode([
            'status' => 'success',
            'order_id' => $razorpayOrder['id'],
            'amount' => (int)($amount * 100),
            'currency' => 'INR',
            'database_order_id' => $order_id
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in create_order.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Order creation failed: ' . $e->getMessage()
    ]);
}
?> 